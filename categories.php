<?php
// ============================================================
// categories.php - Gestion des catégories (admin)
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireRole('admin');

$pdo    = db();
$action = clean($_POST['action'] ?? '');
$errors = [];

if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $libelle = clean($_POST['libelle'] ?? '');
        $type    = clean($_POST['type_marchandise'] ?? '');

        if (empty($libelle)) $errors[] = 'Libellé requis.';
        if (!in_array($type, ['rare','haute_gamme','regulier'])) $errors[] = 'Type invalide.';

        if (empty($errors)) {
            $pdo->prepare("INSERT INTO categorie (libelle, type_marchandise) VALUES (?, ?)")->execute([$libelle, $type]);
            setFlash('success', 'Catégorie ajoutée.');
            redirect(BASE_URL . '/categories.php');
        }
    }
}

if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) redirect(BASE_URL . '/categories.php');
    $idCat = (int)($_POST['idCategorie'] ?? 0);
    // Vérifier si des articles utilisent cette catégorie
    $nb = $pdo->prepare("SELECT COUNT(*) FROM article WHERE idCategorie = ?");
    $nb->execute([$idCat]);
    if ($nb->fetchColumn() > 0) {
        setFlash('error', 'Impossible de supprimer : des articles utilisent cette catégorie.');
    } else {
        $pdo->prepare("DELETE FROM categorie WHERE idCategorie = ?")->execute([$idCat]);
        setFlash('success', 'Catégorie supprimée.');
    }
    redirect(BASE_URL . '/categories.php');
}

$categories = $pdo->query("SELECT c.*, COUNT(a.idArticle) AS nb_articles FROM categorie c LEFT JOIN article a ON c.idCategorie = a.idCategorie GROUP BY c.idCategorie ORDER BY c.libelle")->fetchAll();
$flashes    = getFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Catégories - Omnes MarketPlace</title></head>
<body>
<h1>Gestion des catégories</h1>
<a href="<?= BASE_URL ?>/admin/dashboard.php">← Dashboard</a>
<?php foreach ($flashes as $f): ?><p style="color:<?= $f['type']==='success'?'green':'red' ?>"><?= h($f['message']) ?></p><?php endforeach; ?>
<?php foreach ($errors as $e): ?><p style="color:red"><?= h($e) ?></p><?php endforeach; ?>

<h2>Ajouter une catégorie</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="add">
    <input type="text" name="libelle" placeholder="Libellé" required>
    <select name="type_marchandise">
        <option value="rare">Rares</option>
        <option value="haute_gamme">Haute gamme</option>
        <option value="regulier">Réguliers</option>
    </select>
    <button type="submit">Ajouter</button>
</form>

<h2>Catégories (<?= count($categories) ?>)</h2>
<table border="1">
    <tr><th>Libellé</th><th>Type</th><th>Articles</th><th>Action</th></tr>
    <?php foreach ($categories as $cat): ?>
    <tr>
        <td><?= h($cat['libelle']) ?></td>
        <td><?= h($cat['type_marchandise']) ?></td>
        <td><?= $cat['nb_articles'] ?></td>
        <td>
            <?php if ($cat['nb_articles'] == 0): ?>
            <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="idCategorie" value="<?= $cat['idCategorie'] ?>">
                <button type="submit" onclick="return confirm('Supprimer ?')">Supprimer</button>
            </form>
            <?php else: ?>—<?php endif; ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
