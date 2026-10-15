<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>User Dashboard - Archive Management System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --ink:#14233d; --navy:#0d2547; --navy-2:#16386a; --gold:#c9993f; --gold-soft:#faf2e0;
    --bg:#f3f5f9; --card:#fff; --line:#e3e8f0; --muted:#6a7890; --blue:#2563d0;
    --shadow:0 1px 2px rgba(16,37,71,.05),0 6px 20px rgba(16,37,71,.05);
    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px; --fs-2xl:24px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;background:var(--bg);color:var(--ink);min-height:100vh}
button,input,select{font:inherit}
button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
.app{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{width:256px;flex-shrink:0;display:flex;flex-direction:column;position:relative;z-index:20;overflow:hidden;color:#fff;background:linear-gradient(180deg,#0d2547 0%,#0a1c37 100%);transition:width .3s ease}
.sidebar::before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 0 0,rgba(201,153,63,.14),transparent 38%);pointer-events:none}
.brand{display:flex;align-items:center;gap:12px;padding:24px 18px 20px;position:relative}
.logo-box{width:52px;height:60px;flex-shrink:0;display:flex;align-items:center;justify-content:center;padding:5px;border-radius:12px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14)}
.logo{width:100%;height:100%;object-fit:contain}
.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:12.5px;font-weight:600;line-height:1.7}
.brand-text .english{font-size:10px;line-height:1.5;color:#b9c9e2;margin-top:2px}
.brand-text .system-name{display:inline-block;font-size:10px;font-weight:600;color:var(--gold);margin-top:7px;padding:3px 9px;border-radius:20px;border:1px solid rgba(201,153,63,.45);background:rgba(201,153,63,.1)}
.sidebar-label{padding:6px 22px 10px;color:#7f95b8;font-size:10px;font-weight:600;letter-spacing:1.4px;position:relative}
.sidebar-menu{padding:0 12px;position:relative}
.nav-link{display:flex;align-items:center;gap:12px;min-height:42px;padding:8px 14px;margin-bottom:4px;border-radius:10px;text-decoration:none;color:#c3d1e8;font-weight:500;position:relative;transition:background .2s,color .2s}
.nav-link i{width:18px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.07)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;background:rgba(255,255,255,.11)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-12px;top:9px;bottom:9px;width:4px;border-radius:0 4px 4px 0;background:var(--gold)}
.nav-link.logout-link{margin-top:14px;color:#ffb9c0}
.nav-link.logout-link i{color:#ff8e9b}
.nav-link.logout-link:hover{background:rgba(255,90,110,.16);color:#fff}
.sidebar-bottom{margin-top:auto;padding:18px;position:relative}
.sidebar-quote{padding:14px 15px;border-radius:12px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.05)}
.quote-top{display:flex;align-items:center;gap:8px;margin-bottom:6px;color:var(--gold);font-size:10px;font-weight:600;letter-spacing:.8px}
.sidebar-quote p{font-size:var(--fs-sm);line-height:1.7;color:#dbe6f6}
.sidebar-status{display:flex;align-items:center;gap:8px;margin-top:12px;padding:0 3px;color:#8ea6c9;font-size:var(--fs-xs)}
.status-dot{width:7px;height:7px;border-radius:50%;background:#46d9a0;box-shadow:0 0 8px rgba(70,217,160,.6)}

/* ===== TOPBAR ===== */
.main{flex:1;min-width:0}
.topbar{min-height:64px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.94);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(12px)}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.menu-toggle{width:38px;height:38px;flex-shrink:0;display:grid;place-items:center;background:var(--bg);border:1px solid var(--line);border-radius:10px;color:var(--navy);font-size:14px;transition:.2s}
.menu-toggle:hover{color:#fff;background:var(--navy)}
.topbar-greeting strong{display:block;font-size:var(--fs-md);font-weight:700}
.topbar-greeting span{font-size:var(--fs-xs);color:var(--muted)}
.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0}
.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--gold);background:var(--navy);font-size:14px;box-shadow:0 0 0 3px var(--gold-soft)}
.profile-details strong{display:block;font-size:var(--fs-sm);font-weight:700;line-height:1.3}
.profile-details span{font-size:var(--fs-xs);color:var(--muted)}
.profile-toggle{border:0;background:transparent;color:#5e7290;padding:8px;font-size:11px}
.profile-popover{position:absolute;right:0;top:52px;width:230px;padding:16px;background:#fff;border:1px solid var(--line);border-radius:12px;box-shadow:0 14px 34px rgba(15,37,71,.15);z-index:30}
.profile-popover strong{font-size:var(--fs-md)}
.profile-popover p{color:var(--muted);font-size:var(--fs-sm);margin-top:2px}
.profile-popover a{display:block;padding:9px 0;text-decoration:none;color:#27487a;font-size:var(--fs-sm)}
.profile-popover a:first-of-type{margin-top:8px;border-top:1px solid var(--line)}

/* ===== CONTENT ===== */
.content{max-width:1560px;margin:0 auto;padding:24px 28px 30px}

/* Hero */
.hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.5fr);gap:28px;align-items:center;padding:28px 30px;margin-bottom:22px;border-radius:18px;color:#fff;
    background:linear-gradient(120deg,#0d2547 0%,#16386a 100%);box-shadow:0 12px 30px rgba(13,37,71,.22)}
.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}
.hero::after{content:"";position:absolute;width:260px;height:260px;right:-90px;top:-130px;border-radius:50%;border:1px solid rgba(201,153,63,.35)}
.hero>*{position:relative;z-index:1}
.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 12px;border-radius:20px;background:rgba(255,255,255,.1);font-size:var(--fs-xs);color:#d6e2f5}
.hero h1{font-size:var(--fs-2xl);font-weight:800;letter-spacing:-.4px;line-height:1.3;margin:12px 0 4px}
.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:420px}
.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}
.stat-card{padding:16px;border-radius:14px;background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.13);backdrop-filter:blur(6px);transition:background .2s,transform .2s}
.stat-card:hover{background:rgba(255,255,255,.13);transform:translateY(-2px)}
.stat-icon{width:34px;height:34px;display:grid;place-items:center;border-radius:10px;background:rgba(201,153,63,.18);color:var(--gold);font-size:14px;margin-bottom:12px}
.stat-info h2{font-size:26px;line-height:1.1;font-weight:800}
.stat-info span{display:block;font-size:var(--fs-sm);font-weight:600;color:#e4ecf8;margin-top:6px}
.stat-info small{display:block;font-size:var(--fs-xs);color:#9fb3d3;line-height:1.4;margin-top:2px}

/* Cards */
.card{padding:22px 24px;background:var(--card);border:1px solid var(--line);border-radius:16px;box-shadow:var(--shadow);margin-bottom:22px}
.card-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px}
.card-heading-left{display:flex;align-items:center;gap:12px}
.section-icon{width:38px;height:38px;flex-shrink:0;border-radius:10px;display:grid;place-items:center;background:var(--navy);color:var(--gold);font-size:14px}
.card-heading h2{font-size:var(--fs-xl);font-weight:700;color:var(--ink);line-height:1.3}
.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px}

/* Forms */
.form-grid,.submit-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.field{min-width:0}
.field label{display:block;color:#33466a;font-size:var(--fs-sm);font-weight:600;margin-bottom:6px}
.input-wrap{height:40px;display:flex;align-items:center;gap:10px;padding:0 13px;min-width:0;background:#f8fafd;border:1px solid #d8e0ec;border-radius:10px;transition:.2s}
.input-wrap:hover{border-color:#bccbe2}
.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 3px rgba(37,99,208,.12)}
.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}
.input-wrap input,.input-wrap select{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}
.input-wrap input::placeholder{color:#9aa8bf}
.input-wrap select{cursor:pointer}
.btn{min-height:40px;padding:0 18px;border:1px solid transparent;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;transition:.2s}
.btn-primary{background:var(--navy);color:#fff;box-shadow:0 4px 12px rgba(13,37,71,.22)}
.btn-primary:hover{background:var(--navy-2);transform:translateY(-1px)}
.btn-primary i{color:var(--gold)}
.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}
.btn-outline:hover{background:var(--bg);border-color:#b8c6de}
.search-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:18px;border-top:1px solid var(--line)}

/* Results */
.results-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px}
.results-subtitle{color:var(--muted);font-size:var(--fs-sm);margin-top:1px}
.count-chip{display:inline-flex;align-items:center;gap:7px;padding:5px 12px;background:var(--gold-soft);color:#8a6420;border-radius:20px;font-size:var(--fs-sm);font-weight:600}
.table-wrapper{overflow-x:auto;border:1px solid var(--line);border-radius:12px}
table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:var(--fs-sm)}
thead{background:#eef2f8}
th{text-align:left;padding:12px 14px;color:#3a4f75;font-size:var(--fs-xs);font-weight:700;letter-spacing:.3px}
td{padding:11px 14px;color:#4b5d7c;border-top:1px solid #edf1f6;font-size:var(--fs-sm)}
tbody tr{transition:background .15s}
tbody tr:hover{background:#f7f9fd}
td:first-child{color:#8a98b0;font-weight:600}
.file-name{display:flex;align-items:center;gap:10px;color:var(--ink);font-weight:600}
.file-icon{font-size:16px}
.file-icon.pdf{color:#e04f4f}.file-icon.doc{color:#2f6fe0}.file-icon.xls{color:#17946a}
.file-badge{display:inline-block;padding:3px 10px;border-radius:20px;font-size:10px;font-weight:700;letter-spacing:.3px}
.badge-pdf{color:#c93636;background:#fdeeee}
.badge-docx{color:#2159c0;background:#e9f1ff}
.badge-xlsx{color:#127a56;background:#e5f7ef}
.view-btn{min-height:30px;padding:0 12px;display:inline-flex;align-items:center;gap:6px;border:1px solid #d6e0f0;border-radius:8px;color:var(--navy);background:#fff;font-size:var(--fs-xs);font-weight:600;transition:.2s}
.view-btn:hover{color:#fff;background:var(--navy);border-color:var(--navy)}
.no-results{padding:36px 15px;text-align:center;color:var(--muted)}
.no-results i{font-size:32px;color:#a9b8d0;margin-bottom:10px}
.no-results p{font-size:var(--fs-md)}
.pagination{display:flex;align-items:center;gap:6px;margin-top:16px}
.page-btn{min-width:32px;height:32px;padding:0 10px;border-radius:8px;border:1px solid #d8e0ec;background:#fff;color:#4b5d7c;font-size:var(--fs-sm);font-weight:600}
.page-btn:hover{background:var(--bg)}
.page-btn.active{color:#fff;background:var(--navy);border-color:var(--navy)}
.page-btn:disabled{opacity:.4;cursor:not-allowed}

/* Submit */
.submit-note{display:flex;align-items:flex-start;gap:10px;margin-top:18px;padding:12px 14px;border-radius:10px;border-left:3px solid var(--gold);background:var(--gold-soft);color:#6b5326;font-size:var(--fs-sm);line-height:1.7}
.submit-note i{color:var(--gold);font-size:14px;margin-top:3px}
.submit-bottom{display:flex;justify-content:flex-end;gap:14px;flex-wrap:wrap;margin-top:18px}
.submit-actions{display:flex;gap:10px;flex-wrap:wrap}

/* Footer */
.footer{display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap;padding:4px 4px 0;color:#8796ae;font-size:var(--fs-xs)}

/* Desktop collapse */
@media (min-width:701px){
    .app.sidebar-collapsed .sidebar{width:0}
    .app.sidebar-collapsed .sidebar>*{visibility:hidden}
}

/* Tablet */
@media (max-width:1150px){
    .sidebar{width:236px}
    .content{padding:20px 18px 28px}
    .topbar{padding:10px 18px}
    .hero{grid-template-columns:1fr;gap:20px;padding:24px}
    .form-grid,.submit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    table{min-width:1000px}
}

/* Mobile */
.sidebar-overlay{display:none}
@media (max-width:700px){
    .sidebar{position:fixed;left:-280px;top:0;bottom:0;width:256px;min-height:100%;overflow-y:auto;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}
    .sidebar.open{left:0}
    .sidebar-overlay.show{display:block;position:fixed;inset:0;background:rgba(7,27,55,.55);z-index:15;backdrop-filter:blur(2px)}
    .topbar{min-height:58px;padding:8px 12px}
    .topbar-left{gap:10px}
    .menu-toggle{width:36px;height:36px}
    .topbar-greeting strong{font-size:var(--fs-sm)}
    .topbar-greeting span{font-size:10px}
    .profile{gap:6px}
    .profile-avatar{width:32px;height:32px}
    .profile-details strong{font-size:var(--fs-xs);max-width:90px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
    .profile-details span{font-size:10px}
    .profile-toggle{padding:4px}
    .profile-popover{width:210px;top:46px}
    .content{padding:16px 12px 24px}
    .hero{padding:20px 16px;border-radius:14px;margin-bottom:16px}
    .hero h1{font-size:20px}
    .hero-stats{grid-template-columns:1fr;gap:10px}
    .stat-card{display:flex;align-items:center;gap:12px;padding:12px 14px}
    .stat-icon{margin-bottom:0}
    .stat-info h2{font-size:20px}
    .stat-info span{margin-top:0}
    .card{padding:16px 13px;border-radius:13px;margin-bottom:16px}
    .card-heading{margin-bottom:16px}
    .card-heading h2{font-size:var(--fs-lg)}
    .section-icon{width:34px;height:34px}
    .form-grid,.submit-grid{grid-template-columns:1fr;gap:13px}
    .input-wrap{height:42px}
    .search-actions{gap:8px}
    .search-actions .btn{flex:1;padding:0 10px}
    .results-heading{align-items:flex-start}
    .results-heading .btn{min-height:36px;padding:0 10px;font-size:var(--fs-xs)}
    .submit-actions{width:100%;flex-direction:column}
    .submit-actions .btn{width:100%}
    .footer{justify-content:center;text-align:center;font-size:10px}
}

/* Print */
@media print{
    body{background:#fff}
    .sidebar,.topbar,.hero,.search-card,.submit-card,.footer,.print-btn,.pagination,.view-btn,.sidebar-overlay{display:none!important}
    .app,.main{display:block;width:100%}
    .content{padding:0}
    .results-card{border:none;box-shadow:none;padding:0}
    .table-wrapper{overflow:visible;border:none}
    table{min-width:0;white-space:normal;font-size:10px}
    th,td{padding:7px;color:#000}
}
</style>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app" id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <div class="logo-box">
                <img src="logo.webp" alt="Council Logo" class="logo" onerror="this.parentElement.style.display='none'">
            </div>
            <div class="brand-text">
                <div class="sinhala">දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</div>
                <div class="english">Southern Provincial Council Secretariat</div>
                <div class="system-name">Archive Management System</div>
            </div>
        </div>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-menu">
            <a href="user_dashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="user_profile.php" class="nav-link"><i class="fa-solid fa-circle-user"></i><span>My Profile</span></a>
            <a href="change_password.php" class="nav-link"><i class="fa-solid fa-shield-halved"></i><span>Change Password</span></a>
            <a href="index.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i><span>Logout</span></a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-quote">
                <div class="quote-top"><i class="fa-solid fa-quote-left"></i><span>OUR COMMITMENT</span></div>
                <p>Efficient Records for a <br>Better Tomorrow</p>
            </div>
            <div class="sidebar-status">
                <span class="status-dot"></span>
                <span>Archive System Interface</span>
            </div>
        </div>

    </aside>

    <!-- MAIN -->
    <main class="main">

        <header class="topbar">
            <div class="topbar-left">
                <button class="menu-toggle" id="menuToggle" type="button" aria-label="Toggle menu" aria-expanded="true">
                    <i class="fa-solid fa-bars"></i>
                </button>
                <div class="topbar-greeting">
                    <strong>Welcome to Archive System</strong>
                    <span>Southern Provincial Council Secretariat</span>
                </div>
            </div>

            <div class="profile">
                <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="profile-details">
                    <strong>P.G. Minasha Shenali</strong>
                    <span>User Account</span>
                </div>
                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

                <div class="profile-popover" id="profilePopover" hidden>
                    <strong>P.G. Minasha Shenali</strong>
                    <p><i class="fa-solid fa-user-tag"></i> User Account</p>
                    <a href="user_profile.php"><i class="fa-solid fa-user-pen"></i> View My Profile</a>
                    <a href="change_password.php"><i class="fa-solid fa-lock"></i> Change Password</a>
                    <a href="index.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">

            <!-- HERO + STATS -->
            <section class="hero">
                <div>
                    <span class="hero-date"><i class="fa-regular fa-calendar"></i><span id="currentDate"></span></span>
                    <h1>User Dashboard</h1>
                    <p>Search, manage and submit archive files easily.</p>
                </div>

                <div class="hero-stats">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
                        <div class="stat-info">
                            <h2 id="totalFiles">12</h2>
                            <span>Available Archive Files</span>
                            <small>Sample records in this demo</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="stat-info">
                            <h2 id="totalDepartments">3</h2>
                            <span>Departments</span>
                            <small>Departments represented</small>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="stat-info">
                            <h2 id="matchingFiles">12</h2>
                            <span>Matching Results</span>
                            <small>Files matching your search</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEARCH -->
            <section class="card search-card">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div>
                            <h2>Search Archive Files</h2>
                            <p>Find records using the available file details.</p>
                        </div>
                    </div>
                </div>

                <form id="searchForm">
                    <div class="form-grid">
                        <div class="field">
                            <label for="searchName">File Name</label>
                            <div class="input-wrap">
                                <i class="fa-regular fa-file-lines"></i>
                                <input type="text" id="searchName" placeholder="Enter file name...">
                            </div>
                        </div>

                        <div class="field">
                            <label for="searchDepartment">Department</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-building-columns"></i>
                                <select id="searchDepartment">
                                    <option value="">All Departments</option>
                                    <option value="Institutions">ආයතන(Institutions)</option>
                                    <option value="Councils">සභා(Councils)</option>
                                    <option value="Accounts">ගිණුම්(Accounts)</option>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="searchSubject">Subject Number</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-hashtag"></i>
                                <input type="text" id="searchSubject" placeholder="Enter subject number...">
                            </div>
                        </div>

                        <div class="field">
                            <label for="searchArchive">Archive Number</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-box-archive"></i>
                                <input type="text" id="searchArchive" placeholder="Enter archive number...">
                            </div>
                        </div>
                    </div>

                    <div class="search-actions">
                        <button class="btn btn-outline" type="button" id="clearSearch">
                            <i class="fa-solid fa-rotate-left"></i> Clear Filters
                        </button>
                        <button class="btn btn-primary" type="submit">
                            <i class="fa-solid fa-magnifying-glass"></i> Search Files
                        </button>
                    </div>
                </form>
            </section>

            <!-- RESULTS -->
            <section class="card results-card">
                <div class="results-heading">
                    <div class="card-heading" style="margin-bottom:0">
                        <div class="card-heading-left">
                            <div class="section-icon"><i class="fa-solid fa-table-list"></i></div>
                            <div>
                                <h2>Archive Search Results</h2>
                                <p class="results-subtitle">Review the available archive records.</p>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-primary print-btn" onclick="printResults()">
                        <i class="fa-solid fa-print"></i> Print Results
                    </button>
                </div>

                <div style="margin-bottom:14px">
                    <span class="count-chip">
                        <i class="fa-solid fa-folder"></i>
                        <span id="resultCount">12 files found</span>
                    </span>
                </div>

                <div class="table-wrapper">
                    <table id="resultsTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>File Name</th>
                                <th>Department</th>
                                <th>Subject Number</th>
                                <th>Archive Number</th>
                                <th>Year</th>
                                <th>Type</th>
                                <th>Added Date</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="resultsBody"></tbody>
                    </table>

                    <div class="no-results" id="noResults" hidden>
                        <i class="fa-regular fa-folder-open"></i>
                        <p>No matching archive files found.</p>
                        <p style="font-size:11px;margin-top:4px">Try changing your search filters.</p>
                    </div>
                </div>

                <div class="pagination" id="pagination">
                    <button class="page-btn" type="button" id="previousPage" aria-label="Previous page"><i class="fa-solid fa-chevron-left"></i></button>
                    <button class="page-btn active" type="button" id="pageOne">1</button>
                    <button class="page-btn" type="button" id="pageTwo">2</button>
                    <button class="page-btn" type="button" id="nextPage" aria-label="Next page"><i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </section>

            <!-- SUBMIT -->
            <section class="card submit-card">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-cloud-arrow-up"></i></div>
                        <div>
                            <h2>Submit a File to Archive</h2>
                            <p>Enter the details of the file you want to submit.</p>
                        </div>
                    </div>
                </div>

                <form id="submitForm">
                    <div class="submit-grid">
                        <div class="field">
                            <label for="submissionDate">Submission Date</label>
                            <div class="input-wrap"><i class="fa-regular fa-calendar"></i><input type="date" id="submissionDate" required></div>
                        </div>

                        <div class="field">
                            <label for="subjectNumber">Subject Number</label>
                            <div class="input-wrap"><i class="fa-solid fa-hashtag"></i><input type="text" id="subjectNumber" placeholder="e.g. SUB-101" required></div>
                        </div>

                        <div class="field">
                            <label for="submitDepartment">Department</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-building-columns"></i>
                                <select id="submitDepartment" required>
                                    <option value="">Select Department</option>
                                    <option value="Institutions">ආයතන(Institutions)</option>
                                    <option value="Councils">සභා(Councils)</option>
                                    <option value="Accounts">ගිණුම්(Accounts)</option>
                                </select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="fileNumber">File Number</label>
                            <div class="input-wrap"><i class="fa-regular fa-file"></i><input type="text" id="fileNumber" placeholder="Enter file number..." required></div>
                        </div>

                        <div class="field">
                            <label for="newFileName">File Name</label>
                            <div class="input-wrap"><i class="fa-regular fa-file-lines"></i><input type="text" id="newFileName" placeholder="Enter file name..." required></div>
                        </div>

                        <div class="field">
                            <label for="startDate">File Start Date</label>
                            <div class="input-wrap"><i class="fa-regular fa-calendar-days"></i><input type="date" id="startDate" required></div>
                        </div>

                        <div class="field">
                            <label for="endDate">File End Date</label>
                            <div class="input-wrap"><i class="fa-regular fa-calendar-check"></i><input type="date" id="endDate" required></div>
                        </div>

                        <div class="field">
                            <label for="pageCount">Number of Pages</label>
                            <div class="input-wrap"><i class="fa-solid fa-copy"></i><input type="number" id="pageCount" min="1" step="1" placeholder="Enter page count..." required></div>
                        </div>
                    </div>

                    <div class="submit-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>After submission, the file can be reviewed by the Subject Officer. This interface currently uses demonstration data only.</span>
                    </div>

                    <div class="submit-bottom">
                        <div class="submit-actions">
                            <button type="reset" class="btn btn-outline"><i class="fa-solid fa-rotate-left"></i> Clear Form</button>
                            <button type="submit" class="btn btn-primary submit-main-btn"><i class="fa-solid fa-paper-plane"></i> Save and Submit</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- FOOTER -->
            <footer class="footer">
                <span>© <span id="footerYear">2026</span> Southern Provincial Council. All Rights Reserved.</span>
                <span>Archive Management System</span>
            </footer>

        </div>
    </main>
</div>

<script>
/* ===== SAMPLE ARCHIVE DATA (demonstration only) ===== */
const archiveFiles = [
    {name:"Finance Report 2026",department:"Accounts",subject:"SUB-001",archive:"ARC-001",year:"2026",type:"PDF",date:"2026-06-15"},
    {name:"Annual Budget Plan",department:"Institutions",subject:"SUB-002",archive:"ARC-002",year:"2026",type:"DOCX",date:"2026-06-10"},
    {name:"Staff Details",department:"Institutions",subject:"SUB-015",archive:"ARC-015",year:"2025",type:"XLSX",date:"2025-12-01"},
    {name:"Circular No. 07/2026",department:"Councils",subject:"SUB-021",archive:"ARC-021",year:"2026",type:"PDF",date:"2026-05-20"},
    {name:"IT System Proposal",department:"Institutions",subject:"SUB-030",archive:"ARC-030",year:"2026",type:"DOCX",date:"2026-04-18"},
    {name:"Project Report",department:"Councils",subject:"SUB-045",archive:"ARC-045",year:"2025",type:"PDF",date:"2025-11-25"},
    {name:"Monthly Progress",department:"Councils",subject:"SUB-046",archive:"ARC-046",year:"2025",type:"XLSX",date:"2025-11-20"},
    {name:"Meeting Minutes",department:"Accounts",subject:"SUB-058",archive:"ARC-058",year:"2026",type:"DOCX",date:"2026-03-15"},
    {name:"Finance Circular",department:"Accounts",subject:"SUB-060",archive:"ARC-060",year:"2026",type:"PDF",date:"2026-02-12"},
    {name:"Council Development Plan",department:"Councils",subject:"SUB-065",archive:"ARC-065",year:"2025",type:"DOCX",date:"2025-10-11"},
    {name:"Employee Records",department:"Institutions",subject:"SUB-070",archive:"ARC-070",year:"2026",type:"XLSX",date:"2026-01-18"},
    {name:"Annual Accounts",department:"Accounts",subject:"SUB-080",archive:"ARC-080",year:"2025",type:"PDF",date:"2025-09-20"}
];

let filteredFiles = [...archiveFiles];
let currentPage = 1;
const rowsPerPage = 8;

const resultsBody = document.getElementById("resultsBody");
const resultCount = document.getElementById("resultCount");
const noResults = document.getElementById("noResults");
const resultsTable = document.getElementById("resultsTable");
const pagination = document.getElementById("pagination");

/* ===== DATE ===== */
const now = new Date();
document.getElementById("currentDate").textContent =
    now.toLocaleDateString("en-GB", {day:"2-digit", month:"short", year:"numeric"});
document.getElementById("footerYear").textContent = now.getFullYear();

const localToday = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
document.getElementById("submissionDate").value = localToday;

/* ===== HELPERS ===== */
function escapeHTML(value) {
    return String(value).replace(/[&<>"']/g, ch => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[ch]));
}

function getFileIcon(type) {
    if (type === "PDF") return '<i class="fa-solid fa-file-pdf file-icon pdf"></i>';
    if (type === "XLSX") return '<i class="fa-solid fa-file-excel file-icon xls"></i>';
    return '<i class="fa-solid fa-file-word file-icon doc"></i>';
}

function getBadge(type) {
    const cls = type === "PDF" ? "badge-pdf" : type === "XLSX" ? "badge-xlsx" : "badge-docx";
    return `<span class="file-badge ${cls}">${escapeHTML(type)}</span>`;
}

/* ===== SEARCH ===== */
document.getElementById("searchForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const name = document.getElementById("searchName").value.trim().toLowerCase();
    const department = document.getElementById("searchDepartment").value;
    const subject = document.getElementById("searchSubject").value.trim().toLowerCase();
    const archive = document.getElementById("searchArchive").value.trim().toLowerCase();

    filteredFiles = archiveFiles.filter(file =>
        (!name || file.name.toLowerCase().includes(name)) &&
        (!department || file.department === department) &&
        (!subject || file.subject.toLowerCase().includes(subject)) &&
        (!archive || file.archive.toLowerCase().includes(archive))
    );

    currentPage = 1;
    renderResults();
});

document.getElementById("clearSearch").addEventListener("click", function() {
    document.getElementById("searchForm").reset();
    filteredFiles = [...archiveFiles];
    currentPage = 1;
    renderResults();
});

/* ===== RENDER RESULTS ===== */
function renderResults() {
    const total = filteredFiles.length;
    const totalPages = Math.max(1, Math.ceil(total / rowsPerPage));

    currentPage = Math.max(1, Math.min(currentPage, totalPages));

    const start = (currentPage - 1) * rowsPerPage;
    const pageFiles = filteredFiles.slice(start, start + rowsPerPage);

    resultCount.textContent = `${total} file${total === 1 ? "" : "s"} found`;
    document.getElementById("matchingFiles").textContent = total;
    document.getElementById("totalFiles").textContent = archiveFiles.length;
    document.getElementById("totalDepartments").textContent =
        new Set(archiveFiles.map(file => file.department)).size;

    resultsBody.innerHTML = "";

    if (total === 0) {
        noResults.hidden = false;
        resultsTable.style.display = "none";
        pagination.style.display = "none";
        return;
    }

    noResults.hidden = true;
    resultsTable.style.display = "table";
    pagination.style.display = totalPages > 1 ? "flex" : "none";

    pageFiles.forEach((file, index) => {
        const row = document.createElement("tr");

        row.innerHTML = `
            <td>${start + index + 1}</td>
            <td><div class="file-name">${getFileIcon(file.type)}<span>${escapeHTML(file.name)}</span></div></td>
            <td>${escapeHTML(file.department)}</td>
            <td>${escapeHTML(file.subject)}</td>
            <td>${escapeHTML(file.archive)}</td>
            <td>${escapeHTML(file.year)}</td>
            <td>${getBadge(file.type)}</td>
            <td>${escapeHTML(file.date)}</td>
            <td>
                <button type="button" class="view-btn"
                    data-name="${escapeHTML(file.name)}"
                    data-number="${escapeHTML(file.archive)}"
                    data-subject="${escapeHTML(file.subject)}"
                    data-department="${escapeHTML(file.department)}"
                    data-type="${escapeHTML(file.type)}"
                    data-date="${escapeHTML(file.date)}">
                    <i class="fa-regular fa-eye"></i> View
                </button>
            </td>
        `;

        resultsBody.appendChild(row);
    });

    resultsBody.querySelectorAll(".view-btn").forEach(button => {
        button.addEventListener("click", function() {
            alert(
                "ARCHIVE FILE DETAILS\n\n" +
                "File Name: " + this.dataset.name +
                "\nDepartment: " + this.dataset.department +
                "\nSubject Number: " + this.dataset.subject +
                "\nArchive Number: " + this.dataset.number +
                "\nFile Type: " + this.dataset.type +
                "\nAdded Date: " + this.dataset.date +
                "\n\nThis is a sample record. Connect PHP/MySQL and file storage to open the actual archived file."
            );
        });
    });

    updatePagination(totalPages);
}

/* ===== PAGINATION ===== */
function updatePagination(totalPages) {
    const pageOne = document.getElementById("pageOne");
    const pageTwo = document.getElementById("pageTwo");

    pageOne.textContent = currentPage;
    pageTwo.textContent = Math.min(currentPage + 1, totalPages);

    pageOne.classList.add("active");
    pageTwo.classList.remove("active");

    pageOne.style.display = "";
    pageTwo.style.display = totalPages > 1 ? "" : "none";

    document.getElementById("previousPage").disabled = currentPage === 1;
    document.getElementById("nextPage").disabled = currentPage === totalPages;
}

function changePage(direction) {
    const totalPages = Math.max(1, Math.ceil(filteredFiles.length / rowsPerPage));
    currentPage = Math.max(1, Math.min(currentPage + direction, totalPages));
    renderResults();
}

document.getElementById("previousPage").addEventListener("click", () => changePage(-1));
document.getElementById("nextPage").addEventListener("click", () => changePage(1));

document.getElementById("pageOne").addEventListener("click", function() {
    currentPage = Number(this.textContent);
    renderResults();
});

document.getElementById("pageTwo").addEventListener("click", function() {
    currentPage = Number(this.textContent);
    renderResults();
});

/* ===== PRINT ===== */
function printResults() {
    if (filteredFiles.length === 0) {
        alert("No search results available to print.");
        return;
    }
    window.print();
}

/* ===== FILE SUBMISSION VALIDATION (demo only, not saved to MySQL) ===== */
document.getElementById("submitForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const submission = document.getElementById("submissionDate").value;
    const fileName = document.getElementById("newFileName").value.trim();
    const pageCount = Number(document.getElementById("pageCount").value);

    if (endDate < startDate) {
        alert("File End Date must be on or after File Start Date.");
        return;
    }

    if (submission < startDate || submission < endDate) {
        alert("Please check the submission date against the file dates.");
        return;
    }

    if (!Number.isInteger(pageCount) || pageCount < 1) {
        alert("Please enter a valid number of pages.");
        return;
    }

    alert(
        "Form validation successful!\n\n" +
        "File Name: " + fileName +
        "\n\nThis is a demonstration only. The file has NOT been saved to MySQL or sent to the Subject Officer."
    );
});

document.getElementById("submitForm").addEventListener("reset", function() {
    setTimeout(() => {
        document.getElementById("submissionDate").value = localToday;
    }, 0);
});

/* ===== SIDEBAR ===== */
const app = document.getElementById("app");
const sidebar = document.getElementById("sidebar");
const menuToggle = document.getElementById("menuToggle");
const sidebarOverlay = document.getElementById("sidebarOverlay");

function toggleSidebar() {
    if (window.innerWidth <= 700) {
        const isOpen = sidebar.classList.toggle("open");
        sidebarOverlay.classList.toggle("show", isOpen);
        document.body.style.overflow = isOpen ? "hidden" : "";
        menuToggle.setAttribute("aria-expanded", String(isOpen));
    } else {
        const isCollapsed = app.classList.toggle("sidebar-collapsed");
        menuToggle.setAttribute("aria-expanded", String(!isCollapsed));
    }
}

function closeSidebar() {
    sidebar.classList.remove("open");
    sidebarOverlay.classList.remove("show");
    document.body.style.overflow = "";

    if (window.innerWidth <= 700) {
        menuToggle.setAttribute("aria-expanded", "false");
    }
}

menuToggle.addEventListener("click", toggleSidebar);
sidebarOverlay.addEventListener("click", closeSidebar);

document.addEventListener("keydown", function(e) {
    if (e.key === "Escape") {
        closeSidebar();
        closeProfilePopover();
    }
});

window.addEventListener("resize", function() {
    if (window.innerWidth > 700) {
        closeSidebar();
    } else {
        app.classList.remove("sidebar-collapsed");
    }
});

document.querySelectorAll(".sidebar .nav-link").forEach(link => {
    link.addEventListener("click", function() {
        if (window.innerWidth <= 700) closeSidebar();
    });
});

/* ===== PROFILE DROPDOWN ===== */
const profileToggle = document.getElementById("profileToggle");
const profilePopover = document.getElementById("profilePopover");

function closeProfilePopover() {
    profilePopover.hidden = true;
    profileToggle.setAttribute("aria-expanded", "false");
}

profileToggle.addEventListener("click", function(e) {
    e.stopPropagation();
    profilePopover.hidden = !profilePopover.hidden;
    profileToggle.setAttribute("aria-expanded", String(!profilePopover.hidden));
});

profilePopover.addEventListener("click", e => e.stopPropagation());
document.addEventListener("click", closeProfilePopover);

/* ===== INITIAL DISPLAY ===== */
renderResults();
</script>

</body>
</html>
