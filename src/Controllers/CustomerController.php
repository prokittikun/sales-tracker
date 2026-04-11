<?php

declare(strict_types=1);

namespace App\Controllers;

use PDO;

// ============================================================
// CustomerController
// ============================================================
// Handles CRUD operations for customer / buyer records.
//
// Routes:
//   GET  ?page=customers                      → index()
//   GET  ?page=customers&action=create        → create()
//   POST ?page=customers&action=create        → create()  (save)
//   GET  ?page=customers&action=edit&id=X     → edit()
//   POST ?page=customers&action=edit&id=X     → edit()    (save)
//   POST ?page=customers&action=delete        → delete()
// ============================================================

class CustomerController
{
    public function __construct(private PDO $pdo) {}

    // ──────────────────────────────────────────────────────────
    // index() — List all customers with their sales summary
    // ──────────────────────────────────────────────────────────
    public function index(): void
    {
        $stmt = $this->pdo->query("
            SELECT
                c.id,
                c.name,
                c.phone,
                c.note,
                c.created_at,
                c.updated_at,

                -- Total sales (all work types)
                COALESCE(COUNT(s.id),     0) AS total_orders,
                COALESCE(SUM(s.quantity), 0) AS total_qty,
                COALESCE(SUM(s.price),    0) AS total_amount,

                -- Work type 1: ขายเฉพาะเครื่อง
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.quantity ELSE 0 END), 0) AS wt1_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 1 THEN s.price    ELSE 0 END), 0) AS wt1_amount,

                -- Work type 2: ขายพร้อมติดตั้ง
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.quantity ELSE 0 END), 0) AS wt2_qty,
                COALESCE(SUM(CASE WHEN s.work_type_id = 2 THEN s.price    ELSE 0 END), 0) AS wt2_amount

            FROM customers c
            LEFT JOIN sales s ON s.customer_id = c.id
            GROUP BY c.id, c.name, c.phone, c.note, c.created_at, c.updated_at
            ORDER BY c.name ASC
        ");

        $customers = $stmt->fetchAll();

        render('customers/index', compact('customers'));
    }

    // ──────────────────────────────────────────────────────────
    // create() — Show form (GET) or save new customer (POST)
    // ──────────────────────────────────────────────────────────
    public function create(): void
    {
        if (isPost()) {
            verifyCsrf();

            $name  = trim(post('name',  ''));
            $phone = trim(post('phone', ''));
            $note  = trim(post('note',  ''));

            // ── Validation ──────────────────────────────────
            $errors = $this->validateCustomer($name, $phone);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                redirect(url(['page' => 'customers', 'action' => 'create']));
            }

            // ── Insert ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                INSERT INTO customers (name, phone, note)
                VALUES (:name, :phone, :note)
            ");
            $stmt->execute([
                ':name'  => $name,
                ':phone' => $phone !== '' ? $phone : null,
                ':note'  => $note  !== '' ? $note  : null,
            ]);

            oldClear();
            setFlash('success', "เพิ่มลูกค้า <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'customers']));
        }

        // GET — show blank form
        render('customers/form', [
            'customer'   => null,
            'formAction' => url(['page' => 'customers', 'action' => 'create']),
            'pageTitle'  => 'เพิ่มลูกค้าใหม่',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // edit() — Show populated form (GET) or save changes (POST)
    // ──────────────────────────────────────────────────────────
    public function edit(): void
    {
        $id       = (int) get('id', 0);
        $customer = $this->findOrAbort($id);

        if (isPost()) {
            verifyCsrf();

            $name  = trim(post('name',  ''));
            $phone = trim(post('phone', ''));
            $note  = trim(post('note',  ''));

            // ── Validation ──────────────────────────────────
            $errors = $this->validateCustomer($name, $phone);

            if (!empty($errors)) {
                flashInput();
                foreach ($errors as $err) {
                    setFlash('danger', $err);
                }
                redirect(url(['page' => 'customers', 'action' => 'edit', 'id' => $id]));
            }

            // ── Update ──────────────────────────────────────
            $stmt = $this->pdo->prepare("
                UPDATE customers
                SET
                    name       = :name,
                    phone      = :phone,
                    note       = :note,
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':name'  => $name,
                ':phone' => $phone !== '' ? $phone : null,
                ':note'  => $note  !== '' ? $note  : null,
                ':id'    => $id,
            ]);

            oldClear();
            setFlash('success', "แก้ไขข้อมูลลูกค้า <strong>" . e($name) . "</strong> เรียบร้อยแล้ว");
            redirect(url(['page' => 'customers']));
        }

        // GET — show pre-populated form
        render('customers/form', [
            'customer'   => $customer,
            'formAction' => url(['page' => 'customers', 'action' => 'edit', 'id' => $id]),
            'pageTitle'  => 'แก้ไขข้อมูลลูกค้า',
        ]);
    }

    // ──────────────────────────────────────────────────────────
    // delete() — Remove a customer record (POST only)
    // ──────────────────────────────────────────────────────────
    public function delete(): void
    {
        if (!isPost()) {
            redirect(url(['page' => 'customers']));
        }

        verifyCsrf();

        $id       = (int) post('id', 0);
        $customer = $this->findOrAbort($id);

        // ── Guard: cannot delete if sales records exist ──────
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM sales WHERE customer_id = ?");
        $stmt->execute([$id]);
        $salesCount = (int) $stmt->fetchColumn();

        if ($salesCount > 0) {
            setFlash(
                'warning',
                "ไม่สามารถลบลูกค้า <strong>" . e($customer['name']) . "</strong> ได้ "
                . "เนื่องจากมีรายการขาย <strong>{$salesCount}</strong> รายการที่อ้างอิงอยู่"
            );
            redirect(url(['page' => 'customers']));
        }

        // ── Delete ──────────────────────────────────────────
        $stmt = $this->pdo->prepare("DELETE FROM customers WHERE id = ?");
        $stmt->execute([$id]);

        setFlash('success', "ลบลูกค้า <strong>" . e($customer['name']) . "</strong> เรียบร้อยแล้ว");
        redirect(url(['page' => 'customers']));
    }

    // ============================================================
    // Private Helpers
    // ============================================================

    /**
     * Find a customer by ID or redirect with an error flash.
     *
     * @param  int $id
     * @return array<string, mixed>
     */
    private function findOrAbort(int $id): array
    {
        if ($id <= 0) {
            setFlash('danger', 'ไม่พบข้อมูลลูกค้า');
            redirect(url(['page' => 'customers']));
        }

        $stmt = $this->pdo->prepare("SELECT * FROM customers WHERE id = ? LIMIT 1");
        $stmt->execute([$id]);
        $customer = $stmt->fetch();

        if (!$customer) {
            setFlash('danger', "ไม่พบลูกค้า ID: {$id}");
            redirect(url(['page' => 'customers']));
        }

        return $customer;
    }

    /**
     * Validate customer name and phone fields.
     *
     * @param  string $name
     * @param  string $phone
     * @return string[]  Array of error messages (empty = valid)
     */
    private function validateCustomer(string $name, string $phone): array
    {
        $errors = [];

        // name — required
        if ($name === '') {
            $errors[] = 'กรุณากรอกชื่อลูกค้า';
        } elseif (mb_strlen($name) > 255) {
            $errors[] = 'ชื่อลูกค้าต้องไม่เกิน 255 ตัวอักษร';
        }

        // phone — optional, but validate format if provided
        if ($phone !== '') {
            // Accept digits, spaces, dashes, parentheses, and leading +
            if (!preg_match('/^[0-9\s\-\+\(\)]{7,20}$/', $phone)) {
                $errors[] = 'รูปแบบเบอร์โทรศัพท์ไม่ถูกต้อง (ตัวอย่าง: 081-234-5678)';
            }
        }

        return $errors;
    }
}
