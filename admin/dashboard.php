<?php
// ============================================================
// admin/dashboard.php - Tableau de bord administrateur
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo = db();

// ─── Statistiques globales ─────────────────────────────────
$stats = [];
$stats['nb_vendeurs']   = $pdo->query("SELECT COUNT(*) FROM vendeur WHERE statut_compte = 'actif'")->fetchColumn();
$stats['nb_acheteurs']  = $pdo->query("SELECT COUNT(*) FROM acheteur")->fetchColumn();
$stats['nb_articles']   = $pdo->query("SELECT COUNT(*) FROM article WHERE status = 'disponible'")->fetchColumn();
$stats['nb_commandes']  = $pdo->query("SELECT COUNT(*) FROM commande")->fetchColumn();
$stats['ca_total']      = $pdo->query("SELECT COALESCE(SUM(montant_total),0) FROM commande WHERE status_commande IN ('validee','expediee','livree')")->fetchColumn();
$stats['encheres_actives'] = $pdo->query("SELECT COUNT(*) FROM article WHERE mode_vente='enchere' AND status='disponible' AND date_fin_enchere > NOW()")->fetchColumn();

// ─── Dernières commandes ───────────────────────────────────
$commandes = $pdo->query('
    SELECT c.*, CONCAT(a.prenom," ",a.nom) AS acheteur_nom
    FROM commande c
    JOIN acheteur a ON c.idAcheteur = a.idAcheteur
    ORDER BY c.date_commande DESC LIMIT 10
')->fetchAll();

// ─── Vendeurs actifs ───────────────────────────────────────
$vendeurs = $pdo->query("SELECT * FROM vendeur WHERE statut_compte != 'supprime' ORDER BY nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Dashboard Admin - Omnes MarketPlace</title></head>
<body>
<h1>Tableau de bord Administrateur</h1>
<nav>
    <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a> |
    <a href="<?= BASE_URL ?>/admin/seller.php">Vendeurs</a> |
    <a href="<?= BASE_URL ?>/add-article.php">Ajouter article</a> |
    <a href="<?= BASE_URL ?>/logout.php">Déconnexion</a>
</nav>

<h2>Statistiques</h2>
<ul>
    <li>Vendeurs actifs : <strong><?= $stats['nb_vendeurs'] ?></strong></li>
    <li>Acheteurs : <strong><?= $stats['nb_acheteurs'] ?></strong></li>
    <li>Articles disponibles : <strong><?= $stats['nb_articles'] ?></strong></li>
    <li>Commandes totales : <strong><?= $stats['nb_commandes'] ?></strong></li>
    <li>Chiffre d'affaires : <strong><?= formatPrix($stats['ca_total']) ?></strong></li>
    <li>Enchères actives : <strong><?= $stats['encheres_actives'] ?></strong></li>
</ul>

<h2>Dernières commandes</h2>
<table border="1">
    <tr><th>#</th><th>Acheteur</th><th>Montant</th><th>Statut</th><th>Date</th></tr>
    <?php foreach ($commandes as $cmd): ?>
    <tr>
        <td><?= $cmd['idCommande'] ?></td>
        <td><?= h($cmd['acheteur_nom']) ?></td>
        <td><?= formatPrix($cmd['montant_total']) ?></td>
        <td><?= h($cmd['status_commande']) ?></td>
        <td><?= formatDate($cmd['date_commande']) ?></td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
