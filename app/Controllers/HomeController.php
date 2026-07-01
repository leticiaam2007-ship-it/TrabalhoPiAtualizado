<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;
use App\Models\UserModel;

final class HomeController extends Controller
{
    public function index(): void
    {
        SessionAuth::start();
        $profile = null;

        if (SessionAuth::isLoggedIn()) {
            $userId = SessionAuth::userId();
            if ($userId !== null && $userId > 0) {
                $profile = (new UserModel())->findProfileById($userId);
                if (is_array($profile)) {
                    $_SESSION['foto'] = (string) ($profile['foto'] ?? '');
                }
            }
        }

        $this->render('home/index', [
            'logado' => SessionAuth::isLoggedIn(),
            'tipoUsuario' => SessionAuth::userType(),
            'nomeUsuario' => isset($_SESSION['nome']) ? (string) $_SESSION['nome'] : null,
            'emailUsuario' => isset($_SESSION['email']) ? (string) $_SESSION['email'] : null,
            'fotoUsuario' => is_array($profile) ? (string) ($profile['foto'] ?? '') : (isset($_SESSION['foto']) ? (string) $_SESSION['foto'] : ''),
        ]);
    }
}
