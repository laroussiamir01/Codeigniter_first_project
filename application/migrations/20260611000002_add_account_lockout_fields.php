<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Add_account_lockout_fields extends CI_Migration
{
	public function up()
	{
		$this->dbforge->add_column('users', [
			'login_attempts' => [
				'type'       => 'INT',
				'constraint' => 11,
				'default'    => 0,
			],
			'locked_until' => [
				'type' => 'DATETIME',
				'null' => TRUE,
			],
			'status' => [
				'type'       => "ENUM('active', 'locked', 'suspended')",
				'default'    => 'active',
			],
			'last_login_attempt' => [
				'type' => 'DATETIME',
				'null' => TRUE,
			],
			'last_login_attempt_ip' => [
				'type'       => 'VARCHAR',
				'constraint' => 45,
				'null'       => TRUE,
			],
		]);

		$this->dbforge->add_key('users', ['status']);
	}

	public function down()
	{
		$this->dbforge->drop_key('users', 'status');
		$this->dbforge->drop_column('users', 'login_attempts');
		$this->dbforge->drop_column('users', 'locked_until');
		$this->dbforge->drop_column('users', 'status');
		$this->dbforge->drop_column('users', 'last_login_attempt');
		$this->dbforge->drop_column('users', 'last_login_attempt_ip');
	}
}
