<?php
/**
 * Student Layout Helper
 */
function render_student_header($title = 'Student Portal') {
    global $pageTitle;
    $pageTitle = $title;
    require_once __DIR__ . '/header.php';
    require_once __DIR__ . '/sidebar.php';
    require_once __DIR__ . '/navbar.php';
}

function render_student_footer($extraScripts = null) {
    require_once __DIR__ . '/footer.php';
}
