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



}

/* End of file User_model.php */
/* Location: ./application/models/User_model.php */