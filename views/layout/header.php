<?php
declare(strict_types=1);

// Page title — views can set $pageTitle before render() is called
$pageTitle = $pageTitle ?? 'Sales Tracker';
$currentPage = get('page', 'sales');
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= e($pageTitle) ?> — Sales Tracker</title>

    <!-- Bootstrap 5.3 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Select2 - Searchable dropdowns -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css">

    <!-- Custom styles -->
    <link rel="stylesheet" href="assets/css/style.css">

    <style>
        /* ── Inline critical styles (no extra request) ─────────── */
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, 'Noto Sans Thai', sans-serif;
            background-color: #f0f2f5;
            font-size: .925rem;
        }

        /* Navbar */
        .navbar-brand {
            font-weight: 700;
            letter-spacing: .5px;
        }
        .nav-link {
            font-weight: 500;
        }
        .navbar .nav-link.active {
            background-color: rgba(255,255,255,.15);
            border-radius: .375rem;
        }

        /* Page wrapper */
        .page-wrapper {
            padding-top: 1.5rem;
            padding-bottom: 3rem;
        }

        /* Cards */
        .card {
            border: none;
            box-shadow: 0 1px 4px rgba(0,0,0,.08);
        }
        .card-header {
            border-bottom: 1px solid rgba(0,0,0,.06);
        }

        /* Tables */
        .table > :not(caption) > * > * {
            padding: .55rem .75rem;
        }
        .table thead th {
            font-weight: 600;
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .04em;
            white-space: nowrap;
        }
        .table-hover tbody tr:hover {
            background-color: rgba(13, 110, 253, .04);
        }

        /* Badge work types */
        .badge-wt1 { background-color: #0d6efd !important; }
        .badge-wt2 { background-color: #198754 !important; }

        /* Amount / number cells — right-align */
        .text-amount {
            font-variant-numeric: tabular-nums;
            font-feature-settings: "tnum";
        }

        /* Summary cards */
        .summary-card {
            border-left: 4px solid;
            border-radius: .5rem;
        }
        .summary-card.border-primary { border-left-color: #0d6efd !important; }
        .summary-card.border-success { border-left-color: #198754 !important; }
        .summary-card.border-info    { border-left-color: #0dcaf0 !important; }
        .summary-card.border-warning { border-left-color: #ffc107 !important; }

        /* Form helpers */
        .required-star::after {
            content: ' *';
            color: #dc3545;
        }

        /* Flash dismiss animation */
        .alert { animation: fadeInDown .25s ease; }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-8px); }
            to   { opacity: 1; transform: translateY(0); }
        }

        /* Responsive table wrapper */
        .table-responsive { border-radius: .5rem; }

        /* Print helpers */
        @media print {
            .navbar, .no-print, .btn, .alert { display: none !important; }
            body { background: #fff; }
            .card { box-shadow: none; border: 1px solid #dee2e6; }
        }
    </style>
</head>
<body>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Top Navigation Bar                                      ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container-xl">

        <!-- Brand -->
        <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url(['page' => 'sales']) ?>">
            <i class="bi bi-bar-chart-line-fill text-primary"></i>
            <span>Sales Tracker</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler border-0" type="button"
                data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false"
                aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Nav links -->
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">

                <!-- Sales -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1<?= navActive('sales') ?>"
                       href="<?= url(['page' => 'sales']) ?>">
                        <i class="bi bi-receipt"></i>
                        <span>รายการขาย</span>
                    </a>
                </li>

                <!-- Reports -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1<?= navActive('reports') ?>"
                       href="<?= url(['page' => 'reports']) ?>">
                        <i class="bi bi-graph-up-arrow"></i>
                        <span>รายงาน</span>
                    </a>
                </li>

                <!-- Products -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1<?= navActive('products') ?>"
                       href="<?= url(['page' => 'products']) ?>">
                        <i class="bi bi-box-seam"></i>
                        <span>สินค้า</span>
                    </a>
                </li>

                <!-- Categories -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1<?= navActive('categories') ?>"
                       href="<?= url(['page' => 'categories']) ?>">
                        <i class="bi bi-tag"></i>
                        <span>หมวดหมู่</span>
                    </a>
                </li>

                <!-- Customers -->
                <li class="nav-item">
                    <a class="nav-link d-flex align-items-center gap-1<?= navActive('customers') ?>"
                       href="<?= url(['page' => 'customers']) ?>">
                        <i class="bi bi-people"></i>
                        <span>ลูกค้า</span>
                    </a>
                </li>

            </ul>

            <!-- Right side: Quick actions -->
            <div class="d-flex align-items-center gap-2">
                <a href="<?= url(['page' => 'sales', 'action' => 'create']) ?>"
                   class="btn btn-sm btn-primary d-flex align-items-center gap-1">
                    <i class="bi bi-plus-circle-fill"></i>
                    <span class="d-none d-sm-inline">บันทึกการขาย</span>
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ╔══════════════════════════════════════════════════════════╗ -->
<!-- ║  Main Content Wrapper                                    ║ -->
<!-- ╚══════════════════════════════════════════════════════════╝ -->
<main class="container-xl page-wrapper">

    <!-- Flash Messages -->
    <?php renderFlash(); ?>
