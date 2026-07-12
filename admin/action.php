<?php
// ================================================
//  AJAX endpoint — actions sur les messages
//  Actions : mark_read, mark_unread, delete, get
// ================================================
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/db.php';

// Auth
if (empty($_SESSION['admin_logged_in'])) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'message' => 'Non autorisé.']));
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? $_POST['action'] ?? '';
$id     = (int)($data['id'] ?? $_POST['id'] ?? 0);

if (!$id && $action !== 'stats') {
    http_response_code(400);
    exit(json_encode(['success' => false, 'message' => 'ID manquant.']));
}

$db = getDB();

switch ($action) {

    case 'mark_read':
        $db->prepare("UPDATE messages SET lu = 1 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'mark_unread':
        $db->prepare("UPDATE messages SET lu = 0 WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'delete':
        $db->prepare("DELETE FROM messages WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        break;

    case 'get':
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = ?");
        $stmt->execute([$id]);
        $msg = $stmt->fetch();
        if (!$msg) { http_response_code(404); exit(json_encode(['success'=>false,'message'=>'Introuvable.'])); }
        // Marquer comme lu automatiquement
        $db->prepare("UPDATE messages SET lu = 1 WHERE id = ? AND lu = 0")->execute([$id]);
        $msg['lu'] = 1;
        echo json_encode(['success' => true, 'data' => $msg]);
        break;

    case 'stats':
        $total   = $db->query("SELECT COUNT(*) FROM messages")->fetchColumn();
        $unread  = $db->query("SELECT COUNT(*) FROM messages WHERE lu = 0")->fetchColumn();
        $today   = $db->query("SELECT COUNT(*) FROM messages WHERE DATE(created_at) = CURDATE()")->fetchColumn();
        echo json_encode(['success'=>true,'total'=>$total,'unread'=>$unread,'today'=>$today]);
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Action inconnue.']);
}
