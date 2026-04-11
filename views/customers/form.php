<?php
declare(strict_types=1);

$isEdit       = $customer !== null;
$pageTitle    = $pageTitle ?? ($isEdit ? 'แก้ไขข้อมูลลูกค้า' : 'เพิ่มลูกค้าใหม่');
$defaultName  = $isEdit ? $customer['name']          : '';
$defaultPhone = $isEdit ? ($customer['phone'] ?? '') : '';
$defaultNote  = $isEdit ? ($customer['note']  ?? '') : '';

// Re-populate from old input on validation failure
$defaultName  = old('name',  $defaultName);
$defaultPhone = old('phone', $defaultPhone);
$defaultNote  = old('note',  $defaultNote);
oldClear();
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="<?= url(['page' => 'customers']) ?>" class="text-decoration-none">
                        <i class="bi bi-people me-1"></i>ลูกค้า
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">
            <?php if ($isEdit): ?>
                <i class="bi bi-pencil-square text-warning me-2"></i><?= e($pageTitle) ?>
            <?php else: ?>
                <i class="bi bi-person-plus-fill text-primary me-2"></i><?= e($pageTitle) ?>
            <?php endif; ?>
        </h4>
    </div>
    <a href="<?= url(['page' => 'customers']) ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left me-1"></i>กลับ
    </a>
</div>

<!-- Form Card -->
<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <h6 class="mb-0 fw-semibold text-muted">
                    <i class="bi bi-info-circle me-1"></i>
                    <?= $isEdit ? 'แก้ไขข้อมูลลูกค้า' : 'กรอกข้อมูลลูกค้าใหม่' ?>
                </h6>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="<?= e($formAction) ?>" novalidate>
                    <?php csrfField(); ?>

                    <!-- ── ชื่อลูกค้า ────────────────────────────── -->
                    <div class="mb-3">
                        <label for="name" class="form-label fw-semibold required-star">
                            <i class="bi bi-person me-1 text-primary"></i>ชื่อลูกค้า
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control form-control-lg"
                            placeholder="เช่น นายสมชาย ใจดี, บริษัท ABC จำกัด"
                            value="<?= e($defaultName) ?>"
                            maxlength="255"
                            required
                            autofocus
                        >
                        <div class="form-text text-muted d-flex justify-content-between">
                            <span><i class="bi bi-lightbulb me-1"></i>ชื่อบุคคล หรือชื่อบริษัท/ร้านค้า</span>
                            <span id="nameCount">0</span>/255
                        </div>
                    </div>

                    <!-- ── เบอร์โทรศัพท์ ──────────────────────────── -->
                    <div class="mb-3">
                        <label for="phone" class="form-label fw-semibold">
                            <i class="bi bi-telephone me-1 text-primary"></i>เบอร์โทรศัพท์
                            <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">
                                <i class="bi bi-telephone-fill text-muted"></i>
                            </span>
                            <input
                                type="tel"
                                id="phone"
                                name="phone"
                                class="form-control"
                                placeholder="เช่น 081-234-5678"
                                value="<?= e($defaultPhone) ?>"
                                maxlength="20"
                            >
                        </div>
                        <div class="form-text text-muted">
                            <i class="bi bi-info-circle me-1"></i>
                            รูปแบบที่รับได้: 081-234-5678, 02-123-4567, +66812345678
                        </div>
                    </div>

                    <!-- ── หมายเหตุ ───────────────────────────────── -->
                    <div class="mb-4">
                        <label for="note" class="form-label fw-semibold">
                            <i class="bi bi-chat-left-text me-1 text-primary"></i>หมายเหตุ
                            <span class="text-muted fw-normal">(ไม่บังคับ)</span>
                        </label>
                        <textarea
                            id="note"
                            name="note"
                            class="form-control"
                            rows="3"
                            maxlength="500"
                            placeholder="ข้อมูลเพิ่มเติม เช่น ที่อยู่, เงื่อนไขพิเศษ, ประวัติการสั่งซื้อ..."
                        ><?= e($defaultNote) ?></textarea>
                        <div class="form-text text-end text-muted small">
                            <span id="noteCount">0</span>/500 ตัวอักษร
                        </div>
                    </div>

                    <?php if ($isEdit): ?>
                    <!-- Info block (edit mode only) -->
                    <div class="alert alert-light border py-2 px-3 mb-4 small">
                        <div class="row g-2 text-muted">
                            <div class="col-6">
                                <i class="bi bi-hash me-1"></i>
                                <strong>ID:</strong> <?= e($customer['id']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-calendar-plus me-1"></i>
                                <strong>สร้างเมื่อ:</strong> <?= formatDate($customer['created_at']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-calendar-check me-1"></i>
                                <strong>แก้ไขล่าสุด:</strong> <?= formatDate($customer['updated_at']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- ── Action Buttons ─────────────────────────── -->
                    <div class="d-flex flex-wrap gap-2 justify-content-end border-top pt-3">
                        <a href="<?= url(['page' => 'customers']) ?>"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>ยกเลิก
                        </a>
                        <?php if (!$isEdit): ?>
                        <button type="reset" class="btn btn-outline-warning" id="btnReset">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>ล้างข้อมูล
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary px-4">
                            <?php if ($isEdit): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>บันทึกการแก้ไข
                            <?php else: ?>
                                <i class="bi bi-person-plus-fill me-1"></i>เพิ่มลูกค้า
                            <?php endif; ?>
                        </button>
                    </div>

                </form>
            </div><!-- /.card-body -->
        </div><!-- /.card -->

        <?php if ($isEdit): ?>
        <!-- Danger zone: Delete -->
        <div class="card border-danger mt-3">
            <div class="card-body py-2 px-3 d-flex align-items-center justify-content-between gap-2 flex-wrap">
                <span class="text-danger small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    ต้องการลบลูกค้า <strong><?= e($customer['name']) ?></strong> ออกจากระบบ?
                </span>
                <form method="POST"
                      action="<?= url(['page' => 'customers', 'action' => 'delete']) ?>"
                      class="form-delete d-inline"
                      data-confirm="คุณแน่ใจหรือไม่ว่าต้องการลบลูกค้า «<?= e($customer['name']) ?>»? การกระทำนี้ไม่สามารถยกเลิกได้">
                    <?php csrfField(); ?>
                    <input type="hidden" name="id" value="<?= e($customer['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash3 me-1"></i>ลบลูกค้านี้
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.col -->
</div><!-- /.row -->

<!-- Character counter & reset scripts -->
<script>
(function () {
    'use strict';

    // ── Name counter ─────────────────────────────────────────
    var nameEl    = document.getElementById('name');
    var nameCount = document.getElementById('nameCount');

    function updateNameCount() {
        var len = nameEl ? nameEl.value.length : 0;
        if (nameCount) {
            nameCount.textContent = len;
            nameCount.classList.toggle('text-danger', len > 230);
        }
    }
    if (nameEl) {
        nameEl.addEventListener('input', updateNameCount);
        updateNameCount();
    }

    // ── Note counter ─────────────────────────────────────────
    var noteEl    = document.getElementById('note');
    var noteCount = document.getElementById('noteCount');

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

    // ── Reset button: also reset counters ────────────────────
    var btnReset = document.getElementById('btnReset');
    if (btnReset) {
        btnReset.addEventListener('click', function () {
            setTimeout(function () {
                updateNameCount();
                updateNoteCount();
            }, 10);
        });
    }

})();
</script>
