<?php
/**
 * Teacher Layout Helper
 */
function render_teacher_header($title = 'Teacher Portal') {
    global $pageTitle;
    $pageTitle = $title;
    require_once __DIR__ . '/header.php';
    require_once __DIR__ . '/sidebar.php';
    require_once __DIR__ . '/navbar.php';
}

function render_teacher_footer($extraScripts = null) {
    require_once __DIR__ . '/footer.php';
}
