<h1>Are You Sure?</h1>
<p>You are about to delete a system admin account.  This cannot be undone!</p>
<?php 
echo form_open($form_location);
$attr['class'] = 'danger';
echo form_submit('submit', 'Delete Record Now', $attr);
$attr['class'] = 'alt';
echo form_submit('submit', 'Cancel', $attr);
echo form_close();
?>

<style>
	h1, p, form {
		text-align: center;
	}
</style>