<?php
/* =====================================================================
   subject_api.php  -  backend for subject_dashboard.php
   Only a logged-in, active user with role = 'subject' can use it.
   Every request is a POST with a JSON body: { "action": "...", ... }
   ===================================================================== */
require __DIR__ . '/config.php';

date_default_timezone_set('Asia/Colombo');
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

class InputError extends Exception {}

function out(array $data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

/* ---------- access control ---------- */
if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') out(['error' => 'Invalid request.'], 405);

$uid = (int)($_SESSION['user_id'] ?? 0);
if ($uid <= 0) out(['error' => 'Your session has expired. Please log in again.'], 401);

$st = $pdo->prepare('SELECT id, full_name, role, status FROM users WHERE id = ?');
$st->execute([$uid]);
$me = $st->fetch();
if (!$me || $me['status'] !== 'active') out(['error' => 'Your session has expired. Please log in again.'], 401);
if ($me['role'] !== 'subject')          out(['error' => 'Access denied.'], 403);

$token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (empty($_SESSION['subject_csrf']) || !hash_equals($_SESSION['subject_csrf'], $token)) {
    out(['error' => 'Security check failed. Please reload the page and try again.'], 403);
}

$in = json_decode(file_get_contents('php://input') ?: '', true);
if (!is_array($in)) out(['error' => 'Invalid request.'], 400);
$action = (string)($in['action'] ?? '');

/* ---------- helpers ---------- */
function log_activity(PDO $pdo, int $uid, string $action, string $type, ?int $id, ?string $details = null): void {
    try {
        $st = $pdo->prepare('INSERT INTO activity_log (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?,?,?,?,?,?)');
        $st->execute([$uid, $action, $type, $id, $details, $_SERVER['REMOTE_ADDR'] ?? null]);
    } catch (Throwable $e) { /* logging must never break the request */ }
}

function text_in($v, int $max, string $msg, bool $required = true): string {
    $v = trim((string)$v);
    if ($required && $v === '') throw new InputError($msg);
    if (mb_strlen($v) > $max)   throw new InputError($msg . ' - too long (max ' . $max . ' characters).');
    return $v;
}

function int_in($v, int $min, string $msg): int {
    if (is_string($v)) $v = trim($v);
    $n = filter_var($v, FILTER_VALIDATE_INT);
    if ($n === false || $n < $min) throw new InputError($msg);
    return (int)$n;
}

function date_in($v, string $msg = 'Please enter a valid date.'): string {
    $v = trim((string)$v);
    $d = DateTime::createFromFormat('Y-m-d', $v);
    if (!$d || $d->format('Y-m-d') !== $v) throw new InputError($msg);
    return $v;
}

/* validate the file detail fields shared by "submission" and "archive file" */
function file_details(PDO $pdo, array $in): array {
    $d = [
        'subject' => text_in($in['subject'] ?? '', 50,  'Please enter the Subject Number'),
        'fileNo'  => text_in($in['fileNo']  ?? '', 50,  'Please enter the File Number'),
        'name'    => text_in($in['name']    ?? '', 255, 'Please enter the File Name'),
        'dept'    => int_in($in['departmentId'] ?? 0, 1, 'Please select a department.'),
        'start'   => date_in($in['start'] ?? '', 'Please enter a valid File Start Date.'),
        'end'     => date_in($in['end']   ?? '', 'Please enter a valid File End Date.'),
        'right'   => int_in($in['rightPages'] ?? 0, 0, 'Right Side pages must be 0 or more.'),
        'left'    => int_in($in['leftPages']  ?? 0, 0, 'Left Side pages must be 0 or more.'),
        'total'   => int_in($in['pages']      ?? 0, 1, 'Total Pages must be 1 or more.'),
    ];
    if ($d['end'] < $d['start']) throw new InputError('File End Date must be on or after File Start Date.');

    $st = $pdo->prepare('SELECT id FROM departments WHERE id = ? AND is_active = 1');
    $st->execute([$d['dept']]);
    if (!$st->fetch()) throw new InputError('Please select a valid department.');
    return $d;
}

/* everything the dashboard needs in one go */
function load_all(PDO $pdo, array $me): array {
    $departments = $pdo->query(
        'SELECT id, code, name_si AS nameSi, name_en AS nameEn FROM departments WHERE is_active = 1 ORDER BY id'
    )->fetchAll();

    $files = $pdo->query(
        "SELECT f.id, f.file_name AS `name`, d.code AS department, f.department_id AS departmentId,
                f.subject_number AS subject, f.file_number AS fileNo, f.archive_number AS archive,
                f.rack_number AS rack, f.start_date AS `start`, f.end_date AS `end`,
                f.right_pages AS rightPages, f.left_pages AS leftPages, f.total_pages AS pages,
                DATE(f.added_at) AS `date`, f.availability
           FROM archive_files f
           JOIN departments d ON d.id = f.department_id
          WHERE f.status = 'active'
          ORDER BY f.id DESC"
    )->fetchAll();

    $pending = $pdo->query(
        "SELECT s.id, s.submission_date AS submissionDate, s.file_name AS `name`, d.code AS department,
                s.department_id AS departmentId, s.subject_number AS subject, s.file_number AS fileNo,
                s.start_date AS `start`, s.end_date AS `end`, s.right_pages AS rightPages,
                s.left_pages AS leftPages, s.total_pages AS pages,
                u.full_name AS submittedBy, u.designation AS submitterDesignation
           FROM file_submissions s
           JOIN departments d ON d.id = s.department_id
           JOIN users u       ON u.id = s.submitted_by
          WHERE s.status = 'pending'
          ORDER BY s.created_at ASC, s.id ASC"
    )->fetchAll();

    $issues = $pdo->query(
        "SELECT i.id, i.file_id AS fileId, f.archive_number AS archive, f.file_name AS `name`,
                i.issued_to_name AS issuedTo, i.issued_to_designation AS designation,
                i.issued_date AS issuedDate, i.returned_date AS returnedDate, i.purpose,
                ui.full_name AS issuedBy, ur.full_name AS receivedBy
           FROM file_issues i
           JOIN archive_files f ON f.id = i.file_id
           JOIN users ui        ON ui.id = i.issued_by
           LEFT JOIN users ur   ON ur.id = i.received_by
          ORDER BY (i.returned_date IS NULL) DESC, i.issued_date DESC, i.id DESC
          LIMIT 1000"
    )->fetchAll();

    return [
        'ok'          => true,
        'me'          => ['name' => $me['full_name']],
        'departments' => $departments,
        'files'       => $files,
        'pending'     => $pending,
        'issues'      => $issues,
    ];
}

/* ---------- actions ---------- */
try {
    switch ($action) {

        case 'load':
            out(load_all($pdo, $me));

        /* Subject Officer edits a user's submission (still waiting) */
        case 'edit_pending': {
            $id = int_in($in['id'] ?? 0, 1, 'Invalid file.');
            $d  = file_details($pdo, $in);

            $st = $pdo->prepare("SELECT id FROM file_submissions WHERE id = ? AND status = 'pending'");
            $st->execute([$id]);
            if (!$st->fetch()) throw new InputError('This file has already been processed.');

            $pdo->prepare(
                'UPDATE file_submissions
                    SET subject_number = ?, department_id = ?, file_number = ?, file_name = ?,
                        start_date = ?, end_date = ?, right_pages = ?, left_pages = ?, total_pages = ?
                  WHERE id = ?'
            )->execute([$d['subject'], $d['dept'], $d['fileNo'], $d['name'], $d['start'], $d['end'],
                        $d['right'], $d['left'], $d['total'], $id]);

            log_activity($pdo, $uid, 'edit_submission', 'submission', $id);
            out(['ok' => true, 'message' => 'Changes saved.']);
        }

        /* Add Archive No. + Rack No. and move the file into the archive */
        case 'approve': {
            $id   = int_in($in['id'] ?? 0, 1, 'Invalid file.');
            $d    = file_details($pdo, $in);
            $arc  = text_in($in['archive'] ?? '', 50, 'Please enter the Archive Number');
            $rack = text_in($in['rack']    ?? '', 50, 'Please enter the Rack Number');

            $pdo->beginTransaction();

            $st = $pdo->prepare("SELECT id FROM file_submissions WHERE id = ? AND status = 'pending' FOR UPDATE");
            $st->execute([$id]);
            if (!$st->fetch()) throw new InputError('This file has already been processed.');

            $st = $pdo->prepare('SELECT id FROM archive_files WHERE archive_number = ?');
            $st->execute([$arc]);
            if ($st->fetch()) throw new InputError('This Archive Number is already used by another file.');

            $pdo->prepare(
                'UPDATE file_submissions
                    SET subject_number = ?, department_id = ?, file_number = ?, file_name = ?,
                        start_date = ?, end_date = ?, right_pages = ?, left_pages = ?, total_pages = ?
                  WHERE id = ?'
            )->execute([$d['subject'], $d['dept'], $d['fileNo'], $d['name'], $d['start'], $d['end'],
                        $d['right'], $d['left'], $d['total'], $id]);

            $pdo->prepare(
                'INSERT INTO archive_files
                    (submission_id, archive_number, rack_number, subject_number, department_id, file_number,
                     file_name, start_date, end_date, right_pages, left_pages, total_pages, added_by)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([$id, $arc, $rack, $d['subject'], $d['dept'], $d['fileNo'], $d['name'], $d['start'],
                        $d['end'], $d['right'], $d['left'], $d['total'], $uid]);
            $fileId = (int)$pdo->lastInsertId();

            $pdo->prepare("UPDATE file_submissions SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() WHERE id = ?")
                ->execute([$uid, $id]);

            $pdo->commit();
            log_activity($pdo, $uid, 'approve_file', 'archive_file', $fileId, 'Archive No: ' . $arc . ', Rack No: ' . $rack);
            out(['ok' => true, 'message' => 'File added to the archive (Archive No: ' . $arc . ').']);
        }

        /* Edit a file that is already in the archive */
        case 'edit_file': {
            $id   = int_in($in['id'] ?? 0, 1, 'Invalid file.');
            $d    = file_details($pdo, $in);
            $arc  = text_in($in['archive'] ?? '', 50, 'Please enter the Archive Number');
            $rack = text_in($in['rack']    ?? '', 50, 'Please enter the Rack Number');

            $st = $pdo->prepare("SELECT id FROM archive_files WHERE id = ? AND status = 'active'");
            $st->execute([$id]);
            if (!$st->fetch()) throw new InputError('This file was not found in the archive.');

            $st = $pdo->prepare('SELECT id FROM archive_files WHERE archive_number = ? AND id <> ?');
            $st->execute([$arc, $id]);
            if ($st->fetch()) throw new InputError('This Archive Number is already used by another file.');

            $pdo->prepare(
                'UPDATE archive_files
                    SET archive_number = ?, rack_number = ?, subject_number = ?, department_id = ?, file_number = ?,
                        file_name = ?, start_date = ?, end_date = ?, right_pages = ?, left_pages = ?, total_pages = ?,
                        updated_by = ?
                  WHERE id = ?'
            )->execute([$arc, $rack, $d['subject'], $d['dept'], $d['fileNo'], $d['name'], $d['start'], $d['end'],
                        $d['right'], $d['left'], $d['total'], $uid, $id]);

            log_activity($pdo, $uid, 'edit_file', 'archive_file', $id);
            out(['ok' => true, 'message' => 'Archive file updated.']);
        }

        /* A person takes a file out of the archive */
        case 'issue_file': {
            $fileId = int_in($in['fileId'] ?? 0, 1, 'Please select a valid archive file.');
            $to     = text_in($in['issuedTo'] ?? '', 150, 'Please enter who is taking the file');
            $desig  = text_in($in['designation'] ?? '', 150, 'Designation', false);
            $date   = date_in($in['issuedDate'] ?? '', 'Please enter a valid date taken.');
            $purp   = text_in($in['purpose'] ?? '', 255, 'Purpose', false);

            $st = $pdo->prepare("SELECT id FROM archive_files WHERE id = ? AND status = 'active'");
            $st->execute([$fileId]);
            if (!$st->fetch()) throw new InputError('This file was not found in the archive.');

            // the database trigger refuses a file that is already issued
            $pdo->prepare(
                'INSERT INTO file_issues (file_id, issued_to_name, issued_to_designation, issued_date, purpose, issued_by)
                 VALUES (?,?,?,?,?,?)'
            )->execute([$fileId, $to, $desig !== '' ? $desig : null, $date, $purp !== '' ? $purp : null, $uid]);
            $issueId = (int)$pdo->lastInsertId();

            log_activity($pdo, $uid, 'issue_file', 'archive_file', $fileId, 'Issued to: ' . $to . ' on ' . $date);
            out(['ok' => true, 'message' => 'File issue recorded.', 'issueId' => $issueId]);
        }

        /* The file is handed back */
        case 'return_file': {
            $id   = int_in($in['id'] ?? 0, 1, 'Invalid record.');
            $date = date_in($in['returnedDate'] ?? '', 'Please enter a valid return date.');

            $pdo->beginTransaction();
            $st = $pdo->prepare('SELECT id, file_id, issued_date FROM file_issues WHERE id = ? AND returned_date IS NULL FOR UPDATE');
            $st->execute([$id]);
            $row = $st->fetch();
            if (!$row) throw new InputError('This file has already been marked as returned.');
            if ($date < $row['issued_date']) throw new InputError('Return date cannot be earlier than the date taken (' . $row['issued_date'] . ').');

            // the database trigger puts the file back "in archive"
            $pdo->prepare('UPDATE file_issues SET returned_date = ?, received_by = ? WHERE id = ?')
                ->execute([$date, $uid, $id]);
            $pdo->commit();

            log_activity($pdo, $uid, 'return_file', 'archive_file', (int)$row['file_id'], 'Returned on ' . $date);
            out(['ok' => true, 'message' => 'File return recorded. The file is back in the archive.']);
        }

        default:
            out(['error' => 'Unknown action.'], 400);
    }
} catch (InputError $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    out(['error' => $e->getMessage()], 422);
} catch (PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    // messages raised by the database triggers (SQLSTATE 45000), e.g. "file is already issued"
    if ($e->getCode() === '45000') out(['error' => $e->errorInfo[2] ?? 'This action is not allowed.'], 409);
    if ($e->getCode() === '23000') out(['error' => 'Could not save: a value is duplicated or invalid (check the Archive Number).'], 409);
    out(['error' => 'Database error. Please try again.'], 500);
}
