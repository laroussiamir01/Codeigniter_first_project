<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Migration_Create_user_otp extends CI_Migration
{
	public function up()
	{
		$this->dbforge->add_field(array(
			'id' => array(
				'type'           => 'BIGINT',
				'constraint'     => 20,
				'unsigned'       => TRUE,
				'auto_increment' => TRUE,
			),
			'user_id' => array(
				'type'       => 'INT',
				'constraint' => 11,
				'unsigned'   => TRUE,
				'null'       => FALSE,
			),
			'code_hash' => array(
				'type'       => 'VARCHAR',
				'constraint' => 255,
				'null'       => FALSE,
			),
			'expires_at' => array(
				'type' => 'DATETIME',
				'null' => FALSE,
			),
			'consumed_at' => array(
				'type' => 'DATETIME',
				'null' => TRUE,
			),
			'attempts' => array(
				'type'       => 'TINYINT',
				'constraint' => 3,
				'unsigned'   => TRUE,
				'default'    => 0,
			),
			'created_at' => array(
				'type' => 'DATETIME',
				'null' => FALSE,
			),
		));

		$this->dbforge->add_key('id', TRUE);
		$this->dbforge->add_key('user_id');
		$this->dbforge->add_key('expires_at');

		$this->dbforge->create_table('user_otp');
	}

	public function down()
	{
		$this->dbforge->drop_table('user_otp');
	}
}
