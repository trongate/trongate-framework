<h1 class="text-center">Welcome to Your New Trongate Application</h1>
<?php if ($this->can_install_automatically() === true) { ?>

<p class="text-center mt-1">Everything this application needs has been detected. Check the details below, then choose how you would like to continue.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<div class="instructions">
    <dl class="setup-facts">
        <dt>Application URL</dt>
        <dd><code><?= out($this->get_base_url()) ?></code></dd>
        <dt>Database</dt>
        <dd><code><?= out($this->get_probe_database_name()) ?></code> on <?= out($this->get_probe_database_server()) ?></dd>
        <dt>Permissions</dt>
        <dd>PHP can write the configuration files, and can remove this setup module when it has finished.</dd>
    </dl>
    <p class="note">The application URL and the database settings will be saved for you, the framework's tables will be created, and you will then be asked for an administrator account.</p>
</div>

<form method="post" action="">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="fast_install">Install Automatically</button>
    <button class="btn btn-quiet" type="submit" name="action" value="begin">No, take me through the steps</button>
</form>

<?php } else { ?>

<p class="text-center mt-1">This wizard will guide you through the initial setup of your application.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<div class="alert alert-error">
    <p>The automatic installation is not available on this machine:</p>
    <ul class="setup-reasons">
<?php foreach ($this->get_probe_reasons() as $reason) { ?>
        <li><?= out($reason) ?></li>
<?php } ?>
    </ul>
</div>

<?php if ($this->get_probe_command() !== '') { ?>
<div class="instructions">
    <p>The setup wizard can see that you are using <strong><?= out($this->get_probe_os()) ?></strong>. Open your terminal, run the command below to give PHP permission to write the configuration files and to remove this module when it has finished, then click <strong>Check Again</strong>.</p>

    <div class="copy-btn-wrapper">
        <button type="button" class="copy-btn" data-copy-target="permissions-command">
            <svg viewBox="0 0 24 24"
                fill="none"
                stroke="currentColor"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round">
                <rect x="9" y="9" width="13" height="13" rx="2" ry="2" />
                <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1" />
            </svg>
            Copy
        </button>
    </div>

    <textarea id="permissions-command" class="command-box" rows="2" readonly="readonly" spellcheck="false"><?= out($this->get_probe_command()) ?></textarea>

    <p class="note">This command is for local development only. The framework never runs it for you &mdash; whether to use it is entirely your choice, and you can ignore it and carry on with the steps instead.</p>
<?php if ($this->is_container() === true) { ?>
    <p class="note">PHP appears to be running inside a container. The path shown is inside that container, so this command may need to be run there rather than on your own machine.</p>
<?php } ?>
<?php if ($this->is_wsl() === true) { ?>
    <p class="note">PHP appears to be running through Windows Subsystem for Linux. If this application sits under <code>/mnt/</code>, changing its permissions has no effect: the Windows filesystem does not use them.</p>
<?php } ?>
</div>

<form method="post" action="">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="retry_probe">Check Again</button>
</form>
<?php } ?>

<?php $continue_class = ($this->get_probe_command() !== '') ? 'btn btn-quiet' : 'btn btn-primary'; ?>
<form method="post" action="">
    <?= $this->draw_token_field() ?>
    <button class="<?= $continue_class ?>" type="submit" name="action" value="begin">Continue With Setup</button>
</form>

<?php } ?>

<?= $this->draw_help_link() ?>
