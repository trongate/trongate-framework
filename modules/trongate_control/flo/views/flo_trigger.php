<div class="flo-btn-container">
    <div mx-get="trongate_control-flo/home"
        mx-build-iframe='{
            "id": "terms-modal",
            "width": "800px",
            "height": "600px"
        }'
        class="flo-flagship-div-trigger"
        aria-label="Open Flo"
        role="button"
        tabindex="0"
        onclick="localStorage.removeItem('flo_wizard_state')">
        </div>
</div>

<style>
/* 1. The Wrapper (Same as before) */
.flo-btn-container {
    width: 100%;
    text-align: center;
    padding: 20px 0;
    margin-top: 12px;
}

/* 2. The Div-Based Trigger (Simplified CSS) */
.flo-flagship-div-trigger {
    /* Set to your exact required width */
    width: 162px;
    /* Height remains 66px to maintain the aspect ratio of your image */
    height: 51px;

    /* Center the graphic precisely within the element */
    display: inline-block; /* Makes it respect width/height and stay centered */

    /* 3. The Graphic */
    /* Ensure this path is correct relative to your stylesheet */
    background: url('trongate_control_module/images/flo_button.png') no-repeat center center;
    background-size: contain; /* Scales image down to fit, without clipping */

    /* 4. Overrides and Resets (MUCH CLEANER NOW THAT IT'S A DIV) */
    /* We no longer need 'border: none', 'background-color: transparent', 'outline: none' */
    border-radius: 12px; /* Smooth corners matching your image */
    cursor: pointer;    /* Vital! Replicates the native button hover state */

    /* 5. The Neon Glow Effect */
    /* Base glow */
    box-shadow: 0 0 12px rgba(16, 184, 255, 0.5);
    /* Neon Pulse Animation */
    animation: floPulse 3s infinite ease-in-out;
    /* Smooth scaling and glow change */
    transition: transform 0.2s, box-shadow 0.2s;
    border: 1px #4e85b5 solid;
    opacity: .5;
    transition: .3s;
}

/* Hover and Interaction States */
.flo-flagship-div-trigger:hover {
    transform: scale(1.04);
    box-shadow: 0 0 20px rgba(16, 184, 255, 0.8);
    opacity: 1;
}

.flo-flagship-div-trigger:active {
    transform: scale(0.96); /* Replicates native button depress on click */
}

/* Accessibility Focus State */
.flo-flagship-div-trigger:focus-visible {
    outline: 2px solid #ffffff; /* Shows a white outline for keyboard users */
    outline-offset: 4px;
}

/* Smooth Pulse Animation (Same as before) */
@keyframes floPulse {
    0% { box-shadow: 0 0 10px rgba(16, 184, 255, 0.4); }
    50% { box-shadow: 0 0 22px rgba(16, 184, 255, 0.8); }
    100% { box-shadow: 0 0 10px rgba(16, 184, 255, 0.4); }
}
</style>

<script>
window.addEventListener('message', function(e) {
    if (typeof e.data === 'string' && e.data.startsWith('open_url:')) {
        var parts = e.data.replace('open_url:', '').split('|');
        var url = parts[0];
        var target = parts[1] || '_blank';
        window.open(url, target);
    } else if (typeof e.data === 'string' && e.data.startsWith('reload_iframe:')) {
        // If a reload iframe overlay already exists, close it (toggle behaviour)
        var existingOverlay = document.getElementById('reload-iframe-overlay');
        if (existingOverlay) {
            existingOverlay.remove();
            return;
        }

        // Parse the URL and dimensions from the message
        var parts = e.data.replace('reload_iframe:', '').split('|');
        var url = parts[0];
        var width = parts[1] || '1000';
        var height = parts[2] || '800';

        var overlay = document.createElement('div');
        overlay.setAttribute('id', 'reload-iframe-overlay');
        overlay.style.cssText = 'display:block;position:fixed;z-index:1001;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);';

        var modalContent = document.createElement('div');
        modalContent.style.cssText = 'background-color:#fff;margin:0;padding:0;border:none;border-radius:12px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);box-shadow:0 8px 16px rgba(0,0,0,0.3);overflow:hidden;width:' + width + 'px;height:' + height + 'px;max-width:96vw;max-height:96vh;';

        var spinnerContainer = document.createElement('div');
        spinnerContainer.style.cssText = 'position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);z-index:10;';
        var spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinnerContainer.appendChild(spinner);

        var modalIframe = document.createElement('iframe');
        modalIframe.style.cssText = 'width:100%;height:100%;border:none;border-radius:12px;display:block;background-color:#000;';
        modalIframe.title = 'Flo Wizard';

        modalIframe.addEventListener('load', function() {
            spinnerContainer.remove();
        });

        modalIframe.src = url;

        modalContent.appendChild(spinnerContainer);
        modalContent.appendChild(modalIframe);
        overlay.appendChild(modalContent);

        overlay.addEventListener('click', function(event) {
            if (!modalContent.contains(event.target)) {
                overlay.remove();
            }
        });

        document.body.appendChild(overlay);
    } else if (e.data === 'reset') {
        // Reset the wizard and close the Flo modal
        localStorage.removeItem('flo_wizard_state');
        localStorage.removeItem('properties');
        if (typeof closeModal === 'function') {
            closeModal();
        }
        // Re-trigger Flo to open fresh
        var floTrigger = document.querySelector('.flo-flagship-div-trigger');
        if (floTrigger) {
            setTimeout(function() { floTrigger.click(); }, 100);
        }
    } else if (e.data === 'open_query_builder') {
        // Remove any existing QB modal first (prevents stacking)
        var existingOverlay = document.getElementById('qb-modal-overlay');
        if (existingOverlay) {
            existingOverlay.remove();
        }

        var baseUrl = document.querySelector('base').getAttribute('href');
        var qbUrl = baseUrl + 'trongate_control-query_builder/home';

        // Create overlay (same approach as code-generator.js)
        var overlay = document.createElement('div');
        overlay.setAttribute('id', 'qb-modal-overlay');
        overlay.style.cssText = 'display:block;position:fixed;z-index:1001;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);';

        // Create modal content (centered via top:50%/translate)
        var modalContent = document.createElement('div');
        modalContent.style.cssText = 'background-color:transparent;margin:0;padding:0;border:none;border-radius:12px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);box-shadow:0 8px 16px rgba(0,0,0,0.3);overflow:hidden;width:96vw;height:96vh;';

        // Create spinner (removed on iframe load, same as original)
        var spinnerContainer = document.createElement('div');
        spinnerContainer.style.cssText = 'position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);z-index:10;';
        var spinner = document.createElement('div');
        spinner.className = 'spinner';
        spinnerContainer.appendChild(spinner);

        // Create iframe
        var modalIframe = document.createElement('iframe');
        modalIframe.style.cssText = 'width:100%;height:100%;border:none;border-radius:12px;display:block;background-color:#000;';
        modalIframe.title = 'Query Builder';

        // Remove spinner when iframe loads
        modalIframe.addEventListener('load', function() {
            spinnerContainer.remove();
        });

        // Set src after attaching load handler
        modalIframe.src = qbUrl;

        // Assemble
        modalContent.appendChild(spinnerContainer);
        modalContent.appendChild(modalIframe);
        overlay.appendChild(modalContent);

        // Close on click outside modal content
        overlay.addEventListener('click', function(event) {
            if (!modalContent.contains(event.target)) {
                overlay.remove();
            }
        });

        // Close on Escape (one-shot, like original)
        document.addEventListener('keydown', function handler(event) {
            if (event.key === 'Escape') {
                overlay.remove();
                document.removeEventListener('keydown', handler);
            }
        });

        document.body.appendChild(overlay);
    } else if (e.data === 'close_query_builder') {
        var overlay = document.getElementById('qb-modal-overlay');
        if (overlay) {
            overlay.remove();
        }
    } else if (e.data === 'open_properties_builder') {
        // Close the Flo modal first, then open Properties Builder in parent context
        if (typeof closeModal === 'function') {
            closeModal();
        }
        window.openPropertiesBuilder();
    } else if (e.data === 'properties_submitted') {
        // Properties were submitted — close PB overlay
        var overlay = document.getElementById('pb-modal-overlay');
        if (overlay) {
            overlay.remove();
        }

        // Read properties from localStorage (PB's single source)
        var propertiesData = localStorage.getItem('properties');
        if (!propertiesData) {
            propertiesData = '[]';
        }

        // Convert localStorage → $_SESSION via AJAX
        var baseUrl = document.querySelector('base').getAttribute('href');
        var xhr = new XMLHttpRequest();
        xhr.open('POST', baseUrl + 'trongate_control-evo/store_properties', true);
        xhr.setRequestHeader('Content-Type', 'application/json');
        xhr.onload = function() {
            if (xhr.status === 200) {
                // Success — click trigger FIRST (clears stale flag via onclick),
                // then set the flag for the iframe's async load to pick up
                var floTrigger = document.querySelector('.flo-flagship-div-trigger');
                if (floTrigger) {
                    floTrigger.click();
                }
                localStorage.setItem('flo_wizard_state', 'choose_url_column');
            } else {
                alert('Failed to save properties. Please try again.');
            }
        };
        xhr.onerror = function() {
            alert('Network error. Please try again.');
        };
        xhr.send(propertiesData);
    } else if (e.data === 'close_properties_builder' || e.data === 'close') {
        // Close the Flo modal FIRST to prevent visual flash
        if (typeof closeModal === 'function') {
            closeModal();
        }
        var overlay = document.getElementById('pb-modal-overlay');
        if (overlay) {
            overlay.remove();
        }
    }
});

/* Open Properties Builder — exposed as a callable function so wizard
   steps can invoke it directly without relying on same-page postMessage. */
window.openPropertiesBuilder = function() {
    var existingOverlay = document.getElementById('pb-modal-overlay');
    if (existingOverlay) {
        existingOverlay.remove();
    }

    var baseUrl = document.querySelector('base').getAttribute('href');
    var pbUrl = baseUrl + 'trongate_control-properties_builder/web';

    var overlay = document.createElement('div');
    overlay.setAttribute('id', 'pb-modal-overlay');
    overlay.setAttribute('tabindex', '-1');
    overlay.style.cssText = 'display:block;position:fixed;z-index:1001;left:0;top:0;width:100%;height:100%;background-color:rgba(0,0,0,0.5);';

    var modalContent = document.createElement('div');
    modalContent.style.cssText = 'background-color:transparent;margin:0;padding:0;border:none;border-radius:12px;position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);box-shadow:0 8px 16px rgba(0,0,0,0.3);overflow:hidden;width:96vw;height:96vh;';

    var spinnerContainer = document.createElement('div');
    spinnerContainer.style.cssText = 'position:absolute;top:40%;left:50%;transform:translate(-50%,-50%);z-index:10;';
    var spinner = document.createElement('div');
    spinner.className = 'spinner';
    spinnerContainer.appendChild(spinner);

    var modalIframe = document.createElement('iframe');
    modalIframe.style.cssText = 'width:100%;height:100%;border:none;border-radius:12px;display:block;background-color:#000;';
    modalIframe.title = 'Properties Builder';

    modalIframe.addEventListener('load', function() {
        spinnerContainer.remove();
    });

    modalIframe.src = pbUrl;

    modalContent.appendChild(spinnerContainer);
    modalContent.appendChild(modalIframe);
    overlay.appendChild(modalContent);

    overlay.addEventListener('click', function(event) {
        if (!modalContent.contains(event.target)) {
            overlay.remove();
        }
    });

    document.body.appendChild(overlay);
    setTimeout(function() { overlay.focus(); }, 50);
};

/* Delegated click handler for flo-related buttons loaded dynamically by mx.js.
   Kept for compatibility — currently unused since iframe content uses parent.postMessage. */
document.body.addEventListener('click', function(e) {
    var btn = e.target.closest('[data-flo-trigger]');
    if (!btn) return;
    e.preventDefault();
    var action = btn.getAttribute('data-flo-action');
    if (action === 'open-properties-builder') {
        window.openPropertiesBuilder();
    }
});

/* Capture-phase Escape handler for Properties Builder.
   Fires before mx.js bubble handlers. When PB is open:
   - Removes the PB overlay
   - Closes the Flo modal
   - Stops propagation to prevent mx.js from also handling Escape */
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        var pbOverlay = document.getElementById('pb-modal-overlay');
        if (pbOverlay) {
            pbOverlay.remove();
            if (typeof closeModal === 'function') {
                closeModal();
            }
            e.stopPropagation();
        }
    }
}, true);
</script>


