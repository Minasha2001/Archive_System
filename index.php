<?php
require __DIR__ . '/config.php';

// දැනටමත් login වී ඇත්නම් dashboard එකට
if (!empty($_SESSION['user_id'])) {
    $st = $pdo->prepare('SELECT role FROM users WHERE id = ? AND status = "active"');
    $st->execute([$_SESSION['user_id']]);
    if ($r = $st->fetch()) { header('Location: ' . ROLE_PAGES[$r['role']]); exit; }
}

$error = '';
$info  = '';
$selectedRole = 'user';
if (isset($_GET['registered'])) $info = 'Account created successfully. You can log in once an administrator approves your account.';
if (isset($_GET['reset']))      $info = 'Password changed successfully. Please log in with your new password.';
if (isset($_GET['logout']))     $info = 'You have been logged out.';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role     = $_POST['role'] ?? 'user';
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $selectedRole = in_array($role, ['user', 'admin', 'subject'], true) ? $role : 'user';

    if (($_SESSION['lock_until'] ?? 0) > time()) {
        $error = 'Too many failed attempts. Please try again in a few minutes.';
    } elseif (!csrf_ok()) {
        $error = 'Security check failed. Please reload the page and try again.';
    } elseif ($username === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        $st = $pdo->prepare('SELECT * FROM users WHERE username = ?');
        $st->execute([$username]);
        $u = $st->fetch();
        if (!$u || !password_verify($password, $u['password_hash'])) {
            $_SESSION['fails'] = ($_SESSION['fails'] ?? 0) + 1;
            if ($_SESSION['fails'] >= 5) { $_SESSION['lock_until'] = time() + 300; $_SESSION['fails'] = 0; }
            $error = 'Invalid username or password.';
        } elseif ($u['status'] === 'pending') {
            $error = 'Your account is awaiting administrator approval.';
        } elseif ($u['status'] === 'disabled') {
            $error = 'Your account has been disabled. Please contact the administrator.';
        } elseif ($u['role'] !== $selectedRole) {
            $error = 'This account is not registered as "' . ucfirst($selectedRole) . '". Please select the correct user type.';
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$u['id'];
            unset($_SESSION['fails'], $_SESSION['lock_until']);
            if (!empty($_POST['remember'])) {
                setcookie(session_name(), session_id(), ['expires' => time() + 60 * 60 * 24 * 30, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
            }
            header('Location: ' . ROLE_PAGES[$u['role']]); exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Southern Provincial Council - Archive System</title>
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
   LOGIN CARD (slightly smaller + polished)
   - screen eke madhyayata thiyenawa
   - content ekata anuwa height auto
   - screen eka podi unama vh walin auto kuda wenawa
===================================================== */
.login-card{
  position:absolute;
  top:50%;
  right:4.5%;
  transform:translateY(-50%);
  width:30%;
  min-width:400px;
  max-width:480px;
  height:auto;
  max-height:96vh;
  padding:clamp(30px,5vh,52px) 38px clamp(28px,4.6vh,48px);
  background:linear-gradient(160deg,rgba(255,255,255,.96),rgba(236,246,255,.90));
  border:1px solid rgba(255,255,255,.85);
  border-radius:20px;
  box-shadow:0 26px 60px rgba(0,37,70,.32),0 3px 8px rgba(0,37,70,.10),inset 0 1px 0 rgba(255,255,255,.9);
  z-index:10;
  backdrop-filter:blur(10px);
  overflow-y:auto;
}
/* uda thiyena podi accent line eka */
.login-card::before{content:"";position:absolute;top:0;left:50%;transform:translateX(-50%);width:84px;height:4px;border-radius:0 0 6px 6px;background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}
.login-card::-webkit-scrollbar{width:5px}.login-card::-webkit-scrollbar-thumb{background:#a8c6e5;border-radius:10px}
.leaf-decoration{position:absolute;top:8px;right:14px;width:84px;height:84px;opacity:.10;transform:rotate(-12deg);pointer-events:none}
.leaf-decoration::before,.leaf-decoration::after{content:"";position:absolute;border:2px solid #17669b;border-radius:100% 0 100% 0;width:44px;height:22px}
.leaf-decoration::before{top:16px;right:12px;transform:rotate(-25deg)}
.leaf-decoration::after{top:37px;right:0;transform:rotate(30deg)}

.folder-icon{width:clamp(50px,7.8vh,70px);height:clamp(50px,7.8vh,70px);margin:clamp(2px,.8vh,8px) auto clamp(9px,1.7vh,15px);border-radius:50%;background:linear-gradient(145deg,#0d5290,#0a3d6d);display:flex;align-items:center;justify-content:center;color:#fff;font-size:clamp(22px,3.5vh,32px);box-shadow:0 0 0 5px rgba(11,71,124,.08),0 8px 18px rgba(0,50,90,.22)}
.login-title{text-align:center;font-family:"Libre Baskerville",Georgia,serif;color:#093e70;font-size:clamp(19px,2.5vh,25px);font-weight:700;margin-bottom:clamp(4px,.8vh,8px)}
.login-subtitle{text-align:center;color:#5b7898;font-size:clamp(12px,1.45vh,14px);margin-bottom:clamp(8px,1.5vh,14px)}
.title-line{width:90px;height:3px;margin:0 auto clamp(14px,2.6vh,26px);background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}

/* ALERTS (server messages) */
.alert{display:flex;gap:10px;align-items:flex-start;padding:8px 12px;border-radius:11px;font-size:12px;line-height:1.5;margin-bottom:clamp(8px,1.4vh,14px)}
.alert i{margin-top:2px}
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d}
.alert-ok{background:#e8f6ee;border:1px solid #bfe3cd;color:#14633a}

/* USER TYPE */
.user-type-title{color:#0a3e70;font-size:12.5px;font-weight:600;margin-bottom:clamp(7px,1.2vh,11px);display:flex;align-items:center;gap:9px}
.user-type-title i{font-size:15px}
.user-types{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-bottom:clamp(16px,2.8vh,28px)}
.user-type{height:clamp(36px,4.5vh,44px);border-radius:11px;border:1px solid #c8d9e8;background:rgba(248,252,255,.75);color:#607a98;display:flex;align-items:center;justify-content:center;gap:7px;font-family:"Poppins",sans-serif;font-size:12.5px;font-weight:500;cursor:pointer;transition:.25s ease}
.user-type:hover{border-color:#175487;color:#0a4275;transform:translateY(-1px)}
.user-type.active{background:linear-gradient(135deg,#0b5090,#0a3d6d);color:#fff;border-color:#0b477c;box-shadow:0 6px 12px rgba(4,55,96,.20)}

/* INPUTS */
.input-box{height:clamp(42px,5.2vh,52px);border:1px solid #cbdce9;border-radius:11px;background:rgba(255,255,255,.75);display:flex;align-items:center;padding:0 14px;margin-bottom:clamp(13px,2.4vh,24px);transition:.25s}
.input-box:focus-within{border-color:#2a6a9f;background:#fff;box-shadow:0 0 0 3px rgba(32,103,153,.10)}
.input-box i{width:25px;color:#104d80;font-size:15px}
.input-box input{width:100%;border:none;outline:none;background:transparent;color:#173f64;font-family:"Poppins",sans-serif;font-size:clamp(13px,1.4vh,14.5px)}
.input-box input::placeholder{color:#7a8da3}
.eye{width:22px!important;cursor:pointer;color:#507092!important;font-size:14px!important}

/* REMEMBER / FORGOT */
.options{display:flex;align-items:center;justify-content:space-between;margin:1px 2px clamp(16px,2.8vh,28px);font-size:12.5px}
.remember{display:flex;align-items:center;gap:8px;color:#0a3e70;cursor:pointer}
.remember input{width:16px;height:16px;accent-color:#0a477c;cursor:pointer}
.forgot{color:#084fa2;text-decoration:none;font-weight:500;display:flex;align-items:center;gap:6px}
.forgot:hover{text-decoration:underline}

/* BUTTONS */
.login-button{
  position:relative;
  width:100%;
  height:clamp(44px,5.4vh,54px);
  border:none;
  border-radius:11px;
  background:linear-gradient(135deg,#0b5390,#0a3d6b);
  color:#fff;
  font-family:"Poppins",sans-serif;
  font-size:clamp(14px,1.55vh,15.5px);
  font-weight:600;
  cursor:pointer;
  display:flex;
  align-items:center;
  justify-content:center;          /* "Login" text eka hariyatama madata */
  box-shadow:0 8px 16px rgba(4,57,97,.22);
  transition:.25s;
}
.login-button:hover{transform:translateY(-2px);box-shadow:0 11px 22px rgba(4,57,97,.28)}
.login-button span{position:relative}                          /* text eka madata, icon eka eyata anuwa */
.login-button span i{font-size:17px;position:absolute;right:100%;top:50%;transform:translateY(-50%);margin-right:10px}  /* icon eka text eka langama (left) */
.register-divider{display:flex;align-items:center;gap:14px;margin:clamp(14px,2.6vh,26px) 0}
.register-divider span{flex:1;height:1px;background:#b4cadc}
.register-divider p{color:#0b477c;white-space:nowrap;font-size:11.5px}
.register-button{width:100%;height:clamp(40px,4.9vh,48px);border-radius:11px;border:1px solid #356a98;background:rgba(255,255,255,.45);color:#0752a1;font-family:"Poppins",sans-serif;font-size:13px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:9px;transition:.25s}
.register-button:hover{background:#eaf4fc;border-color:#0b4c83}

/* BOTTOM FEATURES */
.features{position:absolute;bottom:12px;left:4.6%;width:53%;display:flex;align-items:center;z-index:7}
.feature{flex:1;display:flex;align-items:center;gap:13px;color:#fff;padding:0 25px;border-right:1px solid rgba(255,255,255,.35)}
.feature:first-child{padding-left:0}.feature:last-child{border-right:none}
.feature i{font-size:16px;min-width:30px;color:#fff}
.feature span{font-size:10px;line-height:1.5;font-weight:500}

/* FOOTER */
.footer{position:absolute;right:9.6%;bottom:18px;color:#fff;display:flex;align-items:center;gap:14px;font-size:8px;z-index:8}
.footer-divider{width:1px;height:14px;background:rgba(255,255,255,.6)}

/* BOTTOM CURVE (page eke wama pahala koneta) */
.bottom-curve{position:absolute;left:-5%;bottom:-116px;width:64%;height:190px;background:linear-gradient(135deg,#073861,#031f3d);border-radius:50% 50% 0 0;transform:rotate(-2deg);z-index:4;pointer-events:none}

/* RESPONSIVE */
@media (max-width:1200px){
  .login-card{width:40%;min-width:380px;padding:clamp(24px,4vh,40px) 32px}
  .header{left:4%}.quote{left:6%}.features{width:52%}
}
@media (max-width:900px){
  body{overflow-y:auto}
  .page{min-height:100vh;height:auto;background-position:left top}
  .right-overlay{display:none}
  .header{position:relative;top:auto;left:auto;padding:30px;justify-content:center}
  .quote{position:relative;top:auto;left:auto;text-align:center;margin:15px 20px 25px}
  .login-card{position:relative;top:auto;right:auto;transform:none;width:calc(100% - 40px);min-width:0;max-width:520px;margin:20px auto 100px;height:auto;max-height:none;overflow:visible;padding:30px 30px}
  .features{position:relative;left:auto;bottom:auto;width:90%;margin:0 auto 30px}
  .footer,.bottom-curve{display:none}
}
@media (max-width:600px){
  .header{display:block;text-align:center}
  .logo-box{margin:auto}.vertical-line{display:none}
  .institution .sinhala{font-size:18px}.institution .tamil{font-size:14px}.institution .english{font-size:14px}
  .login-card{padding:26px 20px}.login-title{font-size:21px}
  .user-types{gap:7px}.user-type{font-size:11px}
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

    <div class="login-card">
        <div class="leaf-decoration"></div>
        <div class="folder-icon"><i class="fa-regular fa-folder-open"></i></div>
        <h1 class="login-title">Login to Archive System</h1>
        <p class="login-subtitle">Southern Provincial Council Secretariat</p>
        <div class="title-line"></div>

        <?php if ($error): ?><div class="alert alert-err"><i class="fa-solid fa-circle-exclamation"></i><span><?= e($error) ?></span></div><?php endif; ?>
        <?php if ($info):  ?><div class="alert alert-ok"><i class="fa-solid fa-circle-check"></i><span><?= e($info) ?></span></div><?php endif; ?>

        <form method="post" action="index.php" autocomplete="on">
            <?= csrf_field() ?>
            <input type="hidden" name="role" id="role" value="<?= e($selectedRole) ?>">

            <div class="user-type-title"><i class="fa-solid fa-user-group"></i><span>User Type</span></div>
            <div class="user-types">
                <button type="button" class="user-type <?= $selectedRole === 'user' ? 'active' : '' ?>" data-role="user" onclick="selectUser(this)">
                    <i class="fa-solid fa-user"></i><span>User</span>
                </button>
                <button type="button" class="user-type <?= $selectedRole === 'admin' ? 'active' : '' ?>" data-role="admin" onclick="selectUser(this)">
                    <i class="fa-solid fa-shield-halved"></i><span>Admin</span>
                </button>
                <button type="button" class="user-type <?= $selectedRole === 'subject' ? 'active' : '' ?>" data-role="subject" onclick="selectUser(this)">
                    <i class="fa-solid fa-book-open"></i><span>Subject</span>
                </button>
            </div>

            <div class="input-box">
                <i class="fa-solid fa-user"></i>
                <input type="text" id="username" name="username" placeholder="Enter your username" value="<?= e($_POST['username'] ?? '') ?>" autocomplete="username" required>
            </div>

            <div class="input-box">
                <i class="fa-solid fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Enter your password" autocomplete="current-password" required>
                <i class="fa-regular fa-eye-slash eye" id="eyeIcon" onclick="togglePassword()"></i>
            </div>

            <div class="options">
                <label class="remember"><input type="checkbox" name="remember" value="1" checked><span>Remember Me</span></label>
                <a href="forgot_password.php" class="forgot"><i class="fa-solid fa-lock"></i> Forgot Password?</a>
            </div>

            <button type="submit" class="login-button">
                <span><i class="fa-solid fa-arrow-right-to-bracket"></i>Login</span>
            </button>
        </form>

        <div class="register-divider"><span></span><p>Don’t have an account?</p><span></span></div>

        <button type="button" class="register-button" onclick="window.location.href='register.php'">
            <i class="fa-solid fa-user-plus"></i><span>Register New Account</span>
        </button>
    </div><!-- /.login-card -->

    <div class="bottom-curve"></div>

    <div class="features">
        <div class="feature"><i class="fa-solid fa-shield-halved"></i><span>Secure<br>Access</span></div>
        <div class="feature"><i class="fa-regular fa-file-lines"></i><span>Efficient<br>File Management</span></div>
        <div class="feature"><i class="fa-regular fa-folder-open"></i><span>Organized<br>Records</span></div>
        <div class="feature"><i class="fa-solid fa-users"></i><span>For a<br>Better Service</span></div>
    </div>

    <div class="footer">
        <div class="footer-line"></div>
        <span>Southern Provincial Council</span>
        <div class="footer-divider"></div>
        <span>Archive Management System</span>
    </div>
</div><!-- /.page -->

<script>
function selectUser(button) {
    document.querySelectorAll(".user-type").forEach(function (b) { b.classList.remove("active"); });
    button.classList.add("active");
    document.getElementById("role").value = button.dataset.role;
}
function togglePassword() {
    const p = document.getElementById("password"), eye = document.getElementById("eyeIcon");
    if (p.type === "password") { p.type = "text"; eye.classList.replace("fa-eye-slash", "fa-eye"); }
    else { p.type = "password"; eye.classList.replace("fa-eye", "fa-eye-slash"); }
}
</script>
</body>
</html>