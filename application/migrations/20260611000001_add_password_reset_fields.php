<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_password_reset_fields extends CI_Migration
{
	public function up()
	{
		$this->dbforge->add_column('users', [
			'password_reset_token' => [
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => TRUE,
			],
			'password_reset_expires' => [
				'type' => 'DATETIME',
				'null' => TRUE,
			],
			'password_reset_requested_at' => [
				'type' => 'DATETIME',
				'null' => TRUE,
			],
		]);
	}

	public function down()
	{
		$this->dbforge->drop_column('users', 'password_reset_token');
		$this->dbforge->drop_column('users', 'password_reset_expires');
		$this->dbforge->drop_column('users', 'password_reset_requested_at');
	}
}
