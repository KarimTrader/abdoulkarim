<?php
// ================================================
//  AJAX endpoint — gestion galerie
//  Actions : add, edit, delete
// ================================================
session_start();
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Non autorisé.']));
}

$db = getDB();

// Déterminer si la requête est JSON ou multipart
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';
if (str_contains($contentType, 'application/json')) {
    $data   = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $data['action'] ?? '';
    $id     = (int)($data['id'] ?? 0);
} else {
    $action = $_POST['action'] ?? '';
    $id     = (int)($_POST['id'] ?? 0);
}

// ── ADD ──────────────────────────────────────────────────
if ($action === 'add') {
    $titre      = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie  = trim($_POST['categorie'] ?? '') ?: 'Autre';
    $ordre      = (int)($_POST['ordre'] ?? 0);
    $visible    = ($_POST['visible'] ?? '1') === '1' ? 1 : 0;

    if (mb_strlen($titre) < 2) {
        http_response_code(422);
        exit(json_encode(['success' => false, 'message' => 'Le titre est requis.']));
    }

    $image = handleUpload();
    if ($image === false) {
        http_response_code(422);
        exit(json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image.']));
    }

    $stmt = $db->prepare(
        "INSERT INTO galerie (titre, description, image, categorie, ordre, visible)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$titre, $description ?: null, $image, $categorie, $ordre, $visible]);

    exit(json_encode(['success' => true, 'message' => 'Réalisation ajoutée avec succès.']));
}

// ── EDIT ─────────────────────────────────────────────────
if ($action === 'edit') {
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'ID manquant.']));
    }

    $titre       = trim($_POST['titre'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $categorie   = trim($_POST['categorie'] ?? '') ?: 'Autre';
    $ordre       = (int)($_POST['ordre'] ?? 0);
    $visible     = ($_POST['visible'] ?? '1') === '1' ? 1 : 0;

    if (mb_strlen($titre) < 2) {
        http_response_code(422);
        exit(json_encode(['success' => false, 'message' => 'Le titre est requis.']));
    }

    // Récupérer l'ancienne image
    $old = $db->prepare("SELECT image FROM galerie WHERE id = ?");
    $old->execute([$id]);
    $row = $old->fetch();
    if (!$row) {
        http_response_code(404);
        exit(json_encode(['success' => false, 'message' => 'Réalisation introuvable.']));
    }

    $image = handleUpload();
    if ($image === false) {
        http_response_code(422);
        exit(json_encode(['success' => false, 'message' => 'Erreur lors de l\'upload de l\'image.']));
    }

    // Si nouvelle image uploadée, supprimer l'ancienne
    if ($image && $row['image'] && $image !== $row['image']) {
        $oldFile = __DIR__ . '/../uploads/gallery/' . $row['image'];
        if (file_exists($oldFile)) unlink($oldFile);
    }

    $finalImage = $image ?: $row['image'];

    $stmt = $db->prepare(
        "UPDATE galerie SET titre=?, description=?, image=?, categorie=?, ordre=?, visible=? WHERE id=?"
    );
    $stmt->execute([$titre, $description ?: null, $finalImage, $categorie, $ordre, $visible, $id]);

    exit(json_encode(['success' => true, 'message' => 'Réalisation mise à jour.']));
}

// ── DELETE ───────────────────────────────────────────────
if ($action === 'delete') {
    if (!$id) {
        http_response_code(400);
        exit(json_encode(['success' => false, 'message' => 'ID manquant.']));
    }

    $stmt = $db->prepare("SELECT image FROM galerie WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();

    if ($row && $row['image']) {
        $file = __DIR__ . '/../uploads/gallery/' . $row['image'];
        if (file_exists($file)) unlink($file);
    }

    $db->prepare("DELETE FROM galerie WHERE id = ?")->execute([$id]);
    exit(json_encode(['success' => true]));
}

http_response_code(400);
exit(json_encode(['success' => false, 'message' => 'Action inconnue.']));

// ── UPLOAD HELPER ────────────────────────────────────────
function handleUpload(): string|false|null
{
    if (empty($_FILES['image']['tmp_name'])) return null; // pas de fichier = ok

    $file    = $_FILES['image'];
    $maxSize = 5 * 1024 * 1024; // 5 Mo

    if ($file['error'] !== UPLOAD_ERR_OK)  return false;
    if ($file['size']  > $maxSize)         return false;

    // Vérification MIME réelle
    $finfo    = finfo_open(FILEINFO_MIME_TYPE);
    $mime     = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowed, true)) return false;

    $ext      = match($mime) {
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    };
    $filename = bin2hex(random_bytes(10)) . '.' . $ext;
    $dest     = __DIR__ . '/../uploads/gallery/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $dest)) return false;

    return $filename;
}
