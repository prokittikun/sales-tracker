<?php

declare(strict_types=1);

// ============================================================
// Sales Tracker - Global Helper Functions
// ============================================================

// ============================================================
// Session bootstrap
// Must be called before any output
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// View Rendering
// ============================================================

/**
 * Render a view file wrapped in the layout (header + footer).
 *
 * @param string $view  Path relative to /views, without .php extension
 *                      e.g. 'sales/index', 'reports/monthly'
 * @param array  $data  Variables to extract into the view's scope
 */
function render(string $view, array $data = []): void
{
    $viewFile = BASE_DIR . '/views/' . $view . '.php';

    if (!file_exists($viewFile)) {
        http_response_code(500);
        echo '<div class="alert alert-danger m-4">View not found: <code>' . htmlspecialchars($view) . '</code></div>';
        return;
    }

    // Make data variables available in the view scope
    extract($data, EXTR_SKIP);

    // Capture the view content into a buffer so the layout can embed it
    ob_start();
    include $viewFile;
    $content = ob_get_clean();

    // Render layout
    include BASE_DIR . '/views/layout/header.php';
    echo $content;
    include BASE_DIR . '/views/layout/footer.php';
}

/**
 * Render a view WITHOUT the layout (useful for PDF partials, AJAX responses, etc.)
 *
 * @param string $view
 * @param array  $data
 */
function renderPartial(string $view, array $data = []): void
{
    $viewFile = BASE_DIR . '/views/' . $view . '.php';

    if (!file_exists($viewFile)) {
        echo '<div class="alert alert-danger">Partial not found: <code>' . htmlspecialchars($view) . '</code></div>';
        return;
    }

    extract($data, EXTR_SKIP);
    include $viewFile;
}

// ============================================================
// HTTP Helpers
// ============================================================

/**
 * Redirect to a URL and stop execution.
 *
 * @param string $url  Absolute or relative URL
 */
function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/**
 * Build a URL to index.php with the given query parameters.
 *
 * @param array $params  e.g. ['page' => 'sales', 'action' => 'create']
 * @return string
 */
function url(array $params = []): string
{
    $base = 'index.php';
    if (empty($params)) {
        return $base;
    }
    return $base . '?' . http_build_query($params);
}

/**
 * Return the value of a GET parameter (trimmed string).
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function get(string $key, mixed $default = null): mixed
{
    $value = $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Return the value of a POST parameter (trimmed string).
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function post(string $key, mixed $default = null): mixed
{
    $value = $_POST[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Check if the current request is POST.
 */
function isPost(): bool
{
    return $_SERVER['REQUEST_METHOD'] === 'POST';
}

// ============================================================
// Flash Messages (single-use session messages)
// ============================================================

/**
 * Store a flash message in the session.
 *
 * @param string $type    Bootstrap contextual type: 'success' | 'danger' | 'warning' | 'info'
 * @param string $message The message text (HTML is allowed)
 */
function setFlash(string $type, string $message): void
{
    $_SESSION['flash_messages'][] = [
        'type'    => $type,
        'message' => $message,
    ];
}

/**
 * Retrieve and clear all flash messages from the session.
 *
 * @return array<int, array{type: string, message: string}>
 */
function getFlash(): array
{
    $messages = $_SESSION['flash_messages'] ?? [];
    unset($_SESSION['flash_messages']);
    return $messages;
}

/**
 * Render Bootstrap flash alert HTML for all pending flash messages.
 * Outputs nothing if there are no messages.
 */
function renderFlash(): void
{
    $messages = getFlash();
    if (empty($messages)) {
        return;
    }

    $icons = [
        'success' => '✅',
        'danger'  => '❌',
        'warning' => '⚠️',
        'info'    => 'ℹ️',
    ];

    foreach ($messages as $msg) {
        $type    = htmlspecialchars($msg['type']);
        $icon    = $icons[$msg['type']] ?? '';
        $message = $msg['message']; // allow HTML in messages
        echo <<<HTML
        <div class="alert alert-{$type} alert-dismissible fade show" role="alert">
            {$icon} {$message}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        HTML;
    }
}

// ============================================================
// Old Input (re-populate forms after validation failure)
// ============================================================

/**
 * Save POST data to session so forms can be repopulated.
 * Call this before redirect() when validation fails.
 *
 * @param array $data  Defaults to $_POST
 */
function flashInput(array $data = []): void
{
    $_SESSION['old_input'] = $data ?: $_POST;
}

/**
 * Get a previously saved input value, then clear the store.
 *
 * @param string $key
 * @param mixed  $default
 * @return mixed
 */
function old(string $key, mixed $default = ''): mixed
{
    if (!isset($_SESSION['old_input'])) {
        return $default;
    }
    $value = $_SESSION['old_input'][$key] ?? $default;

    // Clear entire old input on first access attempt
    // (call oldClear() explicitly if you want to keep it for multiple fields)
    return $value;
}

/**
 * Clear saved old input from the session.
 */
function oldClear(): void
{
    unset($_SESSION['old_input']);
}

// ============================================================
// Formatting Utilities
// ============================================================

/**
 * Format a number as Thai Baht currency.
 * e.g.  1500.00  →  "1,500.00"
 *
 * @param float|string|null $amount
 * @param int               $decimals  Number of decimal places (default 2)
 * @return string
 */
function formatMoney(float|string|null $amount, int $decimals = 2): string
{
    if ($amount === null || $amount === '') {
        return '0.00';
    }
    return number_format((float) $amount, $decimals, '.', ',');
}

/**
 * Format a MySQL date string (YYYY-MM-DD) to Thai short date.
 * e.g.  "2025-04-06"  →  "06/04/2568"  (Buddhist Era)
 *       Use $buddhist = false for CE: "06/04/2025"
 *
 * @param string|null $date
 * @param bool        $buddhist  Convert year to Buddhist Era
 * @return string
 */
function formatDate(?string $date, bool $buddhist = true): string
{
    if (empty($date)) {
        return '-';
    }

    try {
        $dt   = new DateTimeImmutable($date);
        $year = (int) $dt->format('Y');
        if ($buddhist) {
            $year += 543;
        }
        return $dt->format('d/m/') . $year;
    } catch (Exception) {
        return htmlspecialchars($date);
    }
}

/**
 * Format a MySQL DATETIME string to Thai short date-time.
 * e.g.  "2025-04-06 14:30:00"  →  "06/04/2568 14:30"
 *
 * @param string|null $datetime
 * @param bool        $buddhist
 * @return string
 */
function formatDateTime(?string $datetime, bool $buddhist = true): string
{
    if (empty($datetime)) {
        return '-';
    }

    try {
        $dt   = new DateTimeImmutable($datetime);
        $year = (int) $dt->format('Y');
        if ($buddhist) {
            $year += 543;
        }
        return $dt->format('d/m/') . $year . ' ' . $dt->format('H:i');
    } catch (Exception) {
        return htmlspecialchars($datetime);
    }
}

/**
 * Return the Thai full month name for a given month number (1–12).
 *
 * @param int $month  1–12
 * @return string
 */
function thaiMonthName(int $month): string
{
    $names = [
        1  => 'มกราคม',
        2  => 'กุมภาพันธ์',
        3  => 'มีนาคม',
        4  => 'เมษายน',
        5  => 'พฤษภาคม',
        6  => 'มิถุนายน',
        7  => 'กรกฎาคม',
        8  => 'สิงหาคม',
        9  => 'กันยายน',
        10 => 'ตุลาคม',
        11 => 'พฤศจิกายน',
        12 => 'ธันวาคม',
    ];
    return $names[$month] ?? (string) $month;
}

/**
 * Return the Thai short month name for a given month number (1–12).
 *
 * @param int $month
 * @return string
 */
function thaiMonthShort(int $month): string
{
    $names = [
        1  => 'ม.ค.',
        2  => 'ก.พ.',
        3  => 'มี.ค.',
        4  => 'เม.ย.',
        5  => 'พ.ค.',
        6  => 'มิ.ย.',
        7  => 'ก.ค.',
        8  => 'ส.ค.',
        9  => 'ก.ย.',
        10 => 'ต.ค.',
        11 => 'พ.ย.',
        12 => 'ธ.ค.',
    ];
    return $names[$month] ?? (string) $month;
}

/**
 * Convert a Gregorian year (CE) to Buddhist Era year.
 *
 * @param int $year  e.g. 2025
 * @return int       e.g. 2568
 */
function toBuddhistYear(int $year): int
{
    return $year + 543;
}

// ============================================================
// Security / Output Helpers
// ============================================================

/**
 * Escape a string for safe HTML output.
 * Shorthand for htmlspecialchars().
 *
 * @param mixed $value
 * @return string
 */
function e(mixed $value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/**
 * Generate a CSRF token and store it in the session.
 * Returns the token string.
 *
 * @return string
 */
function csrfToken(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Render a hidden CSRF input field.
 */
function csrfField(): void
{
    echo '<input type="hidden" name="_csrf_token" value="' . e(csrfToken()) . '">';
}

/**
 * Verify the CSRF token submitted in a POST request.
 * Calls abort(403) if the token is invalid.
 */
function verifyCsrf(): void
{
    $submitted = $_POST['_csrf_token'] ?? '';
    $stored    = $_SESSION['csrf_token'] ?? '';

    if (!hash_equals($stored, $submitted)) {
        http_response_code(403);
        echo '<div class="alert alert-danger m-4">⛔ CSRF token ไม่ถูกต้อง กรุณารีเฟรชและลองใหม่อีกครั้ง</div>';
        exit;
    }
}

// ============================================================
// Pagination Helper
// ============================================================

/**
 * Calculate pagination offset.
 *
 * @param int $perPage
 * @return array{page: int, perPage: int, offset: int}
 */
function paginate(int $perPage = 20): array
{
    $page   = max(1, (int) ($_GET['p'] ?? 1));
    $offset = ($page - 1) * $perPage;
    return ['page' => $page, 'perPage' => $perPage, 'offset' => $offset];
}

/**
 * Render Bootstrap 5 pagination links.
 *
 * @param int    $totalRows  Total number of records
 * @param int    $perPage    Records per page
 * @param int    $currentPage
 * @param array  $extraParams  Additional query params to preserve in links
 */
function renderPagination(int $totalRows, int $perPage, int $currentPage, array $extraParams = []): void
{
    if ($totalRows <= $perPage) {
        return;
    }

    $totalPages = (int) ceil($totalRows / $perPage);

    echo '<nav aria-label="Page navigation"><ul class="pagination pagination-sm justify-content-center flex-wrap">';

    for ($i = 1; $i <= $totalPages; $i++) {
        $params        = array_merge($extraParams, ['p' => $i]);
        $link          = url($params);
        $active        = $i === $currentPage ? ' active' : '';
        $ariaCurrent   = $i === $currentPage ? ' aria-current="page"' : '';
        echo "<li class=\"page-item{$active}\"><a class=\"page-link\" href=\"{$link}\"{$ariaCurrent}>{$i}</a></li>";
    }

    echo '</ul></nav>';
}

// ============================================================
// Miscellaneous
// ============================================================

/**
 * Dump a value and die (development helper).
 *
 * @param mixed ...$vars
 */
function dd(mixed ...$vars): never
{
    echo '<pre style="background:#1e1e1e;color:#d4d4d4;padding:1rem;border-radius:.5rem;overflow:auto;font-size:.8rem">';
    foreach ($vars as $var) {
        var_dump($var);
    }
    echo '</pre>';
    exit;
}

/**
 * Return a "selected" attribute string if the value matches the option.
 *
 * @param mixed $value    Current selected value
 * @param mixed $option   The option being rendered
 * @return string         ' selected' or ''
 */
function selected(mixed $value, mixed $option): string
{
    return (string) $value === (string) $option ? ' selected' : '';
}

/**
 * Return a Bootstrap "active" class if the current page matches.
 *
 * @param string $page  The page to check against $_GET['page']
 * @return string       ' active' or ''
 */
function navActive(string $page): string
{
    return (get('page', 'sales') === $page) ? ' active' : '';
}
