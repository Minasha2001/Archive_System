<?php
require __DIR__ . '/config.php';
$user = require_login($pdo);
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cur = $_POST['current_password'] ?? ''; $p1 = $_POST['new_password'] ?? ''; $p2 = $_POST['confirm_password'] ?? '';
    if (!csrf_ok())                                         $errors[] = 'ආරක්ෂක පරීක්ෂාව අසාර්ථකයි. නැවත උත්සාහ කරන්න.';
    if (!password_verify($cur, $user['password_hash']))     $errors[] = 'වත්මන් මුරපදය වැරදියි.';
    if (strlen($p1) < 6)                                    $errors[] = 'නව මුරපදය අවම වශයෙන් අක්ෂර 6ක් විය යුතුය.';
    if ($p1 !== $p2)                                        $errors[] = 'නව මුරපදය සහ තහවුරු කිරීම ගැලපෙන්නේ නැත.';
    if (!$errors) {
        $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($p1, PASSWORD_DEFAULT), $user['id']]);
        session_regenerate_id(true);
        flash_set('ok', 'මුරපදය සාර්ථකව වෙනස් කරන ලදී.');
        header('Location: change_password.php'); exit;
    }
}
$pageTitle = 'මුරපදය වෙනස් කිරීම'; $activeNav = 'password'; $pageIcon = 'fa-lock';
$pageHeading = 'මුරපදය වෙනස් කිරීම'; $pageSub = 'ඔබගේ ගිණුම සුරක්ෂිතව තබා ගන්න';
include __DIR__ . '/includes/header.php';
?>
        <?php if ($errors): ?>
            <div class="alert alert-err"><i class="fa-solid fa-triangle-exclamation"></i><div><ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div></div>
        <?php endif; ?>
        <section class="card" style="max-width:560px">
            <h3><i class="fa-solid fa-lock"></i> නව මුරපදයක් සකසන්න</h3>
            <form method="post">
                <?= csrf_field() ?>
                <div class="grid" style="grid-template-columns:1fr">
                    <div class="field"><label>වත්මන් මුරපදය</label><div class="input"><i class="fa-solid fa-key lead"></i><input type="password" name="current_password" autocomplete="current-password" required></div></div>
                    <div class="field"><label>නව මුරපදය</label><div class="input"><i class="fa-solid fa-lock lead"></i><input type="password" name="new_password" autocomplete="new-password" required></div></div>
                    <div class="field"><label>නව මුරපදය තහවුරු කරන්න</label><div class="input"><i class="fa-solid fa-lock lead"></i><input type="password" name="confirm_password" autocomplete="new-password" required></div></div>
                </div>
                <div class="actions"><button class="btn btn-green" type="submit"><i class="fa-solid fa-floppy-disk"></i> සුරකින්න</button></div>
            </form>
        </section>
<?php include __DIR__ . '/includes/footer.php'; ?>
