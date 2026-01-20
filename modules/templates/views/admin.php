<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate-icons.css">
    <link rel="stylesheet" href="css/trongate.css">
    <link rel="stylesheet" href="templates_module/css/admin.css">
    <?= $additional_includes_top ?? '' ?>
    <title><?= $page_title ?? 'Admin Panel' ?></title>
</head>
<body class="theme-<?= $theme ?? 'default' ?>">

<header>
    <div class="header-lg">
        <div><?= WEBSITE_NAME ?></div>
        <div class="top-rhs">
            <div>
                <nav>
                    <ul class="top-nav">
                        <li><a href="#" class="highlight"><i class="tg tg-envelope"></i> Messages (1)</a></li>
                        <li><a href="#"><i class="tg tg-shopping-cart"></i> Orders</a></li>
                    </ul>
                </nav>
            </div>
            <div class="top-rhs-selector"><i class="tg tg-user"></i> &#9660;</div>
        </div>
    </div>
    <div class="header-sm">
        <div id="hamburger">&#9776;</div>
        <div><?= WEBSITE_NAME ?></div>
        <div class="top-rhs-selector"><i class="tg tg-user"></i> &#9660;</div>
    </div>
    
    <!-- Admin Settings Dropdown -->
    <div id="admin-settings-dropdown">
        <ul>
            <li><a href="trongate_administrators/update_your_details"><i class="tg tg-user"></i> Update Your Details</a></li>
            <li class="top-border"><a href="trongate_administrators/logout"><i class="tg tg-sign-out"></i> Log Out</a></li>
        </ul>
    </div>
</header>

<aside>
    <nav aria-label="Main navigation">
        <ul class="side-nav-menu">
            <li>
                <a href="dashboard">
                    Dashboard
                </a>
            </li>
            <li class="nav-dropdown">
                <div>
                    <span>Messages</span>
                    <span aria-hidden="true" class="arrow-icon">&#9660;</span>
                </div>
                <ul id="messages-submenu" class="nav-submenu">
                    <li><a href="messages/inbox">Inbox</a></li>
                    <li><a href="messages/important">Important</a></li>
                    <li><a href="messages/junk">Junk</a></li>
                    <li><a href="messages/archives">Archives</a></li>
                </ul>
            </li>
            <li>
                <a href="news/manage">
                    Manage News
                </a>
            </li>
            <li class="nav-dropdown">
                <div>
                    <span>Reports</span>
                    <span aria-hidden="true" class="arrow-icon">&#9660;</span>
                </div>
                <ul id="reports-submenu" class="nav-submenu">
                    <li><a href="reports/sales">Sales Reports</a></li>
                    <li><a href="reports/analytics">Analytics</a></li>
                    <li><a href="reports/exports">Exports</a></li>
                </ul>
            </li>
            <li>
                <a href="settings">
                    Settings
                </a>
            </li>
            <li>
                <a href="users/manage">
                    Manage Users
                </a>
            </li>
        </ul>
    </nav>
</aside>

<main>
    <div class="center-stage"><?= display($data) ?></div>
</main>

<footer>
    <div class="footer-lg">
        <div class="footer-left">
            <span>&copy; <?= date('Y').' '.WEBSITE_NAME ?></span>
            <span class="separator">|</span>
            <a href="https//trongate.io/" target="_blank">Powered by Trongate</a>
        </div>
        <div class="footer-right">
            <a href="#">Documentation</a>
            <a href="https://github.com/trongate/trongate-framework" target="_blank">GitHub</a>
        </div>
    </div>
    
    <div class="footer-sm">
        <div>&copy; <?= date('Y').' '.WEBSITE_NAME ?></div>
        <div class="footer-sm-links">
            <a href="https//trongate.io/" target="_blank">Powered by Trongate</a>
            <span class="separator">|</span>
            <a href="#">Documentation</a>
            <span class="separator">|</span>
            <a href="#">Support</a>
        </div>
    </div>
</footer>

<!-- Mobile slide navigation -->
<div class="nav-overlay" id="nav-overlay"></div>

<nav class="slide-nav" id="slide-nav" aria-label="Navigation menu">
  <div class="slide-nav-header">
    <div class="slide-nav-close" id="close-slide-nav">&times;</div>
  </div>

  <ul class="slide-nav-list">
    <li><a href="dashboard">Dashboard</a></li>
    <li><a href="users">Manage Users</a></li>
    <li><a href="products">Products</a></li>
    <li><a href="orders">Orders</a></li>
    <li><a href="messages" class="highlight">Messages (1)</a></li>
    <li><a href="settings">Settings</a></li>
    <li><a href="logout">Log Out</a></li>
  </ul>
</nav>

<script src="templates_module/js/admin.js"></script>
<?= $additional_includes_btm ?? '' ?>
</body>
</html>