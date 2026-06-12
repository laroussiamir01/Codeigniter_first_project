<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *
 * Controller Two_factor
 *
 * Handles the second step of login: deliver an OTP to the user's email,
 * verify the submitted code, and complete the session.
 *
 * Flow:
 *   1. User::login sets pending_user_id + pending_username in session and
 *      redirects here when password verification succeeds.
 *   2. verify() shows the form, checks the code against user_otp.
 *   3. resend() issues a new code, throttled to one per 30 seconds.
 *
 * @package   CodeIgniter
 * @category  Controller
 * @author    Amir Laroussi
 *
 * @property Two_factor_model $Two_factor_model
 * @property User_model       $User_model
 * @property CI_Session       $session
 * @property CI_Form_validation $form_validation
 * @property CI_Input         $input
 * @property CI_Email         $email
 */
class Two_factor extends CI_Controller
{
	private $resend_cooldown_seconds = 30;

	public function __construct()
	{
		parent::__construct();
		$this->load->model('Two_factor_model');
		$this->load->model('User_model');
		$this->load->library('session');
		$this->load->library('form_validation');
		$this->load->library('email');
		$this->load->helper('url');
	}

	public function verify()
	{
		$pending = $this->session->userdata('pending_2fa');
		if (empty($pending) || empty($pending['user_id'])) {
			redirect('home');
		}

		$user = $this->User_model->select_user($pending['user_id']);
		if (empty($user)) {
			$this->session->unset_userdata('pending_2fa');
			redirect('home');
		}

		$auto_issued = $this->maybe_issue_initial_code($pending['user_id'], $pending['username']);

		$this->form_validation->set_rules('otp', 'Verification code', 'trim|required|min_length[6]|max_length[6]|numeric');

		if ($this->form_validation->run() === FALSE) {
			$data = [
				'content'    => 'verify_otp_view',
				'username'   => $pending['username'],
				'cooldown'   => $this->resend_cooldown_seconds,
				'masked_to'  => $this->mask_email($this->get_user_email($pending['user_id'])),
				'auto_sent'  => $auto_issued,
			];
			$this->load->view('home_view', $data);
			return;
		}

		$code = $this->input->post('otp');
		$result = $this->Two_factor_model->verify($pending['user_id'], $code);

		if ($result === 'ok') {
			$this->session->set_userdata([
				'user_id'   => $pending['user_id'],
				'username'  => $pending['username'],
				'logged_in' => TRUE,
			]);
			$this->session->unset_userdata('pending_2fa');
			$this->session->set_flashdata('login_succed', "you're now logged in.");
			redirect('home');
			return;
		}

		$messages = [
			'mismatch' => 'That code did not match. Please try again.',
			'expired'  => 'That code has expired. Request a new one below.',
			'locked'   => 'Too many attempts. Please request a new code.',
			'no_code'  => 'No active code found. Please request a new one below.',
		];
		$this->session->set_flashdata('otp_error', $messages[$result] ?? 'Verification failed.');
		redirect('two_factor/verify');
	}

	public function resend()
	{
		$pending = $this->session->userdata('pending_2fa');
		if (empty($pending) || empty($pending['user_id'])) {
			redirect('home');
		}

		$age = $this->Two_factor_model->last_request_age_seconds($pending['user_id']);
		if ($age < $this->resend_cooldown_seconds) {
			$wait = $this->resend_cooldown_seconds - $age;
			$this->session->set_flashdata('otp_error', "Please wait {$wait} seconds before requesting a new code.");
			redirect('two_factor/verify');
			return;
		}

		$sent = $this->dispatch_otp($pending['user_id'], $pending['username']);
		if ($sent) {
			$this->session->set_flashdata('otp_info', 'A new code has been sent to your email.');
		} else {
			$this->session->set_flashdata('otp_error', 'Could not send the code. Please try again later.');
		}
		redirect('two_factor/verify');
	}

	private function maybe_issue_initial_code($user_id, $username)
	{
		$age = $this->Two_factor_model->last_request_age_seconds($user_id);
		if ($age < $this->resend_cooldown_seconds) {
			return FALSE;
		}
		return $this->dispatch_otp($user_id, $username);
	}

	private function dispatch_otp($user_id, $username)
	{
		$email_to = $this->get_user_email($user_id);
		if (empty($email_to)) {
			return FALSE;
		}

		$code = $this->Two_factor_model->issue_code($user_id);

		$this->email->from('no-reply@example.com', 'CodeIgniter App');
		$this->email->to($email_to);
		$this->email->subject('OTP: ' . $code);
		$this->email->message("<p>Hi " . html_escape($username) . ",</p>"
			. "<p>Your verification code is: <strong>{$code}</strong></p>"
			. "<p>This code expires in " . Two_factor_model::CODE_TTL_MINUTES . " minutes.</p>"
			. "<p>If you did not request this, you can ignore the email.</p>");

		return (bool) $this->email->send();
	}

	private function get_user_email($user_id)
	{
		$row = $this->User_model->select_user($user_id);
		if (empty($row) || ! is_array($row)) {
			return NULL;
		}
		$first = $row[0];
		return $first->email ?? NULL;
	}

	private function mask_email($email)
	{
		if (empty($email) || strpos($email, '@') === FALSE) {
			return '';
		}
		[$local, $domain] = explode('@', $email, 2);
		$visible = min(2, strlen($local));
		return substr($local, 0, $visible) . str_repeat('*', max(1, strlen($local) - $visible)) . '@' . $domain;
	}
}
