<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= htmlspecialchars($base_url, ENT_QUOTES) ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <style><?php readfile(APPPATH . 'modules/setup/css/setup.css'); ?></style>
    <title>Setup</title>
</head>
<body>
    <canvas id="confetti-canvas"></canvas>
    <div class="spinner loading-spinner mx-indicator"></div>

    <div class="setup-container" id="setup-container">
        <h1 class="text-center">Welcome to Your New Trongate Application</h1>
        <p class="text-center mt-1">This wizard will guide you through the initial setup of your application.</p>
<?php $this->draw_steps_indicator(1); ?>
        <button type="button" class="btn btn-primary" data-reveal="setup-base-url">Get Started</button>
    </div>

    <template id="setup-base-url">
        <h1 class="text-center">Set Your Base URL</h1>
        <p class="text-center mt-1">Follow the instructions below to set your base URL.</p>
        <?php $this->draw_steps_indicator(2); ?>

        <div class="instructions">
            <p><strong>1.</strong> Open this file:</p>
            <div class="long-path">
                <code>config/config.php</code>
            </div>

            <p><strong>2.</strong> Find this line:</p>
            <div class="code-wrapper">
                <code>define('BASE_URL', '****');</code>
            </div>

            <p><strong>3.</strong> Replace it with the line below (must end with <code>/</code>):</p>

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
                <code id="base-url-code">define('BASE_URL', '<?= out($base_url) ?>');</code>
            </div>

            <p class="mt-2"><strong>4.</strong> Save the file.</p>

            <p class="mt-2"><strong>5.</strong> After you've saved the file, click the button below.</p>
        </div>

        <p class="text-center">
            <a class="button btn btn-primary" href="<?= out($base_url) ?>" style="display: block; box-sizing: border-box; text-decoration: none;">Continue</a>
        </p>
    </template>

<script>
(function () {
    const container = document.getElementById('setup-container');

    function reveal(templateId) {
        const template = document.getElementById(templateId);

        if (!(template instanceof HTMLTemplateElement)) {
            console.warn(`Setup step "${templateId}" not found.`);
            return;
        }

        container.replaceChildren(template.content.cloneNode(true));
    }

    function copyText(text) {
        if (navigator.clipboard && window.isSecureContext) {
            return navigator.clipboard.writeText(text).catch(() => legacyCopy(text));
        }
        return Promise.resolve(legacyCopy(text));
    }

    // Fallback for non-secure contexts (e.g. http on a LAN address).
    function legacyCopy(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        if (!ok) {
            throw new Error('Copy command was rejected.');
        }
    }

    function showCopied(btn) {
        if (btn.classList.contains('copied')) {
            return; // already showing "Copied!"; don't capture it as the original
        }

        const original = btn.innerHTML;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
        btn.classList.add('copied');

        setTimeout(() => {
            btn.innerHTML = original;
            btn.classList.remove('copied');
        }, 2000);
    }

    // One delegated listener handles every step.
    document.addEventListener('click', (event) => {
        const revealTrigger = event.target.closest('[data-reveal]');
        if (revealTrigger) {
            reveal(revealTrigger.dataset.reveal);
            return;
        }

        const copyTrigger = event.target.closest('[data-copy-target]');
        if (copyTrigger) {
            const source = document.getElementById(copyTrigger.dataset.copyTarget);
            if (!source) {
                console.warn(`Copy source "${copyTrigger.dataset.copyTarget}" not found.`);
                return;
            }

            copyText(source.textContent.trim())
                .then(() => showCopied(copyTrigger))
                .catch((err) => console.warn('Copy failed:', err));
        }
    });
})();
</script>
</body>
</html>