<?php
// ============================================================
// index.php - Page d'accueil Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

$pdo = db();

// Sélection du jour : derniers articles publiés
$selectionDuJour = $pdo->query("
    SELECT a.*, c.libelle AS categorie,
           (SELECT url_photo FROM photo_article WHERE idArticle = a.idArticle ORDER BY ordre LIMIT 1) AS photo
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    WHERE a.status = 'disponible'
    ORDER BY a.date_publication DESC
    LIMIT 8
")->fetchAll();

// Best sellers (articles les plus présents dans des commandes)
$bestSellers = $pdo->query("
    SELECT a.*, COUNT(lp.idLigne) AS nb_ventes,
           (SELECT url_photo FROM photo_article WHERE idArticle = a.idArticle ORDER BY ordre LIMIT 1) AS photo
    FROM article a
    JOIN ligne_panier lp ON a.idArticle = lp.idArticle
    JOIN panier p ON lp.idPanier = p.idPanier
    WHERE p.statut = 'valide'
    GROUP BY a.idArticle
    ORDER BY nb_ventes DESC
    LIMIT 6
")->fetchAll();

// Enchères actives
$encheresActives = $pdo->query("
    SELECT a.*, c.libelle AS categorie,
           (SELECT url_photo FROM photo_article WHERE idArticle = a.idArticle ORDER BY ordre LIMIT 1) AS photo,
           (SELECT MAX(montant_courant) FROM offre_enchere WHERE idArticle = a.idArticle AND statut = 'active') AS meilleure_offre
    FROM article a
    JOIN categorie c ON a.idCategorie = c.idCategorie
    WHERE a.mode_vente = 'enchere' AND a.status = 'disponible' AND a.date_fin_enchere > NOW()
    ORDER BY a.date_fin_enchere ASC
    LIMIT 4
")->fetchAll();

$flashes = getFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Omnes MarketPlace - Accueil</title></head>
<body>

<!-- HEADER -->
<header>
    <h1>🛍 Omnes MarketPlace</h1>
</header>

<!-- NAVIGATION -->
<nav>
    <a href="<?= BASE_URL ?>/index.php">Accueil</a> |
    <a href="<?= BASE_URL ?>/articles.php">Tout Parcourir</a> |
    <?php if (isLoggedIn()): ?>
        <a href="<?= BASE_URL ?>/notifications.php">Notifications</a> |
        <a href="<?= BASE_URL ?>/cart.php">Panier</a> |
        <a href="<?= BASE_URL ?>/account.php">Votre Compte (<?= h($_SESSION['user_nom']) ?>)</a> |
        <a href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
    <?php else: ?>
        <a href="<?= BASE_URL ?>/login.php">Connexion</a> |
        <a href="<?= BASE_URL ?>/register.php">Inscription</a>
    <?php endif; ?>
</nav>

<!-- FLASH MESSAGES -->
<?php foreach ($flashes as $f): ?>
<div style="background:<?= $f['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>;padding:12px;margin:10px 0;border-radius:4px;">
    <?= h($f['message']) ?>
</div>
<?php endforeach; ?>

<!-- SECTION PRINCIPALE -->
<main>
    <section>
        <h2>Bienvenue sur Omnes MarketPlace</h2>
        <p>La marketplace de la communauté Omnes Education. Achetez, négociez, enchérissez !</p>
    </section>

    <!-- Sélection du jour -->
    <section>
        <h2>Sélection du jour</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px;">
            <?php foreach ($selectionDuJour as $art): ?>
            <div style="border:1px solid #ddd;padding:12px;width:200px;">
                <?php if ($art['photo']): ?>
                <img src="<?= BASE_URL . '/' . h($art['photo']) ?>" alt="<?= h($art['nom']) ?>" style="width:100%;height:150px;object-fit:cover;">
                <?php endif; ?>
                <h3><?= h($art['nom']) ?></h3>
                <p><?= h($art['categorie']) ?></p>
                <p><strong><?= formatPrix($art['prix_base']) ?></strong> — <?= h($art['mode_vente']) ?></p>
                <a href="<?= BASE_URL ?>/article-detail.php?id=<?= $art['idArticle'] ?>">Voir l'article</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Enchères en cours -->
    <?php if (!empty($encheresActives)): ?>
    <section>
        <h2>⚡ Enchères en cours</h2>
        <div style="display:flex;flex-wrap:wrap;gap:16px;">
            <?php foreach ($encheresActives as $art): ?>
            <div style="border:2px solid #f90;padding:12px;width:200px;">
                <h3><?= h($art['nom']) ?></h3>
                <p>Fin : <?= formatDate($art['date_fin_enchere']) ?></p>
                <p>Meilleure offre : <?= $art['meilleure_offre'] ? formatPrix($art['meilleure_offre']) : formatPrix($art['prix_base']) . ' (départ)' ?></p>
                <a href="<?= BASE_URL ?>/auction.php?id=<?= $art['idArticle'] ?>">Enchérir</a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</main>

<!-- FOOTER -->
<footer>
    <p>Omnes MarketPlace &copy; <?= date('Y') ?> | Contact : marketplace@omnes-education.fr | Tél : +33 1 23 45 67 89</p>
    <p>10 Rue Sextius Michel, 75015 Paris, France</p>
</footer>

</body>
</html>
