<?php
// ============================================================
// add-article.php - Ajout d'un article (vendeur ou admin)
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();
if (!hasRole('vendeur') && !hasRole('admin')) {
    redirect(BASE_URL . '/login.php');
}

$errors  = [];
$success = false;
$pdo     = db();

// Charger les catégories
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

        // Validations
        if (empty($nom))       $errors[] = 'Le nom de l\'article est requis.';
        if ($prixBase <= 0)    $errors[] = 'Le prix doit être supérieur à 0.';
        if (!in_array($modeVente, ['immediat', 'negotiation', 'enchere'])) $errors[] = 'Mode de vente invalide.';
        if ($idCategorie <= 0) $errors[] = 'Catégorie invalide.';

        if ($modeVente === 'enchere') {
            if (empty($dateDebutEnchere) || empty($dateFinEnchere)) {
                $errors[] = 'Les dates de début et fin d\'enchère sont requises.';
            } elseif (strtotime($dateDebutEnchere) >= strtotime($dateFinEnchere)) {
                $errors[] = 'La date de fin doit être postérieure à la date de début.';
            }
        }

        if (empty($errors)) {
            $idVendeur = currentUserId();
            // Si admin, le vendeur est l'admin lui-même (on peut adapter)
            if (hasRole('admin')) {
                // L'admin vend sous son propre ID, récupérer le premier vendeur lié à cet admin
                $stmt = $pdo->prepare('SELECT idVendeur FROM vendeur WHERE idAdmin = ? LIMIT 1');
                $stmt->execute([$idVendeur]);
                $vendeurRow = $stmt->fetch();
                $idVendeur  = $vendeurRow ? $vendeurRow['idVendeur'] : null;
                if (!$idVendeur) {
                    $errors[] = 'Impossible de déterminer le vendeur pour cet article.';
                }
            }
        }

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare('
                    INSERT INTO article 
                        (nom, description_qualite, description_defaut, prix_base, mode_vente, 
                         date_debut_enchere, date_fin_enchere, video_url, idVendeur, idCategorie)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([
                    $nom,
                    $descQualite ?: null,
                    $descDefaut  ?: null,
                    $prixBase,
                    $modeVente,
                    $modeVente === 'enchere' ? $dateDebutEnchere : null,
                    $modeVente === 'enchere' ? $dateFinEnchere   : null,
                    $videoUrl ?: null,
                    $idVendeur,
                    $idCategorie,
                ]);
                $idArticle = (int)$pdo->lastInsertId();

                // Upload des photos
                if (!empty($_FILES['photos']['name'][0])) {
                    $ordre = 1;
                    foreach ($_FILES['photos']['tmp_name'] as $key => $tmpName) {
                        if ($_FILES['photos']['error'][$key] !== UPLOAD_ERR_OK) continue;
                        $fileData = [
                            'name'     => $_FILES['photos']['name'][$key],
                            'tmp_name' => $tmpName,
                            'error'    => $_FILES['photos']['error'][$key],
                            'size'     => $_FILES['photos']['size'][$key],
                            'type'     => $_FILES['photos']['type'][$key],
                        ];
                        $filename = uploadImage($fileData, UPLOADS_ARTICLES);
                        if ($filename) {
                            $stmtP = $pdo->prepare('INSERT INTO photo_article (url_photo, ordre, idArticle) VALUES (?, ?, ?)');
                            $stmtP->execute(['uploads/articles/' . $filename, $ordre++, $idArticle]);
                        }
                    }
                }

                $pdo->commit();
                setFlash('success', 'Article ajouté avec succès.');
                redirect(BASE_URL . '/vendor/dashboard.php');

            } catch (Exception $e) {
                $pdo->rollBack();
                $errors[] = 'Erreur lors de l\'ajout de l\'article. Veuillez réessayer.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Ajouter un article - Omnes MarketPlace</title></head>
<body>
<h1>Ajouter un article</h1>
<?php foreach ($errors as $e): ?>
    <p style="color:red"><?= h($e) ?></p>
<?php endforeach; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="text" name="nom" placeholder="Nom de l'article" required>
    <textarea name="description_qualite" placeholder="Description qualité"></textarea>
    <textarea name="description_defaut"  placeholder="Défauts éventuels"></textarea>
    <input type="number" name="prix_base" placeholder="Prix (€)" step="0.01" min="0.01" required>
    <select name="mode_vente" id="mode_vente" required>
        <option value="">-- Mode de vente --</option>
        <option value="immediat">Achat immédiat</option>
        <option value="negotiation">Négociation</option>
        <option value="enchere">Enchère (meilleure offre)</option>
    </select>
    <div id="enchere_dates" style="display:none">
        <input type="datetime-local" name="date_debut_enchere" placeholder="Début enchère">
        <input type="datetime-local" name="date_fin_enchere"   placeholder="Fin enchère">
    </div>
    <select name="idCategorie" required>
        <option value="">-- Catégorie --</option>
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['idCategorie'] ?>"><?= h($cat['libelle']) ?> (<?= h($cat['type_marchandise']) ?>)</option>
        <?php endforeach; ?>
    </select>
    <input type="url" name="video_url" placeholder="URL Vidéo (optionnel)">
    <input type="file" name="photos[]" multiple accept="image/*">
    <button type="submit">Publier l'article</button>
</form>
<script>
document.getElementById('mode_vente').addEventListener('change', function() {
    document.getElementById('enchere_dates').style.display = this.value === 'enchere' ? 'block' : 'none';
});
</script>
</body>
</html>
