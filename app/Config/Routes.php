<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */
$routes->get('/', 'Dashboard::index');
$routes->get('dashboard', 'Dashboard::index');

$routes->group('admin', static function ($routes) {
    $routes->get('gestor-campos', 'Admin\GestorCampos::index');
    $routes->get('gestor-campos/configurar/(:any)', 'Admin\GestorCampos::configurar/$1');
    $routes->post('gestor-campos/guardar', 'Admin\GestorCampos::guardar'); 
    $routes->get('gestor-campos/clearcache/(:any)', 'Admin\GestorCampos::clearcache/$1'); 
    $routes->post('gestor-campos/generar-controlador', 'Admin\GestorCampos::generarControlador');
    $routes->get('gestor-campos/getTablas', 'Admin\GestorCampos::getTablas');
    $routes->get('gestor-campos/getCamposDeTabla', 'Admin\GestorCampos::getCamposDeTabla');
    $routes->get('gestor-campos/analizar-relacion-nan', 'Admin\GestorCampos::analizarRelacionNan');
    $routes->get('gestor-campos/get-selector-nan', 'Admin\GestorCampos::getSelectorNan');
    $routes->get('auditoria', 'Admin\Auditoria::index');
    $routes->get('auditoria/detalle/(:num)', 'Admin\Auditoria::detalle/$1');
});

$routes->get('mi_tabla/calculos', 'Mi_tabla::calculos');
$routes->post('mi_tabla/calculos', 'Mi_tabla::calculos');

$routes->get('mi_categorias', 'Mi_categorias::index');
$routes->post('mi_categorias', 'Mi_categorias::index');
$routes->get('mi_categorias/index', 'Mi_categorias::index');
$routes->post('mi_categorias/index', 'Mi_categorias::index');

$routes->get('mi_productos', 'Mi_productos::index');
$routes->post('mi_productos', 'Mi_productos::index');
$routes->get('mi_productos/index', 'Mi_productos::index');
$routes->post('mi_productos/index', 'Mi_productos::index');


$routes->get('twdepartamentos', 'TwDepartamentos::index');
$routes->post('twdepartamentos', 'TwDepartamentos::index');
$routes->get('twdepartamentos/index', 'TwDepartamentos::index');
$routes->post('twdepartamentos/index', 'TwDepartamentos::index');
$routes->get('twclientes', 'TwClientes::index');
$routes->post('twclientes', 'TwClientes::index');
$routes->get('twclientes/index', 'TwClientes::index');
$routes->post('twclientes/index', 'TwClientes::index');
// Las rutas virtuales las maneja el CRUD internamente

// Rutas virtuales para Meta_Crud
$routes->get('(:any)/tcdelete/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index(); // Meta_Crud capturará la acción
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->post('(:any)/tcbulkdelete', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

// Y lo mismo para mcedit, mcview, mcadd
$routes->get('(:any)/tcedit/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->post('(:any)/tcedit/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->get('(:any)/tcview/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->get('(:any)/tcadd', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->post('(:any)/tcadd', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->get('(:any)/tcclone/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->post('(:any)/tcclone/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->get('(:any)/tcexportcsv', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});
$routes->get('(:any)/tcprint', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});
$routes->get('(:any)/tcexportpdf', function($controller) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});

$routes->post('(:any)/tcinlineedit/(:num)', function($controller, $id) {
    $controllerClass = 'App\\Controllers\\' . ucfirst($controller);
    if (class_exists($controllerClass)) {
        $ctrl = new $controllerClass();
        return $ctrl->index();
    }
    throw \CodeIgniter\Exceptions\PageNotFoundException::forPageNotFound();
});