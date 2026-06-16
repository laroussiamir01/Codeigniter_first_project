<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Admin extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->model('Admin_model');
		$this->load->library('form_validation');
		$this->load->helper('url');
		$this->require_admin();
		$this->log_action();
	}

	public function dashboard()
	{
		$data['content'] = 'admin/dashboard_view';
		$data['stats'] = $this->Admin_model->get_user_statistics();
		$this->load->view('home_view', $data);
	}

	public function users()
	{
		$per_page = $this->admin_items_per_page();
		$page = max(1, (int) $this->input->get('page'));
		$offset = ($page - 1) * $per_page;
		$total = $this->Admin_model->count_users();

		$this->load->library('pagination');
		$this->pagination->initialize($this->pagination_config($total, $per_page, 'admin/users'));

		$data['content'] = 'admin/users_list';
		$data['users'] = $this->Admin_model->get_users_paginated($per_page, $offset);
		$data['links'] = $this->pagination->create_links();
		$data['search'] = '';
		$this->load->view('home_view', $data);
	}

	public function search()
	{
		$query = (string) $this->input->get('query');
		$data['content'] = 'admin/users_list';
		$data['users'] = $query === '' ? [] : $this->Admin_model->search_users($query);
		$data['links'] = '';
		$data['search'] = $query;
		$this->load->view('home_view', $data);
	}

	public function user_create()
	{
		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('username', 'Username', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[255]');
			$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[7]|max_length[20]');
			$this->form_validation->set_rules('first_name', 'First Name', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('birthday', 'Birthday', 'trim|required|max_length[20]');
			$this->form_validation->set_rules('role', 'Role', 'trim|required|in_list[user,admin]');

			if ($this->form_validation->run() === TRUE && ! $this->has_duplicate_user_data(NULL)) {
				$this->Admin_model->create_user([
					'username'   => $this->input->post('username'),
					'email'      => $this->input->post('email'),
					'password'   => $this->input->post('password'),
					'first_name' => $this->input->post('first_name'),
					'last_name'  => $this->input->post('last_name'),
					'birthday'   => $this->input->post('birthday'),
					'role'       => $this->input->post('role'),
				]);

				$this->session->set_flashdata('dashboard_success', 'User created successfully.');
				redirect('admin/users');
				return;
			}
		}

		$data['content'] = 'admin/user_create';
		$this->load->view('home_view', $data);
	}

	public function user_edit($user_id)
	{
		$user = $this->Admin_model->get_user($user_id);
		if (empty($user)) {
			show_404();
			return;
		}

		if ($this->input->method() === 'post') {
			$this->form_validation->set_rules('username', 'Username', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email|max_length[255]');
			$this->form_validation->set_rules('password', 'Password', 'trim|min_length[7]|max_length[20]');
			$this->form_validation->set_rules('first_name', 'First Name', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('last_name', 'Last Name', 'trim|required|max_length[255]');
			$this->form_validation->set_rules('birthday', 'Birthday', 'trim|required|max_length[20]');
			$this->form_validation->set_rules('role', 'Role', 'trim|required|in_list[user,admin]');

			if ($this->form_validation->run() === TRUE && ! $this->has_duplicate_user_data($user_id)) {
				$this->Admin_model->update_user($user_id, [
					'username'   => $this->input->post('username'),
					'email'      => $this->input->post('email'),
					'password'   => $this->input->post('password'),
					'first_name' => $this->input->post('first_name'),
					'last_name'  => $this->input->post('last_name'),
					'birthday'   => $this->input->post('birthday'),
					'role'       => $this->input->post('role'),
				]);

				$this->session->set_flashdata('dashboard_success', 'User updated successfully.');
				redirect('admin/users');
				return;
			}
		}

		$data['content'] = 'admin/user_edit';
		$data['user'] = $user;
		$this->load->view('home_view', $data);
	}

	public function user_delete($user_id)
	{
		if ($this->input->method() !== 'post') {
			redirect('admin/users');
			return;
		}

		$current_user_id = $this->session->userdata('user_id');
		if ((int) $current_user_id === (int) $user_id) {
			$this->session->set_flashdata('dashboard_error', 'You cannot delete your own account.');
			redirect('admin/users');
			return;
		}

		$this->Admin_model->delete_user($user_id);
		$this->session->set_flashdata('dashboard_success', 'User deleted successfully.');
		redirect('admin/users');
	}

	public function locked_accounts()
	{
		$data['content'] = 'admin/locked_accounts';
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

	public function login_attempts()
	{
		$data['content'] = 'admin/login_attempts';
		$data['attempts'] = $this->Admin_model->get_login_attempts(50);
		$this->load->view('home_view', $data);
	}

	public function otp_logs()
	{
		$data['content'] = 'admin/otp_logs';
		$data['otp_logs'] = $this->Admin_model->get_otp_logs(50);
		$data['otp_stats'] = $this->Admin_model->get_otp_statistics();
		$this->load->view('home_view', $data);
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

		if ($role !== 'admin' && ! in_array($username, $admin_usernames, TRUE)) {
			show_404();
			return;
		}

		$whitelist = array_filter(array_map('trim', explode(',', (string) $this->config->item('admin_ip_whitelist'))));
		if (! empty($whitelist) && ! in_array($this->input->ip_address(), $whitelist, TRUE)) {
			show_404();
			return;
		}
	}

	private function log_action()
	{
		$user_id = $this->session->userdata('user_id');
		if (empty($user_id)) {
			return;
		}

		$this->Admin_model->log_action($user_id, $this->uri->uri_string(), current_url());
	}

	private function has_duplicate_user_data($except_id)
	{
		$username = $this->input->post('username');
		$email = $this->input->post('email');

		if ($this->Admin_model->username_taken($username, $except_id)) {
			$this->session->set_flashdata('dashboard_error', 'Username already exists.');
			return TRUE;
		}

		if ($this->Admin_model->email_taken($email, $except_id)) {
			$this->session->set_flashdata('dashboard_error', 'Email already exists.');
			return TRUE;
		}

		return FALSE;
	}

	private function admin_items_per_page()
	{
		$per_page = (int) $this->config->item('admin_dashboard_items_per_page');
		return $per_page > 0 ? $per_page : 10;
	}

	private function pagination_config($total_rows, $per_page, $base_url)
	{
		$config['base_url'] = base_url($base_url);
		$config['total_rows'] = $total_rows;
		$config['per_page'] = $per_page;
		$config['page_query_string'] = TRUE;
		$config['query_string_segment'] = 'page';
		$config['reuse_query_string'] = TRUE;
		$config['full_tag_open'] = '<nav aria-label="Page navigation"><ul class="pagination">';
		$config['full_tag_close'] = '</ul></nav>';
		$config['num_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['num_tag_close'] = '</span></li>';
		$config['cur_tag_open'] = '<li class="page-item active"><span class="page-link">';
		$config['cur_tag_close'] = '</span></li>';
		$config['prev_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['prev_tag_close'] = '</span></li>';
		$config['next_tag_open'] = '<li class="page-item"><span class="page-link">';
		$config['next_tag_close'] = '</span></li>';
		$config['first_link'] = FALSE;
		$config['last_link'] = FALSE;
		return $config;
	}
}
