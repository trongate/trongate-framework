/**
 * Setup wizard JavaScript.
 *
 * Printed into the wizard's page by setup_template.php, so nothing here is
 * fetched over HTTP. Nothing here decides whether a test passed: the tick is
 * drawn by PHP, only after a live check has succeeded, and this file only
 * animates the markup it was given.
 */

// ──────────────────────────────────────────────
// Confetti and the "spin cycle" celebration
// ──────────────────────────────────────────────

/**
 * Should animations run at all?
 *
 * @returns {boolean} TRUE when the user has asked for reduced motion.
 */
function setupPrefersReducedMotion() {
    return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
}

/**
 * Orchestrate the celebration sequence:
 *
 *   1. Background flash overlay appears
 *   2. .setup-container spins while shrinking
 *   3. Confetti bursts outward
 *   4. Container spins back and pulses
 *   5. Confetti fades out
 *
 * Called once, by the wizard's final page, after the administrator account has
 * been confirmed in the database.
 */
function startConfetti() {
    var canvas = document.getElementById('confetti-canvas');

    if (!canvas) {
        return;
    }

    if (setupPrefersReducedMotion()) {
        return;
    }

    var container = document.querySelector('.setup-container');

    if (!container) {
        return;
    }

    // ── Step 1: background flash overlay ──
    var flash = document.createElement('div');
    flash.className = 'bg-flash-overlay';
    document.body.appendChild(flash);
    flash.offsetHeight; // force a reflow so the animation runs
    flash.classList.add('flash');

    setTimeout(function () {
        if (flash.parentNode) {
            flash.parentNode.removeChild(flash);
        }
    }, 900);

    // ── Step 2: spin the container ──
    container.classList.add('spin-cycle');

    // ── Step 3: confetti bursts at the peak of the spin ──
    setTimeout(function () {
        fireConfetti(canvas);
    }, 800);

    // ── Step 4: pulse once the spin has finished ──
    setTimeout(function () {
        container.classList.remove('spin-cycle');
        container.classList.add('celebrate-pulse');
    }, 1550);

    setTimeout(function () {
        container.classList.remove('celebrate-pulse');
    }, 4600);
}

/**
 * Burst confetti pieces outward from the centre of the viewport.
 *
 * @param {HTMLCanvasElement} canvas The full-page canvas to draw on.
 */
function fireConfetti(canvas) {
    var ctx = canvas.getContext('2d');
    var width;
    var height;

    function resize() {
        width = canvas.width = window.innerWidth;
        height = canvas.height = window.innerHeight;
    }

    resize();
    window.addEventListener('resize', resize);

    var colours = ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444', '#f97316', '#06b6d4', '#ec4899', '#FFD700', '#FF69B4'];
    var pieces = [];
    var duration = 4000;
    var start = Date.now();
    var origin_x = width / 2;
    var origin_y = height / 2;

    for (var i = 0; i < 250; i++) {
        var angle = Math.random() * Math.PI * 2;
        var speed = Math.random() * 12 + 6;

        pieces.push({
            x: origin_x,
            y: origin_y,
            w: Math.random() * 10 + 4,
            h: Math.random() * 6 + 3,
            colour: colours[Math.floor(Math.random() * colours.length)],
            rotation: Math.random() * 360,
            rotation_speed: (Math.random() - 0.5) * 10,
            velocity_x: Math.cos(angle) * speed,
            velocity_y: Math.sin(angle) * speed - 2,
            gravity: 0.12
        });
    }

    function frame() {
        var elapsed = Date.now() - start;

        if (elapsed >= duration) {
            ctx.clearRect(0, 0, width, height);
            return;
        }

        ctx.clearRect(0, 0, width, height);

        var fade_start = duration - 1000;
        var fade_progress = Math.max(0, (elapsed - fade_start) / 1000);

        for (var i = 0; i < pieces.length; i++) {
            var piece = pieces[i];
            piece.x += piece.velocity_x;
            piece.y += piece.velocity_y;
            piece.velocity_y += piece.gravity;
            piece.rotation += piece.rotation_speed;

            ctx.save();
            ctx.translate(piece.x, piece.y);
            ctx.rotate(piece.rotation * Math.PI / 180);
            ctx.globalAlpha = 1 - fade_progress;
            ctx.fillStyle = piece.colour;
            ctx.fillRect(-piece.w / 2, -piece.h / 2, piece.w, piece.h);
            ctx.restore();
        }

        requestAnimationFrame(frame);
    }

    requestAnimationFrame(frame);
}

// ──────────────────────────────────────────────
// Tick indicator
// ──────────────────────────────────────────────

/**
 * Start the tick's stroke animation on the next frame.
 *
 * The markup is only ever present when PHP has run a live check and it passed,
 * so this function never has to make a judgement of its own.
 */
function drawTicks() {
    var triggers = document.querySelectorAll('[data-draw-tick]');

    for (var i = 0; i < triggers.length; i++) {
        (function (trigger) {
            window.requestAnimationFrame(function () {
                window.requestAnimationFrame(function () {
                    trigger.classList.add('mx-drawn');
                });
            });
        })(triggers[i]);
    }
}

// ──────────────────────────────────────────────
// Copy to clipboard
// ──────────────────────────────────────────────

/**
 * Copy text to the clipboard, falling back to a textarea where the Clipboard
 * API is unavailable (for example, http on a LAN address).
 *
 * @param {string} text The text to copy.
 * @returns {Promise} Resolves when the copy has been attempted.
 */
function setupCopyText(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text).catch(function () {
            setupLegacyCopy(text);
        });
    }

    return Promise.resolve(setupLegacyCopy(text));
}

/**
 * Copy text using a temporary, invisible textarea.
 *
 * @param {string} text The text to copy.
 * @throws {Error} If the browser rejects the copy command.
 */
function setupLegacyCopy(text) {
    var textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.setAttribute('readonly', '');
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();

    var copied = document.execCommand('copy');
    document.body.removeChild(textarea);

    if (!copied) {
        throw new Error('The copy command was rejected.');
    }
}

/**
 * Show the "Copied!" state on a button, then put it back.
 *
 * @param {HTMLElement} button The button that was clicked.
 */
function setupShowCopied(button) {
    if (button.classList.contains('copied')) {
        return;
    }

    var original = button.innerHTML;
    button.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
    button.classList.add('copied');

    setTimeout(function () {
        button.innerHTML = original;
        button.classList.remove('copied');
    }, 2000);
}

// ──────────────────────────────────────────────
// Wiring
// ──────────────────────────────────────────────

document.addEventListener('DOMContentLoaded', function () {
    drawTicks();

    var help_link = document.getElementById('setup-help-link');

    if (help_link) {
        help_link.addEventListener('click', function (event) {
            event.preventDefault();

            var email = document.getElementById('setup-help-email');

            if (email && email.textContent === '') {
                // Assembled here so the address is not scrapable from the HTML.
                email.textContent = 'dave' + '@' + 'trongate' + '.' + 'io';
            }

            var message = document.getElementById('setup-help-message');

            if (message) {
                message.classList.remove('cloak');
            }
        });
    }
});

document.addEventListener('click', function (event) {
    var trigger = event.target.closest('[data-copy-target]');

    if (!trigger) {
        return;
    }

    var source = document.getElementById(trigger.dataset.copyTarget);

    if (!source) {
        return;
    }

    setupCopyText(source.textContent.trim())
        .then(function () {
            setupShowCopied(trigger);
        })
        .catch(function (error) {
            console.warn('Copy failed:', error);
        });
});
