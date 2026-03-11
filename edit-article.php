<?php
// ============================================================
// edit-article.php - Modification d'un article
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/articles.php';

requireLogin();
if (!hasRole('vendeur') && !hasRole('admin')) redirect(BASE_URL . '/login.php');

$pdo       = db();
$idArticle = (int)($_GET['id'] ?? 0);
$article   = getArticleById($idArticle);

if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

// Vérifier que le vendeur est bien propriétaire
if (hasRole('vendeur') && $article['idVendeur'] !== currentUserId()) {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

$errors     = [];
$categories = $pdo->query('SELECT * FROM categorie ORDER BY libelle')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom              = clean($_POST['nom'] ?? '');
        $descQualite      = clean($_POST['description_qualite'] ?? '');
        $descDefaut       = clean($_POST['description_defaut'] ?? '');
        $prixBase         = (float)($_POST['prix_base'] ?? 0);
        $modeVente        = clean($_POST['mode_vente'] ?? '');
        $idCategorie      = (int)($_POST['idCategorie'] ?? 0);
        $videoUrl         = clean($_POST['video_url'] ?? '');
        $dateDebutEnchere = clean($_POST['date_debut_enchere'] ?? '');
        $dateFinEnchere   = clean($_POST['date_fin_enchere'] ?? '');
        $status           = clean($_POST['status'] ?? 'disponible');

        if (empty($nom))    $errors[] = 'Le nom est requis.';
        if ($prixBase <= 0) $errors[] = 'Prix invalide.';

        if (empty($errors)) {
            $stmt = $pdo->prepare('
                UPDATE article SET
                    nom = ?, description_qualite = ?, description_defaut = ?,
                    prix_base = ?, mode_vente = ?, date_debut_enchere = ?,
                    date_fin_enchere = ?, video_url = ?, idCategorie = ?, status = ?
                WHERE idArticle = ?
            ');
            $stmt->execute([
                $nom, $descQualite ?: null, $descDefaut ?: null,
                $prixBase, $modeVente,
                $modeVente === 'enchere' ? $dateDebutEnchere : null,
                $modeVente === 'enchere' ? $dateFinEnchere : null,
                $videoUrl ?: null, $idCategorie, $status, $idArticle
            ]);

            // Nouvelles photos
            if (!empty($_FILES['photos']['name'][0])) {
                $ordre = 1;
                foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                    if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) continue;
                    $fileData = [
                        'name'     => $_FILES['photos']['name'][$key],
                        'tmp_name' => $tmpName,
                        'error'    => $_FILES['photos']['error'][$key],
                        'size'     => $_FILES['photos']['size'][$key],
                    ];
                    $filename = uploadImage($fileData, UPLOADS_ARTICLES);
                    if ($filename) {
                        $stmtP = $pdo->prepare('INSERT INTO photo_article (url_photo, ordre, idArticle) VALUES (?, ?, ?)');
                        $stmtP->execute(['uploads/articles/' . $filename, $ordre++, $idArticle]);
                    }
                }
            }

            setFlash('success', 'Article mis à jour.');
            redirect(BASE_URL . '/vendor/dashboard.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Modifier l'article - Omnes MarketPlace</title></head>
<body>
<h1>Modifier : <?= h($article['nom']) ?></h1>
<?php foreach ($errors as $e): ?><p style="color:red"><?= h($e) ?></p><?php endforeach; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="text" name="nom" value="<?= h($article['nom']) ?>" required>
    <textarea name="description_qualite"><?= h($article['description_qualite'] ?? '') ?></textarea>
    <textarea name="description_defaut"><?= h($article['description_defaut'] ?? '') ?></textarea>
    <input type="number" name="prix_base" value="<?= $article['prix_base'] ?>" step="0.01" required>
    <select name="mode_vente">
        <?php foreach (['immediat','negotiation','enchere'] as $m): ?>
            <option value="<?= $m ?>" <?= $article['mode_vente'] === $m ? 'selected' : '' ?>><?= $m ?></option>
        <?php endforeach; ?>
    </select>
    <input type="datetime-local" name="date_debut_enchere" value="<?= $article['date_debut_enchere'] ?? '' ?>">
    <input type="datetime-local" name="date_fin_enchere"   value="<?= $article['date_fin_enchere'] ?? '' ?>">
    <select name="idCategorie">
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['idCategorie'] ?>" <?= $article['idCategorie'] == $cat['idCategorie'] ? 'selected' : '' ?>>
                <?= h($cat['libelle']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="url" name="video_url" value="<?= h($article['video_url'] ?? '') ?>">
    <select name="status">
        <?php foreach (['disponible','vendu','en_cours','supprime'] as $s): ?>
            <option value="<?= $s ?>" <?= $article['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
        <?php endforeach; ?>
    </select>
    <input type="file" name="photos[]" multiple accept="image/*">
    <button type="submit">Enregistrer</button>
</form>
</body>
</html>
