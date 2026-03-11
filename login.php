<?php
// ============================================================
// login.php - Connexion (admin, vendeur, acheteur)
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/includes/helpers.php';

// Si déjà connecté, rediriger
if (isLoggedIn()) {
    redirect(BASE_URL . '/index.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification CSRF
    if (!verifyCsrf($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Token de sécurité invalide. Veuillez recharger la page.';
    } else {
        $email    = clean($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $role     = clean($_POST['role'] ?? '');

        if (empty($email) || !isValidEmail($email)) {
            $errors[] = 'Adresse email invalide.';
        }
        if (empty($password)) {
            $errors[] = 'Mot de passe requis.';
        }
        if (!in_array($role, ['admin', 'vendeur', 'acheteur'])) {
            $errors[] = 'Rôle invalide.';
        }

        if (empty($errors)) {
            $pdo = db();

            switch ($role) {
                case 'admin':
                    $stmt = $pdo->prepare('SELECT * FROM administrateur WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    if ($user && verifyPassword($password, $user['mot_de_passe'])) {
                        sessionLoginAdmin($user);
                        redirect(BASE_URL . '/admin/dashboard.php');
                    }
                    break;

                case 'vendeur':
                    $stmt = $pdo->prepare('SELECT * FROM vendeur WHERE email = ? AND statut_compte = "actif" LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    if ($user && verifyPassword($password, $user['mot_de_passe'])) {
                        sessionLoginVendeur($user);
                        redirect(BASE_URL . '/vendor/dashboard.php');
                    }
                    break;

                case 'acheteur':
                    $stmt = $pdo->prepare('SELECT * FROM acheteur WHERE email = ? LIMIT 1');
                    $stmt->execute([$email]);
                    $user = $stmt->fetch();
                    if ($user && verifyPassword($password, $user['mdp'])) {
                        sessionLoginAcheteur($user);
                        redirect(BASE_URL . '/index.php');
                    }
                    break;
            }

            $errors[] = 'Email ou mot de passe incorrect.';
        }
    }
}

// Réponse JSON pour requêtes AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    jsonResponse(['success' => empty($errors), 'errors' => $errors]);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - Omnes MarketPlace</title>
</head>
<body>
<?php if (!empty($errors)): ?>
    <div class="errors">
        <?php foreach ($errors as $e): ?>
            <p><?= h($e) ?></p>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
<form method="POST" action="">
    <input type="hidden" name="csrf_token" value="<?= csrfToken() ?>">
    <input type="email" name="email" placeholder="Email" required>
    <input type="password" name="password" placeholder="Mot de passe" required>
    <select name="role">
        <option value="acheteur">Acheteur</option>
        <option value="vendeur">Vendeur</option>
        <option value="admin">Administrateur</option>
    </select>
    <button type="submit">Se connecter</button>
</form>
<a href="<?= BASE_URL ?>/register.php">Créer un compte acheteur</a>
</body>
</html>
