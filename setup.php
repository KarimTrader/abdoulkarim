<?php
// ================================================
//  SETUP — Créer la base de données et les tables
//  Accéder à : http://localhost/portfolio/setup.php
//  SUPPRIMER ce fichier après exécution !
// ================================================

$host = 'sql309.infinityfree.com';
$user = 'if0_41573920';
$pass = 'E7ReqR1ZYsc';
$dbname = 'if0_41573920_portfolio_db';

try {
    // Connexion à la base de données existante
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // Table messages
    $pdo->exec("CREATE TABLE IF NOT EXISTS `messages` (
        `id`         INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
        `nom`        VARCHAR(100)    NOT NULL,
        `email`      VARCHAR(150)    NOT NULL,
        `sujet`      VARCHAR(200)    NOT NULL DEFAULT 'Sans objet',
        `message`    TEXT            NOT NULL,
        `ip`         VARCHAR(45)     DEFAULT NULL,
        `user_agent` VARCHAR(300)    DEFAULT NULL,
        `lu`         TINYINT(1)      NOT NULL DEFAULT 0,
        `archive`    TINYINT(1)      NOT NULL DEFAULT 0,
        `created_at` TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_lu` (`lu`),
        INDEX `idx_created` (`created_at`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Table galerie
    $pdo->exec("CREATE TABLE IF NOT EXISTS `galerie` (
        `id`          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
        `titre`       VARCHAR(200)    NOT NULL,
        `description` TEXT            DEFAULT NULL,
        `image`       VARCHAR(300)    NOT NULL,
        `categorie`   VARCHAR(100)    NOT NULL DEFAULT 'Autre',
        `ordre`       INT             NOT NULL DEFAULT 0,
        `visible`     TINYINT(1)      NOT NULL DEFAULT 1,
        `created_at`  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX `idx_visible` (`visible`),
        INDEX `idx_ordre` (`ordre`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    echo '<div style="font-family:monospace;background:#0a0a12;color:#10b981;padding:40px;min-height:100vh;">';
    echo '<h2 style="color:#818cf8;">✅ Setup terminé avec succès</h2>';
    echo '<p>Base de données <strong>if0_41573920_portfolio_db</strong> utilisée.</p>';
    echo '<p>Table <strong>messages</strong> créée.</p>';
    echo '<p>Table <strong>galerie</strong> créée.</p>';
    echo '<br><p style="color:#f59e0b;">⚠️ <strong>SUPPRIMER ce fichier setup.php maintenant !</strong></p>';
    echo '<br><a href="/portfolio/admin/login.php" style="color:#6366f1;">→ Aller au dashboard admin</a>';
    echo '</div>';

} catch (PDOException $e) {
    echo '<div style="font-family:monospace;background:#0a0a12;color:#f43f5e;padding:40px;">';
    echo '<h2>❌ Erreur</h2><p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
