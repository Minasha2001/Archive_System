<?php
require __DIR__ . '/config.php';

/*
 * 2 step flow:
 *  1) username + registered email ගැලපේ නම් one-time token එකක් සාදා ?token=... වෙත යොමු කරයි
 *  2) නව password එක ඇතුළත් කිරීම
 * (Mail server එකක් ඇත්නම් token link එක email කිරීමට වෙනස් කළ හැක)
 */
$errors = [];
$token  = $_GET['token'] ?? '';
$mode   = 'request';
$resetUserId = null;

if ($token !== '') {
    $st = $pdo->prepare('SELECT user_id FROM password_resets WHERE token_hash = ? AND expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch();
    if ($row) { $mode = 'reset'; $resetUserId = (int)$row['user_id']; }
    else      { $errors[] = 'This reset link is invalid or has expired. Please request a new one.'; }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_ok()) {
        $errors[] = 'Security check failed. Please reload the page and try again.';
    } elseif (($_POST['action'] ?? '') === 'request') {
        $username = trim($_POST['username'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $st = $pdo->prepare('SELECT id FROM users WHERE username = ? AND email = ? AND status = "active"');
        $st->execute([$username, $email]);
        if ($u = $st->fetch()) {
            $raw = bin2hex(random_bytes(24));
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$u['id']]);
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at) VALUES (?,?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))')
                ->execute([$u['id'], hash('sha256', $raw)]);
            header('Location: forgot_password.php?token=' . $raw); exit;
        }
        $errors[] = 'No active account matches that username and email address.';
    } elseif (($_POST['action'] ?? '') === 'reset' && $mode === 'reset') {
        $p1 = $_POST['password'] ?? ''; $p2 = $_POST['confirm_password'] ?? '';
        if (strlen($p1) < 6) $errors[] = 'Password must contain at least 6 characters.';
        if ($p1 !== $p2)     $errors[] = 'Password and Confirm Password do not match.';
        if (!$errors) {
            $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($p1, PASSWORD_DEFAULT), $resetUserId]);
            $pdo->prepare('DELETE FROM password_resets WHERE user_id = ?')->execute([$resetUserId]);
            header('Location: index.php?reset=1'); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Forgot Password - Southern Provincial Council Archive System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Poppins:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:"Poppins",sans-serif;min-height:100vh;overflow:hidden;background:#063765}
.page{width:100%;height:100vh;position:relative;overflow:hidden;
  background:linear-gradient(90deg,rgba(255,255,255,.05) 0%,rgba(255,255,255,.02) 45%,rgba(2,45,86,.18) 100%),url("background1.png");
  background-size:cover;background-position:left center;background-repeat:no-repeat}
.right-overlay{position:absolute;top:0;right:0;width:31%;height:100%;
  background:linear-gradient(120deg,rgba(255,255,255,0) 0%,rgba(5,53,93,.20) 25%,rgba(2,39,73,.82) 70%,rgba(1,34,65,.95) 100%);pointer-events:none}

/* HEADER */
.header{position:absolute;top:40px;left:24%;display:flex;align-items:center;z-index:5}
.logo-box{width:100px;height:130px;display:flex;align-items:center;justify-content:center}
.logo-box img{max-width:80px;max-height:90px;object-fit:contain}
.vertical-line{width:1.2px;height:100px;background:#073d70;margin:0 27px 0 15px}
.institution{color:#083c6e}
.institution .sinhala{font-family:"Noto Sans Sinhala","Poppins",sans-serif;font-size:18px;font-weight:700;line-height:1.2}
.institution .tamil{font-size:18px;font-weight:600;margin-top:3px}
.institution .english{font-family:Georgia,serif;font-size:18px;font-weight:600;margin-top:4px}

/* QUOTE */
.quote{position:absolute;top:180px;left:34%;color:#0b4b7f;font-family:Georgia,"Times New Roman",serif;font-size:16px;font-style:italic;z-index:5}
.quote-line{width:77px;height:3px;background:linear-gradient(to right,#07518d 0 50%,#76c7ef 50% 100%);margin:20px auto 0}

/* =====================================================
   FORGOT CARD (index.php login card ekata samanai)
   - screen eke madhyayata
   - content ekata anuwa height auto
===================================================== */
.forgot-card{
  position:absolute;
  top:50%;
  right:4.5%;
  transform:translateY(-50%);
  width:30%;
  min-width:400px;
  max-width:480px;
  height:auto;
  max-height:96vh;
  padding:clamp(40px,7vh,68px) 38px clamp(38px,6.5vh,64px);
  background:linear-gradient(160deg,rgba(255,255,255,.96),rgba(236,246,255,.90));
  border:1px solid rgba(255,255,255,.85);
  border-radius:20px;
  box-shadow:0 26px 60px rgba(0,37,70,.32),0 3px 8px rgba(0,37,70,.10),inset 0 1px 0 rgba(255,255,255,.9);
  z-index:10;
  backdrop-filter:blur(10px);
  overflow-y:auto;
}
.forgot-card::before{content:"";position:absolute;top:0;left:50%;transform:translateX(-50%);width:84px;height:4px;border-radius:0 0 6px 6px;background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}
.forgot-card::-webkit-scrollbar{width:5px}.forgot-card::-webkit-scrollbar-thumb{background:#a8c6e5;border-radius:10px}

.folder-icon{width:clamp(50px,7.8vh,70px);height:clamp(50px,7.8vh,70px);margin:clamp(2px,.8vh,8px) auto clamp(9px,1.7vh,15px);border-radius:50%;background:linear-gradient(145deg,#0d5290,#0a3d6d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:clamp(22px,3.5vh,32px);box-shadow:0 0 0 5px rgba(11,71,124,.08),0 8px 18px rgba(0,50,90,.22)}
.forgot-title{text-align:center;font-family:"Libre Baskerville",Georgia,serif;color:#093e70;font-size:clamp(19px,2.5vh,25px);font-weight:700;margin-bottom:clamp(4px,.8vh,8px)}
.forgot-subtitle{text-align:center;color:#5b7898;font-size:clamp(12px,1.45vh,14px);margin-bottom:clamp(8px,1.5vh,14px)}
.title-line{width:90px;height:3px;margin:0 auto clamp(12px,2.2vh,20px);background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}
.instruction{color:#465d73;font-size:12.5px;line-height:1.7;text-align:center;margin-bottom:clamp(12px,2.2vh,20px)}

/* ALERTS (server messages) */
.alert{display:flex;gap:10px;align-items:flex-start;padding:8px 12px;border-radius:11px;font-size:12px;line-height:1.5;margin-bottom:clamp(8px,1.4vh,14px)}
.alert i{margin-top:2px}
.alert ul{margin:0 0 0 16px}
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d}

/* INPUTS */
.form-label{display:block;color:#0a3e70;font-size:12.5px;font-weight:600;margin-bottom:clamp(5px,.9vh,8px)}
.input-box{height:clamp(42px,5.2vh,52px);border:1px solid #cbdce9;border-radius:11px;background:rgba(255,255,255,.75);display:flex;align-items:center;padding:0 14px;margin-bottom:clamp(12px,2.2vh,20px);transition:.25s}
.input-box:focus-within{border-color:#2a6a9f;background:#fff;box-shadow:0 0 0 3px rgba(32,103,153,.10)}
.input-box i{width:25px;color:#104d80;font-size:15px}
.input-box input{width:100%;border:none;outline:none;background:transparent;color:#173f64;font-family:"Poppins",sans-serif;font-size:clamp(13px,1.4vh,14.5px)}
.input-box input::placeholder{color:#7a8da3}

/* BUTTONS */
.reset-button{width:100%;height:clamp(44px,5.4vh,54px);margin-top:clamp(2px,.6vh,6px);border:none;border-radius:11px;background:linear-gradient(135deg,#0b5390,#0a3d6b);color:#fff;font-family:"Poppins",sans-serif;font-size:clamp(14px,1.55vh,15.5px);font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:11px;box-shadow:0 8px 16px rgba(4,57,97,.22);transition:.25s}
.reset-button:hover{transform:translateY(-2px);box-shadow:0 11px 22px rgba(4,57,97,.28)}
.reset-button i{font-size:16px}
.back-login{display:flex;align-items:center;justify-content:center;gap:8px;margin-top:clamp(12px,2.2vh,20px);color:#084fa2;font-size:13px;font-weight:600;text-decoration:none}
.back-login:hover{text-decoration:underline}

.security-note{display:flex;align-items:flex-start;gap:10px;margin-top:clamp(12px,2.2vh,20px);padding:10px 12px;background:rgba(218,235,251,.65);border-radius:11px;color:#526b83;font-size:11px;line-height:1.6}
.security-note i{color:#356d9d;margin-top:2px}

/* BOTTOM FEATURES */
.features{position:absolute;bottom:12px;left:4.6%;width:53%;display:flex;align-items:center;z-index:7}
.feature{flex:1;display:flex;align-items:center;gap:13px;color:#fff;padding:0 25px;border-right:1px solid rgba(255,255,255,.35)}
.feature:first-child{padding-left:0}.feature:last-child{border-right:none}
.feature i{font-size:16px;min-width:30px;color:#fff}
.feature span{font-size:10px;line-height:1.5;font-weight:500}

/* FOOTER */
.footer{position:absolute;right:9.6%;bottom:54px;color:#fff;display:flex;align-items:center;gap:14px;font-size:8px;z-index:8}
.footer-divider{width:1px;height:14px;background:rgba(255,255,255,.6)}

/* BOTTOM CURVE */
.bottom-curve{position:absolute;left:-5%;bottom:-116px;width:64%;height:190px;background:linear-gradient(135deg,#073861,#031f3d);border-radius:50% 50% 0 0;transform:rotate(-2deg);z-index:4}

/* RESPONSIVE */
@media (min-width:901px) and (max-height:760px){
  .forgot-subtitle,.security-note{display:none}
}
@media (min-width:901px) and (max-height:620px){
  .folder-icon{display:none}
}
@media (max-width:1200px){
  .forgot-card{width:40%;min-width:380px;padding:clamp(34px,6vh,56px) 32px}
  .header{left:4%}.quote{left:6%}.features{width:52%}
}
@media (max-width:900px){
  body{overflow-y:auto}
  .page{min-height:100vh;height:auto;background-position:left top}
  .right-overlay{display:none}
  .header{position:relative;top:auto;left:auto;padding:30px;justify-content:center}
  .quote{position:relative;top:auto;left:auto;text-align:center;margin:15px 20px 25px}
  .forgot-card{position:relative;top:auto;right:auto;transform:none;width:calc(100% - 40px);min-width:0;max-width:520px;margin:20px auto 100px;height:auto;max-height:none;overflow:visible;padding:30px 30px}
  .features{position:relative;left:auto;bottom:auto;width:90%;margin:0 auto 30px}
  .footer,.bottom-curve{display:none}
}
@media (max-width:600px){
  .header{display:block;text-align:center}
  .logo-box{margin:auto}.vertical-line{display:none}
  .institution .sinhala{font-size:18px}.institution .tamil{font-size:14px}.institution .english{font-size:14px}
  .forgot-card{padding:26px 20px}.forgot-title{font-size:21px}
  .features{display:grid;grid-template-columns:1fr 1fr;gap:20px}
  .feature{border:none;padding:0}
}
</style>
</head>
<body>
<div class="page">
    <div class="right-overlay"></div>

    <div class="header">
        <div class="logo-box"><img src="logo.webp" alt="Southern Provincial Council Logo"></div>
        <div class="vertical-line"></div>
        <div class="institution">
            <div class="sinhala">දකුණු පළාත් සභා ලේකම් කාර්යාලය</div>
            <div class="tamil">தென் மாகாண சட்டசபை அலுவலகம்</div>
            <div class="english">Southern Provincial Council Secretariat</div>
        </div>
    </div>

    <div class="quote">“Efficient Administration for a Better Tomorrow”<div class="quote-line"></div></div>

    <div class="forgot-card">
        <div class="folder-icon"><i class="fa-solid <?= $mode === 'reset' ? 'fa-key' : 'fa-lock' ?>"></i></div>

        <?php if ($mode === 'reset'): ?>
            <h1 class="forgot-title">Reset Password</h1>
            <p class="forgot-subtitle">Southern Provincial Council Secretariat</p>
            <div class="title-line"></div>
            <p class="instruction">Identity confirmed. Enter a new password for your account. This link expires in 15 minutes.</p>
        <?php else: ?>
            <h1 class="forgot-title">Forgot Password?</h1>
            <p class="forgot-subtitle">Southern Provincial Council Secretariat</p>
            <div class="title-line"></div>
            <p class="instruction">Enter your registered email address and username below. We will help you reset your password.</p>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i>
                <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul></div>
        <?php endif; ?>

        <?php if ($mode === 'reset'): ?>
        <form method="post" action="forgot_password.php?token=<?= e($token) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="reset">

            <label class="form-label" for="password">New Password</label>
            <div class="input-box"><i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter new password" autocomplete="new-password" required></div>

            <label class="form-label" for="confirm_password">Confirm New Password</label>
            <div class="input-box"><i class="fa-solid fa-lock"></i>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password" autocomplete="new-password" required></div>

            <button type="submit" class="reset-button"><i class="fa-solid fa-key"></i><span>Change Password</span></button>
        </form>
        <?php else: ?>
        <form method="post" action="forgot_password.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="request">

            <label class="form-label" for="username">Username</label>
            <div class="input-box"><i class="fa-solid fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required></div>

            <label class="form-label" for="email">Registered Email Address</label>
            <div class="input-box"><i class="fa-solid fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Enter your email address" value="<?= e($_POST['email'] ?? '') ?>" required></div>

            <button type="submit" class="reset-button"><i class="fa-solid fa-paper-plane"></i><span>Reset Password</span></button>
        </form>
        <?php endif; ?>

        <a href="index.php" class="back-login"><i class="fa-solid fa-arrow-left"></i> Back to Login</a>

        <div class="security-note">
            <i class="fa-solid fa-shield-halved"></i>
            <span>Your account information is important. Please use the email address registered with your account.</span>
        </div>
    </div>

    <div class="bottom-curve"></div>

    <div class="features">
        <div class="feature"><i class="fa-solid fa-shield-halved"></i><span>Secure<br>Access</span></div>
        <div class="feature"><i class="fa-regular fa-file-lines"></i><span>Efficient<br>File Management</span></div>
        <div class="feature"><i class="fa-regular fa-folder-open"></i><span>Organized<br>Records</span></div>
        <div class="feature"><i class="fa-solid fa-users"></i><span>For a<br>Better Service</span></div>
    </div>

    <div class="footer">
        <span>Southern Provincial Council</span>
        <div class="footer-divider"></div>
        <span>Archive Management System</span>
    </div>
</div>
</body>
</html>
