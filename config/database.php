<?php

declare(strict_types=1);

// ============================================================
// Environment Loader
// ============================================================
// Parses the .env file and loads variables into $_ENV / putenv
// Works on both Docker (dev) and XAMPP (production)
// ============================================================

function loadEnv(string $envPath): void
{
    if (!file_exists($envPath)) {
        return;
    }

    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        // Skip comments and empty lines
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        // Must contain '='
        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key   = trim($key);
        $value = trim($value);

        // Strip inline comments (e.g.  DB_HOST=localhost  # comment)
        if (str_contains($value, ' #')) {
            $value = trim(explode(' #', $value, 2)[0]);
        }

        // Strip surrounding quotes (" or ')
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }

        // Only set if not already defined (allows docker-compose env_vars to win)
        if ($key !== '' && getenv($key) === false && !isset($_ENV[$key])) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

// ============================================================
// Load .env  (one level above config/)
// ============================================================
$envFile = dirname(__DIR__) . DIRECTORY_SEPARATOR . '.env';
loadEnv($envFile);

// ============================================================
// Helper – read from env / putenv / _ENV / _SERVER (in that order)
// ============================================================
function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    if ($value !== false) {
        return $value;
    }
    return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
}

// ============================================================
// Database configuration
// ============================================================
//
//  Docker  → values come from docker-compose environment block
//  XAMPP   → values come from .env file next to index.php,
//             or fall back to the XAMPP defaults below
//
$dbHost = (string) env('DB_HOST', 'localhost');
$dbPort = (string) env('DB_PORT', '3306');
$dbName = (string) env('DB_NAME', 'sales_tracker');
$dbUser = (string) env('DB_USER', 'root');
$dbPass = (string) env('DB_PASS', '');

// Timezone
$timezone = (string) env('APP_TIMEZONE', 'Asia/Bangkok');
date_default_timezone_set($timezone);

// ============================================================
// Create PDO connection
// ============================================================
try {
    $dsn = "mysql:host={$dbHost};port={$dbPort};dbname={$dbName};charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
    ];

    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);
} catch (PDOException $e) {
    // Friendly error page instead of raw exception
    $errorMessage = $e->getMessage();

    // Strip credentials from error message for safety
    $errorMessage = preg_replace('/Access denied for user \'[^\']*\'/', "Access denied", $errorMessage) ?? $errorMessage;

    http_response_code(503);
    ?>
    <!DOCTYPE html>
    <html lang="th">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>ไม่สามารถเชื่อมต่อฐานข้อมูลได้</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    </head>
    <body class="bg-light d-flex align-items-center justify-content-center" style="min-height:100vh">
        <div class="card shadow border-danger" style="max-width:600px;width:100%">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">⚠️ ไม่สามารถเชื่อมต่อฐานข้อมูลได้</h5>
            </div>
            <div class="card-body">
                <p class="mb-2">ระบบไม่สามารถเชื่อมต่อกับฐานข้อมูล MySQL ได้ กรุณาตรวจสอบการตั้งค่าต่อไปนี้:</p>
                <ul>
                    <li>ไฟล์ <code>.env</code> ตั้งค่าถูกต้องหรือไม่</li>
                    <li>MySQL / MariaDB กำลังทำงานอยู่หรือไม่</li>
                    <li>ชื่อ Database, User และ Password ถูกต้องหรือไม่</li>
                    <li>สำหรับ Docker: รัน <code>docker-compose up -d</code> แล้วหรือยัง</li>
                    <li>สำหรับ XAMPP: เปิด MySQL service ใน XAMPP Control Panel แล้วหรือยัง</li>
                </ul>
                <hr>
                <p class="mb-1 text-muted small"><strong>การตั้งค่าปัจจุบัน:</strong></p>
                <ul class="small text-muted">
                    <li>Host: <code><?= htmlspecialchars($dbHost) ?>:<?= htmlspecialchars($dbPort) ?></code></li>
                    <li>Database: <code><?= htmlspecialchars($dbName) ?></code></li>
                    <li>User: <code><?= htmlspecialchars($dbUser) ?></code></li>
                </ul>
                <hr>
                <p class="mb-0 text-danger small"><?= htmlspecialchars($errorMessage) ?></p>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit(1);
}
