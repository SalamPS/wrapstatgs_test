<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateCmsPurchaseTables extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'name' => [
                'type' => 'VARCHAR',
                'constraint' => 100,
            ],
            'email' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'phone' => [
                'type' => 'VARCHAR',
                'constraint' => 20,
            ],
            'address' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addUniqueKey('email');
        $this->forge->createTable('users', true);

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'product_name' => [
                'type' => 'VARCHAR',
                'constraint' => 120,
            ],
            'description' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true,
            ],
            'qty_in_stock' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'default' => 0,
            ],
            'price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
                'default' => 0,
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
        $this->forge->createTable('products', true);

        $this->forge->addField([
            'id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
                'auto_increment' => true,
            ],
            'user_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'product_id' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'payment_method' => [
                'type' => 'VARCHAR',
                'constraint' => 40,
            ],
            'qty' => [
                'type' => 'INT',
                'constraint' => 11,
                'unsigned' => true,
            ],
            'unit_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'total_price' => [
                'type' => 'DECIMAL',
                'constraint' => '15,2',
            ],
            'notes' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
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
        $this->forge->addKey('user_id');
        $this->forge->addKey('product_id');
        $this->forge->addForeignKey('user_id', 'users', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->addForeignKey('product_id', 'products', 'id', 'RESTRICT', 'RESTRICT');
        $this->forge->createTable('transactions', true);
    }

    public function down()
    {
        $this->forge->dropTable('transactions', true);
        $this->forge->dropTable('products', true);
        $this->forge->dropTable('users', true);
    }
}
