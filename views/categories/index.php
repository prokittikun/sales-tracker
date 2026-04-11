<?php declare(strict_types=1); ?>

<?php $pageTitle = 'จัดการหมวดหมู่สินค้า'; ?>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Page Header                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-4">
    <div>
        <h4 class="mb-1 fw-bold">
            <i class="bi bi-tag me-2 text-primary"></i>จัดการหมวดหมู่สินค้า
        </h4>
        <p class="text-muted mb-0 small">
            หมวดหมู่ทั้งหมด <?= number_format(count($categories)) ?> หมวดหมู่
        </p>
    </div>
    <a href="<?= url(['page' => 'categories', 'action' => 'create']) ?>"
       class="btn btn-primary d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle-fill"></i>
        <span>เพิ่มหมวดหมู่ใหม่</span>
    </a>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Summary Card                                            ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="row g-3 mb-4">
    <div class="col-12 col-md-6">
        <div class="card summary-card border-primary h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">หมวดหมู่</p>
                        <h4 class="fw-bold mb-0 text-amount"><?= number_format(count($categories)) ?></h4>
                        <small class="text-muted">หมวดหมู่ทั้งหมด</small>
                    </div>
                    <div class="fs-2 text-primary opacity-50">
                        <i class="bi bi-tag-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12 col-md-6">
        <div class="card summary-card border-info h-100">
            <div class="card-body py-3 px-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <p class="text-muted small mb-1">สินค้ารวม</p>
                        <h4 class="fw-bold mb-0 text-amount">
                            <?= number_format(array_sum(array_column($categories, 'product_count'))) ?>
                        </h4>
                        <small class="text-muted">สินค้า</small>
                    </div>
                    <div class="fs-2 text-info opacity-50">
                        <i class="bi bi-box-seam-fill"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Categories Table                                        ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<div class="card">
    <div class="card-header bg-white d-flex align-items-center justify-content-between py-2 px-3">
        <span class="fw-semibold">
            <i class="bi bi-list-ul me-1"></i>รายชื่อหมวดหมู่
        </span>
        <a href="<?= url(['page' => 'categories', 'action' => 'create']) ?>"
           class="btn btn-sm btn-outline-primary">
            <i class="bi bi-plus me-1"></i>เพิ่มหมวดหมู่
        </a>
    </div>

    <?php if (empty($categories)): ?>
        <!-- Empty state -->
        <div class="card-body text-center py-5">
            <i class="bi bi-tag text-muted" style="font-size:3rem"></i>
            <p class="text-muted mt-3 mb-1 fw-semibold">ยังไม่มีหมวดหมู่ในระบบ</p>
            <p class="text-muted small mb-3">เริ่มต้นด้วยการเพิ่มหมวดหมู่แรก</p>
            <a href="<?= url(['page' => 'categories', 'action' => 'create']) ?>"
               class="btn btn-primary btn-sm">
                <i class="bi bi-plus-circle me-1"></i>เพิ่มหมวดหมู่ใหม่
            </a>
        </div>

    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th class="text-center" style="width:50px">#</th>
                        <th>ชื่อหมวดหมู่</th>
                        <th class="text-center" style="width:120px">
                            <i class="bi bi-box-seam me-1"></i>สินค้า
                        </th>
                        <th class="text-center" style="width:150px">สร้างเมื่อ</th>
                        <th class="text-center" style="width:100px">จัดการ</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categories as $idx => $category): ?>
                    <tr>
                        <!-- # -->
                        <td class="text-center text-muted small">
                            <?= $idx + 1 ?>
                        </td>

                        <!-- Category name -->
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-light text-dark border rounded-circle"
                                      style="width:32px;height:32px;display:inline-flex!important;align-items:center;justify-content:center;font-size:.8rem;font-weight:700;">
                                    <?= mb_substr(e($category['name']), 0, 1) ?>
                                </span>
                                <div>
                                    <div class="fw-semibold"><?= e($category['name']) ?></div>
                                    <div class="text-muted" style="font-size:.75rem">
                                        แก้ไขล่าสุด <?= formatDate($category['updated_at']) ?>
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Product count -->
                        <td class="text-center">
                            <?php if ((int)$category['product_count'] > 0): ?>
                                <span class="badge bg-info text-dark">
                                    <?= number_format((int)$category['product_count']) ?>
                                </span>
                            <?php else: ?>
                                <span class="text-muted small">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Created at -->
                        <td class="text-center small text-muted">
                            <?= formatDate($category['created_at']) ?>
                        </td>

                        <!-- Actions -->
                        <td class="text-center">
                            <div class="d-flex gap-1 justify-content-center">
                                <!-- Edit -->
                                <a href="<?= url(['page' => 'categories', 'action' => 'edit', 'id' => $category['id']]) ?>"
                                   class="btn btn-sm btn-outline-primary"
                                   data-bs-toggle="tooltip"
                                   title="แก้ไข">
                                    <i class="bi bi-pencil-fill"></i>
                                </a>

                                <!-- Delete -->
                                <form method="POST"
                                      action="<?= url(['page' => 'categories', 'action' => 'delete']) ?>"
                                      class="form-delete d-inline"
                                      data-confirm="ต้องการลบหมวดหมู่ «<?= e($category['name']) ?>» ออกจากระบบใช่หรือไม่?">
                                    <?php csrfField(); ?>
                                    <input type="hidden" name="id" value="<?= $category['id'] ?>">
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-danger"
                                            data-bs-toggle="tooltip"
                                            title="<?= (int)$category['product_count'] > 0 ? 'ไม่สามารถลบได้ เนื่องจากมีสินค้าอ้างอิง' : 'ลบ' ?>"
                                            <?= (int)$category['product_count'] > 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash-fill"></i>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
