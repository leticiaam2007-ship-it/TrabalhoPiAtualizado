<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\AuthController;

$controller = new AuthController();
$controller->logout();
