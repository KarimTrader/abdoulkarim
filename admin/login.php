<?php
session_start();
require_once __DIR__ . '/../config/auth.php';

// Déjà connecté
if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php'); exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $u = trim($_POST['username'] ?? '');
    $p = trim($_POST['password'] ?? '');

    if ($u === ADMIN_USERNAME && $p === ADMIN_PASSWORD) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_user']      = $u;
        $_SESSION['login_time']      = time();
        header('Location: index.php'); exit;
    }
    $error = 'Identifiants incorrects.';
    sleep(1); // brute-force throttle
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — Connexion</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<style>
*,*::before,*::after{margin:0;padding:0;box-sizing:border-box}
:root{
  --bg:#04040a; --card:rgba(255,255,255,.04); --border:rgba(255,255,255,.07);
  --indigo:#6366f1; --indigo-lt:#818cf8;
  --text:#f1f5f9; --muted:#94a3b8;
  --grad:linear-gradient(135deg,#6366f1,#8b5cf6,#06b6d4);
  --r:14px;
}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);
     min-height:100vh;display:flex;align-items:center;justify-content:center;
     -webkit-font-smoothing:antialiased;}
.wrap{width:100%;max-width:420px;padding:24px;}
.logo{text-align:center;margin-bottom:40px;}
.logo-text{font-family:'JetBrains Mono',monospace;font-size:22px;font-weight:700;}
.logo-b{color:var(--indigo);}
.logo-sub{font-size:13px;color:var(--muted);margin-top:6px;}
.card{background:var(--card);border:1px solid var(--border);border-radius:24px;padding:36px;
      backdrop-filter:blur(20px);}
.card-title{font-size:18px;font-weight:700;margin-bottom:4px;}
.card-sub{font-size:14px;color:var(--muted);margin-bottom:28px;}
.field{margin-bottom:18px;}
label{display:block;font-size:12px;font-weight:700;color:var(--muted);margin-bottom:7px;
      text-transform:uppercase;letter-spacing:.1em;}
.inp{width:100%;padding:13px 16px;background:rgba(255,255,255,.05);
     border:1px solid var(--border);border-radius:var(--r);color:var(--text);
     font-size:15px;font-family:'Inter',sans-serif;outline:none;transition:all .25s;}
.inp:focus{border-color:var(--indigo);background:rgba(99,102,241,.07);
           box-shadow:0 0 0 3px rgba(99,102,241,.15);}
.inp::placeholder{color:#475569;}
.inp-wrap{position:relative;}
.inp-ico{position:absolute;left:15px;top:50%;transform:translateY(-50%);color:#475569;font-size:15px;}
.inp-pad{padding-left:44px;}
.btn{width:100%;padding:14px;background:var(--grad);color:#fff;border:none;
     border-radius:var(--r);font-size:15px;font-weight:700;cursor:pointer;
     transition:all .25s;margin-top:8px;}
.btn:hover{opacity:.9;transform:translateY(-2px);box-shadow:0 12px 28px rgba(99,102,241,.35);}
.error{display:flex;align-items:center;gap:8px;padding:12px 16px;
       background:rgba(244,63,94,.1);border:1px solid rgba(244,63,94,.25);
       border-radius:var(--r);color:#fb7185;font-size:13px;margin-bottom:18px;}
.back{text-align:center;margin-top:20px;}
.back a{font-size:13px;color:var(--muted);text-decoration:none;transition:color .2s;}
.back a:hover{color:var(--indigo-lt);}
</style>
</head>
<body>
<div class="wrap">
  <div class="logo">
    <div class="logo-text"><span class="logo-b">{</span>AMD<span class="logo-b">}</span></div>
    <div class="logo-sub">Dashboard Admin</div>
  </div>

  <div class="card">
    <div class="card-title">Connexion</div>
    <div class="card-sub">Accès réservé à l'administrateur</div>

    <?php if ($error): ?>
    <div class="error"><i class="fas fa-circle-exclamation"></i><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" autocomplete="off">
      <div class="field">
        <label>Identifiant</label>
        <div class="inp-wrap">
          <i class="fas fa-user inp-ico"></i>
          <input type="text" name="username" class="inp inp-pad"
                 placeholder="admin" required autocomplete="username"
                 value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
      </div>
      <div class="field">
        <label>Mot de passe</label>
        <div class="inp-wrap">
          <i class="fas fa-lock inp-ico"></i>
          <input type="password" name="password" class="inp inp-pad"
                 placeholder="••••••••••" required autocomplete="current-password">
        </div>
      </div>
      <button type="submit" class="btn">
        <i class="fas fa-right-to-bracket"></i> Connexion
      </button>
    </form>
  </div>

  <div class="back"><a href="/portfolio/">← Retour au portfolio</a></div>
</div>
</body>
</html>
