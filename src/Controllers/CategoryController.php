<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

// ============================================================
// CategoryController
// ============================================================
// Handles CRUD operations for product categories.
//
// Routes:
//   GET  ?page=categories                      → index()
//   GET  ?page=categories&action=create        → create()
//   POST ?page=categories&action=create        → create()  (save)
//   GET  ?page=categories&action=edit&id=X     → edit()
//   POST ?page=categories&action=edit&id=X     → edit()    (save)
//   POST ?page=categories&action=delete        → delete()
// ============================================================

class CategoryController
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────
    // index() — List all categories
    // ──────────────────────────────────────────────────────────
    public function index(): void
    {
        $stmt = $this->pdo->query("
            SELECT
                c.id,
                c.name,
                c.created_at,
                c.updated_at,
                COUNT(p.id) AS product_count
            FROM categories c
            LEFT JOIN products p ON p.category_id = c.id
            GROUP BY c.id, c.name, c.created_at, c.updated_at
            ORDER BY c.name ASC
        ");

        $categories = $stmt->fetchAll();

        render('categories/index', compact('categories'));
    }

    // ──────────────────────────────────────────────────────────
    // create() — Show form (GET) or save new category (POST)
    // ──────────────────────────────────────────────────────────
    public function create(): void
    {
        if (isPost()) {
            verifyCsrf();

            $name = trim(post('name', ''));

            // ── Validation ──────────────────────────────────
            $errors = $this->validateName($name);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('categories/form', [
                    'category'   => null,
                    'formAction' => url(['page' => 'categories', 'action' => 'create']),
                    'pageTitle'  => 'เพิ่มหมวดหมู่ใหม่',
                ]);
                return;
            }

            // ── Check duplicate ─────────────────────────────
            if ($this->nameExists($name)) {
                flashInput();
                setFlash('danger', "หมวดหมู่ <strong>" . e($name) . "</strong> มีในระบบแล้ว");
                render('categories/form', [
                    'category'   => null,
                    'formAction' => url(['page' => 'categories', 'action' => 'create']),
                    'pageTitle'  => 'เพิ่มหมวดหมู่ใหม่',
                ]);
                return;
            }

            // ── Insert ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                INSERT INTO categories (name) VALUES (?)
            ");
            $stmt->execute([$name]);

            oldClear();
            setFlash('success', "เพิ่มหมวดหมู่ <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'categories']));
        }

        // GET — show blank form
        render('categories/form', [
            'category'   => null,
            'formAction' => url(['page' => 'categories', 'action' => 'create']),
            'pageTitle'  => 'เพิ่มหมวดหมู่ใหม่',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // edit() — Show populated form (GET) or save changes (POST)
    // ──────────────────────────────────────────────────────────
    public function edit(): void
    {
        $id = (int) get('id', 0);

        $category = $this->findOrAbort($id);

        if (isPost()) {
            verifyCsrf();

            $name = trim(post('name', ''));

            // ── Validation ──────────────────────────────────
            $errors = $this->validateName($name);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('categories/form', [
                    'category'   => $category,
                    'formAction' => url(['page' => 'categories', 'action' => 'edit', 'id' => $id]),
                    'pageTitle'  => 'แก้ไขหมวดหมู่',
                ]);
                return;
            }

            // ── Check duplicate (exclude self) ───────────────
            if ($this->nameExists($name, $id)) {
                flashInput();
                setFlash('danger', "หมวดหมู่ <strong>" . e($name) . "</strong> มีในระบบแล้ว");
                render('categories/form', [
                    'category'   => $category,
                    'formAction' => url(['page' => 'categories', 'action' => 'edit', 'id' => $id]),
                    'pageTitle'  => 'แก้ไขหมวดหมู่',
                ]);
                return;
            }

            // ── Update ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                UPDATE categories SET name = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$name, $id]);

            oldClear();
            setFlash('success', "แก้ไขหมวดหมู่ <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'categories']));
        }

        // GET — show pre-populated form
        render('categories/form', [
            'category'   => $category,
            'formAction' => url(['page' => 'categories', 'action' => 'edit', 'id' => $id]),
            'pageTitle'  => 'แก้ไขหมวดหมู่',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // delete() — Remove a category (POST only)
    // ──────────────────────────────────────────────────────────
    public function delete(): void
    {
        if (!isPost()) {
            redirect(url(['page' => 'categories']));
        }

        verifyCsrf();

        $id = (int) post('id', 0);

        $category = $this->findOrAbort($id);

        // ── Guard: cannot delete if products exist ──────────
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
        $stmt->execute([$id]);
        $productCount = (int) $stmt->fetchColumn();

        if ($productCount > 0) {
            setFlash(
                'warning',
                "ไม่สามารถลบหมวดหมู่ <strong>" . e($category['name']) . "</strong> ได้ "
                . "เนื่องจากมีสินค้า <strong>{$productCount}</strong> รายการที่อ้างอิงอยู่"
            );
            redirect(url(['page' => 'categories']));
        }

        // ── Delete ──────────────────────────────────────────
        $stmt = $this->pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', "ลบหมวดหมู่ <strong>" . e($category['name']) . "</strong> เรียบร้อยแล้ว");
        redirect(url(['page' => 'categories']));
    }

    // ============================================================
    // Private Helpers
    // ============================================================

    /**
     * Find a category by ID or abort with a 404-like error.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            setFlash('danger', 'ไม่พบหมวดหมู่');
            redirect(url(['page' => 'categories']));
        }

        $stmt = $this->pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $category = $stmt->fetch();

        if (!$category) {
            setFlash('danger', "ไม่พบหมวดหมู่ ID: {$id}");
            redirect(url(['page' => 'categories']));
        }

        return $category;
    }

    /**
     * Validate the category name field.
     *
     * @param string $name
     * @return string[]  Array of error messages (empty = valid)
     */
    private function validateName(string $name): array
    {
        $errors = [];

        if ($name === '') {
            $errors[] = 'กรุณากรอกชื่อหมวดหมู่';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'ชื่อหมวดหมู่ต้องไม่เกิน 255 ตัวอักษร';
        }

        return $errors;
    }

    /**
     * Check whether a category name already exists in the database.
     *
     * @param string   $name
     * @param int|null $excludeId  Exclude this ID from the check (used when editing)
     * @return bool
     */
    private function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?
            ");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM categories WHERE name = ?
            ");
            $stmt->execute([$name]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }
}
