<h1 class="text-center">Update Default Module</h1>
<?php
$form_attr = [
    'class' => 'highlight-errors',
    'mx-post' => 'setup/submit_update_default_mod',
    'mx-target' => '.setup-container',
    'mx-indicator' => '.spinner',
    'mx-target-loading' => 'cloak',
    'mx-animate-success' => 'true',
    'mx-animate-error' => 'true',
    'mx-after-swap' => 'startConfetti'
];
echo form_open('#', $form_attr);
echo form_hidden('current_default_module', $current_default_module);
?>

<p class="text-center">Now, let's set your default module.</p>

<?= Modules::run('setup/draw_steps_indicator', 4) ?>

<div class="instructions">
    <p><strong>1.</strong> Open this file:</p>
    <div class="long-path">
        <code><?= $config_path ?></code>
    </div>

    <p class="mt-1"><strong>2.</strong> Find the line that sets DEFAULT_MODULE and replace it with:</p>

    <div class="copy-btn-wrapper">
        <button
            type="button"
            class="copy-btn"
            onclick="copyCode(this, '<?= str_replace("'", "\\'", $replacement_code) ?>')">
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
        <code><?= out($replacement_code) ?></code>
    </pre>

    <p class="mt-2"><strong>3.</strong> Save the file, then click the button below.</p>
</div>

<?php
echo form_submit('submit', 'Verify & Continue', ['class' => 'btn btn-primary']);
echo form_close(); ?>

<?= Modules::run('setup/draw_help_link') ?>