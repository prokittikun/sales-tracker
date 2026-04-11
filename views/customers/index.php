<?php declare(strict_types=1); ?>

<?php $pageTitle = 'จัดการลูกค้า'; ?>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Page Header                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-people me-2 text-primary"></i>จัดการลูกค้า
        </h4>
        <p class="text-muted mb-0 small">
            ลูกค้าทั้งหมด <?= number_format(count($customers)) ?> ราย
        </p>
    </div>
    <a href="<?= url(['page' => 'customers', 'action' => 'create']) ?>"
       class="btn btn-primary d-flex align-items-center gap-2">
        <i class="bi bi-person-plus-fill"></i>
        <span>เพิ่มลูกค้าใหม่</span>
    </a>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Summary Cards                                           ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<?php
$overallTotalOrders  = array_sum(array_column($customers, 'total_orders'));
$overallTotalQty     = array_sum(array_column($customers, 'total_qty'));
$overallTotalAmount  = array_sum(array_column($customers, 'total_amount'));
$overallWt1Amount    = array_sum(array_column($customers, 'wt1_amount'));
$overallWt2Amount    = array_sum(array_column($customers, 'wt2_amount'));
?>
<div class="row g-3 mb-4">

    <!-- Total customers -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-primary h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">ลูกค้าทั้งหมด</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= number_format(count($customers)) ?></h4>
                        <small class="text-muted">ราย</small>
                    </div>
                    <div class="fs-2 text-primary opacity-50">
                        <i class="bi bi-people-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total orders -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-info h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">รายการขายรวม</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= number_format($overallTotalOrders) ?></h4>
                        <small class="text-muted">รายการ</small>
                    </div>
                    <div class="fs-2 text-info opacity-50">
                        <i class="bi bi-receipt-cutoff"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WT1 amount -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-secondary h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">ขายเฉพาะเครื่อง</p>
                        <h5 class="fw-bold mb-0 text-amount"><?= formatMoney($overallWt1Amount) ?></h5>
                        <small class="text-muted">บาท</small>
                    </div>
                    <div class="fs-2 opacity-50" style="color:#0d6efd">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- WT2 amount -->
    <div class="col-6 col-lg-3">
        <div class="card summary-card border-success h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">ขายพร้อมติดตั้ง</p>
                        <h5 class="fw-bold mb-0 text-amount"><?= formatMoney($overallWt2Amount) ?></h5>
                        <small class="text-muted">บาท</small>
                    </div>
                    <div class="fs-2 text-success opacity-50">
                        <i class="bi bi-tools"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Customers Table                                         ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold">
            <i class="bi bi-list-ul me-1"></i>รายชื่อลูกค้า
        </span>
        <a href="<?= url(['page' => 'customers', 'action' => 'create']) ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-person-plus me-1"></i>เพิ่มลูกค้า
        </a>
    </div>

    <?php if (empty($customers)): ?>
        <!-- Empty state -->
        <div class="card-body text-center py-5">
            <i class="bi bi-person-x text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-1 fw-semibold">ยังไม่มีลูกค้าในระบบ</p>
            <p class="text-muted small mb-3">เริ่มต้นด้วยการเพิ่มลูกค้าคนแรก</p>
            <a href="<?= url(['page' => 'customers', 'action' => 'create']) ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-person-plus-fill me-1"></i>เพิ่มลูกค้าใหม่
            </a>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:50px">#</th>
                        <th>ชื่อลูกค้า</th>
                        <th>เบอร์โทร</th>
                        <!-- Work type 1 -->
                        <th class="text-center" colspan="2" style="background-color:#0b5ed7;">
                            <i class="bi bi-cpu me-1"></i>ขายเฉพาะเครื่อง
                        </th>
                        <!-- Work type 2 -->
                        <th class="text-center" colspan="2" style="background-color:#157347;">
                            <i class="bi bi-tools me-1"></i>ขายพร้อมติดตั้ง
                        </th>
                        <!-- Totals -->
                        <th class="text-center" colspan="2" style="background-color:#343a40;">
                            <i class="bi bi-sigma me-1"></i>รวมทั้งหมด
                        </th>
                        <th class="text-center" style="width:100px">จัดการ</th>
                    </tr>
                    <tr class="small" style="font-size:.78rem">
                        <th></th>
                        <th></th>
                        <th></th>
                        <th class="text-center text-info" style="background-color:#0b5ed7;">จำนวน (ชิ้น)</th>
                        <th class="text-center text-info" style="background-color:#0b5ed7;">ยอดขาย (บาท)</th>
                        <th class="text-center" style="background-color:#157347;color:#a3e6b9;">จำนวน (ชิ้น)</th>
                        <th class="text-center" style="background-color:#157347;color:#a3e6b9;">ยอดขาย (บาท)</th>
                        <th class="text-center text-warning-emphasis" style="background-color:#343a40;">จำนวน (ชิ้น)</th>
                        <th class="text-center text-warning-emphasis" style="background-color:#343a40;">ยอดขาย (บาท)</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($customers as $idx => $customer): ?>
                    <tr>
                        <!-- # -->
                        <td class="text-center text-muted small">
                            <?= $idx + 1 ?>
                        </td>

                        <!-- Customer name -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center fw-bold"
                                      style="width:34px;height:34px;font-size:.8rem;flex-shrink:0;">
                                    <?= mb_substr(e($customer['name']), 0, 1) ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= e($customer['name']) ?></div>
                                    <?php if (!empty($customer['note'])): ?>
                                        <div class="text-muted" style="font-size:.75rem">
                                            <?= e(mb_strimwidth($customer['note'], 0, 40, '…')) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div class="text-muted" style="font-size:.72rem">
                                        เพิ่มเมื่อ <?= formatDate($customer['created_at']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Phone -->
                        <td>
                            <?php if (!empty($customer['phone'])): ?>
                                <a href="tel:<?= e(preg_replace('/\s+/', '', $customer['phone'])) ?>"
                                   class="text-decoration-none text-dark">
                                    <i class="bi bi-telephone me-1 text-muted small"></i><?= e($customer['phone']) ?>
                                </a>
                            <?php else: ?>
                                <span class="text-muted">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT1 qty -->
                        <td class="text-center">
                            <?php if ((int)$customer['wt1_qty'] > 0): ?>
                                <span class="badge badge-wt1 rounded-pill text-amount">
                                    <?= number_format((int)$customer['wt1_qty']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT1 amount -->
                        <td class="text-end">
                            <?php if ((float)$customer['wt1_amount'] > 0): ?>
                                <span class="fw-semibold text-primary text-amount">
                                    <?= formatMoney($customer['wt1_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT2 qty -->
                        <td class="text-center">
                            <?php if ((int)$customer['wt2_qty'] > 0): ?>
                                <span class="badge badge-wt2 rounded-pill text-amount">
                                    <?= number_format((int)$customer['wt2_qty']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- WT2 amount -->
                        <td class="text-end">
                            <?php if ((float)$customer['wt2_amount'] > 0): ?>
                                <span class="fw-semibold text-success text-amount">
                                    <?= formatMoney($customer['wt2_amount']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total qty -->
                        <td class="text-center">
                            <?php if ((int)$customer['total_qty'] > 0): ?>
                                <span class="fw-bold text-amount">
                                    <?= number_format((int)$customer['total_qty']) ?>
                                </span>
                                <span class="text-muted small"> ชิ้น</span>
                            <?php else: ?>
                                <span class="text-muted small">ยังไม่มีการขาย</span>
                            <?php endif; ?>
                        </td>

                        <!-- Total amount -->
                        <td class="text-end">
                            <?php if ((float)$customer['total_amount'] > 0): ?>
                                <span class="fw-bold text-dark text-amount">
                                    <?= formatMoney($customer['total_amount']) ?>
                                </span>
                                <div class="text-muted" style="font-size:.72rem">
                                    <?= number_format((int)$customer['total_orders']) ?> รายการ
                                </div>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Actions -->
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- View sales -->
                                <a href="<?= url(['page' => 'sales', 'customer_id' => $customer['id'], 'month' => 0, 'year' => date('Y')]) ?>"
                                   class="btn btn-sm btn-outline-secondary"
                                   data-bs-toggle="tooltip"
                                   title="ดูรายการขาย">
                                    <i class="bi bi-eye-fill"></i>
                                </a>

                                <!-- Edit -->
                                <a href="<?= url(['page' => 'customers', 'action' => 'edit', 'id' => $customer['id']]) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="แก้ไข">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <!-- Delete -->
                                <form method="POST"
                                      action="<?= url(['page' => 'customers', 'action' => 'delete']) ?>"
                                      class="form-delete d-inline"
                                      data-confirm="ต้องการลบลูกค้า «<?= e($customer['name']) ?>» ออกจากระบบใช่หรือไม่?">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $customer['id'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip"
                                            title="<?= (int)$customer['total_orders'] > 0 ? 'ไม่สามารถลบได้ เนื่องจากมีรายการขาย' : 'ลบ' ?>"
                                            <?= (int)$customer['total_orders'] > 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>

                <!-- Grand total footer -->
                <tfoot class="table-secondary fw-bold">
                    <tr>
                        <td colspan="3" class="text-end text-muted">
                            รวมทั้งหมด (<?= number_format(count($customers)) ?> ลูกค้า):
                        </td>
                        <td class="text-center text-amount">
                            <?= number_format(array_sum(array_column($customers, 'wt1_qty'))) ?>
                        </td>
                        <td class="text-end text-amount text-primary">
                            <?= formatMoney($overallWt1Amount) ?>
                        </td>
                        <td class="text-center text-amount">
                            <?= number_format(array_sum(array_column($customers, 'wt2_qty'))) ?>
                        </td>
                        <td class="text-end text-amount text-success">
                            <?= formatMoney($overallWt2Amount) ?>
                        </td>
                        <td class="text-center text-amount">
                            <?= number_format($overallTotalQty) ?> ชิ้น
                        </td>
                        <td class="text-end text-amount">
                            <?= formatMoney($overallTotalAmount) ?>
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>

        <!-- ── Overall progress bar ───────────────────────────────── -->
        <?php if ($overallTotalAmount > 0): ?>
        <div class="card-footer bg-white px-3 py-3">
            <p class="small fw-semibold text-muted mb-2">
                <i class="bi bi-bar-chart-fill me-1"></i>สัดส่วนยอดขายตามประเภทงาน (ลูกค้าทั้งหมด)
            </p>
            <?php
            $wt1Pct = round(($overallWt1Amount / $overallTotalAmount) * 100, 1);
            $wt2Pct = round(($overallWt2Amount / $overallTotalAmount) * 100, 1);
            ?>
            <div class="progress mb-2" style="height:22px;border-radius:.5rem;">
                <?php if ($wt1Pct > 0): ?>
                <div class="progress-bar badge-wt1"
                     role="progressbar"
                     style="width:<?= $wt1Pct ?>%"
                     title="ขายเฉพาะเครื่อง <?= $wt1Pct ?>%">
                    <?php if ($wt1Pct >= 10): ?>
                        <span class="small"><?= $wt1Pct ?>%</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php if ($wt2Pct > 0): ?>
                <div class="progress-bar badge-wt2"
                     role="progressbar"
                     style="width:<?= $wt2Pct ?>%"
                     title="ขายพร้อมติดตั้ง <?= $wt2Pct ?>%">
                    <?php if ($wt2Pct >= 10): ?>
                        <span class="small"><?= $wt2Pct ?>%</span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
            <div class="d-flex gap-4 flex-wrap small text-muted">
                <span>
                    <span class="badge badge-wt1 me-1">&nbsp;</span>
                    ขายเฉพาะเครื่อง:
                    <strong class="text-dark">฿<?= formatMoney($overallWt1Amount) ?></strong>
                    (<?= $wt1Pct ?>%)
                </span>
                <span>
                    <span class="badge badge-wt2 me-1">&nbsp;</span>
                    ขายพร้อมติดตั้ง:
                    <strong class="text-dark">฿<?= formatMoney($overallWt2Amount) ?></strong>
                    (<?= $wt2Pct ?>%)
                </span>
            </div>
        </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- ── Top customers cards ──────────────────────────────────── -->
<?php
$topCustomers = array_filter($customers, fn($c) => (float)$c['total_amount'] > 0);
usort($topCustomers, fn($a, $b) => (float)$b['total_amount'] <=> (float)$a['total_amount']);
$topCustomers = array_slice($topCustomers, 0, 6);
?>
<?php if (!empty($topCustomers)): ?>
<div class="mt-4">
    <div class="mb-3 d-flex align-items-center justify-content-between">
        <h6 class="fw-semibold text-muted mb-0">
            <i class="bi bi-trophy me-1 text-warning"></i>ลูกค้าที่มียอดซื้อสูงสุด
        </h6>
        <div style="flex: 1; max-width: 250px;">
            <input type="text" 
                   id="topCustomerSearch" 
                   class="form-control form-control-sm" 
                   placeholder="ค้นหาชื่อลูกค้า..."
                   style="font-size: 0.9rem;">
        </div>
    </div>
</div>
<div class="row g-3" id="topCustomersGrid">
    <?php foreach ($topCustomers as $rank => $customer): ?>
    <div class="col-12 col-md-6 col-xl-4 top-customer-card" data-customer-name="<?= e(mb_strtolower($customer['name'])) ?>">
        <div class="card h-100">
            <div class="card-header bg-white py-2 d-flex align-items-center justify-content-between gap-2">
                <div class="d-flex align-items-center gap-2">
                    <?php if ($rank === 0): ?>
                        <span class="fs-5">🥇</span>
                    <?php elseif ($rank === 1): ?>
                        <span class="fs-5">🥈</span>
                    <?php elseif ($rank === 2): ?>
                        <span class="fs-5">🥉</span>
                    <?php else: ?>
                        <span class="badge bg-secondary rounded-circle"
                              style="width:28px;height:28px;display:inline-flex;align-items:center;justify-content:center;">
                            <?= $rank + 1 ?>
                        </span>
                    <?php endif; ?>
                    <span class="fw-semibold"><?= e($customer['name']) ?></span>
                </div>
                <div class="d-flex gap-1">
                    <!-- View sales -->
                    <a href="<?= url(['page' => 'sales', 'customer_id' => $customer['id'], 'month' => 0, 'year' => date('Y')]) ?>"
                       class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2"
                       data-bs-toggle="tooltip"
                       title="ดูรายการขาย">
                        <i class="bi bi-eye-fill"></i>
                    </a>
                    <!-- Edit -->
                    <a href="<?= url(['page' => 'customers', 'action' => 'edit', 'id' => $customer['id']]) ?>"
                       class="btn btn-xs btn-outline-secondary btn-sm py-0 px-2"
                       data-bs-toggle="tooltip"
                       title="แก้ไข">
                        <i class="bi bi-pencil"></i>
                    </a>
                </div>
            </div>
            <div class="card-body pb-2">

                <!-- Mini stats -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="text-center bg-light rounded p-2">
                            <div class="small text-muted">รายการ</div>
                            <div class="fw-bold text-amount fs-5"><?= number_format((int)$customer['total_orders']) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center bg-light rounded p-2">
                            <div class="small text-muted">รวมยอด (฿)</div>
                            <div class="fw-bold text-amount" style="font-size:.95rem"><?= formatMoney($customer['total_amount']) ?></div>
                        </div>
                    </div>
                </div>

                <!-- Breakdown table -->
                <table class="table table-sm mb-0 small">
                    <thead>
                        <tr class="table-light">
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
                                <?= number_format((int)$customer['wt1_qty']) ?> ชิ้น
                            </td>
                            <td class="text-end text-amount text-primary">
                                <?= formatMoney($customer['wt1_amount']) ?>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <span class="badge badge-wt2 rounded-pill me-1">2</span>
                                ขายพร้อมติดตั้ง
                            </td>
                            <td class="text-center text-amount">
                                <?= number_format((int)$customer['wt2_qty']) ?> ชิ้น
                            </td>
                            <td class="text-end text-amount text-success">
                                <?= formatMoney($customer['wt2_amount']) ?>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <!-- Mini progress -->
                <?php
                $cTotalAmt = (float)$customer['total_amount'];
                $cWt1Pct   = $cTotalAmt > 0 ? round(((float)$customer['wt1_amount'] / $cTotalAmt) * 100, 1) : 0;
                $cWt2Pct   = $cTotalAmt > 0 ? round(((float)$customer['wt2_amount'] / $cTotalAmt) * 100, 1) : 0;
                ?>
                <div class="progress mt-2" style="height:8px;border-radius:4px;">
                    <?php if ($cWt1Pct > 0): ?>
                    <div class="progress-bar badge-wt1"
                         role="progressbar"
                         style="width:<?= $cWt1Pct ?>%"
                         title="ขายเฉพาะเครื่อง <?= $cWt1Pct ?>%">
                    </div>
                    <?php endif; ?>
                    <?php if ($cWt2Pct > 0): ?>
                    <div class="progress-bar badge-wt2"
                         role="progressbar"
                         style="width:<?= $cWt2Pct ?>%"
                         title="ขายพร้อมติดตั้ง <?= $cWt2Pct ?>%">
                    </div>
                    <?php endif; ?>
                </div>

            </div>
            <?php if (!empty($customer['phone'])): ?>
            <div class="card-footer bg-white py-2 text-center">
                <a href="tel:<?= e(preg_replace('/\s+/', '', $customer['phone'])) ?>"
                   class="btn btn-sm btn-outline-success w-100">
                    <i class="bi bi-telephone me-1"></i><?= e($customer['phone']) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Search filter script -->
<script>
(() => {
    const searchInput = document.getElementById('topCustomerSearch');
    const cards = document.querySelectorAll('.top-customer-card');
    let debounceTimer;

    const filterCards = () => {
        const searchTerm = searchInput.value.trim().toLowerCase();

        cards.forEach(card => {
            const customerName = card.getAttribute('data-customer-name') || '';
            const matches = customerName.includes(searchTerm);
            card.style.display = matches ? '' : 'none';
        });
    };

    searchInput.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(filterCards, 300);
    });
})();
</script>
<?php endif; ?>
