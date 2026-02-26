#!/usr/bin/env php
<?php
/**
 * Instalador de Tor-Crud para CodeIgniter 4
 * 
 * Uso: php install.php
 */

// Configuración inicial
define('DS', DIRECTORY_SEPARATOR);
define('INSTALLER_ROOT', __DIR__);

// Colores para consola
define('COLOR_GREEN', "\033[32m");
define('COLOR_RED', "\033[31m");
define('COLOR_YELLOW', "\033[33m");
define('COLOR_RESET', "\033[0m");

echo COLOR_GREEN . "========================================\n";
echo "Instalador de Tor-Crud\n";
echo "========================================\n" . COLOR_RESET;

// ===========================================
// PASO 2: Solicitar ruta del proyecto
// ===========================================
$defaultPath = getcwd();
echo "Ruta del proyecto CodeIgniter 4 [{$defaultPath}]: ";
$handle = fopen("php://stdin", "r");
$inputPath = trim(fgets($handle));

$projectPath = !empty($inputPath) ? $inputPath : $defaultPath;

if (!is_dir($projectPath)) {
    echo COLOR_RED . "Error: La ruta no existe.\n" . COLOR_RESET;
    exit(1);
}

if (!is_dir($projectPath . DS . 'app') || !is_dir($projectPath . DS . 'public')) {
    echo COLOR_RED . "Error: No parece ser un proyecto CodeIgniter 4.\n" . COLOR_RESET;
    exit(1);
}

echo COLOR_GREEN . "Proyecto encontrado en: {$projectPath}\n" . COLOR_RESET;

// ===========================================
// PASO 3: Preguntar opciones
// ===========================================
echo "\n";
echo COLOR_YELLOW . "¿Qué deseas instalar? (puedes separar con comas)\n";
echo "1. Archivos de la librería (Libraries, Models, Controllers, Services)\n";
echo "2. Vistas\n";
echo "3. Assets (CSS, JS)\n";
echo "4. Helpers\n";
echo "5. Migraciones de base de datos\n";
echo "6. Configuración de rutas\n";
echo "7. TODO (recomendado)\n";
echo "Opción: " . COLOR_RESET;

$option = trim(fgets($handle));
$options = explode(',', $option);

if (empty($option) || in_array('7', $options)) {
    $options = ['1', '2', '3', '4', '5', '6'];
}

echo COLOR_GREEN . "Instalando opciones: " . implode(', ', $options) . "\n" . COLOR_RESET;

// ===========================================
// PASO 4: Definir rutas
// ===========================================
$sourceBase = INSTALLER_ROOT . DS . 'src';
$assetsBase = INSTALLER_ROOT . DS . 'assets';

$destLibraries = $projectPath . DS . 'app' . DS . 'Libraries';
$destModels = $projectPath . DS . 'app' . DS . 'Models';
$destServices = $projectPath . DS . 'app' . DS . 'Services'; // NUEVO
$destControllers = $projectPath . DS . 'app' . DS . 'Controllers' . DS . 'Admin';
$destViews = $projectPath . DS . 'app' . DS . 'Views' . DS . 'tor_crud';
$destConfig = $projectPath . DS . 'app' . DS . 'Config';
$destHelpers = $projectPath . DS . 'app' . DS . 'Helpers';
$destAssets = $projectPath . DS . 'public' . DS . 'assets';
$destGestorViews = $projectPath . DS . 'app' . DS . 'Views' . DS . 'admin' . DS . 'gestor_campos';
$destAuditoriaViews = $projectPath . DS . 'app' . DS . 'Views' . DS . 'admin' . DS . 'auditoria'; // NUEVO

$directories = [
    $destLibraries,
    $destModels,
    $destServices, // NUEVO
    $destControllers,
    $destViews,
    $destGestorViews,
    $destAuditoriaViews, // NUEVO
    $destHelpers,
    $destAssets . DS . 'css',
    $destAssets . DS . 'js',
];

foreach ($directories as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
        echo "Creado directorio: {$dir}\n";
    }
}

// ===========================================
// PASO 5: Funciones auxiliares
// ===========================================
function copyFile($source, $dest, $description = '')
{
    if (!file_exists($source)) {
        echo COLOR_YELLOW . "  ⚠ No encontrado: {$source}\n" . COLOR_RESET;
        return false;
    }
    
    if (copy($source, $dest)) {
        echo COLOR_GREEN . "  ✅ Copiado: {$description}\n" . COLOR_RESET;
        return true;
    } else {
        echo COLOR_RED . "  ❌ Error copiando: {$description}\n" . COLOR_RESET;
        return false;
    }
}

function copyDirectory($source, $dest, $description = '')
{
    if (!is_dir($source)) {
        echo COLOR_YELLOW . "  ⚠ Directorio no encontrado: {$source}\n" . COLOR_RESET;
        return;
    }
    
    $dir = opendir($source);
    @mkdir($dest, 0755, true);
    
    $count = 0;
    while (($file = readdir($dir)) !== false) {
        if ($file != '.' && $file != '..') {
            if (is_dir($source . DS . $file)) {
                copyDirectory($source . DS . $file, $dest . DS . $file, $description . '/' . $file);
            } else {
                copyFile($source . DS . $file, $dest . DS . $file, $description . '/' . $file);
                $count++;
            }
        }
    }
    closedir($dir);
    
    echo COLOR_GREEN . "  📁 Directorio {$description}: {$count} archivos copiados\n" . COLOR_RESET;
}

// ===========================================
// PASO 6: Copiar archivos
// ===========================================
if (in_array('1', $options)) {
    echo "\n" . COLOR_YELLOW . "Instalando archivos de librería...\n" . COLOR_RESET;
    
    if (is_dir($sourceBase . DS . 'Libraries')) {
        copyDirectory($sourceBase . DS . 'Libraries', $destLibraries, 'Libraries');
    }
    
    if (is_dir($sourceBase . DS . 'Models')) {
        copyDirectory($sourceBase . DS . 'Models', $destModels, 'Models');
    }
    
    if (is_dir($sourceBase . DS . 'Services')) { // NUEVO
        copyDirectory($sourceBase . DS . 'Services', $destServices, 'Services');
    }
    
    if (is_dir($sourceBase . DS . 'Controllers')) {
        copyDirectory($sourceBase . DS . 'Controllers', $projectPath . DS . 'app' . DS . 'Controllers', 'Controllers');
    }
}

// ===========================================
// COPIAR VISTAS DEL CRUD (tor_crud)
// ===========================================
if (in_array('2', $options)) {
    $torCrudSource = $sourceBase . DS . 'Views' . DS . 'tor_crud';
    $torCrudDest = $projectPath . DS . 'app' . DS . 'Views' . DS . 'tor_crud';

    if (is_dir($torCrudSource)) {
        echo "\n" . COLOR_YELLOW . "Copiando vistas del CRUD...\n" . COLOR_RESET;
        
        if (!is_dir($torCrudDest)) {
            mkdir($torCrudDest, 0755, true);
        }
        
        $files = scandir($torCrudSource);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..' && is_file($torCrudSource . DS . $file)) {
                copyFile(
                    $torCrudSource . DS . $file, 
                    $torCrudDest . DS . $file, 
                    "tor_crud/{$file}"
                );
            }
        }
    }

    // ===========================================
    // COPIAR VISTAS DEL GESTOR (admin/gestor_campos)
    // ===========================================
    $gestorSource = $sourceBase . DS . 'Views' . DS . 'admin' . DS . 'gestor_campos';
    $gestorDest = $projectPath . DS . 'app' . DS . 'Views' . DS . 'admin' . DS . 'gestor_campos';

    if (is_dir($gestorSource)) {
        echo "\n" . COLOR_YELLOW . "Copiando vistas del gestor de campos...\n" . COLOR_RESET;
        copyDirectory($gestorSource, $gestorDest, 'admin/gestor_campos');
    }

    // ===========================================
    // COPIAR VISTAS DE AUDITORÍA (admin/auditoria) - NUEVO
    // ===========================================
    $auditoriaSource = $sourceBase . DS . 'Views' . DS . 'admin' . DS . 'auditoria';
    $auditoriaDest = $projectPath . DS . 'app' . DS . 'Views' . DS . 'admin' . DS . 'auditoria';

    if (is_dir($auditoriaSource)) {
        echo "\n" . COLOR_YELLOW . "Copiando vistas de auditoría...\n" . COLOR_RESET;
        copyDirectory($auditoriaSource, $auditoriaDest, 'admin/auditoria');
    }

    // Migración de auditoría
    $auditMigrationFile = $sourceBase . DS . 'Database' . DS . 'Migrations' . DS . 'create_audit_log_table.php';
    if (file_exists($auditMigrationFile)) {
        $timestamp = date('Ymd_His', strtotime('+1 second'));
        $destAuditFile = $destMigrationDir . DS . $timestamp . '_create_audit_log_table.php';
        copyFile($auditMigrationFile, $destAuditFile, 'Migración de auditoría');
    }

    // ===========================================
    // COPIAR TEMAS (BOOTSWATCH)
    // ===========================================
    $themesSource = $assetsBase . DS . 'css' . DS . 'themes';
    $themesDest = $destAssets . DS . 'css' . DS . 'themes';

    if (is_dir($themesSource)) {
        echo "\n" . COLOR_YELLOW . "Copiando temas visuales...\n" . COLOR_RESET;
        copyDirectory($themesSource, $themesDest, 'assets/css/themes');
    }

    // ===========================================
    // COPIAR CONFIGURACIÓN (TorCrudConfig.php)
    // ===========================================
    $configSource = $sourceBase . DS . 'Config' . DS . 'TorCrudConfig.php';
    $configDest = $destConfig . DS . 'TorCrudConfig.php';
    if (file_exists($configSource)) {
        copyFile($configSource, $configDest, 'Config/TorCrudConfig.php');
    }

}

if (in_array('3', $options)) {
    echo "\n" . COLOR_YELLOW . "Instalando assets...\n" . COLOR_RESET;
    
    if (is_dir($assetsBase)) {
        copyDirectory($assetsBase, $destAssets, 'assets');
    }
}

if (in_array('4', $options)) {
    echo "\n" . COLOR_YELLOW . "Instalando helpers...\n" . COLOR_RESET;
    
    if (is_dir($sourceBase . DS . 'Helpers')) {
        copyDirectory($sourceBase . DS . 'Helpers', $destHelpers, 'Helpers');
    }
}

// ===========================================
// PASO 7: Migraciones
// ===========================================
if (in_array('5', $options)) {
    echo "\n" . COLOR_YELLOW . "Preparando migraciones...\n" . COLOR_RESET;
    
    $destMigrationDir = $projectPath . DS . 'app' . DS . 'Database' . DS . 'Migrations';
    if (!is_dir($destMigrationDir)) {
        mkdir($destMigrationDir, 0755, true);
    }
    
    // Migración de tablas principales (tc_*)
    $mainMigrationFile = $sourceBase . DS . 'Database' . DS . 'Migrations' . DS . 'create_tc_tables.php';
    if (file_exists($mainMigrationFile)) {
        $destMainFile = $destMigrationDir . DS . date('Ymd_His') . '_create_tc_tables.php';
        copyFile($mainMigrationFile, $destMainFile, 'Migración principal');
    } else {
        echo COLOR_YELLOW . "  ⚠ No se encontró migración principal\n" . COLOR_RESET;
    }
    
    // Migración de auditoría - NUEVO
    $auditMigrationFile = $sourceBase . DS . 'Database' . DS . 'Migrations' . DS . 'create_audit_log_table.php';
    if (file_exists($auditMigrationFile)) {
        // Sumar 1 segundo para que sea posterior
        $timestamp = date('Ymd_His', strtotime('+1 second'));
        $destAuditFile = $destMigrationDir . DS . $timestamp . '_create_audit_log_table.php';
        copyFile($auditMigrationFile, $destAuditFile, 'Migración de auditoría');
    }
    
    echo COLOR_YELLOW . "  ⚠ Ejecuta: php spark migrate\n" . COLOR_RESET;
}

// ===========================================
// PASO 8: Rutas
// ===========================================
if (in_array('6', $options)) {
    echo "\n" . COLOR_YELLOW . "Configurando rutas...\n" . COLOR_RESET;
    
    $routesFile = $destConfig . DS . 'Routes.php';
    
    if (!file_exists($routesFile)) {
        echo COLOR_RED . "  ❌ No se encontró el archivo de rutas\n" . COLOR_RESET;
    } else {
        $routesContent = file_get_contents($routesFile);
        
        if (strpos($routesContent, 'TOR-CRUD ROUTES') !== false) {
            echo COLOR_YELLOW . "  ⚠ Las rutas de Tor-Crud ya están configuradas\n" . COLOR_RESET;
        } else {
            $torRoutes = "\n\n// ===========================================\n";
            $torRoutes .= "// TOR-CRUD ROUTES - GESTOR DE CAMPOS\n";
            $torRoutes .= "// ===========================================\n";
            $torRoutes .= "\$routes->group('admin', static function (\$routes) {\n";
            $torRoutes .= "    \$routes->get('gestor-campos', 'Admin\GestorCampos::index');\n";
            $torRoutes .= "    \$routes->get('gestor-campos/configurar/(:any)', 'Admin\GestorCampos::configurar/$1');\n";
            $torRoutes .= "    \$routes->post('gestor-campos/guardar', 'Admin\GestorCampos::guardar');\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "// ===========================================\n";
            $torRoutes .= "// TOR-CRUD ROUTES - VIRTUALES\n";
            $torRoutes .= "// ===========================================\n";
            $torRoutes .= "\$routes->get('(:any)/tcdelete/(:num)', function(\$controller, \$id) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->post('(:any)/tcbulkdelete', function(\$controller) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcedit/(:num)', function(\$controller, \$id) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcview/(:num)', function(\$controller, \$id) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcadd', function(\$controller) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcclone/(:num)', function(\$controller, \$id) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcexportcsv', function(\$controller) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcexportpdf', function(\$controller) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->get('(:any)/tcprint', function(\$controller) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n\n";
            
            $torRoutes .= "\$routes->post('(:any)/tcinlineedit/(:num)', function(\$controller, \$id) {\n";
            $torRoutes .= "    \$controllerClass = 'App\\\\Controllers\\\\' . ucfirst(\$controller);\n";
            $torRoutes .= "    if (class_exists(\$controllerClass)) {\n";
            $torRoutes .= "        \$ctrl = new \$controllerClass();\n";
            $torRoutes .= "        return \$ctrl->index();\n";
            $torRoutes .= "    }\n";
            $torRoutes .= "    throw \\CodeIgniter\\Exceptions\\PageNotFoundException::forPageNotFound();\n";
            $torRoutes .= "});\n";
            
            // ===========================================
            // TOR-CRUD ROUTES - AUDITORÍA (NUEVO)
            // ===========================================
            $torRoutes .= "\n// ===========================================\n";
            $torRoutes .= "// TOR-CRUD ROUTES - AUDITORÍA\n";
            $torRoutes .= "// ===========================================\n";
            $torRoutes .= "\$routes->get('admin/auditoria', 'Admin\Auditoria::index');\n";
            $torRoutes .= "\$routes->get('admin/auditoria/detalle/(:num)', 'Admin\Auditoria::detalle/$1');\n\n";
            
            if (substr(trim($routesContent), -2) == '})') {
                $routesContent = substr($routesContent, 0, strrpos($routesContent, '})'));
                $routesContent = rtrim($routesContent) . "\n";
                $routesContent .= $torRoutes;
                $routesContent .= "})";
            } else {
                $routesContent .= $torRoutes;
            }
            
            if (file_put_contents($routesFile, $routesContent)) {
                echo COLOR_GREEN . "  ✅ Rutas añadidas correctamente\n" . COLOR_RESET;
            } else {
                echo COLOR_RED . "  ❌ Error al escribir las rutas\n" . COLOR_RESET;
            }
        }
    }
}

// ===========================================
// PASO 9: Mensaje final
// ===========================================
echo "\n" . COLOR_GREEN . "========================================\n";
echo "✅ Instalación completada\n";
echo "========================================\n" . COLOR_RESET;

// Verificar configuración de cache
echo "\n" . COLOR_YELLOW . "Verificando configuración de cache...\n" . COLOR_RESET;

$cacheConfigFile = $projectPath . DS . 'app' . DS . 'Config' . DS . 'Cache.php';
if (file_exists($cacheConfigFile)) {
    $cacheConfig = file_get_contents($cacheConfigFile);
    
    if (strpos($cacheConfig, "public \$handler = 'file'") !== false) {
        echo COLOR_GREEN . "  ✅ Cache configurado con driver 'file'\n" . COLOR_RESET;
    } else {
        echo COLOR_YELLOW . "  ⚠ El cache no está configurado como 'file'. Tor-Crud funcionará pero puede ser más lento.\n";
        echo "     Se recomienda usar driver 'file' en app/Config/Cache.php\n" . COLOR_RESET;
    }
}

echo "\n";
echo "Próximos pasos:\n";
echo "1. Ejecuta las migraciones: php spark migrate\n";
echo "2. Accede al gestor de campos: /admin/gestor-campos\n";
echo "3. Configura tus tablas\n";
echo "4. Accede al visor de auditoría: /admin/auditoria\n";
echo "5. Para activar auditoría, añade a .env: auditoria.enabled = true\n";
echo "   y configura la conexión 'auditoria' en app/Config/Database.php\n";
echo "6. Crea controladores como Tor_Categorias, Tor_Productos, etc.\n";
echo "\n";