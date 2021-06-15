<h1 style="margin-top: 60px;">Login</h1>
<?php
echo validation_errors('<div class="error">', "</div>");
$attr['placeholder'] = 'Enter your username here';
$attr['autocomplete'] = 'off';
$btn_attr['class'] = 'alt';
echo form_open($form_location);
echo form_label('username');
echo form_input('username', $username, $attr);
echo form_label('password');
$attr['placeholder'] = str_replace('username', 'password',  $attr['placeholder']);
echo form_password('password', '', $attr);
echo form_label(form_checkbox('remember', 1).' remember me');
echo form_submit('submit', 'Submit');
echo form_submit('submit', 'Cancel', $btn_attr);
?>
<?php 
echo form_close();
?>
<style>
	body {
		display: flex;
		align-items: flex-start;
		justify-content:center;
		text-align: center;

	}

	label {
		text-align: left;
	}

	body > div.container > form {
		width: 100%;
		max-width: 460px;
		margin: 0 auto;
	}

	body > div.container > form > button {
		width: 100%;
	}

	.go-left {
		text-align: left;
	}
</style>