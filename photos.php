<?php
// ============================================================
// photos.php - Gestion des photos d'articles
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
if (!hasRole('vendeur') && !hasRole('admin')) redirect(BASE_URL . '/login.php');

$pdo       = db();
$idArticle = (int)($_GET['id'] ?? $_POST['idArticle'] ?? 0);
$action    = clean($_POST['action'] ?? '');

// Vérifier propriété de l'article
$stmt = $pdo->prepare('SELECT idVendeur, nom FROM article WHERE idArticle = ?');
$stmt->execute([$idArticle]);
$article = $stmt->fetch();

if (!$article) {
    setFlash('error', 'Article introuvable.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}
if (hasRole('vendeur') && $article['idVendeur'] != currentUserId()) {
    setFlash('error', 'Accès refusé.');
    redirect(BASE_URL . '/vendor/dashboard.php');
}

// ─── Ajouter des photos ────────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) redirect(BASE_URL . '/photos.php?id=' . $idArticle);

    $uploaded = 0;
    $maxOrdre = $pdo->prepare("SELECT COALESCE(MAX(ordre),0) FROM photo_article WHERE idArticle = ?");
    $maxOrdre->execute([$idArticle]);
    $ordre = (int)$maxOrdre->fetchColumn() + 1;

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
            $pdo->prepare("INSERT INTO photo_article (url_photo, ordre, idArticle) VALUES (?, ?, ?)")
                ->execute(['uploads/articles/' . $filename, $ordre++, $idArticle]);
            $uploaded++;
        }
    }
    setFlash('success', "$uploaded photo(s) ajoutée(s).");
    redirect(BASE_URL . '/photos.php?id=' . $idArticle);
}

// ─── Supprimer une photo ───────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) redirect(BASE_URL . '/photos.php?id=' . $idArticle);
    $idPhoto = (int)($_POST['idPhoto'] ?? 0);
    $stmt    = $pdo->prepare("SELECT url_photo FROM photo_article WHERE idPhoto = ? AND idArticle = ?");
    $stmt->execute([$idPhoto, $idArticle]);
    $photo = $stmt->fetch();
    if ($photo) {
        $filePath = ROOT_PATH . '/' . $photo['url_photo'];
        if (file_exists($filePath)) @unlink($filePath);
        $pdo->prepare("DELETE FROM photo_article WHERE idPhoto = ?")->execute([$idPhoto]);
        setFlash('success', 'Photo supprimée.');
    }
    redirect(BASE_URL . '/photos.php?id=' . $idArticle);
}

// ─── Charger les photos ────────────────────────────────────
$stmt = $pdo->prepare("SELECT * FROM photo_article WHERE idArticle = ? ORDER BY ordre");
$stmt->execute([$idArticle]);
$photos = $stmt->fetchAll();
$flashes = getFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Photos - <?= h($article['nom']) ?></title></head>
<body>
<h1>Photos de : <?= h($article['nom']) ?></h1>
<a href="<?= BASE_URL ?>/edit-article.php?id=<?= $idArticle ?>">← Modifier l'article</a>

<?php foreach ($flashes as $f): ?><p style="color:<?= $f['type']==='success'?'green':'red' ?>"><?= h($f['message']) ?></p><?php endforeach; ?>

<h2>Ajouter des photos</h2>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="add">
    <input type="hidden" name="idArticle" value="<?= $idArticle ?>">
    <input type="file" name="photos[]" multiple accept="image/*" required>
    <button type="submit">Uploader</button>
</form>

<h2>Photos existantes (<?= count($photos) ?>)</h2>
<div style="display:flex;flex-wrap:wrap;gap:12px;">
    <?php foreach ($photos as $p): ?>
    <div style="text-align:center">
        <img src="<?= BASE_URL . '/' . h($p['url_photo']) ?>" style="width:150px;height:150px;object-fit:cover;">
        <br>Ordre : <?= $p['ordre'] ?>
        <form method="POST" style="display:inline">
            <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="idArticle" value="<?= $idArticle ?>">
            <input type="hidden" name="idPhoto" value="<?= $p['idPhoto'] ?>">
            <button type="submit" onclick="return confirm('Supprimer cette photo ?')">🗑</button>
        </form>
    </div>
    <?php endforeach; ?>
</div>
</body>
</html>
