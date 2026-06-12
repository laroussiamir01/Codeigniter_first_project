<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Password_reset extends CI_Controller
{
	public $rate_limit_minutes = 15;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('User_model');
		$this->load->library('form_validation');
		$this->load->library('email');
		$this->load->helper('url');
	}

	public function forgot_password()
	{
		$data['content'] = 'forgot_password_view';
		$this->load->view('home_view', $data);
	}

	public function send_reset_link()
	{
		$this->form_validation->set_rules('email', 'Email', 'trim|required|valid_email');

		if ($this->form_validation->run() == FALSE) {
			$data['content'] = 'forgot_password_view';
			$this->load->view('home_view', $data);
			return;
		}

		$email = $this->input->post('email');
		$user  = $this->User_model->get_by_email($email);

		if (empty($user)) {
			$this->session->set_flashdata('reset_info', 'If that email is registered, a reset link has been sent.');
			redirect('password/forgot');
			return;
		}

		if (
			! empty($user->password_reset_requested_at)
			&& strtotime($user->password_reset_requested_at) > strtotime("-{$this->rate_limit_minutes} minutes")
		) {
			$this->session->set_flashdata('reset_info', 'A reset link was already sent recently. Please check your email or try again later.');
			redirect('password/forgot');
			return;
		}

		$token = bin2hex(random_bytes(16));

		$this->User_model->set_reset_token($user->id, $token);

		$sent = $this->send_reset_email($user->email, $token, $user->username ?? 'User');

		if ($sent) {
			$this->session->set_flashdata('reset_info', 'If that email is registered, a reset link has been sent.');
		} else {
			$this->session->set_flashdata('reset_error', 'Could not send the email. Please try again later.');
		}

		redirect('password/forgot');
	}

	public function reset_password($token = NULL)
	{
		if (empty($token)) {
			show_404();
		}

		$user = $this->User_model->get_user_by_reset_token($token);

		if (empty($user)) {
			$this->session->set_flashdata('reset_error', 'This reset link is invalid or has expired.');
			redirect('password/forgot');
			return;
		}

		$data['content']   = 'reset_password_view';
		$data['token']     = $token;
		$data['user_email'] = $user->email;
		$this->load->view('home_view', $data);
	}

	public function update_password()
	{
		$this->form_validation->set_rules('token', 'Token', 'trim|required');
		$this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[7]|max_length[20]');
		$this->form_validation->set_rules('passconf', 'Confirm Password', 'trim|required|matches[password]');

		if ($this->form_validation->run() == FALSE) {
			$token = $this->input->post('token');
			$user  = $this->User_model->get_user_by_reset_token($token);

			if (empty($user)) {
				$this->session->set_flashdata('reset_error', 'This reset link is invalid or has expired.');
				redirect('password/forgot');
				return;
			}

			$data['content']   = 'reset_password_view';
			$data['token']     = $token;
			$data['user_email'] = $user->email;
			$this->load->view('home_view', $data);
			return;
		}

		$token    = $this->input->post('token');
		$password = $this->input->post('password');
		$user     = $this->User_model->get_user_by_reset_token($token);

		if (empty($user)) {
			$this->session->set_flashdata('reset_error', 'This reset link is invalid or has expired.');
			redirect('password/forgot');
			return;
		}

		$this->User_model->update_password($user->id, $password);
		$this->User_model->clear_reset_token($user->id);

		$data['content'] = 'password_reset_success_view';
		$this->load->view('home_view', $data);
	}

	private function send_reset_email($email_to, $token, $username)
	{
		$reset_url = base_url("password/reset/{$token}");

		$message = $this->load->view('emails/password_reset_template', [
			'username'  => html_escape($username),
			'reset_url' => $reset_url,
			'expiry'    => date('H:i', strtotime('+1 hour')),
		], TRUE);

		$this->email->from('no-reply@example.com', 'CodeIgniter App');
		$this->email->to($email_to);
		$this->email->subject('Password Reset Request');
		$this->email->message($message);

		return (bool) $this->email->send();
	}
}
