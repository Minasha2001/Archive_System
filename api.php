<?php
/**
 * api.php - one endpoint used by the admin, user and subject dashboards.
 *
 * ASSUMPTIONS (change the names below if your project differs):
 *  - config.php creates a PDO connection in $pdo (database archive_db)
 *  - login stores $_SESSION['user_id'] and $_SESSION['role'] ('user' | 'subject' | 'admin')
 *  - tables: users(id, full_name, username, role, created_at, status, deleted_at)
 *            archive_files(...)  -> see schema.sql
 */
require_once __DIR__ . '/auth.php';   // also loads config.php and starts the session

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/* ---------- 1. must be logged in ---------- */
$uid  = current_user_id();
$role = current_role();
if (!$uid) {
    // Testing without login: tell the dashboards to use their demo mode instead of kicking the visitor out
    if (!REQUIRE_LOGIN) out(['error' => 'Not logged in (demo mode).'], 503);
    out(['error' => 'Please log in again.', 'logout' => true], 401);
}

/* ---------- 2. account must still be active (deleted users are logged out here) ---------- */
$st = $pdo->prepare("SELECT status FROM users WHERE id = ?");
$st->execute([$uid]);
if ($st->fetchColumn() !== 'active') {
    session_unset();
    session_destroy();
    out(['error' => 'Your account has been removed by the administrator.', 'logout' => true], 401);
}

if (empty($_SESSION['api_csrf'])) $_SESSION['api_csrf'] = bin2hex(random_bytes(32));
$action = $_GET['action'] ?? 'state';

/* ---------- STATE: everything a dashboard needs ---------- */
if ($action === 'state') {
    $files = $pdo->query(
        "SELECT id, file_name AS name, department, subject_no AS subject, file_no AS fileNo,
                archive_no AS archive, `start_date` AS `start`, `end_date` AS `end`, pages,
                DATE(created_at) AS `date`, file_path AS fileUrl
         FROM archive_files WHERE status = 'active' ORDER BY created_at DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = ['csrf' => $_SESSION['api_csrf'], 'role' => $role, 'files' => $files];

    if ($role === 'admin') {
        $data['deletedFiles'] = $pdo->query(
            "SELECT id, file_name AS name, department, subject_no AS subject, file_no AS fileNo,
                    archive_no AS archive, DATE(deleted_at) AS deletedDate, delete_reason AS reason
             FROM archive_files WHERE status = 'deleted' ORDER BY deleted_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        $data['users'] = $pdo->query(
            "SELECT id, full_name AS name, username, role, DATE(created_at) AS registered
             FROM users WHERE status = 'active' AND role <> 'admin' ORDER BY id"
        )->fetchAll(PDO::FETCH_ASSOC);

        $data['deletedUsers'] = $pdo->query(
            "SELECT id, full_name AS name, username, role, DATE(created_at) AS registered,
                    DATE(deleted_at) AS deletedDate
             FROM users WHERE status = 'deleted' AND role <> 'admin' ORDER BY deleted_at DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
    out($data);
}

/* ---------- everything below: admin only, POST only, CSRF protected ---------- */
if ($role !== 'admin') out(['error' => 'Not allowed.'], 403);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'POST required.'], 405);

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['api_csrf'], $token)) out(['error' => 'Security token expired. Reload the page.'], 419);

$in = json_decode(file_get_contents('php://input'), true) ?: [];

/* ---------- DELETE FILE (reason + date) ---------- */
if ($action === 'delete_file') {
    $id     = (int)($in['id'] ?? 0);
    $date   = (string)($in['date'] ?? '');
    $reason = trim((string)($in['reason'] ?? ''));

    if ($id <= 0) out(['error' => 'Invalid file.'], 422);
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) || !strtotime($date)) out(['error' => 'Select a valid deletion date.'], 422);
    if (mb_strlen($reason) < 5) out(['error' => 'Enter the reason (at least 5 characters).'], 422);

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET status = 'deleted', deleted_at = ?, delete_reason = ?, deleted_by = ?
         WHERE id = ? AND status = 'active'"
    );
    $st->execute([$date . ' ' . date('H:i:s'), $reason, $uid, $id]);
    if ($st->rowCount() === 0) out(['error' => 'File not found or already deleted.'], 404);
    out(['ok' => true]);
}

/* ---------- DELETE USER ---------- */
if ($action === 'delete_user') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0 || $id === (int)$uid) out(['error' => 'Invalid user.'], 422);

    $st = $pdo->prepare(
        "UPDATE users SET status = 'deleted', deleted_at = NOW()
         WHERE id = ? AND status = 'active' AND role <> 'admin'"
    );
    $st->execute([$id]);
    if ($st->rowCount() === 0) out(['error' => 'User not found or already deleted.'], 404);
    out(['ok' => true]);
}

out(['error' => 'Unknown action.'], 400);
