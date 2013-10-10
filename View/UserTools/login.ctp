<?php
echo $this->Form->create($userModel);
echo $this->Form->input('username', array(
	'label' => __('Username'),
	'required' => false,
));
echo $this->Form->input('password', array(
	'label' => __('Password'),
	'required' => false,
));
echo $this->Form->end(__('Login'));