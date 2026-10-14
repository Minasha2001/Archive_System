<?php
// config.php - සියලු පිටු වල include කරන ප්‍රධාන ගොනුව
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}
date_default_timezone_set('Asia/Colombo');

const DB_HOST = 'localhost';
const DB_NAME = 'archive_db';
const DB_USER = 'root';
const DB_PASS = '';

const DEPARTMENTS = ['ආයතන', 'සභා', 'ගිණුම්'];   // Institutions | Councils | Accounts
const ROLE_PAGES  = ['user' => 'user_dashboard.php', 'admin' => 'admin_dashboard.php', 'subject' => 'subject_dashboard.php'];
const ROLE_LABELS = ['user' => 'පරිශීලක', 'admin' => 'පරිපාලක', 'subject' => 'විෂය නිලධාරී'];

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, PDO::ATTR_EMULATE_PREPARES => false]
    );
} catch (PDOException $ex) {
    exit('Database connection failed. config.php හි DB_NAME / DB_USER / DB_PASS පරීක්ෂා කරන්න.');
}

function e($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(16));
    return $_SESSION['csrf'];
}
function csrf_field(): string { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_ok(): bool { return hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? ''); }

function flash_set(string $type, string $msg): void { $_SESSION['flash'] = [$type, $msg]; }
function flash_get(): ?array { $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f; }

function valid_date($d): bool { $x = DateTime::createFromFormat('Y-m-d', (string)$d); return $x && $x->format('Y-m-d') === $d; }

/** login වී ඇති, active user ගේ විස්තර */
function require_login(PDO $pdo): array {
    if (empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ? AND status = "active"');
    $st->execute([$_SESSION['user_id']]);
    $u = $st->fetch();
    if (!$u) { session_destroy(); header('Location: index.php'); exit; }
    return $u;
}
/** නිශ්චිත user type එකකට පමණක් ඇතුල් වීමට */
function require_role(PDO $pdo, string $role): array {
    $u = require_login($pdo);
    if ($u['role'] !== $role) { header('Location: ' . ROLE_PAGES[$u['role']]); exit; }
    return $u;
}
