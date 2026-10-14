<?php
require __DIR__ . '/config.php';
$user = require_role($pdo, 'user');

/* ---------- Search logic ----------
   නම (කොටසක් වුවත්) / අංශය / විෂය අංකය / ලේකනාගාර අංකය
   තෝරා ඇති සියලුම filters එකවර ගැලපිය යුතුය (AND). archive කර ඇති ගොනු පමණි. */
function build_where(array $f): array {
    $where = ["status = 'archived'"]; $p = [];
    if ($f['file_name'] !== '')      { $where[] = 'file_name LIKE ?';      $p[] = '%' . $f['file_name'] . '%'; }
    if ($f['department'] !== '')     { $where[] = 'department = ?';        $p[] = $f['department']; }
    if ($f['subject_number'] !== '') { $where[] = 'subject_number LIKE ?'; $p[] = '%' . $f['subject_number'] . '%'; }
    if ($f['archive_number'] !== '') { $where[] = 'archive_number LIKE ?'; $p[] = '%' . $f['archive_number'] . '%'; }
    return [implode(' AND ', $where), $p];
}
$filters = [
    'file_name'      => trim($_GET['file_name'] ?? ''),
    'department'     => in_array($_GET['department'] ?? '', DEPARTMENTS, true) ? $_GET['department'] : '',
    'subject_number' => trim($_GET['subject_number'] ?? ''),
    'archive_number' => trim($_GET['archive_number'] ?? ''),
];
[$whereSql, $whereParams] = build_where($filters);

/* ---------- Print view (සියලු ප්‍රතිඵල) ---------- */
if (isset($_GET['print'])) {
    $st = $pdo->prepare("SELECT * FROM archive_files WHERE $whereSql ORDER BY submit_date DESC, id DESC");
    $st->execute($whereParams);
    $all = $st->fetchAll();
    ?>
<!DOCTYPE html><html lang="si"><head><meta charset="UTF-8"><title>ලිපිගොනු ලැයිස්තුව</title>
<style>
body{font-family:'Noto Sans Sinhala','Iskoola Pota',Arial,sans-serif;margin:30px;color:#111}
h2{text-align:center;margin:0}.sub{text-align:center;color:#555;margin:4px 0 18px;font-size:13px}
table{width:100%;border-collapse:collapse;font-size:12.5px}th,td{border:1px solid #888;padding:6px 8px;text-align:left}th{background:#eee}
</style></head><body onload="window.print()">
<h2>දකුණු පළාත් සභා ලේකම් කාර්යාලය</h2>
<div class="sub">ලේකනාගාර ලිපිගොනු ලැයිස්තුව &nbsp;|&nbsp; මුද්‍රණය කළ දිනය: <?= date('Y-m-d') ?> &nbsp;|&nbsp; ගොනු <?= count($all) ?>ක්</div>
<table>
<tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ලිපිගොනු අංකය</th><th>ආරම්භක දිනය</th><th>අවසන් දිනය</th><th>පිටු</th></tr>
<?php foreach ($all as $i => $r): ?>
<tr><td><?= $i + 1 ?></td><td><?= e($r['file_name']) ?></td><td><?= e($r['department']) ?></td><td><?= e($r['subject_number']) ?></td><td><?= e($r['archive_number']) ?></td><td><?= e($r['file_number']) ?></td><td><?= e($r['start_date']) ?></td><td><?= e($r['end_date']) ?></td><td><?= (int)$r['page_count'] ?></td></tr>
<?php endforeach; ?>
</table></body></html>
<?php
    exit;
}

$tab = ($_GET['tab'] ?? 'search') === 'submit' ? 'submit' : 'search';

/* ---------- ලිපිගොනුවක් යොමු කිරීම (සුරකින්න) ---------- */
$errors = [];
$old = ['submit_date' => date('Y-m-d'), 'subject_number' => '', 'department' => '', 'file_number' => '', 'file_name' => '', 'start_date' => '', 'end_date' => '', 'page_count' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_file') {
    $tab = 'submit';
    if (!csrf_ok()) $errors[] = 'ආරක්ෂක පරීක්ෂාව අසාර්ථකයි. පිටුව නැවත පූරණය කර උත්සාහ කරන්න.';
    foreach ($old as $k => $_) $old[$k] = trim($_POST[$k] ?? '');

    if (!valid_date($old['submit_date']))                 $errors[] = 'දිනය නිවැරදිව ඇතුළත් කරන්න.';
    if ($old['subject_number'] === '')                    $errors[] = 'විෂය අංකය ඇතුළත් කරන්න.';
    if (!in_array($old['department'], DEPARTMENTS, true)) $errors[] = 'අංශය තෝරන්න.';
    if ($old['file_number'] === '')                       $errors[] = 'ලිපිගොනු අංකය ඇතුළත් කරන්න.';
    if ($old['file_name'] === '')                         $errors[] = 'ලිපිගොනුවේ නම ඇතුළත් කරන්න.';
    if (!valid_date($old['start_date']))                  $errors[] = 'ආරම්භක දිනය නිවැරදිව ඇතුළත් කරන්න.';
    if (!valid_date($old['end_date']))                    $errors[] = 'අවසන් කළ දිනය නිවැරදිව ඇතුළත් කරන්න.';
    if (valid_date($old['start_date']) && valid_date($old['end_date']) && $old['end_date'] < $old['start_date'])
                                                          $errors[] = 'අවසන් දිනය ආරම්භක දිනයට පෙර විය නොහැක.';
    if (!ctype_digit($old['page_count']) || (int)$old['page_count'] < 1) $errors[] = 'පිටු ගණන නිවැරදිව ඇතුළත් කරන්න.';

    if (!$errors) {
        $pdo->prepare("INSERT INTO archive_files (submitted_by, submit_date, subject_number, department, file_number, file_name, start_date, end_date, page_count, status)
                       VALUES (?,?,?,?,?,?,?,?,?, 'pending')")
            ->execute([$user['id'], $old['submit_date'], $old['subject_number'], $old['department'], $old['file_number'],
                       $old['file_name'], $old['start_date'], $old['end_date'], (int)$old['page_count']]);
        flash_set('ok', 'ලිපිගොනුව සාර්ථකව සුරකින ලදී. එය දැන් විෂය නිලධාරියාට පෙන්වනු ඇත.');
        header('Location: user_dashboard.php?tab=submit'); exit;
    }
}

/* ---------- Results + pagination ---------- */
$perPage = 10;
$cnt = $pdo->prepare("SELECT COUNT(*) FROM archive_files WHERE $whereSql");
$cnt->execute($whereParams);
$total  = (int)$cnt->fetchColumn();
$pages  = max(1, (int)ceil($total / $perPage));
$page   = min(max(1, (int)($_GET['page'] ?? 1)), $pages);
$offset = ($page - 1) * $perPage;
$st = $pdo->prepare("SELECT * FROM archive_files WHERE $whereSql ORDER BY submit_date DESC, id DESC LIMIT $perPage OFFSET $offset");
$st->execute($whereParams);
$rows = $st->fetchAll();

$mine = $pdo->prepare('SELECT * FROM archive_files WHERE submitted_by = ? ORDER BY created_at DESC LIMIT 8');
$mine->execute([$user['id']]);
$mine = $mine->fetchAll();

function page_url(int $p): string { $q = $_GET; $q['page'] = $p; $q['tab'] = 'search'; return '?' . http_build_query($q); }
function print_url(): string { $q = $_GET; unset($q['page'], $q['tab']); $q['print'] = 1; return '?' . http_build_query($q); }
$statusLabel = ['pending' => ['විෂය නිලධාරී බලාපොරොත්තුවෙන්', 'st-pending'], 'archived' => ['ලේඛනාගාරගත කළා', 'st-ok'], 'rejected' => ['ප්‍රතික්ෂේප කළා', 'st-no']];

$pageTitle = 'පරිශීලක උපකරණ පුවරුව'; $activeNav = 'dashboard'; $pageIcon = 'fa-folder-open';
$pageHeading = 'පරිශීලක උපකරණ පුවරුව'; $pageSub = 'ලේකනාගාර ලිපිගොනු සොයන්න සහ ලිපිගොනු යොමු කරන්න';
include __DIR__ . '/includes/header.php';
?>
        <div class="tabs">
            <a href="?tab=search" class="<?= $tab === 'search' ? 'on' : '' ?>"><i class="fa-solid fa-magnifying-glass"></i> ලිපිගොනු සෙවීම</a>
            <a href="?tab=submit" class="<?= $tab === 'submit' ? 'on' : '' ?>"><i class="fa-solid fa-file-circle-plus"></i> ලිපිගොනුවක් යොමු කිරීම</a>
        </div>

<?php if ($tab === 'search'): ?>
        <section class="card">
            <h3><i class="fa-solid fa-magnifying-glass"></i> ලිපිගොනු සොයන්න</h3>
            <form method="get">
                <input type="hidden" name="tab" value="search">
                <div class="grid">
                    <div class="field"><label>ලිපිගොනුවේ නම</label>
                        <div class="input"><i class="fa-regular fa-file-lines lead"></i>
                            <input type="text" name="file_name" value="<?= e($filters['file_name']) ?>" placeholder="නමේ කොටසක් වුවද ඇතුළත් කරන්න..."></div></div>
                    <div class="field"><label>අංශය</label>
                        <div class="input"><i class="fa-solid fa-landmark lead"></i>
                            <select name="department">
                                <option value="">අංශය තෝරන්න</option>
                                <?php foreach (DEPARTMENTS as $d): ?><option value="<?= e($d) ?>" <?= $filters['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                            </select><i class="fa-solid fa-chevron-down chev"></i></div></div>
                    <div class="field"><label>විෂය අංකය</label>
                        <div class="input"><i class="fa-solid fa-hashtag lead"></i>
                            <input type="text" name="subject_number" value="<?= e($filters['subject_number']) ?>" placeholder="විෂය අංකය ඇතුළත් කරන්න..."></div></div>
                    <div class="field"><label>ලේකනාගාර අංකය</label>
                        <div class="input"><i class="fa-solid fa-box-archive lead"></i>
                            <input type="text" name="archive_number" value="<?= e($filters['archive_number']) ?>" placeholder="ලේකනාගාර අංකය ඇතුළත් කරන්න..."></div></div>
                </div>
                <div class="actions">
                    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> සොයන්න</button>
                    <a class="btn btn-outline" href="?tab=search"><i class="fa-solid fa-rotate"></i> මකන්න</a>
                </div>
            </form>
        </section>

        <section class="card">
            <div class="res-head">
                <h3><i class="fa-regular fa-rectangle-list"></i> සෙවුම් ප්‍රතිඵල</h3>
                <?php if ($total > 0): ?><a class="btn btn-primary" href="<?= e(print_url()) ?>" target="_blank"><i class="fa-solid fa-print"></i> ප්‍රතිඵල මුද්‍රණය</a><?php endif; ?>
            </div>
            <div class="count">ලිපිගොනු <strong><?= $total ?></strong>ක් හමු විය</div>
            <div class="table-wrap">
            <table>
                <thead><tr><th>අංකය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>ආරම්භක දිනය</th><th>අවසන් දිනය</th><th>පිටු</th><th>ක්‍රියා</th></tr></thead>
                <tbody>
                <?php if (!$rows): ?><tr><td colspan="9"><div class="empty"><i class="fa-regular fa-folder-open"></i>ගැලපෙන ලිපිගොනු හමු නොවීය. වෙනත් නමක් හෝ අංකයක් උත්සාහ කරන්න.</div></td></tr><?php endif; ?>
                <?php foreach ($rows as $i => $r): ?>
                    <tr>
                        <td><?= $offset + $i + 1 ?></td>
                        <td class="fname"><?= e($r['file_name']) ?></td>
                        <td><span class="chip"><?= e($r['department']) ?></span></td>
                        <td><?= e($r['subject_number']) ?></td>
                        <td><?= e($r['archive_number']) ?></td>
                        <td><?= e($r['start_date']) ?></td>
                        <td><?= e($r['end_date']) ?></td>
                        <td><?= (int)$r['page_count'] ?></td>
                        <td><a class="btn-view" href="view_file.php?id=<?= (int)$r['id'] ?>"><i class="fa-regular fa-eye"></i> බලන්න</a></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if ($pages > 1): ?>
            <div class="pager">
                <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1)) ?>"><i class="fa-solid fa-chevron-left"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-left"></i></span><?php endif; ?>
                <?php for ($p = 1; $p <= $pages; $p++): ?><?php if ($p === $page): ?><span class="cur"><?= $p ?></span><?php else: ?><a href="<?= e(page_url($p)) ?>"><?= $p ?></a><?php endif; ?><?php endfor; ?>
                <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1)) ?>"><i class="fa-solid fa-chevron-right"></i></a><?php else: ?><span class="dis"><i class="fa-solid fa-chevron-right"></i></span><?php endif; ?>
            </div>
            <?php endif; ?>
        </section>

<?php else: ?>
        <?php if ($errors): ?>
            <div class="alert alert-err"><i class="fa-solid fa-triangle-exclamation"></i>
                <div>කරුණාකර පහත දෝෂ නිවැරදි කරන්න:<ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
        <?php endif; ?>

        <section class="card">
            <h3><i class="fa-solid fa-file-circle-plus"></i> ලේඛනාගාරයට ලිපිගොනුවක් යොමු කරන්න</h3>
            <form method="post" action="user_dashboard.php?tab=submit">
                <input type="hidden" name="action" value="submit_file">
                <?= csrf_field() ?>
                <div class="grid">
                    <div class="field"><label>දිනය <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-calendar lead"></i><input type="date" name="submit_date" value="<?= e($old['submit_date']) ?>" required></div></div>
                    <div class="field"><label>විෂය අංකය <span class="req">*</span></label>
                        <div class="input"><i class="fa-solid fa-hashtag lead"></i><input type="text" name="subject_number" value="<?= e($old['subject_number']) ?>" placeholder="උදා: SUB-001" required></div></div>
                    <div class="field"><label>අංශය <span class="req">*</span></label>
                        <div class="input"><i class="fa-solid fa-landmark lead"></i>
                            <select name="department" required>
                                <option value="">අංශය තෝරන්න</option>
                                <?php foreach (DEPARTMENTS as $d): ?><option value="<?= e($d) ?>" <?= $old['department'] === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                            </select><i class="fa-solid fa-chevron-down chev"></i></div></div>
                    <div class="field"><label>ලිපිගොනු අංකය <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-address-card lead"></i><input type="text" name="file_number" value="<?= e($old['file_number']) ?>" placeholder="ලිපිගොනු අංකය" required></div></div>
                    <div class="field" style="grid-column:span 2"><label>ලිපිගොනුවේ නම <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-file-lines lead"></i><input type="text" name="file_name" value="<?= e($old['file_name']) ?>" placeholder="ලිපිගොනුවේ සම්පූර්ණ නම" required></div></div>
                    <div class="field"><label>ලිපිගොනුවේ ආරම්භක දිනය <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-calendar-plus lead"></i><input type="date" name="start_date" value="<?= e($old['start_date']) ?>" required></div></div>
                    <div class="field"><label>ලිපිගොනුව අවසන් කළ දිනය <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-calendar-check lead"></i><input type="date" name="end_date" value="<?= e($old['end_date']) ?>" required></div></div>
                    <div class="field"><label>ගොනුවේ පිටු ගණන <span class="req">*</span></label>
                        <div class="input"><i class="fa-regular fa-copy lead"></i><input type="number" min="1" name="page_count" value="<?= e($old['page_count']) ?>" placeholder="පිටු ගණන" required></div></div>
                </div>
                <p class="hint">සුරකිනු ලැබූ පසු ලිපිගොනුව විෂය නිලධාරියාට පෙන්වන අතර, ලේකනාගාර අංකය ලබා දෙනු ලබන්නේ ඔහු විසිනි.</p>
                <div class="actions">
                    <button class="btn btn-green" type="submit"><i class="fa-solid fa-floppy-disk"></i> සුරකින්න</button>
                    <button class="btn btn-outline" type="reset"><i class="fa-solid fa-rotate"></i> හිස් කරන්න</button>
                </div>
            </form>
        </section>

        <section class="card">
            <h3><i class="fa-solid fa-clock-rotate-left"></i> මා විසින් යොමු කළ ලිපිගොනු</h3>
            <div class="table-wrap">
            <table style="min-width:760px">
                <thead><tr><th>දිනය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>පිටු</th><th>තත්ත්වය</th></tr></thead>
                <tbody>
                <?php if (!$mine): ?><tr><td colspan="7"><div class="empty"><i class="fa-regular fa-paper-plane"></i>තවම ලිපිගොනු යොමු කර නැත.</div></td></tr><?php endif; ?>
                <?php foreach ($mine as $m): [$lbl, $cls] = $statusLabel[$m['status']]; ?>
                    <tr>
                        <td><?= e($m['submit_date']) ?></td><td class="fname"><?= e($m['file_name']) ?></td>
                        <td><span class="chip"><?= e($m['department']) ?></span></td><td><?= e($m['subject_number']) ?></td>
                        <td><?= e($m['archive_number'] ?? '-') ?></td><td><?= (int)$m['page_count'] ?></td>
                        <td><span class="chip <?= $cls ?>"><?= e($lbl) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
