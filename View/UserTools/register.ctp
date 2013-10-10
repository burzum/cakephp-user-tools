<?php
echo $this->Form->create($userModel);
echo $this->Form->input('username', array(
	'label' => __('Username')
));
echo $this->Form->input('email');
echo $this->Form->input('password');
echo $this->Form->input('confirm_password', array(
	'type' => 'password',
));
echo $this->Form->end(__('Sign up'));