<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin_model extends CI_Model
{
	public function __construct()
	{
		parent::__construct();
	}

	public function get_user_statistics()
	{
		$stats = [
			'total_users'                  => $this->db->count_all('users'),
			'active_sessions'              => $this->active_session_count(),
			'failed_login_attempts_24h'    => $this->failed_login_attempts_24h(),
			'locked_accounts'              => $this->locked_account_count(),
		];

		return array_merge($stats, $this->otp_counts_24h());
	}

	public function get_login_attempts($limit = 50)
	{
		$this->db->where('login_attempts >', 0);
		$this->db->order_by('last_login_attempt', 'DESC');
		return $this->db->get('users', $limit, 0)->result();
	}

	public function get_otp_logs($limit = 50)
	{
		$this->db->order_by('id', 'DESC');
		return $this->db->get('user_otp', $limit, 0)->result();
	}

	public function get_otp_statistics()
	{
		return $this->otp_counts_24h();
	}

	public function search_users($query, $limit = 50)
	{
		$this->db->like('username', $query);
		$this->db->or_like('email', $query);
		$this->db->or_like('first_name', $query);
		$this->db->or_like('last_name', $query);
		return $this->db->get('users', $limit, 0)->result();
	}

	public function get_users_paginated($limit, $offset)
	{
		$this->db->order_by('id', 'DESC');
		return $this->db->get('users', $limit, $offset)->result();
	}

	public function count_users()
	{
		return $this->db->count_all('users');
	}

	public function get_user($user_id)
	{
		return $this->db->where('id', $user_id)->get('users')->row();
	}

	public function create_user($data)
	{
		if (! empty($data['password'])) {
			$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
		}

		return $this->db->insert('users', $data);
	}

	public function update_user($user_id, $data)
	{
		if (isset($data['password']) && $data['password'] === '') {
			unset($data['password']);
		}

		if (! empty($data['password'])) {
			$data['password'] = password_hash($data['password'], PASSWORD_BCRYPT, ['cost' => 12]);
		}

		$this->db->where('id', $user_id);
		return $this->db->update('users', $data);
	}

	public function delete_user($user_id)
	{
		$this->db->where('id', $user_id);
		return $this->db->delete('users');
	}

	public function username_taken($username, $except_id = NULL)
	{
		$this->db->where('username', $username);
		if ($except_id !== NULL) {
			$this->db->where('id !=', $except_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	public function email_taken($email, $except_id = NULL)
	{
		$this->db->where('email', $email);
		if ($except_id !== NULL) {
			$this->db->where('id !=', $except_id);
		}
		return $this->db->count_all_results('users') > 0;
	}

	public function log_action($admin_user_id, $action, $uri)
	{
		return $this->db->insert('admin_audit_log', [
			'admin_user_id' => $admin_user_id,
			'action'        => $action,
			'uri'           => $uri,
			'ip_address'    => $this->input->ip_address(),
			'created_at'    => date('Y-m-d H:i:s'),
		]);
	}

	private function active_session_count()
	{
		$path = config_item('sess_save_path') ?: APPPATH . 'cache/sessions';
		if (! is_dir($path)) {
			return 0;
		}

		$count = 0;
		$files = scandir($path);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}

			if (is_file($path . DIRECTORY_SEPARATOR . $file)) {
				$count++;
			}
		}

		return $count;
	}

	private function failed_login_attempts_24h()
	{
		$cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
		$this->db->where('login_attempts >', 0);
		$this->db->where('last_login_attempt >=', $cutoff);
		return $this->db->count_all_results('users');
	}

	private function locked_account_count()
	{
		$this->db->where('status', 'locked');
		$this->db->where('locked_until >', date('Y-m-d H:i:s'));
		return $this->db->count_all_results('users');
	}

	private function otp_counts_24h()
	{
		$cutoff = date('Y-m-d H:i:s', strtotime('-24 hours'));
		$this->db->where('created_at >=', $cutoff);
		$issued = $this->db->count_all_results('user_otp');

		$this->db->where('created_at >=', $cutoff);
		$this->db->where('consumed_at IS NOT NULL', NULL, FALSE);
		$consumed = $this->db->count_all_results('user_otp');

		return [
			'otp_issued_24h'    => $issued,
			'otp_consumed_24h'  => $consumed,
		];
	}
}
