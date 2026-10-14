<?php
require __DIR__ . '/config.php';
$user = require_role($pdo, 'admin');

/* ---------- User management ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id  = (int)($_POST['user_id'] ?? 0);
    $act = $_POST['action'] ?? '';
    if (!csrf_ok()) {
        flash_set('err', 'ආරක්ෂක පරීක්ෂාව අසාර්ථකයි. නැවත උත්සාහ කරන්න.');
    } elseif ($id === (int)$user['id']) {
        flash_set('err', 'ඔබේම ගිණුම මෙතැනින් වෙනස් කළ නොහැක.');
    } else {
        $map = ['approve' => ['active', 'ගිණුම අනුමත කරන ලදී.'], 'enable' => ['active', 'ගිණුම සක්‍රීය කරන ලදී.'], 'disable' => ['disabled', 'ගිණුම අක්‍රීය කරන ලදී.']];
        if (isset($map[$act])) {
            $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$map[$act][0], $id]);
            flash_set('ok', $map[$act][1]);
        }
    }
    header('Location: admin_dashboard.php?' . http_build_query(['tab' => $_POST['back'] ?? 'users'])); exit;
}

$tab = in_array($_GET['tab'] ?? '', ['users', 'files'], true) ? $_GET['tab'] : 'users';

$stats = [
    'users'    => (int)$pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
    'pendingU' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE status='pending'")->fetchColumn(),
    'pendingF' => (int)$pdo->query("SELECT COUNT(*) FROM archive_files WHERE status='pending'")->fetchColumn(),
    'archived' => (int)$pdo->query("SELECT COUNT(*) FROM archive_files WHERE status='archived'")->fetchColumn(),
];
$users = $pdo->query("SELECT * FROM users ORDER BY FIELD(status,'pending','active','disabled'), created_at DESC")->fetchAll();
$files = $pdo->query('SELECT f.*, u.full_name AS submitter FROM archive_files f JOIN users u ON u.id = f.submitted_by ORDER BY f.created_at DESC LIMIT 100')->fetchAll();

$ust = ['pending' => ['අනුමැතිය බලාපොරොත්තුවෙන්', 'st-pending'], 'active' => ['සක්‍රීය', 'st-ok'], 'disabled' => ['අක්‍රීය', 'st-no']];
$fst = ['pending' => ['බලාපොරොත්තුවෙන්', 'st-pending'], 'archived' => ['ලේඛනාගාරගත', 'st-ok'], 'rejected' => ['ප්‍රතික්ෂේප', 'st-no']];

$pageTitle = 'පරිපාලක උපකරණ පුවරුව'; $activeNav = 'dashboard'; $pageIcon = 'fa-shield-halved';
$pageHeading = 'පරිපාලක උපකරණ පුවරුව'; $pageSub = 'පරිශීලකයින් සහ ලිපිගොනු කළමනාකරණය';
include __DIR__ . '/includes/header.php';
?>
        <div class="stats">
            <div class="stat"><div class="si"><i class="fa-solid fa-users"></i></div><div><b><?= $stats['users'] ?></b><span>සියලු පරිශීලකයින්</span></div></div>
            <div class="stat"><div class="si w"><i class="fa-solid fa-user-clock"></i></div><div><b><?= $stats['pendingU'] ?></b><span>අනුමැතිය බලාපොරොත්තුවෙන්</span></div></div>
            <div class="stat"><div class="si w"><i class="fa-solid fa-hourglass-half"></i></div><div><b><?= $stats['pendingF'] ?></b><span>සකසන්නට ඇති ගොනු</span></div></div>
            <div class="stat"><div class="si g"><i class="fa-solid fa-box-archive"></i></div><div><b><?= $stats['archived'] ?></b><span>ලේඛනාගාරගත ගොනු</span></div></div>
        </div>

        <div class="tabs">
            <a href="?tab=users" class="<?= $tab === 'users' ? 'on' : '' ?>"><i class="fa-solid fa-users"></i> පරිශීලකයින්</a>
            <a href="?tab=files" class="<?= $tab === 'files' ? 'on' : '' ?>"><i class="fa-solid fa-folder-open"></i> සියලු ලිපිගොනු</a>
        </div>

<?php if ($tab === 'users'): ?>
        <section class="card">
            <h3><i class="fa-solid fa-users"></i> පරිශීලක කළමනාකරණය</h3>
            <div class="table-wrap">
            <table>
                <thead><tr><th>නම</th><th>පරිශීලක නාමය</th><th>වර්ගය</th><th>අංශය</th><th>තනතුර</th><th>විෂය අංකය</th><th>NIC</th><th>තත්ත්වය</th><th>ක්‍රියා</th></tr></thead>
                <tbody>
                <?php foreach ($users as $u): [$lbl, $cls] = $ust[$u['status']]; ?>
                    <tr>
                        <td class="fname"><?= e($u['full_name']) ?><div style="font-weight:400;color:var(--muted);font-size:12px"><?= e($u['email']) ?></div></td>
                        <td><?= e($u['username']) ?></td>
                        <td><?= e(ROLE_LABELS[$u['role']]) ?></td>
                        <td><span class="chip"><?= e($u['department']) ?></span></td>
                        <td><?= e($u['designation']) ?></td>
                        <td><?= e($u['subject_number']) ?></td>
                        <td><?= e($u['nic']) ?></td>
                        <td><span class="chip <?= $cls ?>"><?= e($lbl) ?></span></td>
                        <td>
                        <?php if ((int)$u['id'] !== (int)$user['id']): ?>
                            <form method="post" class="inline">
                                <?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="back" value="users">
                                <?php if ($u['status'] === 'pending'): ?><button class="btn btn-green btn-sm" name="action" value="approve"><i class="fa-solid fa-check"></i> අනුමත කරන්න</button>
                                <?php elseif ($u['status'] === 'active'): ?><button class="btn btn-red btn-sm" name="action" value="disable" onclick="return confirm('මෙම ගිණුම අක්‍රීය කරන්නද?')"><i class="fa-solid fa-user-slash"></i> අක්‍රීය කරන්න</button>
                                <?php else: ?><button class="btn btn-primary btn-sm" name="action" value="enable"><i class="fa-solid fa-user-check"></i> සක්‍රීය කරන්න</button><?php endif; ?>
                            </form>
                        <?php else: ?><span class="hint">ඔබ</span><?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
<?php else: ?>
        <section class="card">
            <h3><i class="fa-solid fa-folder-open"></i> සියලු ලිපිගොනු (අවසන් 100)</h3>
            <div class="table-wrap">
            <table>
                <thead><tr><th>දිනය</th><th>ලිපිගොනුවේ නම</th><th>අංශය</th><th>විෂය අංකය</th><th>ලේකනාගාර අංකය</th><th>පිටු</th><th>යොමු කළේ</th><th>තත්ත්වය</th></tr></thead>
                <tbody>
                <?php foreach ($files as $f): [$lbl, $cls] = $fst[$f['status']]; ?>
                    <tr>
                        <td><?= e($f['submit_date']) ?></td>
                        <td class="fname"><a href="view_file.php?id=<?= (int)$f['id'] ?>"><?= e($f['file_name']) ?></a></td>
                        <td><span class="chip"><?= e($f['department']) ?></span></td>
                        <td><?= e($f['subject_number']) ?></td><td><?= e($f['archive_number'] ?? '-') ?></td>
                        <td><?= (int)$f['page_count'] ?></td><td><?= e($f['submitter']) ?></td>
                        <td><span class="chip <?= $cls ?>"><?= e($lbl) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            </div>
        </section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
