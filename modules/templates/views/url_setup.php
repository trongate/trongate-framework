<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <link rel="stylesheet" href="setup_module/css/setup.css">
    <title>Setup — Application URL</title>
    <style>
        /* ── Page-specific overrides not covered by trongate.css or setup.css ── */
        .detected-label {
            font-size: 0.8rem;
            color: #059669;
            background: #ecfdf5;
            padding: 4px 10px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 12px;
        }

        .help-text {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 4px;
        }

        .code-wrapper {
            display: flex;
            align-items: flex-start;
            gap: 8px;
            margin-bottom: 16px;
        }

        .code-wrapper code {
            flex: 1;
            margin-bottom: 0;
            font-family: "SFMono-Regular", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.85rem;
        }

        .setup-container {
            max-width: 640px;
        }
    </style>
</head>
<body>
    <div class="setup-container">
      <h1 class="text-center">Welcome to Trongate</h1>
      <p class="text-center mt-1">
        To begin, confirm your application's base URL. This tells Trongate where your application is located on your server.
      </p>
    
      <div class="instructions">
        <p>
          The wizard has detected your base URL below.  Check that it’s correct, then click <strong>Continue</strong>.
        </p>
      </div>
    
      <form method="post" action="">
        <label for="base_url">Application BASE_URL</label>
        <span class="detected-label">⚡ Detected automatically</span>
    
        <input 
          id="base_url" 
          name="base_url" 
          type="text" 
          value="<?= current_url() ?>" 
          required="required"
        >
    
        <p class="help-text">
          Must end with a trailing slash (<code>/</code>).
        </p>
    
        <button 
          class="btn btn-primary"
          onclick="syncBaseUrlCode(); document.querySelector('.setup-container').classList.add('cloak'); document.querySelector('.setup-container-step-2').classList.remove('cloak'); return false;"
          name="submit" 
          type="submit" 
          value="Continue"
        >
          Continue
        </button>
    
      </form>
    </div>

    <div class="setup-container setup-container-step-2 cloak">
        <h1 class="text-center">Set Your Base URL</h1>
        <p class="text-center mt-1">Follow the instructions below to set your base URL.</p>

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
                <button
                    type="button"
                    class="copy-btn"
                    onclick="copyCode(this, 'define(\'BASE_URL\', \'' + document.getElementById('base_url').value + '\');')">
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
                <code id="base-url-code">define('BASE_URL', '<?= out($detected_url) ?>');</code>
            </div>

            <p class="mt-2"><strong>4.</strong> Save the file.</p>

            <p class="mt-2"><strong>5.</strong> After you've saved the file, click the button below.</p>
        </div>

        <p class="text-center">
            <a class="button btn btn-primary" href="<?= out($detected_url) ?>" style="display: block; box-sizing: border-box; text-decoration: none;">Continue</a>
        </p>
    </div>

    <script>
    function syncBaseUrlCode() {
        var url = document.getElementById('base_url').value;
        document.getElementById('base-url-code').textContent = "define('BASE_URL', '" + url + "');";
    }

    function copyCode(btn, text) {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function() {
                showCopied(btn);
            });
        } else {
            var textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();
            document.execCommand('copy');
            document.body.removeChild(textarea);
            showCopied(btn);
        }
    }

    function showCopied(btn) {
        var original = btn.innerHTML;
        btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
        btn.classList.add('copied');
        setTimeout(function() {
            btn.innerHTML = original;
            btn.classList.remove('copied');
        }, 2000);
    }
    </script>
</body>
</html>