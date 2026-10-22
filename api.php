<?php
/**
 * api.php - one endpoint used by the admin, user and subject dashboards.
 *
 * ASSUMPTIONS (change the names below if your project differs):
 *  - config.php creates a PDO connection in $pdo (database archive_db)
 *  - login stores $_SESSION['user_id'] and $_SESSION['role'] ('user' | 'subject' | 'admin')
 *  - tables: users(id, full_name, username, role, created_at, status, deleted_at)
 *            archive_files(...)  -> see schema.sql and approval_migration.sql
 *
 * archive_files.status values:
 *   'pending'           file sent by a user, waiting for the subject officer
 *   'waiting_approval'  subject officer added Archive No + Archive Tree No, waiting for the admin
 *   'active'            approved by the admin -> part of the archive
 *   'rejected'          admin sent it back to the subject officer (see reject_reason)
 *   'deleted'           removed by the admin
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

/* Column list shared by every "approval" query (same field names the dashboards use) */
const APPROVAL_COLUMNS =
    "a.id, a.file_name AS name, a.department, a.subject_no AS subject, a.file_no AS fileNo,
     a.archive_no AS archive, a.rack_no AS rack, a.`start_date` AS `start`, a.`end_date` AS `end`,
     a.right_side AS `right`, a.left_side AS `left`, a.pages,
     a.submission_date AS submissionDate, DATE(a.sent_date) AS sentDate,
     u.full_name AS sentBy, a.reject_reason AS rejectReason";

/* ---------- STATE: everything a dashboard needs ---------- */
if ($action === 'state') {
    // Only APPROVED (status = 'active') files are part of the archive
    $files = $pdo->query(
        "SELECT id, file_name AS name, department, subject_no AS subject, file_no AS fileNo,
                archive_no AS archive, rack_no AS rack, `start_date` AS `start`, `end_date` AS `end`,
                right_side AS `right`, left_side AS `left`, pages,
                DATE(COALESCE(approved_at, created_at)) AS `date`, file_path AS fileUrl
         FROM archive_files WHERE status = 'active'
         ORDER BY COALESCE(approved_at, created_at) DESC"
    )->fetchAll(PDO::FETCH_ASSOC);

    $data = ['csrf' => $_SESSION['api_csrf'], 'role' => $role, 'files' => $files];

    if ($role === 'admin') {
        // Files waiting for the admin's decision
        $data['approvals'] = $pdo->query(
            "SELECT " . APPROVAL_COLUMNS . "
             FROM archive_files a
             LEFT JOIN users u ON u.id = a.sent_by
             WHERE a.status = 'waiting_approval'
             ORDER BY a.sent_date DESC, a.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

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

    if ($role === 'subject') {
        // Files the subject officer sent to the admin and is still waiting for
        $data['waiting'] = $pdo->query(
            "SELECT " . APPROVAL_COLUMNS . "
             FROM archive_files a
             LEFT JOIN users u ON u.id = a.sent_by
             WHERE a.status = 'waiting_approval'
             ORDER BY a.sent_date DESC, a.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);

        // Files the user sent that still need the Archive No + Archive Tree No.
        // Files rejected by the admin come back here with the reason.
        $data['pending'] = $pdo->query(
            "SELECT " . APPROVAL_COLUMNS . "
             FROM archive_files a
             LEFT JOIN users u ON u.id = a.sent_by
             WHERE a.status IN ('pending', 'rejected')
             ORDER BY a.id DESC"
        )->fetchAll(PDO::FETCH_ASSOC);
    }
    out($data);
}

/* ---------- everything below: POST only, CSRF protected ---------- */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') out(['error' => 'POST required.'], 405);

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!hash_equals($_SESSION['api_csrf'], $token)) out(['error' => 'Security token expired. Reload the page.'], 419);

$in = json_decode(file_get_contents('php://input'), true) ?: [];

/* ---------- SEND FOR APPROVAL (subject officer) ----------
 * Saves the reviewed details + Archive No + Archive Tree No.
 * The file is NOT active yet: it waits for the admin.
 */
if ($action === 'send_for_approval') {
    if ($role !== 'subject') out(['error' => 'Not allowed.'], 403);

    $id      = (int)($in['id'] ?? 0);
    $name    = trim((string)($in['name'] ?? ''));
    $dept    = (string)($in['department'] ?? '');
    $subject = trim((string)($in['subject'] ?? ''));
    $fileNo  = trim((string)($in['fileNo'] ?? ''));
    $start   = (string)($in['start'] ?? '');
    $end     = (string)($in['end'] ?? '');
    $right   = $in['right'] ?? null;
    $left    = $in['left'] ?? null;
    $pages   = $in['pages'] ?? null;
    $archive = trim((string)($in['archive'] ?? ''));
    $rack    = trim((string)($in['rack'] ?? ''));

    $dateOk = fn(string $d): bool => (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', $d) && strtotime($d) !== false;

    if ($id <= 0) out(['error' => 'Invalid file.'], 422);
    if ($name === '' || $subject === '' || $fileNo === '') out(['error' => 'File name, subject number and file number are required.'], 422);
    if (!in_array($dept, ['Institutions', 'Councils', 'Accounts'], true)) out(['error' => 'Invalid department.'], 422);
    if (!$dateOk($start) || !$dateOk($end) || $end < $start) out(['error' => 'Enter valid file start and end dates.'], 422);
    if (!is_numeric($right) || (int)$right < 0 || !is_numeric($left) || (int)$left < 0) out(['error' => 'Enter valid Right Side and Left Side page numbers.'], 422);
    if (!is_numeric($pages) || (int)$pages < 1) out(['error' => 'Enter a valid Total Pages number.'], 422);
    if ($archive === '') out(['error' => 'Enter the Archive Number.'], 422);
    if ($rack === '') out(['error' => 'Enter the Archive Tree Number.'], 422);

    // Archive Number must be unique among approved files and files waiting for approval
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM archive_files
         WHERE LOWER(archive_no) = LOWER(?) AND id <> ? AND status IN ('active', 'waiting_approval')"
    );
    $st->execute([$archive, $id]);
    if ((int)$st->fetchColumn() > 0) out(['error' => 'This Archive Number is already used by another file (or is waiting for admin approval).'], 409);

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET file_name = ?, department = ?, subject_no = ?, file_no = ?, `start_date` = ?, `end_date` = ?,
             right_side = ?, left_side = ?, pages = ?, archive_no = ?, rack_no = ?,
             status = 'waiting_approval', sent_by = ?, sent_date = NOW(),
             reject_reason = NULL, rejected_at = NULL
         WHERE id = ? AND status IN ('pending', 'rejected')"
    );
    $st->execute([$name, $dept, $subject, $fileNo, $start, $end, (int)$right, (int)$left, (int)$pages, $archive, $rack, $uid, $id]);
    if ($st->rowCount() === 0) out(['error' => 'File not found or already sent for approval.'], 404);
    out(['ok' => true]);
}

/* ---------- everything below: admin only ---------- */
if ($role !== 'admin') out(['error' => 'Not allowed.'], 403);

/* ---------- APPROVE FILE (admin) -> becomes part of the archive ---------- */
if ($action === 'approve_file') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) out(['error' => 'Invalid file.'], 422);

    // Last safety check: the Archive Number must not already belong to an active file
    $st = $pdo->prepare(
        "SELECT COUNT(*) FROM archive_files a
         JOIN archive_files b ON LOWER(b.archive_no) = LOWER(a.archive_no) AND b.id <> a.id AND b.status = 'active'
         WHERE a.id = ?"
    );
    $st->execute([$id]);
    if ((int)$st->fetchColumn() > 0) out(['error' => 'This Archive Number is already used by an archived file. Reject the file so the Subject Officer can correct it.'], 409);

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET status = 'active', approved_at = NOW(), approved_by = ?
         WHERE id = ? AND status = 'waiting_approval'"
    );
    $st->execute([$uid, $id]);
    if ($st->rowCount() === 0) out(['error' => 'File not found or already handled.'], 404);
    out(['ok' => true]);
}

/* ---------- REJECT FILE (admin) -> back to the subject officer with a reason ---------- */
if ($action === 'reject_file') {
    $id     = (int)($in['id'] ?? 0);
    $reason = trim((string)($in['reason'] ?? ''));

    if ($id <= 0) out(['error' => 'Invalid file.'], 422);
    if (mb_strlen($reason) < 5) out(['error' => 'Enter the reason (at least 5 characters).'], 422);

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET status = 'rejected', reject_reason = ?, rejected_at = NOW()
         WHERE id = ? AND status = 'waiting_approval'"
    );
    $st->execute([$reason, $id]);
    if ($st->rowCount() === 0) out(['error' => 'File not found or already handled.'], 404);
    out(['ok' => true]);
}

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

/* ---------- RESTORE FILE (admin) ---------- */
if ($action === 'restore_file') {
    $id = (int)($in['id'] ?? 0);
    if ($id <= 0) out(['error' => 'Invalid file.'], 422);

    $st = $pdo->prepare(
        "UPDATE archive_files
         SET status = 'active', deleted_at = NULL, delete_reason = NULL, deleted_by = NULL
         WHERE id = ? AND status = 'deleted'"
    );
    $st->execute([$id]);
    if ($st->rowCount() === 0) out(['error' => 'File not found or not deleted.'], 404);
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