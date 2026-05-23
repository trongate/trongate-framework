<h1 class="text-center mb-1">Congratulations!</h1>
<?= Modules::run('setup/draw_steps_indicator', 5) ?>

<p class="text-center mt-1">You are ready to rock.</p>

<div class="instructions">
    <p><strong>1.</strong> Now, delete the <code style="display: inline;">setup</code> module folder. You'll find it here:</p>
    <div class="long-path">
        <code><?= APPPATH ?>modules/setup</code>
    </div>
    <p class="mt-2"><strong>2.</strong> After you've deleted the module, click the button below to go to your homepage.</p>
</div>

<div class="text-center mt-2">
    <a href="<?= BASE_URL ?>" class="button btn btn-primary">Go to Your Homepage</a>
</div>
<?= Modules::run('setup/draw_help_link') ?>