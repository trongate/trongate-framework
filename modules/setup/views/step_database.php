<?php $defaults = $this->get_database_defaults(); ?>
<?php if ($this->is_database_file_stage()) { ?>

<h1 class="text-center">Database Configuration</h1>
<p class="text-center mt-1">The database server accepted those details. Now save them in <code>config/database.php</code>.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<?php if ($this->is_database_verified()) { ?>
<div class="alert alert-success">
    <p>A live connection to <strong><?= out($this->get_connected_database()) ?></strong> succeeded.</p>
</div>
<div class="text-center"><?= $this->draw_tick() ?></div>
<?php } else { ?>
<div class="alert alert-error">
    <p>Those details could not be used to connect to the database server on this request.</p>
</div>
<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="restart_database">Re-enter the details</button>
</form>
<?php } ?>

<div class="instructions">
    <p><strong>1.</strong> Open this file:</p>
    <div class="long-path">
        <code>config/database.php</code>
    </div>

    <p><strong>2.</strong> Replace its contents with the code below:</p>

    <div class="copy-btn-wrapper">
        <button type="button" class="copy-btn" data-copy-target="db-config-code">
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

    <pre class="code-wrapper"><code id="db-config-code"><?= out($this->get_database_config_code()) ?></code></pre>

    <p><strong>3.</strong> Save the file, then click the button below.</p>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="verify_database_file">Verify Configuration</button>
</form>

<?php } else { ?>

<h1 class="text-center">Database Connection</h1>
<p class="text-center mt-1">Enter the details of the database that your application will use.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <label for="host">Host</label>
    <input id="host" name="host" type="text" value="<?= out($defaults['host']) ?>" placeholder="e.g. 127.0.0.1" required="required">

    <label for="port">Port</label>
    <input id="port" name="port" type="text" value="<?= out($defaults['port']) ?>" placeholder="e.g. 3306">

    <label for="user">Username</label>
    <input id="user" name="user" type="text" value="<?= out($defaults['user']) ?>" autocomplete="off" required="required">

    <label for="password">Password</label>
    <input id="password" name="password" type="password" value="<?= out($defaults['password']) ?>" autocomplete="off">

    <label for="database">Database Name</label>
    <input id="database" name="database" type="text" value="<?= out($defaults['database']) ?>" placeholder="e.g. my_app" required="required">

    <p class="note"><strong>Note:</strong> the database will be created automatically if it does not exist.</p>

    <button class="btn btn-primary" type="submit" name="action" value="test_database">Test Connection &amp; Continue</button>
</form>

<?php } ?>

<?= $this->draw_help_link() ?>
