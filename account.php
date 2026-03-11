<?php
// ============================================================
// account.php - Gestion du compte utilisateur
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

requireLogin();

$pdo    = db();
$role   = currentUserRole();
$userId = currentUserId();
$errors = [];

// ─── Charger le profil ─────────────────────────────────────
switch ($role) {
    case 'admin':
        $stmt = $pdo->prepare('SELECT * FROM administrateur WHERE idAdmin = ?');
        break;
    case 'vendeur':
        $stmt = $pdo->prepare('SELECT * FROM vendeur WHERE idVendeur = ?');
        break;
    default:
        $stmt = $pdo->prepare('SELECT * FROM acheteur WHERE idAcheteur = ?');
}
$stmt->execute([$userId]);
$user = $stmt->fetch();

if (!$user) {
    sessionLogout();
    redirect(BASE_URL . '/login.php');
}

// ─── Modifier le profil ────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $nom    = clean($_POST['nom'] ?? '');
        $prenom = clean($_POST['prenom'] ?? '');
        $tel    = clean($_POST['telephone'] ?? '');
        $adresse= clean($_POST['adresse'] ?? '');

        if (empty($nom) || empty($prenom)) $errors[] = 'Nom et prénom requis.';

        // Changement de mot de passe optionnel
        $newPass = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';
        if (!empty($newPass)) {
            if (!isStrongPassword($newPass)) $errors[] = 'Mot de passe trop faible.';
            if ($newPass !== $confirm)        $errors[] = 'Confirmation du mot de passe incorrecte.';
        }

        if (empty($errors)) {
            switch ($role) {
                case 'acheteur':
                    $sql = 'UPDATE acheteur SET nom=?, prenom=?, adresse=?, NumTelephone=?' . (!empty($newPass) ? ', mdp=?' : '') . ' WHERE idAcheteur=?';
                    $params = [$nom, $prenom, $adresse, $tel];
                    if (!empty($newPass)) $params[] = hashPassword($newPass);
                    $params[] = $userId;
                    break;
                case 'vendeur':
                    // Photo de profil
                    $photoProfil = $user['photo_profil'];
                    $imageFond   = $user['image_fond'];
                    if (!empty($_FILES['photo_profil']['tmp_name'])) {
                        $fn = uploadImage($_FILES['photo_profil'], UPLOADS_PROFILES);
                        if ($fn) $photoProfil = 'uploads/profiles/' . $fn;
                    }
                    if (!empty($_FILES['image_fond']['tmp_name'])) {
                        $fn = uploadImage($_FILES['image_fond'], UPLOADS_PROFILES);
                        if ($fn) $imageFond = 'uploads/profiles/' . $fn;
                    }
                    $sql = 'UPDATE vendeur SET nom=?, prenom=?, photo_profil=?, image_fond=?' . (!empty($newPass) ? ', mot_de_passe=?' : '') . ' WHERE idVendeur=?';
                    $params = [$nom, $prenom, $photoProfil, $imageFond];
                    if (!empty($newPass)) $params[] = hashPassword($newPass);
                    $params[] = $userId;
                    break;
                case 'admin':
                    $sql = 'UPDATE administrateur SET nom=?, prenom=?' . (!empty($newPass) ? ', mot_de_passe=?' : '') . ' WHERE idAdmin=?';
                    $params = [$nom, $prenom];
                    if (!empty($newPass)) $params[] = hashPassword($newPass);
                    $params[] = $userId;
                    break;
            }
            $pdo->prepare($sql)->execute($params);
            setFlash('success', 'Profil mis à jour.');
            redirect(BASE_URL . '/account.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Mon compte - Omnes MarketPlace</title></head>
<body>
<h1>Mon compte (<?= h($role) ?>)</h1>
<?php foreach ($errors as $e): ?><p style="color:red"><?= h($e) ?></p><?php endforeach; ?>
<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="text" name="nom"    value="<?= h($user['nom']) ?>"    placeholder="Nom" required>
    <input type="text" name="prenom" value="<?= h($user['prenom']) ?>" placeholder="Prénom" required>
    <?php if ($role === 'acheteur'): ?>
        <textarea name="adresse" placeholder="Adresse"><?= h($user['adresse'] ?? '') ?></textarea>
        <input type="tel" name="telephone" value="<?= h($user['NumTelephone'] ?? '') ?>" placeholder="Téléphone">
    <?php elseif ($role === 'vendeur'): ?>
        <label>Photo de profil : <input type="file" name="photo_profil" accept="image/*"></label>
        <label>Image de fond  : <input type="file" name="image_fond"   accept="image/*"></label>
    <?php endif; ?>
    <h3>Changer le mot de passe (optionnel)</h3>
    <input type="password" name="new_password"     placeholder="Nouveau mot de passe">
    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe">
    <button type="submit">Enregistrer</button>
</form>
</body>
</html>
