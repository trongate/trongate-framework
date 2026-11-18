<h1 class="text-center">Create Admin Account</h1>
<p class="text-center mt-1">Set up the administrator account that you will use to sign in.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<form method="post" action="">
    <?= $this->draw_token_field() ?>
    <label for="username">Username</label>
    <input id="username" name="username" type="text" value="<?= out($this->get_admin_username()) ?>" placeholder="Enter username here" autocomplete="username" required="required">

    <label for="email">Email Address</label>
    <input id="email" name="email" type="email" value="<?= out($this->get_admin_email()) ?>" placeholder="Enter email address here" autocomplete="email" required="required">

    <label for="password">Password (min. 8 characters)</label>
    <input id="password" name="password" type="password" value="" placeholder="Create a strong password" autocomplete="new-password" required="required">

    <label for="confirm_password">Confirm Password</label>
    <input id="confirm_password" name="confirm_password" type="password" value="" placeholder="Repeat your password" autocomplete="new-password" required="required">

    <button class="btn btn-primary" type="submit" name="action" value="create_admin">Complete Setup</button>
</form>

<?= $this->draw_help_link() ?>
