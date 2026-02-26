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

```bash
# Clona o descarga Tor-Crud
git clone https://github.com/torno/tor-crud.git

# Ejecuta el instalador desde la raíz de tu proyecto CodeIgniter
php ruta/a/tor-crud/install.php
