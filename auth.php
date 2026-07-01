<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Core\SessionAuth;

function iniciarSessaoSegura(): void
{
    SessionAuth::start();
}

function usuarioEstaLogado(): bool
{
    return SessionAuth::isLoggedIn();
}

function usuarioAtualId(): ?int
{
    return SessionAuth::userId();
}

function usuarioAtualTipo(): ?string
{
    return SessionAuth::userType();
}

function registrarLoginUsuario(array $usuario): void
{
    SessionAuth::login($usuario);
}

function exigirLogin(): void
{
    SessionAuth::requireLogin();
}

function exigirAdmin(): void
{
    SessionAuth::requireAdmin();
}

function encerrarSessaoUsuario(): void
{
    SessionAuth::logout();
}
