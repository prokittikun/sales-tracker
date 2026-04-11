<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

// ============================================================
// ProductController
// ============================================================
// Handles CRUD operations for the product catalog.
//
// Routes:
//   GET  ?page=products                      → index()
//   GET  ?page=products&action=create        → create()
//   POST ?page=products&action=create        → create()  (save)
//   GET  ?page=products&action=edit&id=X     → edit()
//   POST ?page=products&action=edit&id=X     → edit()    (save)
//   POST ?page=products&action=delete        → delete()
// ============================================================

class ProductController
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────
    // index() — List all products with their sales summary
    // ──────────────────────────────────────────────────────────
    public function index(): void
    {
        $stmt = $this->pdo->query("
            SELECT
                p.id,
                p.name,
                p.category_id,
                c.name AS category_name,
                p.created_at,
                p.updated_at,

                -- Total sales across ALL work types
                COALESCE(SUM(s.quantity), 0)              AS total_qty,
                COALESCE(SUM(s.price),    0)              AS total_amount,

                -- Work type 1: ขายเฉพาะเครื่อง
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.quantity ELSE 0 END), 0) AS wt1_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.price    ELSE 0 END), 0) AS wt1_amount,

                -- Work type 2: ขายพร้อมติดตั้ง
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.quantity ELSE 0 END), 0) AS wt2_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.price    ELSE 0 END), 0) AS wt2_amount

            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN sales s ON s.product_id = p.id
            GROUP BY p.id, p.name, p.category_id, c.name, p.created_at, p.updated_at
            ORDER BY p.name ASC
        ");

        $products = $stmt->fetchAll();

        render('products/index', compact('products'));
    }

    // ──────────────────────────────────────────────────────────
    // create() — Show form (GET) or save new product (POST)
    // ──────────────────────────────────────────────────────────
    public function create(): void
    {
        $categories = $this->pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

        if (isPost()) {
            verifyCsrf();

            $name       = trim(post('name', ''));
            $categoryId = (int) post('category_id', 0);

            // ── Validation ──────────────────────────────────
            $errors = $this->validateName($name);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('products/form', [
                    'product'     => null,
                    'categories'  => $categories,
                    'formAction'  => url(['page' => 'products', 'action' => 'create']),
                    'pageTitle'   => 'เพิ่มสินค้าใหม่',
                ]);
                return;
            }

            // ── Check duplicate ─────────────────────────────
            if ($this->nameExists($name)) {
                flashInput();
                setFlash('danger', "ชื่อสินค้า <strong>" . e($name) . "</strong> มีในระบบแล้ว");
                render('products/form', [
                    'product'     => null,
                    'categories'  => $categories,
                    'formAction'  => url(['page' => 'products', 'action' => 'create']),
                    'pageTitle'   => 'เพิ่มสินค้าใหม่',
                ]);
                return;
            }

            // ── Insert ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                INSERT INTO products (name, category_id) VALUES (?, ?)
            ");
            $stmt->execute([$name, $categoryId > 0 ? $categoryId : null]);

            oldClear();
            setFlash('success', "เพิ่มสินค้า <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'products']));
        }

        // GET — show blank form
        render('products/form', [
            'product'     => null,
            'categories'  => $categories,
            'formAction'  => url(['page' => 'products', 'action' => 'create']),
            'pageTitle'   => 'เพิ่มสินค้าใหม่',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // edit() — Show populated form (GET) or save changes (POST)
    // ──────────────────────────────────────────────────────────
    public function edit(): void
    {
        $id = (int) get('id', 0);
        $product = $this->findOrAbort($id);

        $categories = $this->pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll();

        if (isPost()) {
            verifyCsrf();

            $name       = trim(post('name', ''));
            $categoryId = (int) post('category_id', 0);

            // ── Validation ──────────────────────────────────
            $errors = $this->validateName($name);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                render('products/form', [
                    'product'     => $product,
                    'categories'  => $categories,
                    'formAction'  => url(['page' => 'products', 'action' => 'edit', 'id' => $id]),
                    'pageTitle'   => 'แก้ไขสินค้า',
                ]);
                return;
            }

            // ── Check duplicate (exclude self) ───────────────
            if ($this->nameExists($name, $id)) {
                flashInput();
                setFlash('danger', "ชื่อสินค้า <strong>" . e($name) . "</strong> มีในระบบแล้ว");
                render('products/form', [
                    'product'     => $product,
                    'categories'  => $categories,
                    'formAction'  => url(['page' => 'products', 'action' => 'edit', 'id' => $id]),
                    'pageTitle'   => 'แก้ไขสินค้า',
                ]);
                return;
            }

            // ── Update ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                UPDATE products SET name = ?, category_id = ?, updated_at = NOW() WHERE id = ?
            ");
            $stmt->execute([$name, $categoryId > 0 ? $categoryId : null, $id]);

            oldClear();
            setFlash('success', "แก้ไขสินค้า <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'products']));
        }

        // GET — show pre-populated form
        render('products/form', [
            'product'     => $product,
            'categories'  => $categories,
            'formAction'  => url(['page' => 'products', 'action' => 'edit', 'id' => $id]),
            'pageTitle'   => 'แก้ไขสินค้า',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // delete() — Remove a product (POST only)
    // ──────────────────────────────────────────────────────────
    public function delete(): void
    {
        if (!isPost()) {
            redirect(url(['page' => 'products']));
        }

        verifyCsrf();

        $id = (int) post('id', 0);

        $product = $this->findOrAbort($id);

        // ── Guard: cannot delete if sales records exist ──────
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sales WHERE product_id = ?");
        $stmt->execute([$id]);
        $salesCount = (int) $stmt->fetchColumn();

        if ($salesCount > 0) {
            setFlash(
                'warning',
                "ไม่สามารถลบสินค้า <strong>" . e($product['name']) . "</strong> ได้ "
                . "เนื่องจากมีรายการขาย <strong>{$salesCount}</strong> รายการที่อ้างอิงอยู่"
            );
            redirect(url(['page' => 'products']));
        }

        // ── Delete ──────────────────────────────────────────
        $stmt = $this->pdo->prepare("DELETE FROM products WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', "ลบสินค้า <strong>" . e($product['name']) . "</strong> เรียบร้อยแล้ว");
        redirect(url(['page' => 'products']));
    }

    // ============================================================
    // Private Helpers
    // ============================================================

    /**
     * Find a product by ID or abort with a 404-like error.
     *
     * @param int $id
     * @return array<string, mixed>
     */
    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            setFlash('danger', 'ไม่พบสินค้า');
            redirect(url(['page' => 'products']));
        }

        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->execute([$id]);
        $product = $stmt->fetch();

        if (!$product) {
            setFlash('danger', "ไม่พบสินค้า ID: {$id}");
            redirect(url(['page' => 'products']));
        }

        return $product;
    }

    /**
     * Validate the product name field.
     *
     * @param string $name
     * @return string[]  Array of error messages (empty = valid)
     */
    private function validateName(string $name): array
    {
        $errors = [];

        if ($name === '') {
            $errors[] = 'กรุณากรอกชื่อสินค้า';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'ชื่อสินค้าต้องไม่เกิน 255 ตัวอักษร';
        }

        return $errors;
    }

    /**
     * Check whether a product name already exists in the database.
     *
     * @param string   $name
     * @param int|null $excludeId  Exclude this ID from the check (used when editing)
     * @return bool
     */
    private function nameExists(string $name, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM products WHERE name = ? AND id != ?
            ");
            $stmt->execute([$name, $excludeId]);
        } else {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM products WHERE name = ?
            ");
            $stmt->execute([$name]);
        }

        return (int) $stmt->fetchColumn() > 0;
    }
}
