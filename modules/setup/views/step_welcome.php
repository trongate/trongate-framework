<h1 class="text-center">Welcome to Trongate</h1>

<?php if ($this->can_install_automatically() === true) { ?>

<p class="lede">Everything is ready. Trongate will do the rest.</p>
<?= $this->draw_errors() ?>

<div class="instructions">
    <dl class="setup-facts">
        <dt>Your application</dt>
        <dd><code><?= out($this->get_base_url()) ?></code></dd>
        <dt>Database</dt>
        <dd><code><?= out($this->get_probe_database_name()) ?></code> on <?= out($this->get_probe_database_server()) ?></dd>
        <dt>What happens next</dt>
        <dd>Trongate saves these settings, creates the framework's tables, and then asks you for the administrator username, email and password of your choice.</dd>
    </dl>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="fast_install">Install Trongate</button>
</form>

<?php } elseif ($this->is_local() === false) { ?>

<p class="lede">This application is being opened from another machine, so Trongate will guide you through the setup one stage at a time.</p>
<?= $this->draw_errors() ?>

<div class="instructions">
    <p>Installations are only carried out without help when the request comes from the machine that is running PHP, because the wizard writes your configuration files and removes itself when it has finished.</p>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="begin">Continue With Setup</button>
</form>

<?php } elseif ($this->get_probe_command() !== '') { ?>

<p class="lede">One small thing first: your web server needs permission to finish the job.</p>
<?= $this->draw_errors() ?>

<div class="instructions">
    <p>The setup wizard can see that you are using <strong><?= out($this->get_probe_os()) ?></strong>. Copy the line below into your terminal and press Return. It lets your web server write two small configuration files and remove this setup folder once your application is installed &mdash; nothing else on your computer is affected.</p>

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

    <p class="poll-status" data-setup-poll="<?= out($this->get_status_url()) ?>">Waiting for that to be done&hellip; this page carries on by itself, so leave it open.</p>

<?php if ($this->is_container() === true) { ?>
    <p class="note">PHP appears to be running inside a container. The paths shown are inside that container, so the line may need to be run there rather than on your own machine.</p>
<?php } ?>
<?php if ($this->is_wsl() === true) { ?>
    <p class="note">PHP appears to be running through Windows Subsystem for Linux. If this application sits under <code>/mnt/</code>, changing its permissions has no effect: the Windows filesystem does not use them.</p>
<?php } ?>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="retry_probe">I Have Done That</button>
</form>

<?php } else { ?>

<p class="lede">One question, and then Trongate can take over: where does your database live?</p>
<?= $this->draw_errors() ?>

<div class="instructions">
<?= $this->draw_probe_reasons() ?>

    <form method="post" action="<?= out($this->get_wizard_url()) ?>">
        <?= $this->draw_token_field() ?>
        <label for="host">Host</label>
        <input id="host" name="host" type="text" value="<?= out($this->get_probe_database_host()) ?>" placeholder="e.g. 127.0.0.1" required="required">

        <label for="port">Port</label>
        <input id="port" name="port" type="text" value="<?= out($this->get_probe_database_port()) ?>" placeholder="e.g. 3306">

        <label for="user">Username</label>
        <input id="user" name="user" type="text" value="<?= out($this->get_probe_database_user()) ?>" autocomplete="off" required="required">

        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="off">

        <label for="database">Database Name</label>
        <input id="database" name="database" type="text" value="<?= out($this->get_offered_database_name()) ?>" placeholder="e.g. my_app" required="required">

        <p class="note">The database is created if it is not there yet. Anything already installed in it is never overwritten.</p>

        <button class="btn btn-primary" type="submit" name="action" value="fast_install">Install Trongate</button>
    </form>
</div>

<?php } ?>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn-link" type="submit" name="action" value="begin">I would rather set it up step by step</button>
</form>
