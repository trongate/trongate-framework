<?php if ($this->is_fast_mode() === true) { ?>
<h1 class="text-center">You're All Set</h1>
<?php } else { ?>
<h1 class="text-center">Almost There</h1>
<?php } ?>
<?= $this->draw_steps_indicator() ?>

<?php if ($this->is_admin_verified()) { ?>
<div class="alert alert-success">
    <p>The administrator account <strong><?= out($this->get_admin_username()) ?></strong> is confirmed in the database.</p>
</div>
<div class="text-center"><?= $this->draw_tick() ?></div>

<div class="instructions">
<?php if ($this->is_module_deleted() === true) { ?>
    <p>The <code>setup</code> module has been removed, so this wizard will not appear again.</p>
    <p class="mt-2">Visit your application's homepage to begin.</p>
<?php } else { ?>
    <p><strong>1.</strong> Delete the <code>setup</code> module folder. You will find it here:</p>
    <div class="long-path">
        <code><?= out(APPPATH . 'modules/setup') ?></code>
    </div>
    <p class="mt-2"><strong>2.</strong> Then visit your application's homepage.</p>
    <p class="note">Until that folder has been deleted, this wizard will keep appearing in place of your application.</p>
<?php } ?>
</div>

<a class="btn" href="<?= out($this->get_base_url()) ?>" style="display: block; box-sizing: border-box; color: #fff; text-decoration: none;">Go to Your Homepage</a>

<?php } else { ?>
<div class="alert alert-error">
    <p>The administrator account could not be found in the database on this request.</p>
</div>

<div class="instructions">
    <p>There is nothing else this stage can do, so the wizard offers a way back rather than leaving you here. Starting again returns to the first stage and asks for the database details and the administrator account once more.</p>
    <p class="note">Nothing is deleted: your configuration files, your database and its tables are all left exactly as they are.</p>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="start_again">Start Again</button>
</form>
<?php } ?>

<?= $this->draw_help_link() ?>
