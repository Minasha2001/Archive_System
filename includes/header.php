<?php
/* Shared dashboard layout (top). Required: $user, $pageTitle, $activeNav, $pageHeading, $pageSub, $pageIcon */
$dash = ROLE_PAGES[$user['role']];
$nav = [
    'dashboard' => [$dash,                'fa-house',              'උපකරණ පුවරුව'],
    'profile'   => ['profile.php',         'fa-user',               'මගේ පැතිකඩ'],
    'password'  => ['change_password.php', 'fa-lock',               'මුරපදය වෙනස් කිරීම'],
];
?>
<!DOCTYPE html>
<html lang="si">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> - ලේකනාගාර පද්ධතිය</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
<aside class="sidebar">
    <div class="brand">
        <img src="logo.webp" alt="" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
        <div class="crest"><i class="fa-solid fa-landmark"></i></div>
        <div>
            <h1>දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</h1>
            <p>ලේකනාගාර ලිපිගොනු පද්ධතිය</p>
        </div>
    </div>
    <nav class="nav">
        <?php foreach ($nav as $key => [$href, $icon, $label]): ?>
            <a href="<?= e($href) ?>" class="<?= $activeNav === $key ? 'active' : '' ?>"><i class="fa-solid <?= $icon ?>"></i> <?= $label ?></a>
        <?php endforeach; ?>
        <div class="sep"></div>
        <a href="logout.php"><i class="fa-solid fa-right-from-bracket"></i> ඉවත් වන්න</a>
    </nav>
    <div class="tagline">කාර්යක්ෂම ලේඛන,<br>සුබ හෙට දිනයක් උදෙසා</div>
</aside>

<div class="main">
    <header class="topbar">
        <button class="hamburger" onclick="document.body.classList.toggle('menu-open')" aria-label="මෙනුව"><i class="fa-solid fa-bars"></i></button>
        <div class="user">
            <div class="avatar"><i class="fa-solid fa-user"></i></div>
            <div><div class="nm"><?= e($user['full_name']) ?></div><div class="rl"><?= e(ROLE_LABELS[$user['role']]) ?></div></div>
        </div>
    </header>
    <main class="content">
        <div class="page-head">
            <div class="ico"><i class="fa-solid <?= e($pageIcon) ?>"></i></div>
            <div><h2><?= e($pageHeading) ?></h2><p><?= e($pageSub) ?></p></div>
        </div>
        <?php if ($fl = flash_get()): ?>
            <div class="alert alert-<?= $fl[0] === 'ok' ? 'ok' : 'err' ?>">
                <i class="fa-solid <?= $fl[0] === 'ok' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i><div><?= e($fl[1]) ?></div>
            </div>
        <?php endif; ?>
