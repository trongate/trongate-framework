<h1><?= $headline ?></h1>
<?= validation_errors('<div class="error">', "</div>") ?>
<?php
echo form_open($form_location);
$input_attr['placeholder'] = 'Enter username here';
$input_attr['autocomplete'] = 'off';
echo form_label('Username');
echo form_input('username', $username, $input_attr);
$input_attr['placeholder'] = str_replace('username', 'password', $input_attr['placeholder']);
echo form_label('Password');
echo form_password('password', '', $input_attr);
$input_attr['placeholder'] = str_replace('Enter', 'Repeat', $input_attr['placeholder']);
echo form_label('Repeat Password');
echo form_password('repeat_password', '', $input_attr);
echo form_submit('submit', 'Submit');

if ((is_numeric(segment(3))) && (segment(3) !== $my_admin_id)) {
	$delete_attr['class'] = 'button danger float-right-lg';
	$delete_attr['onclick'] = 'confDelete()';
	echo form_button('submit', 'Delete', $delete_attr);
}

$cancel_btn_attr['class'] = 'alt';
echo form_submit('submit', 'Cancel', $cancel_btn_attr);
echo form_close();
?>
<script>
	function confDelete() {
		window.location.href = "<?= $conf_delete_url ?>";
	}
</script>