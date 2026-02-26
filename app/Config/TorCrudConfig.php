<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class TorCrudConfig extends BaseConfig
{
    /**
     * Tema visual global para todo el CRUD
     * Coloca aquí el nombre del archivo CSS del tema
     * Ejemplos: 'darkly.min.css', 'flatly.min.css', 'minty.min.css', etc.
     * Si está vacío, no se carga ningún tema extra
     */
    public string $tema = 'united.css';

    /**
     * Ruta base donde están los temas
     * Por defecto: 'assets/css/themes/'
     */
    public string $rutaTemas = 'assets/css/themes/';
}