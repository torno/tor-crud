<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($title) ? $title : 'Dashboard' ?></title>
    <link href="<?php echo base_url ( 'css/' ); ?>bootstrap.min.css" rel="stylesheet">
    <link href="<?php echo base_url ( 'css/' ); ?>bootstrap-icons.css" rel="stylesheet">
    <?php if (isset($css)): ?>
        <?php foreach ($css as $cssFile): ?>
            <link rel="stylesheet" href="<?= $cssFile ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    <style>
        .btn {
            border-radius: 0.85rem !important;
        }
    </style>
</head>
<body>
    <!-- Barra de Navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('/') ?>">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNavDropdown">
                <ul class="navbar-nav me-auto">
					<!-- Menú Pruebas -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="gestionDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-x-circle"></i> Pruebas
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('mi_productos/index') ?>"><i class="bi bi-list-task"></i> Productos</a></li>
							<li><a class="dropdown-item" href="<?= base_url('mi_categorias/index') ?>"><i class="bi bi-list-task"></i> Mi Categorías</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('twdepartamentos/index') ?>"><i class="bi bi-list-task"></i> Departamentos</a></li>
                            <li><a class="dropdown-item" href="<?= base_url('twclientes/index') ?>"><i class="bi bi-list-task"></i> Clientes</a></li>
                        </ul>
                    </li>
                    <!-- Menú Gestión -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="gestionDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear-wide-connected"></i> Gestión
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('admin/gestor-campos') ?>"><i class="bi bi-list-task"></i> Gestor de Campos</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-table"></i> Entidades</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person-gear"></i> Usuarios</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-shield-lock"></i> Roles</a></li>
                        </ul>
                    </li>
                    <!-- Menú Seguridad -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="seguridadDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-shield-lock"></i> Seguridad
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="<?= base_url('admin/auditoria') ?>"><i class="bi bi-journal-text"></i> Auditoría</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person-badge"></i> Permisos</a></li>
                        </ul>
                    </li>
                    <!-- Menú Configuración -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="configuracionDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-sliders"></i> Configuración
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-palette"></i> Temas</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-box-arrow-right"></i> Backup</a></li>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="perfilDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> Usuario
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="#"><i class="bi bi-person"></i> Perfil</a></li>
                            <li><a class="dropdown-item" href="#"><i class="bi bi-gear"></i> Ajustes</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('logout') ?>"><i class="bi bi-box-arrow-right"></i> Salir</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Mensajes de Sesión -->
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle-fill"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle-fill"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Contenido Principal -->
    <main class="container-fluid mt-3">
        <?= $this->renderSection('content') ?>
    </main>

    <!-- Pie de Página -->
    <footer class="bg-light text-center text-muted py-3 mt-5">
        <small>&copy; 2026 - Universal Tor-CRUD. Todos los derechos reservados.</small>
    </footer>

    <script src="<?php echo base_url ( 'js/' ); ?>bootstrap.bundle.min.js"></script>
    <?php if (isset($js)): ?>
        <?php foreach ($js as $jsFile): ?>
            <script src="<?= $jsFile ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
</body>
</html>