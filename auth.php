<?php
/**
 * auth.php - one place that decides "is this person logged in, and who are they?"
 * Used by user_dashboard.php, admin_dashboard.php and api.php.
 */
require_once __DIR__ . '/config.php';
if (session_status() === PHP_SESSION_NONE) session_start();

/* ===== SETTINGS ===== */
const REQUIRE_LOGIN = false;      // set to false ONLY while testing, to open the dashboards without logging in
const LOGIN_PAGE    = 'index.php';

// The session names your index.php uses after a successful login.
// Open session_check.php after logging in to see them, then add yours to these lists.
const SESSION_ID_KEYS   = ['user_id', 'id', 'userid', 'uid', 'users_id', 'user_ID'];
const SESSION_ROLE_KEYS = ['role', 'user_role', 'type', 'user_type', 'role_name'];

function session_pick(array $keys) {
    foreach ($keys as $k) {
        if (isset($_SESSION[$k]) && $_SESSION[$k] !== '') return $_SESSION[$k];
        if (isset($_SESSION['user']) && is_array($_SESSION['user']) && isset($_SESSION['user'][$k]) && $_SESSION['user'][$k] !== '') {
            return $_SESSION['user'][$k];
        }
    }
    return null;
}

function current_user_id() { return session_pick(SESSION_ID_KEYS); }

function current_role() {
    $r = session_pick(SESSION_ROLE_KEYS);
    return $r === null ? null : strtolower(trim((string)$r));
}

/** Call at the top of a page. $roles = [] means any logged-in user. */
function require_page_login(array $roles = []): void {
    if (!REQUIRE_LOGIN) return;
    if (!current_user_id() || ($roles && !in_array(current_role(), $roles, true))) {
        header('Location: ' . LOGIN_PAGE);
        exit;
    }
}
