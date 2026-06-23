<div id="form-area">
    <div class="mt-1">
        <button mx-get="trongate_control-evo/run_gen" mx-target="main" mx-indicator="#loading" class="highlight">Generate New Module</button>
    </div>
    <div class="mt-1">
        <button onclick="window.parent.postMessage('reload_iframe:<?= BASE_URL ?>trongate_control-evo/module_details/web|1000|800', '*')">View Module Details</button>
    </div>
</div>
<div id="loading" class="mx-indicator" style="display: none;"><img src="trongate_control-evo_module/images/loader.svg" alt="loading"></div>
