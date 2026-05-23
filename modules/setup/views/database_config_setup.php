<h1 class="text-center">Database Configuration</h1>
<?php
$form_attr = [
    'class' => 'highlight-errors',
    'mx-post' => 'setup/submit_database_config_setup',
    'mx-target' => '.setup-container',
    'mx-indicator' => '.spinner',
    'mx-target-loading' => 'cloak',
    'mx-animate-success' => 'true',
    'mx-animate-error' => 'true'
];
echo form_open('#', $form_attr);
echo form_hidden('verification_trigger', $db_config_code);
?>

<p class="text-center">Let's make sure your database configuration is set up correctly.</p>

<?= Modules::run('setup/draw_steps_indicator', 2) ?>

<div class="instructions">
    <p><strong>1.</strong> Open this file:</p>
    <div class="long-path">
        <code><?= $db_config_path ?></code>
    </div>

    <p><strong>2.</strong> Replace its contents with the code below:</p>

    <div class="copy-btn-wrapper">
        <button
            type="button"
            class="copy-btn"
            onclick="copyCode(this, '<?= str_replace(["\\", "'", "\n"], ["\\\\", "\\'", "\\n"], $db_config_code) ?>')">
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

    <pre class="code-wrapper">
        <code><?= out($db_config_code) ?></code>
    </pre>

    <p><strong>3.</strong> Save the file, then click the button below.</p>
</div>

<?php
echo form_submit('submit', 'Verify Configuration', ['class' => 'btn btn-primary']);
echo form_close(); ?>

<?= Modules::run('setup/draw_help_link') ?>