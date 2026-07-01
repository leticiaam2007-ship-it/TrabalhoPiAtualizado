<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;
use App\Models\UserModel;

final class MeusPedidosController extends Controller
{
    public function index(): void
    {
        SessionAuth::requireLogin('login.php?erro=login&next=' . rawurlencode('meus_pedidos.php'));
        SessionAuth::start();

        $profile = null;
        $userId = SessionAuth::userId();
        if ($userId !== null && $userId > 0) {
            $profile = (new UserModel())->findProfileById($userId);
            if (is_array($profile)) {
                $_SESSION['foto'] = (string) ($profile['foto'] ?? '');
            }
        }

        $this->render('meus_pedidos/index', [
            'nomeUsuario' => is_array($profile) ? (string) ($profile['nome'] ?? '') : (isset($_SESSION['nome']) ? (string) $_SESSION['nome'] : ''),
            'emailUsuario' => is_array($profile) ? (string) ($profile['email'] ?? '') : (isset($_SESSION['email']) ? (string) $_SESSION['email'] : ''),
            'fotoUsuario' => is_array($profile) ? (string) ($profile['foto'] ?? '') : (isset($_SESSION['foto']) ? (string) $_SESSION['foto'] : ''),
        ]);
    }
}
