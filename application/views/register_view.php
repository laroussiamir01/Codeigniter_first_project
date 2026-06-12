<?php
echo '<h1> Registration Form </h1><br /><br />';
if($this->session->flashdata('errors')){
    echo $this->session->flashdata('errors');
}
echo '<div class="col-md-6 pull-left">';


?>

<?php

echo '<div class="form-login">';
$data= array(
    'class' => 'form-horizontal'
);
echo form_open('/user/register', $data);
   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'text',
        'name' => 'username',
        'placeholder' => 'Username'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'password',
        'name' => 'password',
        'placeholder' => 'Password'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'password',
        'name' => 'password_repeat',
        'placeholder' => 'Repeat Password'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'text',
        'name' => 'first_name',
        'placeholder' => 'First Name'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'text',
        'name' => 'last_name',
        'placeholder' => 'Last Name'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'email',
        'name' => 'email',
        'placeholder' => 'Email'
    );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'date',
        'name' => 'birthday'
            );
    echo form_input($data);
   echo '</div>';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control btn btn-primary',
        'type' => 'submit',
        'value' => 'Register'
    );
    echo form_input($data);
   echo '</div>';

echo form_close();
echo '</div>';


echo '</div>';
?>