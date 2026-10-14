<?php
require __DIR__ . '/config.php';
$user = require_login($pdo);
$pageTitle = 'මගේ පැතිකඩ'; $activeNav = 'profile'; $pageIcon = 'fa-user';
$pageHeading = 'මගේ පැතිකඩ'; $pageSub = 'ඔබගේ ගිණුම් විස්තර';
include __DIR__ . '/includes/header.php';
$rows = [
    'සම්පූර්ණ නම' => $user['full_name'], 'පරිශීලක නාමය' => $user['username'], 'පරිශීලක වර්ගය' => ROLE_LABELS[$user['role']],
    'අංශය' => $user['department'], 'තනතුර' => $user['designation'], 'විෂය අංකය' => $user['subject_number'],
    'NIC අංකය' => $user['nic'], 'ඊමේල් ලිපිනය' => $user['email'], 'ගිණුම සෑදූ දිනය' => substr($user['created_at'], 0, 10),
];
?>
        <section class="card">
            <h3><i class="fa-solid fa-id-card"></i> ගිණුම් විස්තර</h3>
            <dl class="details">
                <?php foreach ($rows as $k => $v): ?><div><dt><?= e($k) ?></dt><dd><?= e($v) ?></dd></div><?php endforeach; ?>
            </dl>
            <div class="actions"><a class="btn btn-primary" href="change_password.php"><i class="fa-solid fa-lock"></i> මුරපදය වෙනස් කරන්න</a></div>
        </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
