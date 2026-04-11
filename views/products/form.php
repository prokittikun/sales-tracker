<?php
declare(strict_types=1);

$isEdit        = $product !== null;
$pageTitle     = $pageTitle ?? ($isEdit ? 'แก้ไขสินค้า' : 'เพิ่มสินค้าใหม่');
$defaultName   = $isEdit ? $product['name'] : '';
$defaultCategory = $isEdit ? $product['category_id'] : '';

$defaultName     = old('name', $defaultName);
$defaultCategory = old('category_id', $defaultCategory);
oldClear();
?>

<!-- Page Header -->
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-1">
                <li class="breadcrumb-item">
                    <a href="<?= url(['page' => 'products']) ?>" class="text-decoration-none">
                        <i class="bi bi-box-seam me-1"></i>สินค้า
                    </a>
                </li>
                <li class="breadcrumb-item active"><?= e($pageTitle) ?></li>
            </ol>
        </nav>
        <h4 class="fw-bold mb-0">
            <?php if ($isEdit): ?>
                <i class="bi bi-pencil-square text-warning me-2"></i><?= e($pageTitle) ?>
            <?php else: ?>
                <i class="bi bi-plus-circle-fill text-primary me-2"></i><?= e($pageTitle) ?>
            <?php endif; ?>
        </h4>
    </div>
    <a href="<?= url(['page' => 'products']) ?>" class="btn btn-outline-secondary">
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
                    <?= $isEdit ? 'แก้ไขข้อมูลสินค้า' : 'กรอกข้อมูลสินค้าใหม่' ?>
                </h6>
            </div>

            <div class="card-body p-4">
                <form method="POST" action="<?= e($formAction) ?>" novalidate>
                    <?php csrfField(); ?>

                    <!-- ชื่อสินค้า -->
                    <div class="mb-4">
                        <label for="name" class="form-label fw-semibold required-star">
                            <i class="bi bi-box-seam me-1 text-primary"></i>ชื่อสินค้า
                        </label>
                        <input
                            type="text"
                            id="name"
                            name="name"
                            class="form-control form-control-lg"
                            placeholder="เช่น สินค้า A, เครื่องปรับอากาศ 9000 BTU"
                            value="<?= e($defaultName) ?>"
                            maxlength="255"
                            required
                            autofocus
                        >
                        <div class="form-text text-muted d-flex justify-content-between">
                            <span><i class="bi bi-lightbulb me-1"></i>ชื่อสินค้าต้องไม่ซ้ำกับที่มีอยู่แล้ว</span>
                            <span id="nameCount">0</span>/255
                        </div>
                    </div>

                    <!-- หมวดหมู่สินค้า -->
                    <div class="mb-4">
                        <label for="category_id" class="form-label fw-semibold">
                            <i class="bi bi-tag me-1 text-primary"></i>หมวดหมู่สินค้า
                        </label>
                        <select
                            id="category_id"
                            name="category_id"
                            class="form-select form-select-lg"
                        >
                            <option value="">-- ไม่ระบุ --</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= e($cat['id']) ?>"
                                    <?= (string)$defaultCategory === (string)$cat['id'] ? 'selected' : '' ?>>
                                    <?= e($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <?php if ($isEdit): ?>
                    <!-- Info block (edit mode) -->
                    <div class="alert alert-light border py-2 px-3 mb-4 small">
                        <div class="row g-2 text-muted">
                            <div class="col-6">
                                <i class="bi bi-hash me-1"></i>
                                <strong>ID:</strong> <?= e($product['id']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-calendar-plus me-1"></i>
                                <strong>สร้างเมื่อ:</strong> <?= formatDate($product['created_at']) ?>
                            </div>
                            <div class="col-6">
                                <i class="bi bi-calendar-check me-1"></i>
                                <strong>แก้ไขล่าสุด:</strong> <?= formatDate($product['updated_at']) ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Buttons -->
                    <div class="d-flex flex-wrap gap-2 justify-content-end border-top pt-3">
                        <a href="<?= url(['page' => 'products']) ?>"
                           class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle me-1"></i>ยกเลิก
                        </a>
                        <?php if (!$isEdit): ?>
                        <button type="reset" class="btn btn-outline-warning">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>ล้างข้อมูล
                        </button>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary px-4">
                            <?php if ($isEdit): ?>
                                <i class="bi bi-check-circle-fill me-1"></i>บันทึกการแก้ไข
                            <?php else: ?>
                                <i class="bi bi-plus-circle-fill me-1"></i>เพิ่มสินค้า
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
                    ต้องการลบสินค้า <strong><?= e($product['name']) ?></strong> ออกจากระบบ?
                </span>
                <form method="POST"
                      action="<?= url(['page' => 'products', 'action' => 'delete']) ?>"
                      class="form-delete d-inline"
                      data-confirm="คุณแน่ใจหรือไม่ว่าต้องการลบสินค้า «<?= e($product['name']) ?>»? การกระทำนี้ไม่สามารถยกเลิกได้">
                    <?php csrfField(); ?>
                    <input type="hidden" name="id" value="<?= e($product['id']) ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                        <i class="bi bi-trash3 me-1"></i>ลบสินค้านี้
                    </button>
                </form>
            </div>
        </div>
        <?php endif; ?>

    </div><!-- /.col -->
</div><!-- /.row -->

<!-- Character counter script -->
<script>
(function () {
    'use strict';
    var nameEl    = document.getElementById('name');
    var nameCount = document.getElementById('nameCount');

    function update() {
        var len = nameEl ? nameEl.value.length : 0;
        if (nameCount) {
            nameCount.textContent = len;
            nameCount.classList.toggle('text-danger', len > 230);
        }
    }

    if (nameEl) {
        nameEl.addEventListener('input', update);
        update();
    }
})();
</script>
