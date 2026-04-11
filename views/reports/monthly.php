<?php declare(strict_types=1); ?>

<?php
$pageTitle      = $isYearly ? 'รายงานยอดขายประจำปี' : 'รายงานยอดขายประจำเดือน';
$buddhistYear   = toBuddhistYear($year);
$monthName      = $isYearly ? 'ทั้งปี' : thaiMonthName($month);
$reportPeriod   = $isYearly ? "ทั้งปี {$buddhistYear}" : "เดือน {$monthName} {$buddhistYear}";

// Build month list for quick nav
$months = [];
for ($m = 1; $m <= 12; $m++) {
    $months[$m] = thaiMonthName($m);
}
?>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Page Header                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-graph-up-arrow me-2 text-primary"></i>รายงานยอดขาย
        </h4>
        <p class="text-muted mb-0 small">
            <?= $reportPeriod ?>
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center no-print">
        <!-- PDF Export -->
        <a href="<?= url(['page' => 'reports', 'action' => 'pdf', 'month' => $month, 'year' => $year, 'customer_id' => $customerId, 'category_id' => $categoryId, 'product_id' => $productId]) ?>"
           class="btn btn-danger d-flex align-items-center gap-2"
           target="_blank">
            <i class="bi bi-file-earmark-pdf-fill"></i>
            <span>ดาวน์โหลด PDF</span>
        </a>
        <!-- Print -->
        <button type="button" class="btn btn-outline-secondary d-flex align-items-center gap-2"
                onclick="window.print()">
            <i class="bi bi-printer-fill"></i>
            <span class="d-none d-sm-inline">พิมพ์</span>
        </button>
    </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Filter Form                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card mb-4 no-print">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="reports">

            <!-- Month (or Yearly option) -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">เดือน</label>
                <select name="month" class="form-select form-select-sm">
                    <option value="0"<?= selected($month, 0) ?>>— ทั้งปี —</option>
                    <?php foreach ($months as $num => $name): ?>
                        <option value="<?= $num ?>"<?= selected($month, $num) ?>>
                            <?= e($name) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Year -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">ปี (พ.ศ.)</label>
                <select name="year" class="form-select form-select-sm">
                    <?php foreach ($years as $y): ?>
                        <option value="<?= $y ?>"<?= selected($year, $y) ?>>
                            <?= toBuddhistYear($y) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Customer filter -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">ลูกค้า</label>
                <select name="customer_id" class="form-select form-select-sm">
                    <option value="0">— ทุกลูกค้า —</option>
                    <?php foreach ($customers as $cust): ?>
                        <option value="<?= $cust['id'] ?>"<?= selected($customerId, $cust['id']) ?>>
                            <?= e($cust['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Category filter -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">หมวดหมู่</label>
                <select name="category_id" id="categoryFilter" class="form-select form-select-sm">
                    <option value="0">— ทุกหมวดหมู่ —</option>
                    <?php foreach ($categories as $cat): ?>
                        <option value="<?= $cat['id'] ?>"<?= selected($categoryId, $cat['id']) ?>>
                            <?= e($cat['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Product filter -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">สินค้า</label>
                <select name="product_id" id="productFilter" class="form-select form-select-sm">
                    <option value="0">— ทุกสินค้า —</option>
                    <?php foreach ($allProducts as $p): ?>
                        <?php if ($categoryId === 0 || (int)($p['category_id'] ?? 0) === $categoryId): ?>
                            <option value="<?= $p['id'] ?>"<?= selected($productId, $p['id']) ?>>
                                <?= e($p['name']) ?>
                            </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-12 col-md-auto d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1">
                    <i class="bi bi-search me-1"></i>ดูรายงาน
                </button>
                <a href="<?= url(['page' => 'reports', 'month' => $month, 'year' => $year]) ?>"
                   class="btn btn-sm btn-outline-secondary"
                   data-bs-toggle="tooltip" title="ล้างตัวกรอง">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Grand Summary Cards                                     ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="row g-3 mb-4">

    <!-- Total amount -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-success h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">ยอดขายรวมทั้งเดือน</p>
                        <h4 class="fw-bold mb-0 text-amount text-success">
                            ฿<?= formatMoney($grandTotalAmount) ?>
                        </h4>
                        <small class="text-muted">บาท</small>
                    </div>
                    <div class="fs-2 text-success opacity-50">
                        <i class="bi bi-cash-coin"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total qty -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-primary h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">จำนวนรวมทั้งเดือน</p>
                        <h4 class="fw-bold mb-0 text-amount">
                            <?= number_format($grandTotalQty) ?>
                        </h4>
                        <small class="text-muted">ชิ้น</small>
                    </div>
                    <div class="fs-2 text-primary opacity-50">
                        <i class="bi bi-stack"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Work type 1 -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card h-100" style="border-left:4px solid #0d6efd !important;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">
                            <span class="badge badge-wt1 rounded-pill me-1">1</span>ขายเฉพาะเครื่อง
                        </p>
                        <h5 class="fw-bold mb-0 text-amount text-primary">
                            ฿<?= formatMoney($grandWt1Amount) ?>
                        </h5>
                        <small class="text-muted"><?= number_format($grandWt1Qty) ?> ชิ้น</small>
                    </div>
                    <div class="fs-2 opacity-50" style="color:#0d6efd">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Work type 2 -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-success h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">
                            <span class="badge badge-wt2 rounded-pill me-1">2</span>ขายพร้อมติดตั้ง
                        </p>
                        <h5 class="fw-bold mb-0 text-amount text-success">
                            ฿<?= formatMoney($grandWt2Amount) ?>
                        </h5>
                        <small class="text-muted"><?= number_format($grandWt2Qty) ?> ชิ้น</small>
                    </div>
                    <div class="fs-2 text-success opacity-50">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<?php if ($grandTotalAmount > 0): ?>
<!-- ── Overall ratio bar ─────────────────────────────────────── -->
<div class="card mb-4 no-print">
    <div class="card-body py-3 px-4">
        <p class="small fw-semibold text-muted mb-2">
            <i class="bi bi-pie-chart-fill me-1"></i>
            สัดส่วนยอดขายตามประเภทงาน — <?= $monthName ?> <?= $buddhistYear ?>
        </p>
        <?php
        $wt1Pct = $grandTotalAmount > 0 ? round(($grandWt1Amount / $grandTotalAmount) * 100, 1) : 0;
        $wt2Pct = $grandTotalAmount > 0 ? round(($grandWt2Amount / $grandTotalAmount) * 100, 1) : 0;
        ?>
        <div class="progress mb-2" style="height:28px;border-radius:.5rem;">
            <?php if ($wt1Pct > 0): ?>
            <div class="progress-bar badge-wt1 d-flex align-items-center justify-content-center"
                 role="progressbar"
                 style="width:<?= $wt1Pct ?>%;font-size:.85rem;"
                 title="ขายเฉพาะเครื่อง <?= $wt1Pct ?>%">
                <?php if ($wt1Pct >= 8): ?>
                    <?= $wt1Pct ?>%
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <?php if ($wt2Pct > 0): ?>
            <div class="progress-bar badge-wt2 d-flex align-items-center justify-content-center"
                 role="progressbar"
                 style="width:<?= $wt2Pct ?>%;font-size:.85rem;"
                 title="ขายพร้อมติดตั้ง <?= $wt2Pct ?>%">
                <?php if ($wt2Pct >= 8): ?>
                    <?= $wt2Pct ?>%
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <div class="d-flex gap-4 flex-wrap small text-muted">
            <span>
                <span class="badge badge-wt1 me-1">&nbsp;&nbsp;</span>
                ขายเฉพาะเครื่อง:
                <strong class="text-dark">฿<?= formatMoney($grandWt1Amount) ?></strong>
                — <?= number_format($grandWt1Qty) ?> ชิ้น
                <span class="text-primary fw-semibold">(<?= $wt1Pct ?>%)</span>
            </span>
            <span>
                <span class="badge badge-wt2 me-1">&nbsp;&nbsp;</span>
                ขายพร้อมติดตั้ง:
                <strong class="text-dark">฿<?= formatMoney($grandWt2Amount) ?></strong>
                — <?= number_format($grandWt2Qty) ?> ชิ้น
                <span class="text-success fw-semibold">(<?= $wt2Pct ?>%)</span>
            </span>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Section 1: Product Summary Table                        ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->

<!-- Print header (only shows when printing) -->
<div class="print-only mb-3" style="display:none;">
    <h3 class="fw-bold text-center mb-1">รายงานยอดขายประจำเดือน <?= $monthName ?> <?= $buddhistYear ?></h3>
    <p class="text-center text-muted small mb-0">สร้างเมื่อ <?= formatDateTime(date('Y-m-d H:i:s')) ?></p>
    <hr>
</div>

<div class="card mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold">
            <i class="bi bi-table me-1 text-primary"></i>
            สรุปยอดขายรายสินค้า — <?= $monthName ?> <?= $buddhistYear ?>
        </span>
        <span class="badge bg-light text-dark border small">
            <?= number_format(count($rows)) ?> สินค้า
        </span>
    </div>

    <?php if (empty($rows) || $grandTotalQty === 0): ?>
        <div class="card-body text-center py-5">
            <i class="bi bi-clipboard-x text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-1 fw-semibold">ไม่มีรายการขายในเดือนนี้</p>
            <p class="text-muted small mb-3">
                ยังไม่มีการบันทึกการขายในเดือน <?= $monthName ?> <?= $buddhistYear ?>
            </p>
            <a href="<?= url(['page' => 'sales', 'action' => 'create']) ?>"
               class="btn btn-primary btn-sm no-print">
                <i class="bi bi-plus-circle me-1"></i>บันทึกรายการขาย
            </a>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="summaryTable">
                <thead>
                    <tr class="table-dark">
                        <th class="text-center" style="width:50px">#</th>
                        <th>ชื่อสินค้า</th>
                        <!-- WT1 -->
                        <th class="text-center" colspan="2" style="background-color:#0b5ed7;">
                            <i class="bi bi-cpu me-1"></i>ขายเฉพาะเครื่อง
                        </th>
                        <!-- WT2 -->
                        <th class="text-center" colspan="2" style="background-color:#157347;">
                            <i class="bi bi-tools me-1"></i>ขายพร้อมติดตั้ง
                        </th>
                        <!-- Total -->
                        <th class="text-center" colspan="2" style="background-color:#343a40;">
                            <i class="bi bi-sigma me-1"></i>รวมทั้งหมด
                        </th>
                    </tr>
                    <tr style="font-size:.79rem;">
                        <th class="table-dark"></th>
                        <th class="table-dark"></th>
                        <th class="text-center text-info"        style="background-color:#0b5ed7;">จำนวน (ชิ้น)</th>
                        <th class="text-center text-info"        style="background-color:#0b5ed7;">ยอดขาย (บาท)</th>
                        <th class="text-center"                  style="background-color:#157347;color:#a3e6b9;">จำนวน (ชิ้น)</th>
                        <th class="text-center"                  style="background-color:#157347;color:#a3e6b9;">ยอดขาย (บาท)</th>
                        <th class="text-center text-warning-emphasis" style="background-color:#343a40;">จำนวน (ชิ้น)</th>
                        <th class="text-center text-warning-emphasis" style="background-color:#343a40;">ยอดขาย (บาท)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $idx => $row): ?>
                    <?php $hasSales = (int)$row['total_qty'] > 0; ?>
                    <?php if (!$hasSales) continue; ?>
                    <tr>
                        <!-- # -->
                        <td class="text-center text-muted small"><?= $idx + 1 ?></td>

                        <!-- Product name -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border rounded-circle fw-bold"
                                      style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;font-size:.8rem;">
                                    <?= mb_substr(e($row['product_name']), 0, 1) ?>
                                </span>
                                <span class="fw-semibold">
                                    <?= e($row['product_name']) ?>
                                </span>
                            </div>
                        </td>

                        <!-- WT1 qty -->
                        <td class="text-center">
                            <?php if ((int)$row['wt1_qty'] > 0): ?>
                                <span class="badge badge-wt1 rounded-pill text-amount">
                                    <?= number_format((int)$row['wt1_qty']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT1 amount -->
                        <td class="text-end">
                            <?php if ((float)$row['wt1_amount'] > 0): ?>
                                <span class="fw-semibold text-primary text-amount">
                                    <?= formatMoney($row['wt1_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT2 qty -->
                        <td class="text-center">
                            <?php if ((int)$row['wt2_qty'] > 0): ?>
                                <span class="badge badge-wt2 rounded-pill text-amount">
                                    <?= number_format((int)$row['wt2_qty']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT2 amount -->
                        <td class="text-end">
                            <?php if ((float)$row['wt2_amount'] > 0): ?>
                                <span class="fw-semibold text-success text-amount">
                                    <?= formatMoney($row['wt2_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total qty -->
                        <td class="text-center">
                            <?php if ((int)$row['total_qty'] > 0): ?>
                                <span class="fw-bold text-amount">
                                    <?= number_format((int)$row['total_qty']) ?>
                                </span>
                                <span class="text-muted small"> ชิ้น</span>
                            <?php else: ?>
                                <span class="text-muted small">0</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total amount -->
                        <td class="text-end">
                            <?php if ((float)$row['total_amount'] > 0): ?>
                                <span class="fw-bold text-dark text-amount">
                                    <?= formatMoney($row['total_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- Grand total row -->
                <tfoot>
                    <tr class="fw-bold" style="background-color:#e8f0fe;border-top:2px solid #6c8ebf;">
                        <td colspan="2" class="text-end py-2" style="color:#1a3a6e;">
                            <i class="bi bi-sigma me-1"></i>รวมทั้งหมด
                        </td>
                        <td class="text-center text-amount" style="color:#0b5ed7;">
                            <?= number_format($grandWt1Qty) ?>
                        </td>
                        <td class="text-end text-amount" style="color:#0b5ed7;">
                            <?= formatMoney($grandWt1Amount) ?>
                        </td>
                        <td class="text-center text-amount" style="color:#157347;">
                            <?= number_format($grandWt2Qty) ?>
                        </td>
                        <td class="text-end text-amount" style="color:#157347;">
                            <?= formatMoney($grandWt2Amount) ?>
                        </td>
                        <td class="text-center text-amount fw-bolder fs-6">
                            <?= number_format($grandTotalQty) ?> ชิ้น
                        </td>
                        <td class="text-end text-amount fw-bolder fs-6 text-success">
                            ฿<?= formatMoney($grandTotalAmount) ?>
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- Per-product mini bars -->
        <?php if ($grandTotalAmount > 0): ?>
        <div class="card-footer bg-white px-3 py-3 no-print">
            <p class="small fw-semibold text-muted mb-3">
                <i class="bi bi-bar-chart-fill me-1"></i>
                สัดส่วนยอดขายรายสินค้า (เฉพาะสินค้าที่มีการขาย)
            </p>
            <div class="row g-2">
                <?php foreach ($rows as $row):
                    if ((float)$row['total_amount'] <= 0) continue;
                    $pct = round(((float)$row['total_amount'] / $grandTotalAmount) * 100, 1);
                ?>
                <div class="col-12 col-md-6">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-muted small text-nowrap" style="min-width:110px;overflow:hidden;text-overflow:ellipsis;">
                            <?= e(mb_strimwidth($row['product_name'], 0, 14, '…')) ?>
                        </span>
                        <div class="flex-grow-1">
                            <div class="progress" style="height:16px;border-radius:4px;">
                                <div class="progress-bar bg-primary"
                                     role="progressbar"
                                     style="width:<?= $pct ?>%"
                                     title="<?= e($row['product_name']) ?> — <?= $pct ?>%">
                                </div>
                            </div>
                        </div>
                        <span class="small text-muted text-nowrap" style="min-width:45px;text-align:right;">
                            <?= $pct ?>%
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Section 2: Daily Sales Detail                           ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card mb-4">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold">
            <i class="bi bi-list-check me-1 text-success"></i>
            รายละเอียดการขายรายวัน — <?= $monthName ?> <?= $buddhistYear ?>
        </span>
        <span class="badge bg-light text-dark border small">
            <?= number_format(count($dailySales)) ?> รายการ
        </span>
    </div>

    <?php if (empty($dailySales)): ?>
        <div class="card-body text-center py-4">
            <i class="bi bi-inbox text-muted fs-1"></i>
            <p class="text-muted mt-2 mb-0">ไม่มีรายการขายในเดือนนี้</p>
        </div>

    <?php else: ?>

        <?php
        // Group daily sales by date for better readability
        $salesByDate = [];
        foreach ($dailySales as $sale) {
            $salesByDate[$sale['sale_date']][] = $sale;
        }
        ?>

        <?php foreach ($salesByDate as $date => $daySales): ?>
        <?php
        $dayTotal    = array_sum(array_column($daySales, 'price'));
        $dayQty      = array_sum(array_column($daySales, 'quantity'));
        ?>

        <!-- Date group header -->
        <div class="px-3 py-2 bg-light border-bottom border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
            <span class="fw-semibold text-dark">
                <i class="bi bi-calendar-event me-1 text-primary"></i>
                <?= formatDate($date) ?>
            </span>
            <div class="d-flex gap-3 small text-muted">
                <span>
                    <i class="bi bi-box-seam me-1"></i>
                    <?= number_format($dayQty) ?> ชิ้น
                </span>
                <span class="fw-semibold text-success">
                    <i class="bi bi-currency-exchange me-1"></i>
                    ฿<?= formatMoney($dayTotal) ?>
                </span>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0" style="font-size:.875rem;">
                <thead class="table-light" style="font-size:.78rem;">
                    <tr>
                        <th class="text-center ps-3" style="width:40px">#</th>
                        <th>ลูกค้า</th>
                        <th>สินค้า</th>
                        <th>ประเภทงาน</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end pe-3">ยอดขาย (บาท)</th>
                        <th>หมายเหตุ</th>
                        <th class="text-center no-print" style="width:80px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($daySales as $idx => $sale): ?>
                    <?php
                    $wtId = (int)$sale['work_type_id'];
                    $rowBgColor = $wtId === 1 ? 'rgba(13, 110, 253, 0.05)' : ($wtId === 2 ? 'rgba(21, 115, 71, 0.05)' : 'transparent');
                    $borderColor = $wtId === 1 ? '#0d6efd' : ($wtId === 2 ? '#157347' : 'transparent');
                    ?>
                    <tr style="background-color: <?= $rowBgColor ?>; border-left: 3px solid <?= $borderColor ?>;">
                        <td class="text-center text-muted small ps-3">
                            <?= $idx + 1 ?>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                      style="width:26px;height:26px;font-size:.7rem;">
                                    <?= mb_substr(e($sale['customer_name']), 0, 1) ?>
                                </span>
                                <span><?= e($sale['customer_name']) ?></span>
                            </div>
                        </td>
                        <td>
                            <span class="badge bg-light text-dark border fw-normal">
                                <?= e($sale['product_name']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ((int)$sale['work_type_id'] === 1): ?>
                                <span class="badge badge-wt1 rounded-pill print-wt1-color">
                                    <i class="bi bi-cpu me-1"></i><?= e($sale['work_type_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-wt2 rounded-pill print-wt2-color">
                                    <i class="bi bi-tools me-1"></i><?= e($sale['work_type_name']) ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <span class="fw-semibold text-amount">
                                <?= number_format((int)$sale['quantity']) ?>
                            </span>
                            <span class="text-muted small"> ชิ้น</span>
                        </td>
                        <td class="text-end pe-3">
                            <span class="fw-bold text-success text-amount">
                                <?= formatMoney($sale['price']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if (!empty($sale['note'])): ?>
                                <span class="text-muted small"
                                      data-bs-toggle="tooltip"
                                      title="<?= e($sale['note']) ?>">
                                    <?= e(mb_strimwidth($sale['note'], 0, 25, '…')) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center no-print">
                            <a href="<?= url(['page' => 'sales', 'action' => 'edit', 'id' => $sale['id']]) ?>"
                               class="btn btn-xs btn-outline-primary btn-sm py-0 px-2"
                               data-bs-toggle="tooltip" title="แก้ไข">
                                <i class="bi bi-pencil-fill" style="font-size:.75rem;"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <!-- Day subtotal row -->
                <tfoot>
                    <tr class="table-light fw-semibold border-top">
                        <td colspan="4" class="text-end text-muted ps-3">
                            รวมวันที่ <?= formatDate($date) ?>:
                        </td>
                        <td class="text-center text-amount">
                            <?= number_format($dayQty) ?> ชิ้น
                        </td>
                        <td class="text-end pe-3 text-success text-amount">
                            <?= formatMoney($dayTotal) ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <?php endforeach; ?>

        <!-- Overall detail totals -->
        <div class="card-footer bg-white py-2 px-3">
            <div class="row align-items-center">
                <div class="col text-end fw-bold text-muted small">
                    รวมทั้งเดือน (<?= number_format(count($dailySales)) ?> รายการ):
                </div>
                <div class="col-auto fw-bold text-amount">
                    <?= number_format(array_sum(array_column($dailySales, 'quantity'))) ?> ชิ้น
                </div>
                <div class="col-auto fw-bold text-amount text-success fs-6">
                    ฿<?= formatMoney(array_sum(array_column($dailySales, 'price'))) ?>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Section 3: Product Breakdown Cards                      ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<?php
$soldProducts = array_filter($rows, fn($r) => (int)$r['total_qty'] > 0);
?>
<?php if (!empty($soldProducts)): ?>
<h6 class="fw-semibold text-muted mb-3 mt-2 no-print">
    <i class="bi bi-grid-3x3-gap me-1"></i>สรุปแยกตามสินค้า
</h6>
<div class="row g-3 mb-4 no-print">
    <?php foreach ($soldProducts as $row): ?>
    <div class="col-12 col-md-6 col-xl-4">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-white py-2 px-3 d-flex align-items-center gap-2">
                <span class="badge bg-primary rounded-circle fw-bold"
                      style="width:30px;height:30px;display:inline-flex;align-items:center;justify-content:center;">
                    <?= mb_substr(e($row['product_name']), 0, 1) ?>
                </span>
                <span class="fw-semibold flex-grow-1"><?= e($row['product_name']) ?></span>
                <a href="<?= url(['page' => 'sales', 'product_id' => $row['product_id'], 'month' => $month, 'year' => $year]) ?>"
                   class="btn btn-sm btn-outline-secondary py-0 px-2">
                    <i class="bi bi-eye" style="font-size:.8rem;"></i>
                </a>
            </div>
            <div class="card-body px-3 pb-2 pt-3">

                <!-- Stats row -->
                <div class="row g-2 mb-3 text-center">
                    <div class="col-6">
                        <div class="bg-light rounded py-2 px-1">
                            <div class="text-muted small">รวมชิ้น</div>
                            <div class="fw-bold fs-5 text-amount"><?= number_format((int)$row['total_qty']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-light rounded py-2 px-1">
                            <div class="text-muted small">รวมยอด (฿)</div>
                            <div class="fw-bold text-amount" style="font-size:.95rem;"><?= formatMoney($row['total_amount']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- WT breakdown -->
                <table class="table table-sm mb-1 small">
                    <thead>
                        <tr class="table-light" style="font-size:.78rem;">
                            <th>ประเภทงาน</th>
                            <th class="text-center">จำนวน</th>
                            <th class="text-end">ยอดขาย</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>
                                <span class="badge badge-wt1 rounded-pill me-1">1</span>
                                ขายเฉพาะเครื่อง
                            </td>
                            <td class="text-center text-amount">
                                <?php if ((int)$row['wt1_qty'] > 0): ?>
                                    <span class="badge badge-wt1 rounded-pill"><?= number_format((int)$row['wt1_qty']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-amount <?= (float)$row['wt1_amount'] > 0 ? 'text-primary fw-semibold' : 'text-muted' ?>">
                                <?= formatMoney($row['wt1_amount']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="badge badge-wt2 rounded-pill me-1">2</span>
                                ขายพร้อมติดตั้ง
                            </td>
                            <td class="text-center text-amount">
                                <?php if ((int)$row['wt2_qty'] > 0): ?>
                                    <span class="badge badge-wt2 rounded-pill"><?= number_format((int)$row['wt2_qty']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">0</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end text-amount <?= (float)$row['wt2_amount'] > 0 ? 'text-success fw-semibold' : 'text-muted' ?>">
                                <?= formatMoney($row['wt2_amount']) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Mini ratio bar -->
                <?php
                $pTotalAmt = (float)$row['total_amount'];
                $pWt1Pct   = $pTotalAmt > 0 ? round(((float)$row['wt1_amount'] / $pTotalAmt) * 100, 1) : 0;
                $pWt2Pct   = $pTotalAmt > 0 ? round(((float)$row['wt2_amount'] / $pTotalAmt) * 100, 1) : 0;
                ?>
                <div class="progress mt-2" style="height:8px;border-radius:4px;">
                    <?php if ($pWt1Pct > 0): ?>
                    <div class="progress-bar badge-wt1" role="progressbar"
                         style="width:<?= $pWt1Pct ?>%"
                         title="ขายเฉพาะเครื่อง <?= $pWt1Pct ?>%">
                    </div>
                    <?php endif; ?>
                    <?php if ($pWt2Pct > 0): ?>
                    <div class="progress-bar badge-wt2" role="progressbar"
                         style="width:<?= $pWt2Pct ?>%"
                         title="ขายพร้อมติดตั้ง <?= $pWt2Pct ?>%">
                    </div>
                    <?php endif; ?>
                </div>
                <div class="d-flex justify-content-between small text-muted mt-1" style="font-size:.73rem;">
                    <span>ขายเฉพาะเครื่อง: <?= $pWt1Pct ?>%</span>
                    <span>ขายพร้อมติดตั้ง: <?= $pWt2Pct ?>%</span>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<!-- ── Month quick nav ──────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-center gap-1 mt-2 mb-4 no-print">
    <?php foreach ($months as $num => $name): ?>
        <?php $isActive = ((int)$month === $num); ?>
        <a href="<?= url(['page' => 'reports', 'month' => $num, 'year' => $year]) ?>"
           class="btn btn-sm <?= $isActive ? 'btn-dark' : 'btn-outline-secondary' ?>">
            <?= e($name) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- ── Bottom action bar ─────────────────────────────────────── -->
<?php if (!empty($dailySales)): ?>
<div class="d-flex justify-content-center gap-3 mb-4 no-print">
    <a href="<?= url(['page' => 'reports', 'action' => 'pdf', 'month' => $month, 'year' => $year]) ?>"
       class="btn btn-danger btn-lg d-flex align-items-center gap-2"
       target="_blank">
        <i class="bi bi-file-earmark-pdf-fill fs-5"></i>
        <span>ดาวน์โหลดรายงาน PDF — <?= $monthName ?> <?= $buddhistYear ?></span>
    </a>
</div>
<?php endif; ?>

<!-- Dynamic product filter script -->
<script>
(function() {
    'use strict';
    
    // Product data from server
    const allProducts = <?= $allProductsJson ?>;
    const categoryFilter = document.getElementById('categoryFilter') || document.querySelector('[name="category_id"]');
    const productFilter = document.getElementById('productFilter') || document.querySelector('[name="product_id"]');
    const currentProductId = <?= (int)$productId ?>;
    
    if (!categoryFilter || !productFilter) {
        return;
    }
    
    function updateProductFilter() {
        const selectedCategoryId = parseInt(categoryFilter.value);
        const currentProductValue = productFilter.value;
        
        // Filter products by selected category
        const filteredProducts = selectedCategoryId === 0
            ? allProducts
            : allProducts.filter(p => p.category_id === selectedCategoryId);
        
        // Store current options
        const options = productFilter.querySelectorAll('option');
        let defaultOptionHtml = '';
        
        // Find and preserve the "all products" option
        options.forEach(opt => {
            if (opt.value === '0') {
                defaultOptionHtml = opt.outerHTML;
            }
        });
        
        // Build new options
        let newHtml = defaultOptionHtml;
        filteredProducts.forEach(product => {
            const isSelected = parseInt(currentProductValue) === product.id ? 'selected' : '';
            newHtml += `<option value="${product.id}" ${isSelected}>${escapeHtml(product.name)}</option>`;
        });
        
        // Update select
        productFilter.innerHTML = newHtml;
        
        // If current product is not in filtered list, reset to "all"
        if (!filteredProducts.find(p => p.id === parseInt(currentProductValue))) {
            productFilter.value = '0';
        }
    }
    
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    // Update on category change
    categoryFilter.addEventListener('change', updateProductFilter);
})();
</script>

<!-- Print-specific styles -->
<style>
@media print {
    .no-print { display: none !important; }
    .print-only { display: block !important; }
    .card { box-shadow: none !important; border: 1px solid #dee2e6 !important; }
    body { font-size: 11pt; }
    table { font-size: 9pt; }
    
    /* Work type badges for print - keep background and make text dark for visibility */
    .print-wt1-color { 
        background-color: rgba(13, 110, 253, 0.3) !important;
        color: #0d6efd !important; 
        font-weight: 600 !important;
    }
    .print-wt2-color { 
        background-color: rgba(21, 115, 71, 0.3) !important;
        color: #157347 !important; 
        font-weight: 600 !important;
    }
}
</style>
