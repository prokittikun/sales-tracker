<?php
declare(strict_types=1);

$isEdit    = $sale !== null;
$pageTitle = $isEdit ? 'แก้ไขรายการขาย #' . e($sale['id']) : 'บันทึกรายการขายใหม่';
$formAction = $isEdit
    ? url(['page' => 'sales', 'action' => 'edit', 'id' => $sale['id']])
    : url(['page' => 'sales', 'action' => 'create']);

// Default values
$defaultDate       = $isEdit ? $sale['sale_date']    : date('Y-m-d');
$defaultCustomer   = $isEdit ? $sale['customer_id']  : '';
$defaultProduct    = $isEdit ? $sale['product_id']   : '';
$defaultWorkType   = $isEdit ? $sale['work_type_id'] : '';
$defaultQuantity   = $isEdit ? $sale['quantity']     : 1;
$defaultPrice      = $isEdit ? $sale['price']        : '';
$defaultNote       = $isEdit ? ($sale['note'] ?? '') : '';

// If old input exists (validation fail), override defaults
$defaultDate     = old('sale_date',    $defaultDate);
$defaultCustomer = old('customer_id',  $defaultCustomer);
$defaultProduct  = old('product_id',   $defaultProduct);
$defaultWorkType = old('work_type_id', $defaultWorkType);
$defaultQuantity = old('quantity',     $defaultQuantity);
$defaultPrice    = old('price',        $defaultPrice);
$defaultNote     = old('note',         $defaultNote);
oldClear();
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="<?= url(['page' => 'sales']) ?>" class="text-decoration-none">
                        <i class="bi bi-receipt me-1"></i>รายการขาย
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= $pageTitle ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">
            <?php if ($isEdit): ?>
                <i class="bi bi-pencil-square text-warning me-2"></i><?= $pageTitle ?>
            <?php else: ?>
                <i class="bi bi-plus-circle-fill text-primary me-2"></i><?= $pageTitle ?>
            <?php endif; ?>
        </h4>
    </div>
    <a href="<?= url(['page' => 'sales']) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </a>
</div>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-12 col-lg-8 col-xl-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= $isEdit ? 'แก้ไขข้อมูลรายการขาย' : 'กรอกข้อมูลรายการขายใหม่' ?>
                </h6>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="<?= $formAction ?>" novalidate>
                    <?php csrfField(); ?>

                    <!-- ── Row 1: วันที่ + ลูกค้า ─────────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- วันที่ขาย -->
                        <div class="col-sm-6">
                            <label for="sale_date" class="form-label fw-semibold required-star">
                                <i class="bi bi-calendar3 me-1 text-primary"></i>วันที่ขาย
                            </label>
                            <input
                                type="date"
                                id="sale_date"
                                name="sale_date"
                                class="form-control"
                                value="<?= e($defaultDate) ?>"
                                required
                                max="<?= date('Y-m-d') ?>"
                            >
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle"></i>
                                แสดงผลในรูปแบบ วัน/เดือน/ปี (พ.ศ.)
                            </div>
                        </div>

                        <!-- ลูกค้า -->
                        <div class="col-sm-6">
                            <label for="customer_id" class="form-label fw-semibold required-star">
                                <i class="bi bi-person me-1 text-primary"></i>ลูกค้า
                            </label>
                            <div class="input-group">
                                <select id="customer_id" name="customer_id" class="form-select select-searchable" 
                                        data-placeholder="ค้นหาลูกค้า..." required>
                                    <option value="">-- เลือกลูกค้า --</option>
                                    <?php foreach ($customers as $customer): ?>
                                        <option value="<?= e($customer['id']) ?>"
                                            <?= selected($defaultCustomer, $customer['id']) ?>>
                                            <?= e($customer['name']) ?>
                                            <?php if (!empty($customer['phone'])): ?>
                                                (<?= e($customer['phone']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= url(['page' => 'customers', 'action' => 'create']) ?>"
                                   class="btn btn-outline-secondary"
                                   title="เพิ่มลูกค้าใหม่"
                                   data-bs-toggle="tooltip"
                                   target="_blank">
                                    <i class="bi bi-plus"></i>
                                </a>
                            </div>
                            <?php if (empty($customers)): ?>
                                <div class="form-text text-danger">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    ยังไม่มีลูกค้าในระบบ
                                    <a href="<?= url(['page' => 'customers', 'action' => 'create']) ?>">เพิ่มลูกค้า</a>
                                </div>
                            <?php endif; ?>
                        </div>

                    </div>

                    <!-- ── Row 2: สินค้า + ประเภทงาน ──────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- สินค้า -->
                        <div class="col-sm-6">
                            <label for="product_id" class="form-label fw-semibold required-star">
                                <i class="bi bi-box-seam me-1 text-primary"></i>สินค้า
                            </label>
                            <div class="input-group">
                                <select id="product_id" name="product_id" class="form-select select-searchable" 
                                        data-placeholder="ค้นหาสินค้า..." required>
                                    <option value="">-- เลือกสินค้า --</option>
                                    <?php foreach ($products as $product): ?>
                                        <option value="<?= e($product['id']) ?>"
                                            <?= selected($defaultProduct, $product['id']) ?>>
                                            <?= e($product['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="<?= url(['page' => 'products', 'action' => 'create']) ?>"
                                   class="btn btn-outline-secondary"
                                   title="เพิ่มสินค้าใหม่"
                                   data-bs-toggle="tooltip"
                                   target="_blank">
                                    <i class="bi bi-plus"></i>
                                </a>
                            </div>
                            <?php if (empty($products)): ?>
                                <div class="form-text text-danger">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    ยังไม่มีสินค้าในระบบ
                                    <a href="<?= url(['page' => 'products', 'action' => 'create']) ?>">เพิ่มสินค้า</a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- ประเภทงาน -->
                        <div class="col-sm-6">
                            <label class="form-label fw-semibold required-star">
                                <i class="bi bi-tags me-1 text-primary"></i>ประเภทงาน
                            </label>
                            <div class="d-flex flex-column gap-2 mt-1">
                                <?php foreach ($workTypes as $wt): ?>
                                    <div class="form-check form-check-inline">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="work_type_id"
                                            id="wt_<?= e($wt['id']) ?>"
                                            value="<?= e($wt['id']) ?>"
                                            <?= ((string)$defaultWorkType === (string)$wt['id']) ? 'checked' : '' ?>
                                            required
                                        >
                                        <label class="form-check-label" for="wt_<?= e($wt['id']) ?>">
                                            <?php if ($wt['id'] == 1): ?>
                                                <span class="badge badge-wt1 rounded-pill me-1">1</span>
                                            <?php else: ?>
                                                <span class="badge badge-wt2 rounded-pill me-1">2</span>
                                            <?php endif; ?>
                                            <?= e($wt['name']) ?>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                    </div>

                    <!-- ── Row 3: จำนวน + ยอดขาย ────────────────── -->
                    <div class="row g-3 mb-3">

                        <!-- จำนวน -->
                        <div class="col-sm-4">
                            <label for="quantity" class="form-label fw-semibold required-star">
                                <i class="bi bi-123 me-1 text-primary"></i>จำนวน (ชิ้น)
                            </label>
                            <input
                                type="number"
                                id="quantity"
                                name="quantity"
                                class="form-control text-center"
                                value="<?= e($defaultQuantity) ?>"
                                min="1"
                                max="99999"
                                step="1"
                                required
                            >
                        </div>

                        <!-- ยอดขาย -->
                        <div class="col-sm-8">
                            <label for="price" class="form-label fw-semibold required-star">
                                <i class="bi bi-currency-exchange me-1 text-primary"></i>ยอดขาย (บาท)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-light text-muted">฿</span>
                                <input
                                    type="number"
                                    id="price"
                                    name="price"
                                    class="form-control text-amount"
                                    placeholder="0.00"
                                    value="<?= e($defaultPrice) ?>"
                                    min="0"
                                    step="0.01"
                                    required
                                >
                                <span class="input-group-text bg-light text-muted">บาท</span>
                            </div>
                        </div>

                    </div>

                    <!-- ── Row 4: หมายเหตุ ──────────────────────── -->
                    <div class="mb-4">
                        <label for="note" class="form-label fw-semibold">
                            <i class="bi bi-chat-left-text me-1 text-primary"></i>หมายเหตุ
                            <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                        </label>
                        <textarea
                            id="note"
                            name="note"
                            class="form-control"
                            rows="2"
                            maxlength="500"
                            placeholder="รายละเอียดเพิ่มเติม เช่น ชื่อรุ่น, สี, เงื่อนไขพิเศษ..."
                        ><?= e($defaultNote) ?></textarea>
                        <div class="form-text text-end text-muted small">
                            <span id="noteCount">0</span>/500 ตัวอักษร
                        </div>
                    </div>

                    <!-- ── Preview Summary ───────────────────────── -->
                    <div id="previewBox" class="alert alert-info d-none mb-4 py-2" role="status">
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <i class="bi bi-eye fs-5"></i>
                            <span class="fw-semibold">ตัวอย่างรายการ:</span>
                            <span id="previewText" class="font-monospace small"></span>
                        </div>
                    </div>

                    <!-- ── Action Buttons ───────────────────────── -->
                    <div class="d-flex flex-wrap gap-2 justify-content-end border-top pt-3">
                        <a href="<?= url(['page' => 'sales']) ?>"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>ยกเลิก
                        </a>
                        <button type="reset" class="btn btn-outline-warning">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>ล้างข้อมูล
                        </button>
                        <button type="submit" class="btn btn-primary px-4">
                            <?php if ($isEdit): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>บันทึกการแก้ไข
                            <?php else: ?>
                                <i class="bi bi-plus-circle-fill me-1"></i>บันทึกรายการขาย
                            <?php endif; ?>
                        </button>
                    </div>

                </form>
            </div><!-- /.card-body -->
        </div><!-- /.card -->

        <?php if ($isEdit): ?>
        <!-- Delete card (edit mode only) -->
        <div class="card border-danger mt-3">
            <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <span class="text-danger small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    ต้องการลบรายการขาย #<?= e($sale['id']) ?> ออกจากระบบ?
                </span>
                <form method="POST"
                      action="<?= url(['page' => 'sales', 'action' => 'delete']) ?>"
                      class="form-delete d-inline"
                      data-confirm="คุณแน่ใจหรือไม่ว่าต้องการลบรายการขายนี้? การกระทำนี้ไม่สามารถยกเลิกได้">
                    <?php csrfField(); ?>
                    <input type="hidden" name="id" value="<?= e($sale['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash3 me-1"></i>ลบรายการนี้
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.col -->
</div><!-- /.row -->

<!-- ── Preview & note counter scripts ─────────────────────── -->
<script>
(function () {
    'use strict';

    var noteEl     = document.getElementById('note');
    var noteCount  = document.getElementById('noteCount');
    var previewBox = document.getElementById('previewBox');
    var previewTxt = document.getElementById('previewText');

    var productSel  = document.getElementById('product_id');
    var qtySel      = document.getElementById('quantity');
    var priceSel    = document.getElementById('price');
    var wtRadios    = document.querySelectorAll('input[name="work_type_id"]');

    // Note character counter
    function updateNoteCount() {
        var len = noteEl ? noteEl.value.length : 0;
        if (noteCount) {
            noteCount.textContent = len;
            noteCount.classList.toggle('text-danger', len > 450);
        }
    }
    if (noteEl) {
        noteEl.addEventListener('input', updateNoteCount);
        updateNoteCount();
    }

    // Live preview
    function updatePreview() {
        var productText = productSel && productSel.selectedIndex > 0
            ? productSel.options[productSel.selectedIndex].text
            : null;
        var qty   = qtySel  ? parseInt(qtySel.value,  10) : null;
        var price = priceSel ? parseFloat(priceSel.value) : null;
        var wtText = null;
        wtRadios.forEach(function (r) {
            if (r.checked) {
                var lbl = document.querySelector('label[for="' + r.id + '"]');
                wtText  = lbl ? lbl.textContent.trim() : r.value;
            }
        });

        if (!productText || !qty || !price || !wtText || isNaN(qty) || isNaN(price)) {
            previewBox.classList.add('d-none');
            return;
        }

        previewTxt.textContent =
            productText + '  ×' + qty + '  ราคา ฿' +
            price.toLocaleString('th-TH', {minimumFractionDigits: 2}) +
            '  [' + wtText.replace(/[0-9]\s*/, '').trim() + ']';
        previewBox.classList.remove('d-none');
    }

    [productSel, qtySel, priceSel].forEach(function (el) {
        if (el) el.addEventListener('input', updatePreview);
    });
    wtRadios.forEach(function (r) {
        r.addEventListener('change', updatePreview);
    });
    updatePreview();

})();
</script>
