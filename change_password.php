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

$nameSafe = htmlspecialchars($loggedInName, ENT_QUOTES, 'UTF-8');
$roleSafe = htmlspecialchars($loggedInRole, ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Change Password - Archives File Management System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&family=Libre+Baskerville:wght@400;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --ink:#14233d; --navy:#0b2146; --navy-2:#16386a; --gold:#d4a24a; --gold-2:#f0cf8a; --gold-soft:#fbf3e1;
    --bg:#eef2f8; --card:#fff; --line:#e1e7f1; --muted:#6a7890; --blue:#2563d0; --red:#c93636;
    --shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);
    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px;
    --serif:'Libre Baskerville',Georgia,'Noto Sans Sinhala',serif;
    --rule:#d9cfb8;
}
*{margin:0;padding:0;box-sizing:border-box}
html{scroll-behavior:smooth}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;-webkit-font-smoothing:antialiased;
    background-color:var(--bg);
    background-image:radial-gradient(rgba(22,56,106,.075) 1px,transparent 1.2px),radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%);
    background-size:22px 22px,auto,auto}
button,input,select{font:inherit}
button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
::selection{background:rgba(212,162,74,.35);color:var(--navy)}
.app{display:flex;min-height:100vh}

/* ===== SIDEBAR (with faint shelf lines) ===== */
.sidebar{width:272px;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;overflow-x:hidden;overflow-y:auto;color:#fff;
    background:linear-gradient(180deg,#0c2347 0%,#091b38 100%);transition:width .3s ease;scrollbar-width:none;border-right:1px solid rgba(255,255,255,.06)}
.sidebar::-webkit-scrollbar{display:none}
.sidebar::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0,rgba(212,162,74,.16),transparent 55%)}
.sidebar::after{content:"";position:absolute;inset:0;pointer-events:none;background:repeating-linear-gradient(0deg,transparent 0 55px,rgba(255,255,255,.028) 55px 56px)}
.sidebar>*{position:relative;z-index:1}
.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;padding:14px 10px 16px;margin-bottom:2px}
.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}
.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}
.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}
.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center}
.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}
.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:14px;font-weight:700;line-height:1.7}
.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}
.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;letter-spacing:.2px;white-space:nowrap;color:var(--gold-2);margin-top:12px;padding:5px 11px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}
.sidebar-label{padding:10px 26px 6px;color:#7389ad;font-size:11px;font-weight:700;letter-spacing:.6px}
.sidebar-menu{padding:0 14px}
.nav-link{display:flex;align-items:center;gap:14px;width:100%;min-height:36px;padding:4px 14px;border:0;border-radius:8px;background:transparent;text-align:left;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s,transform .2s}
.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06);transform:translateX(4px)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;font-weight:600;background:linear-gradient(90deg,rgba(212,162,74,.22),rgba(255,255,255,.04));box-shadow:inset 0 0 0 1px rgba(212,162,74,.18)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold);box-shadow:0 0 12px rgba(212,162,74,.7)}
.nav-link.logout-link{color:#f2b8bf}
.nav-link.logout-link i{color:#ef8a96}
.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}
.sidebar-bottom{margin-top:auto;padding:12px 38px}
.sidebar-quote{position:relative;overflow:hidden;padding:10px 10px 9px;border-radius:10px;text-align:center;background:linear-gradient(160deg,rgba(212,162,74,.18),rgba(255,255,255,.04) 55%);border:1px solid rgba(212,162,74,.35);box-shadow:0 4px 12px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.08)}
.sidebar-quote::before{content:"";position:absolute;left:18%;right:18%;top:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.quote-mark{position:absolute;right:8px;top:5px;font-size:16px;color:rgba(212,162,74,.18)}
.quote-top{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:5px;color:var(--gold-2);font-size:8px;font-weight:700;letter-spacing:1.2px}
.quote-top::before,.quote-top::after{content:"";width:10px;height:1px;background:rgba(212,162,74,.6)}
.sidebar-quote p{position:relative;font-size:10.5px;line-height:1.4;font-weight:600;color:#e8eefb}
.sidebar-quote p strong{display:block;margin-top:1px;padding-bottom:3px;margin-bottom:-3px;font-size:12px;font-weight:800;background:linear-gradient(90deg,var(--gold-2),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.quote-line{width:22px;height:2px;margin:7px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}

/* ===== TOPBAR ===== */
.main{flex:1;min-width:0}
.topbar{min-height:66px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.82);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(14px);box-shadow:0 4px 18px rgba(16,37,71,.05)}
.topbar::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55) 50%,transparent)}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.menu-toggle{width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--navy);font-size:14px;transition:.2s}
.menu-toggle:hover{color:#fff;background:var(--navy)}
.topbar-title{display:flex;align-items:center;gap:12px;min-width:0}
.title-icon{width:42px;height:42px;flex-shrink:0;display:grid;place-items:center;border-radius:13px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:17px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.title-text{min-width:0;padding-left:14px;border-left:2px solid var(--gold)}
.title-text h1{font-family:var(--serif);font-size:19px;font-weight:700;line-height:1.2;color:var(--navy)}
.title-text>span{display:block;font-size:var(--fs-xs);color:var(--muted);margin-top:2px}
.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0;padding:5px 8px 5px 5px;background:#fff;border:1px solid var(--line);border-radius:40px;box-shadow:0 2px 8px rgba(16,37,71,.06);transition:box-shadow .2s,border-color .2s}
.profile:hover{border-color:#cdd8ea;box-shadow:0 6px 16px rgba(16,37,71,.1)}
.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--navy);background:linear-gradient(135deg,var(--gold-2),var(--gold));font-size:14px;box-shadow:0 0 0 3px rgba(212,162,74,.22)}
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

/* Hero with archive-shelf watermark */
.hero{position:relative;overflow:hidden;display:grid;grid-template-columns:1fr;gap:18px;align-items:center;padding:22px 24px;margin-bottom:24px;border-radius:22px;color:#fff;
    background:linear-gradient(120deg,#0a1d3f 0%,#16386a 55%,#215596 100%);box-shadow:0 18px 40px rgba(13,37,71,.25);animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}
.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}
.hero::after{content:"";position:absolute;width:320px;height:320px;right:-110px;top:-160px;border-radius:50%;border:1px solid rgba(212,162,74,.4);box-shadow:0 0 0 40px rgba(212,162,74,.05);animation:floatGlow 9s ease-in-out infinite}
.hero>*{position:relative;z-index:1}
.hero>.hero-art{position:absolute;right:-8px;top:-10px;width:380px;max-width:42%;opacity:.14;z-index:0;pointer-events:none}
.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 13px;border-radius:20px;background:rgba(240,207,138,.14);border:1px solid rgba(240,207,138,.35);font-size:var(--fs-xs);color:#f6e6bd;backdrop-filter:blur(6px)}
.hero h1{font-family:var(--serif);font-size:28px;font-weight:700;line-height:1.35;margin:14px 0 4px;background:linear-gradient(90deg,#fff,var(--gold-2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:520px}

/* Cards: folder edge on the left */
.card{position:relative;overflow:hidden;padding:24px 26px;background:var(--card);border:1px solid var(--line);border-left:4px solid var(--navy-2);border-radius:20px;box-shadow:var(--shadow);margin-bottom:24px;transition:box-shadow .3s}
.card:hover{box-shadow:0 2px 4px rgba(16,37,71,.05),0 18px 40px rgba(16,37,71,.1)}
.card::before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.card.gold{border-left-color:var(--gold)}
.card-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px;padding-bottom:16px;border-bottom:1px dashed var(--rule);position:relative}
.card-heading::after{content:"";position:absolute;left:0;bottom:-1px;width:84px;height:2px;border-radius:2px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}
.card-heading-left{display:flex;align-items:center;gap:13px}
.section-icon{width:42px;height:42px;flex-shrink:0;border-radius:12px 12px 12px 4px;display:grid;place-items:center;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:15px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.card-heading h2{font-family:var(--serif);font-size:16px;font-weight:700;color:var(--ink);line-height:1.3}
.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px;max-width:62ch}

/* Forms */
.field{min-width:0}
.field label{display:block;color:var(--navy-2);font-size:var(--fs-sm);font-weight:700;margin-bottom:6px;letter-spacing:.2px}
.input-wrap{height:42px;display:flex;align-items:center;gap:10px;padding:0 13px;min-width:0;background:#fff;border:1px solid #d9e1ee;border-radius:12px;transition:.2s;box-shadow:inset 0 1px 2px rgba(16,37,71,.04)}
.input-wrap:hover{border-color:var(--gold)}
.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,208,.12),0 6px 16px rgba(37,99,208,.08)}
.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}
.input-wrap:focus-within>i{color:var(--blue)}
.input-wrap input{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}
.input-wrap input::placeholder{color:#9aa8bf}
.input-wrap input:disabled{cursor:not-allowed;color:#9aa8bf}
.input-wrap.invalid{border-color:#d9776c;background:#fff8f7}
.btn{position:relative;overflow:hidden;min-height:42px;padding:0 20px;border:1px solid transparent;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;transition:.2s}
.btn:active{transform:scale(.98)}
.btn:disabled{opacity:.5;cursor:not-allowed}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;box-shadow:0 6px 16px rgba(13,37,71,.25)}
.btn-primary:hover:not(:disabled){transform:translateY(-2px);box-shadow:0 10px 22px rgba(13,37,71,.3)}
.btn-primary i{color:var(--gold-2)}
.btn-primary::after{content:"";position:absolute;top:0;left:-70%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-20deg);transition:left .6s}
.btn-primary:hover::after{left:130%}
.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}
.btn-outline:hover{background:var(--bg);border-color:var(--gold);color:var(--navy)}

/* Alerts: pinned slips */
.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:6px;margin-bottom:18px;font-size:var(--fs-sm);line-height:1.7}
.alert-success{background:#e5f7ef;color:#0f6b4b;border-left:4px solid #17946a;box-shadow:0 4px 14px rgba(23,148,106,.12)}
.alert-error{background:#fdecea;color:#8e271d;border-left:4px solid #d9534f}
.alert ul{margin:0 0 0 16px}
.alert i{margin-top:3px}

/* ===== CHANGE PASSWORD ===== */
.pw-layout{display:grid;grid-template-columns:minmax(0,1.5fr) minmax(0,1fr);gap:24px;align-items:start}
.pw-layout .card{margin-bottom:24px}
.pw-form{max-width:520px}
.pw-form .field{margin-bottom:18px}
.eye{border:0;background:transparent;color:#6b86a3;width:32px;height:32px;border-radius:8px;font-size:13px;flex-shrink:0;transition:.2s}
.eye:hover{color:var(--navy);background:rgba(11,33,70,.07)}
.strength{margin-top:8px}
.strength-bar{height:5px;border-radius:5px;background:#e1e8f2;overflow:hidden}
.strength-bar span{display:block;height:100%;width:0;border-radius:5px;transition:width .3s,background .3s}
.strength-text{display:flex;justify-content:space-between;margin-top:5px;font-size:var(--fs-xs);color:var(--muted)}
.match-text{margin-top:6px;font-size:var(--fs-xs);min-height:16px;font-weight:600}
.match-text.good{color:#0f7650}.match-text.bad{color:#b3392c}
.pw-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px;padding-top:18px;border-top:1px dashed var(--rule)}
.tips{list-style:none;display:grid;gap:12px}
.tips li{display:flex;gap:12px;align-items:flex-start;padding:12px 14px;border-radius:6px;background:#fffdf7;border:1px dashed #e0d5b8;border-left:4px solid var(--gold);font-size:var(--fs-sm);color:#4b5d7c;line-height:1.6}
.tips li i{color:var(--gold);margin-top:3px;font-size:14px;width:16px;text-align:center}
.tips li strong{display:block;color:var(--ink);font-size:var(--fs-md)}

.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;margin-top:6px;padding:16px 4px 0;border-top:1px dashed var(--rule);color:#8796ae;font-size:var(--fs-xs)}
::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-thumb{background:#c3cfe3;border-radius:10px;border:2px solid transparent;background-clip:content-box}
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes floatGlow{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,14px)}}

/* Desktop collapse */
@media (min-width:701px){
    .app.sidebar-collapsed .sidebar{width:0}
    .app.sidebar-collapsed .sidebar>*{visibility:hidden}
}
@media (max-width:1150px){
    .sidebar{width:248px}
    .content{padding:20px 18px 28px}
    .topbar{padding:10px 18px}
    .hero{padding:20px}
    .hero>.hero-art{opacity:.1;max-width:60%}
    .pw-layout{grid-template-columns:1fr}
    .pw-form{max-width:none}
}
.sidebar-overlay{display:none}
@media (max-width:700px){
    .sidebar{position:fixed;left:-290px;top:0;bottom:0;width:268px;height:auto;min-height:100%;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}
    .sidebar.open{left:0}
    .sidebar-overlay.show{display:block;position:fixed;inset:0;background:rgba(7,27,55,.55);z-index:15;backdrop-filter:blur(2px)}
    .topbar{min-height:58px;padding:8px 12px}
    .topbar-left{gap:10px}
    .menu-toggle{width:36px;height:36px}
    .title-icon{width:34px;height:34px;font-size:14px;border-radius:10px}
    .title-text{padding-left:10px}
    .title-text h1{font-size:16px}
    .title-text>span{display:none}
    .profile-details strong{font-size:var(--fs-xs);max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .profile-popover{width:210px;top:50px}
    .content{padding:16px 12px 24px}
    .hero{padding:20px 16px;border-radius:16px;margin-bottom:16px}
    .hero>.hero-art{display:none}
    .hero h1{font-size:22px}
    .card{padding:18px 13px;border-radius:16px;margin-bottom:16px;border-left-width:3px}
    .card-heading{margin-bottom:16px;padding-bottom:12px}
    .card-heading h2{font-size:var(--fs-lg)}
    .section-icon{width:36px;height:36px}
    .input-wrap{height:44px}
    .pw-actions .btn{width:100%}
    .footer{justify-content:center;text-align:center;font-size:10px}
}
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

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-menu">
            <a href="user_dashboard.php" class="nav-link"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="change_password.php" class="nav-link active"><i class="fa-solid fa-key"></i><span>Change Password</span></a>
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
                <div class="topbar-title">
                    <div class="title-icon"><i class="fa-solid fa-user"></i></div>
                    <div class="title-text">
                        <h1>User</h1>
                        <span>Southern Provincial Council Secretariat</span>
                    </div>
                </div>
            </div>

            <div class="profile">
                <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="profile-details">
                    <strong><?= $nameSafe ?></strong>
                    <span><?= $roleSafe ?></span>
                </div>
                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="profile-popover" id="profilePopover" hidden>
                    <strong><?= $nameSafe ?></strong>
                    <p><i class="fa-solid fa-user-tag"></i> <?= $roleSafe ?></p>
                    <a href="change_password.php"><i class="fa-solid fa-lock"></i> Change Password</a>
                    <a href="index.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">

            <section class="hero">
                <svg class="hero-art" viewBox="0 0 360 200" aria-hidden="true" fill="none" stroke="#f0cf8a" stroke-width="2">
                    <path d="M10 10H350M10 100H350M10 190H350M10 10V190M350 10V190"/>
                    <rect x="24" y="44" width="46" height="56"/><rect x="76" y="34" width="46" height="66"/>
                    <rect x="128" y="52" width="46" height="48"/><rect x="190" y="40" width="46" height="60"/>
                    <rect x="242" y="48" width="46" height="52"/><rect x="294" y="36" width="40" height="64"/>
                    <rect x="24" y="134" width="56" height="56"/><rect x="88" y="124" width="46" height="66"/>
                    <rect x="142" y="140" width="46" height="50"/><rect x="204" y="128" width="56" height="62"/>
                    <rect x="268" y="138" width="66" height="52"/>
                    <path d="M34 62h26M86 54h26M138 68h26M200 58h26M252 66h26M34 152h36M98 144h26M152 156h26M214 148h36M278 154h46"/>
                </svg>
                <div>
                    <span class="hero-date"><i class="fa-solid fa-shield-halved"></i> Account Security</span>
                    <h1>User Change Password</h1>
                    <p>Update your user account password to keep your archive account secure.</p>
                </div>
            </section>

            <div class="pw-layout">

                <section class="card gold">
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

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") { closeSidebar(); closeProfilePopover(); }
});
window.addEventListener("resize", function() {
    if (window.innerWidth > 700) closeSidebar();
    else app.classList.remove("sidebar-collapsed");
});
document.querySelectorAll(".sidebar .nav-link").forEach(link => {
    link.addEventListener("click", function() { if (window.innerWidth <= 700) closeSidebar(); });
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