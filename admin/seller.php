<?php
// ============================================================
// admin/seller.php - Gestion des vendeurs (admin)
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../includes/helpers.php';

requireRole('admin');

$pdo     = db();
$errors  = [];
$action  = clean($_POST['action'] ?? '');

// ─── Ajouter un vendeur ────────────────────────────────────
if ($action === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token invalide.';
    } else {
        $pseudo = clean($_POST['pseudo'] ?? '');
        $nom    = clean($_POST['nom'] ?? '');
        $prenom = clean($_POST['prenom'] ?? '');
        $email  = clean($_POST['email'] ?? '');

        if (empty($pseudo)) $errors[] = 'Pseudo requis.';
        if (empty($nom))    $errors[] = 'Nom requis.';
        if (empty($prenom)) $errors[] = 'Prénom requis.';
        if (!isValidEmail($email)) $errors[] = 'Email invalide.';

        if (empty($errors)) {
            // Vérifier unicité
            $stmt = $pdo->prepare('SELECT idVendeur FROM vendeur WHERE email = ? OR pseudo = ?');
            $stmt->execute([$email, $pseudo]);
            if ($stmt->fetch()) {
                $errors[] = 'Email ou pseudo déjà utilisé.';
            } else {
                // Mot de passe temporaire
                $tempPass = bin2hex(random_bytes(6));
                $stmt = $pdo->prepare('
                    INSERT INTO vendeur (pseudo, nom, prenom, email, mot_de_passe, idAdmin)
                    VALUES (?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$pseudo, $nom, $prenom, $email, hashPassword($tempPass), currentUserId()]);
                setFlash('success', "Vendeur créé. Mot de passe temporaire : $tempPass");
                redirect(BASE_URL . '/admin/seller.php');
            }
        }
    }
}

// ─── Suspendre / Réactiver un vendeur ─────────────────────
if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) redirect(BASE_URL . '/admin/seller.php');
    $idVendeur = (int)($_POST['idVendeur'] ?? 0);
    $stmt = $pdo->prepare("SELECT statut_compte FROM vendeur WHERE idVendeur = ?");
    $stmt->execute([$idVendeur]);
    $v = $stmt->fetch();
    if ($v) {
        $nouveauStatut = $v['statut_compte'] === 'actif' ? 'suspendu' : 'actif';
        $pdo->prepare("UPDATE vendeur SET statut_compte = ? WHERE idVendeur = ?")->execute([$nouveauStatut, $idVendeur]);
    }
    redirect(BASE_URL . '/admin/seller.php');
}

// ─── Supprimer un vendeur ──────────────────────────────────
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) redirect(BASE_URL . '/admin/seller.php');
    $idVendeur = (int)($_POST['idVendeur'] ?? 0);
    $pdo->prepare("UPDATE vendeur SET statut_compte = 'supprime' WHERE idVendeur = ?")->execute([$idVendeur]);
    redirect(BASE_URL . '/admin/seller.php');
}

// ─── Lister les vendeurs ───────────────────────────────────
$vendeurs = $pdo->query("SELECT v.*, (SELECT COUNT(*) FROM article WHERE idVendeur = v.idVendeur) AS nb_articles FROM vendeur v WHERE statut_compte != 'supprime' ORDER BY nom")->fetchAll();

// Flash messages
$flashes = getFlashes();
?>
<!DOCTYPE html>
<html lang="fr">
<head><meta charset="UTF-8"><title>Gestion Vendeurs - Omnes MarketPlace</title></head>
<body>
<h1>Gestion des Vendeurs</h1>
<a href="<?= BASE_URL ?>/admin/dashboard.php">← Dashboard</a>

<?php foreach ($flashes as $f): ?>
<div style="background:<?= $f['type'] === 'success' ? '#d4edda' : '#f8d7da' ?>;padding:10px;margin:10px 0;">
    <?= h($f['message']) ?>
</div>
<?php endforeach; ?>

<?php foreach ($errors as $e): ?><p style="color:red"><?= h($e) ?></p><?php endforeach; ?>

<h2>Ajouter un vendeur</h2>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="action" value="add">
    <input type="text"  name="pseudo" placeholder="Pseudo" required>
    <input type="text"  name="nom"    placeholder="Nom" required>
    <input type="text"  name="prenom" placeholder="Prénom" required>
    <input type="email" name="email"  placeholder="Email" required>
    <button type="submit">Créer le vendeur</button>
</form>

<h2>Vendeurs (<?= count($vendeurs) ?>)</h2>
<table border="1">
    <tr><th>Pseudo</th><th>Nom</th><th>Email</th><th>Articles</th><th>Statut</th><th>Actions</th></tr>
    <?php foreach ($vendeurs as $v): ?>
    <tr>
        <td><?= h($v['pseudo']) ?></td>
        <td><?= h($v['nom'] . ' ' . $v['prenom']) ?></td>
        <td><?= h($v['email']) ?></td>
        <td><?= $v['nb_articles'] ?></td>
        <td><?= h($v['statut_compte']) ?></td>
        <td>
            <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="toggle">
                <input type="hidden" name="idVendeur" value="<?= $v['idVendeur'] ?>">
                <button type="submit"><?= $v['statut_compte'] === 'actif' ? 'Suspendre' : 'Réactiver' ?></button>
            </form>
            <form method="POST" style="display:inline">
                <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="idVendeur" value="<?= $v['idVendeur'] ?>">
                <button type="submit" onclick="return confirm('Supprimer ce vendeur ?')">Supprimer</button>
            </form>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
</body>
</html>
