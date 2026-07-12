<?php
// ================================================
//  Endpoint : traitement du formulaire de contact
//  POST /portfolio/submit.php
// ================================================
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Méthode
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'message' => 'Méthode non autorisée.']));
}

// Limite de taille
if ((int)$_SERVER['CONTENT_LENGTH'] > 20000) {
    http_response_code(413);
    exit(json_encode(['success' => false, 'message' => 'Requête trop volumineuse.']));
}

require_once __DIR__ . '/config/db.php';

// Nettoyage des entrées
function clean(string $v): string {
    return trim(htmlspecialchars(strip_tags($v), ENT_QUOTES, 'UTF-8'));
}

$nom     = clean($_POST['nom']     ?? '');
$email   = clean($_POST['email']   ?? '');
$sujet   = clean($_POST['sujet']   ?? '');
$message = clean($_POST['message'] ?? '');

// Validation
$errors = [];
if (mb_strlen($nom) < 2)                              $errors[] = 'Le nom doit contenir au moins 2 caractères.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL))        $errors[] = 'Adresse email invalide.';
if (mb_strlen($message) < 10)                          $errors[] = 'Le message doit contenir au moins 10 caractères.';

if (!empty($errors)) {
    http_response_code(422);
    exit(json_encode(['success' => false, 'errors' => $errors]));
}

// Tronquer selon taille de colonne
$nom     = mb_substr($nom, 0, 100);
$email   = mb_substr($email, 0, 150);
$sujet   = mb_strlen($sujet) > 0 ? mb_substr($sujet, 0, 200) : 'Sans objet';
$message = mb_substr($message, 0, 5000);

// Métadonnées
$ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ?? $_SERVER['HTTP_CLIENT_IP']
    ?? $_SERVER['REMOTE_ADDR']
    ?? '';
$ip = mb_substr(trim(explode(',', $ip)[0]), 0, 45);
$ua = mb_substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 300);

// Insertion
try {
    $db = getDB();
    $stmt = $db->prepare(
        "INSERT INTO messages (nom, email, sujet, message, ip, user_agent)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $stmt->execute([$nom, $email, $sujet, $message, $ip, $ua]);

    echo json_encode([
        'success' => true,
        'message' => 'Votre message a bien été envoyé. Je vous répondrai très prochainement !',
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erreur serveur. Réessayez plus tard.']);
}
