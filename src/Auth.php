<?php

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Auth
{
    private const BASE_URL = '/sistem-spk';

    private function __construct() {}

    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public static function attempt(string $username, string $password): bool
    {
        self::startSession();

        $pdo = Database::connect();

        $query = "
            SELECT
                id_user,
                nama,
                username,
                password,
                role
            FROM users
            WHERE username = :username
            LIMIT 1
        ";

        $statement = $pdo->prepare($query);

        $statement->execute([
            'username' => $username,
        ]);

        $user = $statement->fetch();

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id_user' => (int) $user['id_user'],
            'nama' => $user['nama'],
            'username' => $user['username'],
            'role' => $user['role'],
            'role_group' => self::getRoleGroup($user['role']),
        ];

        return true;
    }

    public static function check(): bool
    {
        self::startSession();

        return isset($_SESSION['user']);
    }

    public static function user(): ?array
    {
        self::startSession();

        return $_SESSION['user'] ?? null;
    }

    public static function logout(): void
    {
        self::startSession();

        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'],
                $params['domain'],
                $params['secure'],
                $params['httponly']
            );
        }

        session_destroy();
    }

    public static function redirectToDashboard(): never
    {
        $user = self::user();

        if (!$user) {
            self::redirectToLogin();
        }

        switch ($user['role_group']) {
            case 'staf':
                header(
                    'Location: ' .
                        self::BASE_URL .
                        '/dashboard/staf/index.php'
                );
                exit;

            case 'manajemen':
                header(
                    'Location: ' .
                        self::BASE_URL .
                        '/dashboard/manajemen/index.php'
                );
                exit;

            default:
                self::logout();

                header(
                    'Location: ' .
                        self::BASE_URL .
                        '/login.php?error=role'
                );
                exit;
        }
    }

    public static function redirectIfAuthenticated(): void
    {
        if (self::check()) {
            self::redirectToDashboard();
        }
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            self::redirectToLogin();
        }
    }

    public static function requireRole(string $role): void
    {
        self::requireLogin();

        $user = self::user();

        if (($user['role_group'] ?? null) !== $role) {
            self::redirectToDashboard();
        }
    }

    public static function csrfToken(): string
    {
        self::startSession();

        if (
            empty($_SESSION['csrf_token']) ||
            !is_string($_SESSION['csrf_token'])
        ) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrf(string $token): bool
    {
        self::startSession();

        $sessionToken = $_SESSION['csrf_token'] ?? '';

        if (
            !is_string($sessionToken) ||
            $sessionToken === '' ||
            $token === ''
        ) {
            return false;
        }

        return hash_equals($sessionToken, $token);
    }

    private static function redirectToLogin(): never
    {
        header(
            'Location: ' .
                self::BASE_URL .
                '/login.php'
        );

        exit;
    }

    private static function getRoleGroup(string $role): ?string
    {
        $normalizedRole = strtolower(trim($role));

        return match ($normalizedRole) {
            'staf',
            'staf/admin',
            'staf penjualan',
            'admin it',
            'staf penjualan/admin it'
            => 'staf',

            'manajemen',
            'kepala cabang',
            'pimpinan',
            'manajemen/kepala cabang/pimpinan'
            => 'manajemen',

            default => null,
        };
    }
}
