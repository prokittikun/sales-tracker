<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

// ============================================================
// SaleController
// Handles all CRUD operations for daily sales records
// ============================================================

class SaleController
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────
    // index() — List sales, filterable by month + year + product + category
    // GET ?page=sales[&month=M&year=Y&product_id=X&work_type_id=X&category_id=X&customer_id=X]
    // ──────────────────────────────────────────────────────────
    public function index(): void
    {
        $month       = (int) get('month',       (int) date('m'));
        $year        = (int) get('year',        (int) date('Y'));
        $productId   = (int) get('product_id',  0);
        $workTypeId  = (int) get('work_type_id', 0);
        $categoryId  = (int) get('category_id', 0);
        $customerId  = (int) get('customer_id', 0);

        $isYearly = ($month === 0);

        // Build dynamic WHERE clauses
        $where  = $isYearly ? ['YEAR(s.sale_date) = :year'] : ['MONTH(s.sale_date) = :month', 'YEAR(s.sale_date) = :year'];
        $params = [':year' => $year];
        if (!$isYearly) {
            $params[':month'] = $month;
        }

        if ($productId > 0) {
            $where[]              = 's.product_id = :product_id';
            $params[':product_id'] = $productId;
        }
        if ($workTypeId > 0) {
            $where[]               = 's.work_type_id = :work_type_id';
            $params[':work_type_id'] = $workTypeId;
        }
        if ($categoryId > 0) {
            $where[]              = 'p.category_id = :category_id';
            $params[':category_id'] = $categoryId;
        }
        if ($customerId > 0) {
            $where[]              = 's.customer_id = :customer_id';
            $params[':customer_id'] = $customerId;
        }

        $whereClause = 'WHERE ' . implode(' AND ', $where);

        $sql = "
            SELECT
                s.id,
                s.sale_date,
                s.quantity,
                s.price,
                s.note,
                s.created_at,
                p.id   AS product_id,
                p.name AS product_name,
                p.category_id,
                c.id   AS category_id,
                c.name AS category_name,
                cu.id   AS customer_id,
                cu.name AS customer_name,
                wt.id   AS work_type_id,
                wt.name AS work_type_name
            FROM  sales      s
            JOIN  products   p  ON p.id  = s.product_id
            LEFT JOIN categories c ON c.id = p.category_id
            JOIN  customers  cu ON cu.id = s.customer_id
            JOIN  work_types wt ON wt.id = s.work_type_id
            {$whereClause}
            ORDER BY s.sale_date DESC, s.id DESC
        ";

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $sales = $stmt->fetchAll();

        // Monthly totals (always show full month/year totals, ignoring product/work_type filter)
        $totalsWhereDate = $isYearly
            ? "WHERE YEAR(s.sale_date) = :year"
            : "WHERE MONTH(s.sale_date) = :month AND YEAR(s.sale_date) = :year";

        $totalsStmt = $this->pdo->prepare("
            SELECT
                COUNT(*)          AS total_rows,
                SUM(s.quantity)   AS total_qty,
                SUM(s.price)      AS total_amount,
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.quantity ELSE 0 END), 0) AS wt1_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.price ELSE 0 END), 0) AS wt1_amount,
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.quantity ELSE 0 END), 0) AS wt2_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.price ELSE 0 END), 0) AS wt2_amount
            FROM sales s
            {$totalsWhereDate}
        ");
        $totalsParams = [':year' => $year];
        if (!$isYearly) {
            $totalsParams[':month'] = $month;
        }
        $totalsStmt->execute($totalsParams);
        $totals = $totalsStmt->fetch();

        // Filter options
        $products   = $this->pdo->query("SELECT id, name FROM products  ORDER BY name")->fetchAll();
        $categories = $this->pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();
        $workTypes  = $this->pdo->query("SELECT id, name FROM work_types ORDER BY id")->fetchAll();

        // Build year options: current year ± 2
        $years = range((int) date('Y') - 2, (int) date('Y') + 1);

        render('sales/index', compact(
            'sales', 'totals',
            'month', 'year', 'years',
            'products', 'categories', 'workTypes',
            'productId', 'categoryId', 'workTypeId', 'customerId'
        ));
    }

    // ──────────────────────────────────────────────────────────
    // create() — Show form (GET) / Save new sale (POST)
    // GET  ?page=sales&action=create
    // POST ?page=sales&action=create
    // ──────────────────────────────────────────────────────────
    public function create(): void
    {
        $products  = $this->pdo->query("SELECT id, name FROM products  ORDER BY name")->fetchAll();
        $customers = $this->pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
        $workTypes = $this->pdo->query("SELECT id, name FROM work_types ORDER BY id")->fetchAll();

        if (empty($products)) {
            setFlash('warning', 'กรุณาเพิ่มสินค้าก่อน ก่อนที่จะบันทึกรายการขาย');
            redirect(url(['page' => 'products', 'action' => 'create']));
        }

        if (empty($customers)) {
            setFlash('warning', 'กรุณาเพิ่มข้อมูลลูกค้าก่อน ก่อนที่จะบันทึกรายการขาย');
            redirect(url(['page' => 'customers', 'action' => 'create']));
        }

        if (isPost()) {
            verifyCsrf();

            $errors = $this->validateSale();

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('sales/form', compact('products', 'customers', 'workTypes'));
                return;
            }

            $stmt = $this->pdo->prepare("
                INSERT INTO sales (sale_date, customer_id, product_id, work_type_id, quantity, price, note)
                VALUES (:sale_date, :customer_id, :product_id, :work_type_id, :quantity, :price, :note)
            ");

            $stmt->execute([
                ':sale_date'    => post('sale_date'),
                ':customer_id'  => (int) post('customer_id'),
                ':product_id'   => (int) post('product_id'),
                ':work_type_id' => (int) post('work_type_id'),
                ':quantity'     => (int) post('quantity', '1'),
                ':price'        => (float) post('price'),
                ':note'         => post('note') ?: null,
            ]);

            oldClear();
            setFlash('success', '✅ บันทึกรายการขายเรียบร้อยแล้ว');
            redirect(url(['page' => 'sales']));
        }

        // GET — show blank form
        $sale = null; // signals "create mode" to the view
        render('sales/form', compact('sale', 'products', 'customers', 'workTypes'));
    }

    // ──────────────────────────────────────────────────────────
    // edit() — Show pre-filled form (GET) / Save changes (POST)
    // GET  ?page=sales&action=edit&id=X
    // POST ?page=sales&action=edit&id=X
    // ──────────────────────────────────────────────────────────
    public function edit(): void
    {
        $id   = (int) get('id', 0);
        $sale = $this->findOrAbort($id);

        $products  = $this->pdo->query("SELECT id, name FROM products  ORDER BY name")->fetchAll();
        $customers = $this->pdo->query("SELECT id, name FROM customers ORDER BY name")->fetchAll();
        $workTypes = $this->pdo->query("SELECT id, name FROM work_types ORDER BY id")->fetchAll();

        if (isPost()) {
            verifyCsrf();

            $errors = $this->validateSale();

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('sales/form', compact('sale', 'products', 'customers', 'workTypes'));
                return;
            }

            $stmt = $this->pdo->prepare("
                UPDATE sales
                SET
                    sale_date    = :sale_date,
                    customer_id  = :customer_id,
                    product_id   = :product_id,
                    work_type_id = :work_type_id,
                    quantity     = :quantity,
                    price        = :price,
                    note         = :note
                WHERE id = :id
            ");

            $stmt->execute([
                ':sale_date'    => post('sale_date'),
                ':customer_id'  => (int) post('customer_id'),
                ':product_id'   => (int) post('product_id'),
                ':work_type_id' => (int) post('work_type_id'),
                ':quantity'     => (int) post('quantity', '1'),
                ':price'        => (float) post('price'),
                ':note'         => post('note') ?: null,
                ':id'           => $id,
            ]);

            oldClear();
            setFlash('success', '✅ แก้ไขรายการขายเรียบร้อยแล้ว');

            // Return to the same month/year the sale was in
            $saleDate = new \DateTimeImmutable(post('sale_date'));
            redirect(url([
                'page'  => 'sales',
                'month' => $saleDate->format('n'),
                'year'  => $saleDate->format('Y'),
            ]));
        }

        // GET — show pre-filled form
        render('sales/form', compact('sale', 'products', 'customers', 'workTypes'));
    }

    // ──────────────────────────────────────────────────────────
    // delete() — Remove a sale record (POST only)
    // POST ?page=sales&action=delete
    //      body: id=X, _csrf_token=...
    // ──────────────────────────────────────────────────────────
    public function delete(): void
    {
        if (!isPost()) {
            redirect(url(['page' => 'sales']));
        }

        verifyCsrf();

        $id   = (int) post('id', '0');
        $sale = $this->findOrAbort($id);

        // Extract month/year before deleting so we can return to the right page
        $saleDate = new \DateTimeImmutable($sale['sale_date']);
        $month    = $saleDate->format('n');
        $year     = $saleDate->format('Y');

        $stmt = $this->pdo->prepare("DELETE FROM sales WHERE id = :id");
        $stmt->execute([':id' => $id]);

        setFlash('success', '🗑️ ลบรายการขายเรียบร้อยแล้ว');
        redirect(url(['page' => 'sales', 'month' => $month, 'year' => $year]));
    }

    // ──────────────────────────────────────────────────────────
    // get_products() — Return products by category as JSON (AJAX)
    // GET ?page=sales&action=get_products&category_id=X
    // ──────────────────────────────────────────────────────────
    public function get_products(): void
    {
        header('Content-Type: application/json');

        $categoryId = (int) get('category_id', 0);

        if ($categoryId > 0) {
            // Get products from specific category
            $stmt = $this->pdo->prepare("
                SELECT id, name
                FROM products
                WHERE category_id = :category_id
                ORDER BY name
            ");
            $stmt->execute([':category_id' => $categoryId]);
        } else {
            // Get all products
            $stmt = $this->pdo->prepare("
                SELECT id, name
                FROM products
                ORDER BY name
            ");
            $stmt->execute();
        }

        $products = $stmt->fetchAll();
        echo json_encode($products);
        exit;
    }

    // ══════════════════════════════════════════════════════════
    // Private helpers
    // ══════════════════════════════════════════════════════════

    /**
     * Find a sale by ID or redirect with an error flash.
     *
     * @return array<string, mixed>
     */
    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            setFlash('danger', 'ID รายการขายไม่ถูกต้อง');
            redirect(url(['page' => 'sales']));
        }

        $stmt = $this->pdo->prepare("
            SELECT
                s.*,
                p.name  AS product_name,
                c.name  AS customer_name,
                wt.name AS work_type_name
            FROM  sales      s
            JOIN  products   p  ON p.id  = s.product_id
            JOIN  customers  c  ON c.id  = s.customer_id
            JOIN  work_types wt ON wt.id = s.work_type_id
            WHERE s.id = :id
            LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $sale = $stmt->fetch();

        if (!$sale) {
            setFlash('danger', "ไม่พบรายการขาย ID #{$id}");
            redirect(url(['page' => 'sales']));
        }

        return $sale;
    }

    /**
     * Validate POST data for create/edit.
     *
     * @return string[]  Array of error messages (empty = valid)
     */
    private function validateSale(): array
    {
        $errors = [];

        // sale_date
        $saleDate = post('sale_date');
        if (empty($saleDate)) {
            $errors[] = 'กรุณาระบุวันที่ขาย';
        } elseif (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $saleDate)) {
            $errors[] = 'รูปแบบวันที่ขายไม่ถูกต้อง (YYYY-MM-DD)';
        } else {
            // Check if it's a valid calendar date
            [$y, $m, $d] = explode('-', $saleDate);
            if (!checkdate((int) $m, (int) $d, (int) $y)) {
                $errors[] = 'วันที่ขายไม่ถูกต้อง';
            }
        }

        // customer_id
        $customerId = (int) post('customer_id');
        if ($customerId <= 0) {
            $errors[] = 'กรุณาเลือกชื่อลูกค้า';
        } else {
            $check = $this->pdo->prepare("SELECT id FROM customers WHERE id = ?");
            $check->execute([$customerId]);
            if (!$check->fetch()) {
                $errors[] = 'ไม่พบข้อมูลลูกค้าที่เลือก';
            }
        }

        // product_id
        $productId = (int) post('product_id');
        if ($productId <= 0) {
            $errors[] = 'กรุณาเลือกสินค้า';
        } else {
            $check = $this->pdo->prepare("SELECT id FROM products WHERE id = ?");
            $check->execute([$productId]);
            if (!$check->fetch()) {
                $errors[] = 'ไม่พบสินค้าที่เลือก';
            }
        }

        // work_type_id
        $workTypeId = (int) post('work_type_id');
        if ($workTypeId <= 0) {
            $errors[] = 'กรุณาเลือกประเภทงาน';
        } else {
            $check = $this->pdo->prepare("SELECT id FROM work_types WHERE id = ?");
            $check->execute([$workTypeId]);
            if (!$check->fetch()) {
                $errors[] = 'ไม่พบประเภทงานที่เลือก';
            }
        }

        // quantity
        $qty = (int) post('quantity', '1');
        if ($qty < 1) {
            $errors[] = 'จำนวนต้องมากกว่า 0';
        } elseif ($qty > 99999) {
            $errors[] = 'จำนวนไม่ควรเกิน 99,999';
        }

        // price
        $priceRaw = post('price');
        if ($priceRaw === null || $priceRaw === '') {
            $errors[] = 'กรุณาระบุยอดขาย';
        } else {
            $price = (float) $priceRaw;
            if ($price < 0) {
                $errors[] = 'ยอดขายต้องไม่ติดลบ';
            } elseif ($price > 99_999_999.99) {
                $errors[] = 'ยอดขายสูงเกินไป';
            }
        }

        return $errors;
    }
}
