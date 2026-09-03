<div id="form-area">
    <div class="mt-1">
        <button mx-get="trongate_control-module_relations_builder/run_gen" mx-target="main" mx-after-swap="TrongateCodeGenerator.focusOnInput" mx-indicator="#loading" class="highlight">Generate Relation</button>
    </div>
    <div class="mt-1">
        <button onclick="window.parent.postMessage('reload_iframe:<?= BASE_URL ?>trongate_control-module_relations_builder/relation_details/web|1000|800', '*')">View Relation Details</button>
    </div>
</div>
<div id="loading" class="mx-indicator" style="display: none;"><img src="trongate_control-evo_module/images/loader.svg" alt="loading"></div>
