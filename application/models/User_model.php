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
  public function user_login($username,$password){
    $this->db->where(array(
      'username' => $username
    ));
      $result= $this->db->get('users');

      $db_password = $result->row(7)->password;
      if(password_verify($password,$db_password)){
      return $result->row(0)->id;
  } else{
    return false;
  }
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