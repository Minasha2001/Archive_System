<?php
require __DIR__ . '/config.php';
$user = require_login($pdo);

$st = $pdo->prepare('SELECT f.*, u.full_name AS submitter FROM archive_files f JOIN users u ON u.id = f.submitted_by WHERE f.id = ?');
$st->execute([(int)($_GET['id'] ?? 0)]);
$f = $st->fetch();

// archive කළ ගොනු සියලු දෙනාට; අනෙක් ඒවා ගොනුව යොමු කළ අයට / විෂය නිලධාරී / පරිපාලකට පමණි
$allowed = $f && ($f['status'] === 'archived' || (int)$f['submitted_by'] === (int)$user['id'] || in_array($user['role'], ['admin', 'subject'], true));
if (!$allowed) { flash_set('err', 'ඉල්ලූ ලිපිගොනුව හමු නොවීය.'); header('Location: ' . ROLE_PAGES[$user['role']]); exit; }

$labels = ['pending' => 'බලාපොරොත්තුවෙන්', 'archived' => 'ලේඛනාගාරගත', 'rejected' => 'ප්‍රතික්ෂේප කළා'];
$pageTitle = 'ලිපිගොනු විස්තර'; $activeNav = 'dashboard'; $pageIcon = 'fa-file-lines';
$pageHeading = 'ලිපිගොනු විස්තර'; $pageSub = $f['file_name'];
include __DIR__ . '/includes/header.php';
$rows = [
    'ලිපිගොනුවේ නම' => $f['file_name'], 'අංශය' => $f['department'], 'විෂය අංකය' => $f['subject_number'],
    'ලිපිගොනු අංකය' => $f['file_number'], 'ලේකනාගාර අංකය' => $f['archive_number'] ?? '-', 'ආරම්භක දිනය' => $f['start_date'],
    'අවසන් කළ දිනය' => $f['end_date'], 'පිටු ගණන' => $f['page_count'], 'යොමු කළ දිනය' => $f['submit_date'],
    'යොමු කළේ' => $f['submitter'], 'තත්ත්වය' => $labels[$f['status']],
];
?>
        <section class="card">
            <h3><i class="fa-regular fa-file-lines"></i> <?= e($f['file_name']) ?></h3>
            <dl class="details">
                <?php foreach ($rows as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
            </dl>
            <div class="actions no-print">
                <a class="btn btn-outline" href="javascript:history.back()"><i class="fa-solid fa-arrow-left"></i> ආපසු</a>
                <button class="btn btn-primary" onclick="window.print()"><i class="fa-solid fa-print"></i> මුද්‍රණය</button>
            </div>
        </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
