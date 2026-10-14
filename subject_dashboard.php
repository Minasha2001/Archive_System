<?php
require __DIR__ . '/config.php';
$user = require_role($pdo, 'subject');

/* ---------- Approve / Reject ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['file_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if (!csrf_ok()) {
        flash_set('err', 'ආරක්ෂක පරීක්ෂාව අසාර්ථකයි. නැවත උත්සාහ කරන්න.');
    } elseif ($act === 'approve') {
        $arc = trim($_POST['archive_number'] ?? '');
        if ($arc === '') {
            flash_set('err', 'ලේකනාගාර අංකය ඇතුළත් කරන්න.');
        } else {
            $dup = $pdo->prepare('SELECT COUNT(*) FROM archive_files WHERE archive_number = ?');
            $dup->execute([$arc]);
            if ($dup->fetchColumn() > 0) {
                flash_set('err', "ලේකනාගාර අංකය \"$arc\" දැනටමත් භාවිතා වී ඇත.");
            } else {
                $u = $pdo->prepare("UPDATE archive_files SET status='archived', archive_number=?, reviewed_by=?, reviewed_at=NOW() WHERE id=? AND status='pending'");
                $u->execute([$arc, $user['id'], $id]);
                flash_set($u->rowCount() ? 'ok' : 'err', $u->rowCount() ? "ලිපිගොනුව ලේඛනාගාරගත කරන ලදී ($arc)." : 'මෙම ලිපිගොනුව දැනටමත් සකසා ඇත.');
            }
        }
    } elseif ($act === 'reject') {
        $u = $pdo->prepare("UPDATE archive_files SET status='rejected', reviewed_by=?, reviewed_at=NOW() WHERE id=? AND status='pending'");
        $u->execute([$user['id'], $id]);
        flash_set($u->rowCount() ? 'ok' : 'err', $u->rowCount() ? 'ලිපිගොනුව ප්‍රතික්ෂේප කරන ලදී.' : 'මෙම ලිපිගොනුව දැනටමත් සකසා ඇත.');
    }
    header('Location: subject_dashboard.php?' . http_build_query(['status' => $_POST['back'] ?? 'pending'])); exit;
}

$status = in_array($_GET['status'] ?? '', ['pending', 'archived', 'rejected'], true) ? $_GET['status'] : 'pending';
$dept   = in_array($_GET['department'] ?? '', DEPARTMENTS, true) ? $_GET['department'] : '';

$counts = ['pending' => 0, 'archived' => 0, 'rejected' => 0];
foreach ($pdo->query('SELECT status, COUNT(*) c FROM archive_files GROUP BY status') as $r) $counts[$r['status']] = (int)$r['c'];

$sql = 'SELECT f.*, u.full_name AS submitter FROM archive_files f JOIN users u ON u.id = f.submitted_by WHERE f.status = ?';
$params = [$status];
if ($dept !== '') { $sql .= ' AND f.department = ?'; $params[] = $dept; }
$sql .= ' ORDER BY f.created_at DESC';
$st = $pdo->prepare($sql); $st->execute($params);
$files = $st->fetchAll();

$pageTitle = 'විෂය නිලධාරී උපකරණ පුවරුව'; $activeNav = 'dashboard'; $pageIcon = 'fa-book-open';
$pageHeading = 'විෂය නිලධාරී උපකරණ පුවරුව'; $pageSub = 'යොමු කළ ලිපිගොනු පරීක්ෂා කර ලේකනාගාර අංක ලබා දෙන්න';
include __DIR__ . '/includes/header.php';
?>
        <div class="stats" style="grid-template-columns:repeat(3,1fr)">
            <div class="stat"><div class="si w"><i class="fa-solid fa-hourglass-half"></i></div><div><b><?= $counts['pending'] ?></b><span>බලාපොරොත්තු වන ගොනු</span></div></div>
            <div class="stat"><div class="si g"><i class="fa-solid fa-box-archive"></i></div><div><b><?= $counts['archived'] ?></b><span>ලේඛනාගාරගත ගොනු</span></div></div>
            <div class="stat"><div class="si r"><i class="fa-solid fa-ban"></i></div><div><b><?= $counts['rejected'] ?></b><span>ප්‍රතික්ෂේප කළ ගොනු</span></div></div>
        </div>

        <div class="tabs">
            <a href="?status=pending"  class="<?= $status === 'pending'  ? 'on' : '' ?>"><i class="fa-solid fa-hourglass-half"></i> බලාපොරොත්තුවෙන්</a>
            <a href="?status=archived" class="<?= $status === 'archived' ? 'on' : '' ?>"><i class="fa-solid fa-box-archive"></i> ලේඛනාගාරගත</a>
            <a href="?status=rejected" class="<?= $status === 'rejected' ? 'on' : '' ?>"><i class="fa-solid fa-ban"></i> ප්‍රතික්ෂේප කළ</a>
        </div>

        <section class="card">
            <div class="res-head">
                <h3><i class="fa-regular fa-rectangle-list"></i> ලිපිගොනු (<?= count($files) ?>)</h3>
                <form method="get" class="inline">
                    <input type="hidden" name="status" value="<?= e($status) ?>">
                    <div class="input" style="width:200px"><i class="fa-solid fa-landmark lead"></i>
                        <select name="department" onchange="this.form.submit()">
                            <option value="">සියලු අංශ</option>
                            <?php foreach (DEPARTMENTS as $d): ?><option value="<?= e($d) ?>" <?= $dept === $d ? 'selected' : '' ?>><?= e($d) ?></option><?php endforeach; ?>
                        </select><i class="fa-solid fa-chevron-down chev"></i></div>
                </form>
            </div>

            <div class="table-wrap">
            <table>
                <thead><tr><th>යොමු කළ දිනය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලිපිගොනු අංකය</th><th>කාල සීමාව</th><th>පිටු</th><th>යොමු කළේ</th>
                    <th><?= $status === 'pending' ? 'ක්‍රියා' : 'ලේකනාගාර අංකය' ?></th></tr></thead>
                <tbody>
                <?php if (!$files): ?><tr><td colspan="9"><div class="empty"><i class="fa-regular fa-folder-open"></i>මෙම ගොනු කිසිවක් නොමැත.</div></td></tr><?php endif; ?>
                <?php foreach ($files as $f): ?>
                    <tr>
                        <td><?= e($f['submit_date']) ?></td>
                        <td class="fname"><a href="view_file.php?id=<?= (int)$f['id'] ?>"><?= e($f['file_name']) ?></a></td>
                        <td><span class="chip"><?= e($f['department']) ?></span></td>
                        <td><?= e($f['subject_number']) ?></td>
                        <td><?= e($f['file_number']) ?></td>
                        <td><?= e($f['start_date']) ?> → <?= e($f['end_date']) ?></td>
                        <td><?= (int)$f['page_count'] ?></td>
                        <td><?= e($f['submitter']) ?></td>
                        <td>
                        <?php if ($status === 'pending'): ?>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="file_id" value="<?= (int)$f['id'] ?>">
                                <input type="hidden" name="back" value="pending">
                                <input type="text" name="archive_number" value="ARC-<?= str_pad((string)$f['id'], 3, '0', STR_PAD_LEFT) ?>" aria-label="ලේකනාගාර අංකය">
                                <button class="btn btn-green btn-sm" name="action" value="approve"><i class="fa-solid fa-check"></i> අනුමත කරන්න</button>
                                <button class="btn btn-red btn-sm" name="action" value="reject" onclick="return confirm('මෙම ලිපිගොනුව ප්‍රතික්ෂේප කරන්නද?')"><i class="fa-solid fa-xmark"></i></button>
                            </form>
                        <?php else: ?>
                            <?= e($f['archive_number'] ?? '-') ?>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
            <?php if ($status === 'pending'): ?><p class="hint">ලේකනාගාර අංකය සංස්කරණය කළ හැක (අද්විතීය විය යුතුය). "අනුමත කරන්න" ඔබන විට ගොනුව පරිශීලකයින්ගේ සෙවීමේ ප්‍රතිඵලවලට ඇතුළත් වේ.</p><?php endif; ?>
        </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
