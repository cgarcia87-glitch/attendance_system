<?php
/**
 * Global Utility Functions
 */

require_once __DIR__ . '/../config/config.php';

function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function e($string) {
    return htmlspecialchars((string)$string, ENT_QUOTES, 'UTF-8');
}

function log_activity($db, $user_id, $action, $details = null) {
    try {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $action, $details, $ip]);
    } catch (Exception $e) {
        // Fail silently on logging
    }
}

function create_notification($db, $user_id, $title, $message, $type = 'info') {
    try {
        $stmt = $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $title, $message, $type]);
    } catch (Exception $e) {
        // Fail silently
    }
}

function format_date($dateStr, $format = 'M d, Y') {
    if (empty($dateStr)) return 'N/A';
    return date($format, strtotime($dateStr));
}

function format_time($timeStr, $format = 'h:i A') {
    if (empty($timeStr)) return 'N/A';
    return date($format, strtotime($timeStr));
}

function format_datetime($dtStr, $format = 'M d, Y h:i A') {
    if (empty($dtStr)) return 'N/A';
    return date($format, strtotime($dtStr));
}

function get_status_badge($status) {
    $status = strtoupper($status);
    switch ($status) {
        case 'PRESENT':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle-fill me-1"></i>Present</span>';
        case 'ABSENT':
            return '<span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1"><i class="bi bi-x-circle-fill me-1"></i>Absent</span>';
        case 'LATE':
            return '<span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1"><i class="bi bi-clock-history me-1"></i>Late</span>';
        case 'EXCUSED':
            return '<span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1"><i class="bi bi-info-circle-fill me-1"></i>Excused</span>';
        case 'ACTIVE':
            return '<span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1"><i class="bi bi-check-circle me-1"></i>Active</span>';
        case 'INACTIVE':
            return '<span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Inactive</span>';
        default:
            return '<span class="badge bg-light text-dark border px-2 py-1">' . htmlspecialchars($status) . '</span>';
    }
}

function calculate_attendance_percentage($present, $late, $excused, $total) {
    if ($total <= 0) return 0;
    // Standard academic formula: (Present + (Late * 0.5) + Excused) / Total * 100
    // or (Present + Excused) / Total * 100 depending on policy; here we weight Present=1.0, Late=0.5, Excused=1.0
    $weighted = $present + ($late * 0.5) + $excused;
    $pct = ($weighted / $total) * 100;
    return round(min(100, max(0, $pct)), 1);
}

function send_json($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}
