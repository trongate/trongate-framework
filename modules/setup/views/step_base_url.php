<h1 class="text-center">Set Your Base URL</h1>
<p class="text-center mt-1">Your application's base URL has been detected. Save it in <code>config/config.php</code> and the installation is complete.</p>
<?= $this->draw_steps_indicator() ?>
<?= $this->draw_errors() ?>

<div class="instructions">
    <p><strong>1.</strong> Open this file:</p>
    <div class="long-path">
        <code>config/config.php</code>
    </div>

    <p><strong>2.</strong> Find this line:</p>
    <div class="code-wrapper">
        <code>define('BASE_URL', '****');</code>
    </div>

    <p><strong>3.</strong> Replace it with the line below (it must end with <code>/</code>):</p>

    <div class="copy-btn-wrapper">
        <button type="button" class="copy-btn" data-copy-target="base-url-code">
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

    <div class="code-wrapper">
        <code id="base-url-code"><?= out($this->get_base_url_code()) ?></code>
    </div>

    <p class="mt-2"><strong>4.</strong> Save the file, then click the button below.</p>
</div>

<form method="post" action="<?= out($this->get_wizard_url()) ?>">
    <?= $this->draw_token_field() ?>
    <button class="btn btn-primary" type="submit" name="action" value="confirm_base_url">Continue</button>
</form>

<p class="note">This is the last thing the setup writes. Until the line above is saved, the application counts as not installed and this wizard keeps appearing at whatever address you open. If <code><?= out($this->get_base_url()) ?></code> is not your application's URL, set the correct value yourself in <code>config/config.php</code>.</p>

<?= $this->draw_help_link() ?>
