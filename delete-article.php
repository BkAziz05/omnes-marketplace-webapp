<?php
// ============================================================
// delete-article.php - Suppression d'un article
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
if (!hasRole('vendeur') && !hasRole('admin')) redirect(BASE_URL . '/login.php');

if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
    setFlash('error', 'Requête invalide.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

$pdo       = db();
$idArticle = (int)($_POST['idArticle'] ?? 0);

// Vérifier propriété
$stmt = $pdo->prepare('SELECT idVendeur FROM article WHERE idArticle = ?');
$stmt->execute([$idArticle]);
$article = $stmt->fetch();

if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

if (hasRole('vendeur') && $article['idVendeur'] !== currentUserId()) {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

// Suppression logique (status = supprime)
$stmt = $pdo->prepare("UPDATE article SET status = 'supprime' WHERE idArticle = ?");
$stmt->execute([$idArticle]);

setFlash('success', 'Article supprimé.');
redirect(BASE_URL . '/vendor/dashboard.php');
