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
  $this->form_validation->set_rules('username','Username','trim|required|min_length[3]|max_length[15]');
  $this->form_validation->set_rules('password','Password','trim|required|min_length[7]|max_length[20]');
  if($this->form_validation->run() == FALSE){
    $this->session->set_flashdata('error', validation_errors());
    redirect(base_url('home'));
  } else{
    $username = $this->input->post('username');
    $password = $this->input->post('password');

    $user_id= $this->User_model->user_login($username,$password);

    if($user_id){
      $data = array(
        'user_id' => $user_id,
        'username' => $username,
        'logged_in'=> true
      );
      $this ->session->set_userdata($data);
      $this->session->set_flashdata('login_succed','you\'re now logged in.');
      redirect(base_url('home'));
    } else{
      $this->session->set_flashdata('login_failed', 'Usernme or password is incorrect');
      redirect(base_url('home'));
    }
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