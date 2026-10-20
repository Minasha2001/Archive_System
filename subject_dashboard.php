<?php
require_once __DIR__ . '/auth.php';

// Subject officer ta witharak
require_page_login(['subject']);

// Login karapu officer ge name eka database eken
$officerName = 'Subject Officer';
$uid = current_user_id();
if ($uid) {
    $st = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $st->execute([$uid]);
    if ($u = $st->fetch()) {
        $officerName = $u['full_name'] ?? $u['name'] ?? $u['username'] ?? $officerName;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
    
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Subject Officer Dashboard - Archives File Management System</title>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Noto+Sans+Sinhala:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

<style>
:root{
    --ink:#14233d; --navy:#0b2146; --navy-2:#16386a; --gold:#d4a24a; --gold-2:#f0cf8a; --gold-soft:#fbf3e1;
    --bg:#eef2f8; --card:#fff; --line:#e1e7f1; --muted:#6a7890; --blue:#2563d0;
    --shadow:0 1px 2px rgba(16,37,71,.05),0 10px 28px rgba(16,37,71,.07);
    --fs-xs:11px; --fs-sm:12px; --fs-md:13px; --fs-lg:14px; --fs-xl:17px; --fs-2xl:26px;
}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Plus Jakarta Sans','Noto Sans Sinhala',sans-serif;font-size:var(--fs-md);line-height:1.6;color:var(--ink);min-height:100vh;
    background:radial-gradient(900px 420px at 100% 0,rgba(212,162,74,.12),transparent 60%),radial-gradient(800px 500px at 30% -10%,rgba(37,99,208,.10),transparent 60%),var(--bg)}
button,input,select{font:inherit}
button{cursor:pointer}
[hidden]{display:none!important}
:focus-visible{outline:2px solid var(--blue);outline-offset:2px}
.app{display:flex;min-height:100vh}

/* ===== SIDEBAR ===== */
.sidebar{width:272px;flex-shrink:0;display:flex;flex-direction:column;position:sticky;top:0;height:100vh;z-index:20;overflow-x:hidden;overflow-y:auto;color:#fff;
    background:linear-gradient(180deg,#0c2347 0%,#091b38 100%);transition:width .3s ease;scrollbar-width:none;border-right:1px solid rgba(255,255,255,.06)}
.sidebar::-webkit-scrollbar{display:none}
.sidebar::before{content:"";position:absolute;inset:0;pointer-events:none;background:radial-gradient(ellipse at 50% 0,rgba(212,162,74,.16),transparent 55%)}

/* Brand: transparent emblem, no white background */
.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:14px;padding:14px 10px 16px;position:relative;margin-bottom:2px}
.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}
.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}
.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}
.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:none;border:0;padding:0}
.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}
.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:14px;font-weight:700;line-height:1.7}
.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}
.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;letter-spacing:.2px;white-space:nowrap;color:var(--gold-2);margin-top:12px;padding:5px 11px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}
.sidebar-label{padding:10px 26px 6px;color:#7389ad;font-size:10px;font-weight:700;letter-spacing:1.8px;position:relative}
.sidebar-menu{padding:0 14px;position:relative}
.nav-link{display:flex;align-items:center;gap:14px;width:100%;min-height:36px;padding:4px 14px;margin-bottom:0;border:0;border-radius:8px;background:transparent;text-align:left;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s}
.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;font-weight:600;background:rgba(255,255,255,.09)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold)}
.nav-link.logout-link{margin-top:0;color:#f2b8bf}
.nav-link.logout-link i{color:#ef8a96}
.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}

/* Sidebar bottom quote (extra compact size) */
.sidebar-bottom{margin-top:auto;padding:12px 38px;position:relative}
.sidebar-quote{position:relative;overflow:hidden;padding:10px 10px 9px;border-radius:10px;text-align:center;
    background:linear-gradient(160deg,rgba(212,162,74,.18),rgba(255,255,255,.04) 55%);border:1px solid rgba(212,162,74,.35);
    box-shadow:0 4px 12px rgba(0,0,0,.25),inset 0 1px 0 rgba(255,255,255,.08)}
.sidebar-quote::before{content:"";position:absolute;left:18%;right:18%;top:0;height:2px;background:linear-gradient(90deg,transparent,var(--gold),transparent)}
.quote-mark{position:absolute;right:8px;top:5px;font-size:16px;color:rgba(212,162,74,.18)}
.quote-top{display:flex;align-items:center;justify-content:center;gap:6px;margin-bottom:5px;color:var(--gold-2);font-size:8px;font-weight:700;letter-spacing:1.2px}
.quote-top::before,.quote-top::after{content:"";width:10px;height:1px;background:rgba(212,162,74,.6)}
.sidebar-quote p{position:relative;font-size:10.5px;line-height:1.4;font-weight:600;color:#e8eefb}
.sidebar-quote p strong{display:block;margin-top:1px;padding-bottom:3px;margin-bottom:-3px;font-size:12px;font-weight:800;background:linear-gradient(90deg,var(--gold-2),var(--gold));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.quote-line{width:22px;height:2px;margin:7px auto 0;border-radius:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}
.sidebar-status{display:flex;align-items:center;gap:8px;margin-top:14px;padding:12px 3px 0;border-top:1px solid rgba(255,255,255,.08);color:#8ea6c9;font-size:var(--fs-xs)}
.status-dot{width:7px;height:7px;border-radius:50%;background:#46d9a0;box-shadow:0 0 8px rgba(70,217,160,.7)}

/* ===== TOPBAR ===== */
.main{flex:1;min-width:0}
.topbar{min-height:66px;display:flex;justify-content:space-between;align-items:center;gap:14px;padding:10px 28px;background:rgba(255,255,255,.82);border-bottom:1px solid var(--line);position:sticky;top:0;z-index:12;backdrop-filter:blur(14px)}
.topbar-left{display:flex;align-items:center;gap:14px;min-width:0}
.menu-toggle{width:40px;height:40px;flex-shrink:0;display:grid;place-items:center;background:#fff;border:1px solid var(--line);border-radius:12px;color:var(--navy);font-size:14px;transition:.2s}
.menu-toggle:hover{color:#fff;background:var(--navy)}
.topbar-greeting strong{display:block;font-size:var(--fs-lg);font-weight:700}
.topbar-greeting span{font-size:var(--fs-xs);color:var(--muted)}
.profile{display:flex;align-items:center;gap:10px;position:relative;flex-shrink:0;padding:5px 8px 5px 5px;background:#fff;border:1px solid var(--line);border-radius:40px}
.profile-avatar{width:36px;height:36px;border-radius:50%;display:grid;place-items:center;color:var(--navy);background:linear-gradient(135deg,var(--gold-2),var(--gold));font-size:14px}
.profile-details strong{display:block;font-size:var(--fs-sm);font-weight:700;line-height:1.3}
.profile-details span{font-size:var(--fs-xs);color:var(--muted)}
.profile-toggle{border:0;background:transparent;color:#5e7290;padding:8px;font-size:11px}
.profile-popover{position:absolute;right:0;top:56px;width:230px;padding:16px;background:#fff;border:1px solid var(--line);border-radius:14px;box-shadow:0 14px 34px rgba(15,37,71,.16);z-index:30}
.profile-popover strong{font-size:var(--fs-md)}
.profile-popover p{color:var(--muted);font-size:var(--fs-sm);margin-top:2px}
.profile-popover a{display:block;padding:9px 0;text-decoration:none;color:#27487a;font-size:var(--fs-sm)}
.profile-popover a:first-of-type{margin-top:8px;border-top:1px solid var(--line)}

/* ===== CONTENT ===== */
.content{max-width:1560px;margin:0 auto;padding:24px 28px 30px}

/* Hero */
.hero{position:relative;overflow:hidden;display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.5fr);gap:28px;align-items:center;padding:30px 32px;margin-bottom:24px;border-radius:22px;color:#fff;
    background:linear-gradient(120deg,#0b2146 0%,#16386a 60%,#1d4a85 100%);box-shadow:0 18px 40px rgba(13,37,71,.25)}
.hero::before{content:"";position:absolute;inset:0;background-image:linear-gradient(rgba(255,255,255,.045) 1px,transparent 1px),linear-gradient(90deg,rgba(255,255,255,.045) 1px,transparent 1px);background-size:36px 36px;mask-image:linear-gradient(90deg,transparent 10%,#000 90%);-webkit-mask-image:linear-gradient(90deg,transparent 10%,#000 90%)}
.hero::after{content:"";position:absolute;width:320px;height:320px;right:-110px;top:-160px;border-radius:50%;border:1px solid rgba(212,162,74,.4);box-shadow:0 0 0 40px rgba(212,162,74,.05)}
.hero>*{position:relative;z-index:1}
.hero-date{display:inline-flex;align-items:center;gap:8px;padding:5px 13px;border-radius:20px;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.14);font-size:var(--fs-xs);color:#e0e9f8}
.hero h1{font-size:var(--fs-2xl);font-weight:800;letter-spacing:-.4px;line-height:1.3;margin:14px 0 4px;background:linear-gradient(90deg,#fff,var(--gold-2));-webkit-background-clip:text;background-clip:text;-webkit-text-fill-color:transparent}
.hero p{font-size:var(--fs-md);color:#bccbe3;max-width:420px}
.hero-stats{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.stat-card{padding:18px;border-radius:16px;background:rgba(255,255,255,.09);border:1px solid rgba(255,255,255,.15);backdrop-filter:blur(6px);transition:background .2s,transform .2s}
.stat-card:hover{background:rgba(255,255,255,.15);transform:translateY(-3px)}
.stat-icon{width:38px;height:38px;display:grid;place-items:center;border-radius:11px;background:linear-gradient(135deg,var(--gold-2),var(--gold));color:#1b1203;font-size:15px;margin-bottom:12px}
.stat-info h2{font-size:28px;line-height:1.1;font-weight:800}
.stat-info span{display:block;font-size:var(--fs-sm);font-weight:600;color:#e4ecf8;margin-top:6px}
.stat-info small{display:block;font-size:var(--fs-xs);color:#9fb3d3;line-height:1.4;margin-top:2px}

/* Cards */
.card{position:relative;overflow:hidden;padding:24px 26px;background:var(--card);border:1px solid var(--line);border-radius:20px;box-shadow:var(--shadow);margin-bottom:24px}
.card::before{content:"";position:absolute;left:0;top:0;right:0;height:4px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.card-heading{display:flex;justify-content:space-between;align-items:center;gap:12px;margin-bottom:20px}
.card-heading-left{display:flex;align-items:center;gap:13px}
.section-icon{width:42px;height:42px;flex-shrink:0;border-radius:13px;display:grid;place-items:center;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:15px;box-shadow:0 6px 14px rgba(13,37,71,.25)}
.card-heading h2{font-size:var(--fs-xl);font-weight:700;color:var(--ink);line-height:1.3}
.card-heading p{font-size:var(--fs-sm);color:var(--muted);margin-top:1px}

/* Forms */
.form-grid,.submit-grid{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:16px}
.field{min-width:0}
.field label{display:block;color:#33466a;font-size:var(--fs-sm);font-weight:600;margin-bottom:6px}
.input-wrap{height:42px;display:flex;align-items:center;gap:10px;padding:0 13px;min-width:0;background:#f6f8fc;border:1px solid #d8e0ec;border-radius:12px;transition:.2s}
.input-wrap:hover{border-color:#bccbe2}
.input-wrap:focus-within{border-color:var(--blue);background:#fff;box-shadow:0 0 0 4px rgba(37,99,208,.12)}
.input-wrap>i{color:#7a8ca8;font-size:12px;flex-shrink:0}
.input-wrap:focus-within>i{color:var(--blue)}
.input-wrap input,.input-wrap select{width:100%;min-width:0;height:100%;border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-md)}
.input-wrap input::placeholder{color:#9aa8bf}
.input-wrap select{cursor:pointer}
.btn{min-height:42px;padding:0 20px;border:1px solid transparent;border-radius:12px;display:inline-flex;align-items:center;justify-content:center;gap:8px;font-size:var(--fs-md);font-weight:600;transition:.2s}
.btn-primary{background:linear-gradient(135deg,var(--navy),var(--navy-2));color:#fff;box-shadow:0 6px 16px rgba(13,37,71,.25)}
.btn-primary:hover{transform:translateY(-2px);box-shadow:0 10px 22px rgba(13,37,71,.3)}
.btn-primary i{color:var(--gold-2)}
.btn-outline{background:#fff;border-color:#d3dcea;color:#33466a}
.btn-outline:hover{background:var(--bg);border-color:#b8c6de}
.search-actions{display:flex;justify-content:flex-end;gap:10px;margin-top:20px;padding-top:18px;border-top:1px dashed var(--line)}

/* Results */
.results-heading{display:flex;justify-content:space-between;align-items:center;gap:14px;margin-bottom:16px}
.results-subtitle{color:var(--muted);font-size:var(--fs-sm);margin-top:1px}
.count-chip{display:inline-flex;align-items:center;gap:7px;padding:6px 14px;background:var(--gold-soft);color:#8a6420;border:1px solid #f0dcb0;border-radius:20px;font-size:var(--fs-sm);font-weight:700}
.table-wrapper{overflow-x:auto;border:1px solid var(--line);border-radius:14px}
table{width:100%;border-collapse:collapse;white-space:nowrap;font-size:var(--fs-sm)}
thead{background:linear-gradient(180deg,#eff3fa,#e6ecf6)}
th{text-align:left;padding:13px 14px;color:#2f4570;font-size:var(--fs-xs);font-weight:700;letter-spacing:.4px;text-transform:uppercase}
td{padding:12px 14px;color:#4b5d7c;border-top:1px solid #edf1f6;font-size:var(--fs-sm)}
tbody tr:nth-child(even){background:#fafbfe}
tbody tr{transition:background .15s}
tbody tr:hover{background:#f1f6ff}
td:first-child{color:#8a98b0;font-weight:700}
.file-name{display:flex;align-items:center;gap:10px;color:var(--ink);font-weight:600}
.file-icon{font-size:16px}
.file-icon.pdf{color:#e04f4f}.file-icon.doc{color:#2f6fe0}.file-icon.xls{color:#17946a}
.no-results{padding:36px 15px;text-align:center;color:var(--muted)}
.no-results i{font-size:32px;color:#a9b8d0;margin-bottom:10px}
.no-results p{font-size:var(--fs-md)}
.pagination{display:flex;align-items:center;gap:6px;margin-top:16px}
.page-btn{min-width:34px;height:34px;padding:0 10px;border-radius:10px;border:1px solid #d8e0ec;background:#fff;color:#4b5d7c;font-size:var(--fs-sm);font-weight:600}
.page-btn:hover{background:var(--bg)}
.page-btn.active{color:#fff;background:linear-gradient(135deg,var(--navy),var(--navy-2));border-color:var(--navy)}
.page-btn:disabled{opacity:.4;cursor:not-allowed}

/* Submit */
.submit-note{display:flex;align-items:flex-start;gap:10px;margin-top:18px;padding:13px 15px;border-radius:12px;border-left:3px solid var(--gold);background:var(--gold-soft);color:#6b5326;font-size:var(--fs-sm);line-height:1.7}
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
    .sidebar{width:248px}
    .content{padding:20px 18px 28px}
    .topbar{padding:10px 18px}
    .hero{grid-template-columns:1fr;gap:20px;padding:24px}
    .form-grid,.submit-grid{grid-template-columns:repeat(2,minmax(0,1fr))}
    table{min-width:1150px}
}

/* Mobile */
.sidebar-overlay{display:none}
@media (max-width:700px){
    .sidebar{position:fixed;left:-290px;top:0;bottom:0;width:268px;height:auto;min-height:100%;box-shadow:10px 0 30px rgba(0,25,60,.3);transition:left .28s ease}
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
    .profile-popover{width:210px;top:50px}
    .content{padding:16px 12px 24px}
    .hero{padding:20px 16px;border-radius:16px;margin-bottom:16px}
    .hero h1{font-size:21px}
    .hero-stats{grid-template-columns:1fr;gap:10px}
    .stat-card{display:flex;align-items:center;gap:12px;padding:12px 14px}
    .stat-icon{margin-bottom:0}
    .stat-info h2{font-size:20px}
    .stat-info span{margin-top:0}
    .card{padding:18px 13px;border-radius:16px;margin-bottom:16px}
    .card-heading{margin-bottom:16px}
    .card-heading h2{font-size:var(--fs-lg)}
    .section-icon{width:36px;height:36px}
    .form-grid,.submit-grid{grid-template-columns:1fr;gap:13px}
    .input-wrap{height:44px}
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
    .card::before{display:none}
    .table-wrapper{overflow:visible;border:none}
    table{min-width:0;white-space:normal;font-size:10px}
    th,td{padding:7px;color:#000}
}

/* ===== Process additions ===== */
.dept-pill{display:inline-block;padding:3px 11px;border-radius:20px;background:#e9effa;color:#2f4570;font-size:11px;font-weight:600}
.status-badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:var(--gold-soft);border:1px solid #f0dcb0;color:#8a6420;font-size:10px;font-weight:700}
.alert{display:flex;align-items:flex-start;gap:10px;padding:12px 14px;border-radius:12px;margin-bottom:18px;font-size:var(--fs-sm);line-height:1.7}
.alert-success{background:#e5f7ef;color:#0f6b4b;border-left:3px solid #17946a}
.empty-note{padding:28px 15px;text-align:center;color:var(--muted);font-size:var(--fs-md)}
.print-only{display:none}
@media print{
    .submitted-card{display:none!important}
    .print-only{display:block}
    .print-only h2{font-size:16px;margin-bottom:2px}
    .print-only p{font-size:11px;margin-bottom:6px;color:#333}
    .results-heading,.count-chip,.results-card>div[style],.results-card .table-wrapper{display:none!important}
    .print-only .table-wrapper{display:block!important;border:none}
    .print-only table{min-width:0;white-space:normal;font-size:10px}
    .print-only th,.print-only td{border:1px solid #999;padding:5px 6px;color:#000}
    .dept-pill{background:none;padding:0}
}

/* ===== File upload ===== */
.upload-field{margin-top:18px}
.field label.dropzone{display:flex;flex-direction:column;align-items:center;justify-content:center;text-align:center;gap:10px;margin-bottom:0;padding:28px 20px;border:1.5px dashed #b4c3dc;border-radius:16px;background:linear-gradient(180deg,#f8fafd,#f1f5fb);cursor:pointer;transition:.2s}
.field label.dropzone:hover,.field label.dropzone.dragover{border-color:var(--blue);background:#f0f6ff}
.dropzone:focus-within{border-color:var(--blue);box-shadow:0 0 0 4px rgba(37,99,208,.12)}
.dropzone input{position:absolute;width:1px;height:1px;opacity:0;pointer-events:none}
.dropzone-icon{width:54px;height:54px;flex-shrink:0;display:grid;place-items:center;border-radius:16px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:20px;box-shadow:0 8px 18px rgba(13,37,71,.25)}
.dropzone-text strong{display:block;font-size:var(--fs-md);color:var(--ink)}
.dropzone-text strong span{color:var(--blue);text-decoration:underline}
.dropzone-text small{display:block;font-size:var(--fs-xs);color:var(--muted);margin-top:2px}
.file-chip{display:flex;align-items:center;gap:12px;padding:12px 14px;border:1px solid #cfe3d9;border-radius:14px;background:#f2fbf6}
.file-chip .chip-icon{font-size:22px}
.file-chip .chip-info{flex:1;min-width:0}
.file-chip .chip-info strong{display:block;font-size:var(--fs-md);color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.file-chip .chip-info small{font-size:var(--fs-xs);color:#4d7a64}
.chip-remove{width:30px;height:30px;flex-shrink:0;border:1px solid #e3c9cd;border-radius:8px;background:#fff;color:#c93636;font-size:12px;transition:.2s}
.chip-remove:hover{background:#c93636;color:#fff;border-color:#c93636}
.attach-cell{display:inline-flex;align-items:center;gap:8px;color:var(--ink);font-weight:600}
@media (max-width:700px){.dropzone{padding:20px 12px}}

/* ===== Scrollable results ===== */
.results-scroll{max-height:520px;overflow-y:auto;overflow-x:auto;scrollbar-width:thin;scrollbar-color:#b4c3dc transparent}
.results-scroll::-webkit-scrollbar{width:8px;height:8px}
.results-scroll::-webkit-scrollbar-thumb{background:#b4c3dc;border-radius:8px}
.results-scroll thead th{position:sticky;top:0;z-index:2;background:#e9eff8;box-shadow:0 1px 0 var(--line)}
@media (max-width:700px){.results-scroll{max-height:440px}}
@media print{.results-scroll{max-height:none;overflow:visible}}

/* ===== Row print button ===== */
.row-print-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #d3dcea;background:#fff;color:#27487a;font-size:var(--fs-xs);font-weight:600;transition:.2s}
.row-print-btn i{color:var(--gold);font-size:11px}
.row-print-btn:hover{background:linear-gradient(135deg,var(--navy),var(--navy-2));border-color:var(--navy);color:#fff}
.row-print-btn:hover i{color:var(--gold-2)}
.row-view-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #cfe0fa;background:#eef4ff;color:var(--blue);font-size:var(--fs-xs);font-weight:600;transition:.2s}
.row-view-btn i{font-size:11px}
.row-view-btn:hover{background:var(--blue);border-color:var(--blue);color:#fff}

.row-delete-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;border:1px solid #e3c9cd;background:#fff;color:#c93636;font-size:var(--fs-xs);font-weight:600;transition:.2s}
.row-delete-btn i{font-size:11px}
.row-delete-btn:hover{background:#c93636;border-color:#c93636;color:#fff}

/* ===== Document viewer ===== */
.doc-modal{position:fixed;inset:0;z-index:60;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(7,27,55,.6);backdrop-filter:blur(3px)}
.doc-dialog{width:min(1000px,100%);height:min(90vh,840px);display:flex;flex-direction:column;overflow:hidden;background:#fff;border-radius:20px;box-shadow:0 24px 60px rgba(7,27,55,.4)}
.doc-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:16px 20px;border-bottom:1px solid var(--line);position:relative}
.doc-head::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:3px;background:linear-gradient(90deg,var(--navy),var(--navy-2) 60%,var(--gold))}
.doc-title{display:flex;align-items:center;gap:12px;min-width:0}
.doc-title h3{font-size:var(--fs-xl);font-weight:700;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.doc-title p{font-size:var(--fs-xs);color:var(--muted)}
.doc-close{width:36px;height:36px;flex-shrink:0;border:1px solid var(--line);border-radius:10px;background:#fff;color:#5e7290;transition:.2s}
.doc-close:hover{background:#c93636;border-color:#c93636;color:#fff}
.doc-meta{display:flex;flex-wrap:wrap;gap:8px;padding:12px 20px;background:#f8fafd;border-bottom:1px solid var(--line)}
.doc-meta span{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:#fff;border:1px solid var(--line);color:#33466a;font-size:var(--fs-xs);font-weight:600}
.doc-meta span i{color:var(--gold)}
.doc-body{flex:1;min-height:0;background:#e9eef6;display:flex;align-items:center;justify-content:center;overflow:auto}
.doc-body object,.doc-body iframe{width:100%;height:100%;border:0;background:#fff}
.doc-body img{max-width:100%;max-height:100%;object-fit:contain;display:block}
.doc-fallback{max-width:380px;padding:30px 20px;text-align:center;color:var(--muted)}
.doc-fallback i{font-size:40px;color:#a9b8d0;margin-bottom:12px}
.doc-fallback p{font-size:var(--fs-md);margin-bottom:4px}
.doc-foot{display:flex;justify-content:flex-end;flex-wrap:wrap;gap:10px;padding:14px 20px;border-top:1px solid var(--line)}
.doc-foot a.btn{text-decoration:none}
@media (max-width:700px){
    .doc-modal{padding:0}
    .doc-dialog{height:100%;border-radius:0}
    .doc-foot .btn{flex:1;padding:0 10px}
}
@media print{.doc-modal{display:none!important}}

/* ===== UI POLISH ===== */
::selection{background:rgba(212,162,74,.35);color:var(--navy)}
html{scroll-behavior:smooth}
body{-webkit-font-smoothing:antialiased}

/* Entrance animation */
@keyframes fadeUp{from{opacity:0;transform:translateY(14px)}to{opacity:1;transform:none}}
@keyframes floatGlow{0%,100%{transform:translate(0,0)}50%{transform:translate(-18px,14px)}}
.hero,.card{animation:fadeUp .55s cubic-bezier(.2,.7,.2,1) both}
.search-card{animation-delay:.08s}.results-card{animation-delay:.16s}.submit-card{animation-delay:.24s}.submitted-card{animation-delay:.32s}

/* Topbar */
.topbar{box-shadow:0 4px 18px rgba(16,37,71,.05)}
.topbar::after{content:"";position:absolute;left:0;right:0;bottom:-1px;height:2px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55) 50%,transparent)}
.profile{box-shadow:0 2px 8px rgba(16,37,71,.06);transition:box-shadow .2s,border-color .2s}
.profile:hover{border-color:#cdd8ea;box-shadow:0 6px 16px rgba(16,37,71,.1)}
.profile-avatar{box-shadow:0 0 0 3px rgba(212,162,74,.22)}

/* Sidebar nav */
.nav-link{transition:background .2s,color .2s,transform .2s}
.nav-link:hover{transform:translateX(4px)}
.nav-link.active{background:linear-gradient(90deg,rgba(212,162,74,.18),rgba(255,255,255,.05));box-shadow:inset 0 0 0 1px rgba(212,162,74,.18)}
.nav-link.active::before{box-shadow:0 0 12px rgba(212,162,74,.7)}

/* Hero */
.hero{background:linear-gradient(120deg,#0a1d3f 0%,#16386a 55%,#215596 100%)}
.hero::after{animation:floatGlow 9s ease-in-out infinite}
.hero h1{font-size:30px}
.hero-date{backdrop-filter:blur(6px)}
.stat-card{position:relative;overflow:hidden;background:linear-gradient(160deg,rgba(255,255,255,.14),rgba(255,255,255,.05))}
.stat-card::before{content:"";position:absolute;left:0;top:0;right:0;height:3px;background:linear-gradient(90deg,var(--gold),var(--gold-2),transparent)}
.stat-icon{box-shadow:0 8px 18px rgba(212,162,74,.35)}
.stat-info h2{letter-spacing:-.5px;text-shadow:0 2px 12px rgba(0,0,0,.2)}

/* Cards */
.card{transition:box-shadow .3s,transform .3s}
.card:hover{box-shadow:0 2px 4px rgba(16,37,71,.05),0 18px 40px rgba(16,37,71,.1)}
.card-heading{padding-bottom:16px;border-bottom:1px solid var(--line);position:relative}
.card-heading::after{content:"";position:absolute;left:0;bottom:-1px;width:64px;height:2px;border-radius:2px;background:linear-gradient(90deg,var(--gold),var(--gold-2))}
.results-heading .card-heading{border-bottom:0;padding-bottom:0}
.results-heading .card-heading::after{display:none}
.section-icon{box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}

/* Forms */
.field label{letter-spacing:.2px}
.input-wrap{background:#f7f9fd;box-shadow:inset 0 1px 2px rgba(16,37,71,.04)}
.input-wrap:focus-within{box-shadow:0 0 0 4px rgba(37,99,208,.12),0 6px 16px rgba(37,99,208,.08)}
.input-wrap select{appearance:none;-webkit-appearance:none;padding-right:20px;
    background:url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' width='10' height='6' viewBox='0 0 10 6'><path d='M1 1l4 4 4-4' fill='none' stroke='%237a8ca8' stroke-width='1.6' stroke-linecap='round' stroke-linejoin='round'/></svg>") no-repeat right 2px center}

/* Buttons */
.btn{position:relative;overflow:hidden}
.btn-primary::after{content:"";position:absolute;top:0;left:-70%;width:50%;height:100%;background:linear-gradient(100deg,transparent,rgba(255,255,255,.28),transparent);transform:skewX(-20deg);transition:left .6s}
.btn-primary:hover::after{left:130%}
.btn:active{transform:translateY(0) scale(.98)}
.btn-outline:hover{border-color:var(--gold);color:var(--navy)}

/* Count chip */
.count-chip{background:linear-gradient(135deg,#fff6df,#fbeac0);box-shadow:0 3px 10px rgba(212,162,74,.18)}

/* Tables */
.table-wrapper{box-shadow:inset 0 0 0 1px var(--line),0 4px 14px rgba(16,37,71,.04)}
thead{background:linear-gradient(180deg,#f1f5fb,#e4ebf6)}
th{border-bottom:2px solid #d5deed;color:#26406b}
.results-scroll thead th{background:#e9eff8}
tbody tr{position:relative}
tbody tr:hover{background:linear-gradient(90deg,#fff7e6,#f1f6ff 40%)}
tbody tr:hover td:first-child{box-shadow:inset 3px 0 0 var(--gold)}
td:first-child{color:var(--navy-2)}
#resultsTable td:nth-child(6){font-weight:700;color:var(--navy-2);letter-spacing:.3px}
#resultsTable td:nth-child(9){font-weight:600;color:#33466a}
.file-icon.doc{background:#eaf1fd;padding:6px;border-radius:8px;font-size:13px;width:28px;height:28px;display:inline-grid;place-items:center}

/* Department pills */
.dept-pill{border:1px solid transparent}
.dept-Institutions{background:#e8f0ff;color:#1f4fa8;border-color:#cfdffc}
.dept-Councils{background:#fdf3dc;color:#8a6420;border-color:#f0dcb0}
.dept-Accounts{background:#e3f6ee;color:#11704f;border-color:#c5ead9}

/* Row buttons */
.row-view-btn,.row-print-btn,.row-delete-btn{box-shadow:0 1px 2px rgba(16,37,71,.06)}
.row-view-btn:hover,.row-print-btn:hover,.row-delete-btn:hover{transform:translateY(-1px);box-shadow:0 6px 12px rgba(16,37,71,.14)}

/* Upload */
.field label.dropzone{background:repeating-linear-gradient(135deg,#f8fafd 0 12px,#f3f7fc 12px 24px)}
.field label.dropzone:hover .dropzone-icon{transform:translateY(-3px) scale(1.05)}
.dropzone-icon{transition:transform .25s}
.submit-note{background:linear-gradient(90deg,#fdf3dc,#fffaf0)}
.file-chip{box-shadow:0 4px 12px rgba(23,148,106,.1)}

/* Alerts */
.alert-success{box-shadow:0 4px 14px rgba(23,148,106,.12);border-radius:12px}

/* Viewer */
.doc-dialog{animation:fadeUp .3s ease both}

/* Scrollbars */
.content{scrollbar-width:thin}
::-webkit-scrollbar{width:10px;height:10px}
::-webkit-scrollbar-thumb{background:#c3cfe3;border-radius:10px;border:2px solid transparent;background-clip:content-box}

/* Footer */
.footer{margin-top:6px;padding-top:16px;border-top:1px solid var(--line)}

@media (max-width:700px){.hero h1{font-size:23px}.card-heading{padding-bottom:12px}}
@media (prefers-reduced-motion:reduce){*{animation:none!important;transition:none!important}}
@media print{.hero,.card{animation:none}.topbar::after,.card-heading::after{display:none}}

/* Page Numbers group */
.page-numbers{grid-column:1/-1;padding:16px 18px 18px;border:1px solid var(--line);border-radius:14px;background:linear-gradient(180deg,#f8fafd,#f3f7fc)}
.page-numbers-title{display:flex;align-items:center;gap:8px;margin-bottom:14px;font-size:var(--fs-md);font-weight:700;color:var(--navy)}
.page-numbers-title i{color:var(--gold)}
.page-numbers-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:16px}
.page-numbers .hint{display:block;margin-top:5px;font-size:var(--fs-xs);color:var(--muted)}
#totalPages{font-weight:700;color:var(--navy-2)}
@media (max-width:700px){.page-numbers{padding:14px 12px}.page-numbers-grid{grid-template-columns:1fr;gap:13px}}

/* ===== SUBJECT OFFICER DASHBOARD ===== */
section[id]{scroll-margin-top:84px}
.nav-count{margin-left:auto;min-width:22px;height:20px;padding:0 7px;border-radius:20px;background:var(--gold);color:#1b1203;font-style:normal;font-size:11px;font-weight:700;display:grid;place-items:center}
.badge{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;font-size:10px;font-weight:700;border:1px solid transparent}
.badge-in{background:#e3f6ee;color:#11704f;border-color:#c5ead9}
.badge-out{background:var(--gold-soft);color:#8a6420;border-color:#f0dcb0}
.badge-done{background:#e8f0ff;color:#1f4fa8;border-color:#cfdffc}
.form-grid.cols-3{grid-template-columns:repeat(3,minmax(0,1fr))}
.row-return-btn,.row-edit-btn{display:inline-flex;align-items:center;gap:6px;height:30px;padding:0 12px;border-radius:8px;font-size:var(--fs-xs);font-weight:600;transition:.2s;box-shadow:0 1px 2px rgba(16,37,71,.06)}
.row-return-btn{border:1px solid #c5ead9;background:#e3f6ee;color:#11704f}
.row-return-btn:hover{background:#17946a;border-color:#17946a;color:#fff;transform:translateY(-1px)}
.row-edit-btn{border:1px solid #cfe0fa;background:#eef4ff;color:var(--blue)}
.row-edit-btn:hover{background:var(--blue);border-color:var(--blue);color:#fff;transform:translateY(-1px)}
.row-print-btn:disabled{opacity:.45;cursor:not-allowed;transform:none}
.action-cell{display:flex;gap:8px}
.list-toolbar{display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap;margin-bottom:14px}
.mini-select{height:38px;display:inline-flex;align-items:center;gap:8px;padding:0 12px;background:#f7f9fd;border:1px solid #d8e0ec;border-radius:12px}
.mini-select i{color:#7a8ca8;font-size:12px}
.mini-select select{border:0;outline:0;background:transparent;color:var(--ink);font-size:var(--fs-sm);cursor:pointer}
.empty-note.inline{padding:22px 15px}
.issue-hint{margin-top:14px;padding:12px 14px;border-radius:12px;border-left:3px solid var(--gold);background:var(--gold-soft);color:#6b5326;font-size:var(--fs-sm);line-height:1.7}
.alert-error{background:#fdecea;color:#8e271d;border-left:3px solid #d9534f}

/* Edit / return dialogs */
.doc-dialog.edit-dialog{height:auto;max-height:92vh}
.doc-dialog.small-dialog{width:min(480px,100%);height:auto}
.edit-dialog form,.small-dialog form{display:flex;flex-direction:column;min-height:0;flex:1}
.modal-body{padding:18px 20px;overflow:auto;flex:1;min-height:0}
.edit-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:14px}
.edit-grid .full{grid-column:1/-1}
.archive-box{margin-top:16px;padding:14px 16px 16px;border:1px solid #f0dcb0;border-radius:14px;background:linear-gradient(90deg,#fdf3dc,#fffaf0)}
.archive-box-title{display:flex;align-items:center;gap:8px;margin-bottom:12px;font-size:var(--fs-md);font-weight:700;color:#8a6420}
.archive-box-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
.info-line{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:14px}
.info-line span{display:inline-flex;align-items:center;gap:6px;padding:4px 11px;border-radius:20px;background:#f8fafd;border:1px solid var(--line);color:#33466a;font-size:var(--fs-xs);font-weight:600}
.form-error{display:none;margin-top:14px}
@media (max-width:1150px){.form-grid.cols-3{grid-template-columns:repeat(2,minmax(0,1fr))}}
@media (max-width:700px){
    .form-grid.cols-3,.edit-grid,.archive-box-grid{grid-template-columns:1fr}
    .modal-body{padding:14px}
    .list-toolbar{align-items:flex-start}
}

/* ===== Topbar title + icon (same as Admin dashboard) ===== */
.topbar-title{display:flex;align-items:center;gap:12px;min-width:0}
.title-icon{width:42px;height:42px;flex-shrink:0;display:grid;place-items:center;border-radius:13px;background:linear-gradient(135deg,var(--navy),var(--navy-2));color:var(--gold-2);font-size:17px;box-shadow:0 8px 18px rgba(13,37,71,.28),inset 0 1px 0 rgba(255,255,255,.18)}
.title-text{min-width:0;padding-left:14px;border-left:2px solid var(--gold)}
.title-text h1{font-size:21px;font-weight:800;line-height:1.2;letter-spacing:.2px;color:var(--navy)}
.title-text>span{display:block;font-size:var(--fs-xs);color:var(--muted);margin-top:2px}
@media (max-width:700px){
    .title-icon{width:34px;height:34px;font-size:14px;border-radius:10px}
    .title-text{padding-left:10px}
    .title-text h1{font-size:16px}
    .title-text>span{display:none}
}

/* ===== Top boxes (same size as Admin dashboard) ===== */
.hero{grid-template-columns:1fr;gap:18px;padding:22px 24px}
.hero h1{font-size:30px;margin:14px 0 4px}
.hero-stats,.hero-stats-6{grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}
.hero-stats .stat-card,.hero-stats-6 .stat-card{display:block;padding:12px;border-radius:16px}
.hero-stats .stat-card:hover{transform:translateY(-3px)}
.hero-stats .stat-card::before{height:3px}
.hero-stats .stat-icon{width:30px;height:30px;margin-bottom:8px;font-size:12px;border-radius:9px;box-shadow:0 8px 18px rgba(212,162,74,.35)}
.hero-stats .stat-info h2{font-size:22px;line-height:1.1;letter-spacing:-.4px}
.hero-stats .stat-info span{font-size:11px;margin-top:5px;line-height:1.4}
.hero-stats .stat-info small{display:block;font-size:10px;line-height:1.35;margin-top:3px}

@media (max-width:1150px){
    .hero{padding:20px;gap:16px}
    .hero-stats,.hero-stats-6{grid-template-columns:repeat(3,minmax(0,1fr))}
}
@media (max-width:700px){
    .hero{padding:20px 16px}
    .hero h1{font-size:23px}
    .hero-stats,.hero-stats-6{grid-template-columns:repeat(2,minmax(0,1fr));gap:8px}
    .hero-stats .stat-card,.hero-stats-6 .stat-card{display:flex;align-items:center;gap:10px;padding:10px}
    .hero-stats .stat-icon{width:28px;height:28px;margin-bottom:0;flex-shrink:0}
    .hero-stats .stat-info h2{font-size:18px}
    .hero-stats .stat-info span{font-size:10px;margin-top:2px}
    .hero-stats .stat-info small{font-size:9px}
}
@media (max-width:380px){
    .hero-stats,.hero-stats-6{grid-template-columns:1fr}
}
</style>
</head>

<body>

<div class="sidebar-overlay" id="sidebarOverlay"></div>

<div class="app" id="app">

    <!-- SIDEBAR -->
    <aside class="sidebar" id="sidebar">

        <div class="brand">
            <div class="logo-ring">
                <div class="logo-box">
                    <img src="logo.webp" alt="Council Logo" class="logo" onerror="this.parentElement.style.display='none'">
                </div>
            </div>
            <div class="brand-text">
                <div class="sinhala">දකුණු පළාත් සභා<br>ලේකම් කාර්යාලය</div>
                <div class="english">Southern Provincial Council Secretariat</div>
                <div class="system-name"><i class="fa-solid fa-box-archive"></i> Archives File Management System</div>
            </div>
        </div>

        <div class="sidebar-label">MAIN MENU</div>

        <nav class="sidebar-menu">
            <a href="subject_dashboard.php" class="nav-link active"><i class="fa-solid fa-house"></i><span>Dashboard</span></a>
            <a href="#pendingSection" class="nav-link"><i class="fa-solid fa-inbox"></i><span>Files from Users</span></a>
            <a href="#resultsSection" class="nav-link"><i class="fa-solid fa-box-archive"></i><span>Archive Files</span></a>
            <a href="#issueSection" class="nav-link"><i class="fa-solid fa-file-export"></i><span>Issue a File</span></a>
            <a href="#loansSection" class="nav-link"><i class="fa-solid fa-clipboard-list"></i><span>Issued Files List</span></a>
            <a href="subject_change_password.php" class="nav-link"><i class="fa-solid fa-shield-halved"></i><span>Change Password</span></a>
            <a href="logout.php" class="nav-link logout-link" onclick="return confirm('Are you sure you want to logout?')"></a>
        </nav>

        <div class="sidebar-bottom">
            <div class="sidebar-quote">
                <i class="fa-solid fa-quote-right quote-mark"></i>
                <div class="quote-top"><span>OUR COMMITMENT</span></div>
                <p>Efficient Records for a <strong>Better Tomorrow</strong></p>
                <div class="quote-line"></div>
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
                <div class="topbar-title">
                    <div class="title-icon"><i class="fa-solid fa-user-tie"></i></div>
                    <div class="title-text">
                        <h1>Subject Officer</h1>
                        <span>Southern Provincial Council Secretariat</span>
                    </div>
                </div>
            </div>

                <div class="profile">
                <div class="profile-avatar"><i class="fa-solid fa-user"></i></div>
                <div class="profile-details">
                <strong title="<?= htmlspecialchars($officerName, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($officerName, ENT_QUOTES, 'UTF-8') ?></strong>
                <span>Subject Officer Account</span>
                </div>  
                <button class="profile-toggle" id="profileToggle" type="button" aria-label="Profile menu" aria-expanded="false">
                    <i class="fa-solid fa-chevron-down"></i>
                </button>

               <div class="profile-popover" id="profilePopover" hidden>
                <strong><?= htmlspecialchars($officerName, ENT_QUOTES, 'UTF-8') ?></strong>
                <p><i class="fa-solid fa-user-tag"></i> Subject Officer Account</p>
                <a href="subject_change_password.php"><i class="fa-solid fa-lock"></i> Change Password</a>
                <a href="logout.php" onclick="return confirm('Are you sure you want to logout?')"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </div>
        </header>

        <div class="content">

            <div class="alert alert-success" id="pageAlert" hidden>
                <i class="fa-solid fa-circle-check"></i>
                <span id="pageAlertText"></span>
            </div>

            <!-- HERO + STATS -->
            <section class="hero">
                <div>
                    <span class="hero-date"><i class="fa-regular fa-calendar"></i><span id="currentDate"></span></span>
                    <h1>Subject Officer Portal</h1>
                    <p>Review submitted files, add them to the archive and keep track of issued files.</p>
                </div>

                <div class="hero-stats hero-stats-6">
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-box-archive"></i></div>
                        <div class="stat-info">
                            <h2 id="totalFiles">0</h2>
                            <span>Available Archive Files</span>
                            <small>Files added to the archive</small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-building-columns"></i></div>
                        <div class="stat-info">
                            <h2 id="totalDepartments">0</h2>
                            <span>Departments</span>
                            <small>Departments represented</small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div class="stat-info">
                            <h2 id="matchingFiles">0</h2>
                            <span>Matching Results</span>
                            <small>Files matching your search</small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-inbox"></i></div>
                        <div class="stat-info">
                            <h2 id="statPending">0</h2>
                            <span>Waiting for Review</span>
                            <small>Files sent by users</small>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon"><i class="fa-solid fa-file-export"></i></div>
                        <div class="stat-info">
                            <h2 id="statIssued">0</h2>
                            <span>Currently Issued</span>
                            <small>Files not yet returned</small>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SEARCH -->
            <section class="card search-card" id="searchSection">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-magnifying-glass"></i></div>
                        <div>
                            <h2>Search Archive Files</h2>
                            <p>Fill in one or more fields. Only the fields you fill are used to filter the files.</p>
                        </div>
                    </div>
                </div>

                <form id="searchForm">
                    <div class="form-grid cols-3">
                        <div class="field">
                            <label for="searchName">File Name</label>
                            <div class="input-wrap"><i class="fa-regular fa-file-lines"></i><input type="text" id="searchName" placeholder="Enter file name or part of it..."></div>
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
                            <div class="input-wrap"><i class="fa-solid fa-hashtag"></i><input type="text" id="searchSubject" placeholder="Enter subject number..."></div>
                        </div>

                        <div class="field">
                            <label for="searchArchive">Archive Number</label>
                            <div class="input-wrap"><i class="fa-solid fa-box-archive"></i><input type="text" id="searchArchive" placeholder="Enter archive number..."></div>
                        </div>

                        <div class="field">
                            <label for="searchRack">Archive Tree Number</label>
                            <div class="input-wrap"><i class="fa-solid fa-table-cells-large"></i><input type="text" id="searchRack" placeholder="Enter Archive Tree Number..."></div>
                        </div>

                        <div class="field">
                            <label for="searchStatus">Status</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-circle-half-stroke"></i>
                                <select id="searchStatus">
                                    <option value="">All</option>
                                    <option value="in">In Archive</option>
                                    <option value="out">Issued</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="search-actions">
                        <button class="btn btn-outline" type="button" id="clearSearch"><i class="fa-solid fa-rotate-left"></i> Clear Filters</button>
                        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-magnifying-glass"></i> Search Files</button>
                    </div>
                </form>
            </section>

            <!-- FILES SENT BY USERS -->
            <section class="card" id="pendingSection">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-inbox"></i></div>
                        <div>
                            <h2>Files Sent by Users</h2>
                            <p>Review the details, correct them if needed, then add the Archive Number and Archive Tree Number to add the file to the archive.</p>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:14px">
                    <span class="count-chip"><i class="fa-solid fa-inbox"></i><span id="pendingCount">0 files waiting</span></span>
                </div>

                <div class="table-wrapper results-scroll">
                    <table id="pendingTable">
                        <thead>
                            <tr>
                                <th>No.</th><th>Submission Date</th><th>File Name</th><th>Department</th><th>Subject Number</th><th>File Number</th>
                                <th>Start Date</th><th>End Date</th><th>Right Side</th><th>Left Side</th><th>Pages</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="pendingBody"></tbody>
                    </table>
                    <div class="empty-note" id="pendingEmpty">No files are waiting for review.</div>
                </div>
            </section>

            <!-- ARCHIVE RESULTS -->
            <section class="card results-card" id="resultsSection">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-table-list"></i></div>
                        <div>
                            <h2>Archive Search Results</h2>
                            <p>All files added to the archive. You can edit the details or issue a file.</p>
                        </div>
                    </div>
                </div>

                <div style="margin-bottom:14px">
                    <span class="count-chip"><i class="fa-solid fa-folder"></i><span id="resultCount">0 files found</span></span>
                </div>

                <div class="table-wrapper results-scroll" id="resultsScroll">
                    <table id="resultsTable">
                        <thead>
                            <tr>
                                <th>No.</th><th>File Name</th><th>Department</th><th>Subject Number</th><th>File Number</th><th>Archive Number</th><th>Archive Tree Number</th>
                                <th>Start Date</th><th>End Date</th><th>Right Side</th><th>Left Side</th><th>Pages</th><th>Added Date</th><th>Status</th><th>Action</th>
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
            </section>

            <!-- ISSUE A FILE -->
            <section class="card" id="issueSection">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-file-export"></i></div>
                        <div>
                            <h2>Issue a File from Archive</h2>
                            <p>Record who took a file from the archive and the date it was taken.</p>
                        </div>
                    </div>
                </div>

                <div class="alert alert-success" id="issueAlert" hidden>
                    <i class="fa-solid fa-circle-check"></i>
                    <span>The issued file was saved and added to the Issued Files List.</span>
                </div>

                <form id="issueForm">
                    <div class="form-grid cols-3">
                        <div class="field">
                            <label for="issueFile">Archive File</label>
                            <div class="input-wrap">
                                <i class="fa-solid fa-box-archive"></i>
                                <select id="issueFile" required></select>
                            </div>
                        </div>

                        <div class="field">
                            <label for="takenBy">Taken By</label>
                            <div class="input-wrap"><i class="fa-solid fa-user"></i><input type="text" id="takenBy" placeholder="Name of the person who took the file" required></div>
                        </div>

                        <div class="field">
                            <label for="takenDate">Date Taken</label>
                            <div class="input-wrap"><i class="fa-regular fa-calendar"></i><input type="date" id="takenDate" required></div>
                        </div>
                    </div>

                    <div class="issue-hint" id="issueHint" hidden>No files are available to issue. All archive files are already issued, or no files have been added yet.</div>

                    <div class="search-actions">
                        <button type="reset" class="btn btn-outline" id="issueReset"><i class="fa-solid fa-rotate-left"></i> Clear Form</button>
                        <button type="submit" class="btn btn-primary" id="issueSave"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                    </div>
                </form>
            </section>

            <!-- ISSUED FILES LIST -->
            <section class="card" id="loansSection">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-clipboard-list"></i></div>
                        <div>
                            <h2>Issued Files List</h2>
                            <p>When a file is returned, enter the return date and save.</p>
                        </div>
                    </div>
                </div>

                <div class="list-toolbar">
                    <span class="count-chip"><i class="fa-solid fa-clipboard-list"></i><span id="loanCount">0 records</span></span>
                    <label class="mini-select"><i class="fa-solid fa-filter"></i>
                        <select id="loanFilter" aria-label="Filter issued files">
                            <option value="">All Records</option>
                            <option value="out">Not Returned</option>
                            <option value="done">Returned</option>
                        </select>
                    </label>
                </div>

                <div class="table-wrapper results-scroll">
                    <table id="loansTable">
                        <thead>
                            <tr>
                                <th>No.</th><th>File Name</th><th>Archive Number</th><th>Archive Tree Number</th><th>Taken By</th><th>Date Taken</th><th>Date Returned</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="loansBody"></tbody>
                    </table>
                    <div class="empty-note" id="loansEmpty">No issued files to show.</div>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="footer">
                <span>© <span id="footerYear">2026</span> Southern Provincial Council. All Rights Reserved.</span>
                <span>Archives File Management System</span>
            </footer>

        </div>
    </main>
</div>

<!-- EDIT / ADD TO ARCHIVE DIALOG -->
<div class="doc-modal" id="editModal" hidden>
    <div class="doc-dialog edit-dialog" role="dialog" aria-modal="true" aria-labelledby="editTitle">
        <div class="doc-head">
            <div class="doc-title">
                <div class="section-icon"><i class="fa-solid fa-pen-to-square"></i></div>
                <div style="min-width:0">
                    <h3 id="editTitle">Review File</h3>
                    <p id="editSub"></p>
                </div>
            </div>
            <button type="button" class="doc-close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="editForm" novalidate>
            <div class="modal-body">
                <div class="edit-grid">
                    <div class="field full">
                        <label for="eName">File Name</label>
                        <div class="input-wrap"><i class="fa-regular fa-file-lines"></i><input type="text" id="eName"></div>
                    </div>
                    <div class="field">
                        <label for="eDept">Department</label>
                        <div class="input-wrap">
                            <i class="fa-solid fa-building-columns"></i>
                            <select id="eDept">
                                <option value="Institutions">ආයතන(Institutions)</option>
                                <option value="Councils">සභා(Councils)</option>
                                <option value="Accounts">ගිණුම්(Accounts)</option>
                            </select>
                        </div>
                    </div>
                    <div class="field">
                        <label for="eSubject">Subject Number</label>
                        <div class="input-wrap"><i class="fa-solid fa-hashtag"></i><input type="text" id="eSubject"></div>
                    </div>
                    <div class="field">
                        <label for="eFileNo">File Number</label>
                        <div class="input-wrap"><i class="fa-regular fa-file"></i><input type="text" id="eFileNo"></div>
                    </div>
                    <div class="field">
                        <label for="eStart">File Start Date</label>
                        <div class="input-wrap"><i class="fa-regular fa-calendar-days"></i><input type="date" id="eStart"></div>
                    </div>
                    <div class="field">
                        <label for="eEnd">File End Date</label>
                        <div class="input-wrap"><i class="fa-regular fa-calendar-check"></i><input type="date" id="eEnd"></div>
                    </div>
                    <div class="field">
                        <label for="ePages">Total Pages</label>
                        <div class="input-wrap"><i class="fa-solid fa-copy"></i><input type="number" id="ePages" min="1" step="1"></div>
                    </div>
                    <div class="field">
                        <label for="eRight">Right Side</label>
                        <div class="input-wrap"><i class="fa-solid fa-arrow-right-long"></i><input type="number" id="eRight" min="0" step="1"></div>
                    </div>
                    <div class="field">
                        <label for="eLeft">Left Side</label>
                        <div class="input-wrap"><i class="fa-solid fa-arrow-left-long"></i><input type="number" id="eLeft" min="0" step="1"></div>
                    </div>
                </div>

                <div class="archive-box">
                    <div class="archive-box-title"><i class="fa-solid fa-box-archive"></i> Archive Details</div>
                    <div class="archive-box-grid">
                        <div class="field">
                            <label for="eArchive">Archive Number *</label>
                            <div class="input-wrap"><i class="fa-solid fa-box-archive"></i><input type="text" id="eArchive" placeholder="e.g. ARC-090"></div>
                        </div>
                        <div class="field">
                            <label for="eRack">Archive Tree Number*</label>
                            <div class="input-wrap"><i class="fa-solid fa-table-cells-large"></i><input type="text" id="eRack" placeholder="e.g. RK-05"></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-error form-error" id="editError"><i class="fa-solid fa-circle-exclamation"></i><span id="editErrorText"></span></div>
            </div>

            <div class="doc-foot">
                <button type="button" class="btn btn-outline" data-close><i class="fa-solid fa-xmark"></i> Cancel</button>
                <button type="submit" class="btn btn-primary" id="editSave"><i class="fa-solid fa-floppy-disk"></i> <span id="editSaveText">Save</span></button>
            </div>
        </form>
    </div>
</div>

<!-- RETURN DIALOG -->
<div class="doc-modal" id="returnModal" hidden>
    <div class="doc-dialog small-dialog" role="dialog" aria-modal="true" aria-labelledby="returnTitle">
        <div class="doc-head">
            <div class="doc-title">
                <div class="section-icon"><i class="fa-solid fa-rotate-left"></i></div>
                <div style="min-width:0">
                    <h3 id="returnTitle">Return File</h3>
                    <p id="returnSub"></p>
                </div>
            </div>
            <button type="button" class="doc-close" data-close aria-label="Close"><i class="fa-solid fa-xmark"></i></button>
        </div>

        <form id="returnForm" novalidate>
            <div class="modal-body">
                <div class="info-line" id="returnInfo"></div>
                <div class="field">
                    <label for="returnDate">Date Returned</label>
                    <div class="input-wrap"><i class="fa-regular fa-calendar-check"></i><input type="date" id="returnDate"></div>
                </div>
                <div class="alert alert-error form-error" id="returnError"><i class="fa-solid fa-circle-exclamation"></i><span id="returnErrorText"></span></div>
            </div>
            <div class="doc-foot">
                <button type="button" class="btn btn-outline" data-close><i class="fa-solid fa-xmark"></i> Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save</button>
            </div>
        </form>
    </div>
</div>

<script>
/* =====================================================================
   SUBJECT OFFICER DASHBOARD  (demonstration data)
   Data is kept in this browser (localStorage) until the database is connected.
   Replace the load()/save() calls with your PHP / database code later.
   Files saved on the User Dashboard ("archiveSubmissions") are imported
   automatically into the "Files Sent by Users" list.
   ===================================================================== */
const KEY_RECORDS = "subjectArchiveRecords";
const KEY_PENDING = "subjectPending";
const KEY_LOANS   = "subjectLoans";
const KEY_USERSUB = "archiveSubmissions";

const deptLabels = {
    Institutions:"ආයතන(Institutions)",
    Councils:"සභා(Councils)",
    Accounts:"ගිණුම්(Accounts)"
};

/* ----- sample data (used only the first time) ----- */
const seedRecords = [
    {id:"r1",name:"Finance Report 2026",department:"Accounts",subject:"SUB-001",fileNo:"FN-101",archive:"ARC-001",rack:"RK-01",start:"2026-01-02",end:"2026-06-10",right:70,left:50,pages:120,date:"2026-06-15"},
    {id:"r2",name:"Annual Budget Plan",department:"Institutions",subject:"SUB-002",fileNo:"FN-102",archive:"ARC-002",rack:"RK-01",start:"2026-01-05",end:"2026-06-05",right:45,left:40,pages:85,date:"2026-06-10"},
    {id:"r3",name:"Staff Details",department:"Institutions",subject:"SUB-015",fileNo:"FN-115",archive:"ARC-015",rack:"RK-02",start:"2025-01-03",end:"2025-11-28",right:34,left:30,pages:64,date:"2025-12-01"},
    {id:"r4",name:"Circular No. 07/2026",department:"Councils",subject:"SUB-021",fileNo:"FN-121",archive:"ARC-021",rack:"RK-02",start:"2026-03-01",end:"2026-05-15",right:7,left:5,pages:12,date:"2026-05-20"},
    {id:"r5",name:"Project Report",department:"Councils",subject:"SUB-045",fileNo:"FN-145",archive:"ARC-045",rack:"RK-03",start:"2025-03-01",end:"2025-11-20",right:50,left:46,pages:96,date:"2025-11-25"},
    {id:"r6",name:"Meeting Minutes",department:"Accounts",subject:"SUB-058",fileNo:"FN-158",archive:"ARC-058",rack:"RK-03",start:"2026-01-08",end:"2026-03-10",right:16,left:14,pages:30,date:"2026-03-15"},
    {id:"r7",name:"Council Development Plan",department:"Councils",subject:"SUB-065",fileNo:"FN-165",archive:"ARC-065",rack:"RK-04",start:"2025-02-01",end:"2025-10-05",right:80,left:70,pages:150,date:"2025-10-11"},
    {id:"r8",name:"Annual Accounts",department:"Accounts",subject:"SUB-080",fileNo:"FN-180",archive:"ARC-080",rack:"RK-04",start:"2025-01-01",end:"2025-09-12",right:95,left:80,pages:175,date:"2025-09-20"}
];
const seedPending = [
    {id:"p1",submissionDate:"2026-10-12",name:"Land Records 2025",department:"Institutions",subject:"SUB-090",fileNo:"FN-190",start:"2025-01-02",end:"2025-12-20",right:60,left:44,pages:104},
    {id:"p2",submissionDate:"2026-10-14",name:"Council Sessions Report",department:"Councils",subject:"SUB-091",fileNo:"FN-191",start:"2026-02-01",end:"2026-08-30",right:28,left:22,pages:50},
    {id:"p3",submissionDate:"2026-10-16",name:"Payment Vouchers Q3",department:"Accounts",subject:"SUB-092",fileNo:"FN-192",start:"2026-07-01",end:"2026-09-30",right:90,left:75,pages:165}
];
const seedLoans = [
    {id:"l1",recordId:"r2",takenBy:"K. Perera",takenDate:"2026-10-10",returnDate:""},
    {id:"l2",recordId:"r4",takenBy:"S. Fernando",takenDate:"2026-09-22",returnDate:"2026-09-29"}
];

/* ----- storage helpers ----- */
function load(key, fallback) {
    try {
        const v = JSON.parse(localStorage.getItem(key));
        return Array.isArray(v) ? v : fallback;
    } catch (err) { return fallback; }
}
function save(key, value) {
    try { localStorage.setItem(key, JSON.stringify(value)); } catch (err) { /* storage unavailable */ }
}
function uid() { return Date.now().toString(36) + Math.random().toString(36).slice(2, 7); }

let records = load(KEY_RECORDS, JSON.parse(JSON.stringify(seedRecords)));
let pending = load(KEY_PENDING, JSON.parse(JSON.stringify(seedPending)));
let loans   = load(KEY_LOANS,   JSON.parse(JSON.stringify(seedLoans)));
let filteredRecords = [];

/* ----- import files saved on the User Dashboard ----- */
(function importUserSubmissions() {
    const userSubs = load(KEY_USERSUB, []);
    let changed = false;

    userSubs.forEach(u => {
        if (u.imported) return;
        pending.push({
            id: uid(),
            submissionDate: u.submissionDate,
            name: u.name,
            department: u.department,
            subject: u.subject,
            fileNo: u.fileNo,
            start: u.start,
            end: u.end,
            right: u.front,
            left: u.back,
            pages: u.pages
        });
        u.imported = true;
        changed = true;
    });

    if (changed) { save(KEY_USERSUB, userSubs); }
    save(KEY_PENDING, pending);
    save(KEY_RECORDS, records);
    save(KEY_LOANS, loans);
})();

/* ----- dates ----- */
const now = new Date();
const localToday = new Date(now.getTime() - now.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
document.getElementById("currentDate").textContent =
    now.toLocaleDateString("en-GB", {day:"2-digit", month:"short", year:"numeric"});
document.getElementById("footerYear").textContent = now.getFullYear();
document.getElementById("takenDate").value = localToday;

/* ----- helpers ----- */
const $ = id => document.getElementById(id);

function escapeHTML(value) {
    return String(value ?? "").replace(/[&<>"']/g, ch => ({"&":"&amp;","<":"&lt;",">":"&gt;",'"':"&quot;","'":"&#039;"}[ch]));
}
function deptPill(dep) {
    return `<span class="dept-pill dept-${escapeHTML(dep)}">${escapeHTML(deptLabels[dep] || dep)}</span>`;
}
function fileCell(name) {
    return `<div class="file-name"><i class="fa-solid fa-file-lines file-icon doc"></i><span>${escapeHTML(name)}</span></div>`;
}
function openLoan(recordId) {
    return loans.find(l => l.recordId === recordId && !l.returnDate);
}
function recordById(id) {
    return records.find(r => r.id === id);
}

let flashTimer;
function flash(message) {
    $("pageAlertText").textContent = message;
    $("pageAlert").hidden = false;
    clearTimeout(flashTimer);
    flashTimer = setTimeout(() => { $("pageAlert").hidden = true; }, 6000);
    window.scrollTo({top: 0, behavior: "smooth"});
}

/* ===== STATS ===== */
function renderStats() {
    const issued = loans.filter(l => !l.returnDate).length;
    $("statPending").textContent = pending.length;
    $("statIssued").textContent = issued;
    $("totalFiles").textContent = records.length;
    $("totalDepartments").textContent = new Set(records.map(r => r.department)).size;
}

/* ===== FILES SENT BY USERS ===== */
function renderPending() {
    $("pendingBody").innerHTML = pending.map((f, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHTML(f.submissionDate)}</td>
            <td>${fileCell(f.name)}</td>
            <td>${deptPill(f.department)}</td>
            <td>${escapeHTML(f.subject)}</td>
            <td>${escapeHTML(f.fileNo)}</td>
            <td>${escapeHTML(f.start)}</td>
            <td>${escapeHTML(f.end)}</td>
            <td>${escapeHTML(f.right ?? "-")}</td>
            <td>${escapeHTML(f.left ?? "-")}</td>
            <td>${escapeHTML(f.pages ?? "-")}</td>
            <td><button type="button" class="row-edit-btn" data-review="${escapeHTML(f.id)}"><i class="fa-solid fa-pen-to-square"></i> Review / Add</button></td>
        </tr>`).join("");

    $("pendingCount").textContent = `${pending.length} file${pending.length === 1 ? "" : "s"} waiting`;
    $("pendingEmpty").hidden = pending.length > 0;
    $("pendingTable").style.display = pending.length ? "table" : "none";
}

/* ===== ARCHIVE RESULTS ===== */
function applySearch() {
    const name = $("searchName").value.trim().toLowerCase();
    const dept = $("searchDepartment").value;
    const subject = $("searchSubject").value.trim().toLowerCase();
    const archive = $("searchArchive").value.trim().toLowerCase();
    const rack = $("searchRack").value.trim().toLowerCase();
    const status = $("searchStatus").value;

    filteredRecords = records.filter(r => {
        const out = !!openLoan(r.id);
        return (!name || r.name.toLowerCase().includes(name))
            && (!dept || r.department === dept)
            && (!subject || String(r.subject).toLowerCase().includes(subject))
            && (!archive || String(r.archive).toLowerCase().includes(archive))
            && (!rack || String(r.rack).toLowerCase().includes(rack))
            && (!status || (status === "out" ? out : !out));
    });
}

function renderResults() {
    applySearch();
    const total = filteredRecords.length;
    $("resultCount").textContent = `${total} file${total === 1 ? "" : "s"} found`;
    $("matchingFiles").textContent = total;
    $("resultsScroll").scrollTop = 0;

    if (total === 0) {
        $("resultsBody").innerHTML = "";
        $("noResults").hidden = false;
        $("resultsTable").style.display = "none";
        return;
    }

    $("noResults").hidden = true;
    $("resultsTable").style.display = "table";

    $("resultsBody").innerHTML = filteredRecords.map((r, i) => {
        const out = !!openLoan(r.id);
        return `
        <tr>
            <td>${i + 1}</td>
            <td>${fileCell(r.name)}</td>
            <td>${deptPill(r.department)}</td>
            <td>${escapeHTML(r.subject)}</td>
            <td>${escapeHTML(r.fileNo)}</td>
            <td>${escapeHTML(r.archive)}</td>
            <td>${escapeHTML(r.rack)}</td>
            <td>${escapeHTML(r.start)}</td>
            <td>${escapeHTML(r.end)}</td>
            <td>${escapeHTML(r.right ?? "-")}</td>
            <td>${escapeHTML(r.left ?? "-")}</td>
            <td>${escapeHTML(r.pages)}</td>
            <td>${escapeHTML(r.date)}</td>
            <td>${out ? '<span class="badge badge-out"><i class="fa-solid fa-file-export"></i> Issued</span>'
                      : '<span class="badge badge-in"><i class="fa-solid fa-box-archive"></i> In Archive</span>'}</td>
            <td><div class="action-cell">
                <button type="button" class="row-edit-btn" data-edit="${escapeHTML(r.id)}"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
                <button type="button" class="row-print-btn" data-issue="${escapeHTML(r.id)}" ${out ? "disabled" : ""}><i class="fa-solid fa-file-export"></i> Issue</button>
            </div></td>
        </tr>`;
    }).join("");
}

/* ===== ISSUE FORM ===== */
function renderIssueSelect(selectedId) {
    const available = records.filter(r => !openLoan(r.id));
    const select = $("issueFile");

    select.innerHTML = '<option value="">Select archive file</option>' + available.map(r =>
        `<option value="${escapeHTML(r.id)}">${escapeHTML(r.archive)} - ${escapeHTML(r.name)}</option>`).join("");

    if (selectedId && available.some(r => r.id === selectedId)) select.value = selectedId;

    $("issueHint").hidden = available.length > 0;
    $("issueSave").disabled = available.length === 0;
}

$("issueForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const recordId = $("issueFile").value;
    const takenBy = $("takenBy").value.trim();
    const takenDate = $("takenDate").value;

    if (!recordId || !recordById(recordId)) { alert("Please select the archive file."); return; }
    if (openLoan(recordId)) { alert("This file is already issued."); renderAll(); return; }
    if (!takenBy) { alert("Please enter the name of the person who took the file."); return; }
    if (!takenDate) { alert("Please enter the date the file was taken."); return; }

    loans.unshift({id: uid(), recordId, takenBy, takenDate, returnDate: ""});
    save(KEY_LOANS, loans);

    this.reset();
    renderAll();

    const box = $("issueAlert");
    box.hidden = false;
    setTimeout(() => { box.hidden = true; }, 6000);
});

$("issueForm").addEventListener("reset", function() {
    setTimeout(() => { $("takenDate").value = localToday; }, 0);
});

/* ===== ISSUED FILES LIST ===== */
function renderLoans() {
    const filter = $("loanFilter").value;
    const list = loans.filter(l => !filter || (filter === "out" ? !l.returnDate : !!l.returnDate));

    $("loansBody").innerHTML = list.map((l, i) => {
        const r = recordById(l.recordId);
        const done = !!l.returnDate;
        return `
        <tr>
            <td>${i + 1}</td>
            <td>${r ? fileCell(r.name) : "-"}</td>
            <td>${escapeHTML(r ? r.archive : "-")}</td>
            <td>${escapeHTML(r ? r.rack : "-")}</td>
            <td>${escapeHTML(l.takenBy)}</td>
            <td>${escapeHTML(l.takenDate)}</td>
            <td>${done ? escapeHTML(l.returnDate) : "-"}</td>
            <td>${done ? '<span class="badge badge-done"><i class="fa-solid fa-circle-check"></i> Returned</span>'
                       : '<span class="badge badge-out"><i class="fa-regular fa-clock"></i> Not Returned</span>'}</td>
            <td>${done ? "-" : `<button type="button" class="row-return-btn" data-return="${escapeHTML(l.id)}"><i class="fa-solid fa-rotate-left"></i> Mark Returned</button>`}</td>
        </tr>`;
    }).join("");

    $("loanCount").textContent = `${list.length} record${list.length === 1 ? "" : "s"}`;
    $("loansEmpty").hidden = list.length > 0;
    $("loansTable").style.display = list.length ? "table" : "none";
}

$("loanFilter").addEventListener("change", renderLoans);

/* ===== RETURN DIALOG ===== */
function openReturn(loanId) {
    const loan = loans.find(l => l.id === loanId);
    if (!loan) return;
    const r = recordById(loan.recordId);

    $("returnForm").dataset.id = loanId;
    $("returnSub").textContent = r ? r.archive + " | " + r.name : "";
    $("returnInfo").innerHTML = [
        ["fa-solid fa-user", "Taken by: " + loan.takenBy],
        ["fa-regular fa-calendar", "Taken on: " + loan.takenDate]
    ].map(m => `<span><i class="${m[0]}"></i>${escapeHTML(m[1])}</span>`).join("");
    $("returnDate").value = localToday;
    $("returnError").style.display = "none";
    showModal("returnModal");
}

$("returnForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const loan = loans.find(l => l.id === this.dataset.id);
    if (!loan) { closeModals(); return; }

    const date = $("returnDate").value;
    let problem = "";
    if (!date) problem = "Please enter the date the file was returned.";
    else if (date < loan.takenDate) problem = "The return date cannot be earlier than the date the file was taken.";

    if (problem) {
        $("returnErrorText").textContent = problem;
        $("returnError").style.display = "flex";
        return;
    }

    loan.returnDate = date;
    save(KEY_LOANS, loans);
    closeModals();
    renderAll();
    flash("File returned and saved. It is available in the archive again.");
});

/* ===== EDIT / ADD TO ARCHIVE DIALOG ===== */
function openEdit(mode, item) {
    const form = $("editForm");
    form.dataset.mode = mode;
    form.dataset.id = item.id;

    $("eName").value = item.name || "";
    $("eDept").value = item.department || "Institutions";
    $("eSubject").value = item.subject || "";
    $("eFileNo").value = item.fileNo || "";
    $("eStart").value = item.start || "";
    $("eEnd").value = item.end || "";
    $("ePages").value = item.pages ?? "";
    $("eRight").value = item.right ?? "";
    $("eLeft").value = item.left ?? "";
    $("eArchive").value = item.archive || "";
    $("eRack").value = item.rack || "";

    if (mode === "pending") {
        $("editTitle").textContent = "Review & Add to Archive";
        $("editSub").textContent = "Submitted on " + item.submissionDate + ". Check the details, then enter the Archive Number and Rack Number.";
        $("editSaveText").textContent = "Save & Add to Archive";
    } else {
        $("editTitle").textContent = "Edit Archive File";
        $("editSub").textContent = (item.archive || "") + " | Added on " + item.date;
        $("editSaveText").textContent = "Save Changes";
    }

    $("editError").style.display = "none";
    showModal("editModal");
}

$("editForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const mode = this.dataset.mode;
    const id = this.dataset.id;
    const d = {
        name: $("eName").value.trim(),
        department: $("eDept").value,
        subject: $("eSubject").value.trim(),
        fileNo: $("eFileNo").value.trim(),
        start: $("eStart").value,
        end: $("eEnd").value,
        right: Number($("eRight").value),
        left: Number($("eLeft").value),
        pages: Number($("ePages").value),
        archive: $("eArchive").value.trim(),
        rack: $("eRack").value.trim()
    };

    let problem = "";
    if (!d.name) problem = "Please enter the file name.";
    else if (!d.subject) problem = "Please enter the subject number.";
    else if (!d.fileNo) problem = "Please enter the file number.";
    else if (!d.start || !d.end) problem = "Please enter the file start date and end date.";
    else if (d.end < d.start) problem = "File End Date must be on or after File Start Date.";
    else if ($("eRight").value === "" || !Number.isInteger(d.right) || d.right < 0) problem = "Please enter a valid number of pages for the Right Side (0 or more).";
    else if ($("eLeft").value === "" || !Number.isInteger(d.left) || d.left < 0) problem = "Please enter a valid number of pages for the Left Side (0 or more).";
    else if (!Number.isInteger(d.pages) || d.pages < 1) problem = "Please enter a valid Total Pages number (1 or more).";
    else if (!d.archive) problem = "Please enter the Archive Number.";
    else if (!d.rack) problem = "Please enter the Rack Number.";
    else if (records.some(r => r.id !== id && String(r.archive).toLowerCase() === d.archive.toLowerCase())) {
        problem = "This Archive Number is already used by another file.";
    }

    if (problem) {
        $("editErrorText").textContent = problem;
        $("editError").style.display = "flex";
        return;
    }

    if (mode === "pending") {
        const item = pending.find(p => p.id === id);
        if (!item) { closeModals(); return; }

        records.unshift({id: uid(), ...d, submissionDate: item.submissionDate, date: localToday});
        pending = pending.filter(p => p.id !== id);
        save(KEY_PENDING, pending);
        save(KEY_RECORDS, records);
        closeModals();
        renderAll();
        flash("File saved and added to the archive (Archive No: " + d.archive + ", Rack No: " + d.rack + ").");
    } else {
        const rec = recordById(id);
        if (!rec) { closeModals(); return; }

        Object.assign(rec, d);
        save(KEY_RECORDS, records);
        closeModals();
        renderAll();
        flash("Archive file details updated.");
    }
});

/* ===== DIALOG HELPERS ===== */
function showModal(id) {
    $(id).hidden = false;
    document.body.style.overflow = "hidden";
}
function closeModals() {
    $("editModal").hidden = true;
    $("returnModal").hidden = true;
    document.body.style.overflow = "";
}
document.querySelectorAll("[data-close]").forEach(btn => btn.addEventListener("click", closeModals));
["editModal", "returnModal"].forEach(id => {
    $(id).addEventListener("click", e => { if (e.target === $(id)) closeModals(); });
});

/* ===== TABLE BUTTONS ===== */
$("pendingBody").addEventListener("click", function(e) {
    const btn = e.target.closest("[data-review]");
    if (!btn) return;
    const item = pending.find(p => p.id === btn.dataset.review);
    if (item) openEdit("pending", item);
});

$("resultsBody").addEventListener("click", function(e) {
    const edit = e.target.closest("[data-edit]");
    if (edit) {
        const rec = recordById(edit.dataset.edit);
        if (rec) openEdit("record", rec);
        return;
    }

    const issue = e.target.closest("[data-issue]");
    if (issue && !issue.disabled) {
        renderIssueSelect(issue.dataset.issue);
        $("issueSection").scrollIntoView({behavior: "smooth", block: "start"});
        setTimeout(() => $("takenBy").focus(), 450);
    }
});

$("loansBody").addEventListener("click", function(e) {
    const btn = e.target.closest("[data-return]");
    if (btn) openReturn(btn.dataset.return);
});

/* ===== SEARCH ===== */
$("searchForm").addEventListener("submit", function(e) {
    e.preventDefault();
    renderResults();
});
$("clearSearch").addEventListener("click", function() {
    $("searchForm").reset();
    renderResults();
});

/* ===== RENDER EVERYTHING ===== */
function renderAll() {
    const keep = $("issueFile").value;
    renderStats();
    renderPending();
    renderResults();
    renderIssueSelect(keep);
    renderLoans();
}

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
        closeModals();
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
renderAll();
</script>

</body>
</html>