<?php declare(strict_types=1); ?>

<?php
// ── Page meta ─────────────────────────────────────────────────
$pageTitle = 'รายการขาย';

// ── Month/Year selector data ──────────────────────────────────
$months = [];
for ($m = 1; $m <= 12; $m++) {
    $months[$m] = thaiMonthName($m);
}

// ── Determine period display ──────────────────────────────────
$isYearly = ($month === 0);
$monthDisplay = $isYearly ? 'ทั้งปี' : thaiMonthName($month);

// ── Summary from $totals ──────────────────────────────────────
$totalRows   = (int)   ($totals['total_rows']   ?? 0);
$totalQty    = (int)   ($totals['total_qty']    ?? 0);
$totalAmount = (float) ($totals['total_amount'] ?? 0);
?>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Page Header                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-receipt me-2 text-primary"></i>รายการขาย
            <?php if (!empty($customerName)): ?>
                <span class="badge bg-info ms-2"><?= e($customerName) ?></span>
            <?php endif; ?>
        </h4>
        <p class="text-muted mb-0 small">
            <?php if (!empty($customerName)): ?>
                <i class="bi bi-person me-1"></i><?= e($customerName) ?> • 
            <?php endif; ?>
            <?= $monthDisplay ?> <?= toBuddhistYear($year) ?>
        </p>
    </div>
    <a href="<?= url(['page' => 'sales', 'action' => 'create']) ?>"
       class="btn btn-primary d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle-fill"></i>
        <span>บันทึกการขาย</span>
    </a>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Filter Form                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card mb-4">
    <div class="card-body py-3">
        <form method="GET" action="index.php" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="sales">

            <!-- Month -->
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
                    <?php foreach ($products as $p): ?>
                        <option value="<?= $p['id'] ?>"<?= selected($productId, $p['id']) ?>>
                            <?= e($p['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Work type filter -->
            <div class="col-6 col-sm-4 col-md-2">
                <label class="form-label small fw-semibold mb-1">ประเภทงาน</label>
                <select name="work_type_id" class="form-select form-select-sm">
                    <option value="0">— ทุกประเภท —</option>
                    <?php foreach ($workTypes as $wt): ?>
                        <option value="<?= $wt['id'] ?>"<?= selected($workTypeId, $wt['id']) ?>>
                            <?= e($wt['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Buttons -->
            <div class="col-12 col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-dark flex-grow-1">
                    <i class="bi bi-search me-1"></i>ค้นหา
                </button>
                <a href="<?= url(['page' => 'sales', 'month' => $month, 'year' => $year]) ?>"
                   class="btn btn-sm btn-outline-secondary"
                   data-bs-toggle="tooltip" title="ล้างตัวกรอง">
                    <i class="bi bi-arrow-counterclockwise"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Summary Cards                                           ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="row g-3 mb-4">

    <!-- Work type summary cards (dynamic from database) -->
    <?php foreach ($workTypes as $wt): ?>
    <?php
    $wtId = (int)$wt['id'];
    $qtyKey = 'wt' . $wtId . '_qty';
    $amountKey = 'wt' . $wtId . '_amount';
    $wtQty = (int)($totals[$qtyKey] ?? 0);
    $wtAmount = (float)($totals[$amountKey] ?? 0);
    
    // Color and icon mapping for work types
    $colorMap = [
        1 => ['color' => '#0d6efd', 'icon' => 'cpu-fill', 'badge' => 'badge-wt1', 'textClass' => 'text-primary'],
        2 => ['color' => '#157347', 'icon' => 'tools-fill', 'badge' => 'badge-wt2', 'textClass' => 'text-success'],
    ];
    $styling = $colorMap[$wtId] ?? ['color' => '#6c757d', 'icon' => 'box2-fill', 'badge' => 'bg-secondary', 'textClass' => 'text-secondary'];
    ?>
    <div class="col-6 col-lg-3">
        <div class="card summary-card h-100" style="border-left:4px solid <?= $styling['color'] ?> !important;">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">
                            <span class="badge <?= $styling['badge'] ?> rounded-pill me-1"><?= $wtId ?></span><?= e($wt['name']) ?>
                        </p>
                        <h5 class="fw-bold mb-0 text-amount <?= $styling['textClass'] ?>">
                            <?= formatMoney($wtAmount) ?>
                        </h5>
                        <small class="text-muted"><?= number_format($wtQty) ?> ชิ้น</small>
                    </div>
                    <div class="fs-2 opacity-50" style="color:<?= $styling['color'] ?>">
                        <i class="bi bi-<?= $styling['icon'] ?>"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>

    <!-- Total transactions -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-primary h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">รายการขายทั้งหมด</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= number_format($totalRows) ?></h4>
                        <small class="text-muted">รายการ</small>
                    </div>
                    <div class="fs-2 text-primary opacity-50">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total quantity -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-info h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">จำนวนชิ้นรวม</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= number_format($totalQty) ?></h4>
                        <small class="text-muted">ชิ้น</small>
                    </div>
                    <div class="fs-2 text-info opacity-50">
                        <i class="bi bi-box-seam"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total amount -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-success h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">ยอดขายรวม</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= formatMoney($totalAmount) ?></h4>
                        <small class="text-muted">บาท</small>
                    </div>
                    <div class="fs-2 text-success opacity-50">
                        <i class="bi bi-currency-exchange"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Report link -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-warning h-100">
            <div class="card-body py-3 px-3 d-flex flex-column justify-content-between">
                <div>
                    <p class="text-muted small mb-1">สรุปรายเดือน</p>
                    <p class="fw-semibold mb-0">
                        <?= thaiMonthName($month) ?> <?= toBuddhistYear($year) ?>
                    </p>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <a href="<?= url(['page' => 'reports', 'month' => $month, 'year' => $year]) ?>"
                       class="btn btn-sm btn-warning flex-grow-1">
                        <i class="bi bi-graph-up me-1"></i>ดูรายงาน
                    </a>
                    <a href="<?= url(['page' => 'reports', 'action' => 'pdf', 'month' => $month, 'year' => $year]) ?>"
                       class="btn btn-sm btn-outline-danger"
                       data-bs-toggle="tooltip" title="ดาวน์โหลด PDF">
                        <i class="bi bi-file-earmark-pdf"></i>
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Sales Table                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold">
            รายการขาย
            <?php if ($productId > 0 || $workTypeId > 0 || $customerId > 0): ?>
                <span class="badge bg-primary ms-1">กรองอยู่</span>
            <?php endif; ?>
        </span>
        <span class="text-muted small">
            แสดง <?= number_format(count($sales)) ?> รายการ
        </span>
    </div>

    <?php if (empty($sales)): ?>
        <!-- Empty state -->
        <div class="card-body text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-1 fw-semibold">ไม่พบรายการขาย</p>
            <p class="text-muted small mb-3">
                <?php if ($customerId > 0): ?>
                    <!--customer specific message -->
                    <?= e($customerName) ?> ยังไม่มีรายการขายใน<?= thaiMonthName($month) ?> <?= toBuddhistYear($year) ?>
                <?php elseif ($productId > 0 || $workTypeId > 0): ?>
                    ลองเปลี่ยนตัวกรองแล้วค้นหาใหม่
                <?php else: ?>
                    ยังไม่มีรายการขายใน<?= thaiMonthName($month) ?> <?= toBuddhistYear($year) ?>
                <?php endif; ?>
            </p>
            <a href="<?= url(['page' => 'sales', 'action' => 'create']) ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>บันทึกรายการแรก
            </a>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:50px">#</th>
                        <th>วันที่</th>
                        <th>ลูกค้า</th>
                        <th>สินค้า</th>
                        <th>ประเภทงาน</th>
                        <th class="text-center">จำนวน</th>
                        <th class="text-end">ยอดขาย (บาท)</th>
                        <th>หมายเหตุ</th>
                        <th class="text-center" style="width:110px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($sales as $idx => $sale): ?>
                    <tr>
                        <!-- # -->
                        <td class="text-center text-muted small">
                            <?= $idx + 1 ?>
                        </td>

                        <!-- Date -->
                        <td>
                            <span class="fw-semibold"><?= formatDate($sale['sale_date']) ?></span>
                        </td>

                        <!-- Customer -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge rounded-circle bg-secondary text-white"
                                      style="width:28px;height:28px;display:inline-flex!important;align-items:center;justify-content:center;font-size:.75rem">
                                    <?= mb_substr(e($sale['customer_name']), 0, 1) ?>
                                </span>
                                <span><?= e($sale['customer_name']) ?></span>
                            </div>
                        </td>

                        <!-- Product -->
                        <td>
                            <span class="badge bg-light text-dark border fw-normal">
                                <i class="bi bi-box-seam me-1"></i><?= e($sale['product_name']) ?>
                            </span>
                        </td>

                        <!-- Work type -->
                        <td>
                            <?php if ((int)$sale['work_type_id'] === 1): ?>
                                <span class="badge badge-wt1 rounded-pill">
                                    <i class="bi bi-cpu me-1"></i><?= e($sale['work_type_name']) ?>
                                </span>
                            <?php else: ?>
                                <span class="badge badge-wt2 rounded-pill">
                                    <i class="bi bi-tools me-1"></i><?= e($sale['work_type_name']) ?>
                                </span>
                            <?php endif; ?>
                        </td>

                        <!-- Quantity -->
                        <td class="text-center">
                            <span class="fw-semibold text-amount"><?= number_format((int)$sale['quantity']) ?></span>
                            <span class="text-muted small"> ชิ้น</span>
                        </td>

                        <!-- Price -->
                        <td class="text-end">
                            <span class="fw-bold text-success text-amount">
                                <?= formatMoney($sale['price']) ?>
                            </span>
                        </td>

                        <!-- Note -->
                        <td>
                            <?php if (!empty($sale['note'])): ?>
                                <span class="text-muted small"
                                      data-bs-toggle="tooltip"
                                      title="<?= e($sale['note']) ?>">
                                    <?= e(mb_strimwidth($sale['note'], 0, 30, '…')) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Edit -->
                                <a href="<?= url(['page' => 'sales', 'action' => 'edit', 'id' => $sale['id']]) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip" title="แก้ไข">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <!-- Delete -->
                                <form method="POST"
                                      action="<?= url(['page' => 'sales', 'action' => 'delete']) ?>"
                                      class="form-delete d-inline"
                                      data-confirm="ต้องการลบรายการขายของ «<?= e($sale['customer_name']) ?>» ใช่หรือไม่?">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $sale['id'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip" title="ลบ">
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- Table footer totals -->
                <tfoot class="table-light">
                    <tr class="fw-bold">
                        <td colspan="5" class="text-end text-muted">
                            <?php if ($customerId > 0): ?>
                                รวมของ <?= e($customerName) ?> (<?= number_format(count($sales)) ?> รายการ):
                            <?php elseif ($productId > 0 || $workTypeId > 0): ?>
                                รวม (<?= number_format(count($sales)) ?> รายการที่กรอง):
                            <?php else: ?>
                                รวม (<?= number_format(count($sales)) ?> รายการที่กรอง):
                            <?php endif; ?>
                        </td>
                        <td class="text-center text-amount">
                            <?= number_format(array_sum(array_column($sales, 'quantity'))) ?> ชิ้น
                        </td>
                        <td class="text-end text-amount text-success">
                            <?= formatMoney(array_sum(array_column($sales, 'price'))) ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                    <?php if (($productId > 0 || $workTypeId > 0) && $customerId === 0): ?>
                    <tr class="table-warning">
                        <td colspan="5" class="text-end text-muted small">
                            รวมทั้งเดือน (ไม่กรอง):
                        </td>
                        <td class="text-center text-amount small">
                            <?= number_format($totalQty) ?> ชิ้น
                        </td>
                        <td class="text-end text-amount small text-success">
                            <?= formatMoney($totalAmount) ?>
                        </td>
                        <td colspan="2"></td>
                    </tr>
                    <?php endif; ?>
                </tfoot>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ── Month quick nav ──────────────────────────────────────────── -->
<div class="d-flex flex-wrap justify-content-center gap-1 mt-4">
    <?php foreach ($months as $num => $name): ?>
        <?php
        $isActive = ((int)$month === $num);
        $linkParams = ['page' => 'sales', 'month' => $num, 'year' => $year];
        ?>
        <a href="<?= url($linkParams) ?>"
           class="btn btn-sm <?= $isActive ? 'btn-dark' : 'btn-outline-secondary' ?>">
            <?= e($name) ?>
        </a>
    <?php endforeach; ?>
</div>

<!-- JavaScript: Dynamic product filtering based on category -->
<script>
(function () {
    'use strict';

    const categoryFilter = document.getElementById('categoryFilter');
    const productFilter = document.getElementById('productFilter');
    
    if (!categoryFilter || !productFilter) return;

    // Store all original products
    const allProducts = Array.from(productFilter.options)
        .filter(opt => opt.value !== '0')
        .map(opt => ({ id: parseInt(opt.value), name: opt.textContent }));

    // All products data embedded from server (with category mapping)
    const allProductsData = <?php 
        echo json_encode(array_map(function($p) {
            return ['id' => $p['id'], 'name' => $p['name']];
        }, $products));
    ?>;

    // Get category for each product from the current page data
    const productCategories = {};
    
    // Fetch products by category when selector changes
    categoryFilter.addEventListener('change', function() {
        const selectedCategoryId = parseInt(this.value);

        if (selectedCategoryId === 0) {
            // Show all products
            repopulateProducts(allProductsData);
        } else {
            // Fetch only products in selected category
            fetch('index.php?page=sales&action=get_products&category_id=' + selectedCategoryId)
                .then(response => response.json())
                .then(products => {
                    repopulateProducts(products);
                })
                .catch(error => {
                    console.error('Error fetching products:', error);
                    // Fallback to all products
                    repopulateProducts(allProductsData);
                });
        }
    });

    // Repopulate the product dropdown with new options
    function repopulateProducts(products) {
        // Get currently selected value
        const currentValue = productFilter.value;

        // Clear all options except the first one ("— ทุกสินค้า —")
        while (productFilter.options.length > 1) {
            productFilter.remove(1);
        }

        // Add new products
        products.forEach(product => {
            const option = document.createElement('option');
            option.value = product.id;
            option.textContent = product.name;
            productFilter.appendChild(option);
        });

        // Try to restore previous selection if it's still available
        if (currentValue !== '0' && products.some(p => p.id === parseInt(currentValue))) {
            productFilter.value = currentValue;
        } else {
            productFilter.value = '0';
        }
    }
})();
</script>

