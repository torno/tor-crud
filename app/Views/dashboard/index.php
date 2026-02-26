<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>

<div class="row">
    <div class="col-12">
        <h2>Bienvenido al Dashboard</h2>
        <p>Este es el panel de control del sistema Universal Meta-CRUD.</p>
    </div>
</div>

<div class="row mt-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-people"></i> Usuarios</h5>
                <p class="card-text">Gestionar usuarios del sistema</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-table"></i> Entidades</h5>
                <p class="card-text">Configurar tablas y campos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-shield-lock"></i> Seguridad</h5>
                <p class="card-text">Auditoría y permisos</p>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <h5 class="card-title"><i class="bi bi-gear"></i> Configuración</h5>
                <p class="card-text">Ajustes del sistema</p>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>