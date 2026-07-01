<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;
use App\Core\SessionAuth;

final class AdminController extends Controller
{
    public function index(): void
    {
        SessionAuth::requireAdmin();
        $this->render('admin/index');
    }
}
