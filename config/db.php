<?php
// ============================================================
// db.php - Connexion PDO à la base de données
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;

    public static function getInstance(): PDO {
        if (self::$instance === null) {
            try {
                $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
            } catch (PDOException $e) {
                if (ENV === 'development') {
                    die('Erreur de connexion à la base de données : ' . $e->getMessage());
                } else {
                    die('Erreur interne du serveur. Veuillez réessayer plus tard.');
                }
            }
        }
        return self::$instance;
    }

    // Empêcher le clonage du singleton
    private function __clone() {}
    private function __construct() {}
}

// Raccourci global
function db(): PDO {
    return Database::getInstance();
}
