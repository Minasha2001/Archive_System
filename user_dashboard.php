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
.brand{display:flex;flex-direction:column;align-items:center;text-align:center;gap:12px;padding:20px 20px 20px;position:relative;margin-bottom:8px}
.brand::after{content:"";position:absolute;left:24px;right:24px;bottom:0;height:1px;background:linear-gradient(90deg,transparent,rgba(212,162,74,.55),transparent)}
.logo-ring{width:36px;height:36px;position:relative;display:grid;place-items:center;flex-shrink:0}
.logo-ring::before{content:"";position:absolute;inset:6px;border-radius:50%;background:radial-gradient(circle,rgba(212,162,74,.28),transparent 68%)}
.logo-box{position:relative;width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:none;border:0;padding:0}
.logo{width:100%;height:100%;object-fit:contain;filter:drop-shadow(0 6px 14px rgba(0,0,0,.45))}
.brand-text .sinhala{font-family:'Noto Sans Sinhala',sans-serif;font-size:14px;font-weight:700;line-height:1.7}
.brand-text .english{font-size:10.5px;line-height:1.5;color:#aebfdc;margin-top:2px;letter-spacing:.3px}
.brand-text .system-name{display:inline-flex;align-items:center;gap:6px;font-size:10px;font-weight:600;letter-spacing:.5px;text-transform:uppercase;color:var(--gold-2);margin-top:12px;padding:5px 13px;border-radius:6px;border:1px solid rgba(212,162,74,.4);background:rgba(212,162,74,.08)}
.sidebar-label{padding:16px 26px 10px;color:#7389ad;font-size:10px;font-weight:700;letter-spacing:1.8px;position:relative}
.sidebar-menu{padding:0 14px;position:relative}
.nav-link{display:flex;align-items:center;gap:14px;min-height:44px;padding:8px 14px;margin-bottom:4px;border-radius:8px;text-decoration:none;color:#b7c6df;font-weight:500;position:relative;transition:background .2s,color .2s}
.nav-link i{width:20px;text-align:center;font-size:14px;color:#7f9bc6;transition:color .2s}
.nav-link:hover{color:#fff;background:rgba(255,255,255,.06)}
.nav-link:hover i{color:#fff}
.nav-link.active{color:#fff;font-weight:600;background:rgba(255,255,255,.09)}
.nav-link.active i{color:var(--gold)}
.nav-link.active::before{content:"";position:absolute;left:-14px;top:8px;bottom:8px;width:4px;border-radius:0 4px 4px 0;background:var(--gold)}
.nav-link.logout-link{margin-top:16px;color:#f2b8bf}
.nav-link.logout-link i{color:#ef8a96}
.nav-link.logout-link:hover{background:rgba(239,90,110,.14);color:#fff}
.sidebar-bottom{margin-top:auto;padding:18px;position:relative}
.sidebar-quote{padding:14px 15px;border-radius:10px;border-left:3px solid var(--gold);background:rgba(255,255,255,.05)}
.quote-top{display:flex;align-items:center;gap:8px;margin-bottom:6px;color:var(--gold-2);font-size:10px;font-weight:700;letter-spacing:1px}
.sidebar-quote p{font-size:var(--fs-sm);line-height:1.7;color:#dbe5f5}
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
                <div class="system-name"><i class="fa-solid fa-box-archive"></i> Archive Management System</div>
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
                            <small>Files added to the archive</small>
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
                            <p>Fill in one or more fields. Only the fields you fill are used to filter the files.</p>
                        </div>
                    </div>
                </div>

                <form id="searchForm">
                    <div class="form-grid">
                        <div class="field">
                            <label for="searchName">File Name</label>
                            <div class="input-wrap">
                                <i class="fa-regular fa-file-lines"></i>
                                <input type="text" id="searchName" placeholder="Enter file name or part of it...">
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
                                <th>File Number</th>
                                <th>Archive Number</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Pages</th>
                                <th>Added Date</th>
                                <th>Document</th>
                                <th>Print</th>
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

                <!-- Print version: ALL matching files, or ONE file when a row Print button is used -->
                <div class="print-only">
                    <h2>දකුණු පළාත් සභා ලේකම් කාර්යාලය - Archive Search Results</h2>
                    <p id="printInfo"></p>
                    <div class="table-wrapper">
                        <table>
                            <thead>
                                <tr>
                                    <th>No.</th><th>File Name</th><th>Department</th><th>Subject No.</th><th>File No.</th>
                                    <th>Archive No.</th><th>Start Date</th><th>End Date</th><th>Pages</th>
                                </tr>
                            </thead>
                            <tbody id="printBody"></tbody>
                        </table>
                    </div>
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

                <div class="alert alert-success" id="submitAlert" hidden>
                    <i class="fa-solid fa-circle-check"></i>
                    <span>File saved successfully and sent to the Subject Officer for review.</span>
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

                    <div class="field upload-field">
                        <label for="fileUpload">Upload File</label>
                        <label class="dropzone" id="dropzone" for="fileUpload">
                            <input type="file" id="fileUpload" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png">
                            <span class="dropzone-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                            <span class="dropzone-text">
                                <strong>Drag and drop the file here, or <span>browse</span></strong>
                                <small>PDF, DOC, DOCX, XLS, XLSX, JPG or PNG. Maximum size 25 MB.</small>
                            </span>
                        </label>
                        <div class="file-chip" id="fileChip" hidden>
                            <i class="fa-solid fa-file chip-icon" id="chipIcon"></i>
                            <div class="chip-info">
                                <strong id="chipName"></strong>
                                <small id="chipSize"></small>
                            </div>
                            <button type="button" class="chip-remove" id="chipRemove" aria-label="Remove file"><i class="fa-solid fa-xmark"></i></button>
                        </div>
                    </div>

                    <div class="submit-note">
                        <i class="fa-solid fa-circle-info"></i>
                        <span>After you save, the uploaded file and its details are sent to the Subject Officer for review. It appears in the search results once the Subject Officer adds it to the archive.</span>
                    </div>

                    <div class="submit-bottom">
                        <div class="submit-actions">
                            <button type="reset" class="btn btn-outline"><i class="fa-solid fa-rotate-left"></i> Clear Form</button>
                            <button type="submit" class="btn btn-primary submit-main-btn"><i class="fa-solid fa-floppy-disk"></i> Save</button>
                        </div>
                    </div>
                </form>
            </section>

            <!-- SUBMITTED FILES -->
            <section class="card submitted-card">
                <div class="card-heading">
                    <div class="card-heading-left">
                        <div class="section-icon"><i class="fa-solid fa-paper-plane"></i></div>
                        <div>
                            <h2>Files Sent to Subject Officer</h2>
                            <p>Files you have saved. The Subject Officer can see these for review.</p>
                        </div>
                    </div>
                </div>

                <div class="table-wrapper">
                    <table id="submittedTable">
                        <thead>
                            <tr>
                                <th>No.</th>
                                <th>Submission Date</th>
                                <th>File Name</th>
                                <th>Department</th>
                                <th>Subject Number</th>
                                <th>File Number</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Pages</th>
                                <th>Attachment</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="submittedBody"></tbody>
                    </table>
                    <div class="empty-note" id="submittedEmpty">No files submitted yet.</div>
                </div>
            </section>

            <!-- FOOTER -->
            <footer class="footer">
                <span>© <span id="footerYear">2026</span> Southern Provincial Council. All Rights Reserved.</span>
                <span>Archive Management System</span>
            </footer>

        </div>
    </main>
</div>

<!-- DOCUMENT VIEWER -->
<div class="doc-modal" id="docViewer" hidden>
    <div class="doc-dialog" role="dialog" aria-modal="true" aria-labelledby="docTitle">
        <div class="doc-head">
            <div class="doc-title">
                <div class="section-icon"><i class="fa-solid fa-file-lines"></i></div>
                <div style="min-width:0">
                    <h3 id="docTitle"></h3>
                    <p id="docSub"></p>
                </div>
            </div>
            <button type="button" class="doc-close" id="docClose" aria-label="Close viewer"><i class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="doc-meta" id="docMeta"></div>
        <div class="doc-body" id="docBody"></div>
        <div class="doc-foot">
            <a class="btn btn-outline" id="docOpen" href="#" target="_blank" rel="noopener"><i class="fa-solid fa-up-right-from-square"></i> Open in New Tab</a>
            <a class="btn btn-outline" id="docDownload" href="#" download><i class="fa-solid fa-download"></i> Download</a>
            <button type="button" class="btn btn-primary" id="docCloseBtn"><i class="fa-solid fa-xmark"></i> Close</button>
        </div>
    </div>
</div>

<script>
/* ===== SAMPLE ARCHIVE DATA (demonstration only) ===== */
const archiveFiles = [
    {name:"Finance Report 2026",department:"Accounts",subject:"SUB-001",fileNo:"FN-101",archive:"ARC-001",start:"2026-01-02",end:"2026-06-10",pages:120,date:"2026-06-15"},
    {name:"Annual Budget Plan",department:"Institutions",subject:"SUB-002",fileNo:"FN-102",archive:"ARC-002",start:"2026-01-05",end:"2026-06-05",pages:85,date:"2026-06-10"},
    {name:"Staff Details",department:"Institutions",subject:"SUB-015",fileNo:"FN-115",archive:"ARC-015",start:"2025-01-03",end:"2025-11-28",pages:64,date:"2025-12-01"},
    {name:"Circular No. 07/2026",department:"Councils",subject:"SUB-021",fileNo:"FN-121",archive:"ARC-021",start:"2026-03-01",end:"2026-05-15",pages:12,date:"2026-05-20"},
    {name:"IT System Proposal",department:"Institutions",subject:"SUB-030",fileNo:"FN-130",archive:"ARC-030",start:"2026-02-10",end:"2026-04-12",pages:40,date:"2026-04-18"},
    {name:"Project Report",department:"Councils",subject:"SUB-045",fileNo:"FN-145",archive:"ARC-045",start:"2025-03-01",end:"2025-11-20",pages:96,date:"2025-11-25"},
    {name:"Monthly Progress",department:"Councils",subject:"SUB-046",fileNo:"FN-146",archive:"ARC-046",start:"2025-01-01",end:"2025-11-15",pages:58,date:"2025-11-20"},
    {name:"Meeting Minutes",department:"Accounts",subject:"SUB-058",fileNo:"FN-158",archive:"ARC-058",start:"2026-01-08",end:"2026-03-10",pages:30,date:"2026-03-15"},
    {name:"Finance Circular",department:"Accounts",subject:"SUB-060",fileNo:"FN-160",archive:"ARC-060",start:"2026-01-15",end:"2026-02-08",pages:9,date:"2026-02-12"},
    {name:"Council Development Plan",department:"Councils",subject:"SUB-065",fileNo:"FN-165",archive:"ARC-065",start:"2025-02-01",end:"2025-10-05",pages:150,date:"2025-10-11"},
    {name:"Employee Records",department:"Institutions",subject:"SUB-070",fileNo:"FN-170",archive:"ARC-070",start:"2025-12-01",end:"2026-01-12",pages:210,date:"2026-01-18"},
    {name:"Annual Accounts",department:"Accounts",subject:"SUB-080",fileNo:"FN-180",archive:"ARC-080",start:"2025-01-01",end:"2025-09-12",pages:175,date:"2025-09-20"}
];
/* Document path for each archive file.
   Demo: uploads/<ArchiveNo>.pdf. Replace with the real stored path from the database (e.g. file.fileUrl from PHP). */
archiveFiles.forEach(f => { f.fileUrl = "uploads/" + f.archive + ".pdf"; });

const deptLabels = {
    Institutions:"ආයතන(Institutions)",
    Councils:"සභා(Councils)",
    Accounts:"ගිණුම්(Accounts)"
};
let activeFilters = [];

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

function deptPill(dep) {
    return `<span class="dept-pill">${escapeHTML(deptLabels[dep] || dep)}</span>`;
}

/* ===== SEARCH ===== */
document.getElementById("searchForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const name = document.getElementById("searchName").value.trim().toLowerCase();
    const department = document.getElementById("searchDepartment").value;
    const subject = document.getElementById("searchSubject").value.trim().toLowerCase();
    const archive = document.getElementById("searchArchive").value.trim().toLowerCase();

    activeFilters = [];
    if (name) activeFilters.push("File Name: " + document.getElementById("searchName").value.trim());
    if (department) activeFilters.push("Department: " + department);
    if (subject) activeFilters.push("Subject No: " + document.getElementById("searchSubject").value.trim());
    if (archive) activeFilters.push("Archive No: " + document.getElementById("searchArchive").value.trim());

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
    activeFilters = [];
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
            <td><div class="file-name"><i class="fa-solid fa-file-lines file-icon doc"></i><span>${escapeHTML(file.name)}</span></div></td>
            <td>${deptPill(file.department)}</td>
            <td>${escapeHTML(file.subject)}</td>
            <td>${escapeHTML(file.fileNo)}</td>
            <td>${escapeHTML(file.archive)}</td>
            <td>${escapeHTML(file.start)}</td>
            <td>${escapeHTML(file.end)}</td>
            <td>${escapeHTML(file.pages)}</td>
            <td>${escapeHTML(file.date)}</td>
            <td><button type="button" class="row-view-btn" data-view="${start + index}" aria-label="View ${escapeHTML(file.name)}"><i class="fa-solid fa-eye"></i> View</button></td>
            <td><button type="button" class="row-print-btn" data-i="${start + index}" aria-label="Print ${escapeHTML(file.name)}"><i class="fa-solid fa-print"></i> Print</button></td>
        `;

        resultsBody.appendChild(row);
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
function printDate() {
    return new Date().toLocaleDateString("en-GB", {day:"2-digit", month:"short", year:"numeric"});
}

function printFiles(list, infoText) {
    document.getElementById("printInfo").textContent = infoText;

    document.getElementById("printBody").innerHTML = list.map((file, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHTML(file.name)}</td>
            <td>${escapeHTML(deptLabels[file.department] || file.department)}</td>
            <td>${escapeHTML(file.subject)}</td>
            <td>${escapeHTML(file.fileNo)}</td>
            <td>${escapeHTML(file.archive)}</td>
            <td>${escapeHTML(file.start)}</td>
            <td>${escapeHTML(file.end)}</td>
            <td>${escapeHTML(file.pages)}</td>
        </tr>`).join("");

    window.print();
}

/* Print ALL matching files (not only the current page) */
function printResults() {
    if (filteredFiles.length === 0) {
        alert("No search results available to print.");
        return;
    }

    printFiles(
        filteredFiles,
        "Printed on " + printDate() +
        " | " + filteredFiles.length + " file" + (filteredFiles.length === 1 ? "" : "s") +
        (activeFilters.length ? " | " + activeFilters.join(", ") : "")
    );
}

/* Print ONE file (Print button in each row) */
resultsBody.addEventListener("click", function(e) {
    const btn = e.target.closest(".row-print-btn");
    if (!btn) return;

    const file = filteredFiles[Number(btn.dataset.i)];
    if (!file) return;

    printFiles([file], "Printed on " + printDate() + " | Archive No: " + file.archive);
});

/* ===== DOCUMENT VIEWER ===== */
const docViewer = document.getElementById("docViewer");
const docBody = document.getElementById("docBody");

function docFallback(message) {
    return `<div class="doc-fallback"><i class="fa-regular fa-file-excel"></i>
        <p><strong>Preview not available</strong></p>
        <p>${escapeHTML(message)}</p></div>`;
}

function openViewer(file) {
    const url = file.fileUrl || "";
    const ext = fileExt(url);
    const safeUrl = encodeURI(url);

    document.getElementById("docTitle").textContent = file.name;
    document.getElementById("docSub").textContent = (deptLabels[file.department] || file.department) + " | " + file.archive;

    document.getElementById("docMeta").innerHTML = [
        ["fa-hashtag", "Subject: " + file.subject],
        ["fa-regular fa-file", "File No: " + file.fileNo],
        ["fa-box-archive", "Archive No: " + file.archive],
        ["fa-regular fa-calendar-days", file.start + " to " + file.end],
        ["fa-copy", file.pages + " pages"],
        ["fa-regular fa-clock", "Added: " + file.date]
    ].map(m => `<span><i class="${m[0].startsWith("fa-regular") ? m[0] : "fa-solid " + m[0]}"></i>${escapeHTML(m[1])}</span>`).join("");

    if (!url) {
        docBody.innerHTML = docFallback("No document is attached to this archive record.");
    } else if (ext === "pdf") {
        docBody.innerHTML = `<object data="${safeUrl}" type="application/pdf">${docFallback("The PDF could not be loaded here. Use Open in New Tab or Download.")}</object>`;
    } else if (["jpg", "jpeg", "png"].includes(ext)) {
        docBody.innerHTML = `<img src="${safeUrl}" alt="${escapeHTML(file.name)}">`;
        docBody.querySelector("img").onerror = () => { docBody.innerHTML = docFallback("The image could not be loaded."); };
    } else {
        docBody.innerHTML = docFallback("Word and Excel files cannot be previewed in the browser. Please download the file to open it.");
    }

    const openLink = document.getElementById("docOpen");
    const downloadLink = document.getElementById("docDownload");
    openLink.hidden = downloadLink.hidden = !url;
    openLink.href = downloadLink.href = safeUrl || "#";

    docViewer.hidden = false;
    document.body.style.overflow = "hidden";
}

function closeViewer() {
    if (docViewer.hidden) return;
    docViewer.hidden = true;
    docBody.innerHTML = "";
    document.body.style.overflow = "";
}

resultsBody.addEventListener("click", function(e) {
    const btn = e.target.closest(".row-view-btn");
    if (!btn) return;

    const file = filteredFiles[Number(btn.dataset.view)];
    if (file) openViewer(file);
});

document.getElementById("docClose").addEventListener("click", closeViewer);
document.getElementById("docCloseBtn").addEventListener("click", closeViewer);
docViewer.addEventListener("click", e => { if (e.target === docViewer) closeViewer(); });

/* ===== FILE UPLOAD (drag & drop or browse) ===== */
const MAX_FILE_SIZE = 25 * 1024 * 1024;
const ALLOWED_EXT = ["pdf", "doc", "docx", "xls", "xlsx", "jpg", "jpeg", "png"];

const fileInput = document.getElementById("fileUpload");
const dropzone = document.getElementById("dropzone");
const fileChip = document.getElementById("fileChip");

function fileExt(name) {
    return name.split(".").pop().toLowerCase();
}

function fileIcon(name) {
    const ext = fileExt(name);
    if (ext === "pdf") return '<i class="fa-solid fa-file-pdf file-icon pdf"></i>';
    if (ext === "xls" || ext === "xlsx") return '<i class="fa-solid fa-file-excel file-icon xls"></i>';
    if (ext === "jpg" || ext === "jpeg" || ext === "png") return '<i class="fa-solid fa-file-image file-icon doc"></i>';
    return '<i class="fa-solid fa-file-word file-icon doc"></i>';
}

function formatSize(bytes) {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + " KB";
    return (bytes / (1024 * 1024)).toFixed(2) + " MB";
}

function fileProblem(file) {
    if (!ALLOWED_EXT.includes(fileExt(file.name))) {
        return "This file type is not allowed. Please upload a PDF, DOC, DOCX, XLS, XLSX, JPG or PNG file.";
    }
    if (file.size > MAX_FILE_SIZE) {
        return "The file is too large. Maximum size is 25 MB.";
    }
    if (file.size === 0) {
        return "The selected file is empty.";
    }
    return "";
}

function updateFileChip() {
    const file = fileInput.files[0];

    if (!file) {
        fileChip.hidden = true;
        dropzone.hidden = false;
        return;
    }

    document.getElementById("chipIcon").outerHTML =
        fileIcon(file.name).replace('class="', 'id="chipIcon" class="chip-icon ');
    document.getElementById("chipName").textContent = file.name;
    document.getElementById("chipSize").textContent = formatSize(file.size) + " - ready to submit";

    dropzone.hidden = true;
    fileChip.hidden = false;
}

fileInput.addEventListener("change", function() {
    const file = fileInput.files[0];
    const problem = file ? fileProblem(file) : "";

    if (problem) {
        alert(problem);
        fileInput.value = "";
    }

    updateFileChip();
});

["dragenter", "dragover"].forEach(type => {
    dropzone.addEventListener(type, function(e) {
        e.preventDefault();
        dropzone.classList.add("dragover");
    });
});

["dragleave", "drop"].forEach(type => {
    dropzone.addEventListener(type, function(e) {
        e.preventDefault();
        dropzone.classList.remove("dragover");
    });
});

dropzone.addEventListener("drop", function(e) {
    const file = e.dataTransfer.files[0];
    if (!file) return;

    const problem = fileProblem(file);
    if (problem) {
        alert(problem);
        return;
    }

    fileInput.files = e.dataTransfer.files;
    updateFileChip();
});

document.getElementById("chipRemove").addEventListener("click", function() {
    fileInput.value = "";
    updateFileChip();
});

/* ===== FILE SUBMISSION =====
   Demo: saved files are kept in this browser (localStorage) until the database is connected. */
const STORAGE_KEY = "archiveSubmissions";
let submissions = [];

try {
    submissions = JSON.parse(localStorage.getItem(STORAGE_KEY)) || [];
} catch (err) {
    submissions = [];
}

function saveSubmissions() {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(submissions));
    } catch (err) { /* storage unavailable - list stays for this visit only */ }
}

function renderSubmissions() {
    const body = document.getElementById("submittedBody");
    const empty = document.getElementById("submittedEmpty");
    const table = document.getElementById("submittedTable");

    body.innerHTML = submissions.map((f, i) => `
        <tr>
            <td>${i + 1}</td>
            <td>${escapeHTML(f.submissionDate)}</td>
            <td><div class="file-name"><i class="fa-solid fa-file-lines file-icon doc"></i><span>${escapeHTML(f.name)}</span></div></td>
            <td>${deptPill(f.department)}</td>
            <td>${escapeHTML(f.subject)}</td>
            <td>${escapeHTML(f.fileNo)}</td>
            <td>${escapeHTML(f.start)}</td>
            <td>${escapeHTML(f.end)}</td>
            <td>${escapeHTML(f.pages)}</td>
            <td>${f.fileName ? `<span class="attach-cell">${fileIcon(f.fileName)}${escapeHTML(f.fileName)}</span>` : "-"}</td>
            <td><span class="status-badge"><i class="fa-regular fa-clock"></i> Sent to Subject Officer</span></td>
            <td><button type="button" class="row-delete-btn" data-del="${i}" aria-label="Delete ${escapeHTML(f.name)}"><i class="fa-solid fa-trash-can"></i> Delete</button></td>
        </tr>`).join("");

    empty.hidden = submissions.length > 0;
    table.style.display = submissions.length ? "table" : "none";
}

/* Delete a submitted file (document + its details) */
document.getElementById("submittedBody").addEventListener("click", function(e) {
    const btn = e.target.closest(".row-delete-btn");
    if (!btn) return;

    const index = Number(btn.dataset.del);
    const item = submissions[index];
    if (!item) return;

    if (!confirm('Delete "' + item.name + '" and its uploaded document? This cannot be undone.')) return;

    submissions.splice(index, 1);
    saveSubmissions();
    renderSubmissions();
});

document.getElementById("submitForm").addEventListener("submit", function(e) {
    e.preventDefault();

    const startDate = document.getElementById("startDate").value;
    const endDate = document.getElementById("endDate").value;
    const submission = document.getElementById("submissionDate").value;
    const pageCount = Number(document.getElementById("pageCount").value);

    if (endDate < startDate) {
        alert("File End Date must be on or after File Start Date.");
        return;
    }

    if (submission < endDate) {
        alert("Submission Date cannot be earlier than the File End Date.");
        return;
    }

    if (!Number.isInteger(pageCount) || pageCount < 1) {
        alert("Please enter a valid number of pages.");
        return;
    }

    const uploaded = fileInput.files[0];
    if (!uploaded) {
        alert("Please upload the file you want to submit.");
        dropzone.scrollIntoView({behavior: "smooth", block: "center"});
        return;
    }

    submissions.unshift({
        submissionDate: submission,
        subject: document.getElementById("subjectNumber").value.trim(),
        department: document.getElementById("submitDepartment").value,
        fileNo: document.getElementById("fileNumber").value.trim(),
        name: document.getElementById("newFileName").value.trim(),
        start: startDate,
        end: endDate,
        pages: pageCount,
        fileName: uploaded.name,
        fileSize: uploaded.size
    });

    saveSubmissions();
    renderSubmissions();
    this.reset();

    const alertBox = document.getElementById("submitAlert");
    alertBox.hidden = false;
    setTimeout(() => { alertBox.hidden = true; }, 6000);
});

document.getElementById("submitForm").addEventListener("reset", function() {
    setTimeout(() => {
        updateFileChip();
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
        closeViewer();
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
renderSubmissions();
</script>

</body>
</html>
