<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->require_admin();
	}

	public function locked_accounts()
	{
		$data['content'] = 'locked_accounts_view';
		$data['locked_users'] = $this->User_model->get_locked_accounts();
		$this->load->view('home_view', $data);
	}

	public function unlock_account($user_id)
	{
		if ($this->input->method() !== 'post') {
			redirect('admin/locked-accounts');
			return;
		}

		if ($this->User_model->unlock_account($user_id)) {
			$this->session->set_flashdata('unlock_success', 'Account unlocked successfully.');
		} else {
			$this->session->set_flashdata('unlock_error', 'Unable to unlock that account.');
		}

		redirect('admin/locked-accounts');
	}

	private function require_admin()
	{
		if ($this->session->userdata('logged_in') !== TRUE) {
			redirect('home');
			return;
		}

		$role = $this->session->userdata('role');
		$username = $this->session->userdata('username');
		$admin_usernames = array_map('trim', explode(',', (string) $this->config->item('account_lockout_admin_usernames')));

		if ($role === 'admin' || in_array($username, $admin_usernames, TRUE)) {
			return;
		}

		show_404();
	}
}
