<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Auth guard
if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}

// Timeout de session
if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_LIFETIME) {
    session_destroy();
    header('Location: login.php?expired=1'); exit;
}

$db = getDB();

// Paramètres de filtre / recherche / pagination
$filter  = $_GET['filter']  ?? 'all';   // all | unread | read
$search  = trim($_GET['q']  ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 15;
$offset  = ($page - 1) * $perPage;

// Construction de la requête
$where  = ['archive = 0'];
$params = [];

if ($filter === 'unread') { $where[] = 'lu = 0'; }
if ($filter === 'read')   { $where[] = 'lu = 1'; }

if ($search !== '') {
    $where[] = '(nom LIKE ? OR email LIKE ? OR sujet LIKE ? OR message LIKE ?)';
    $s = "%$search%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}

$whereSQL = 'WHERE ' . implode(' AND ', $where);

// Total pour pagination
$countStmt = $db->prepare("SELECT COUNT(*) FROM messages $whereSQL");
$countStmt->execute($params);
$total    = (int)$countStmt->fetchColumn();
$pages    = max(1, ceil($total / $perPage));

// Messages de la page
$stmt = $db->prepare("SELECT * FROM messages $whereSQL ORDER BY created_at DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$messages = $stmt->fetchAll();

// Stats globales
$stats = $db->query("SELECT
    COUNT(*) as total,
    SUM(lu = 0) as unread,
    SUM(DATE(created_at) = CURDATE()) as today
  FROM messages WHERE archive = 0
")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Messages</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
/* ==============================
   RESET & ROOT
============================== */
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#04040a; --bg2:#080812; --bg3:#0d0d1a;
  --card:rgba(255,255,255,.038); --card-h:rgba(255,255,255,.06);
  --border:rgba(255,255,255,.07); --border-h:rgba(99,102,241,.4);

  --indigo:#6366f1; --indigo-lt:#818cf8;
  --purple:#8b5cf6; --cyan:#06b6d4;
  --green:#10b981; --amber:#f59e0b; --rose:#f43f5e;

  --text:#f1f5f9; --sub:#94a3b8; --muted:#475569;
  --grad:linear-gradient(135deg,#6366f1,#8b5cf6,#06b6d4);

  --font:'Inter',sans-serif; --mono:'JetBrains Mono',monospace;
  --r:12px; --r2:18px;
  --sidebar:260px;
  --trans:all .25s cubic-bezier(.4,0,.2,1);
}
html,body{height:100%;font-family:var(--font);background:var(--bg);color:var(--text);
          -webkit-font-smoothing:antialiased;font-size:15px;}

/* ==============================
   LAYOUT
============================== */
.layout{display:flex;min-height:100vh;}

/* ==============================
   SIDEBAR
============================== */
.sidebar{
  width:var(--sidebar); flex-shrink:0;
  background:var(--bg2); border-right:1px solid var(--border);
  display:flex; flex-direction:column;
  position:fixed; top:0; left:0; bottom:0; z-index:100;
  padding:28px 0;
}
.sidebar-logo{
  padding:0 24px 28px;
  border-bottom:1px solid var(--border);
  margin-bottom:16px;
}
.logo-txt{font-family:var(--mono);font-size:18px;font-weight:700;}
.logo-b{color:var(--indigo);}
.logo-sub{font-size:11px;color:var(--muted);margin-top:4px;letter-spacing:.05em;}

.nav-section{padding:0 12px;margin-bottom:8px;}
.nav-label{font-size:10px;font-weight:700;color:var(--muted);
           letter-spacing:.12em;text-transform:uppercase;padding:6px 12px 8px;}
.nav-link{
  display:flex;align-items:center;gap:11px;padding:10px 12px;
  border-radius:var(--r);color:var(--sub);text-decoration:none;
  font-size:14px;font-weight:500;transition:var(--trans);position:relative;
}
.nav-link:hover{color:var(--text);background:var(--card-h);}
.nav-link.active{color:var(--indigo-lt);background:rgba(99,102,241,.1);}
.nav-link .ico{width:18px;text-align:center;font-size:14px;}
.nav-link .badge-count{
  margin-left:auto;background:var(--rose);color:#fff;
  font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px;
}

.sidebar-footer{margin-top:auto;padding:16px 12px 0;border-top:1px solid var(--border);}
.user-row{display:flex;align-items:center;gap:12px;padding:12px;}
.avatar-sm{
  width:34px;height:34px;border-radius:8px;
  background:var(--grad);
  display:flex;align-items:center;justify-content:center;
  font-size:12px;font-weight:800;color:#fff;flex-shrink:0;
}
.user-name{font-size:13px;font-weight:600;}
.user-role{font-size:11px;color:var(--muted);}
.btn-logout{
  display:flex;align-items:center;gap:8px;width:100%;
  padding:10px 12px;border:none;background:transparent;
  color:var(--sub);font-size:14px;font-family:var(--font);
  border-radius:var(--r);cursor:pointer;transition:var(--trans);
}
.btn-logout:hover{color:var(--rose);background:rgba(244,63,94,.08);}

/* ==============================
   MAIN
============================== */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;}

/* Top bar */
.topbar{
  height:64px;display:flex;align-items:center;justify-content:space-between;
  padding:0 32px;border-bottom:1px solid var(--border);
  background:rgba(4,4,10,.85);backdrop-filter:blur(16px);
  position:sticky;top:0;z-index:50;
}
.topbar-title{font-size:17px;font-weight:700;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.topbar-time{font-family:var(--mono);font-size:12px;color:var(--muted);}
.refresh-btn{
  display:flex;align-items:center;gap:7px;padding:8px 16px;
  background:var(--card);border:1px solid var(--border);border-radius:var(--r);
  color:var(--sub);font-size:13px;font-family:var(--font);cursor:pointer;transition:var(--trans);
}
.refresh-btn:hover{border-color:var(--border-h);color:var(--indigo-lt);}

/* Content */
.content{flex:1;padding:32px;}

/* Stats */
.stats-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px;margin-bottom:28px;}
.stat-card{
  padding:22px 24px;border-radius:var(--r2);
  background:var(--card);border:1px solid var(--border);
  display:flex;align-items:center;gap:18px;transition:var(--trans);
}
.stat-card:hover{border-color:var(--border-h);transform:translateY(-2px);}
.stat-ico{
  width:48px;height:48px;border-radius:var(--r);
  display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;
}
.ico-total  {background:rgba(99,102,241,.15);color:var(--indigo-lt);}
.ico-unread {background:rgba(244,63,94,.12);color:#fb7185;}
.ico-today  {background:rgba(16,185,129,.12);color:#34d399;}
.stat-n{font-size:30px;font-weight:900;line-height:1;}
.stat-l{font-size:12px;color:var(--muted);margin-top:3px;font-weight:500;}

/* Toolbar */
.toolbar{
  display:flex;align-items:center;gap:12px;margin-bottom:20px;flex-wrap:wrap;
}
.filter-tabs{display:flex;background:var(--card);border:1px solid var(--border);
             border-radius:var(--r);padding:3px;gap:2px;}
.ftab{
  padding:7px 16px;border-radius:8px;font-size:13px;font-weight:600;
  color:var(--sub);cursor:pointer;text-decoration:none;transition:var(--trans);
  white-space:nowrap;
}
.ftab:hover{color:var(--text);}
.ftab.on{background:rgba(99,102,241,.18);color:var(--indigo-lt);}

.search-wrap{flex:1;min-width:220px;position:relative;}
.search-ico{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted);font-size:14px;}
.search-inp{
  width:100%;padding:9px 14px 9px 40px;
  background:var(--card);border:1px solid var(--border);
  border-radius:var(--r);color:var(--text);font-size:14px;
  font-family:var(--font);outline:none;transition:var(--trans);
}
.search-inp:focus{border-color:var(--indigo);box-shadow:0 0 0 3px rgba(99,102,241,.12);}
.search-inp::placeholder{color:var(--muted);}

/* Table */
.table-wrap{
  background:var(--card);border:1px solid var(--border);border-radius:var(--r2);
  overflow:hidden;
}
table{width:100%;border-collapse:collapse;}
thead th{
  padding:14px 18px;text-align:left;font-size:11px;font-weight:700;
  color:var(--muted);letter-spacing:.1em;text-transform:uppercase;
  border-bottom:1px solid var(--border);background:rgba(255,255,255,.02);
}
tbody tr{
  border-bottom:1px solid rgba(255,255,255,.04);
  transition:var(--trans);cursor:pointer;
}
tbody tr:last-child{border-bottom:none;}
tbody tr:hover{background:var(--card-h);}
tbody tr.unread{background:rgba(99,102,241,.03);}
tbody tr.unread td:first-child{border-left:3px solid var(--indigo);}

td{padding:14px 18px;font-size:14px;vertical-align:middle;}
.td-id{color:var(--muted);font-family:var(--mono);font-size:12px;width:50px;}
.td-name{font-weight:600;}
.td-email{color:var(--sub);font-size:13px;}
.td-subject{color:var(--sub);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}
.td-date{color:var(--muted);font-size:12px;font-family:var(--mono);white-space:nowrap;}
.td-actions{white-space:nowrap;text-align:right;width:110px;}

.badge-read{
  display:inline-flex;align-items:center;gap:5px;
  padding:3px 10px;border-radius:100px;font-size:11px;font-weight:700;
}
.badge-new{background:rgba(244,63,94,.12);color:#fb7185;border:1px solid rgba(244,63,94,.25);}
.badge-seen{background:rgba(71,85,105,.12);color:var(--muted);border:1px solid rgba(71,85,105,.2);}

.act-btn{
  width:32px;height:32px;border-radius:8px;border:none;cursor:pointer;
  display:inline-flex;align-items:center;justify-content:center;
  font-size:13px;transition:var(--trans);background:transparent;color:var(--muted);
}
.act-btn:hover.read-btn{color:var(--indigo-lt);background:rgba(99,102,241,.12);}
.act-btn:hover.del-btn{color:#fb7185;background:rgba(244,63,94,.1);}

/* Empty state */
.empty{
  text-align:center;padding:72px 24px;
}
.empty-ico{font-size:48px;color:var(--muted);margin-bottom:16px;}
.empty-t{font-size:17px;font-weight:700;margin-bottom:8px;}
.empty-s{font-size:14px;color:var(--muted);}

/* Pagination */
.pagination{display:flex;align-items:center;justify-content:space-between;
            margin-top:20px;flex-wrap:wrap;gap:12px;}
.page-info{font-size:13px;color:var(--muted);}
.page-links{display:flex;gap:6px;}
.page-link{
  width:34px;height:34px;border-radius:var(--r);
  display:flex;align-items:center;justify-content:center;
  background:var(--card);border:1px solid var(--border);
  color:var(--sub);text-decoration:none;font-size:13px;font-weight:600;
  transition:var(--trans);
}
.page-link:hover{border-color:var(--border-h);color:var(--indigo-lt);}
.page-link.active{background:rgba(99,102,241,.15);border-color:var(--indigo);color:var(--indigo-lt);}
.page-link.disabled{opacity:.3;pointer-events:none;}

/* ==============================
   MODAL
============================== */
.overlay{
  position:fixed;inset:0;background:rgba(0,0,0,.6);backdrop-filter:blur(6px);
  z-index:500;opacity:0;pointer-events:none;transition:opacity .25s;
}
.overlay.open{opacity:1;pointer-events:all;}

.modal{
  position:fixed;top:50%;left:50%;transform:translate(-50%,-46%) scale(.96);
  z-index:501;width:min(680px,calc(100vw - 40px));max-height:90vh;
  background:var(--bg2);border:1px solid var(--border);border-radius:24px;
  overflow:hidden;display:flex;flex-direction:column;
  transition:opacity .25s,transform .25s;opacity:0;pointer-events:none;
}
.overlay.open .modal{opacity:1;transform:translate(-50%,-50%) scale(1);pointer-events:all;}

.modal-head{
  padding:22px 28px;border-bottom:1px solid var(--border);
  display:flex;align-items:center;justify-content:space-between;
  flex-shrink:0;
}
.modal-title{font-size:16px;font-weight:700;}
.modal-close{
  width:34px;height:34px;border:none;background:var(--card);border-radius:8px;
  color:var(--sub);cursor:pointer;font-size:16px;transition:var(--trans);
  display:flex;align-items:center;justify-content:center;
}
.modal-close:hover{color:var(--text);background:var(--card-h);}

.modal-body{padding:28px;overflow-y:auto;flex:1;}
.msg-meta{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:22px;}
.meta-field{
  padding:12px 16px;background:rgba(255,255,255,.03);
  border:1px solid var(--border);border-radius:var(--r);
}
.meta-label{font-size:10px;font-weight:700;color:var(--muted);text-transform:uppercase;
            letter-spacing:.1em;margin-bottom:4px;}
.meta-val{font-size:14px;font-weight:600;}
.msg-subject{
  font-size:15px;font-weight:700;margin-bottom:14px;padding-bottom:14px;
  border-bottom:1px solid var(--border);
}
.msg-text{
  font-size:15px;color:var(--sub);line-height:1.8;white-space:pre-wrap;word-break:break-word;
}
.modal-foot{
  padding:18px 28px;border-top:1px solid var(--border);
  display:flex;gap:10px;flex-shrink:0;
}
.btn-sm{
  display:inline-flex;align-items:center;gap:7px;padding:9px 18px;
  border-radius:var(--r);font-size:13px;font-weight:600;cursor:pointer;
  border:1px solid var(--border);background:var(--card);color:var(--sub);
  font-family:var(--font);transition:var(--trans);text-decoration:none;
}
.btn-sm:hover{border-color:var(--border-h);color:var(--indigo-lt);}
.btn-sm.danger:hover{border-color:rgba(244,63,94,.4);color:#fb7185;background:rgba(244,63,94,.08);}
.btn-sm.primary{background:var(--grad);border:none;color:#fff;}
.btn-sm.primary:hover{opacity:.9;transform:translateY(-1px);}

/* Toast */
.toast{
  position:fixed;bottom:28px;right:28px;z-index:600;
  padding:12px 20px;border-radius:var(--r);
  background:#1a1a2e;border:1px solid var(--border);
  font-size:13px;font-weight:600;display:flex;align-items:center;gap:9px;
  transform:translateY(80px);opacity:0;transition:all .3s;
  pointer-events:none;
}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{border-color:rgba(16,185,129,.3);color:#34d399;}
.toast.error{border-color:rgba(244,63,94,.3);color:#fb7185;}

/* Responsive */
@media(max-width:900px){
  .sidebar{transform:translateX(-100%);transition:var(--trans);}
  .sidebar.open{transform:none;}
  .main{margin-left:0;}
  .stats-grid{grid-template-columns:1fr;}
  .msg-meta{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="layout">

  <!-- ==============================
       SIDEBAR
  ============================== -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-txt"><span class="logo-b">{</span>AMD<span class="logo-b">}</span></div>
      <div class="logo-sub">Dashboard Admin</div>
    </div>

    <nav class="nav-section">
      <div class="nav-label">Navigation</div>
      <a href="index.php" class="nav-link active">
        <i class="fas fa-inbox ico"></i> Messages
        <?php if ($stats['unread'] > 0): ?>
          <span class="badge-count"><?= (int)$stats['unread'] ?></span>
        <?php endif; ?>
      </a>
      <a href="gallery.php" class="nav-link">
        <i class="fas fa-images ico"></i> Galerie
      </a>
      <a href="/portfolio/" class="nav-link" target="_blank">
        <i class="fas fa-globe ico"></i> Voir le portfolio
      </a>
    </nav>

    <div class="sidebar-footer">
      <div class="user-row">
        <div class="avatar-sm">A</div>
        <div>
          <div class="user-name"><?= htmlspecialchars($_SESSION['admin_user']) ?></div>
          <div class="user-role">Administrateur</div>
        </div>
      </div>
      <a href="logout.php" class="btn-logout">
        <i class="fas fa-right-from-bracket"></i> Déconnexion
      </a>
    </div>
  </aside>

  <!-- ==============================
       MAIN
  ============================== -->
  <div class="main">

    <!-- Topbar -->
    <header class="topbar">
      <div class="topbar-title">
        <i class="fas fa-inbox" style="color:var(--indigo-lt);margin-right:10px;"></i>
        Messages reçus
      </div>
      <div class="topbar-right">
        <span class="topbar-time" id="clock"></span>
        <button class="refresh-btn" onclick="location.reload()">
          <i class="fas fa-rotate-right"></i> Actualiser
        </button>
      </div>
    </header>

    <!-- Content -->
    <div class="content">

      <!-- Stats -->
      <div class="stats-grid">
        <div class="stat-card">
          <div class="stat-ico ico-total"><i class="fas fa-envelope"></i></div>
          <div>
            <div class="stat-n" id="s-total"><?= (int)$stats['total'] ?></div>
            <div class="stat-l">Messages total</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-ico ico-unread"><i class="fas fa-envelope-open"></i></div>
          <div>
            <div class="stat-n" id="s-unread"><?= (int)$stats['unread'] ?></div>
            <div class="stat-l">Non lus</div>
          </div>
        </div>
        <div class="stat-card">
          <div class="stat-ico ico-today"><i class="fas fa-calendar-day"></i></div>
          <div>
            <div class="stat-n" id="s-today"><?= (int)$stats['today'] ?></div>
            <div class="stat-l">Aujourd'hui</div>
          </div>
        </div>
      </div>

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="filter-tabs">
          <a href="?filter=all<?= $search ? '&q='.urlencode($search) : '' ?>"
             class="ftab <?= $filter === 'all'    ? 'on' : '' ?>">Tous (<?= (int)$stats['total'] ?>)</a>
          <a href="?filter=unread<?= $search ? '&q='.urlencode($search) : '' ?>"
             class="ftab <?= $filter === 'unread' ? 'on' : '' ?>">Non lus (<?= (int)$stats['unread'] ?>)</a>
          <a href="?filter=read<?= $search ? '&q='.urlencode($search) : '' ?>"
             class="ftab <?= $filter === 'read'   ? 'on' : '' ?>">Lus</a>
        </div>

        <form method="GET" class="search-wrap">
          <input type="hidden" name="filter" value="<?= htmlspecialchars($filter) ?>">
          <i class="fas fa-magnifying-glass search-ico"></i>
          <input type="search" name="q" class="search-inp"
                 placeholder="Rechercher par nom, email, sujet…"
                 value="<?= htmlspecialchars($search) ?>">
        </form>
      </div>

      <!-- Table -->
      <div class="table-wrap">
        <?php if (empty($messages)): ?>
          <div class="empty">
            <div class="empty-ico"><i class="fas fa-inbox"></i></div>
            <div class="empty-t">Aucun message</div>
            <div class="empty-s"><?= $search ? 'Aucun résultat pour «&nbsp;'.htmlspecialchars($search).'&nbsp;»' : 'Votre boîte est vide.' ?></div>
          </div>
        <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>#</th>
              <th>Expéditeur</th>
              <th>Sujet</th>
              <th>Statut</th>
              <th>Date</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
          <?php foreach ($messages as $m): ?>
            <tr class="<?= !$m['lu'] ? 'unread' : '' ?>"
                data-id="<?= $m['id'] ?>" onclick="openMessage(<?= $m['id'] ?>)">
              <td class="td-id">#<?= $m['id'] ?></td>
              <td>
                <div class="td-name"><?= htmlspecialchars($m['nom']) ?></div>
                <div class="td-email"><?= htmlspecialchars($m['email']) ?></div>
              </td>
              <td class="td-subject"><?= htmlspecialchars($m['sujet']) ?></td>
              <td>
                <?php if (!$m['lu']): ?>
                  <span class="badge-read badge-new"><i class="fas fa-circle" style="font-size:6px"></i>Nouveau</span>
                <?php else: ?>
                  <span class="badge-read badge-seen"><i class="fas fa-check"></i>Lu</span>
                <?php endif; ?>
              </td>
              <td class="td-date"><?= date('d/m/Y H:i', strtotime($m['created_at'])) ?></td>
              <td class="td-actions" onclick="event.stopPropagation()">
                <button class="act-btn read-btn" title="<?= $m['lu'] ? 'Marquer non lu' : 'Marquer lu' ?>"
                        onclick="toggleRead(<?= $m['id'] ?>, <?= $m['lu'] ?>, this)">
                  <i class="fas <?= $m['lu'] ? 'fa-envelope' : 'fa-envelope-open' ?>"></i>
                </button>
                <button class="act-btn del-btn" title="Supprimer"
                        onclick="deleteMsg(<?= $m['id'] ?>, this)">
                  <i class="fas fa-trash"></i>
                </button>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php endif; ?>
      </div>

      <!-- Pagination -->
      <?php if ($pages > 1): ?>
      <div class="pagination">
        <div class="page-info">
          <?= $offset + 1 ?>–<?= min($offset + $perPage, $total) ?> sur <?= $total ?> message<?= $total > 1 ? 's' : '' ?>
        </div>
        <div class="page-links">
          <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= max(1,$page-1) ?>"
             class="page-link <?= $page <= 1 ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-left"></i>
          </a>
          <?php for ($i = max(1,$page-2); $i <= min($pages,$page+2); $i++): ?>
            <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= $i ?>"
               class="page-link <?= $i === $page ? 'active' : '' ?>"><?= $i ?></a>
          <?php endfor; ?>
          <a href="?filter=<?= $filter ?>&q=<?= urlencode($search) ?>&page=<?= min($pages,$page+1) ?>"
             class="page-link <?= $page >= $pages ? 'disabled' : '' ?>">
            <i class="fas fa-chevron-right"></i>
          </a>
        </div>
      </div>
      <?php endif; ?>

    </div><!-- /content -->
  </div><!-- /main -->
</div><!-- /layout -->

<!-- ==============================
     MODAL MESSAGE
============================== -->
<div class="overlay" id="overlay" onclick="closeModal(event)">
  <div class="modal" id="modal">
    <div class="modal-head">
      <div class="modal-title" id="modal-title">Message</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body" id="modal-body">
      <!-- Chargé dynamiquement -->
    </div>
    <div class="modal-foot" id="modal-foot"></div>
  </div>
</div>

<!-- Toast notification -->
<div class="toast" id="toast"></div>

<!-- ==============================
     JAVASCRIPT
============================== -->
<script>
// Horloge
(function clock(){
  const el = document.getElementById('clock');
  function tick(){
    const now = new Date();
    el.textContent = now.toLocaleDateString('fr-FR',{weekday:'short',day:'2-digit',month:'short'})
      + ' · ' + now.toLocaleTimeString('fr-FR',{hour:'2-digit',minute:'2-digit',second:'2-digit'});
  }
  tick(); setInterval(tick, 1000);
})();

// Toast
function toast(msg, type='success'){
  const el = document.getElementById('toast');
  el.className = 'toast ' + type;
  el.innerHTML = `<i class="fas fa-${type==='success'?'circle-check':'circle-exclamation'}"></i>${msg}`;
  el.classList.add('show');
  setTimeout(()=>el.classList.remove('show'), 3200);
}

// AJAX helper
async function api(body){
  const r = await fetch('action.php', {
    method:'POST',
    headers:{'Content-Type':'application/json'},
    body: JSON.stringify(body)
  });
  return r.json();
}

// Ouvrir message
async function openMessage(id){
  const overlay = document.getElementById('overlay');
  const body    = document.getElementById('modal-body');
  const foot    = document.getElementById('modal-foot');
  const title   = document.getElementById('modal-title');

  body.innerHTML = '<div style="text-align:center;padding:40px;color:var(--muted)"><i class="fas fa-spinner fa-spin fa-2x"></i></div>';
  foot.innerHTML = '';
  overlay.classList.add('open');

  const res = await api({action:'get', id});
  if (!res.success){ toast('Erreur de chargement.','error'); return; }

  const m = res.data;
  title.textContent = `Message #${m.id}`;

  body.innerHTML = `
    <div class="msg-meta">
      <div class="meta-field">
        <div class="meta-label">Expéditeur</div>
        <div class="meta-val">${esc(m.nom)}</div>
      </div>
      <div class="meta-field">
        <div class="meta-label">Email</div>
        <div class="meta-val" style="font-size:13px">${esc(m.email)}</div>
      </div>
      <div class="meta-field">
        <div class="meta-label">Date de réception</div>
        <div class="meta-val" style="font-family:var(--mono);font-size:13px">
          ${new Date(m.created_at).toLocaleString('fr-FR')}
        </div>
      </div>
      <div class="meta-field">
        <div class="meta-label">IP</div>
        <div class="meta-val" style="font-family:var(--mono);font-size:12px;color:var(--muted)">${esc(m.ip||'—')}</div>
      </div>
    </div>
    <div class="msg-subject">
      <i class="fas fa-tag" style="color:var(--indigo-lt);margin-right:8px;font-size:12px"></i>
      ${esc(m.sujet)}
    </div>
    <div class="msg-text">${esc(m.message)}</div>
  `;

  foot.innerHTML = `
    <a href="mailto:${esc(m.email)}?subject=Re: ${esc(m.sujet)}" class="btn-sm primary">
      <i class="fas fa-reply"></i> Répondre
    </a>
    <button class="btn-sm" onclick="toggleRead(${m.id},1,null,true)">
      <i class="fas fa-envelope"></i> Marquer non lu
    </button>
    <button class="btn-sm danger" onclick="deleteMsg(${m.id},null,true)">
      <i class="fas fa-trash"></i> Supprimer
    </button>
  `;

  // Mettre à jour la ligne dans le tableau
  const row = document.querySelector(`tr[data-id="${m.id}"]`);
  if(row){
    row.classList.remove('unread');
    const badge = row.querySelector('.badge-read');
    if(badge){ badge.className='badge-read badge-seen'; badge.innerHTML='<i class="fas fa-check"></i>Lu'; }
  }
  refreshStats();
}

function closeModal(e){
  if(e && e.target !== document.getElementById('overlay')) return;
  document.getElementById('overlay').classList.remove('open');
}
document.addEventListener('keydown', e => { if(e.key==='Escape') document.getElementById('overlay').classList.remove('open'); });

// Basculer lu/non lu
async function toggleRead(id, isRead, btn, fromModal=false){
  const action = isRead ? 'mark_unread' : 'mark_read';
  const res = await api({action, id});
  if(!res.success){ toast('Erreur.','error'); return; }

  if(!fromModal){
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if(row){
      if(isRead){
        row.classList.add('unread');
        const badge = row.querySelector('.badge-read');
        if(badge){ badge.className='badge-read badge-new'; badge.innerHTML='<i class="fas fa-circle" style="font-size:6px"></i>Nouveau'; }
      } else {
        row.classList.remove('unread');
        const badge = row.querySelector('.badge-read');
        if(badge){ badge.className='badge-read badge-seen'; badge.innerHTML='<i class="fas fa-check"></i>Lu'; }
      }
    }
    if(btn) btn.innerHTML = `<i class="fas ${isRead?'fa-envelope-open':'fa-envelope'}"></i>`;
  } else {
    document.getElementById('overlay').classList.remove('open');
    setTimeout(()=>location.reload(), 400);
  }

  toast(isRead ? 'Marqué comme non lu.' : 'Marqué comme lu.');
  refreshStats();
}

// Supprimer
async function deleteMsg(id, btn, fromModal=false){
  if(!confirm('Supprimer ce message définitivement ?')) return;
  const res = await api({action:'delete', id});
  if(!res.success){ toast('Erreur.','error'); return; }

  if(!fromModal){
    const row = document.querySelector(`tr[data-id="${id}"]`);
    if(row){ row.style.opacity='0'; row.style.transform='translateX(20px)'; setTimeout(()=>row.remove(),300); }
  } else {
    document.getElementById('overlay').classList.remove('open');
    setTimeout(()=>location.reload(), 400);
  }
  toast('Message supprimé.');
  refreshStats();
}

// Rafraîchir les stats
async function refreshStats(){
  const res = await api({action:'stats'});
  if(res.success){
    document.getElementById('s-total').textContent  = res.total;
    document.getElementById('s-unread').textContent = res.unread;
    document.getElementById('s-today').textContent  = res.today;
    const sBadge = document.querySelector('.badge-count');
    if(sBadge){ sBadge.textContent=res.unread; sBadge.style.display=res.unread>0?'':'none'; }
  }
}

// Échapper HTML
function esc(s){ return String(s||'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;'); }

// Recherche en direct (debounce)
const si = document.querySelector('.search-inp');
let st;
si.addEventListener('input', ()=>{
  clearTimeout(st);
  st = setTimeout(()=>si.form.submit(), 500);
});
</script>
</body>
</html>
