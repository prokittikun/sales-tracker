<?php

declare(strict_types=1);

// ============================================================
// Sales Tracker — Front Controller
// ============================================================
// All HTTP requests pass through this file.
// Routing is handled via the `page` and `action` query params:
//
//   ?page=sales                  → SaleController::index()
//   ?page=sales&action=create    → SaleController::create()
//   ?page=sales&action=edit&id=X → SaleController::edit()
//   ?page=sales&action=delete    → SaleController::delete()  (POST)
//   ?page=products               → ProductController::index()
//   ...
//   ?page=reports                → ReportController::index()
//   ?page=reports&action=pdf     → ReportController::pdf()
// ============================================================

// ── Bootstrap ────────────────────────────────────────────────

define('BASE_PATH', __DIR__);
define('BASE_DIR',  __DIR__);   // alias used by helpers.php and controllers
define('VIEW_PATH', BASE_PATH . '/views');

session_start();

// Composer autoload (PSR-4 + helpers.php)
$autoload = BASE_PATH . '/vendor/autoload.php';
if (!file_exists($autoload)) {
    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ยังไม่ได้ติดตั้ง Composer</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
        <div class="card shadow border-warning" style="max-width:640px;width:100%">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">⚠️ กรุณาติดตั้ง Composer Dependencies ก่อน</h5>
            </div>
            <div class="card-body">
                <p>ยังไม่พบโฟลเดอร์ <code>vendor/</code> กรุณารันคำสั่งต่อไปนี้ก่อน:</p>
                <h6 class="mt-3">Docker (development):</h6>
                <pre class="bg-dark text-white rounded p-3">docker-compose exec app composer install</pre>
                <h6 class="mt-3">XAMPP / Local PHP (production):</h6>
                <pre class="bg-dark text-white rounded p-3">composer install --no-dev --optimize-autoloader</pre>
                <hr>
                <p class="mb-0 text-muted small">
                    หากยังไม่มี Composer สามารถดาวน์โหลดได้ที่
                    <a href="https://getcomposer.org" target="_blank">getcomposer.org</a>
                </p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(1);
}

require_once $autoload;

// Database connection — creates $pdo
require_once BASE_PATH . '/config/database.php';

// ── Controllers ──────────────────────────────────────────────

use App\Controllers\CategoryController;
use App\Controllers\CustomerController;
use App\Controllers\ProductController;
use App\Controllers\ReportController;
use App\Controllers\SaleController;

// ── Router ───────────────────────────────────────────────────

$page   = trim($_GET['page']   ?? 'sales');
$action = trim($_GET['action'] ?? 'index');

// Whitelist allowed pages
$allowedPages = ['sales', 'products', 'customers', 'categories', 'reports'];
if (!in_array($page, $allowedPages, true)) {
    $page   = 'sales';
    $action = 'index';
}

// Whitelist allowed actions per page
$allowedActions = [
    'sales'      => ['index', 'create', 'edit', 'delete', 'get_products'],
    'products'   => ['index', 'create', 'edit', 'delete'],
    'customers'  => ['index', 'create', 'edit', 'delete'],
    'categories' => ['index', 'create', 'edit', 'delete'],
    'reports'    => ['index', 'pdf'],
];

if (!in_array($action, $allowedActions[$page], true)) {
    $action = 'index';
}

// Instantiate controller and dispatch action
try {
    $controller = match ($page) {
        'sales'      => new SaleController($pdo),
        'products'   => new ProductController($pdo),
        'customers'  => new CustomerController($pdo),
        'categories' => new CategoryController($pdo),
        'reports'    => new ReportController($pdo),
    };

    $controller->{$action}();

} catch (Throwable $e) {
    // Render a friendly error page
    $errorTitle   = 'เกิดข้อผิดพลาดในระบบ';
    $errorMessage = $e->getMessage();
    $errorFile    = $e->getFile();
    $errorLine    = $e->getLine();
    $errorTrace   = $e->getTraceAsString();

    http_response_code(500);

    // Include layout header if possible
    $showFullLayout = file_exists(VIEW_PATH . '/layout/header.php');

    if ($showFullLayout) {
        $pageTitle = $errorTitle;
        include VIEW_PATH . '/layout/header.php';
    } else {
        echo '<!DOCTYPE html><html lang="th"><head><meta charset="UTF-8">'
           . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
           . '</head><body><div class="container mt-4">';
    }
    ?>

    <div class="alert alert-danger d-flex align-items-start gap-3" role="alert">
        <span class="fs-3">❌</span>
        <div>
            <h5 class="alert-heading mb-1">เกิดข้อผิดพลาดในระบบ</h5>
            <p class="mb-0"><?= htmlspecialchars($errorMessage) ?></p>
        </div>
    </div>

    <?php if (env('APP_ENV', 'production') !== 'production'): ?>
    <div class="card border-danger mt-3">
        <div class="card-header bg-danger text-white">Debug Information</div>
        <div class="card-body">
            <p><strong>File:</strong> <code><?= htmlspecialchars($errorFile) ?></code></p>
            <p><strong>Line:</strong> <code><?= htmlspecialchars((string) $errorLine) ?></code></p>
            <p class="mb-1"><strong>Stack Trace:</strong></p>
            <pre class="bg-light rounded p-3" style="font-size:.8rem;overflow-x:auto"><?= htmlspecialchars($errorTrace) ?></pre>
        </div>
    </div>
    <?php endif; ?>

    <div class="mt-3">
        <a href="index.php" class="btn btn-outline-secondary">
            ← กลับหน้าหลัก
        </a>
    </div>

    <?php
    if ($showFullLayout) {
        include VIEW_PATH . '/layout/footer.php';
    } else {
        echo '</div></body></html>';
    }
}
