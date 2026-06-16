<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_role_admin_fields extends CI_Migration
{
	public function up()
	{
		$this->dbforge->add_column('users', [
			'role' => [
				'type'       => "ENUM('user', 'admin')",
				'default'    => 'user',
				'null'       => FALSE,
			],
		]);

		$this->dbforge->add_field([
			'id' => [
				'type'           => 'INT',
				'constraint'     => 11,
				'unsigned'       => TRUE,
				'auto_increment' => TRUE,
			],
			'admin_user_id' => [
				'type'       => 'INT',
				'constraint' => 11,
				'unsigned'   => TRUE,
			],
			'action' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
			],
			'uri' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
			],
			'ip_address' => [
				'type'       => 'VARCHAR',
				'constraint' => 45,
			],
			'created_at' => [
				'type' => 'DATETIME',
			],
		]);
		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->add_key('admin_user_id');
		$this->dbforge->create_table('admin_audit_log');
	}

	public function down()
	{
		$this->dbforge->drop_table('admin_audit_log', TRUE);
		$this->dbforge->drop_column('users', 'role');
	}
}
