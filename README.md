# Tor-Crud

<p align="center">
    <img src="public/assets/TorCrud Logo.png" alt="Tor-Crud Logo" width="200">
</p>

<p align="center">
    <strong>Sistema de CRUD dinámico basado en metadatos para CodeIgniter 4</strong>
</p>

<p align="center">
    <a href="#-características">Características</a> •
    <a href="#-requisitos">Requisitos</a> •
    <a href="#-instalación">Instalación</a> •
    <a href="#-uso-básico">Uso básico</a> •
    <a href="#-documentación">Documentación</a> •
    <a href="#-licencia">Licencia</a>
</p>

---

## ✨ Características

Tor-Crud es un generador de CRUD que lee la configuración directamente desde la base de datos, permitiendo crear interfaces de administración completas sin escribir una sola línea de código por cada tabla.

### 🔥 Principales funcionalidades

- **Gestor de campos visual** - Interfaz para configurar tablas, campos, tipos de control y relaciones.
- **CRUD automático** - Listado, creación, edición, vista detalle y eliminación desde un solo controlador.
- **Todos los tipos de campo** - text, number, email, password, textarea, select, enum, boolean, date, datetime, hidden, file, image, wysiwyg.
- **Relaciones** - 1 a N y N a N con formato personalizable `{campo1} - {campo2}`.
- **Campos virtuales** - Display (función del controlador) y N a N (tablas intermedias).
- **Edición en línea** - Doble clic para editar campos directamente en el listado.
- **Filtros y búsqueda** - Filtros por columna con timer, búsqueda global, persistencia en sesión.
- **Exportación** - CSV, PDF (con DomPDF) e impresión.
- **Callbacks** - before/after Insert, Update, Delete, Upload.
- **Auditoría** - Registro de cambios en base de datos externa (opcional).
- **Seguridad** - Deshabilitar acciones a nivel de controlador (`unsetAdd`, `unsetEdit`, etc.).
- **Cache de metadatos** - Reducción de consultas a BD.

---

## 📋 Requisitos

- PHP 8.0 o superior
- CodeIgniter 4.4 o superior
- MySQL 5.7+ / MariaDB 10.2+
- Composer (para dependencias)

---

## 🚀 Instalación

### 1. Usando el instalador (recomendado)

### Clona o descarga Tor-Crud
git clone https://github.com/torno/tor-crud.git

### Ejecuta el instalador desde la raíz de tu proyecto CodeIgniter
php ruta/a/tor-crud/install.php

## 2. Instalación manual

### Si prefieres hacerlo manualmente: Copia las carpetas a tu proyecto
cp -r src/Libraries app/

cp -r src/Models app/

cp -r src/Controllers app/

cp -r src/Views app/

cp -r src/Helpers app/

cp -r assets public/

cp src/Database/Migrations/* app/Database/Migrations/

### Ejecuta las migraciones
php spark migrate

### Añade las rutas a app/Config/Routes.php (ver sección de rutas)

### 3. Configuración

Base de datos: 
- Configura tu conexión en .env.
- Cache (opcional): Para mejor rendimiento, usa driver file en app/Config/Cache.php.
- Auditoría (opcional): Configura conexión externa en app/Config/Database.php y activa en .env:

    auditoria.enabled = true

## Uso básico

### 1. Configurar una tabla

Accede a /admin/gestor-campos y configura tu primera tabla:

- Define campos, tipos de control, validaciones
- Configura relaciones 1 a N con el asistente visual
- Añade campos virtuales si los necesitas

### 2. Crear un controlador básico

    <?php
    
    namespace App\Controllers;
    
    use App\Libraries\TorCrud;
    
    class Tor_Productos extends BaseController
    {
        public function index()
        {
            $crud = new TorCrud();
            $crud->setTable('mc_productos');
            
            // Opcional: personalizar acciones
            // $crud->unsetAdd();
            // $crud->unsetEdit();
            
            // Añadir acciones personalizadas
            $crud->addGlobalAction([
                'icono' => 'bi-printer',
                'nombre' => '',
                'url' => '/tor_productos/print',
                'tooltip' => 'Imprimir'
            ]);
            
            $crud->addRowAction([
                'icono' => 'bi-calculator',
                'nombre' => 'Calcular',
                'js' => 'calcularGanancias({id})'
            ]);
            
            // Callbacks
            $crud->beforeInsert('procesarPrecio');
            
            return $crud->render();
        }
        
        public function procesarPrecio(&$data)
        {
            $data['precio_total'] = $data['precio'] * $data['cantidad'];
            return $data;
        }
    }

### 3. Ejemplo con filtros personalizados

    $crud = new TorCrud();
    $crud->setTable('mc_productos');
    
    // Filtros simples
    $crud->setWhere('activo', 1);
    $crud->setWhere([
        'precio >' => 100,
        'categoria_id' => 5
    ]);
    
    // Filtros complejos
    $crud->setWhere([
        ['precio', '>', 100],
        ['nombre', 'LIKE', '%laptop%'],
        ['categoria_id', 'IN', [1,2,3]]
    ]);
    
    // Sin búsquedas (para vistas de solo listado)
    $crud->unsetSearch();

## Documentación

### Métodos principales
- setTable($tabla)	Establece la tabla a trabajar
- setViewData($data)	Pasa datos adicionales a la vista
- setWhere($field, $value)	Filtros personalizados
- unsetAdd()	Deshabilita creación
- unsetEdit()	Deshabilita edición
- unsetDelete()	Deshabilita eliminación
- unsetSearch()	Deshabilita búsquedas
- unsetClone()	Deshabilita clonación
- unsetExport()	Deshabilita exportación
- addGlobalAction($action)	Añade botón global
- addRowAction($action)	Añade acción por fila
- beforeInsert($callback)	Callback antes de insertar
- afterInsert($callback)	Callback después de insertar
- beforeUpdate($callback)	Callback antes de actualizar
- afterUpdate($callback)	Callback después de actualizar
- beforeDelete($callback)	Callback antes de eliminar
- afterDelete($callback)	Callback después de eliminar
    
### Tipos de campo soportados
- text, number, email, password, textarea
- select (relaciones 1 a N con formato {campo1} - {campo2})
- enum (valores fijos o desde BD)
- boolean (checkbox)
- date, datetime
- hidden (con __NOW__, __USER_ID__, __CONTROLADOR__:campo)
- file, image (subida con validación)
- wysiwyg (editor Quill)
- virtual_n_a_n (relaciones muchos a muchos)
    
