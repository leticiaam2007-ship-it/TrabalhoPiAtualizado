<?php
declare(strict_types=1);

require_once __DIR__ . '/app/bootstrap.php';

use App\Controllers\PerfilController;

$controller = new PerfilController();
$controller->index();

