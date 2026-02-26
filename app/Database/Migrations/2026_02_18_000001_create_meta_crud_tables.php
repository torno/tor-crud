<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateMetaCrudTables extends Migration
{
    public function up()
    {
        // 1. Tabla meta_entidades
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre_tabla' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'titulo' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'permite_busqueda' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'filtro_global' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'eliminacion_logica' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'activo' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'permite_crear' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'permite_editar' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'permite_eliminar' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'permite_exportar' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'permite_importar' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'permite_clonar' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'permite_borrado_masivo' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'usa_paginacion' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'usa_ajax' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'filtro_personalizado' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addUniqueKey('nombre_tabla');
        $this->forge->createTable('meta_entidades');

        // 2. Tabla meta_campos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'entidad_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'nombre_campo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'etiqueta_mostrar' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'tipo_control' => [
                'type' => 'ENUM',
                'constraint' => ['text','number','email','password','textarea','select','enum','date','datetime','hidden','file','image','wysiwyg','virtual'],
                'null' => true,
            ],
            'oculto_en_lista' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'oculto_en_form' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'orden_visual' => [
                'type' => 'INT',
                'default' => 0,
            ],
            'relacion_tabla' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'relacion_campo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'relacion_id' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true,
            ],
            'valores_posibles' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'valor_default' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'comportamiento_hidden' => [
                'type' => 'ENUM',
                'constraint' => ['usar_default_db','dejar_null','forzar_valor'],
                'null' => true,
            ],
            'es_virtual' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'archivo_tipo_permitido' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'archivo_tamano_maximo' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
                'null' => true,
            ],
            'archivo_carpeta_destino' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'archivo_subcarpeta_por_entidad' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'archivo_mostrar_miniatura' => [
                'type' => 'BOOLEAN',
                'default' => false,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('entidad_id');
        $this->forge->addUniqueKey(['entidad_id', 'nombre_campo']);
        $this->forge->addForeignKey('entidad_id', 'meta_entidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('meta_campos');

        // 3. Tabla meta_reglas_validacion
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'campo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'regla_tipo' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'parametro_valor' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('campo_id');
        $this->forge->addForeignKey('campo_id', 'meta_campos', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('meta_reglas_validacion');

        // 4. Tabla meta_permisos_campos
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'campo_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'rol_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'permiso_tipo' => [
                'type' => 'ENUM',
                'constraint' => ['ver','editar','ninguno'],
                'default' => 'ver',
            ],
            'activo' => [
                'type' => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('campo_id');
        $this->forge->addUniqueKey(['campo_id', 'rol_id']);
        $this->forge->addForeignKey('campo_id', 'meta_campos', 'id', 'CASCADE', 'CASCADE');
        // La FK a 'roles' la agregarás después cuando exista la tabla
        $this->forge->createTable('meta_permisos_campos');

        // 5. Tabla meta_campos_virtuales
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'entidad_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
            ],
            'tipo' => [
                'type' => 'ENUM',
                'constraint' => ['display','n_a_n'],
            ],
            'funcion_display' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'tabla_intermedia' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'tabla_fuente' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'campo_local_fk' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'campo_externo_fk' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'campo_id_fuente' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'formato_visualizacion' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
                'default' => null,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey('entidad_id');
        $this->forge->addUniqueKey(['entidad_id', 'nombre']);
        $this->forge->addForeignKey('entidad_id', 'meta_entidades', 'id', 'CASCADE', 'CASCADE');
        $this->forge->createTable('meta_campos_virtuales');
    }

    public function down()
    {
        $this->forge->dropTable('meta_campos_virtuales');
        $this->forge->dropTable('meta_permisos_campos');
        $this->forge->dropTable('meta_reglas_validacion');
        $this->forge->dropTable('meta_campos');
        $this->forge->dropTable('meta_entidades');
    }
}