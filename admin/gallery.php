<?php
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

if (empty($_SESSION['admin_logged_in'])) {
    header('Location: login.php'); exit;
}
if (time() - ($_SESSION['login_time'] ?? 0) > SESSION_LIFETIME) {
    session_destroy();
    header('Location: login.php?expired=1'); exit;
}

$db = getDB();
$items = $db->query("SELECT * FROM galerie ORDER BY ordre ASC, created_at DESC")->fetchAll();
$categories = $db->query("SELECT DISTINCT categorie FROM galerie ORDER BY categorie")->fetchAll(PDO::FETCH_COLUMN);

// Stats messages (pour badge sidebar)
$unread = (int)$db->query("SELECT COUNT(*) FROM messages WHERE lu = 0 AND archive = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — Galerie</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@500;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
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
  --r:12px; --r2:18px; --sidebar:260px;
  --trans:all .25s cubic-bezier(.4,0,.2,1);
}
html,body{height:100%;font-family:var(--font);background:var(--bg);color:var(--text);-webkit-font-smoothing:antialiased;font-size:15px;}

/* LAYOUT */
.layout{display:flex;min-height:100vh;}

/* SIDEBAR */
.sidebar{width:var(--sidebar);flex-shrink:0;background:var(--bg2);border-right:1px solid var(--border);
  display:flex;flex-direction:column;position:fixed;top:0;left:0;bottom:0;z-index:100;padding:28px 0;}
.sidebar-logo{padding:0 24px 28px;border-bottom:1px solid var(--border);margin-bottom:16px;}
.logo-txt{font-family:var(--mono);font-size:18px;font-weight:700;}
.logo-b{color:var(--indigo);}
.logo-sub{font-size:11px;color:var(--muted);margin-top:4px;letter-spacing:.05em;}
.nav-section{padding:0 12px;margin-bottom:8px;}
.nav-label{font-size:10px;font-weight:700;color:var(--muted);letter-spacing:.12em;text-transform:uppercase;padding:6px 12px 8px;}
.nav-link{display:flex;align-items:center;gap:11px;padding:10px 12px;border-radius:var(--r);
  color:var(--sub);text-decoration:none;font-size:14px;font-weight:500;transition:var(--trans);position:relative;}
.nav-link:hover{color:var(--text);background:var(--card-h);}
.nav-link.active{color:var(--indigo-lt);background:rgba(99,102,241,.1);}
.nav-link .ico{width:18px;text-align:center;font-size:14px;}
.nav-link .badge-count{margin-left:auto;background:var(--rose);color:#fff;font-size:10px;font-weight:700;padding:2px 7px;border-radius:100px;}
.sidebar-footer{margin-top:auto;padding:16px 12px 0;border-top:1px solid var(--border);}
.user-row{display:flex;align-items:center;gap:12px;padding:12px;}
.avatar-sm{width:34px;height:34px;border-radius:8px;background:var(--grad);display:flex;align-items:center;justify-content:center;font-size:12px;font-weight:800;color:#fff;flex-shrink:0;}
.user-name{font-size:13px;font-weight:600;}
.user-role{font-size:11px;color:var(--muted);}
.btn-logout{display:flex;align-items:center;gap:8px;width:100%;padding:10px 12px;border:none;background:transparent;
  color:var(--sub);font-size:14px;font-family:var(--font);border-radius:var(--r);cursor:pointer;transition:var(--trans);}
.btn-logout:hover{color:var(--rose);background:rgba(244,63,94,.08);}

/* MAIN */
.main{margin-left:var(--sidebar);flex:1;display:flex;flex-direction:column;min-height:100vh;}
.topbar{height:64px;display:flex;align-items:center;justify-content:space-between;padding:0 32px;
  border-bottom:1px solid var(--border);background:rgba(4,4,10,.85);backdrop-filter:blur(16px);position:sticky;top:0;z-index:50;}
.topbar-title{font-size:17px;font-weight:700;}
.topbar-right{display:flex;align-items:center;gap:10px;}
.content{flex:1;padding:32px;}

/* TOOLBAR */
.toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:24px;flex-wrap:wrap;}
.toolbar-left{display:flex;align-items:center;gap:10px;flex-wrap:wrap;}
.btn{display:inline-flex;align-items:center;gap:8px;padding:9px 18px;border-radius:var(--r);
  font-size:13px;font-weight:600;cursor:pointer;border:none;transition:var(--trans);font-family:var(--font);}
.btn-primary{background:var(--grad);color:#fff;}
.btn-primary:hover{opacity:.88;transform:translateY(-1px);}
.btn-ghost{background:var(--card);border:1px solid var(--border);color:var(--sub);}
.btn-ghost:hover{border-color:var(--border-h);color:var(--indigo-lt);}
.btn-sm{padding:6px 12px;font-size:12px;}
.btn-danger{background:rgba(244,63,94,.15);border:1px solid rgba(244,63,94,.3);color:#fb7185;}
.btn-danger:hover{background:rgba(244,63,94,.25);}

/* FILTER TABS */
.filter-tabs{display:flex;background:var(--card);border:1px solid var(--border);border-radius:var(--r);padding:3px;gap:2px;}
.filter-tab{padding:6px 14px;border-radius:8px;font-size:13px;font-weight:500;color:var(--sub);
  cursor:pointer;border:none;background:transparent;font-family:var(--font);transition:var(--trans);}
.filter-tab.active{background:rgba(99,102,241,.2);color:var(--indigo-lt);}

/* GALLERY GRID */
.gallery-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(240px,1fr));gap:20px;}
.gallery-card{background:var(--card);border:1px solid var(--border);border-radius:var(--r2);
  overflow:hidden;transition:var(--trans);position:relative;}
.gallery-card:hover{border-color:var(--border-h);transform:translateY(-3px);box-shadow:0 12px 32px rgba(0,0,0,.4);}
.gallery-card.hidden-item{opacity:.45;}
.card-img{width:100%;height:180px;object-fit:cover;display:block;background:var(--bg3);}
.card-img-placeholder{width:100%;height:180px;display:flex;align-items:center;justify-content:center;
  background:var(--bg3);color:var(--muted);font-size:32px;}
.card-body{padding:14px 16px;}
.card-cat{font-size:10px;font-weight:700;color:var(--cyan);text-transform:uppercase;letter-spacing:.1em;margin-bottom:6px;}
.card-title{font-size:14px;font-weight:700;margin-bottom:4px;line-height:1.4;}
.card-desc{font-size:12px;color:var(--sub);line-height:1.5;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;}
.card-actions{display:flex;gap:8px;padding:12px 16px;border-top:1px solid var(--border);}
.card-badge-hidden{position:absolute;top:10px;right:10px;background:rgba(244,63,94,.85);color:#fff;
  font-size:10px;font-weight:700;padding:3px 8px;border-radius:100px;backdrop-filter:blur(4px);}

/* EMPTY STATE */
.empty{text-align:center;padding:80px 20px;color:var(--muted);}
.empty i{font-size:48px;margin-bottom:16px;display:block;opacity:.3;}
.empty p{font-size:15px;}

/* MODAL */
.modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,.7);backdrop-filter:blur(8px);
  z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;opacity:0;pointer-events:none;transition:opacity .25s;}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--bg2);border:1px solid var(--border);border-radius:24px;width:100%;max-width:580px;
  max-height:90vh;overflow-y:auto;transform:translateY(20px);transition:transform .25s;}
.modal-overlay.open .modal{transform:translateY(0);}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:24px 28px 0;}
.modal-title{font-size:17px;font-weight:700;}
.modal-close{background:none;border:none;color:var(--sub);font-size:20px;cursor:pointer;padding:4px;
  border-radius:8px;transition:var(--trans);}
.modal-close:hover{color:var(--rose);background:rgba(244,63,94,.1);}
.modal-body{padding:24px 28px 28px;}
.field{margin-bottom:18px;}
.field label{display:block;font-size:11px;font-weight:700;color:var(--muted);text-transform:uppercase;
  letter-spacing:.1em;margin-bottom:7px;}
.inp{width:100%;padding:11px 14px;background:rgba(255,255,255,.05);border:1px solid var(--border);
  border-radius:var(--r);color:var(--text);font-size:14px;font-family:var(--font);outline:none;transition:var(--trans);}
.inp:focus{border-color:var(--indigo);background:rgba(99,102,241,.07);box-shadow:0 0 0 3px rgba(99,102,241,.12);}
textarea.inp{resize:vertical;min-height:90px;}
.inp-row{display:grid;grid-template-columns:1fr 1fr;gap:14px;}

/* UPLOAD ZONE */
.upload-zone{border:2px dashed var(--border);border-radius:var(--r2);padding:28px;text-align:center;
  cursor:pointer;transition:var(--trans);position:relative;}
.upload-zone:hover,.upload-zone.drag{border-color:var(--indigo);background:rgba(99,102,241,.05);}
.upload-zone input[type=file]{position:absolute;inset:0;opacity:0;cursor:pointer;}
.upload-zone i{font-size:28px;color:var(--muted);margin-bottom:10px;display:block;}
.upload-zone p{font-size:13px;color:var(--sub);}
.upload-zone small{font-size:11px;color:var(--muted);}
.preview-img{width:100%;height:160px;object-fit:cover;border-radius:var(--r);margin-top:12px;display:none;}

/* TOGGLE */
.toggle-row{display:flex;align-items:center;gap:12px;}
.toggle{position:relative;width:42px;height:24px;flex-shrink:0;}
.toggle input{opacity:0;width:0;height:0;}
.toggle-slider{position:absolute;inset:0;background:var(--muted);border-radius:24px;cursor:pointer;transition:var(--trans);}
.toggle-slider::before{content:'';position:absolute;width:18px;height:18px;border-radius:50%;
  background:#fff;left:3px;top:3px;transition:var(--trans);}
.toggle input:checked + .toggle-slider{background:var(--green);}
.toggle input:checked + .toggle-slider::before{transform:translateX(18px);}
.toggle-label{font-size:13px;color:var(--sub);}

/* TOAST */
.toast{position:fixed;bottom:28px;right:28px;padding:14px 20px;border-radius:var(--r2);
  font-size:14px;font-weight:600;z-index:999;transform:translateY(80px);opacity:0;transition:var(--trans);
  display:flex;align-items:center;gap:10px;max-width:360px;}
.toast.show{transform:translateY(0);opacity:1;}
.toast.success{background:#064e3b;border:1px solid #059669;color:#6ee7b7;}
.toast.error{background:#4c0519;border:1px solid #be123c;color:#fda4af;}

/* RESPONSIVE */
@media(max-width:768px){
  .sidebar{transform:translateX(-100%);transition:transform .3s;}
  .sidebar.open{transform:translateX(0);}
  .main{margin-left:0;}
  .gallery-grid{grid-template-columns:repeat(auto-fill,minmax(160px,1fr));}
  .inp-row{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="layout">

  <!-- SIDEBAR -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <div class="logo-txt"><span class="logo-b">{</span>AMD<span class="logo-b">}</span></div>
      <div class="logo-sub">Dashboard Admin</div>
    </div>
    <nav class="nav-section">
      <div class="nav-label">Navigation</div>
      <a href="index.php" class="nav-link">
        <i class="fas fa-inbox ico"></i> Messages
        <?php if ($unread > 0): ?>
          <span class="badge-count"><?= $unread ?></span>
        <?php endif; ?>
      </a>
      <a href="gallery.php" class="nav-link active">
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

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div class="topbar-title">
        <i class="fas fa-images" style="color:var(--indigo-lt);margin-right:10px;"></i>
        Galerie de réalisations
      </div>
      <div class="topbar-right">
        <span style="font-size:12px;color:var(--muted);"><?= count($items) ?> réalisation<?= count($items) > 1 ? 's' : '' ?></span>
        <button class="btn btn-primary" onclick="openModal()">
          <i class="fas fa-plus"></i> Ajouter
        </button>
      </div>
    </header>

    <div class="content">

      <!-- Toolbar -->
      <div class="toolbar">
        <div class="toolbar-left">
          <div class="filter-tabs">
            <button class="filter-tab active" data-filter="all">Tous</button>
            <button class="filter-tab" data-filter="visible">Visibles</button>
            <button class="filter-tab" data-filter="hidden">Masqués</button>
          </div>
          <?php if (!empty($categories)): ?>
          <select class="inp" id="catFilter" style="width:auto;padding:7px 12px;font-size:13px;" onchange="filterCat(this.value)">
            <option value="">Toutes catégories</option>
            <?php foreach ($categories as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
            <?php endforeach; ?>
          </select>
          <?php endif; ?>
        </div>
      </div>

      <!-- Grid -->
      <?php if (empty($items)): ?>
      <div class="empty">
        <i class="fas fa-images"></i>
        <p>Aucune réalisation pour le moment.</p>
        <button class="btn btn-primary" style="margin-top:20px;" onclick="openModal()">
          <i class="fas fa-plus"></i> Ajouter la première
        </button>
      </div>
      <?php else: ?>
      <div class="gallery-grid" id="galleryGrid">
        <?php foreach ($items as $item): ?>
        <div class="gallery-card <?= !$item['visible'] ? 'hidden-item' : '' ?>"
             data-visible="<?= $item['visible'] ?>"
             data-cat="<?= htmlspecialchars($item['categorie']) ?>">
          <?php if (!$item['visible']): ?>
            <div class="card-badge-hidden"><i class="fas fa-eye-slash"></i> Masqué</div>
          <?php endif; ?>
          <?php if ($item['image'] && file_exists(__DIR__ . '/../uploads/gallery/' . $item['image'])): ?>
            <img src="/portfolio/uploads/gallery/<?= htmlspecialchars($item['image']) ?>"
                 alt="<?= htmlspecialchars($item['titre']) ?>" class="card-img">
          <?php else: ?>
            <div class="card-img-placeholder"><i class="fas fa-image"></i></div>
          <?php endif; ?>
          <div class="card-body">
            <div class="card-cat"><?= htmlspecialchars($item['categorie']) ?></div>
            <div class="card-title"><?= htmlspecialchars($item['titre']) ?></div>
            <?php if ($item['description']): ?>
              <div class="card-desc"><?= htmlspecialchars($item['description']) ?></div>
            <?php endif; ?>
          </div>
          <div class="card-actions">
            <button class="btn btn-ghost btn-sm" onclick='editItem(<?= json_encode($item) ?>)'>
              <i class="fas fa-pen"></i> Modifier
            </button>
            <button class="btn btn-danger btn-sm" onclick="deleteItem(<?= $item['id'] ?>, this)">
              <i class="fas fa-trash"></i>
            </button>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<!-- MODAL AJOUT / ÉDITION -->
<div class="modal-overlay" id="modalOverlay" onclick="closeOnOverlay(event)">
  <div class="modal">
    <div class="modal-header">
      <div class="modal-title" id="modalTitle">Ajouter une réalisation</div>
      <button class="modal-close" onclick="closeModal()"><i class="fas fa-xmark"></i></button>
    </div>
    <div class="modal-body">
      <form id="galleryForm" enctype="multipart/form-data">
        <input type="hidden" name="action" id="formAction" value="add">
        <input type="hidden" name="id" id="formId" value="">

        <div class="field">
          <label>Titre *</label>
          <input type="text" name="titre" id="fTitre" class="inp" placeholder="Nom du projet" required>
        </div>

        <div class="inp-row">
          <div class="field">
            <label>Catégorie</label>
            <input type="text" name="categorie" id="fCategorie" class="inp" placeholder="Web, Mobile, Design…" list="catList">
            <datalist id="catList">
              <?php foreach ($categories as $cat): ?>
                <option value="<?= htmlspecialchars($cat) ?>">
              <?php endforeach; ?>
            </datalist>
          </div>
          <div class="field">
            <label>Ordre d'affichage</label>
            <input type="number" name="ordre" id="fOrdre" class="inp" placeholder="0" min="0">
          </div>
        </div>

        <div class="field">
          <label>Description</label>
          <textarea name="description" id="fDesc" class="inp" placeholder="Décrivez cette réalisation…"></textarea>
        </div>

        <div class="field">
          <label>Photo</label>
          <div class="upload-zone" id="uploadZone">
            <input type="file" name="image" id="fImage" accept="image/*" onchange="previewImage(this)">
            <i class="fas fa-cloud-arrow-up"></i>
            <p>Cliquez ou glissez une image</p>
            <small>JPG, PNG, WEBP — max 5 Mo</small>
            <img id="imgPreview" class="preview-img" alt="Aperçu">
          </div>
          <div id="currentImgWrap" style="display:none;margin-top:10px;">
            <img id="currentImg" src="" style="width:100%;height:140px;object-fit:cover;border-radius:var(--r);" alt="">
            <small style="color:var(--muted);">Image actuelle — importez une nouvelle pour la remplacer</small>
          </div>
        </div>

        <div class="field">
          <div class="toggle-row">
            <label class="toggle">
              <input type="checkbox" name="visible" id="fVisible" checked>
              <span class="toggle-slider"></span>
            </label>
            <span class="toggle-label">Visible sur le portfolio</span>
          </div>
        </div>

        <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:8px;">
          <button type="button" class="btn btn-ghost" onclick="closeModal()">Annuler</button>
          <button type="submit" class="btn btn-primary" id="formSubmitBtn">
            <i class="fas fa-save"></i> Enregistrer
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- TOAST -->
<div class="toast" id="toast"></div>

<script>
// ── FILTER ──────────────────────────────────────────────
const cards = () => document.querySelectorAll('.gallery-card');
let activeFilter = 'all', activeCat = '';

document.querySelectorAll('.filter-tab').forEach(btn => {
  btn.addEventListener('click', () => {
    document.querySelectorAll('.filter-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    activeFilter = btn.dataset.filter;
    applyFilter();
  });
});

function filterCat(val) { activeCat = val; applyFilter(); }

function applyFilter() {
  cards().forEach(c => {
    const vis = c.dataset.visible === '1';
    const cat = c.dataset.cat;
    const byFilter = activeFilter === 'all' || (activeFilter === 'visible' && vis) || (activeFilter === 'hidden' && !vis);
    const byCat = !activeCat || cat === activeCat;
    c.style.display = byFilter && byCat ? '' : 'none';
  });
}

// ── MODAL ────────────────────────────────────────────────
function openModal(reset = true) {
  if (reset) {
    document.getElementById('galleryForm').reset();
    document.getElementById('formAction').value = 'add';
    document.getElementById('formId').value = '';
    document.getElementById('modalTitle').textContent = 'Ajouter une réalisation';
    document.getElementById('imgPreview').style.display = 'none';
    document.getElementById('currentImgWrap').style.display = 'none';
    document.getElementById('fVisible').checked = true;
  }
  document.getElementById('modalOverlay').classList.add('open');
}

function closeModal() {
  document.getElementById('modalOverlay').classList.remove('open');
}

function closeOnOverlay(e) {
  if (e.target === document.getElementById('modalOverlay')) closeModal();
}

function editItem(item) {
  document.getElementById('formAction').value = 'edit';
  document.getElementById('formId').value    = item.id;
  document.getElementById('fTitre').value    = item.titre;
  document.getElementById('fCategorie').value = item.categorie;
  document.getElementById('fOrdre').value    = item.ordre;
  document.getElementById('fDesc').value     = item.description || '';
  document.getElementById('fVisible').checked = item.visible == 1;
  document.getElementById('modalTitle').textContent = 'Modifier la réalisation';
  document.getElementById('imgPreview').style.display = 'none';

  const wrap = document.getElementById('currentImgWrap');
  if (item.image) {
    document.getElementById('currentImg').src = '/portfolio/uploads/gallery/' + item.image;
    wrap.style.display = 'block';
  } else {
    wrap.style.display = 'none';
  }

  openModal(false);
}

// ── PREVIEW IMAGE ────────────────────────────────────────
function previewImage(input) {
  const preview = document.getElementById('imgPreview');
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => { preview.src = e.target.result; preview.style.display = 'block'; };
    reader.readAsDataURL(input.files[0]);
  }
}

// Drag & drop
const zone = document.getElementById('uploadZone');
['dragover','dragenter'].forEach(ev => zone.addEventListener(ev, e => { e.preventDefault(); zone.classList.add('drag'); }));
['dragleave','drop'].forEach(ev => zone.addEventListener(ev, () => zone.classList.remove('drag')));

// ── SUBMIT ───────────────────────────────────────────────
document.getElementById('galleryForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn = document.getElementById('formSubmitBtn');
  btn.disabled = true;
  btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enregistrement…';

  const fd = new FormData(this);
  fd.set('visible', document.getElementById('fVisible').checked ? '1' : '0');

  try {
    const res  = await fetch('gallery_action.php', { method: 'POST', body: fd });
    const data = await res.json();
    if (data.success) {
      showToast(data.message || 'Enregistré !', 'success');
      closeModal();
      setTimeout(() => location.reload(), 800);
    } else {
      showToast(data.message || 'Erreur.', 'error');
    }
  } catch {
    showToast('Erreur réseau.', 'error');
  } finally {
    btn.disabled = false;
    btn.innerHTML = '<i class="fas fa-save"></i> Enregistrer';
  }
});

// ── DELETE ───────────────────────────────────────────────
async function deleteItem(id, btn) {
  if (!confirm('Supprimer cette réalisation ? Cette action est irréversible.')) return;
  btn.disabled = true;
  try {
    const res  = await fetch('gallery_action.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ action: 'delete', id })
    });
    const data = await res.json();
    if (data.success) {
      showToast('Réalisation supprimée.', 'success');
      btn.closest('.gallery-card').remove();
    } else {
      showToast(data.message || 'Erreur.', 'error');
    }
  } catch {
    showToast('Erreur réseau.', 'error');
  } finally {
    btn.disabled = false;
  }
}

// ── TOAST ────────────────────────────────────────────────
function showToast(msg, type = 'success') {
  const t = document.getElementById('toast');
  t.className = `toast ${type} show`;
  t.innerHTML = `<i class="fas fa-${type === 'success' ? 'circle-check' : 'circle-exclamation'}"></i>${msg}`;
  setTimeout(() => t.classList.remove('show'), 3500);
}
</script>
</body>
</html>
