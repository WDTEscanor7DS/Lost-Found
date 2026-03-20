<?php

/**
 * User page header template.
 * Include at the top of every user page.
 * Set $pageTitle and $activePage before including.
 */
require_once __DIR__ . '/../../auth/guard_user.php';

if (!isset($pageTitle)) $pageTitle = 'Lost & Found';
if (!isset($activePage)) $activePage = '';

require_once __DIR__ . '/../../backend/config.php';

$navSearchItems = [
    ['label' => 'Dashboard', 'url' => 'index.php', 'icon' => 'bi bi-speedometer2'],
    ['label' => 'Report Lost Item', 'url' => 'report-lost-item.php', 'icon' => 'bi bi-exclamation-triangle'],
    ['label' => 'Report Found Item', 'url' => 'report-found-item.php', 'icon' => 'bi bi-check-circle-fill'],
    ['label' => 'Track Status', 'url' => 'track-status.php', 'icon' => 'bi bi-clipboard'],
    ['label' => 'Browse Found Items', 'url' => 'browse-found.php', 'icon' => 'bi bi-search'],
    ['label' => 'My Claims', 'url' => 'my-claims.php', 'icon' => 'bi bi-shield-check'],
    ['label' => 'Notifications', 'url' => 'notifications.php', 'icon' => 'bi bi-bell'],
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
    <link rel="icon" type="image/x-icon" href="../assets/img/icon/company-icon.png" />
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
                        <span class="menu-header-text">Item Reporting</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'report-lost' ? 'active open' : ''; ?>">
                        <a href="report-lost-item.php" class="menu-link">
                            <i class="menu-icon bi bi-exclamation-triangle"></i>
                            <div data-i18n="Basic">Report Lost Item</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'report-found' ? 'active open' : ''; ?>">
                        <a href="report-found-item.php" class="menu-link">
                            <i class="menu-icon bi bi-check-circle-fill"></i>
                            <div data-i18n="Basic">Report Found Item</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Tracking & Claims</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'track-status' ? 'active open' : ''; ?>">
                        <a href="track-status.php" class="menu-link">
                            <i class="menu-icon bi bi-clipboard"></i>
                            <div data-i18n="Basic">Track Status</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'browse-found' ? 'active open' : ''; ?>">
                        <a href="browse-found.php" class="menu-link">
                            <i class="menu-icon bi bi-search"></i>
                            <div data-i18n="Basic">Browse Found Items</div>
                        </a>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'my-claims' ? 'active open' : ''; ?>">
                        <a href="my-claims.php" class="menu-link">
                            <i class="menu-icon bi bi-shield-check"></i>
                            <div data-i18n="Basic">My Claims</div>
                        </a>
                    </li>

                    <li class="menu-header mt-7">
                        <span class="menu-header-text">Notifications</span>
                    </li>
                    <li class="menu-item <?php echo $activePage === 'notifications' ? 'active open' : ''; ?>">
                        <a href="notifications.php" class="menu-link">
                            <i class="menu-icon bi bi-bell"></i>
                            <div data-i18n="Basic">Notifications</div>
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
                                                    <h6 class="mb-0"><?php echo htmlspecialchars($_SESSION['username'] ?? 'User'); ?></h6>
                                                    <small class="text-body-secondary">User</small>
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