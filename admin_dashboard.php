<?php
/* =====================================================================
   admin_dashboard.php  -  පරිපාලක උපකරණ පුවරුව (Admin Dashboard)
   1) ලේඛනාගාර ලිපිගොනු සෙවීම (user dashboard එකේ ආකාරයටම) + ලිපිගොනු ඉවත් කිරීම (හේතුව + දිනය)
   2) ඉවත් කළ ලිපිගොනු ලැයිස්තුව
   3) පරිශීලක කළමනාකරණය (අනුමත / අක්‍රීය / සක්‍රීය / මකා දැමීම)
   Standalone file: config.php සහ admin_update.sql (ඇත්නම් database.sql) පමණක් අවශ්‍යයි.
   ===================================================================== */
require __DIR__ . '/config.php';
$user = require_role($pdo, 'admin');

const FILE_STATUSES = ['archived', 'pending', 'rejected'];

/* ------------------------------------------------------------------
   POST ACTIONS  (CSRF පරීක්ෂාව සහිතව)
------------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $act = $_POST['action'] ?? '';
    parse_str($_POST['back'] ?? '', $back);                       // පෙර තිබූ tab / search තත්ත්වයට ආපසු යාමට
    $backUrl = 'admin_dashboard.php' . ($back ? '?' . http_build_query($back) : '');

    if (!csrf_ok()) {
        flash_set('err', 'ආරක්ෂක පරීක්ෂාව අසාර්ථකයි. පිටුව නැවත පූරණය කර උත්සාහ කරන්න.');

    /* ---------- ලිපිගොනුවක් ඉවත් කිරීම (soft delete + හේතුව + දිනය) ---------- */
    } elseif ($act === 'delete_file') {
        $id     = (int)($_POST['file_id'] ?? 0);
        $reason = trim($_POST['delete_reason'] ?? '');
        $date   = trim($_POST['delete_date'] ?? '');
        if ($reason === '')                  flash_set('err', 'ලිපිගොනුව ඉවත් කිරීමට හේතුව ඇතුළත් කරන්න.');
        elseif (mb_strlen($reason) > 500)    flash_set('err', 'හේතුව අක්ෂර 500 කට වඩා දිගු විය නොහැක.');
        elseif (!valid_date($date))          flash_set('err', 'ඉවත් කළ දිනය නිවැරදිව ඇතුළත් කරන්න.');
        elseif ($date > date('Y-m-d'))       flash_set('err', 'අනාගත දිනයක් ඇතුළත් කළ නොහැක.');
        else {
            $u = $pdo->prepare("UPDATE archive_files
                                SET status='deleted', deleted_by=?, deleted_by_name=?, deleted_at=NOW(), delete_date=?, delete_reason=?
                                WHERE id=? AND status<>'deleted'");
            $u->execute([$user['id'], $user['full_name'], $date, $reason, $id]);
            if ($u->rowCount()) flash_set('ok', 'ලිපිගොනුව පද්ධතියෙන් ඉවත් කරන ලදී. එය "ඉවත් කළ ලිපිගොනු" අංශයෙන් බලාගත හැක.');
            else                flash_set('err', 'ලිපිගොනුව හමු නොවීය, නැතහොත් දැනටමත් ඉවත් කර ඇත.');
        }

    /* ---------- පරිශීලකයින් කළමනාකරණය ---------- */
    } elseif (in_array($act, ['approve', 'enable', 'disable', 'delete_user'], true)) {
        $uid = (int)($_POST['user_id'] ?? 0);
        if ($uid === (int)$user['id']) {
            flash_set('err', 'ඔබේම ගිණුම මෙතැනින් වෙනස් කිරීමට හෝ මකා දැමීමට නොහැක.');
        } elseif ($act === 'delete_user') {
            $chk = $pdo->prepare('SELECT full_name FROM users WHERE id = ?');
            $chk->execute([$uid]);
            if (!($target = $chk->fetch())) {
                flash_set('err', 'පරිශීලකයා හමු නොවීය.');
            } else {
                try {
                    $pdo->beginTransaction();
                    // ඔහු යොමු කළ / පරීක්ෂා කළ ලිපිගොනු ලේඛනාගාරයේම පවතී; පරිශීලක සම්බන්ධය පමණක් ඉවත් වේ
                    $pdo->prepare('UPDATE archive_files SET submitted_by = NULL WHERE submitted_by = ?')->execute([$uid]);
                    $pdo->prepare('UPDATE archive_files SET reviewed_by  = NULL WHERE reviewed_by  = ?')->execute([$uid]);
                    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                    $pdo->commit();
                    flash_set('ok', 'පරිශීලක "' . $target['full_name'] . '" පද්ධතියෙන් මකා දමන ලදී.');
                } catch (Throwable $ex) {
                    if ($pdo->inTransaction()) $pdo->rollBack();
                    flash_set('err', 'පරිශීලකයා මකා දැමීමට නොහැකි විය.');
                }
            }
        } else {
            $map = ['approve' => ['active', 'ගිණුම අනුමත කරන ලදී.'], 'enable' => ['active', 'ගිණුම සක්‍රීය කරන ලදී.'], 'disable' => ['disabled', 'ගිණුම අක්‍රීය කරන ලදී.']];
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$map[$act][0], $uid]);
            flash_set('ok', $map[$act][1]);
        }
    }
    header('Location: ' . $backUrl); exit;
}

/* ------------------------------------------------------------------
   HELPERS
------------------------------------------------------------------- */
function qs(array $over = [], array $drop = []): string {
    $q = $_GET;
    foreach ($drop as $d) unset($q[$d]);
    foreach ($over as $k => $v) $q[$k] = $v;
    return '?' . http_build_query($q);
}
function hl(string $text, string $q): string {                    // සෙවූ කොටස highlight කිරීම
    $safe = e($text);
    if ($q === '') return $safe;
    return preg_replace('/(' . preg_quote(e($q), '/') . ')/iu', '<mark>$1</mark>', $safe) ?? $safe;
}
function pager(int $page, int $pages, string $param, string $tab): string {
    if ($pages <= 1) return '';
    $link = fn(int $p) => e(qs([$param => $p, 'tab' => $tab]));
    $h = '<div class="pager no-print">';
    $h .= $page > 1 ? '<a href="' . $link($page - 1) . '"><i class="fa-solid fa-chevron-left"></i></a>' : '<span class="dis"><i class="fa-solid fa-chevron-left"></i></span>';
    for ($p = 1; $p <= $pages; $p++) $h .= $p === $page ? '<span class="cur">' . $p . '</span>' : '<a href="' . $link($p) . '">' . $p . '</a>';
    $h .= $page < $pages ? '<a href="' . $link($page + 1) . '"><i class="fa-solid fa-chevron-right"></i></a>' : '<span class="dis"><i class="fa-solid fa-chevron-right"></i></span>';
    return $h . '</div>';
}
$deptClass   = ['ආයතන' => 'd1', 'සභා' => 'd2', 'ගිණුම්' => 'd3'];
$fileStatus  = ['pending' => ['බලාපොරොත්තුවෙන්', 'st-pending', 'fa-hourglass-half'], 'archived' => ['ලේඛනාගාරගත', 'st-ok', 'fa-circle-check'], 'rejected' => ['ප්‍රතික්ෂේප කළා', 'st-no', 'fa-circle-xmark'], 'deleted' => ['ඉවත් කළා', 'st-no', 'fa-trash']];
$userStatus  = ['pending' => ['අනුමැතිය බලාපොරොත්තුවෙන්', 'st-pending'], 'active' => ['සක්‍රීය', 'st-ok'], 'disabled' => ['අක්‍රීය', 'st-no']];
$today = date('Y-m-d');

/* ------------------------------------------------------------------
   1) ලිපිගොනු සෙවීම - FILTERS (user dashboard එකේ ආකාරයටම; අමතරව තත්ත්වය)
   තෝරා ඇති filters සියල්ලම එකවර ගැලපිය යුතුය (AND)
------------------------------------------------------------------- */
$filters = [
    'file_name'      => trim($_GET['file_name'] ?? ''),
    'department'     => in_array($_GET['department'] ?? '', DEPARTMENTS, true) ? $_GET['department'] : '',
    'subject_number' => trim($_GET['subject_number'] ?? ''),
    'archive_number' => trim($_GET['archive_number'] ?? ''),
    'status'         => in_array($_GET['status'] ?? '', FILE_STATUSES, true) ? $_GET['status'] : 'archived',
];
function build_where(array $f): array {
    $where = ['status = ?']; $p = [$f['status']];
    if ($f['file_name'] !== '')      { $where[] = 'file_name LIKE ?';      $p[] = '%' . $f['file_name'] . '%'; }
    if ($f['department'] !== '')     { $where[] = 'department = ?';        $p[] = $f['department']; }
    if ($f['subject_number'] !== '') { $where[] = 'subject_number LIKE ?'; $p[] = '%' . $f['subject_number'] . '%'; }
    if ($f['archive_number'] !== '') { $where[] = 'archive_number LIKE ?'; $p[] = '%' . $f['archive_number'] . '%'; }
    return [implode(' AND ', $where), $p];
}
[$whereSql, $whereParams] = build_where($filters);
$isFiltered = $filters['file_name'] !== '' || $filters['department'] !== '' || $filters['subject_number'] !== '' || $filters['archive_number'] !== '' || $filters['status'] !== 'archived';

/* ------------------------------------------------------------------
   PRINT VIEWS
------------------------------------------------------------------- */
if (isset($_GET['print'])) {
    $deletedPrint = $_GET['print'] === 'deleted';
    if ($deletedPrint) {
        $dq = trim($_GET['dq'] ?? '');
        $sql = "SELECT * FROM archive_files WHERE status='deleted'"; $par = [];
        if ($dq !== '') { $sql .= ' AND (file_name LIKE ? OR delete_reason LIKE ? OR archive_number LIKE ?)'; $par = ["%$dq%", "%$dq%", "%$dq%"]; }
        $st = $pdo->prepare($sql . ' ORDER BY deleted_at DESC'); $st->execute($par);
    } else {
        $st = $pdo->prepare("SELECT * FROM archive_files WHERE $whereSql ORDER BY submit_date DESC, id DESC"); $st->execute($whereParams);
    }
    $all = $st->fetchAll();
    $ft = [];
    if (!$deletedPrint) {
        if ($filters['file_name'] !== '')      $ft[] = 'නම: ' . $filters['file_name'];
        if ($filters['department'] !== '')     $ft[] = 'අංශය: ' . $filters['department'];
        if ($filters['subject_number'] !== '') $ft[] = 'විෂය අංකය: ' . $filters['subject_number'];
        if ($filters['archive_number'] !== '') $ft[] = 'ලේකනාගාර අංකය: ' . $filters['archive_number'];
        $ft[] = 'තත්ත්වය: ' . $fileStatus[$filters['status']][0];
    }
    ?>
<!DOCTYPE html><html lang="si"><head><meta charset="UTF-8"><title><?= $deletedPrint ? 'ඉවත් කළ ලිපිගොනු' : 'ලිපිගොනු ලැයිස්තුව' ?></title>
<link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<style>
body{font-family:'Noto Sans Sinhala','Iskoola Pota',Arial,sans-serif;margin:28px;color:#111}
.head{text-align:center;border-bottom:2px solid #0f2a5c;padding-bottom:10px;margin-bottom:12px}
.head h2{font-size:20px;color:#0f2a5c}.head p{font-size:13px;color:#444;margin-top:3px}
.meta{font-size:12.5px;color:#333;margin-bottom:10px;display:flex;justify-content:space-between;gap:12px;flex-wrap:wrap}
table{width:100%;border-collapse:collapse;font-size:12px}th,td{border:1px solid #777;padding:5px 7px;text-align:left;vertical-align:top}th{background:#e9eef7}
.foot{margin-top:18px;font-size:11.5px;color:#555;text-align:right}
@media print{@page{size:A4 landscape;margin:12mm}}
</style></head><body onload="window.print()">
<div class="head"><h2>දකුණු පළාත් සභා ලේකම් කාර්යාලය</h2><p><?= $deletedPrint ? 'පද්ධතියෙන් ඉවත් කළ ලිපිගොනු ලැයිස්තුව' : 'ලේකනාගාර ලිපිගොනු ලැයිස්තුව (පරිපාලක)' ?></p></div>
<div class="meta"><span><?= $deletedPrint ? 'සෙවුම: ' . ($dq !== '' ? e($dq) : 'සියල්ල') : 'සෙවුම: ' . e(implode(' | ', $ft)) ?></span>
<span>මුද්‍රණය කළ දිනය: <?= date('Y-m-d H:i') ?> &nbsp;|&nbsp; ගොනු <?= count($all) ?>ක්</span></div>
<table>
<?php if ($deletedPrint): ?>
<tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ඉවත් කළ දිනය</th><th>ඉවත් කිරීමට හේතුව</th><th>ඉවත් කළේ</th></tr>
<?php foreach ($all as $i => $r): ?>
<tr><td><?= $i + 1 ?></td><td><?= e($r['file_name']) ?></td><td><?= e($r['department']) ?></td><td><?= e($r['subject_number']) ?></td><td><?= e($r['archive_number']) ?></td><td><?= e($r['delete_date']) ?></td><td><?= e($r['delete_reason']) ?></td><td><?= e($r['deleted_by_name']) ?></td></tr>
<?php endforeach; ?>
<?php else: ?>
<tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ලිපිගොනු අංකය</th><th>ආරම්භක දිනය</th><th>අවසන් දිනය</th><th>පිටු</th></tr>
<?php foreach ($all as $i => $r): ?>
<tr><td><?= $i + 1 ?></td><td><?= e($r['file_name']) ?></td><td><?= e($r['department']) ?></td><td><?= e($r['subject_number']) ?></td><td><?= e($r['archive_number']) ?></td><td><?= e($r['file_number']) ?></td><td><?= e($r['start_date']) ?></td><td><?= e($r['end_date']) ?></td><td><?= (int)$r['page_count'] ?></td></tr>
<?php endforeach; ?>
<?php endif; ?>
</table>
<div class="foot">මුද්‍රණය කළේ: <?= e($user['full_name']) ?></div>
</body></html>
<?php
    exit;
}

/* ------------------------------------------------------------------
   DATA
------------------------------------------------------------------- */
$tab = in_array($_GET['tab'] ?? '', ['files', 'deleted', 'users'], true) ? $_GET['tab'] : 'files';
$perPage = 10;

// --- stats ---
$stats = [
    'users'    => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'pendingU' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn(),
    'archived' => (int)$pdo->query("SELECT COUNT(*) FROM archive_files WHERE status='archived'")->fetchColumn(),
    'deleted'  => (int)$pdo->query("SELECT COUNT(*) FROM archive_files WHERE status='deleted'")->fetchColumn(),
];

// --- files tab ---
$rows = []; $total = 0; $pages = 1; $page = 1; $offset = 0;
if ($tab === 'files') {
    $cnt = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE $whereSql"); $cnt->execute($whereParams);
    $total  = (int)$cnt->fetchColumn();
    $pages  = max(1, (int)ceil($total / $perPage));
    $page   = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
    $offset = ($page - 1) * $perPage;
    $st = $pdo->prepare("SELECT * FROM archive_files WHERE $whereSql ORDER BY submit_date DESC, id DESC LIMIT $perPage OFFSET $offset");
    $st->execute($whereParams);
    $rows = $st->fetchAll();
}

// --- deleted tab ---
$dq = trim($_GET['dq'] ?? '');
$dRows = []; $dTotal = 0; $dPages = 1; $dPage = 1; $dOffset = 0;
if ($tab === 'deleted') {
    $dw = "status = 'deleted'"; $dp = [];
    if ($dq !== '') { $dw .= ' AND (file_name LIKE ? OR delete_reason LIKE ? OR archive_number LIKE ?)'; $dp = ["%$dq%", "%$dq%", "%$dq%"]; }
    $c = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE $dw"); $c->execute($dp);
    $dTotal  = (int)$c->fetchColumn();
    $dPages  = max(1, (int)ceil($dTotal / $perPage));
    $dPage   = min(max(1, (int)($_GET['dpage'] ?? 1)), $dPages);
    $dOffset = ($dPage - 1) * $perPage;
    $st = $pdo->prepare("SELECT * FROM archive_files WHERE $dw ORDER BY deleted_at DESC LIMIT $perPage OFFSET $dOffset");
    $st->execute($dp);
    $dRows = $st->fetchAll();
}

// --- users tab ---
$uq = trim($_GET['uq'] ?? '');
$us = in_array($_GET['us'] ?? '', ['pending', 'active', 'disabled'], true) ? $_GET['us'] : '';
$ur = in_array($_GET['ur'] ?? '', ['user', 'admin', 'subject'], true) ? $_GET['ur'] : '';
$users = [];
if ($tab === 'users') {
    $uw = ['1=1']; $up = [];
    if ($uq !== '') { $uw[] = '(full_name LIKE ? OR username LIKE ? OR email LIKE ? OR nic LIKE ?)'; array_push($up, "%$uq%", "%$uq%", "%$uq%", "%$uq%"); }
    if ($us !== '') { $uw[] = 'status = ?'; $up[] = $us; }
    if ($ur !== '') { $uw[] = 'role = ?';   $up[] = $ur; }
    $st = $pdo->prepare('SELECT * FROM users WHERE ' . implode(' AND ', $uw) . " ORDER BY FIELD(status,'pending','active','disabled'), created_at DESC");
    $st->execute($up);
    $users = $st->fetchAll();
}

$flash = flash_get();
$backQs = $_SERVER['QUERY_STRING'] ?? '';
$hour = (int)date('G');
$greet = $hour < 12 ? 'සුභ උදෑසනක්' : ($hour < 18 ? 'සුභ දහවලක්' : 'සුභ සන්ධ්‍යාවක්');
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>පරිපාලක උපකරණ පුවරුව - ලේකනාගාර පද්ධතිය</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
:root{--navy:#0b1f4d;--navy2:#112d63;--blue:#1668e3;--blue-d:#0f55c4;--sky:#e8f0fe;--bg:#f2f5fb;--card:#fff;--line:#e1e7f0;--text:#1c2740;--muted:#6b778c;--ok:#1b8a4b;--warn:#b7791f;--err:#c0392b;--shadow:0 2px 10px rgba(20,40,90,.06)}
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter','Noto Sans Sinhala',sans-serif;background:var(--bg);color:var(--text);font-size:14px;display:flex;min-height:100vh}
a{text-decoration:none;color:inherit}
mark{background:#fff1b8;color:inherit;padding:0 2px;border-radius:3px}

/* SIDEBAR */
.sidebar{width:272px;flex-shrink:0;background:linear-gradient(185deg,var(--navy2) 0%,var(--navy) 100%);color:#fff;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;overflow:hidden}
.sidebar::after{content:"";position:absolute;left:-60px;right:-60px;bottom:-30px;height:260px;background:radial-gradient(ellipse at 30% 70%,rgba(90,130,210,.28),transparent 62%);pointer-events:none}
.brand{display:flex;gap:14px;padding:22px 18px 22px;align-items:flex-start}
.brand img{width:46px;max-height:56px;object-fit:contain;flex-shrink:0}
.brand .crest{width:46px;height:54px;border-radius:50% 50% 45% 45%;background:linear-gradient(145deg,#e6c04a,#a8801a);flex-shrink:0;display:none;align-items:center;justify-content:center;color:#7a1d1d;font-size:22px}
.brand h1{font-size:16px;line-height:1.4;font-weight:700}
.brand p{font-size:12.5px;color:#b9c6e2;margin-top:5px}
.nav{padding:0 12px;display:flex;flex-direction:column;gap:4px;position:relative;z-index:1}
.nav a{display:flex;align-items:center;gap:14px;padding:12px 14px;border-radius:10px;font-size:14.5px;color:#e3eaf8;font-weight:500;transition:.15s}
.nav a i{width:22px;text-align:center;font-size:17px}
.nav a:hover{background:rgba(255,255,255,.09)}
.nav a.active{background:#27498f;color:#fff;box-shadow:0 4px 12px rgba(0,0,0,.18)}
.nav .badge{margin-left:auto;background:#f0b429;color:#3b2a00;font-size:11px;font-weight:700;border-radius:20px;padding:1px 8px}
.nav .sep{height:1px;background:rgba(255,255,255,.13);margin:10px 6px}
.tagline{margin-top:auto;padding:0 24px 30px;font-size:13px;color:#a9b9dd;line-height:1.7;position:relative;z-index:1}

/* TOPBAR / MAIN */
.main{flex:1;min-width:0}
.topbar{height:64px;background:#fff;border-bottom:1px solid #edf0f6;display:flex;align-items:center;justify-content:space-between;padding:0 26px;position:sticky;top:0;z-index:20}
.hamburger{display:none;background:none;border:0;font-size:20px;cursor:pointer;color:var(--text)}
.user{display:flex;align-items:center;gap:12px;margin-left:auto}
.avatar{width:40px;height:40px;border-radius:50%;background:linear-gradient(135deg,#1668e3,#0b3a8c);display:flex;align-items:center;justify-content:center;color:#fff;font-size:16px;font-weight:700}
.user .nm{font-weight:600;line-height:1.2}.user .rl{font-size:12px;color:var(--muted)}
.content{padding:24px 28px 48px;max-width:1550px}

/* HERO */
.hero{position:relative;overflow:hidden;border-radius:16px;padding:26px 30px;color:#fff;margin-bottom:20px;background:linear-gradient(120deg,#0b1f4d 0%,#1b4aa8 60%,#2f7bf0 100%);box-shadow:0 10px 26px rgba(22,70,170,.28)}
.hero::before,.hero::after{content:"";position:absolute;border-radius:50%;background:rgba(255,255,255,.08)}
.hero::before{width:260px;height:260px;right:-60px;top:-110px}.hero::after{width:180px;height:180px;right:140px;bottom:-110px}
.hero h2{font-size:24px;font-weight:700;position:relative}.hero p{margin-top:6px;opacity:.92;position:relative;font-size:14.5px}
.hero .hero-ico{position:absolute;right:34px;top:50%;transform:translateY(-50%);font-size:64px;opacity:.18}

/* STATS */
.stats{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:22px}
.stat{background:var(--card);border:1px solid #edf0f6;border-radius:14px;padding:18px 20px;box-shadow:var(--shadow);display:flex;align-items:center;gap:16px}
.stat .si{width:48px;height:48px;border-radius:13px;display:flex;align-items:center;justify-content:center;font-size:20px;background:var(--sky);color:var(--blue)}
.stat .si.w{background:#fff4dc;color:var(--warn)}.stat .si.g{background:#e6f6ec;color:var(--ok)}.stat .si.r{background:#fdecea;color:var(--err)}
.stat b{display:block;font-size:25px;color:#12285a;line-height:1.1}.stat span{color:var(--muted);font-size:12.5px}

/* TABS */
.tabs{display:inline-flex;gap:6px;background:#e6ebf4;padding:5px;border-radius:12px;margin-bottom:18px;flex-wrap:wrap}
.tabs a{padding:10px 20px;font-weight:600;color:#55627c;border-radius:9px;display:flex;align-items:center;gap:9px;transition:.15s}
.tabs a:hover{color:var(--blue)}.tabs a.on{background:#fff;color:var(--blue);box-shadow:0 2px 8px rgba(20,40,90,.10)}
.tabs .cnt{background:#d5ddec;color:#33415f;border-radius:20px;font-size:11.5px;padding:1px 8px}
.tabs a.on .cnt{background:var(--sky);color:var(--blue)}

/* CARDS / FORMS */
.card{background:var(--card);border:1px solid #edf0f6;border-radius:14px;padding:24px;box-shadow:var(--shadow);margin-bottom:20px}
.card-title{display:flex;align-items:center;gap:12px;font-size:18px;font-weight:600;color:#14224a}
.card-title .ti{width:38px;height:38px;border-radius:10px;background:var(--sky);color:var(--blue);display:flex;align-items:center;justify-content:center;font-size:16px}
.card-title .ti.red{background:#fdecea;color:var(--err)}
.grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px 20px;margin-top:22px}
.field label{display:block;font-weight:600;font-size:13.5px;margin-bottom:7px}
.field label .req{color:var(--err)}
.input{position:relative}
.input>i.lead{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#5a6781;font-size:15px}
.input input,.input select{width:100%;height:44px;border:1px solid var(--line);border-radius:9px;padding:0 12px 0 42px;font:inherit;color:var(--text);background:#fbfcfe;outline:none;transition:.15s}
.input select{appearance:none;-webkit-appearance:none;padding-right:34px;cursor:pointer}
.input .chev{position:absolute;right:14px;top:50%;transform:translateY(-50%);font-size:11px;pointer-events:none;color:#44516b}
.input input::placeholder{color:#8f9aae}
.input input:focus,.input select:focus,textarea:focus{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(22,104,227,.13)}
textarea{width:100%;min-height:96px;border:1px solid var(--line);border-radius:9px;padding:12px 14px;font:inherit;color:var(--text);background:#fbfcfe;outline:none;resize:vertical}
.actions{display:flex;gap:10px;margin-top:22px;flex-wrap:wrap}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;height:42px;padding:0 24px;border-radius:9px;font:inherit;font-weight:600;cursor:pointer;border:1px solid transparent;transition:.15s}
.btn-primary{background:var(--blue);color:#fff;box-shadow:0 5px 14px rgba(22,104,227,.25)}.btn-primary:hover{background:var(--blue-d);transform:translateY(-1px)}
.btn-outline{background:#fff;border-color:var(--line);color:#2a3550}.btn-outline:hover{background:#f5f8fd}
.btn-green{background:var(--ok);color:#fff}.btn-green:hover{background:#157640}
.btn-red{background:var(--err);color:#fff;box-shadow:0 5px 14px rgba(192,57,43,.25)}.btn-red:hover{background:#a52f23}
.btn-sm{height:34px;padding:0 14px;font-size:13px;border-radius:8px;gap:7px}
.alert{padding:13px 16px;border-radius:10px;margin-bottom:18px;display:flex;gap:12px;align-items:flex-start}
.alert-ok{background:#e9f7ef;border:1px solid #bfe5cf;color:#14633a}
.alert-err{background:#fdecea;border:1px solid #f5c6c0;color:#8e271d}

/* TABLE */
.res-head{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap}
.count{color:#4a5873;margin:18px 0 12px;display:flex;align-items:center;gap:10px;flex-wrap:wrap}
.count strong{color:var(--blue);font-size:16px}
.tag-clear{font-size:12.5px;color:var(--blue);font-weight:600}
.table-wrap{overflow-x:auto;border:1px solid #edf0f6;border-radius:10px}
table{width:100%;border-collapse:collapse;min-width:1080px}
thead th{background:#f1f4fa;text-align:left;font-weight:600;padding:13px 14px;white-space:nowrap;font-size:13px;color:#2a3550}
tbody td{padding:12px 14px;border-top:1px solid #eef1f6;vertical-align:middle}
tbody tr:hover{background:#f8faff}
.fname{display:flex;align-items:center;gap:12px;font-weight:600;color:#14224a}
.fname .fi{width:34px;height:34px;border-radius:9px;background:#e8f0fe;color:var(--blue);display:flex;align-items:center;justify-content:center;flex-shrink:0}
.fname .fi.red{background:#fdecea;color:var(--err)}
.sub{display:block;font-weight:400;color:var(--muted);font-size:12px;margin-top:2px}
.chip{display:inline-block;padding:3px 11px;border-radius:20px;font-size:12px;font-weight:600;white-space:nowrap}
.d1{background:#e8f0fe;color:#1a4fb3}.d2{background:#f1e8fd;color:#6b2fb0}.d3{background:#e3f6ec;color:#1b7a47}
.st-pending{background:#fff4dc;color:var(--warn)}.st-ok{background:#e6f6ec;color:var(--ok)}.st-no{background:#fdecea;color:var(--err)}
.arc{font-weight:600;color:#12285a}
.row-actions{display:flex;gap:6px;align-items:center;flex-wrap:nowrap}
.btn-view{height:34px;padding:0 14px;background:var(--blue);color:#fff;border-radius:8px;font-weight:500;font-size:13px;display:inline-flex;align-items:center;gap:7px}
.btn-view:hover{background:var(--blue-d)}
.btn-del{height:34px;padding:0 14px;background:#fff;color:var(--err);border:1px solid #f0c3bd;border-radius:8px;font:inherit;font-weight:600;font-size:13px;display:inline-flex;align-items:center;gap:7px;cursor:pointer;transition:.15s}
.btn-del:hover{background:var(--err);color:#fff;border-color:var(--err)}
.reason{max-width:340px;line-height:1.5;color:#33415f}
.empty{text-align:center;padding:48px 10px;color:var(--muted)}
.empty i{font-size:38px;display:block;margin-bottom:12px;color:#b7c2d8}
.pager{display:flex;gap:6px;margin-top:18px;flex-wrap:wrap}
.pager a,.pager span{min-width:36px;height:36px;display:flex;align-items:center;justify-content:center;border:1px solid var(--line);border-radius:8px;background:#fff;font-weight:500}
.pager .cur{background:var(--blue);border-color:var(--blue);color:#fff}.pager .dis{color:#b4bccb}.pager a:hover{background:#f1f5fc}
.filters-inline{display:flex;gap:10px;flex-wrap:wrap;align-items:center;margin-top:18px}
.filters-inline .input{min-width:230px;flex:1}.filters-inline .input.sm{flex:0 0 190px;min-width:170px}
.you{color:var(--muted);font-size:12.5px;font-style:italic}

/* MODAL */
.modal{position:fixed;inset:0;background:rgba(8,20,50,.55);display:none;align-items:center;justify-content:center;z-index:100;padding:16px;backdrop-filter:blur(2px)}
.modal.show{display:flex}
.modal-box{background:#fff;border-radius:16px;width:100%;max-width:520px;box-shadow:0 24px 70px rgba(0,0,0,.35);overflow:hidden;animation:pop .18s ease}
@keyframes pop{from{transform:translateY(12px) scale(.98);opacity:0}to{transform:none;opacity:1}}
.modal-head{padding:20px 24px;display:flex;align-items:center;gap:14px;border-bottom:1px solid #eef1f6}
.modal-head .mi{width:44px;height:44px;border-radius:12px;background:#fdecea;color:var(--err);display:flex;align-items:center;justify-content:center;font-size:19px}
.modal-head h3{font-size:18px;color:#14224a}.modal-head p{font-size:13px;color:var(--muted);margin-top:2px}
.modal-body{padding:20px 24px}
.modal-foot{padding:16px 24px;background:#f8fafd;display:flex;justify-content:flex-end;gap:10px;border-top:1px solid #eef1f6}
.info-box{background:#f4f7fc;border:1px solid #e3eaf5;border-radius:10px;padding:12px 14px;margin-bottom:16px;line-height:1.6}
.info-box b{color:#14224a}
.warn-box{background:#fff6e5;border:1px solid #f4dba6;color:#7a5200;border-radius:10px;padding:12px 14px;font-size:13px;line-height:1.6;margin-top:6px}
.modal .field{margin-bottom:14px}.modal .field:last-child{margin-bottom:0}

@media (max-width:1100px){.stats{grid-template-columns:repeat(2,1fr)}}
@media (max-width:1000px){
  .sidebar{position:fixed;z-index:40;transform:translateX(-100%);transition:transform .2s}
  body.menu-open .sidebar{transform:none}
  .hamburger{display:block}.grid{grid-template-columns:1fr}
  .content{padding:18px 14px 40px}.hero .hero-ico{display:none}
}
@media (max-width:560px){.stats{grid-template-columns:1fr}.tabs{display:flex}.tabs a{flex:1;justify-content:center;padding:10px 8px;font-size:13px}}
@media print{.sidebar,.topbar,.hero,.stats,.tabs,.no-print{display:none!important}.content{padding:0}.card{box-shadow:none;border:0}}
</style>
</head>
<body>

<!-- ======================= SIDEBAR ======================= -->
<aside class="sidebar">
    <div class="brand">
        <img src="logo.webp" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="crest"><i class="fa-solid fa-landmark"></i></div>
        <div><h1>දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</h1><p>ලේකනාගාර ලිපිගොනු පද්ධතිය</p></div>
    </div>
    <nav class="nav">
        <a href="admin_dashboard.php?tab=files"   class="<?= $tab === 'files' ? 'active' : '' ?>"><i class="fa-solid fa-house"></i> උපකරණ පුවරුව</a>
        <a href="admin_dashboard.php?tab=deleted" class="<?= $tab === 'deleted' ? 'active' : '' ?>"><i class="fa-solid fa-trash-can"></i> ඉවත් කළ ලිපිගොනු</a>
        <a href="admin_dashboard.php?tab=users"   class="<?= $tab === 'users' ? 'active' : '' ?>"><i class="fa-solid fa-users-gear"></i> පරිශීලක කළමනාකරණය
            <?php if ($stats['pendingU'] > 0): ?><span class="badge"><?= $stats['pendingU'] ?></span><?php endif; ?></a>
        <div class="sep"></div>
        <a href="profile.php"><i class="fa-solid fa-user"></i> මගේ පැතිකඩ</a>
        <a href="change_password.php"><i class="fa-solid fa-lock"></i> මුරපදය වෙනස් කිරීම</a>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> ඉවත් වන්න</a>
    </nav>
    <div class="tagline">කාර්යක්ෂම ලේඛන,<br>සුබ හෙට දිනයක් උදෙසා</div>
</aside>

<!-- ======================= MAIN ======================= -->
<div class="main">
    <header class="topbar">
        <button class="hamburger" onclick="document.body.classList.toggle('menu-open')" aria-label="මෙනුව"><i class="fa-solid fa-bars"></i></button>
        <div class="user">
            <div class="avatar"><?= e(mb_strtoupper(mb_substr($user['full_name'], 0, 1))) ?></div>
            <div><div class="nm"><?= e($user['full_name']) ?></div><div class="rl"><?= e(ROLE_LABELS['admin']) ?></div></div>
        </div>
    </header>

    <main class="content">
        <section class="hero">
            <h2><?= $greet ?>, <?= e(explode(' ', trim($user['full_name']))[0]) ?>!</h2>
            <p>ලේඛනාගාර ලිපිගොනු සොයන්න, අවශ්‍ය නම් ඉවත් කරන්න, සහ පරිශීලකයින් කළමනාකරණය කරන්න.</p>
            <i class="fa-solid fa-user-shield hero-ico"></i>
        </section>

        <div class="stats">
            <div class="stat"><div class="si"><i class="fa-solid fa-users"></i></div><div><b><?= $stats['users'] ?></b><span>ලියාපදිංචි පරිශීලකයින්</span></div></div>
            <div class="stat"><div class="si w"><i class="fa-solid fa-user-clock"></i></div><div><b><?= $stats['pendingU'] ?></b><span>අනුමැතිය බලාපොරොත්තුවෙන්</span></div></div>
            <div class="stat"><div class="si g"><i class="fa-solid fa-box-archive"></i></div><div><b><?= $stats['archived'] ?></b><span>ලේඛනාගාරගත ලිපිගොනු</span></div></div>
            <div class="stat"><div class="si r"><i class="fa-solid fa-trash-can"></i></div><div><b><?= $stats['deleted'] ?></b><span>ඉවත් කළ ලිපිගොනු</span></div></div>
        </div>

        <?php if ($flash): ?>
            <div class="alert alert-<?= $flash[0] === 'ok' ? 'ok' : 'err' ?>">
                <i class="fa-solid <?= $flash[0] === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i><div><?= e($flash[1]) ?></div>
            </div>
        <?php endif; ?>

        <div class="tabs">
            <a href="?tab=files"   class="<?= $tab === 'files' ? 'on' : '' ?>"><i class="fa-solid fa-magnifying-glass"></i> ලිපිගොනු සෙවීම</a>
            <a href="?tab=deleted" class="<?= $tab === 'deleted' ? 'on' : '' ?>"><i class="fa-solid fa-trash-can"></i> ඉවත් කළ ලිපිගොනු <span class="cnt"><?= $stats['deleted'] ?></span></a>
            <a href="?tab=users"   class="<?= $tab === 'users' ? 'on' : '' ?>"><i class="fa-solid fa-users-gear"></i> පරිශීලකයින් <span class="cnt"><?= $stats['users'] ?></span></a>
        </div>

<?php if ($tab === 'files'): ?>
        <!-- ===================== ලිපිගොනු සෙවීම ===================== -->
        <section class="card">
            <div class="card-title"><span class="ti"><i class="fa-solid fa-magnifying-glass"></i></span> ලිපිගොනු සොයන්න</div>
            <form method="get">
                <input type="hidden" name="tab" value="files">
                <div class="grid">
                    <div class="field"><label>ලිපිගොනුවේ නම</label>
                        <div class="input"><i class="fa-regular fa-file-lines lead"></i><input type="text" name="file_name" value="<?= e($filters['file_name']) ?>" placeholder="නමේ කොටසක් වුවද ඇතුළත් කරන්න..."></div></div>
                    <div class="field"><label>අංශය</label>
                        <div class="input"><i class="fa-solid fa-landmark lead"></i>
                            <select name="department"><option value="">අංශය තෝරන්න</option>
                                <?php foreach (DEPARTMENTS as $d): ?><option value="<?= e($d) ?>" <?= $filters['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                            </select><i class="fa-solid fa-chevron-down chev"></i></div></div>
                    <div class="field"><label>විෂය අංකය</label>
                        <div class="input"><i class="fa-solid fa-hashtag lead"></i><input type="text" name="subject_number" value="<?= e($filters['subject_number']) ?>" placeholder="විෂය අංකය ඇතුළත් කරන්න..."></div></div>
                    <div class="field"><label>ලේකනාගාර අංකය</label>
                        <div class="input"><i class="fa-solid fa-box-archive lead"></i><input type="text" name="archive_number" value="<?= e($filters['archive_number']) ?>" placeholder="ලේකනාගාර අංකය ඇතුළත් කරන්න..."></div></div>
                    <div class="field"><label>ලිපිගොනුවේ තත්ත්වය</label>
                        <div class="input"><i class="fa-solid fa-circle-half-stroke lead"></i>
                            <select name="status">
                                <?php foreach (FILE_STATUSES as $s): ?><option value="<?= $s ?>" <?= $filters['status'] === $s ? 'selected' : '' ?>><?= e($fileStatus[$s][0]) ?></option><?php endforeach; ?>
                            </select><i class="fa-solid fa-chevron-down chev"></i></div></div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> සොයන්න</button>
                    <a class="btn btn-outline" href="?tab=files"><i class="fa-solid fa-rotate"></i> මකන්න</a>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="res-head">
                <div class="card-title"><span class="ti"><i class="fa-regular fa-rectangle-list"></i></span> සෙවුම් ප්‍රතිඵල</div>
                <?php if ($total > 0): ?><a class="btn btn-primary no-print" href="<?= e(qs(['print' => 1], ['page', 'tab'])) ?>" target="_blank"><i class="fa-solid fa-print"></i> ප්‍රතිඵල මුද්‍රණය</a><?php endif; ?>
            </div>
            <div class="count">ලිපිගොනු <strong><?= $total ?></strong>ක් හමු විය
                <?php if ($isFiltered): ?><a class="tag-clear" href="?tab=files"><i class="fa-solid fa-xmark"></i> filters ඉවත් කරන්න</a><?php endif; ?></div>

            <div class="table-wrap">
            <table>
                <thead><tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ආරම්භක දිනය</th><th>අවසන් දිනය</th><th>පිටු</th><th>තත්ත්වය</th><th>ක්‍රියා</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="10"><div class="empty"><i class="fa-regular fa-folder-open"></i>ගැලපෙන ලිපිගොනු හමු නොවීය.<br>වෙනත් නමක් හෝ අංකයක් උත්සාහ කරන්න.</div></td></tr><?php endif; ?>
                <?php foreach ($rows as $i => $r): [$sl, $sc, $si] = $fileStatus[$r['status']]; ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td><div class="fname"><span class="fi"><i class="fa-regular fa-file-lines"></i></span><span><?= hl($r['file_name'], $filters['file_name']) ?></span></div></td>
                        <td><span class="chip <?= $deptClass[$r['department']] ?? 'd1' ?>"><?= e($r['department']) ?></span></td>
                        <td><?= hl($r['subject_number'], $filters['subject_number']) ?></td>
                        <td class="arc"><?= hl((string)$r['archive_number'], $filters['archive_number']) ?></td>
                        <td><?= e($r['start_date']) ?></td>
                        <td><?= e($r['end_date']) ?></td>
                        <td><?= (int)$r['page_count'] ?></td>
                        <td><span class="chip <?= $sc ?>"><i class="fa-solid <?= $si ?>"></i> <?= e($sl) ?></span></td>
                        <td><div class="row-actions">
                            <a class="btn-view" href="view_file.php?id=<?= (int)$r['id'] ?>"><i class="fa-regular fa-eye"></i> බලන්න</a>
                            <button type="button" class="btn-del" data-id="<?= (int)$r['id'] ?>" data-name="<?= e($r['file_name']) ?>" data-arc="<?= e($r['archive_number'] ?? '-') ?>" onclick="openFileDelete(this)"><i class="fa-solid fa-trash-can"></i> ඉවත් කරන්න</button>
                        </div></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?= pager($page, $pages, 'page', 'files') ?>
        </section>

<?php elseif ($tab === 'deleted'): ?>
        <!-- ===================== ඉවත් කළ ලිපිගොනු ===================== -->
        <section class="card">
            <div class="res-head">
                <div class="card-title"><span class="ti red"><i class="fa-solid fa-trash-can"></i></span> ඉවත් කළ ලිපිගොනු</div>
                <?php if ($dTotal > 0): ?><a class="btn btn-primary no-print" href="?print=deleted&amp;dq=<?= urlencode($dq) ?>" target="_blank"><i class="fa-solid fa-print"></i> මුද්‍රණය</a><?php endif; ?>
            </div>
            <form method="get" class="filters-inline">
                <input type="hidden" name="tab" value="deleted">
                <div class="input"><i class="fa-solid fa-magnifying-glass lead"></i><input type="text" name="dq" value="<?= e($dq) ?>" placeholder="නම, ලේකනාගාර අංකය හෝ හේතුව අනුව සොයන්න..."></div>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> සොයන්න</button>
                <?php if ($dq !== ''): ?><a class="btn btn-outline" href="?tab=deleted"><i class="fa-solid fa-rotate"></i> මකන්න</a><?php endif; ?>
            </form>
            <div class="count">ඉවත් කළ ලිපිගොනු <strong><?= $dTotal ?></strong>ක් ඇත</div>

            <div class="table-wrap">
            <table>
                <thead><tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ඉවත් කළ දිනය</th><th>ඉවත් කිරීමට හේතුව</th><th>ඉවත් කළේ</th><th>ක්‍රියා</th></tr></thead>
                <tbody>
                <?php if (!$dRows): ?><tr><td colspan="9"><div class="empty"><i class="fa-regular fa-trash-can"></i>ඉවත් කළ ලිපිගොනු නොමැත.</div></td></tr><?php endif; ?>
                <?php foreach ($dRows as $i => $r): ?>
                    <tr>
                        <td><?= $dOffset + $i + 1 ?></td>
                        <td><div class="fname"><span class="fi red"><i class="fa-regular fa-file-lines"></i></span><span><?= hl($r['file_name'], $dq) ?></span></div></td>
                        <td><span class="chip <?= $deptClass[$r['department']] ?? 'd1' ?>"><?= e($r['department']) ?></span></td>
                        <td><?= e($r['subject_number']) ?></td>
                        <td class="arc"><?= hl((string)$r['archive_number'], $dq) ?></td>
                        <td><?= e($r['delete_date']) ?><span class="sub"><?= e(substr((string)$r['deleted_at'], 11, 5)) ?></span></td>
                        <td class="reason"><?= hl((string)$r['delete_reason'], $dq) ?></td>
                        <td><?= e($r['deleted_by_name'] ?? '-') ?></td>
                        <td><a class="btn-view" href="view_file.php?id=<?= (int)$r['id'] ?>"><i class="fa-regular fa-eye"></i> බලන්න</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?= pager($dPage, $dPages, 'dpage', 'deleted') ?>
        </section>

<?php else: ?>
        <!-- ===================== පරිශීලක කළමනාකරණය ===================== -->
        <section class="card">
            <div class="card-title"><span class="ti"><i class="fa-solid fa-users-gear"></i></span> ලියාපදිංචි පරිශීලකයින්</div>
            <form method="get" class="filters-inline">
                <input type="hidden" name="tab" value="users">
                <div class="input"><i class="fa-solid fa-magnifying-glass lead"></i><input type="text" name="uq" value="<?= e($uq) ?>" placeholder="නම, පරිශීලක නාමය, email හෝ NIC..."></div>
                <div class="input sm"><i class="fa-solid fa-user-shield lead"></i>
                    <select name="ur"><option value="">සියලු වර්ග</option>
                        <?php foreach (ROLE_LABELS as $k => $l): ?><option value="<?= $k ?>" <?= $ur === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                    </select><i class="fa-solid fa-chevron-down chev"></i></div>
                <div class="input sm"><i class="fa-solid fa-circle-half-stroke lead"></i>
                    <select name="us"><option value="">සියලු තත්ත්ව</option>
                        <?php foreach ($userStatus as $k => $l): ?><option value="<?= $k ?>" <?= $us === $k ? 'selected' : '' ?>><?= e($l[0]) ?></option><?php endforeach; ?>
                    </select><i class="fa-solid fa-chevron-down chev"></i></div>
                <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> පෙරන්න</button>
                <?php if ($uq !== '' || $us !== '' || $ur !== ''): ?><a class="btn btn-outline" href="?tab=users"><i class="fa-solid fa-rotate"></i> මකන්න</a><?php endif; ?>
            </form>
            <div class="count">පරිශීලකයින් <strong><?= count($users) ?></strong>ක් හමු විය</div>

            <div class="table-wrap">
            <table>
                <thead><tr><th>නම</th><th>පරිශීලක නාමය</th><th>වර්ගය</th><th>අංශය</th><th>තනතුර</th><th>විෂය අංකය</th><th>NIC</th><th>තත්ත්වය</th><th>ක්‍රියා</th></tr></thead>
                <tbody>
                <?php if (!$users): ?><tr><td colspan="9"><div class="empty"><i class="fa-solid fa-users-slash"></i>පරිශීලකයින් හමු නොවීය.</div></td></tr><?php endif; ?>
                <?php foreach ($users as $u): [$ul, $uc] = $userStatus[$u['status']]; $self = (int)$u['id'] === (int)$user['id']; ?>
                    <tr>
                        <td><div class="fname"><span class="fi"><i class="fa-solid fa-user"></i></span><span><?= hl($u['full_name'], $uq) ?><span class="sub"><?= hl($u['email'], $uq) ?></span></span></div></td>
                        <td><?= hl($u['username'], $uq) ?></td>
                        <td><?= e(ROLE_LABELS[$u['role']]) ?></td>
                        <td><span class="chip <?= $deptClass[$u['department']] ?? 'd1' ?>"><?= e($u['department']) ?></span></td>
                        <td><?= e($u['designation']) ?></td>
                        <td><?= e($u['subject_number']) ?></td>
                        <td><?= hl($u['nic'], $uq) ?></td>
                        <td><span class="chip <?= $uc ?>"><?= e($ul) ?></span></td>
                        <td>
                        <?php if ($self): ?><span class="you">ඔබ</span>
                        <?php else: ?>
                            <div class="row-actions">
                                <form method="post" style="display:inline-flex">
                                    <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="back" value="<?= e($backQs) ?>">
                                    <?php if ($u['status'] === 'pending'): ?><button class="btn btn-green btn-sm" name="action" value="approve"><i class="fa-solid fa-check"></i> අනුමත කරන්න</button>
                                    <?php elseif ($u['status'] === 'active'): ?><button class="btn btn-outline btn-sm" name="action" value="disable" onclick="return confirm('මෙම ගිණුම අක්‍රීය කරන්නද?')"><i class="fa-solid fa-user-slash"></i> අක්‍රීය</button>
                                    <?php else: ?><button class="btn btn-primary btn-sm" name="action" value="enable"><i class="fa-solid fa-user-check"></i> සක්‍රීය</button><?php endif; ?>
                                </form>
                                <button type="button" class="btn-del" data-id="<?= (int)$u['id'] ?>" data-name="<?= e($u['full_name']) ?>" data-user="<?= e($u['username']) ?>" onclick="openUserDelete(this)"><i class="fa-solid fa-user-xmark"></i> මකන්න</button>
                            </div>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
<?php endif; ?>
    </main>
</div>

<!-- ======================= MODAL: ලිපිගොනුව ඉවත් කිරීම ======================= -->
<div class="modal" id="mFile" onclick="if(event.target===this)closeModal('mFile')">
  <form class="modal-box" method="post" action="admin_dashboard.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_file">
    <input type="hidden" name="back" value="<?= e($backQs) ?>">
    <input type="hidden" name="file_id" id="dfId">
    <div class="modal-head"><div class="mi"><i class="fa-solid fa-trash-can"></i></div><div><h3>ලිපිගොනුව ඉවත් කරන්න</h3><p>ඉවත් කිරීමට හේතුව සහ දිනය සඳහන් කිරීම අනිවාර්යයි</p></div></div>
    <div class="modal-body">
        <div class="info-box"><b id="dfName"></b><br><span style="color:var(--muted)">ලේකනාගාර අංකය: <span id="dfArc"></span></span></div>
        <div class="field"><label>ඉවත් කළ දිනය <span class="req">*</span></label>
            <div class="input"><i class="fa-regular fa-calendar lead"></i><input type="date" name="delete_date" id="dfDate" max="<?= $today ?>" value="<?= $today ?>" required></div></div>
        <div class="field"><label>ඉවත් කිරීමට හේතුව <span class="req">*</span></label>
            <textarea name="delete_reason" id="dfReason" maxlength="500" placeholder="ලිපිගොනුව ඉවත් කිරීමට හේතුව සඳහන් කරන්න..." required></textarea></div>
        <div class="warn-box"><i class="fa-solid fa-circle-info"></i> ඉවත් කළ ලිපිගොනුව සෙවුම් ප්‍රතිඵලවලින් ඉවත් වන අතර, හේතුව සහ දිනය සමඟ "ඉවත් කළ ලිපිගොනු" අංශයේ සටහන් වේ.</div>
    </div>
    <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('mFile')">අවලංගු කරන්න</button>
        <button type="submit" class="btn btn-red"><i class="fa-solid fa-trash-can"></i> ඉවත් කරන්න</button>
    </div>
  </form>
</div>

<!-- ======================= MODAL: පරිශීලකයා මකා දැමීම ======================= -->
<div class="modal" id="mUser" onclick="if(event.target===this)closeModal('mUser')">
  <form class="modal-box" method="post" action="admin_dashboard.php">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="delete_user">
    <input type="hidden" name="back" value="<?= e($backQs) ?>">
    <input type="hidden" name="user_id" id="duId">
    <div class="modal-head"><div class="mi"><i class="fa-solid fa-user-xmark"></i></div><div><h3>පරිශීලකයා මකා දමන්න</h3><p>මෙම ක්‍රියාව ආපසු හැරවිය නොහැක</p></div></div>
    <div class="modal-body">
        <div class="info-box"><b id="duName"></b><br><span style="color:var(--muted)">පරිශීලක නාමය: <span id="duUser"></span></span></div>
        <div class="warn-box"><i class="fa-solid fa-triangle-exclamation"></i> මෙම පරිශීලකයාගේ ගිණුම සම්පූර්ණයෙන්ම මකා දැමේ. ඔහු යොමු කළ ලිපිගොනු ලේඛනාගාරයේ ඒ අයුරින්ම පවතී. තාවකාලිකව වැළැක්වීමට පමණක් නම් "අක්‍රීය" කිරීම භාවිතා කරන්න.</div>
    </div>
    <div class="modal-foot">
        <button type="button" class="btn btn-outline" onclick="closeModal('mUser')">අවලංගු කරන්න</button>
        <button type="submit" class="btn btn-red"><i class="fa-solid fa-user-xmark"></i> මකා දමන්න</button>
    </div>
  </form>
</div>

<script>
const TODAY = "<?= $today ?>";
function openModal(id){ document.getElementById(id).classList.add('show'); }
function closeModal(id){ document.getElementById(id).classList.remove('show'); }
function openFileDelete(b){
    document.getElementById('dfId').value = b.dataset.id;
    document.getElementById('dfName').textContent = b.dataset.name;
    document.getElementById('dfArc').textContent = b.dataset.arc;
    document.getElementById('dfDate').value = TODAY;
    document.getElementById('dfReason').value = '';
    openModal('mFile');
    setTimeout(function(){ document.getElementById('dfReason').focus(); }, 60);
}
function openUserDelete(b){
    document.getElementById('duId').value = b.dataset.id;
    document.getElementById('duName').textContent = b.dataset.name;
    document.getElementById('duUser').textContent = b.dataset.user;
    openModal('mUser');
}
document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') document.querySelectorAll('.modal.show').forEach(function(m){ m.classList.remove('show'); });
});
</script>
</body>
</html>
