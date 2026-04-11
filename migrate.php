<?php

declare(strict_types=1);

// ============================================================
// Sales Tracker - Database Migration Runner
// ============================================================
// Usage:
//   CLI  : php migrate.php
//   Docker: docker-compose exec app php migrate.php
//
// This script will:
//   1. Connect to the database (reads .env automatically)
//   2. Create the `migrations` tracking table if needed
//   3. Scan the /migrations folder for *.sql files
//   4. Skip already-executed migrations
//   5. Execute new migrations in filename order
// ============================================================

define('BASE_DIR', __DIR__);
define('MIGRATIONS_DIR', BASE_DIR . '/migrations');
define('CONFIG_FILE', BASE_DIR . '/config/database.php');

// ANSI colour helpers (work in CLI; ignored in browser)
function c(string $text, string $colour): string
{
    if (PHP_SAPI !== 'cli') {
        return htmlspecialchars($text);
    }
    $colours = [
        'green'  => "\033[0;32m",
        'yellow' => "\033[0;33m",
        'red'    => "\033[0;31m",
        'cyan'   => "\033[0;36m",
        'white'  => "\033[1;37m",
        'reset'  => "\033[0m",
    ];
    return ($colours[$colour] ?? '') . $text . $colours['reset'];
}

function line(string $text = ''): void
{
    if (PHP_SAPI === 'cli') {
        echo $text . PHP_EOL;
    } else {
        echo nl2br(htmlspecialchars($text)) . '<br>';
    }
}

function separator(): void
{
    line(c('────────────────────────────────────────────────', 'cyan'));
}

// ============================================================
// HTML wrapper (when run from browser)
// ============================================================
if (PHP_SAPI !== 'cli') {
    header('Content-Type: text/html; charset=utf-8');
    echo '<!DOCTYPE html><html lang="th"><head>'
       . '<meta charset="UTF-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Database Migration</title>'
       . '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">'
       . '</head><body class="bg-light p-4"><div class="card shadow mx-auto" style="max-width:760px">'
       . '<div class="card-header bg-dark text-white"><h5 class="mb-0">🗄️ Database Migration Runner</h5></div>'
       . '<div class="card-body"><pre class="mb-0" style="font-size:.85rem">';
}

// ============================================================
// Banner
// ============================================================
separator();
line(c('  Sales Tracker — Migration Runner', 'white'));
separator();

// ============================================================
// Load database config (creates $pdo)
// ============================================================
if (!file_exists(CONFIG_FILE)) {
    line(c('✗ ไม่พบไฟล์ config/database.php', 'red'));
    exit(1);
}

// Temporarily suppress PDO die() so we can show a nicer message
try {
    require CONFIG_FILE;
} catch (Throwable $e) {
    line(c('✗ โหลด config/database.php ไม่สำเร็จ: ' . $e->getMessage(), 'red'));
    exit(1);
}

if (!isset($pdo) || !($pdo instanceof PDO)) {
    line(c('✗ ไม่สามารถเชื่อมต่อฐานข้อมูลได้ กรุณาตรวจสอบไฟล์ .env', 'red'));
    exit(1);
}

line(c('✓ เชื่อมต่อฐานข้อมูลสำเร็จ', 'green'));

// ============================================================
// Ensure the `migrations` tracking table exists
// ============================================================
$pdo->exec("
    CREATE TABLE IF NOT EXISTS `migrations` (
        `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `migration`   VARCHAR(255) NOT NULL,
        `executed_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_migrations_migration` (`migration`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");

line(c('✓ ตาราง migrations พร้อมใช้งาน', 'green'));

// ============================================================
// Read already-executed migrations from DB
// ============================================================
$executed = $pdo->query("SELECT `migration` FROM `migrations` ORDER BY `migration`")
                ->fetchAll(PDO::FETCH_COLUMN, 0);
$executed = array_flip($executed); // use as a hash-set for O(1) lookup

// ============================================================
// Scan /migrations directory for *.sql files
// ============================================================
if (!is_dir(MIGRATIONS_DIR)) {
    line(c('✗ ไม่พบโฟลเดอร์ migrations/', 'red'));
    exit(1);
}

$files = glob(MIGRATIONS_DIR . '/*.sql');
if ($files === false || count($files) === 0) {
    line(c('⚠ ไม่พบไฟล์ .sql ใน migrations/', 'yellow'));
    exit(0);
}

// Sort by filename to guarantee execution order
sort($files, SORT_STRING);

// ============================================================
// Run pending migrations
// ============================================================
separator();
line(c('  พบไฟล์ migration ทั้งหมด: ' . count($files) . ' ไฟล์', 'white'));
separator();

$ranCount    = 0;
$skippedCount = 0;
$errorCount  = 0;

foreach ($files as $filePath) {
    $filename = basename($filePath);

    // Already executed → skip
    if (isset($executed[$filename])) {
        line(c('  ⟳ SKIP  ', 'yellow') . $filename);
        $skippedCount++;
        continue;
    }

    // Read SQL content
    $sql = file_get_contents($filePath);
    if ($sql === false || trim($sql) === '') {
        line(c('  ⚠ EMPTY ', 'yellow') . $filename . ' — ข้ามเพราะไฟล์ว่างเปล่า');
        $skippedCount++;
        continue;
    }

    // Execute the migration inside a transaction
    line(c('  ▶ RUN   ', 'cyan') . $filename);

    try {
        $pdo->beginTransaction();

        // PDO::exec() does not support multi-statement by default.
        // We split on ";" but keep delimiter-aware to handle stored procs if any.
        $statements = splitSql($sql);

        foreach ($statements as $stmt) {
            $stmt = trim($stmt);
            if ($stmt === '') {
                continue;
            }
            $pdo->exec($stmt);
        }

        // Record as executed
        $insertStmt = $pdo->prepare(
            "INSERT INTO `migrations` (`migration`) VALUES (?)"
        );
        $insertStmt->execute([$filename]);

        $pdo->commit();

        line(c('  ✓ DONE  ', 'green') . $filename);
        $ranCount++;

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        line(c('  ✗ ERROR ', 'red') . $filename);
        line(c('    ' . $e->getMessage(), 'red'));
        $errorCount++;

        // Stop on first error to avoid cascading failures
        break;
    }
}

// ============================================================
// Summary
// ============================================================
separator();
line(c('  สรุป:', 'white'));
line(c("  ✓ รันสำเร็จ  : {$ranCount} migration(s)", 'green'));
line(c("  ⟳ ข้ามแล้ว  : {$skippedCount} migration(s)", 'yellow'));
if ($errorCount > 0) {
    line(c("  ✗ เกิดข้อผิดพลาด: {$errorCount} migration(s)", 'red'));
}
separator();

if ($errorCount === 0) {
    line(c('  ✅ Migration เสร็จสมบูรณ์!', 'green'));
} else {
    line(c('  ❌ Migration มีข้อผิดพลาด กรุณาตรวจสอบ', 'red'));
}
separator();

// ============================================================
// HTML wrapper close
// ============================================================
if (PHP_SAPI !== 'cli') {
    echo '</pre></div>';

    if ($errorCount === 0) {
        echo '<div class="card-footer text-center">'
           . '<a href="index.php" class="btn btn-success">▶ เข้าสู่ระบบ</a>'
           . '</div>';
    }

    echo '</div></body></html>';
}

exit($errorCount > 0 ? 1 : 0);

// ============================================================
// Helper: Split a multi-statement SQL string into statements
// Handles:
//   - Single-line comments (--)
//   - Multi-line comments (/* */)
//   - String literals (single & double quoted)
//   - Semicolons inside strings/comments are NOT treated as delimiters
// ============================================================
function splitSql(string $sql): array
{
    $statements  = [];
    $current     = '';
    $len         = strlen($sql);
    $i           = 0;
    $inSingle    = false; // inside '...'
    $inDouble    = false; // inside "..."
    $inLineComment  = false; // after --
    $inBlockComment = false; // inside /* */

    while ($i < $len) {
        $char = $sql[$i];
        $next = $sql[$i + 1] ?? '';

        // ── Line comment ─────────────────────────────────────
        if (!$inSingle && !$inDouble && !$inBlockComment && !$inLineComment
            && $char === '-' && $next === '-'
        ) {
            $inLineComment = true;
            $current .= $char;
            $i++;
            continue;
        }
        if ($inLineComment && $char === "\n") {
            $inLineComment = false;
            $current .= $char;
            $i++;
            continue;
        }

        // ── Block comment ─────────────────────────────────────
        if (!$inSingle && !$inDouble && !$inLineComment && !$inBlockComment
            && $char === '/' && $next === '*'
        ) {
            $inBlockComment = true;
            $current .= $char;
            $i++;
            continue;
        }
        if ($inBlockComment && $char === '*' && $next === '/') {
            $inBlockComment = false;
            $current .= $char . $next;
            $i += 2;
            continue;
        }

        // ── Single-quoted string ──────────────────────────────
        if (!$inDouble && !$inLineComment && !$inBlockComment && $char === "'") {
            $inSingle = !$inSingle;
            $current .= $char;
            $i++;
            continue;
        }

        // ── Double-quoted string ──────────────────────────────
        if (!$inSingle && !$inLineComment && !$inBlockComment && $char === '"') {
            $inDouble = !$inDouble;
            $current .= $char;
            $i++;
            continue;
        }

        // ── Statement delimiter ───────────────────────────────
        if (!$inSingle && !$inDouble && !$inLineComment && !$inBlockComment
            && $char === ';'
        ) {
            $statements[] = $current;
            $current = '';
            $i++;
            continue;
        }

        $current .= $char;
        $i++;
    }

    // Catch any trailing statement without a terminating semicolon
    if (trim($current) !== '') {
        $statements[] = $current;
    }

    return $statements;
}
