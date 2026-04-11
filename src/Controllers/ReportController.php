<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

// ============================================================
// ReportController
// ============================================================
// Generates monthly sales summaries and PDF exports.
//
// Routes:
//   GET ?page=reports                                    → index()
//   GET ?page=reports&action=pdf&month=M&year=Y          → pdf()
// ============================================================

class ReportController
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────
    // index() — Monthly report page (or yearly when month = 0)
    // GET ?page=reports[&month=M&year=Y&customer_id=X&category_id=X&product_id=X]
    // month = 0 means yearly summary
    // ──────────────────────────────────────────────────────────
    public function index(): void
    {
        $month      = (int) get('month', (int) date('m'));
        $year       = (int) get('year',  (int) date('Y'));
        $customerId = (int) get('customer_id', 0);
        $categoryId = (int) get('category_id', 0);
        $productId  = (int) get('product_id', 0);

        $reportData = $this->buildReportData($month, $year, $customerId, $categoryId, $productId);
        $years      = range((int) date('Y') - 2, (int) date('Y') + 1);
        $customers  = $this->pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
        $categories = $this->pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
        
        // Fetch all products for dynamic filtering
        $allProducts = $this->pdo->query("SELECT id, name, category_id FROM products ORDER BY name")->fetchAll();
        
        // Convert to JSON for JavaScript filtering
        $allProductsJson = json_encode(array_map(fn($p) => [
            'id' => $p['id'],
            'name' => $p['name'],
            'category_id' => (int)($p['category_id'] ?? 0)
        ], $allProducts));

        render('reports/monthly', array_merge($reportData, compact('month', 'year', 'years', 'customers', 'categories', 'allProducts', 'allProductsJson', 'customerId', 'categoryId', 'productId')));
    }

    // ──────────────────────────────────────────────────────────
    // pdf() — Generate and stream a PDF report
    // GET ?page=reports&action=pdf&month=M&year=Y&customer_id=X&category_id=X&product_id=X
    // ──────────────────────────────────────────────────────────
    public function pdf(): void
    {
        $month      = (int) get('month', (int) date('m'));
        $year       = (int) get('year',  (int) date('Y'));
        $customerId = (int) get('customer_id', 0);
        $categoryId = (int) get('category_id', 0);
        $productId  = (int) get('product_id', 0);

        $reportData = $this->buildReportData($month, $year, $customerId, $categoryId, $productId);

        // TCPDF autoload check
        if (!class_exists('TCPDF')) {
            setFlash('danger', 'ไม่พบ TCPDF กรุณารัน <code>composer install</code> ก่อน');
            redirect(url(['page' => 'reports', 'month' => $month, 'year' => $year, 'category_id' => $categoryId]));
        }

        $this->generatePdf($month, $year, $reportData);
    }

    // ============================================================
    // Private Helpers
    // ============================================================

    /**
     * Query the database and build report data arrays.
     *
     * @param int $month       1–12 (0 = yearly)
     * @param int $year        e.g. 2025
     * @param int $categoryId   0 = all categories
     * @return array{
     *   rows: list<array<string,mixed>>,
     *   grandTotalQty: int,
     *   grandTotalAmount: float,
     *   grandWt1Qty: int,
     *   grandWt1Amount: float,
     *   grandWt2Qty: int,
     *   grandWt2Amount: float,
     *   dailySales: list<array<string,mixed>>,
     *   workTypes: list<array<string,mixed>>,
     *   isYearly: bool
     * }
     */
    private function buildReportData(int $month, int $year, int $customerId = 0, int $categoryId = 0, int $productId = 0): array
    {
        $isYearly = ($month === 0);

        // ── Per-product summary ────────────────────────
        $whereDate = $isYearly
            ? "AND YEAR(s.sale_date) = :year"
            : "AND MONTH(s.sale_date) = :month AND YEAR(s.sale_date) = :year";

        $whereCustomer = ($customerId > 0)
            ? "AND s.customer_id = :customer_id"
            : "";

        $whereCategory = ($categoryId > 0)
            ? "AND p.category_id = :category_id"
            : "";

        $whereProduct = ($productId > 0)
            ? "AND p.id = :product_id"
            : "";

        $stmt = $this->pdo->prepare("
            SELECT
                p.id                                                              AS product_id,
                p.name                                                            AS product_name,
                p.category_id,
                c.name                                                            AS category_name,

                COALESCE(SUM(s.quantity), 0)                                      AS total_qty,
                COALESCE(SUM(s.price),    0)                                      AS total_amount,

                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.quantity ELSE 0 END), 0) AS wt1_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.price    ELSE 0 END), 0) AS wt1_amount,

                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.quantity ELSE 0 END), 0) AS wt2_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.price    ELSE 0 END), 0) AS wt2_amount
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN sales s
                   ON s.product_id = p.id
                  {$whereDate}
                  {$whereCustomer}
                  {$whereCategory}
                  {$whereProduct}
            GROUP BY p.id, p.name, p.category_id, c.name
            ORDER BY p.name ASC
        ");

        $params = [':year' => $year];
        if (!$isYearly) {
            $params[':month'] = $month;
        }
        if ($customerId > 0) {
            $params[':customer_id'] = $customerId;
        }
        if ($categoryId > 0) {
            $params[':category_id'] = $categoryId;
        }
        if ($productId > 0) {
            $params[':product_id'] = $productId;
        }

        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        // ── Grand totals ────────────────────────────────────
        $grandTotalQty    = 0;
        $grandTotalAmount = 0.0;
        $grandWt1Qty      = 0;
        $grandWt1Amount   = 0.0;
        $grandWt2Qty      = 0;
        $grandWt2Amount   = 0.0;

        foreach ($rows as $row) {
            $grandTotalQty    += (int)   $row['total_qty'];
            $grandTotalAmount += (float) $row['total_amount'];
            $grandWt1Qty      += (int)   $row['wt1_qty'];
            $grandWt1Amount   += (float) $row['wt1_amount'];
            $grandWt2Qty      += (int)   $row['wt2_qty'];
            $grandWt2Amount   += (float) $row['wt2_amount'];
        }

        // ── Daily sales detail (for the detail section) ─────
        $dailyWhereDate = $isYearly
            ? "WHERE YEAR(s.sale_date) = :year"
            : "WHERE MONTH(s.sale_date) = :month AND YEAR(s.sale_date) = :year";

        $dailyWhereCustomer = ($customerId > 0)
            ? "AND s.customer_id = :customer_id"
            : "";

        $dailyWhereCategory = ($categoryId > 0)
            ? "AND p.category_id = :category_id"
            : "";

        $dailyWhereProduct = ($productId > 0)
            ? "AND p.id = :product_id"
            : "";

        $dailyStmt = $this->pdo->prepare("
            SELECT
                s.id,
                s.sale_date,
                s.quantity,
                s.price,
                s.note,
                p.name  AS product_name,
                c.name  AS customer_name,
                wt.id   AS work_type_id,
                wt.name AS work_type_name
            FROM  sales      s
            JOIN  products   p  ON p.id  = s.product_id
            JOIN  customers  c  ON c.id  = s.customer_id
            JOIN  work_types wt ON wt.id = s.work_type_id
            {$dailyWhereDate}
            {$dailyWhereCustomer}
            {$dailyWhereCategory}
            {$dailyWhereProduct}
            ORDER BY s.sale_date ASC, s.id ASC
        ");

        $dailyParams = [':year' => $year];
        if (!$isYearly) {
            $dailyParams[':month'] = $month;
        }
        if ($customerId > 0) {
            $dailyParams[':customer_id'] = $customerId;
        }
        if ($categoryId > 0) {
            $dailyParams[':category_id'] = $categoryId;
        }
        if ($productId > 0) {
            $dailyParams[':product_id'] = $productId;
        }

        $dailyStmt->execute($dailyParams);
        $dailySales = $dailyStmt->fetchAll();

        // ── Work types (for column headers) ─────────────────
        $workTypes = $this->pdo->query("SELECT * FROM work_types ORDER BY id")->fetchAll();

        return compact(
            'rows',
            'grandTotalQty',
            'grandTotalAmount',
            'grandWt1Qty',
            'grandWt1Amount',
            'grandWt2Qty',
            'grandWt2Amount',
            'dailySales',
            'workTypes',
            'isYearly'
        );
    }

    /**
     * Build and stream the PDF to the browser.
     *
     * @param int   $month
     * @param int   $year
     * @param array $data   Output of buildReportData()
     */
    private function generatePdf(int $month, int $year, array $data): void
    {
        extract($data, EXTR_SKIP);

        $buddhistYear = toBuddhistYear($year);
        $monthName    = thaiMonthName($month);
        $reportTitle  = "รายงานยอดขายประจำเดือน {$monthName} {$buddhistYear}";
        $generatedAt  = formatDateTime(date('Y-m-d H:i:s'));

        // ── Instantiate TCPDF ────────────────────────────────
        $pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

        // ── Add Thai-compatible font (NotoSerifCJK) ──────────
        // This font supports Thai, Chinese, Japanese, Korean
        $fontName = 'NotoSerifCJK';
        $thaiFont = '/usr/share/fonts/opentype/noto/NotoSerifCJK-Regular.ttc';
        
        // Try to add the font
        if (!file_exists($thaiFont)) {
            // Fallback to NotoSansCJK if Serif not found
            $fontName = 'NotoSansCJK';
            $thaiFont = '/usr/share/fonts/opentype/noto/NotoSansCJK-Regular.ttc';
        }
        
        // If neither exists, fallback to dejavusans
        if (!file_exists($thaiFont)) {
            $fontName = 'dejavusans';
        } else {
            // Add the TTF font to TCPDF
            try {
                $pdf->AddFont($fontName, '', $thaiFont);
            } catch (\Exception $e) {
                // If font adding fails, fallback to dejavusans
                $fontName = 'dejavusans';
            }
        }

        // ── Document metadata ────────────────────────────────
        $pdf->SetCreator('Sales Tracker');
        $pdf->SetAuthor('Sales Tracker');
        $pdf->SetTitle($reportTitle);
        $pdf->SetSubject("Monthly Sales Report {$monthName} {$year}");
        $pdf->SetKeywords('sales, report, monthly');

        // ── Margins & auto page break ────────────────────────
        $pdf->SetMargins(15, 20, 15);
        $pdf->SetHeaderMargin(5);
        $pdf->SetFooterMargin(10);
        $pdf->SetAutoPageBreak(true, 20);

        // ── Header ───────────────────────────────────────────
        $pdf->setHeaderData('', 0, $reportTitle, 'สร้างเมื่อ: ' . $generatedAt, [44, 62, 80], [44, 62, 80]);
        $pdf->setHeaderFont([$fontName, '', 12]);
        $pdf->setFooterFont([$fontName, '', 9]);

        // ── Footer ───────────────────────────────────────────
        $pdf->setFooterData([44, 62, 80], [44, 62, 80]);

        // ── Font ─────────────────────────────────────────────
        // Use Thai-compatible font (NotoSerifCJK or fallback)
        $pdf->SetFont($fontName, '', 10);

        // ── Add first page ───────────────────────────────────
        $pdf->AddPage();

        // ════════════════════════════════════════════════════
        // Section 1: Product Summary Table
        // ════════════════════════════════════════════════════
        $this->pdfSectionTitle($pdf, '1. สรุปยอดขายรายสินค้า', $fontName);

        // Table header
        $colWidths = [60, 30, 35, 30, 35, 30, 40];
        $headers   = [
            'ชื่อสินค้า',
            'จำนวน (ชิ้น)\nขายเฉพาะเครื่อง',
            'ยอดขาย (บาท)\nขายเฉพาะเครื่อง',
            'จำนวน (ชิ้น)\nพร้อมติดตั้ง',
            'ยอดขาย (บาท)\nพร้อมติดตั้ง',
            'รวมจำนวน\n(ชิ้น)',
            'รวมยอดขาย\n(บาท)',
        ];

        $pdf->SetFont($fontName, 'B', 9);
        $pdf->SetFillColor(44, 62, 80);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->SetDrawColor(200, 200, 200);
        $pdf->SetLineWidth(0.3);

        foreach ($headers as $i => $header) {
            $pdf->MultiCell(
                $colWidths[$i], 14, $header,
                1, 'C', true, 0,
                '', '', true, 0, false, true, 14, 'M'
            );
        }
        $pdf->Ln();

        // Table rows
        $pdf->SetFont($fontName, '', 9);
        $pdf->SetTextColor(30, 30, 30);
        $rowBg = false;

        foreach ($rows as $row) {
            $pdf->SetFillColor($rowBg ? 245 : 255, $rowBg ? 245 : 255, $rowBg ? 245 : 255);
            $rowBg = !$rowBg;

            $cells = [
                $row['product_name'],
                number_format((int) $row['wt1_qty']),
                number_format((float) $row['wt1_amount'], 2),
                number_format((int) $row['wt2_qty']),
                number_format((float) $row['wt2_amount'], 2),
                number_format((int) $row['total_qty']),
                number_format((float) $row['total_amount'], 2),
            ];
            $aligns = ['L', 'R', 'R', 'R', 'R', 'R', 'R'];

            foreach ($cells as $ci => $cell) {
                $pdf->MultiCell(
                    $colWidths[$ci], 8, $cell,
                    1, $aligns[$ci], true, 0,
                    '', '', true, 0, false, true, 8, 'M'
                );
            }
            $pdf->Ln();
        }

        // Grand total row
        $pdf->SetFont($fontName, 'B', 9);
        $pdf->SetFillColor(230, 240, 250);
        $pdf->SetTextColor(44, 62, 80);

        $grandCells = [
            'รวมทั้งหมด',
            number_format($grandWt1Qty),
            number_format($grandWt1Amount, 2),
            number_format($grandWt2Qty),
            number_format($grandWt2Amount, 2),
            number_format($grandTotalQty),
            number_format($grandTotalAmount, 2),
        ];
        $grandAligns = ['L', 'R', 'R', 'R', 'R', 'R', 'R'];

        foreach ($grandCells as $ci => $cell) {
            $pdf->MultiCell(
                $colWidths[$ci], 9, $cell,
                1, $grandAligns[$ci], true, 0,
                '', '', true, 0, false, true, 9, 'M'
            );
        }
        $pdf->Ln(14);

        // ════════════════════════════════════════════════════
        // Section 2: Daily Sales Detail
        // ════════════════════════════════════════════════════
        $this->pdfSectionTitle($pdf, '2. รายละเอียดการขายรายวัน', $fontName);

        if (empty($dailySales)) {
            $pdf->SetFont($fontName, '', 10);
            $pdf->SetTextColor(120, 120, 120);
            $pdf->Cell(0, 10, 'ไม่มีรายการขายในเดือนนี้', 0, 1, 'C');
        } else {
            // Detail table header
            $dColWidths = [12, 35, 45, 45, 50, 20, 35, 18];
            $dHeaders   = ['#', 'วันที่', 'ลูกค้า', 'สินค้า', 'ประเภทงาน', 'จำนวน', 'ยอด (บาท)', 'หมายเหตุ'];

            $pdf->SetFont($fontName, 'B', 8);
            $pdf->SetFillColor(44, 62, 80);
            $pdf->SetTextColor(255, 255, 255);

            foreach ($dHeaders as $di => $dh) {
                $pdf->MultiCell(
                    $dColWidths[$di], 9, $dh,
                    1, 'C', true, 0,
                    '', '', true, 0, false, true, 9, 'M'
                );
            }
            $pdf->Ln();

            $pdf->SetFont($fontName, '', 8);
            $pdf->SetTextColor(30, 30, 30);
            $rowBg2 = false;

            foreach ($dailySales as $idx => $sale) {
                $pdf->SetFillColor($rowBg2 ? 248 : 255, $rowBg2 ? 248 : 255, $rowBg2 ? 255 : 255);
                $rowBg2 = !$rowBg2;

                $dCells = [
                    (string) ($idx + 1),
                    formatDate($sale['sale_date']),
                    $sale['customer_name'],
                    $sale['product_name'],
                    $sale['work_type_name'],
                    number_format((int) $sale['quantity']),
                    number_format((float) $sale['price'], 2),
                    $sale['note'] ?? '',
                ];
                $dAligns = ['C', 'C', 'L', 'L', 'L', 'R', 'R', 'L'];

                foreach ($dCells as $ci => $cell) {
                    $pdf->MultiCell(
                        $dColWidths[$ci], 7, $cell,
                        1, $dAligns[$ci], true, 0,
                        '', '', true, 0, false, true, 7, 'M'
                    );
                }
                $pdf->Ln();
            }

            // Detail subtotals
            $pdf->SetFont($fontName, 'B', 8);
            $pdf->SetFillColor(230, 240, 250);
            $pdf->SetTextColor(44, 62, 80);

            $detailTotalQty    = array_sum(array_column($dailySales, 'quantity'));
            $detailTotalAmount = array_sum(array_column($dailySales, 'price'));

            $subtotalCells = ['', '', '', '', 'รวม', number_format($detailTotalQty), number_format($detailTotalAmount, 2), ''];
            $subtotalAligns = ['C', 'C', 'L', 'L', 'R', 'R', 'R', 'L'];

            foreach ($subtotalCells as $ci => $cell) {
                $pdf->MultiCell(
                    $dColWidths[$ci], 8, $cell,
                    1, $subtotalAligns[$ci], true, 0,
                    '', '', true, 0, false, true, 8, 'M'
                );
            }
            $pdf->Ln();
        }

        // ── Stream to browser ────────────────────────────────
        $filename = "sales_report_{$year}_{$month}.pdf";
        $pdf->Output($filename, 'D'); // 'D' = force download, use 'I' for inline
        exit;
    }

    /**
     * Render a section title in the PDF.
     */
    private function pdfSectionTitle(\TCPDF $pdf, string $title, string $fontName = 'dejavusans'): void
    {
        $pdf->SetFont($fontName, 'B', 11);
        $pdf->SetTextColor(44, 62, 80);
        $pdf->SetFillColor(236, 240, 245);
        $pdf->Cell(0, 9, $title, 0, 1, 'L', true);
        $pdf->Ln(3);
    }
}
