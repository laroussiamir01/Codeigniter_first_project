<?php
defined('BASEPATH') || exit('No direct script access allowed');


/**
 *
 * Controller User
 *
 * This controller for ...
 *
 * @package   CodeIgniter
 * @category  Controller CI
 * @author    Setiawan Jodi <jodisetiawan@fisip-untirta.ac.id>
 * @author    Raul Guerrero <r.g.c@me.com>
 * @link      https://github.com/setdjod/myci-extension/
 * @param     ...
 * @return    ...
 *
 */
/**
 * @property User_model $User_model
 * @property CI_Form_validation $form_validation
 * @property CI_Session $session
 * @property CI_Input $input
 */

class User extends CI_Controller
{
    
  public function __construct()
  {
    parent::__construct();
  }

  public function index()
  {
    $data['users'] = $this->User_model->get_users();
    $this->load->view('users_view',$data);
  }
  public function display($user_id)
  {
    $data['users'] = $this->User_model->select_user($user_id);
    $this->load->view('users_view',$data);
  }
  public function insert(){
    $data= array(
      'username' => 'amir',
      'password' => 'amir123'
    );
    $this->User_model->insert_user($data);
  }
  public function update($user_id){
  $data= array(
    'username'=> 'amir',
    "password"=> 'amir1234',
  );
  $this->User_model->update_user($user_id,$data);
}
public function delete($user_id){
  $this->User_model->delete_user($user_id);
}
public function login(){
  $this->form_validation->set_rules('username','Username','trim|required|max_length[255]');
  $this->form_validation->set_rules('password','Password','trim|required|min_length[7]|max_length[20]');
  if($this->form_validation->run() == FALSE){
    $this->session->set_flashdata('error', validation_errors());
    redirect(base_url('home'));
  } else{
    $username = $this->input->post('username');
    $password = $this->input->post('password');
    $user = $this->User_model->get_by_username_or_email($username);

    if(empty($user)){
      $this->session->set_flashdata('login_failed', 'Username or password is incorrect');
      redirect(base_url('home'));
      return;
    }

    if($user->status === 'locked' && $this->User_model->is_account_locked($user->id)){
      $this->session->set_flashdata('login_locked', $this->locked_account_message($user->id));
      redirect(base_url('home'));
      return;
    }

    if($user->status === 'locked'){
      $this->User_model->reset_login_attempts($user->id);
      $user = $this->User_model->get_user($user->id);
    }

    if($user->status !== 'active'){
      $this->session->set_flashdata('login_failed', 'This account is not active. Please contact an administrator.');
      redirect(base_url('home'));
      return;
    }

    $user_id = $this->User_model->user_login($username,$password);

    if($user_id){
      $this->User_model->reset_login_attempts($user_id);
      $logged_user = $this->User_model->get_user($user_id);
      $this->session->set_userdata('pending_2fa', [
        'user_id'  => $user_id,
        'username' => $username,
        'role'     => $logged_user->role ?? NULL,
      ]);
      $this->session->set_flashdata('login_succed', 'Please complete two-factor verification.');
      redirect(base_url('two_factor/verify'));
    } else{
      $locked = $this->User_model->increment_login_attempts($user->id);

      if($locked){
        $user = $this->User_model->get_user($user->id);
        $this->send_account_locked_notifications($user, $this->input->ip_address());
        $this->session->set_flashdata('login_locked', $this->locked_account_message($user->id));
      } else{
        $remaining = max(0, $this->max_login_attempts() - $this->User_model->login_attempts($user->id));
        $attempt_word = $remaining === 1 ? 'attempt' : 'attempts';
        $this->session->set_flashdata('login_failed', 'Username or password is incorrect. ' . $remaining . ' ' . $attempt_word . ' remaining.');
      }

      redirect(base_url('home'));
    }
  }
}

private function max_login_attempts(){
  $max = (int) $this->config->item('account_lockout_max_attempts');
  return $max > 0 ? $max : 5;
}

private function locked_account_message($user_id){
  $seconds = $this->User_model->lockout_remaining_seconds($user_id);
  $minutes = max(1, (int) ceil($seconds / 60));
  $word = $minutes === 1 ? 'minute' : 'minutes';
  return 'This account is locked for security reasons. Try again in ' . $minutes . ' ' . $word . '.';
}

private function send_account_locked_notifications($user, $ip_address){
  $this->load->library('email');
  $locked_until = $this->User_model->locked_until($user->id);
  $from_email = $this->config->item('account_lockout_from_email') ?: 'no-reply@example.com';
  $from_name = $this->config->item('account_lockout_from_name') ?: 'CodeIgniter App';

  $user_message = $this->load->view('emails/account_locked_user_template', [
    'username'     => $user->username,
    'locked_until' => $locked_until,
    'ip_address'   => $ip_address,
  ], TRUE);

  $this->email->from($from_email, $from_name);
  $this->email->to($user->email);
  $this->email->subject('Account temporarily locked');
  $this->email->message($user_message);
  $this->email->send();

  $admin_email = $this->config->item('account_lockout_admin_email');
  if(! empty($admin_email)){
    $this->email->clear(TRUE);
    $admin_message = $this->load->view('emails/account_locked_admin_template', [
      'username'     => $user->username,
      'email'        => $user->email,
      'locked_until' => $locked_until,
      'ip_address'   => $ip_address,
    ], TRUE);

    $this->email->from($from_email, $from_name);
    $this->email->to($admin_email);
    $this->email->subject('Account locked notification');
    $this->email->message($admin_message);
    $this->email->send();
  }
}

public function logout(){
  $this->session->sess_destroy();
  redirect(base_url('home'));
}
public function register(){
  $this->form_validation->set_rules('username','Username','trim|required|min_length[3]|max_length[15]');
  $this->form_validation->set_rules('password','Repeat Password','trim|required|min_length[7]|max_length[20]');
  $this->form_validation->set_rules('password','Password','trim|required|min_length[7]|max_length[20]|matches[password]');
  $this->form_validation->set_rules('first_name','First Name','trim|required|min_length[3]|max_length[12]');
  $this->form_validation->set_rules('last_name','Last Name','trim|required|min_length[4]|max_length[12]');
  $this->form_validation->set_rules('email','Email','trim|required');
  $this->form_validation->set_rules('birthday','Birthday','required');
  
  if( $this->form_validation->run() == FALSE ){
    $data = array(
      'errors' => '<div class="bg-danger">'.validation_errors().'</div>'
    );
    $this->session->set_flashdata($data);
    $data['content'] = 'register_view';
    $this->load->view('home_view', $data);
  } else{
    $user_register = $this->User_model->register_user();

    if($user_register){
      $this->session->set_flashdata('register_succed','<div class="bg-success">You have been Successfully registred, you can now login</div>');
      redirect(base_url('home'));
    } else{
      redirect(base_url('user/register'));

    }
  }
}


}


/* End of file User.php */
/* Location: ./application/controllers/User.php */