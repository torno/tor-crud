<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateSecurityTables extends Migration
{
    public function up()
    {
        // Tabla: roles
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'nombre' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'descripcion' => [
                'type' => 'TEXT',
                'null' => true,
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
        $this->forge->createTable('roles');

        // Agregar FK a meta_permisos_campos (que ya existe)
        $this->forge->addForeignKey('rol_id', 'roles', 'id', 'CASCADE', 'CASCADE', 'fk_permisos_rol');
    }

    public function down()
    {
        // Eliminar FK primero
        if ($this->db->tableExists('meta_permisos_campos')) {
            $this->forge->dropForeignKey('meta_permisos_campos', 'fk_permisos_rol');
        }
        $this->forge->dropTable('roles');
    }
}