<?php

/**
 * Admin page header template.
 * Include at the top of every admin page.
 * Set $pageTitle and $activePage before including.
 */
require_once __DIR__ . '/../../auth/guard_admin.php';

if (!isset($pageTitle)) $pageTitle = 'Lost & Found Admin';
if (!isset($activePage)) $activePage = '';

require_once __DIR__ . '/../../backend/config.php';

$navSearchItems = [
    ['label' => 'Dashboard', 'url' => 'index.php', 'icon' => 'bi bi-speedometer2'],
    ['label' => 'Submitted Lost Item', 'url' => 'lost-submit.php', 'icon' => 'bi bi-exclamation-triangle-fill'],
    ['label' => 'Submitted Found Item', 'url' => 'found-submit.php', 'icon' => 'bi bi-check-circle'],
    ['label' => 'View Categories', 'url' => 'view.php', 'icon' => 'bi bi-list-ul'],
    ['label' => 'Add Categories', 'url' => 'add-category.php', 'icon' => 'bi bi-plus-circle'],
    ['label' => 'Update Categories', 'url' => 'view.php', 'icon' => 'bi bi-pencil-square'],
    ['label' => 'All Items', 'url' => 'all-items.php', 'icon' => 'bi bi-collection'],
    ['label' => 'Detected Matches', 'url' => 'matches.php', 'icon' => 'bi bi-link-45deg'],
    ['label' => 'Matched Items', 'url' => 'matched-items.php', 'icon' => 'bi bi-card-checklist'],
    ['label' => 'Claimed Items', 'url' => 'claimed-items.php', 'icon' => 'bi bi-check2-circle'],
    ['label' => 'Claim Requests', 'url' => 'claim-requests.php', 'icon' => 'bi bi-shield-check'],
    ['label' => 'Found Item Reports', 'url' => 'found-item-reports.php', 'icon' => 'bi bi-search'],
    ['label' => 'Claims Reports', 'url' => 'claims-reports.php', 'icon' => 'bi bi-clipboard-data'],
    ['label' => 'Unsolved Reports', 'url' => 'unsolved-reports.php', 'icon' => 'bi bi-hourglass-split'],
];
?>
<!doctype html>
<html
    lang="en"
    class="layout-menu-fixed layout-compact"
    data-assets-path="../assets/"
    data-template="vertical-menu-template-free">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, minimum-scale=1.0, maximum-scale=1.0" />
    <meta name="robots" content="noindex, nofollow" />
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="icon" type="image/x-icon" href="../assets/img/favicon/favicon.ico" />
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&ampdisplay=swap" rel="stylesheet" />
    <link rel="stylesheet" href="../assets/vendor/fonts/iconify-icons.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/node-waves/node-waves.css" />
    <link rel="stylesheet" href="../assets/vendor/css/core.css" />
    <link rel="stylesheet" href="../assets/css/demo.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/perfect-scrollbar/perfect-scrollbar.css" />
    <link rel="stylesheet" href="../assets/vendor/libs/apex-charts/apex-charts.css" />
    <link rel="stylesheet" href="../assets/vendor/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="../assets/css/navbar.css?v=5">
    <script src="../assets/vendor/js/helpers.js"></script>
    <script src="../assets/js/config.js"></script>
    <script>
        try {
            if (localStorage.getItem('sidebarCollapsed') === '1') document.documentElement.classList.add('layout-menu-collapsed')
        } catch (e) {}
    </script>
</head>

<body>
    <div class="layout-wrapper layout-content-navbar">
        <div class="layout-container">

            <aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
                <div class="app-brand demo">
                    <a href="index.php" class="app-brand-link">
                        <span class="app-brand-logo demo me-1">
                            <span class="text-primary">
                                <img src="../assets/img/icon/company-icon.png" alt="Company Icon" style="width: 40px; height: 34px;" />
                            </span>
                        </span>
                        <span class="app-brand-text demo menu-text fw-semibold ms-2">LJH</span>
                    </a>
                    <a href="javascript:void(0);" id="sidebar-toggle" class="layout-menu-toggle ms-auto d-flex align-items-center">
                        <i class="bi bi-list" style="font-size:1.4rem"></i>
                    </a>
                </div>

                <div class="menu-inner-shadow"></div>

                <ul class="menu-inner py-1">
                    <li class="menu-item <?php echo $activePage === 'dashboard' ? 'active open' : ''; ?>">
                        <a href="index.php" class="menu-link">
                            <i class="menu-icon bi bi-speedometer2"></i>
                            <div data-i18n="Dashboards">Dashboard</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Item Verification</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'lost-submit' ? 'active open' : ''; ?>">
                        <a href="lost-submit.php" class="menu-link">
                            <i class="menu-icon bi bi-exclamation-triangle-fill"></i>
                            <div data-i18n="Basic">Submitted Lost Item</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'found-submit' ? 'active open' : ''; ?>">
                        <a href="found-submit.php" class="menu-link">
                            <i class="menu-icon bi bi-check-circle"></i>
                            <div data-i18n="Basic">Submitted Found Item</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Category Management</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'view-categories' ? 'active open' : ''; ?>">
                        <a href="view.php" class="menu-link">
                            <i class="menu-icon bi bi-list-ul"></i>
                            <div data-i18n="Basic">View Categories</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'add-category' ? 'active open' : ''; ?>">
                        <a href="add-category.php" class="menu-link">
                            <i class="menu-icon bi bi-plus-circle"></i>
                            <div data-i18n="Basic">Add Categories</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'update-category' ? 'active open' : ''; ?>">
                        <a href="view.php" class="menu-link">
                            <i class="menu-icon bi bi-pencil-square"></i>
                            <div data-i18n="Basic">Update Categories</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Matchmaking Tool</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'all-items' ? 'active open' : ''; ?>">
                        <a href="all-items.php" class="menu-link">
                            <i class="menu-icon bi bi-collection"></i>
                            <div data-i18n="Basic">All Items</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'matches' ? 'active open' : ''; ?>">
                        <a href="matches.php" class="menu-link">
                            <i class="menu-icon bi bi-link-45deg"></i>
                            <div data-i18n="Basic">Detected Matches</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'matched-items' ? 'active open' : ''; ?>">
                        <a href="matched-items.php" class="menu-link">
                            <i class="menu-icon bi bi-card-checklist"></i>
                            <div data-i18n="Basic">Matched Items</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'claimed-items' ? 'active open' : ''; ?>">
                        <a href="claimed-items.php" class="menu-link">
                            <i class="menu-icon bi bi-check2-circle"></i>
                            <div data-i18n="Basic">Claimed Items</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Claim Verification</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'claim-requests' ? 'active open' : ''; ?>">
                        <a href="claim-requests.php" class="menu-link">
                            <i class="menu-icon bi bi-shield-check"></i>
                            <div data-i18n="Basic">Claim Requests</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Reports Management</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'found-item-reports' ? 'active open' : ''; ?>">
                        <a href="found-item-reports.php" class="menu-link">
                            <i class="menu-icon bi bi-search"></i>
                            <div data-i18n="Basic">Found Item Reports</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'claims-reports' ? 'active open' : ''; ?>">
                        <a href="claims-reports.php" class="menu-link">
                            <i class="menu-icon bi bi-clipboard-data"></i>
                            <div data-i18n="Basic">Claims Reports</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'unsolved-reports' ? 'active open' : ''; ?>">
                        <a href="unsolved-reports.php" class="menu-link">
                            <i class="menu-icon bi bi-hourglass-split"></i>
                            <div data-i18n="Basic">Unsolved Reports</div>
                        </a>
                    </li>
                </ul>
            </aside>

            <div class="layout-page">
                <nav class="layout-navbar container-fluid navbar navbar-expand-xl align-items-center bg-navbar-theme" id="layout-navbar">
                    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
                        <div class="navbar-search-wrapper">
                            <div class="navbar-search-bar">
                                <input type="text" id="navSearch" placeholder="Search" autocomplete="off">
                                <i class="search-icon icon-base ri ri-search-line"></i>
                            </div>
                            <div id="navSearchResults" class="nav-search-results"></div>
                        </div>
                        <ul class="navbar-nav flex-row align-items-center">
                            <li class="nav-item navbar-dropdown dropdown-user dropdown">
                                <a class="nav-link dropdown-toggle hide-arrow p-0" href="javascript:void(0);" data-bs-toggle="dropdown">
                                    <div class="avatar avatar-online">
                                        <img src="../assets/img/avatars/1.png" alt class="rounded-circle" />
                                    </div>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="#">
                                            <div class="d-flex">
                                                <div class="flex-shrink-0 me-3">
                                                    <div class="avatar avatar-online">
                                                        <img src="../assets/img/avatars/1.png" alt class="w-px-40 h-auto rounded-circle" />
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></h6>
                                                    <small class="text-body-secondary"><?php echo ucfirst($_SESSION['role'] ?? 'admin'); ?></small>
                                                </div>
                                            </div>
                                        </a>
                                    </li>
                                    <li>
                                        <div class="dropdown-divider my-1"></div>
                                    </li>
                                    <li>
                                        <div class="d-grid px-4 pt-2 pb-1">
                                            <a class="btn btn-danger d-flex" href="logout.php">
                                                <small class="align-middle">Logout</small>
                                                <i class="ri ri-logout-box-r-line ms-2 ri-xs"></i>
                                            </a>
                                        </div>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </nav>

                <div class="content-wrapper">
                    <div class="container-fluid flex-grow-1 container-p-y">
                        <!-- CONTENT AREA -->