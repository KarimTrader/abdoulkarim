<?php
// ================================================
//  API publique — galerie
//  GET /portfolio/api/gallery.php
//  GET /portfolio/api/gallery.php?cat=Web
// ================================================
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');

require_once __DIR__ . '/../config/db.php';

$cat    = trim($_GET['cat'] ?? '');
$db     = getDB();
$params = [];
$where  = 'WHERE visible = 1';

if ($cat !== '') {
    $where  .= ' AND categorie = ?';
    $params[] = $cat;
}

$items = $db->prepare(
    "SELECT id, titre, description, image, categorie, ordre
     FROM galerie $where
     ORDER BY ordre ASC, created_at DESC"
);
$items->execute($params);
$rows = $items->fetchAll();

// Ajouter l'URL complète de l'image
foreach ($rows as &$row) {
    $file = __DIR__ . '/../uploads/gallery/' . $row['image'];
    $row['image_url'] = ($row['image'] && file_exists($file))
        ? '/portfolio/uploads/gallery/' . $row['image']
        : null;
}

// Catégories disponibles
$cats = $db->query(
    "SELECT DISTINCT categorie FROM galerie WHERE visible = 1 ORDER BY categorie"
)->fetchAll(PDO::FETCH_COLUMN);

echo json_encode([
    'success'    => true,
    'items'      => $rows,
    'categories' => $cats,
    'total'      => count($rows),
], JSON_UNESCAPED_UNICODE);
