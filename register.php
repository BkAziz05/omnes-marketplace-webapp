<?php
// ============================================================
// register.php - Inscription acheteur
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide.';
    } else {
        $nom      = clean($_POST['nom'] ?? '');
        $prenom   = clean($_POST['prenom'] ?? '');
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $adresse  = clean($_POST['adresse'] ?? '');
        $tel      = clean($_POST['telephone'] ?? '');
        $clause   = isset($_POST['clause_acceptee']) ? 1 : 0;

        // Validations
        if (empty($nom))    $errors[] = 'Le nom est requis.';
        if (empty($prenom)) $errors[] = 'Le prénom est requis.';
        if (!isValidEmail($email)) $errors[] = 'Email invalide.';
        if (!isStrongPassword($password)) $errors[] = 'Le mot de passe doit contenir au moins 8 caractères, une majuscule, un chiffre et un caractère spécial.';
        if ($password !== $confirm) $errors[] = 'Les mots de passe ne correspondent pas.';
        if ($clause !== 1) $errors[] = 'Vous devez accepter les conditions générales.';

        if (empty($errors)) {
            $pdo = db();
            // Vérifier si email déjà utilisé
            $stmt = $pdo->prepare('SELECT idAcheteur FROM acheteur WHERE email = ?');
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors[] = 'Cet email est déjà utilisé.';
            } else {
                $stmt = $pdo->prepare('
                    INSERT INTO acheteur (nom, prenom, email, mdp, adresse, NumTelephone, clause_acceptee)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ');
                $stmt->execute([$nom, $prenom, $email, hashPassword($password), $adresse, $tel, $clause]);

                $acheteur = [
                    'idAcheteur' => (int)$pdo->lastInsertId(),
                    'nom'    => $nom,
                    'prenom' => $prenom,
                    'email'  => $email,
                ];
                sessionLoginAcheteur($acheteur);
                setFlash('success', 'Bienvenue ' . h($prenom) . ' ! Votre compte a été créé avec succès.');
                redirect(BASE_URL . '/index.php');
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Inscription - Omnes MarketPlace</title>
</head>
<body>
<?php foreach ($errors as $e): ?>
    <p style="color:red"><?= h($e) ?></p>
<?php endforeach; ?>
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="text"  name="nom"      placeholder="Nom" required>
    <input type="text"  name="prenom"   placeholder="Prénom" required>
    <input type="email" name="email"    placeholder="Email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <input type="password" name="confirm_password" placeholder="Confirmer le mot de passe" required>
    <textarea name="adresse" placeholder="Adresse"></textarea>
    <input type="tel" name="telephone" placeholder="Téléphone">
    <label>
        <input type="checkbox" name="clause_acceptee" required>
        J'accepte que toute offre faite est un engagement légal d'achat si acceptée par le vendeur.
    </label>
    <button type="submit">Créer mon compte</button>
</form>
<a href="<?= BASE_URL ?>/login.php">Déjà un compte ? Se connecter</a>
</body>
</html>
