<?php
// ============================================================
// vendor/dashboard.php - Tableau de bord vendeur
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('vendeur');

$pdo       = db();
$idVendeur = currentUserId();

// ─── Statistiques vendeur ──────────────────────────────────
$nbArticles    = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idVendeur = ? AND status != 'supprime'");
$nbArticles->execute([$idVendeur]);
$nbArticles = $nbArticles->fetchColumn();

$nbVendus = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idVendeur = ? AND status = 'vendu'");
$nbVendus->execute([$idVendeur]);
$nbVendus = $nbVendus->fetchColumn();

$nbNegoEnCours = $pdo->prepare("SELECT COUNT(*) FROM negociation WHERE idVendeur = ? AND statut = 'en_cours'");
$nbNegoEnCours->execute([$idVendeur]);
$nbNegoEnCours = $nbNegoEnCours->fetchColumn();

// ─── Articles du vendeur ───────────────────────────────────
$stmt = $pdo->prepare("
    SELECT a.*, c.libelle AS categorie_libelle,
           (SELECT url_photo FROM photo_article WHERE idArticle = a.idArticle ORDER BY ordre LIMIT 1) AS photo
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    WHERE a.idVendeur = ? AND a.status != 'supprime'
    ORDER BY a.date_publication DESC
");
$stmt->execute([$idVendeur]);
$articles = $stmt->fetchAll();

$flashes = getFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Espace Vendeur - Omnes MarketPlace</title></head>
<body>
<h1>Espace Vendeur — <?= h($_SESSION['user_nom']) ?></h1>
<nav>
    <a href="<?= BASE_URL ?>/vendor/dashboard.php">Mes articles</a> |
    <a href="<?= BASE_URL ?>/add-article.php">Publier un article</a> |
    <a href="<?= BASE_URL ?>/negotiation.php">Négociations (<?= $nbNegoEnCours ?>)</a> |
    <a href="<?= BASE_URL ?>/account.php">Mon compte</a> |
    <a href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
</nav>

<?php foreach ($flashes as $f): ?>
<div style="background:<?= $f['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>;padding:10px;margin:10px 0;">
    <?= h($f['message']) ?>
</div>
<?php endforeach; ?>

<p>Articles : <strong><?= $nbArticles ?></strong> | Vendus : <strong><?= $nbVendus ?></strong> | Négociations en cours : <strong><?= $nbNegoEnCours ?></strong></p>

<h2>Mes articles</h2>
<?php if (empty($articles)): ?>
    <p>Vous n'avez pas encore d'article publié. <a href="<?= BASE_URL ?>/add-article.php">Publier un article</a></p>
<?php else: ?>
<table border="1">
    <tr><th>Article</th><th>Catégorie</th><th>Prix</th><th>Mode</th><th>Statut</th><th>Actions</th></tr>
    <?php foreach ($articles as $art): ?>
    <tr>
        <td><?= h($art['nom']) ?></td>
        <td><?= h($art['categorie_libelle']) ?></td>
        <td><?= formatPrix($art['prix_base']) ?></td>
        <td><?= h($art['mode_vente']) ?></td>
        <td><?= h($art['status']) ?></td>
        <td>
            <a href="<?= BASE_URL ?>/edit-article.php?id=<?= $art['idArticle'] ?>">Modifier</a>
            <form method="POST" action="<?= BASE_URL ?>/delete-article.php" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="idArticle" value="<?= $art['idArticle'] ?>">
                <button type="submit" onclick="return confirm('Supprimer cet article ?')">Supprimer</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>
</body>
</html>
