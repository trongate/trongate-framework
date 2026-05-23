<div class="flo-btn-container">
    <div
        class="flo-flagship-div-trigger code-generator-trigger cloak"
        data-api-base-url="<?= $api_base_url ?>"
        aria-label="Open Flo"
        role="button"
        tabindex="0">
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

<script src="<?= BASE_URL ?>trongate_control_module/js/code-generator.js"></script>