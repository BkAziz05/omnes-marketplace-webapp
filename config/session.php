<?php
// ============================================================
// session.php - Gestion des sessions utilisateur
// Omnes MarketPlace
// ============================================================

require_once __DIR__ . '/config.php';

// Configuration de la session
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', ENV === 'production' ? 1 : 0);
session_name(SESSION_NAME);
session_set_cookie_params(SESSION_LIFETIME);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ─── Fonctions de session ───────────────────────────────────

/**
 * Connecter un administrateur
 */
function sessionLoginAdmin(array $admin): void {
    $_SESSION['user_id']   = $admin['idAdmin'];
    $_SESSION['user_role'] = 'admin';
    $_SESSION['user_nom']  = $admin['nom'] . ' ' . $admin['prenom'];
    $_SESSION['user_email']= $admin['email'];
    session_regenerate_id(true);
}

/**
 * Connecter un vendeur
 */
function sessionLoginVendeur(array $vendeur): void {
    $_SESSION['user_id']    = $vendeur['idVendeur'];
    $_SESSION['user_role']  = 'vendeur';
    $_SESSION['user_nom']   = $vendeur['nom'] . ' ' . $vendeur['prenom'];
    $_SESSION['user_email'] = $vendeur['email'];
    $_SESSION['user_pseudo']= $vendeur['pseudo'];
    session_regenerate_id(true);
}

/**
 * Connecter un acheteur
 */
function sessionLoginAcheteur(array $acheteur): void {
    $_SESSION['user_id']    = $acheteur['idAcheteur'];
    $_SESSION['user_role']  = 'acheteur';
    $_SESSION['user_nom']   = $acheteur['nom'] . ' ' . $acheteur['prenom'];
    $_SESSION['user_email'] = $acheteur['email'];
    session_regenerate_id(true);
}

/**
 * Déconnexion
 */
function sessionLogout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']
        );
    }
    session_destroy();
}

/**
 * Vérifier si l'utilisateur est connecté
 */
function isLoggedIn(): bool {
    return isset($_SESSION['user_id'], $_SESSION['user_role']);
}

/**
 * Vérifier le rôle
 */
function hasRole(string $role): bool {
    return isLoggedIn() && $_SESSION['user_role'] === $role;
}

/**
 * Forcer l'authentification (redirige si non connecté)
 */
function requireLogin(string $redirect = '/login.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Forcer un rôle spécifique
 */
function requireRole(string $role, string $redirect = '/login.php'): void {
    if (!hasRole($role)) {
        header('Location: ' . BASE_URL . $redirect);
        exit;
    }
}

/**
 * Récupérer l'ID de l'utilisateur connecté
 */
function currentUserId(): ?int {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Récupérer le rôle de l'utilisateur connecté
 */
function currentUserRole(): ?string {
    return $_SESSION['user_role'] ?? null;
}

/**
 * Flash messages (messages temporaires)
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function getFlashes(): array {
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}
