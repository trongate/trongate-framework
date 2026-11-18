<h1 class="text-center">Welcome to Your New Trongate Application</h1>
<p class="text-center mt-1">This wizard will guide you through the initial setup of your application.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<form method="post" action="">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="begin">Get Started</button>
</form>

<?= $this->draw_help_link() ?>
