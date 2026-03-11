<?php
// ============================================================
// articles.php - Affichage et récupération des articles
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = db();

// ─── Paramètres de filtre & pagination ─────────────────────
$page      = max(1, (int)($_GET['page'] ?? 1));
$perPage   = 12;
$search    = clean($_GET['q'] ?? '');
$categorie = (int)($_GET['categorie'] ?? 0);
$modeVente = clean($_GET['mode'] ?? '');
$prixMax   = (float)($_GET['prix_max'] ?? 0);
$typeMarc  = clean($_GET['type'] ?? '');

// ─── Construction de la requête dynamique ──────────────────
$where  = ["a.status = 'disponible'"];
$params = [];

if (!empty($search)) {
    $where[]  = "(a.nom LIKE ? OR a.description_qualite LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($categorie > 0) {
    $where[]  = "a.idCategorie = ?";
    $params[] = $categorie;
}
if (in_array($modeVente, ['immediat', 'negotiation', 'enchere'])) {
    $where[]  = "a.mode_vente = ?";
    $params[] = $modeVente;
}
if ($prixMax > 0) {
    $where[]  = "a.prix_base <= ?";
    $params[] = $prixMax;
}
if (in_array($typeMarc, ['rare', 'haute_gamme', 'regulier'])) {
    $where[]  = "c.type_marchandise = ?";
    $params[] = $typeMarc;
}

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Compter le total
$countSql = "
    SELECT COUNT(*) 
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    $whereClause
";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();

$pagination = paginate($total, $perPage, $page);

// Récupérer les articles
$sql = "
    SELECT 
        a.*,
        c.libelle AS categorie_libelle,
        c.type_marchandise,
        v.pseudo AS vendeur_pseudo,
        v.nom AS vendeur_nom,
        (SELECT url_photo FROM photo_article WHERE idArticle = a.idArticle ORDER BY ordre LIMIT 1) AS photo_principale
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    JOIN vendeur v   ON a.idVendeur   = v.idVendeur
    $whereClause
    ORDER BY a.date_publication DESC
    LIMIT ? OFFSET ?
";
$params[] = $perPage;
$params[] = $pagination['offset'];

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$articles = $stmt->fetchAll();

// ─── Récupérer les catégories pour le filtre ───────────────
$categories = $pdo->query('SELECT * FROM categorie ORDER BY libelle')->fetchAll();

// ─── Réponse JSON si requête AJAX ──────────────────────────
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    jsonResponse([
        'success'    => true,
        'articles'   => $articles,
        'pagination' => $pagination,
    ]);
}

// ─── Fonction utilitaire : récupérer un article par ID ─────
function getArticleById(int $id): array|false {
    $stmt = db()->prepare('
        SELECT a.*, c.libelle AS categorie_libelle, c.type_marchandise,
               v.pseudo AS vendeur_pseudo, v.nom AS vendeur_nom, v.idVendeur
        FROM article a
        JOIN categorie c ON a.idCategorie = c.idCategorie
        JOIN vendeur v   ON a.idVendeur   = v.idVendeur
        WHERE a.idArticle = ?
    ');
    $stmt->execute([$id]);
    return $stmt->fetch();
}

// ─── Récupérer les photos d'un article ─────────────────────
function getArticlePhotos(int $idArticle): array {
    $stmt = db()->prepare('SELECT * FROM photo_article WHERE idArticle = ? ORDER BY ordre');
    $stmt->execute([$idArticle]);
    return $stmt->fetchAll();
}
