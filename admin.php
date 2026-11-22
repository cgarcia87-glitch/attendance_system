<?php
/**
 * Admin Layout Helper
 */
function render_admin_header($title = 'Admin Panel') {
    global $pageTitle;
    $pageTitle = $title;
    require_once __DIR__ . '/header.php';
    require_once __DIR__ . '/sidebar.php';
    require_once __DIR__ . '/navbar.php';
}

function render_admin_footer($extraScripts = null) {
    require_once __DIR__ . '/footer.php';
}
