<?php
echo '<h1> Login Form </h1><br /><br />';
echo '<div class="col-md-6 pull-left">';

if($this->session->flashdata('error')){
    echo $this->session->flashdata('error');
}
?>
<p class="bg-success">
    <?php
    if($this->session->flashdata('login_succed')){
        echo $this->session->flashdata('login_succed');
    }
     ?>
</p>
<p class="bg-danger">
    <?php
    if($this->session->flashdata('login_failed')){
        echo $this->session->flashdata('login_failed');
    }
     ?>
</p>
<?php

if(!$this->session->userdata('logged_in')):
echo '<div class="form-login">';
$data= array(
    'class' => 'form-horizontal'
);
echo form_open('/user/login', $data);
   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'text',
        'name' => 'username',
        'placeholder' => 'Username'
    );
    echo form_input($data);
   echo '</div';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control',
        'type' => 'password',
        'name' => 'password',
        'placeholder' => 'Password'
    );
    echo form_input($data);
   echo '</div';

   echo '<div class="form-group" >';
    $data = array(
        'class'=> 'form-control btn btn-primary',
        'type' => 'submit',
        'value' => 'Login'
    );
    echo form_input($data);
   echo '</div';

echo form_close();
   echo '<div class="mt-2 text-center">';
   echo '<a href="' . base_url('password/forgot') . '">Forgot Password?</a>';
   echo '</div>';
echo '</div>';
else:

    if($this->session->userdata('username')){
        echo 'You\'re logged in as '.$this->session->userdata('username').'';
    }
    $data = array(
        'class' => 'form_horizontal'
    );
    echo form_open('user/logout',$data);

     $data = array(
        'class'=> 'btn btn-primary',
        'value' => 'Logout'
    );

    echo form_submit($data);
    echo form_close();
endif;

echo '</div>';
?>