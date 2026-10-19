<?php
require __DIR__ . '/config.php';

/*
 * Change Password (administrator)
 *  - works exactly like the user "Change Password" page: the page always opens
 *  - the administrator's current password must be correct
 *  - new password must be different from the current one
 *  - after 5 wrong current-password attempts the form is locked for 5 minutes
 *  - only an active account with role = 'admin' can be changed from this page
 */
if (session_status() === PHP_SESSION_NONE) session_start();

/* Find the logged-in account's id. Tries the common session keys used by login pages. */
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
        $q = $pdo->prepare('SELECT id FROM users WHERE username = ? AND role = "admin" AND status = "active"');
        $q->execute([$sessName]);
        $userId = (int)$q->fetchColumn();
    }
}

/* Name shown in the top bar */
$adminName = 'System Administrator';
$sessionOk = false;
if ($userId > 0) {
    $q = $pdo->prepare('SELECT * FROM users WHERE id = ? AND role = "admin" AND status = "active"');
    $q->execute([$userId]);
    if ($acc = $q->fetch()) {
        $sessionOk = true;
        $adminName = $acc['full_name'] ?? $acc['name'] ?? $acc['username'] ?? $adminName;
    }
}
/* The page always opens. If the administrator cannot be identified, saving shows a message instead. */

const ADM_MAX_TRIES = 5;
const ADM_LOCK_SECS = 300;

$errors  = [];
$success = false;

$lockedUntil = (int)($_SESSION['admin_cp_locked_until'] ?? 0);
if ($lockedUntil && $lockedUntil <= time()) {
    unset($_SESSION['admin_cp_locked_until'], $_SESSION['admin_cp_tries']);
    $lockedUntil = 0;
}
$locked = $lockedUntil > time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    } elseif (!$sessionOk) {
        $errors[] = 'Your administrator account could not be identified. Please log in as the administrator and try again.';
    } elseif ($locked) {
        $mins = (int)ceil(($lockedUntil - time()) / 60);
        $errors[] = "Too many incorrect attempts. Please try again in $mins minute(s).";
    } else {
        $current = $_POST['current_password'] ?? '';
        $p1      = $_POST['password'] ?? '';
        $p2      = $_POST['confirm_password'] ?? '';

        $st = $pdo->prepare('SELECT password_hash FROM users WHERE id = ? AND role = "admin" AND status = "active"');
        $st->execute([$userId]);
        $row = $st->fetch();

        if (!$row) { $errors[] = 'Your administrator account could not be found or is not active.'; }

        $currentOk = $row && password_verify($current, $row['password_hash']);

        if ($row && !$currentOk) {
            $_SESSION['admin_cp_tries'] = (int)($_SESSION['admin_cp_tries'] ?? 0) + 1;
            if ($_SESSION['admin_cp_tries'] >= ADM_MAX_TRIES) {
                $_SESSION['admin_cp_locked_until'] = time() + ADM_LOCK_SECS;
                $locked = true;
                $errors[] = 'Too many incorrect attempts. The form is locked for 5 minutes.';
            } else {
                $left = ADM_MAX_TRIES - $_SESSION['admin_cp_tries'];
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
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = "admin" AND status = "active"')
                ->execute([password_hash($p1, PASSWORD_DEFAULT), $userId]);
            unset($_SESSION['admin_cp_tries'], $_SESSION['admin_cp_locked_until']);
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
:root{--ink:#14233d;--navy:#0b2146;--navy-2:#16386a;--gold:#d4a24a;--gold-2:#f0cf8a;--bg:#eef2f8;--line:#e1e7f1;--muted:#6a7890;--blue:#2563d0;
--shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);--fs-xs:11px;--fs-sm:12px;--fs-md:13px;--fs-lg:14px;--fs-xl:17px}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;
background:radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%),var(--bg)}
button,input{font:inherit}button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
::selection{background:rgba(212,162,74,.35);color:var(--navy)}
.app{display:flex;min-height:100vh}

/* Sidebar */
.sidebar{width:272px;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;overflow-x:hidden;overflow-y:auto;color:#fff;background:linear-gradient(180deg,#0c2347,#091b38);transition:width .3s ease;scrollbar-width:none;border-right:1px solid rgba(255,255,255,.06)}
.sidebar::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0,rgba(212,162,74,.16),transparent 55%)}
.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;padding:14px 10px 16px;position:relative;margin-bottom:2px}
.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}
.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}
.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}
.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}
.brand-text .sinhala{font-size:14px;font-weight:700;line-height:1.7}
.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}
.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;white-space:nowrap;color:var(--gold-2);margin-top:12px;padding:5px 11px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}
.sidebar-label{padding:10px 26px 6px;color:#7389ad;font-size:10px;font-weight:700;letter-spacing:1.8px;position:relative}
.sidebar-menu{padding:0 14px;position:relative}
.nav-link{display:flex;align-items:center;gap:14px;min-height:36px;padding:4px 14px;border-radius:8px;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s,transform .2s}
.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06);transform:translateX(4px)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;font-weight:600;background:linear-gradient(90deg,rgba(212,162,74,.18),rgba(255,255,255,.05));box-shadow:inset 0 0 0 1px rgba(212,162,74,.18)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold);box-shadow:0 0 12px rgba(212,162,74,.7)}
.nav-link.logout-link{color:#f2b8bf}
.nav-link.logout-link i{color:#ef8a96}
.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}
.sidebar-bottom{margin-top:auto;padding:12px 38px;position:relative}
.sidebar-quote{position:relative;overflow:hidden;padding:10px 10px 9px;border-radius:10px;text-align:center;background:linear-gradient(160deg,rgba(212,162,74,.18),rgba(255,255,255,.04) 55%);border:1px solid rgba(212,162,74,.35);box-shadow:0 4px 12px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.08)}
.sidebar-quote::before{content:"";position:absolute;left:18%;right:18%;top:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.quote-mark{position:absolute;right:8px;top:5px;font-size:16px;color:rgba(212,162,74,.18)}
.quote-top{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:5px;color:var(--gold-2);font-size:8px;font-weight:700;letter-spacing:1.2px}
.quote-top::before,.quote-top::after{content:"";width:10px;height:1px;background:rgba(212,162,74,.6)}
.sidebar-quote p{font-size:10.5px;line-height:1.4;font-weight:600;color:#e8eefb}
.sidebar-quote p strong{display:block;margin-top:1px;font-size:12px;font-weight:800;background:linear-gradient(90deg,var(--gold-2),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.quote-line{width:22px;height:2px;margin:7px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}

/* Topbar */
.main{flex:1;min-width:0}
.topbar{min-height:66px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.82);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(14px);box-shadow:0 4px 18px rgba(16,37,71,.05)}
.topbar::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55) 50%,transparent)}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.menu-toggle{width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--navy);font-size:14px;transition:.2s}
.menu-toggle:hover{color:#fff;background:var(--navy)}
.topbar-greeting strong{display:block;font-size:var(--fs-lg);font-weight:700}
.topbar-greeting span{font-size:var(--fs-xs);color:var(--muted)}
.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0;padding:5px 8px 5px 5px;background:#fff;border:1px solid var(--line);border-radius:40px;box-shadow:0 2px 8px rgba(16,37,71,.06)}
.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--navy);background:linear-gradient(135deg,var(--gold-2),var(--gold));font-size:14px;box-shadow:0 0 0 3px rgba(212,162,74,.22)}
.profile-details strong{display:block;font-size:var(--fs-sm);font-weight:700;line-height:1.3}
.profile-details span{font-size:var(--fs-xs);color:var(--muted)}
.profile-toggle{border:0;background:transparent;color:#5e7290;padding:8px;font-size:11px}
.profile-popover{position:absolute;right:0;top:56px;width:230px;padding:16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 34px rgba(15,37,71,.16);z-index:30}
.profile-popover strong{font-size:var(--fs-md)}
.profile-popover p{color:var(--muted);font-size:var(--fs-sm);margin-top:2px}
.profile-popover a{display:block;padding:9px 0;text-decoration:none;color:#27487a;font-size:var(--fs-sm)}
.profile-popover a:first-of-type{margin-top:8px;border-top:1px solid var(--line)}

/* Content + hero */
.content{max-width:1560px;margin:0 auto;padding:24px 28px 30px}
.hero{position:relative;overflow:hidden;padding:24px 32px;margin-bottom:24px;border-radius:22px;color:#fff;background:linear-gradient(120deg,#0a1d3f,#16386a 55%,#215596);box-shadow:0 18px 40px rgba(13,37,71,.25);animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}
.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}
.hero::after{content:"";position:absolute;width:320px;height:320px;right:-110px;top:-160px;border-radius:50%;border:1px solid rgba(212,162,74,.4);box-shadow:0 0 0 40px rgba(212,162,74,.05)}
.hero>*{position:relative;z-index:1}
.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 13px;border-radius:20px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);font-size:var(--fs-xs);color:#e0e9f8}
.hero h1{font-size:30px;font-weight:800;letter-spacing:-.4px;line-height:1.3;margin:12px 0 4px;background:linear-gradient(90deg,#fff,var(--gold-2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:480px}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}

/* Cards */
.pw-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:24px;align-items:start}
.card{position:relative;overflow:hidden;padding:24px 26px;background:#fff;border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);margin-bottom:24px}
.card::before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.card-heading{display:flex;align-items:center;gap:13px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px solid var(--line);position:relative}
.card-heading::after{content:"";position:absolute;left:0;bottom:-1px;width:64px;height:2px;border-radius:2px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}
.card-heading-left{display:flex;align-items:center;gap:13px}
.section-icon{width:42px;height:42px;flex-shrink:0;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:15px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.card-heading h2{font-size:var(--fs-xl);font-weight:700;line-height:1.3}
.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px}

/* Form */
.pw-form{max-width:520px}
.field{margin-bottom:18px;min-width:0}
.field label{display:block;color:#33466a;font-size:var(--fs-sm);font-weight:600;margin-bottom:6px;letter-spacing:.2px}
.input-wrap{height:42px;display:flex;align-items:center;gap:10px;padding:0 6px 0 13px;min-width:0;background:#f7f9fd;border:1px solid #d8e0ec;border-radius:12px;transition:.2s;box-shadow:inset 0 1px 2px rgba(16,37,71,.04)}
.input-wrap:hover{border-color:#bccbe2}
.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,208,.12),0 6px 16px rgba(37,99,208,.08)}
.input-wrap.invalid{border-color:#d9776c;background:#fff8f7}
.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}
.input-wrap:focus-within>i{color:var(--blue)}
.input-wrap input{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}
.input-wrap input::placeholder{color:#9aa8bf}
.input-wrap input:disabled{cursor:not-allowed}
.eye{border:0;background:transparent;color:#6b86a3;width:32px;height:32px;border-radius:8px;font-size:13px;flex-shrink:0;transition:.2s}
.eye:hover{color:var(--navy);background:rgba(11,33,70,.07)}
.strength{margin-top:8px}
.strength-bar{height:5px;border-radius:5px;background:#e1e8f2;overflow:hidden}
.strength-bar span{display:block;height:100%;width:0;border-radius:5px;transition:width .3s,background .3s}
.strength-text{display:flex;justify-content:space-between;margin-top:5px;font-size:var(--fs-xs);color:var(--muted)}
.match-text{margin-top:6px;font-size:var(--fs-xs);min-height:16px;font-weight:600}
.match-text.good{color:#0f7650}.match-text.bad{color:#b3392c}
.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;margin-bottom:18px;font-size:var(--fs-sm);line-height:1.7}
.alert-success{background:#e5f7ef;color:#0f6b4b;border-left:3px solid #17946a;box-shadow:0 4px 14px rgba(23,148,106,.12)}
.alert-error{background:#fdecea;color:#8e271d;border-left:3px solid #d9534f}
.alert ul{margin:0 0 0 16px}
.pw-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;padding-top:18px;border-top:1px dashed var(--line)}
.btn{position:relative;overflow:hidden;min-height:42px;padding:0 20px;border:1px solid transparent;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;text-decoration:none;transition:.2s}
.btn:active{transform:scale(.98)}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;box-shadow:0 6px 16px rgba(13,37,71,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(13,37,71,.3)}
.btn-primary i{color:var(--gold-2)}
.btn-primary::after{content:"";position:absolute;top:0;left:-70%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-20deg);transition:left .6s}
.btn-primary:hover::after{left:130%}
.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}
.btn-outline:hover{background:var(--bg);border-color:var(--gold);color:var(--navy)}
.btn:disabled{opacity:.5;cursor:not-allowed;transform:none}

/* Tips */
.tips{list-style:none;display:grid;gap:12px}
.tips li{display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border-radius:12px;background:#f7f9fd;border:1px solid var(--line);font-size:var(--fs-sm);color:#4b5d7c;line-height:1.6}
.tips li i{color:var(--gold);margin-top:3px;font-size:14px;width:16px;text-align:center}
.tips li strong{display:block;color:var(--ink);font-size:var(--fs-md)}

.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px;padding:16px 4px 0;border-top:1px solid var(--line);color:#8796ae;font-size:var(--fs-xs)}
.sidebar-overlay{display:none}
::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-thumb{background:#c3cfe3;border-radius:10px;border:2px solid transparent;background-clip:content-box}

@media (min-width:701px){.app.sidebar-collapsed .sidebar{width:0}.app.sidebar-collapsed .sidebar>*{visibility:hidden}}
@media (max-width:1150px){.sidebar{width:248px}.content{padding:20px 18px 28px}.topbar{padding:10px 18px}.pw-layout{grid-template-columns:1fr}.pw-form{max-width:none}}
@media (max-width:700px){
.sidebar{position:fixed;left:-290px;top:0;bottom:0;width:268px;height:auto;min-height:100%;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}
.sidebar.open{left:0}
.sidebar-overlay.show{display:block;position:fixed;inset:0;background:rgba(7,27,55,.55);z-index:15;backdrop-filter:blur(2px)}
.topbar{min-height:58px;padding:8px 12px}
.topbar-left{gap:10px}
.menu-toggle{width:36px;height:36px}
.topbar-greeting strong{font-size:var(--fs-sm)}
.topbar-greeting span{font-size:10px}
.profile-details strong{font-size:var(--fs-xs);max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.profile-popover{width:210px;top:50px}
.content{padding:16px 12px 24px}
.hero{padding:18px 16px;border-radius:16px;margin-bottom:16px}
.hero h1{font-size:23px}
.card{padding:18px 13px;border-radius:16px;margin-bottom:16px}
.card-heading{margin-bottom:16px;padding-bottom:12px}
.card-heading h2{font-size:var(--fs-lg)}
.section-icon{width:36px;height:36px}
.input-wrap{height:44px}
.pw-actions .btn{width:100%}
.footer{justify-content:center;text-align:center;font-size:10px}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
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

        <div class="sidebar-label">ADMIN MENU</div>

        <nav class="sidebar-menu">
            <a href="admin_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="admin_change_password.php" class="nav-link active"><i class="fa-solid fa-key"></i><span>Change Password</span></a>
            <a href="index.php?logout=1" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
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
                <div class="profile-avatar"><i class="fa-solid fa-user-shield"></i></div>
                <div class="profile-details">
                    <strong><?= e($adminName) ?></strong>
                    <span>Admin Account</span>
                </div>
                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="profile-popover" id="profilePopover" hidden>
                    <strong><?= e($adminName) ?></strong>
                    <p><i class="fa-solid fa-user-tag"></i> Admin Account</p>
                    <a href="admin_change_password.php"><i class="fa-solid fa-lock"></i> Change Password</a>
                    <a href="index.php?logout=1" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">

            <section class="hero">
                <span class="hero-date"><i class="fa-solid fa-shield-halved"></i> Account Security</span>
                <h1>Admin Change Password</h1>
                <p>Update your administrator password to keep the archive system secure.</p>
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

                    <?php if ($locked && !$errors): ?>
                        <div class="alert alert-error"><i class="fa-solid fa-clock"></i>
                            <span>Too many incorrect attempts. Please wait 5 minutes.</span></div>
                    <?php endif; ?>

                    <form method="post" action="admin_change_password.php" id="cpForm" class="pw-form" autocomplete="off">
                        <?= csrf_field() ?>

                        <div class="field">
                            <label for="current_password">Current Password</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-lock"></i>
                                <input type="password" id="current_password" name="current_password" placeholder="Enter current password" autocomplete="current-password" required <?= $locked ? 'disabled' : '' ?>>
                                <button type="button" class="eye" data-target="current_password" aria-label="Show password"><i class="fa-regular fa-eye"></i></button>
                            </div>
                        </div>

                        <div class="field">
                            <label for="password">New Password</label>
                            <div class="input-wrap">
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
                            <a href="admin_dashboard.php" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back to Dashboard</a>
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
                                <p>Keep the administrator account safe.</p>
                            </div>
                        </div>
                    </div>
                    <ul class="tips">
                        <li><i class="fa-solid fa-ruler"></i><div><strong>Use a longer password</strong>Use at least 10 characters when possible.</div></li>
                        <li><i class="fa-solid fa-font"></i><div><strong>Mix characters</strong>Combine upper and lower case letters, numbers and symbols.</div></li>
                        <li><i class="fa-solid fa-user-lock"></i><div><strong>Keep it private</strong>Never share the administrator password with anyone.</div></li>
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
document.getElementById("footerYear").textContent = new Date().getFullYear();

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
    if (window.innerWidth <= 700) menuToggle.setAttribute("aria-expanded", "false");
}
menuToggle.addEventListener("click", toggleSidebar);
sidebarOverlay.addEventListener("click", closeSidebar);
document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") { closeSidebar(); closeProfilePopover(); }
});
window.addEventListener("resize", function () {
    if (window.innerWidth > 700) closeSidebar(); else app.classList.remove("sidebar-collapsed");
});

/* ===== PROFILE DROPDOWN ===== */
const profileToggle = document.getElementById("profileToggle");
const profilePopover = document.getElementById("profilePopover");
function closeProfilePopover() {
    profilePopover.hidden = true;
    profileToggle.setAttribute("aria-expanded", "false");
}
profileToggle.addEventListener("click", function (e) {
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
