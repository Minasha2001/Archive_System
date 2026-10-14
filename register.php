<?php
require __DIR__ . '/config.php';

$errors = [];
$f = ['full_name' => '', 'nic' => '', 'email' => '', 'designation' => '', 'subject_number' => '', 'role' => '', 'department' => '', 'username' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($f as $k => $_) $f[$k] = trim($_POST[$k] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $f['nic'] = strtoupper($f['nic']);

    if (!csrf_ok())                                   $errors[] = 'Security check failed. Please reload the page and try again.';
    if ($f['full_name'] === '' || mb_strlen($f['full_name']) > 150) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^(\d{9}[VX]|\d{12})$/', $f['nic'])) $errors[] = 'Please enter a valid NIC number (e.g. 199012345678 or 901234567V).';
    if (!filter_var($f['email'], FILTER_VALIDATE_EMAIL))  $errors[] = 'Please enter a valid email address.';
    if ($f['designation'] === '')                     $errors[] = 'Please enter your designation.';
    if ($f['subject_number'] === '')                  $errors[] = 'Please enter your subject number.';
    if (!in_array($f['role'], ['user', 'admin', 'subject'], true)) $errors[] = 'Please select a user type.';
    if (!in_array($f['department'], DEPARTMENTS, true))            $errors[] = 'Please select a department.';
    if (!preg_match('/^[A-Za-z0-9_.]{4,30}$/', $f['username']))    $errors[] = 'Username must be 4-30 characters (letters, numbers, _ or .).';
    if (strlen($password) < 6)                        $errors[] = 'Password must contain at least 6 characters.';
    if ($password !== $confirm)                       $errors[] = 'Password and Confirm Password do not match.';

    if (!$errors) {
        $chk = $pdo->prepare('SELECT username, nic, email FROM users WHERE username = ? OR nic = ? OR email = ?');
        $chk->execute([$f['username'], $f['nic'], $f['email']]);
        foreach ($chk->fetchAll() as $row) {
            if (strcasecmp($row['username'], $f['username']) === 0) $errors[] = 'This username is already taken.';
            if ($row['nic'] === $f['nic'])                           $errors[] = 'An account with this NIC number already exists.';
            if (strcasecmp($row['email'], $f['email']) === 0)        $errors[] = 'An account with this email already exists.';
        }
        $errors = array_values(array_unique($errors));
    }

    if (!$errors) {
        $ins = $pdo->prepare(
            'INSERT INTO users (full_name, nic, email, designation, subject_number, role, department, username, password_hash, status)
             VALUES (?,?,?,?,?,?,?,?,?, "pending")'
        );
        $ins->execute([$f['full_name'], $f['nic'], $f['email'], $f['designation'], $f['subject_number'], $f['role'],
                       $f['department'], $f['username'], password_hash($password, PASSWORD_DEFAULT)]);
        header('Location: index.php?registered=1'); exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register - Archive File Management System</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Libre+Baskerville:wght@400;700&family=Poppins:wght@400;500;600;700&family=Noto+Sans+Sinhala:wght@400;600;700&family=Yaldevi:wght@400;500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
html{width:100%;height:100%}
body{width:100%;height:100vh;font-family:"Poppins",sans-serif;overflow:hidden;background:#063765}

/* PAGE (index.php ekata samanai) */
.page{height:100vh;width:100%;position:relative;overflow:hidden;
  display:flex;justify-content:flex-end;align-items:center;
  padding:16px 4.5% 16px 20px;
  background:linear-gradient(90deg,rgba(255,255,255,.05) 0%,rgba(255,255,255,.02) 45%,rgba(2,45,86,.18) 100%),url("background1.png");
  background-size:cover;background-position:left center;background-repeat:no-repeat}
.right-overlay{position:absolute;top:0;right:0;width:34%;height:100%;
  background:linear-gradient(120deg,rgba(255,255,255,0) 0%,rgba(5,53,93,.12) 25%,rgba(2,39,73,.52) 70%,rgba(1,34,65,.68) 100%);pointer-events:none}

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
   REGISTER CARD (podi + index login card ekata samana)
   - height: auto  -> content ekata hariyatama
   - max-height    -> screen ekata wada loku unoth scroll wenawa
===================================================== */
.register-card{
  position:relative;
  z-index:10;
  width:min(440px,52vw);
  min-width:440px;
  height:auto;
  max-height:calc(100vh - 56px);
  overflow-y:auto;
  padding:clamp(20px,3.4vh,36px) 30px clamp(16px,2.6vh,28px);
  background:linear-gradient(160deg,rgba(255,255,255,.96),rgba(236,246,255,.90));
  border:1px solid rgba(255,255,255,.85);
  border-radius:20px;
  box-shadow:0 26px 60px rgba(0,37,70,.32),0 3px 8px rgba(0,37,70,.10),inset 0 1px 0 rgba(255,255,255,.9);
  backdrop-filter:blur(10px);
  animation:cardAppear .6s ease;
}
@keyframes cardAppear{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
.register-card::before{content:"";position:absolute;top:0;left:50%;transform:translateX(-50%);width:84px;height:4px;border-radius:0 0 6px 6px;background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}

.register-card::-webkit-scrollbar{width:5px}.register-card::-webkit-scrollbar-thumb{background:#a8c6e5;border-radius:10px}
.register-header{text-align:center;margin-bottom:clamp(10px,1.8vh,18px)}
.register-icon{width:clamp(38px,6vh,54px);height:clamp(38px,6vh,54px);margin:0 auto clamp(6px,1.2vh,12px);border-radius:50%;display:flex;align-items:center;justify-content:center;background:linear-gradient(145deg,#0d5290,#0a3d6d);color:#fff;font-size:clamp(16px,2.6vh,23px);box-shadow:0 0 0 5px rgba(11,71,124,.08),0 8px 18px rgba(0,50,90,.22)}
.register-header h1{font-family:"Libre Baskerville",Georgia,serif;font-size:clamp(16px,2.1vh,21px);color:#093e70;margin-bottom:3px}
.register-header p{color:#5b7898;font-size:12px}
.title-line{width:90px;height:3px;margin:clamp(8px,1.4vh,14px) auto 0;background:linear-gradient(to right,#0a3e70 0 52%,#79c7ee 52% 100%)}

/* ALERT (server validation) */
.alert-err{background:#fdecea;border:1px solid #f3c2bc;color:#8e271d;border-radius:11px;padding:8px 12px;font-size:12px;line-height:1.5;margin-bottom:10px}
.alert-err ul{margin:3px 0 0 18px}

/* FORM */
.form-grid{display:flex;flex-direction:column;gap:clamp(7px,1.3vh,13px)}
.input-group{width:100%;min-width:0}
.input-group label{display:block;font-size:11.5px;font-weight:600;color:#34495e;margin-bottom:3px}
.input-box{height:clamp(34px,4.4vh,42px);display:flex;align-items:center;border:1px solid #cbdce9;border-radius:11px;background:rgba(255,255,255,.75);transition:all .25s ease}
.input-box>i:first-child{width:36px;flex-shrink:0;text-align:center;color:#104d80;font-size:14px}
.input-box input,.input-box select{flex:1;min-width:0;height:100%;border:none;outline:none;background:transparent;font-family:"Poppins","Noto Sans Sinhala",sans-serif;font-size:12.5px;color:#173f64;padding-right:10px}
.input-box input::placeholder{color:#7a8da3;font-size:12px}
.input-box select{cursor:pointer;color:#526577}
/* Department dropdown */
#department,#department option{font-family:"Noto Sans Sinhala","Poppins",sans-serif;font-size:13.5px;font-weight:500}
#department option{color:#173f64;padding:8px}
.input-box:focus-within{background:#fff;border-color:#2a6a9f;box-shadow:0 0 0 3px rgba(32,103,153,.10)}
.two-column-row{width:100%;display:grid;grid-template-columns:1fr 1fr;gap:12px}
.password-box{position:relative}
.password-box input{padding-right:34px}
.toggle-password{position:absolute;right:11px;width:auto!important;color:#507092!important;cursor:pointer;font-size:13px!important;transition:.2s}
.toggle-password:hover{color:#315f8c!important}

/* BUTTONS */
.button-area{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:clamp(14px,2.4vh,24px)}
.btn{height:clamp(36px,4.5vh,44px);border:none;border-radius:11px;font-family:"Poppins",sans-serif;font-size:12.5px;font-weight:600;cursor:pointer;transition:all .25s ease}
.register-btn{color:#fff;background:linear-gradient(135deg,#0b5390,#0a3d6b);box-shadow:0 8px 16px rgba(4,57,97,.22)}
.register-btn:hover{transform:translateY(-2px);box-shadow:0 11px 22px rgba(4,57,97,.28)}
.back-btn{color:#0752a1;background:rgba(255,255,255,.55);border:1px solid #356a98}
.back-btn:hover{background:#eaf4fc;border-color:#0b4c83;transform:translateY(-2px)}
.login-text{text-align:center;margin-top:clamp(10px,1.6vh,16px);font-size:11.5px;color:#5b7898}
.login-text a{color:#084fa2;text-decoration:none;font-weight:600}
.login-text a:hover{text-decoration:underline}

/* BOTTOM */
.bottom-curve{position:absolute;left:-5%;bottom:-116px;width:64%;height:190px;background:linear-gradient(135deg,#073861,#031f3d);border-radius:50% 50% 0 0;transform:rotate(-2deg);z-index:4;pointer-events:none}
.features{position:absolute;bottom:12px;left:4.6%;width:53%;display:flex;align-items:center;z-index:7}
.feature{flex:1;display:flex;align-items:center;gap:13px;color:#fff;padding:0 25px;border-right:1px solid rgba(255,255,255,.35)}
.feature:first-child{padding-left:0}.feature:last-child{border-right:none}
.feature i{font-size:16px;min-width:30px;color:#fff}
.feature span{font-size:10px;line-height:1.5;font-weight:500}
.card-footer-text{position:absolute;right:4.5%;bottom:16px;width:min(440px,52vw);min-width:440px;text-align:center;color:#fff;z-index:6;pointer-events:none}
.card-footer-text p{font-size:8px;font-weight:500}

/* RESPONSIVE */
/* screen eka usa adu unama podi dewal hide karanawa (scroll nowenna) */
@media (min-width:901px) and (max-height:720px){
  .register-header p,.title-line{display:none}
}
@media (min-width:901px) and (max-height:600px){
  .register-icon{display:none}
}
@media (max-width:1200px){
  .header{left:4%}.quote{left:6%}
  .register-card{width:52vw;min-width:420px}
  .card-footer-text{width:52vw;min-width:420px}
  .features{width:44%}
  .feature{padding:0 10px;gap:8px}.feature span{font-size:9px}
}
@media (max-width:900px){
  body{height:auto;overflow-y:auto}
  .page{height:auto;min-height:100vh;justify-content:center;flex-direction:column;align-items:center;padding:20px 20px 30px;gap:10px;overflow:visible}
  .right-overlay,.bottom-curve{display:none}
  .header{position:relative;top:auto;left:auto;padding:20px 10px 0;justify-content:center}
  .quote{position:relative;top:auto;left:auto;text-align:center;margin:5px 20px 15px}
  .register-card{width:calc(100% - 20px);min-width:0;max-width:600px;margin:0 auto;max-height:none;overflow:visible}
  .features{position:relative;left:auto;bottom:auto;width:90%;margin:20px auto 0}
  .card-footer-text{position:relative;right:auto;bottom:auto;width:100%;min-width:0;margin:10px auto 0}
}
@media (max-width:700px){
  .register-card{padding:24px 20px 18px}
  .two-column-row,.button-area{grid-template-columns:1fr}
}
@media (max-width:600px){
  .header{display:block;text-align:center}
  .logo-box{margin:auto}.vertical-line{display:none}
  .institution .sinhala{font-size:18px}.institution .tamil{font-size:14px}.institution .english{font-size:14px}
  .features{display:grid;grid-template-columns:1fr 1fr;gap:18px 12px}
  .feature{border:none;padding:0}
}
@media (max-width:480px){
  .page{padding:15px 8px 25px}
  .register-card{border-radius:18px}
  .register-header h1{font-size:20px}
}
</style>
</head>
<body>
<div class="page">
    <div class="right-overlay"></div>

    <div class="header">
        <div class="logo-box"><img src="logo.webp" alt="Southern Provincial Council Emblem"></div>
        <div class="vertical-line"></div>
        <div class="institution">
            <div class="sinhala">දකුණු පළාත් සභා ලේකම් කාර්යාලය</div>
            <div class="tamil">தென் மாகாண சட்டசபை அலுவலகம்</div>
            <div class="english">Southern Provincial Council Secretariat</div>
        </div>
    </div>

    <div class="quote">“Efficient Administration for a Better Tomorrow”<div class="quote-line"></div></div>

    <div class="register-card">
        <div class="register-header">
            <div class="register-icon"><i class="fa-solid fa-user-plus"></i></div>
            <h1>Create an Account</h1>
            <p>Register for the Archive File Management System</p>
            <div class="title-line"></div>
        </div>

        <?php if ($errors): ?>
            <div class="alert-err"><i class="fa-solid fa-circle-exclamation"></i> Please fix the following:
                <ul><?php foreach ($errors as $er): ?><li><?= e($er) ?></li><?php endforeach; ?></ul>
            </div>
        <?php endif; ?>

        <form id="registerForm" method="post" action="register.php">
            <?= csrf_field() ?>
            <div class="form-grid">

                <div class="input-group">
                    <label for="fullName">Full Name</label>
                    <div class="input-box"><i class="fa-solid fa-id-card"></i>
                        <input type="text" id="fullName" name="full_name" value="<?= e($f['full_name']) ?>" placeholder="Enter full name" required></div>
                </div>

                <div class="two-column-row">
                    <div class="input-group">
                        <label for="nic">NIC Number</label>
                        <div class="input-box"><i class="fa-solid fa-address-card"></i>
                            <input type="text" id="nic" name="nic" value="<?= e($f['nic']) ?>" placeholder="Enter NIC number" required></div>
                    </div>
                    <div class="input-group">
                        <label for="email">Email</label>
                        <div class="input-box"><i class="fa-solid fa-envelope"></i>
                            <input type="email" id="email" name="email" value="<?= e($f['email']) ?>" placeholder="Enter email address" required></div>
                    </div>
                </div>

                <div class="two-column-row">
                    <div class="input-group">
                        <label for="designation">Designation</label>
                        <div class="input-box"><i class="fa-solid fa-briefcase"></i>
                            <input type="text" id="designation" name="designation" value="<?= e($f['designation']) ?>" placeholder="Enter designation" required></div>
                    </div>
                    <div class="input-group">
                        <label for="subjectNumber">Subject Number</label>
                        <div class="input-box"><i class="fa-solid fa-hashtag"></i>
                            <input type="text" id="subjectNumber" name="subject_number" value="<?= e($f['subject_number']) ?>" placeholder="Enter subject number" required></div>
                    </div>
                </div>

                <div class="two-column-row">
                    <div class="input-group">
                        <label for="userType">User Type</label>
                        <div class="input-box"><i class="fa-solid fa-user-shield"></i>
                            <select id="userType" name="role" required>
                                <option value="" disabled <?= $f['role'] === '' ? 'selected' : '' ?>>Select user type</option>
                                <option value="user"    <?= $f['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                <option value="admin"   <?= $f['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                <option value="subject" <?= $f['role'] === 'subject' ? 'selected' : '' ?>>Subject</option>
                            </select></div>
                    </div>
                    <div class="input-group">
                        <label for="department">Department</label>
                        <div class="input-box"><i class="fa-solid fa-building"></i>
                            <select id="department" name="department" required>
                                <option value="" disabled <?= $f['department'] === '' ? 'selected' : '' ?>>Select department</option>
                                <option value="ආයතන"  <?= $f['department'] === 'ආයතන' ? 'selected' : '' ?>>ආයතන</option>
                                <option value="සභා"    <?= $f['department'] === 'සභා' ? 'selected' : '' ?>>සභා</option>
                                <option value="ගිණුම්" <?= $f['department'] === 'ගිණුම්' ? 'selected' : '' ?>>ගිණුම්</option>
                            </select></div>
                    </div>
                </div>

                <div class="input-group">
                    <label for="username">Username</label>
                    <div class="input-box"><i class="fa-solid fa-user"></i>
                        <input type="text" id="username" name="username" value="<?= e($f['username']) ?>" placeholder="Enter username" autocomplete="username" required></div>
                </div>

                <div class="two-column-row">
                    <div class="input-group">
                        <label for="password">Password</label>
                        <div class="input-box password-box"><i class="fa-solid fa-lock"></i>
                            <input type="password" id="password" name="password" placeholder="Enter password" autocomplete="new-password" required>
                            <i class="fa-solid fa-eye-slash toggle-password" role="button" tabindex="0" aria-label="Show password"
                               onclick="togglePassword('password', this)"
                               onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword('password',this)}"></i></div>
                    </div>
                    <div class="input-group">
                        <label for="confirmPassword">Confirm Password</label>
                        <div class="input-box password-box"><i class="fa-solid fa-lock"></i>
                            <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm password" autocomplete="new-password" required>
                            <i class="fa-solid fa-eye-slash toggle-password" role="button" tabindex="0" aria-label="Show confirm password"
                               onclick="togglePassword('confirmPassword', this)"
                               onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();togglePassword('confirmPassword',this)}"></i></div>
                    </div>
                </div>
            </div>

            <div class="button-area">
                <button type="button" class="btn back-btn" onclick="window.location.href='index.php'"><i class="fa-solid fa-arrow-left"></i> &nbsp; Back to Login</button>
                <button type="submit" class="btn register-btn"><i class="fa-solid fa-user-plus"></i> &nbsp; Create Account</button>
            </div>
        </form>

        <div class="login-text">Already have an account? <a href="index.php">Login here</a></div>
    </div>

    <div class="card-footer-text"><p>Southern Provincial Council&nbsp;&nbsp;&nbsp;|&nbsp;&nbsp;&nbsp;Archive Management System</p></div>
    <div class="bottom-curve"></div>

    <div class="features">
        <div class="feature"><i class="fa-solid fa-shield-halved"></i><span>Secure<br>Access</span></div>
        <div class="feature"><i class="fa-regular fa-file-lines"></i><span>Efficient<br>File Management</span></div>
        <div class="feature"><i class="fa-regular fa-folder-open"></i><span>Organized<br>Records</span></div>
        <div class="feature"><i class="fa-solid fa-users"></i><span>For a<br>Better Service</span></div>
    </div>
</div>

<script>
function togglePassword(inputId, icon) {
    const input = document.getElementById(inputId);
    if (input.type === "password") {
        // akuru penawa -> eye open
        input.type = "text";
        icon.classList.replace("fa-eye-slash", "fa-eye");
        icon.setAttribute("aria-label", "Hide password");
    } else {
        // akuru penne na -> eye close
        input.type = "password";
        icon.classList.replace("fa-eye", "fa-eye-slash");
        icon.setAttribute("aria-label", "Show password");
    }
}
document.getElementById("registerForm").addEventListener("submit", function (e) {
    const p = document.getElementById("password").value, c = document.getElementById("confirmPassword").value;
    if (p !== c) { e.preventDefault(); alert("Password and Confirm Password do not match."); return; }
    if (p.length < 6) { e.preventDefault(); alert("Password must contain at least 6 characters."); }
});
</script>
</body>
</html>