<?php

require __DIR__ . '/config.php';



/*

 * Change Password (logged-in user)

 *  - user must be logged in

 *  - current password must be correct

 *  - new password must be different from the current one

 *  - after 5 wrong current-password attempts the form is locked for 5 minutes

 *

 * The user id is read from the session (user_id / id / user['id'] / username).

 */

if (session_status() === PHP_SESSION_NONE) session_start();



/* Find the logged-in user's id. Tries the common session keys used by login pages. */

$userId = 0;

foreach (['user_id', 'id', 'userid', 'userId', 'uid'] as $k) {

    if (!empty($_SESSION[$k])) { $userId = (int)$_SESSION[$k]; break; }

}

if (!$userId && !empty($_SESSION['user']) && is_array($_SESSION['user'])) {

    $userId = (int)($_SESSION['user']['id'] ?? $_SESSION['user']['user_id'] ?? 0);

}

if (!$userId) {

    /* Last fallback: login saved only the username */

    $sessName = $_SESSION['username'] ?? ($_SESSION['user']['username'] ?? '');

    if (is_string($sessName) && $sessName !== '') {

        $q = $pdo->prepare('SELECT id FROM users WHERE username = ? AND status = "active"');

        $q->execute([$sessName]);

        $userId = (int)$q->fetchColumn();

    }

}

/* The page always opens. If the account cannot be identified, saving shows a message instead. */

$sessionOk = $userId > 0;

/* Load the logged-in user's display name and role from the database. */
$loggedInName = 'User';
$loggedInRole = 'User Account';

if ($userId > 0) {
    $profileStmt = $pdo->prepare(
        'SELECT full_name, username, user_type FROM users WHERE id = ? AND status = "active" LIMIT 1'
    );
    $profileStmt->execute([$userId]);
    $loggedInUser = $profileStmt->fetch(PDO::FETCH_ASSOC);

    if ($loggedInUser) {
        $loggedInName = trim((string)($loggedInUser['full_name'] ?? ''));
        if ($loggedInName === '') {
            $loggedInName = trim((string)($loggedInUser['username'] ?? 'User'));
        }
        $loggedInRole = trim((string)($loggedInUser['user_type'] ?? 'User Account'));
        if ($loggedInRole === '') $loggedInRole = 'User Account';
    }
}




const CP_MAX_TRIES = 5;

const CP_LOCK_SECS = 300;



$errors  = [];

$success = false;



$lockedUntil = (int)($_SESSION['cp_locked_until'] ?? 0);

if ($lockedUntil && $lockedUntil <= time()) {

    unset($_SESSION['cp_locked_until'], $_SESSION['cp_tries']);

    $lockedUntil = 0;

}

$locked = $lockedUntil > time();



if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_ok()) {

        $errors[] = 'Security check failed. Please reload the page and try again.';

    } elseif (!$sessionOk) {

        $errors[] = 'Your account could not be identified. Please log in again and try once more.';

    } elseif ($locked) {

        $mins = (int)ceil(($lockedUntil - time()) / 60);

        $errors[] = "Too many incorrect attempts. Please try again in $mins minute(s).";

    } else {

        $current = $_POST['current_password'] ?? '';

        $p1      = $_POST['password'] ?? '';

        $p2      = $_POST['confirm_password'] ?? '';



        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? AND status = "active"');

        $st->execute([$userId]);

        $row = $st->fetch();



        if (!$row) { $errors[] = 'Your account could not be found or is not active.'; }



        $currentOk = $row && password_verify($current, $row['password_hash']);



        if ($row && !$currentOk) {

            $_SESSION['cp_tries'] = (int)($_SESSION['cp_tries'] ?? 0) + 1;

            if ($_SESSION['cp_tries'] >= CP_MAX_TRIES) {

                $_SESSION['cp_locked_until'] = time() + CP_LOCK_SECS;

                $locked = true;

                $errors[] = 'Too many incorrect attempts. The form is locked for 5 minutes.';

            } else {

                $left = CP_MAX_TRIES - $_SESSION['cp_tries'];

                $errors[] = "Current password is incorrect. $left attempt(s) remaining.";

            }

        }



        if (strlen($p1) < 6)  $errors[] = 'New password must contain at least 6 characters.';

        if (strlen($p1) > 72) $errors[] = 'New password is too long (maximum 72 characters).';

        if ($p1 !== $p2)      $errors[] = 'New Password and Confirm Password do not match.';

        if ($currentOk && $p1 !== '' && $p1 === $current) {

            $errors[] = 'New password must be different from your current password.';

        }



        if (!$errors) {

            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')

                ->execute([password_hash($p1, PASSWORD_DEFAULT), $userId]);

            unset($_SESSION['cp_tries'], $_SESSION['cp_locked_until']);

            session_regenerate_id(true);

            $success = true;

        }

    }

}

?>

<!DOCTYPE html>

<html lang="en">

<head>

<meta charset="UTF-8">

<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Change Password - Archives File Management System</title>



<link rel="preconnect" href="https://fonts.googleapis.com">

<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">



<style>

:root{

    --ink:#14233d; --navy:#0b2146; --navy-2:#16386a; --gold:#d4a24a; --gold-2:#f0cf8a; --gold-soft:#fbf3e1;

    --bg:#eef2f8; --card:#fff; --line:#e1e7f1; --muted:#6a7890; --blue:#2563d0;

    --shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);

    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px; --fs-2xl:26px;

}

*{margin:0;padding:0;box-sizing:border-box}

body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;

    background:radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%),var(--bg)}

button,input,select{font:inherit}

button{cursor:pointer}

[hidden]{display:none!important}

:focus-visible{outline:2px solid var(--blue);outline-offset:2px}

.app{display:flex;min-height:100vh}



/* ===== SIDEBAR ===== */

.sidebar{width:272px;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;overflow-x:hidden;overflow-y:auto;color:#fff;

    background:linear-gradient(180deg,#0c2347 0%,#091b38 100%);transition:width .3s ease;scrollbar-width:none;border-right:1px solid rgba(255,255,255,.06)}

.sidebar::-webkit-scrollbar{display:none}

.sidebar::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0,rgba(212,162,74,.16),transparent 55%)}



/* Brand: transparent emblem, no white background */

.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:26px;padding:20px 10px 24px;position:relative;margin-bottom:8px}

.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}

.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}

.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}

.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:none;border:0;padding:0}

.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}

.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:14px;font-weight:700;line-height:1.7}

.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}

.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;letter-spacing:.2px;white-space:nowrap;color:var(--gold-2);margin-top:12px;padding:5px 11px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}

.sidebar-label{padding:16px 26px 10px;color:#7389ad;font-size:10px;font-weight:700;letter-spacing:1.8px;position:relative}

.sidebar-menu{padding:0 14px;position:relative}

.nav-link{display:flex;align-items:center;gap:14px;min-height:44px;padding:8px 14px;margin-bottom:4px;border-radius:8px;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s}

.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}

.nav-link:hover{color:#fff;background:rgba(255,255,255,.06)}

.nav-link:hover i{color:#fff}

.nav-link.active{color:#fff;font-weight:600;background:rgba(255,255,255,.09)}

.nav-link.active i{color:var(--gold)}

.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold)}

.nav-link.logout-link{margin-top:16px;color:#f2b8bf}

.nav-link.logout-link i{color:#ef8a96}

.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}



/* Sidebar bottom quote (extra compact size) */

.sidebar-bottom{margin-top:auto;padding:12px 38px;position:relative}

.sidebar-quote{position:relative;overflow:hidden;padding:10px 10px 9px;border-radius:10px;text-align:center;

    background:linear-gradient(160deg,rgba(212,162,74,.18),rgba(255,255,255,.04) 55%);border:1px solid rgba(212,162,74,.35);

    box-shadow:0 4px 12px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.08)}

.sidebar-quote::before{content:"";position:absolute;left:18%;right:18%;top:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}

.quote-mark{position:absolute;right:8px;top:5px;font-size:16px;color:rgba(212,162,74,.18)}

.quote-top{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:5px;color:var(--gold-2);font-size:8px;font-weight:700;letter-spacing:1.2px}

.quote-top::before,.quote-top::after{content:"";width:10px;height:1px;background:rgba(212,162,74,.6)}

.sidebar-quote p{position:relative;font-size:10.5px;line-height:1.4;font-weight:600;color:#e8eefb}

.sidebar-quote p strong{display:block;margin-top:1px;font-size:12px;font-weight:800;background:linear-gradient(90deg,var(--gold-2),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}

.quote-line{width:22px;height:2px;margin:7px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}

.sidebar-status{display:flex;align-items:center;gap:8px;margin-top:14px;padding:12px 3px 0;border-top:1px solid rgba(255,255,255,.08);color:#8ea6c9;font-size:var(--fs-xs)}

.status-dot{width:7px;height:7px;border-radius:50%;background:#46d9a0;box-shadow:0 0 8px rgba(70,217,160,.7)}



/* ===== TOPBAR ===== */

.main{flex:1;min-width:0}

.topbar{min-height:66px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.82);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(14px)}

.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}

.menu-toggle{width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--navy);font-size:14px;transition:.2s}

.menu-toggle:hover{color:#fff;background:var(--navy)}

.topbar-greeting strong{display:block;font-size:var(--fs-lg);font-weight:700}

.topbar-greeting span{font-size:var(--fs-xs);color:var(--muted)}

.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0;padding:5px 8px 5px 5px;background:#fff;border:1px solid var(--line);border-radius:40px}

.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--navy);background:linear-gradient(135deg,var(--gold-2),var(--gold));font-size:14px}

.profile-details strong{display:block;font-size:var(--fs-sm);font-weight:700;line-height:1.3}

.profile-details span{font-size:var(--fs-xs);color:var(--muted)}

.profile-toggle{border:0;background:transparent;color:#5e7290;padding:8px;font-size:11px}

.profile-popover{position:absolute;right:0;top:56px;width:230px;padding:16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 34px rgba(15,37,71,.16);z-index:30}

.profile-popover strong{font-size:var(--fs-md)}

.profile-popover p{color:var(--muted);font-size:var(--fs-sm);margin-top:2px}

.profile-popover a{display:block;padding:9px 0;text-decoration:none;color:#27487a;font-size:var(--fs-sm)}

.profile-popover a:first-of-type{margin-top:8px;border-top:1px solid var(--line)}



/* ===== CONTENT ===== */

.content{max-width:1560px;margin:0 auto;padding:24px 28px 30px}



/* Hero */

.hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.5fr);gap:28px;align-items:center;padding:30px 32px;margin-bottom:24px;border-radius:22px;color:#fff;

    background:linear-gradient(120deg,#0b2146 0%,#16386a 60%,#1d4a85 100%);box-shadow:0 18px 40px rgba(13,37,71,.25)}

.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}

.hero::after{content:"";position:absolute;width:320px;height:320px;right:-110px;top:-160px;border-radius:50%;border:1px solid rgba(212,162,74,.4);box-shadow:0 0 0 40px rgba(212,162,74,.05)}

.hero>*{position:relative;z-index:1}

.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 13px;border-radius:20px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);font-size:var(--fs-xs);color:#e0e9f8}

.hero h1{font-size:var(--fs-2xl);font-weight:800;letter-spacing:-.4px;line-height:1.3;margin:14px 0 4px;background:linear-gradient(90deg,#fff,var(--gold-2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}

.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:420px}

.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}

.stat-card{padding:18px;border-radius:16px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(6px);transition:background .2s,transform .2s}

.stat-card:hover{background:rgba(255,255,255,.15);transform:translateY(-3px)}

.stat-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:linear-gradient(135deg,var(--gold-2),var(--gold));color:#1b1203;font-size:15px;margin-bottom:12px}

.stat-info h2{font-size:28px;line-height:1.1;font-weight:800}

.stat-info span{display:block;font-size:var(--fs-sm);font-weight:600;color:#e4ecf8;margin-top:6px}

.stat-info small{display:block;font-size:var(--fs-xs);color:#9fb3d3;line-height:1.4;margin-top:2px}



/* Cards */

.card{position:relative;overflow:hidden;padding:24px 26px;background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);margin-bottom:24px}

.card::before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}

.card-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px}

.card-heading-left{display:flex;align-items:center;gap:13px}

.section-icon{width:42px;height:42px;flex-shrink:0;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:15px;box-shadow:0 6px 14px rgba(13,37,71,.25)}

.card-heading h2{font-size:var(--fs-xl);font-weight:700;color:var(--ink);line-height:1.3}

.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px}



/* Forms */

.form-grid,.submit-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}

.field{min-width:0}

.field label{display:block;color:#33466a;font-size:var(--fs-sm);font-weight:600;margin-bottom:6px}

.input-wrap{height:42px;display:flex;align-items:center;gap:10px;padding:0 13px;min-width:0;background:#f6f8fc;border:1px solid #d8e0ec;border-radius:12px;transition:.2s}

.input-wrap:hover{border-color:#bccbe2}

.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,208,.12)}

.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}

.input-wrap:focus-within>i{color:var(--blue)}

.input-wrap input,.input-wrap select{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}

.input-wrap input::placeholder{color:#9aa8bf}

.input-wrap select{cursor:pointer}

.btn{min-height:42px;padding:0 20px;border:1px solid transparent;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;transition:.2s}

.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;box-shadow:0 6px 16px rgba(13,37,71,.25)}

.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(13,37,71,.3)}

.btn-primary i{color:var(--gold-2)}

.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}

.btn-outline:hover{background:var(--bg);border-color:#b8c6de}

.search-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:18px;border-top:1px dashed var(--line)}



/* Results */

.results-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px}

.results-subtitle{color:var(--muted);font-size:var(--fs-sm);margin-top:1px}

.count-chip{display:inline-flex;align-items:center;gap:7px;padding:6px 14px;background:var(--gold-soft);color:#8a6420;border:1px solid #f0dcb0;border-radius:20px;font-size:var(--fs-sm);font-weight:700}

.table-wrapper{overflow-x:auto;border:1px solid var(--line);border-radius:14px}

table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:var(--fs-sm)}

thead{background:linear-gradient(180deg,#eff3fa,#e6ecf6)}

th{text-align:left;padding:13px 14px;color:#2f4570;font-size:var(--fs-xs);font-weight:700;letter-spacing:.4px;text-transform:uppercase}

td{padding:12px 14px;color:#4b5d7c;border-top:1px solid #edf1f6;font-size:var(--fs-sm)}

tbody tr:nth-child(even){background:#fafbfe}

tbody tr{transition:background .15s}

tbody tr:hover{background:#f1f6ff}

td:first-child{color:#8a98b0;font-weight:700}

.file-name{display:flex;align-items:center;gap:10px;color:var(--ink);font-weight:600}

.file-icon{font-size:16px}

.file-icon.pdf{color:#e04f4f}.file-icon.doc{color:#2f6fe0}.file-icon.xls{color:#17946a}

.no-results{padding:36px 15px;text-align:center;color:var(--muted)}

.no-results i{font-size:32px;color:#a9b8d0;margin-bottom:10px}

.no-results p{font-size:var(--fs-md)}

.pagination{display:flex;align-items:center;gap:6px;margin-top:16px}

.page-btn{min-width:34px;height:34px;padding:0 10px;border-radius:10px;border:1px solid #d8e0ec;background:#fff;color:#4b5d7c;font-size:var(--fs-sm);font-weight:600}

.page-btn:hover{background:var(--bg)}

.page-btn.active{color:#fff;background:linear-gradient(135deg,var(--navy),var(--navy-2));border-color:var(--navy)}

.page-btn:disabled{opacity:.4;cursor:not-allowed}



/* Submit */

.submit-note{display:flex;align-items:flex-start;gap:10px;margin-top:18px;padding:13px 15px;border-radius:12px;border-left:3px solid var(--gold);background:var(--gold-soft);color:#6b5326;font-size:var(--fs-sm);line-height:1.7}

.submit-note i{color:var(--gold);font-size:14px;margin-top:3px}

.submit-bottom{display:flex;justify-content:flex-end;gap:14px;flex-wrap:wrap;margin-top:18px}

.submit-actions{display:flex;gap:10px;flex-wrap:wrap}



/* Footer */

.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:4px 4px 0;color:#8796ae;font-size:var(--fs-xs)}



/* Desktop collapse */

@media (min-width:701px){

    .app.sidebar-collapsed .sidebar{width:0}

    .app.sidebar-collapsed .sidebar>*{visibility:hidden}

}



/* Tablet */

@media (max-width:1150px){

    .sidebar{width:248px}

    .content{padding:20px 18px 28px}

    .topbar{padding:10px 18px}

    .hero{grid-template-columns:1fr;gap:20px;padding:24px}

    .form-grid,.submit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}

    table{min-width:1150px}

}



/* Mobile */

.sidebar-overlay{display:none}

@media (max-width:700px){

    .sidebar{position:fixed;left:-290px;top:0;bottom:0;width:268px;height:auto;min-height:100%;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}

    .sidebar.open{left:0}

    .sidebar-overlay.show{display:block;position:fixed;inset:0;background:rgba(7,27,55,.55);z-index:15;backdrop-filter:blur(2px)}

    .topbar{min-height:58px;padding:8px 12px}

    .topbar-left{gap:10px}

    .menu-toggle{width:36px;height:36px}

    .topbar-greeting strong{font-size:var(--fs-sm)}

    .topbar-greeting span{font-size:10px}

    .profile{gap:6px}

    .profile-avatar{width:32px;height:32px}

    .profile-details strong{font-size:var(--fs-xs);max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

    .profile-details span{font-size:10px}

    .profile-toggle{padding:4px}

    .profile-popover{width:210px;top:50px}

    .content{padding:16px 12px 24px}

    .hero{padding:20px 16px;border-radius:16px;margin-bottom:16px}

    .hero h1{font-size:21px}

    .hero-stats{grid-template-columns:1fr;gap:10px}

    .stat-card{display:flex;align-items:center;gap:12px;padding:12px 14px}

    .stat-icon{margin-bottom:0}

    .stat-info h2{font-size:20px}

    .stat-info span{margin-top:0}

    .card{padding:18px 13px;border-radius:16px;margin-bottom:16px}

    .card-heading{margin-bottom:16px}

    .card-heading h2{font-size:var(--fs-lg)}

    .section-icon{width:36px;height:36px}

    .form-grid,.submit-grid{grid-template-columns:1fr;gap:13px}

    .input-wrap{height:44px}

    .search-actions{gap:8px}

    .search-actions .btn{flex:1;padding:0 10px}

    .results-heading{align-items:flex-start}

    .results-heading .btn{min-height:36px;padding:0 10px;font-size:var(--fs-xs)}

    .submit-actions{width:100%;flex-direction:column}

    .submit-actions .btn{width:100%}

    .footer{justify-content:center;text-align:center;font-size:10px}

}



/* Print */

@media print{

    body{background:#fff}

    .sidebar,.topbar,.hero,.search-card,.submit-card,.footer,.print-btn,.pagination,.view-btn,.sidebar-overlay{display:none!important}

    .app,.main{display:block;width:100%}

    .content{padding:0}

    .results-card{border:none;box-shadow:none;padding:0}

    .card::before{display:none}

    .table-wrapper{overflow:visible;border:none}

    table{min-width:0;white-space:normal;font-size:10px}

    th,td{padding:7px;color:#000}

}



/* ===== Process additions ===== */

.dept-pill{display:inline-block;padding:3px 11px;border-radius:20px;background:#e9effa;color:#2f4570;font-size:11px;font-weight:600}

.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:var(--gold-soft);border:1px solid #f0dcb0;color:#8a6420;font-size:10px;font-weight:700}

.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;margin-bottom:18px;font-size:var(--fs-sm);line-height:1.7}

.alert-success{background:#e5f7ef;color:#0f6b4b;border-left:3px solid #17946a}

.empty-note{padding:28px 15px;text-align:center;color:var(--muted);font-size:var(--fs-md)}

.print-only{display:none}

@media print{

    .submitted-card{display:none!important}

    .print-only{display:block}

    .print-only h2{font-size:16px;margin-bottom:2px}

    .print-only p{font-size:11px;margin-bottom:6px;color:#333}

    .results-heading,.count-chip,.results-card>div[style],.results-card .table-wrapper{display:none!important}

    .print-only .table-wrapper{display:block!important;border:none}

    .print-only table{min-width:0;white-space:normal;font-size:10px}

    .print-only th,.print-only td{border:1px solid #999;padding:5px 6px;color:#000}

    .dept-pill{background:none;padding:0}

}



/* ===== File upload ===== */

.upload-field{margin-top:18px}

.field label.dropzone{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:10px;margin-bottom:0;padding:28px 20px;border:1.5px dashed #b4c3dc;border-radius:16px;background:linear-gradient(180deg,#f8fafd,#f1f5fb);cursor:pointer;transition:.2s}

.field label.dropzone:hover,.field label.dropzone.dragover{border-color:var(--blue);background:#f0f6ff}

.dropzone:focus-within{border-color:var(--blue);box-shadow:0 0 0 4px rgba(37,99,208,.12)}

.dropzone input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}

.dropzone-icon{width:54px;height:54px;flex-shrink:0;display:grid;place-items:center;border-radius:16px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:20px;box-shadow:0 8px 18px rgba(13,37,71,.25)}

.dropzone-text strong{display:block;font-size:var(--fs-md);color:var(--ink)}

.dropzone-text strong span{color:var(--blue);text-decoration:underline}

.dropzone-text small{display:block;font-size:var(--fs-xs);color:var(--muted);margin-top:2px}

.file-chip{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #cfe3d9;border-radius:14px;background:#f2fbf6}

.file-chip .chip-icon{font-size:22px}

.file-chip .chip-info{flex:1;min-width:0}

.file-chip .chip-info strong{display:block;font-size:var(--fs-md);color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.file-chip .chip-info small{font-size:var(--fs-xs);color:#4d7a64}

.chip-remove{width:30px;height:30px;flex-shrink:0;border:1px solid #e3c9cd;border-radius:8px;background:#fff;color:#c93636;font-size:12px;transition:.2s}

.chip-remove:hover{background:#c93636;color:#fff;border-color:#c93636}

.attach-cell{display:inline-flex;align-items:center;gap:8px;color:var(--ink);font-weight:600}

@media (max-width:700px){.dropzone{padding:20px 12px}}



/* ===== Scrollable results ===== */

.results-scroll{max-height:520px;overflow-y:auto;overflow-x:auto;scrollbar-width:thin;scrollbar-color:#b4c3dc transparent}

.results-scroll::-webkit-scrollbar{width:8px;height:8px}

.results-scroll::-webkit-scrollbar-thumb{background:#b4c3dc;border-radius:8px}

.results-scroll thead th{position:sticky;top:0;z-index:2;background:#e9eff8;box-shadow:0 1px 0 var(--line)}

@media (max-width:700px){.results-scroll{max-height:440px}}

@media print{.results-scroll{max-height:none;overflow:visible}}



/* ===== Row print button ===== */

.row-print-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #d3dcea;background:#fff;color:#27487a;font-size:var(--fs-xs);font-weight:600;transition:.2s}

.row-print-btn i{color:var(--gold);font-size:11px}

.row-print-btn:hover{background:linear-gradient(135deg,var(--navy),var(--navy-2));border-color:var(--navy);color:#fff}

.row-print-btn:hover i{color:var(--gold-2)}

.row-view-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #cfe0fa;background:#eef4ff;color:var(--blue);font-size:var(--fs-xs);font-weight:600;transition:.2s}

.row-view-btn i{font-size:11px}

.row-view-btn:hover{background:var(--blue);border-color:var(--blue);color:#fff}



.row-delete-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #e3c9cd;background:#fff;color:#c93636;font-size:var(--fs-xs);font-weight:600;transition:.2s}

.row-delete-btn i{font-size:11px}

.row-delete-btn:hover{background:#c93636;border-color:#c93636;color:#fff}



/* ===== Document viewer ===== */

.doc-modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(7,27,55,.6);backdrop-filter:blur(3px)}

.doc-dialog{width:min(1000px,100%);height:min(90vh,840px);display:flex;flex-direction:column;overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(7,27,55,.4)}

.doc-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--line);position:relative}

.doc-head::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:3px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}

.doc-title{display:flex;align-items:center;gap:12px;min-width:0}

.doc-title h3{font-size:var(--fs-xl);font-weight:700;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}

.doc-title p{font-size:var(--fs-xs);color:var(--muted)}

.doc-close{width:36px;height:36px;flex-shrink:0;border:1px solid var(--line);border-radius:10px;background:#fff;color:#5e7290;transition:.2s}

.doc-close:hover{background:#c93636;border-color:#c93636;color:#fff}

.doc-meta{display:flex;flex-wrap:wrap;gap:8px;padding:12px 20px;background:#f8fafd;border-bottom:1px solid var(--line)}

.doc-meta span{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:#fff;border:1px solid var(--line);color:#33466a;font-size:var(--fs-xs);font-weight:600}

.doc-meta span i{color:var(--gold)}

.doc-body{flex:1;min-height:0;background:#e9eef6;display:flex;align-items:center;justify-content:center;overflow:auto}

.doc-body object,.doc-body iframe{width:100%;height:100%;border:0;background:#fff}

.doc-body img{max-width:100%;max-height:100%;object-fit:contain;display:block}

.doc-fallback{max-width:380px;padding:30px 20px;text-align:center;color:var(--muted)}

.doc-fallback i{font-size:40px;color:#a9b8d0;margin-bottom:12px}

.doc-fallback p{font-size:var(--fs-md);margin-bottom:4px}

.doc-foot{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px;padding:14px 20px;border-top:1px solid var(--line)}

.doc-foot a.btn{text-decoration:none}

@media (max-width:700px){

    .doc-modal{padding:0}

    .doc-dialog{height:100%;border-radius:0}

    .doc-foot .btn{flex:1;padding:0 10px}

}

@media print{.doc-modal{display:none!important}}



/* ===== UI POLISH ===== */

::selection{background:rgba(212,162,74,.35);color:var(--navy)}

html{scroll-behavior:smooth}

body{-webkit-font-smoothing:antialiased}



/* Entrance animation */

@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

@keyframes floatGlow{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,14px)}}

.hero,.card{animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}

.search-card{animation-delay:.08s}.results-card{animation-delay:.16s}.submit-card{animation-delay:.24s}.submitted-card{animation-delay:.32s}



/* Topbar */

.topbar{box-shadow:0 4px 18px rgba(16,37,71,.05)}

.topbar::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55) 50%,transparent)}

.profile{box-shadow:0 2px 8px rgba(16,37,71,.06);transition:box-shadow .2s,border-color .2s}

.profile:hover{border-color:#cdd8ea;box-shadow:0 6px 16px rgba(16,37,71,.1)}

.profile-avatar{box-shadow:0 0 0 3px rgba(212,162,74,.22)}



/* Sidebar nav */

.nav-link{transition:background .2s,color .2s,transform .2s}

.nav-link:hover{transform:translateX(4px)}

.nav-link.active{background:linear-gradient(90deg,rgba(212,162,74,.18),rgba(255,255,255,.05));box-shadow:inset 0 0 0 1px rgba(212,162,74,.18)}

.nav-link.active::before{box-shadow:0 0 12px rgba(212,162,74,.7)}



/* Hero */

.hero{background:linear-gradient(120deg,#0a1d3f 0%,#16386a 55%,#215596 100%)}

.hero::after{animation:floatGlow 9s ease-in-out infinite}

.hero h1{font-size:30px}

.hero-date{backdrop-filter:blur(6px)}

.stat-card{position:relative;overflow:hidden;background:linear-gradient(160deg,rgba(255,255,255,.14),rgba(255,255,255,.05))}

.stat-card::before{content:"";position:absolute;left:0;top:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2),transparent)}

.stat-icon{box-shadow:0 8px 18px rgba(212,162,74,.35)}

.stat-info h2{letter-spacing:-.5px;text-shadow:0 2px 12px rgba(0,0,0,.2)}



/* Cards */

.card{transition:box-shadow .3s,transform .3s}

.card:hover{box-shadow:0 2px 4px rgba(16,37,71,.05),0 18px 40px rgba(16,37,71,.1)}

.card-heading{padding-bottom:16px;border-bottom:1px solid var(--line);position:relative}

.card-heading::after{content:"";position:absolute;left:0;bottom:-1px;width:64px;height:2px;border-radius:2px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}

.results-heading .card-heading{border-bottom:0;padding-bottom:0}

.results-heading .card-heading::after{display:none}

.section-icon{box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}



/* Forms */

.field label{letter-spacing:.2px}

.input-wrap{background:#f7f9fd;box-shadow:inset 0 1px 2px rgba(16,37,71,.04)}

.input-wrap:focus-within{box-shadow:0 0 0 4px rgba(37,99,208,.12),0 6px 16px rgba(37,99,208,.08)}

.input-wrap select{appearance:none;-webkit-appearance:none;padding-right:20px;

    background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' fill='none' stroke='%237a8ca8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/></svg>") no-repeat right 2px center}



/* Buttons */

.btn{position:relative;overflow:hidden}

.btn-primary::after{content:"";position:absolute;top:0;left:-70%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-20deg);transition:left .6s}

.btn-primary:hover::after{left:130%}

.btn:active{transform:translateY(0) scale(.98)}

.btn-outline:hover{border-color:var(--gold);color:var(--navy)}



/* Count chip */

.count-chip{background:linear-gradient(135deg,#fff6df,#fbeac0);box-shadow:0 3px 10px rgba(212,162,74,.18)}



/* Tables */

.table-wrapper{box-shadow:inset 0 0 0 1px var(--line),0 4px 14px rgba(16,37,71,.04)}

thead{background:linear-gradient(180deg,#f1f5fb,#e4ebf6)}

th{border-bottom:2px solid #d5deed;color:#26406b}

.results-scroll thead th{background:#e9eff8}

tbody tr{position:relative}

tbody tr:hover{background:linear-gradient(90deg,#fff7e6,#f1f6ff 40%)}

tbody tr:hover td:first-child{box-shadow:inset 3px 0 0 var(--gold)}

td:first-child{color:var(--navy-2)}

#resultsTable td:nth-child(6){font-weight:700;color:var(--navy-2);letter-spacing:.3px}

#resultsTable td:nth-child(9){font-weight:600;color:#33466a}

.file-icon.doc{background:#eaf1fd;padding:6px;border-radius:8px;font-size:13px;width:28px;height:28px;display:inline-grid;place-items:center}



/* Department pills */

.dept-pill{border:1px solid transparent}

.dept-Institutions{background:#e8f0ff;color:#1f4fa8;border-color:#cfdffc}

.dept-Councils{background:#fdf3dc;color:#8a6420;border-color:#f0dcb0}

.dept-Accounts{background:#e3f6ee;color:#11704f;border-color:#c5ead9}



/* Row buttons */

.row-view-btn,.row-print-btn,.row-delete-btn{box-shadow:0 1px 2px rgba(16,37,71,.06)}

.row-view-btn:hover,.row-print-btn:hover,.row-delete-btn:hover{transform:translateY(-1px);box-shadow:0 6px 12px rgba(16,37,71,.14)}



/* Upload */

.field label.dropzone{background:repeating-linear-gradient(135deg,#f8fafd 0 12px,#f3f7fc 12px 24px)}

.field label.dropzone:hover .dropzone-icon{transform:translateY(-3px) scale(1.05)}

.dropzone-icon{transition:transform .25s}

.submit-note{background:linear-gradient(90deg,#fdf3dc,#fffaf0)}

.file-chip{box-shadow:0 4px 12px rgba(23,148,106,.1)}



/* Alerts */

.alert-success{box-shadow:0 4px 14px rgba(23,148,106,.12);border-radius:12px}



/* Viewer */

.doc-dialog{animation:fadeUp .3s ease both}



/* Scrollbars */

.content{scrollbar-width:thin}

::-webkit-scrollbar{width:10px;height:10px}

::-webkit-scrollbar-thumb{background:#c3cfe3;border-radius:10px;border:2px solid transparent;background-clip:content-box}



/* Footer */

.footer{margin-top:6px;padding-top:16px;border-top:1px solid var(--line)}



@media (max-width:700px){.hero h1{font-size:23px}.card-heading{padding-bottom:12px}}

@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}

@media print{.hero,.card{animation:none}.topbar::after,.card-heading::after{display:none}}



/* ===== CHANGE PASSWORD PAGE ===== */

.hero.hero-slim{grid-template-columns:1fr;padding:24px 32px}

.hero.hero-slim h1{margin-top:12px}

.pw-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:24px;align-items:start}

.pw-layout .card{margin-bottom:24px}

.pw-form{max-width:520px}

.pw-form .field{margin-bottom:18px}

.eye{border:0;background:transparent;color:#6b86a3;width:32px;height:32px;border-radius:8px;font-size:13px;flex-shrink:0;transition:.2s}

.eye:hover{color:var(--navy);background:rgba(11,33,70,.07)}

.input-wrap.invalid{border-color:#d9776c;background:#fff8f7}

.strength{margin-top:8px}

.strength-bar{height:5px;border-radius:5px;background:#e1e8f2;overflow:hidden}

.strength-bar span{display:block;height:100%;width:0;border-radius:5px;transition:width .3s,background .3s}

.strength-text{display:flex;justify-content:space-between;margin-top:5px;font-size:var(--fs-xs);color:var(--muted)}

.match-text{margin-top:6px;font-size:var(--fs-xs);min-height:16px;font-weight:600}

.match-text.good{color:#0f7650}.match-text.bad{color:#b3392c}

.alert-error{background:#fdecea;color:#8e271d;border-left:3px solid #d9534f}

.alert ul{margin:0 0 0 16px}

.pw-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;padding-top:18px;border-top:1px dashed var(--line)}

.tips{list-style:none;display:grid;gap:12px}

.tips li{display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border-radius:12px;background:#f7f9fd;border:1px solid var(--line);font-size:var(--fs-sm);color:#4b5d7c;line-height:1.6}

.tips li i{color:var(--gold);margin-top:3px;font-size:14px;width:16px;text-align:center}

.tips li strong{display:block;color:var(--ink);font-size:var(--fs-md)}

@media (max-width:1150px){.pw-layout{grid-template-columns:1fr}.pw-form{max-width:none}}

@media (max-width:700px){.hero.hero-slim{padding:18px 16px}.pw-actions .btn{width:100%}}

</style>

</head>



<body>



<div class="sidebar-overlay" id="sidebarOverlay"></div>



<div class="app" id="app">



    <!-- SIDEBAR -->

    <aside class="sidebar" id="sidebar">



        <div class="brand">

            <div class="logo-ring">

                <div class="logo-box">

                    <img src="logo.webp" alt="Council Logo" class="logo" onerror="this.parentElement.style.display='none'">

                </div>

            </div>

            <div class="brand-text">

                <div class="sinhala">දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</div>

                <div class="english">Southern Provincial Council Secretariat</div>

                <div class="system-name"><i class="fa-solid fa-box-archive"></i> Archives File Management System</div>

            </div>

        </div>



        <div class="sidebar-label">MAIN MENU</div>



        <nav class="sidebar-menu">

            <a href="user_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>

            <a href="change_password.php" class="nav-link active"><i class="fa-solid fa-shield-halved"></i><span>Change Password</span></a>

            <a href="index.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>

        </nav>



        <div class="sidebar-bottom">

            <div class="sidebar-quote">

                <i class="fa-solid fa-quote-right quote-mark"></i>

                <div class="quote-top"><span>OUR COMMITMENT</span></div>

                <p>Efficient Records for a <strong>Better Tomorrow</strong></p>

                <div class="quote-line"></div>

            </div>

        </div>



    </aside>



    <!-- MAIN -->

    <main class="main">



        <header class="topbar">

            <div class="topbar-left">

                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu" aria-expanded="true">

                    <i class="fa-solid fa-bars"></i>

                </button>

                <div class="topbar-greeting">

                    <strong>Welcome to Archive System</strong>

                    <span>Southern Provincial Council Secretariat</span>

                </div>

            </div>



            <div class="profile">

                <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>

                <div class="profile-details">

                    <strong><?= htmlspecialchars($loggedInName, ENT_QUOTES, 'UTF-8') ?></strong>

                    <span><?= htmlspecialchars($loggedInRole, ENT_QUOTES, 'UTF-8') ?></span>

                </div>

                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">

                    <i class="fa-solid fa-chevron-down"></i>

                </button>



                <div class="profile-popover" id="profilePopover" hidden>

                    <strong><?= htmlspecialchars($loggedInName, ENT_QUOTES, 'UTF-8') ?></strong>

                    <p><i class="fa-solid fa-user-tag"></i> <?= htmlspecialchars($loggedInRole, ENT_QUOTES, 'UTF-8') ?></p>

                    <a href="change_password.php"><i class="fa-solid fa-lock"></i> Change Password</a>

                    <a href="index.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>

                </div>

            </div>

        </header>



        <div class="content">



            <section class="hero hero-slim">

                <div>

                    <span class="hero-date"><i class="fa-solid fa-shield-halved"></i> Account Security</span>

                    <h1>User Change Password</h1>

                    <p>Update your user account password to keep your archive account secure.</p>

                </div>

            </section>



            <div class="pw-layout">



                <section class="card">

                    <div class="card-heading">

                        <div class="card-heading-left">

                            <div class="section-icon"><i class="fa-solid fa-key"></i></div>

                            <div>

                                <h2>Update Your Password</h2>

                                <p>Enter your current password, then choose a new one.</p>

                            </div>

                        </div>

                    </div>



                    <?php if ($success): ?>

                        <div class="alert alert-success"><i class="fa-solid fa-circle-check"></i>

                            <span>Your password has been changed successfully. Use the new password the next time you log in.</span></div>

                    <?php endif; ?>



                    <?php if ($errors): ?>

                        <div class="alert alert-error"><i class="fa-solid fa-circle-exclamation"></i>

                            <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>

                    <?php endif; ?>



                    <form method="post" action="change_password.php" id="cpForm" class="pw-form" autocomplete="off">

                        <?= csrf_field() ?>



                        <div class="field">

                            <label for="current_password">Current Password</label>

                            <div class="input-wrap" id="current_passwordWrap">

                                <i class="fa-solid fa-lock"></i>

                                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" autocomplete="current-password" required <?= $locked ? 'disabled' : '' ?>>

                                <button type="button" class="eye" data-target="current_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>

                            </div>

                        </div>



                        <div class="field">

                            <label for="password">New Password</label>

                            <div class="input-wrap" id="passwordWrap">

                                <i class="fa-solid fa-key"></i>

                                <input type="password" id="password" name="password" placeholder="Enter new password" autocomplete="new-password" required minlength="6" maxlength="72" <?= $locked ? 'disabled' : '' ?>>

                                <button type="button" class="eye" data-target="password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>

                            </div>

                            <div class="strength">

                                <div class="strength-bar"><span id="strengthFill"></span></div>

                                <div class="strength-text"><span id="strengthLabel">Password strength</span><span>Minimum 6 characters</span></div>

                            </div>

                        </div>



                        <div class="field">

                            <label for="confirm_password">Confirm New Password</label>

                            <div class="input-wrap" id="confirm_passwordWrap">

                                <i class="fa-solid fa-key"></i>

                                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required <?= $locked ? 'disabled' : '' ?>>

                                <button type="button" class="eye" data-target="confirm_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>

                            </div>

                            <div class="match-text" id="matchText"></div>

                        </div>



                        <div class="pw-actions">

                            <button type="reset" class="btn btn-outline" id="cpReset"><i class="fa-solid fa-rotate-left"></i> Clear</button>

                            <button type="submit" class="btn btn-primary" <?= $locked ? 'disabled' : '' ?>><i class="fa-solid fa-floppy-disk"></i> Change Password</button>

                        </div>

                    </form>

                </section>



                <section class="card">

                    <div class="card-heading">

                        <div class="card-heading-left">

                            <div class="section-icon"><i class="fa-solid fa-lightbulb"></i></div>

                            <div>

                                <h2>Password Tips</h2>

                                <p>Keep your account safe.</p>

                            </div>

                        </div>

                    </div>

                    <ul class="tips">

                        <li><i class="fa-solid fa-ruler"></i><div><strong>Use a longer password</strong>Use at least 10 characters when possible.</div></li>

                        <li><i class="fa-solid fa-font"></i><div><strong>Mix characters</strong>Combine upper and lower case letters, numbers and symbols.</div></li>

                        <li><i class="fa-solid fa-user-lock"></i><div><strong>Keep it private</strong>Do not share your password with anyone, including colleagues.</div></li>

                        <li><i class="fa-solid fa-rotate"></i><div><strong>Use a new password</strong>Your new password must be different from the current one.</div></li>

                    </ul>

                </section>



            </div>



            <footer class="footer">

                <span>© <span id="footerYear">2026</span> Southern Provincial Council. All Rights Reserved.</span>

                <span>Archives File Management System</span>

            </footer>



        </div>

    </main>

</div>



<script>

/* ===== SIDEBAR ===== */

const app = document.getElementById("app");

const sidebar = document.getElementById("sidebar");

const menuToggle = document.getElementById("menuToggle");

const sidebarOverlay = document.getElementById("sidebarOverlay");



function toggleSidebar() {

    if (window.innerWidth <= 700) {

        const isOpen = sidebar.classList.toggle("open");

        sidebarOverlay.classList.toggle("show", isOpen);

        document.body.style.overflow = isOpen ? "hidden" : "";

        menuToggle.setAttribute("aria-expanded", String(isOpen));

    } else {

        const isCollapsed = app.classList.toggle("sidebar-collapsed");

        menuToggle.setAttribute("aria-expanded", String(!isCollapsed));

    }

}



function closeSidebar() {

    sidebar.classList.remove("open");

    sidebarOverlay.classList.remove("show");

    document.body.style.overflow = "";



    if (window.innerWidth <= 700) {

        menuToggle.setAttribute("aria-expanded", "false");

    }

}



menuToggle.addEventListener("click", toggleSidebar);

sidebarOverlay.addEventListener("click", closeSidebar);



document.addEventListener("keydown", function(e) {

    if (e.key === "Escape") {

        closeSidebar();

        closeProfilePopover();

    }

});



window.addEventListener("resize", function() {

    if (window.innerWidth > 700) {

        closeSidebar();

    } else {

        app.classList.remove("sidebar-collapsed");

    }

});



document.querySelectorAll(".sidebar .nav-link").forEach(link => {

    link.addEventListener("click", function() {

        if (window.innerWidth <= 700) closeSidebar();

    });

});



/* ===== PROFILE DROPDOWN ===== */

const profileToggle = document.getElementById("profileToggle");

const profilePopover = document.getElementById("profilePopover");



function closeProfilePopover() {

    profilePopover.hidden = true;

    profileToggle.setAttribute("aria-expanded", "false");

}



profileToggle.addEventListener("click", function(e) {

    e.stopPropagation();

    profilePopover.hidden = !profilePopover.hidden;

    profileToggle.setAttribute("aria-expanded", String(!profilePopover.hidden));

});



profilePopover.addEventListener("click", e => e.stopPropagation());

document.addEventListener("click", closeProfilePopover);





/* ===== SHOW / HIDE PASSWORD ===== */

document.querySelectorAll(".eye").forEach(function (btn) {

    btn.addEventListener("click", function () {

        var input = document.getElementById(btn.dataset.target);

        var show = input.type === "password";

        input.type = show ? "text" : "password";

        btn.innerHTML = show ? '<i class="fa-regular fa-eye-slash"></i>' : '<i class="fa-regular fa-eye"></i>';

        btn.setAttribute("aria-label", show ? "Hide password" : "Show password");

    });

});



/* ===== STRENGTH METER + MATCH CHECK ===== */

(function () {

    var pw = document.getElementById("password");

    var cf = document.getElementById("confirm_password");

    var fill = document.getElementById("strengthFill");

    var label = document.getElementById("strengthLabel");

    var matchText = document.getElementById("matchText");

    var cfWrap = document.getElementById("confirm_passwordWrap");



    function score(v) {

        var s = 0;

        if (v.length >= 6) s++;

        if (v.length >= 10) s++;

        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;

        if (/\d/.test(v)) s++;

        if (/[^A-Za-z0-9]/.test(v)) s++;

        return s;

    }



    function updateStrength() {

        var v = pw.value;

        if (!v) { fill.style.width = "0"; label.textContent = "Password strength"; return; }

        var levels = [

            {w: "20%", c: "#d9534f", t: "Very weak"},

            {w: "40%", c: "#e8833a", t: "Weak"},

            {w: "60%", c: "#e0b72f", t: "Fair"},

            {w: "80%", c: "#6cb544", t: "Good"},

            {w: "100%", c: "#1b9a6c", t: "Strong"}

        ];

        var l = levels[Math.min(Math.max(score(v) - 1, 0), 4)];

        fill.style.width = l.w; fill.style.background = l.c; label.textContent = l.t;

    }



    function updateMatch() {

        if (!cf.value) { matchText.textContent = ""; matchText.className = "match-text"; cfWrap.classList.remove("invalid"); return; }

        var ok = pw.value === cf.value;

        matchText.textContent = ok ? "Passwords match" : "Passwords do not match";

        matchText.className = "match-text " + (ok ? "good" : "bad");

        cfWrap.classList.toggle("invalid", !ok);

    }



    pw.addEventListener("input", function () { updateStrength(); updateMatch(); });

    cf.addEventListener("input", updateMatch);



    document.getElementById("cpReset").addEventListener("click", function () {

        setTimeout(function () { updateStrength(); updateMatch(); }, 0);

    });



    document.getElementById("cpForm").addEventListener("submit", function (e) {

        if (pw.value !== cf.value) { e.preventDefault(); updateMatch(); cf.focus(); }

    });

})();

</script>



</body>

</html>
