<?php
// ============================================================
// article-detail.php - Page détail d'un article
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/articles.php';

$pdo       = db();
$idArticle = (int)($_GET['id'] ?? 0);
$article   = getArticleById($idArticle);

if (!$article || $article['status'] === 'supprime') {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/articles.php');
}

$photos = getArticlePhotos($idArticle);

// Infos spécifiques selon mode de vente
$offreActuelle = null;
if ($article['mode_vente'] === 'enchere') {
    $stmt = $pdo->prepare("SELECT MAX(montant_courant) AS meilleure FROM offre_enchere WHERE idArticle = ? AND statut = 'active'");
    $stmt->execute([$idArticle]);
    $offreActuelle = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title><?= h($article['nom']) ?> - Omnes MarketPlace</title></head>
<body>
<a href="<?= BASE_URL ?>/articles.php">← Retour aux articles</a>
<h1><?= h($article['nom']) ?></h1>
<p>Catégorie : <?= h($article['categorie_libelle']) ?> (<?= h($article['type_marchandise']) ?>)</p>
<p>Vendu par : <?= h($article['vendeur_pseudo']) ?></p>

<?php foreach ($photos as $p): ?>
<img src="<?= BASE_URL . '/' . h($p['url_photo']) ?>" alt="Photo" style="max-width:300px;margin:4px;">
<?php endforeach; ?>

<?php if ($article['video_url']): ?>
<p><a href="<?= h($article['video_url']) ?>" target="_blank">Voir la vidéo</a></p>
<?php endif; ?>

<p><?= nl2br(h($article['description_qualite'] ?? '')) ?></p>
<?php if ($article['description_defaut']): ?>
<p><em>Défauts : <?= nl2br(h($article['description_defaut'])) ?></em></p>
<?php endif; ?>

<h2>Prix : <?= formatPrix($article['prix_base']) ?></h2>
<p>Mode de vente : <strong><?= h($article['mode_vente']) ?></strong></p>

<?php if ($article['status'] === 'disponible' && isLoggedIn() && hasRole('acheteur')): ?>

    <?php if ($article['mode_vente'] === 'immediat'): ?>
    <form method="POST" action="cart.php">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="idArticle" value="<?= $idArticle ?>">
        <input type="hidden" name="mode_acquisition" value="immediat">
        <button type="submit">🛒 Ajouter au panier</button>
    </form>

    <?php elseif ($article['mode_vente'] === 'negotiation'): ?>
    <h3>Proposer un prix</h3>
    <form method="POST" action="negotiation.php">
        <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="action" value="start">
        <input type="hidden" name="idArticle" value="<?= $idArticle ?>">
        <input type="number" name="montant" step="0.01" min="1" placeholder="Votre offre (€)" required>
        <button type="submit">Négocier</button>
    </form>
    <small>Vous pouvez négocier jusqu'à <?= NEGOCIATION_MAX_TOURS ?> fois.</small>

    <?php elseif ($article['mode_vente'] === 'enchere'): ?>
    <p>Période : <?= formatDate($article['date_debut_enchere']) ?> → <?= formatDate($article['date_fin_enchere']) ?></p>
    <p>Meilleure offre actuelle : <strong><?= $offreActuelle ? formatPrix($offreActuelle) : 'Aucune' ?></strong></p>
    <a href="<?= BASE_URL ?>/auction.php?id=<?= $idArticle ?>">Voir l'enchère et enchérir</a>
    <?php endif; ?>

<?php elseif ($article['status'] === 'vendu'): ?>
    <p><strong>Cet article a été vendu.</strong></p>
<?php elseif (!isLoggedIn()): ?>
    <p><a href="<?= BASE_URL ?>/login.php">Connectez-vous</a> pour acheter cet article.</p>
<?php endif; ?>
</body>
</html>
