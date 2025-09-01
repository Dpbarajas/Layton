<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>ToniNet</title>
    <link rel="icon" type="image/x-icon" href="/assets/img/icono.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="stylesheet" href="/../assets/styles.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="bg-light">

<!-- Navbar -->
<?php if (!isset($noCargarNavBar)): ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="/index.php">ToniNet</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link<?= strpos($_SERVER['REQUEST_URI'], '/contratos') !== false ? ' active' : '' ?>" href="/contratos/index.php">Contratos</a>
                </li>
                <li class="nav-item dropdown hover-dropdown">
                    <a class="nav-link dropdown-toggle<?= (strpos($_SERVER['REQUEST_URI'], '/clientes') !== false || strpos($_SERVER['REQUEST_URI'], '/productos') !== false || strpos($_SERVER['REQUEST_URI'], '/proveedores') !== false) ? ' active' : '' ?>"
                       href="#" id="entitiesDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Entidades
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="entitiesDropdown">
                        <li>
                            <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/clientes/index.php') !== false ? 'active' : '' ?>" href="/clientes/index.php">Clientes</a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/productos/index.php') !== false ? 'active' : '' ?>" href="/productos/index.php">Productos</a>
                        </li>
                        <li>
                            <a class="dropdown-item <?= strpos($_SERVER['REQUEST_URI'], '/proveedores/index.php') !== false ? 'active' : '' ?>" href="/proveedores/index.php">Proveedores</a>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link<?= strpos($_SERVER['REQUEST_URI'], '/balances') !== false ? ' active' : '' ?>" href="/balances/index.php">Balance</a>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Contrato Notas</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modalBody">

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="container py-4">
