<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 *
 * Model User_model
 *
 * This Model for ...
 * 
 * @package		CodeIgniter
 * @category	Model
 * @author    Setiawan Jodi <jodisetiawan@fisip-untirta.ac.id>
 * @link      https://github.com/setdjod/myci-extension/
 * @param     ...
 * @return    ...
 *
 */

class User_model extends CI_Model {

  // ------------------------------------------------------------------------

  public function __construct()
  {
    parent::__construct();
  }

  // ------------------------------------------------------------------------


  // ------------------------------------------------------------------------
  public function index()
  {
    // 
  }
  public function get_users()
  {
    $query= $this->db->get('users');
    return $query->result();
  }
  public function select_user($user_id)
  {
    $this->db->where('id', $user_id);
    $query= $this->db->get('users');
    return $query->result();
  }
  public function insert_user($data)
  {
    $query= $this->db->insert('users',$data);
  }
  public function update_user($user_id,$data){
    $this->db->where('id', $user_id);
    $query= $this->db->update('users',$data);

  }public function delete_user($user_id){
    $this->db->where('id', $user_id);
    $query= $this->db->delete('users');
  }
  public function get_by_username_or_email($identifier)
  {
    $this->db->where('username', $identifier);
    $this->db->or_where('email', $identifier);
    $query = $this->db->get('users');
    return $query->row();
  }

  public function get_user($user_id)
  {
    $this->db->where('id', $user_id);
    $query = $this->db->get('users');
    return $query->row();
  }

  public function user_login($username, $password)
  {
    $user = $this->get_by_username_or_email($username);

    if (empty($user) || empty($user->password)) {
      return FALSE;
    }

    if (password_verify($password, $user->password)) {
      return $user->id;
    }

    return FALSE;
  }

  public function increment_login_attempts($user_id)
  {
    $user = $this->get_user($user_id);
    if (empty($user)) {
      return FALSE;
    }

    $attempts = ((int) $user->login_attempts) + 1;
    $max_attempts = $this->max_login_attempts();
    $now = date('Y-m-d H:i:s');

    $this->db->where('id', $user_id);
    $this->db->update('users', [
      'login_attempts'        => $attempts,
      'last_login_attempt'    => $now,
      'last_login_attempt_ip' => $this->input->ip_address(),
    ]);

    if ($attempts >= $max_attempts) {
      $this->lock_account($user_id, $this->lockout_duration_minutes());
      return TRUE;
    }

    return FALSE;
  }

  public function lock_account($user_id, $duration_minutes)
  {
    $user = $this->get_user($user_id);
    if (empty($user)) {
      return FALSE;
    }

    $locked_until = date('Y-m-d H:i:s', strtotime("+{$duration_minutes} minutes"));

    $this->db->where('id', $user_id);
    $this->db->update('users', [
      'login_attempts'     => $this->max_login_attempts(),
      'locked_until'       => $locked_until,
      'status'             => 'locked',
      'last_login_attempt' => date('Y-m-d H:i:s'),
      'last_login_attempt_ip' => $this->input->ip_address(),
    ]);

    return $locked_until;
  }

  public function is_account_locked($user_id)
  {
    return $this->lockout_remaining_seconds($user_id) > 0;
  }

  public function lockout_remaining_seconds($user_id)
  {
    $user = $this->get_user($user_id);

    if (empty($user) || $user->status !== 'locked' || empty($user->locked_until)) {
      return 0;
    }

    $remaining = strtotime($user->locked_until) - time();
    return max(0, $remaining);
  }

  public function reset_login_attempts($user_id)
  {
    $this->db->where('id', $user_id);
    return $this->db->update('users', [
      'login_attempts'  => 0,
      'locked_until'    => NULL,
      'status'          => 'active',
    ]);
  }

  public function login_attempts($user_id)
  {
    $user = $this->get_user($user_id);
    return empty($user) ? 0 : (int) $user->login_attempts;
  }

  public function locked_until($user_id)
  {
    $user = $this->get_user($user_id);
    return empty($user) ? NULL : $user->locked_until;
  }

  public function unlock_account($user_id)
  {
    return $this->reset_login_attempts($user_id);
  }

  public function get_locked_accounts()
  {
    $this->db->where('status', 'locked');
    $this->db->where('locked_until >', date('Y-m-d H:i:s'));
    $this->db->order_by('locked_until', 'ASC');
    $query = $this->db->get('users');
    return $query->result();
  }

  private function max_login_attempts()
  {
    $max = (int) $this->config->item('account_lockout_max_attempts');
    return $max > 0 ? $max : 5;
  }

  private function lockout_duration_minutes()
  {
    $duration = (int) $this->config->item('account_lockout_duration_minutes');
    return $duration > 0 ? $duration : 30;
  }

  public function register_user(){
    $options= ['cost' => 12];
    $encrypted_password= password_hash($this->input->post('password'), PASSWORD_BCRYPT);
    $data = array(
      'username'=> $this->input->post('username'),
      'password'=> $encrypted_password,
     // 'password_repeat'=> $this->input->post('password_repeat'),
      'first_name'=> $this->input->post('first_name'),
      'last_name'=> $this->input->post('last_name'),
      'email'=> $this->input->post('email'),
      'birthday'=> $this->input->post('birthday'),
    );
    $user_register = $this->db->insert('users',$data);

    return $user_register;
  }

  public function get_by_email($email)
  {
    return $this->db->get_where('users', ['email' => $email])->row();
  }

  public function set_reset_token($user_id, $token)
  {
    $this->db->where('id', $user_id);
    return $this->db->update('users', [
      'password_reset_token'         => $token,
      'password_reset_expires'       => date('Y-m-d H:i:s', strtotime('+1 hour')),
      'password_reset_requested_at'  => date('Y-m-d H:i:s'),
    ]);
  }

  public function get_user_by_reset_token($token)
  {
    $this->db->where('password_reset_token', $token);
    $this->db->where('password_reset_expires >', date('Y-m-d H:i:s'));
    return $this->db->get('users')->row();
  }

  public function clear_reset_token($user_id)
  {
    $this->db->where('id', $user_id);
    return $this->db->update('users', [
      'password_reset_token'         => NULL,
      'password_reset_expires'       => NULL,
      'password_reset_requested_at'  => NULL,
    ]);
  }

  public function update_password($user_id, $password)
  {
    $hashed = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    $this->db->where('id', $user_id);
    return $this->db->update('users', ['password' => $hashed]);
  }

}

/* End of file User_model.php */
/* Location: ./application/models/User_model.php */