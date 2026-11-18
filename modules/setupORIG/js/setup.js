/**
 * Setup Module JavaScript
 *
 * Provides confetti animation for the setup completion page,
 * copy-to-clipboard functionality and email obfuscation.
 */

// ──────────────────────────────────────────────
// Confetti (shown on the "Congratulations!" page)
// ──────────────────────────────────────────────

/**
 * Orchestrate the full "Spin Cycle" celebration sequence:
 *
 *   1. Background flash overlay appears
 *   2. .setup-container spins rapidly (720°) while shrinking
 *   3. Confetti explodes outward from inside the container
 *   4. Container spins back, grows with an overshoot bounce
 *   5. Container pulses gently (celebrate-pulse)
 *   6. Confetti fades out
 *
 * Called via mx-after-swap when the setup completes.
 */
function startConfetti() {
    var container = document.querySelector('.setup-container');
    if (!container) return;

    // ── Step 1: Background flash overlay ──
    var flash = document.createElement('div');
    flash.className = 'bg-flash-overlay';
    document.body.appendChild(flash);
    // Force reflow so the flash class animation triggers
    flash.offsetHeight;
    flash.classList.add('flash');

    // Remove flash after its animation completes
    setTimeout(function() {
        if (flash.parentNode) flash.parentNode.removeChild(flash);
    }, 900);

    // ── Step 2: Spin the container ──
    container.classList.add('spin-cycle');

    // ── Step 3: Confetti bursts at the peak of the spin ──
    // The spin peaks around 50-70% of the animation (750-1050ms)
    // Fire confetti at 800ms so it explodes during the tight spin
    setTimeout(function() {
        fireConfetti(container);
    }, 800);

    // ── Step 4: Pulse after spin finishes ──
    // The spin-cycle animation lasts 1.5s
    setTimeout(function() {
        container.classList.remove('spin-cycle');
        container.classList.add('celebrate-pulse');
    }, 1550);

    // ── Step 5: Clean up pulse class ──
    setTimeout(function() {
        container.classList.remove('celebrate-pulse');
    }, 4600);
}

/**
 * Fire confetti pieces that explode outward from a given origin element.
 * Pieces start at the element's center and burst in all directions.
 *
 * @param {HTMLElement} origin - The element to burst confetti from.
 */
function fireConfetti(origin) {
    var canvas = document.getElementById('confetti-canvas');

    var ctx = canvas.getContext('2d');
    var W, H;

    function resize() {
        W = canvas.width = window.innerWidth;
        H = canvas.height = window.innerHeight;
    }
    resize();
    window.addEventListener('resize', resize);

    // Burst from the viewport centre so confetti covers the whole page
    var originX = W / 2;
    var originY = H / 2;

    var colors = ['#667eea', '#764ba2', '#f59e0b', '#10b981', '#ef4444', '#f97316', '#06b6d4', '#ec4899', '#FFD700', '#FF69B4'];
    var pieces = [];
    var duration = 4000;
    var start = Date.now();

    for (var i = 0; i < 250; i++) {
        var angle = Math.random() * Math.PI * 2;
        var speed = Math.random() * 12 + 6;
        pieces.push({
            x: originX,
            y: originY,
            w: Math.random() * 10 + 4,
            h: Math.random() * 6 + 3,
            color: colors[Math.floor(Math.random() * colors.length)],
            rot: Math.random() * 360,
            rotSpeed: (Math.random() - 0.5) * 10,
            velX: Math.cos(angle) * speed,
            velY: Math.sin(angle) * speed - 2,
            gravity: 0.12,
            opacity: 1,
        });
    }

    function frame() {
        var elapsed = Date.now() - start;
        if (elapsed >= duration) {
            ctx.clearRect(0, 0, W, H);
            return;
        }

        ctx.clearRect(0, 0, W, H);

        var fadeStart = duration - 1000;
        var fadeProgress = Math.max(0, (elapsed - fadeStart) / 1000);

        for (var i = 0; i < pieces.length; i++) {
            var p = pieces[i];
            p.x += p.velX;
            p.y += p.velY;
            p.velY += p.gravity;
            p.rot += p.rotSpeed;

            var opacity = 1 - fadeProgress;

            ctx.save();
            ctx.translate(p.x, p.y);
            ctx.rotate(p.rot * Math.PI / 180);
            ctx.globalAlpha = opacity;
            ctx.fillStyle = p.color;
            ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
            ctx.restore();
        }

        requestAnimationFrame(frame);
    }

    requestAnimationFrame(frame);
}

// ──────────────────────────────────────────────
// Help message: display obfuscated email (anti-scraping)
// ──────────────────────────────────────────────

/**
 * Fill in the obfuscated email address in the help message.
 *
 * Called via mx-after-swap from the "CLICK HERE" link.
 * The email is never present as a contiguous string in the HTML source,
 * making it harder for spam bots to scrape.
 */
function setupEmailDisplay() {
    var placeholder = document.getElementById('email-placeholder');
    if (placeholder) {
        placeholder.textContent = 'dave' + '@' + 'trongate' + '.' + 'io';
    }
}

// ──────────────────────────────────────────────

/**
 * Copy text to the clipboard and show "Copied!" state on the button.
 * Falls back to a textarea-based approach if the Clipboard API is unavailable.
 *
 * @param {HTMLElement} btn - The button element that was clicked.
 * @param {string}      text - The text to copy.
 */
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

/**
 * Update a button's appearance to show a "Copied!" confirmation state
 * for 2 seconds, then revert to its original content.
 *
 * @param {HTMLElement} btn - The button element to update.
 */
function showCopied(btn) {
    var original = btn.innerHTML;
    btn.innerHTML = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg> Copied!';
    btn.classList.add('copied');
    setTimeout(function() {
        btn.innerHTML = original;
        btn.classList.remove('copied');
    }, 2000);
}