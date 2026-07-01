<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;

final class CheckoutController extends Controller
{
    public function index(): void
    {
        SessionAuth::requireLogin('login.php?erro=login&next=' . rawurlencode('finalizar_compra.php'));
        $this->render('checkout/index');
    }
}
