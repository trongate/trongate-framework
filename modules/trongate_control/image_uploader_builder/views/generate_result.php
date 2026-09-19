<div class="center-stage cloak">
    <div class="mt-1"><strong>Image Uploader Successfully Created</strong></div>

    <div class="mt-1">
        <button onclick="window.open('<?= out($module_url) ?>','_blank');setTimeout(function(){doReset();},1000)" class="success">View <?= out($module_label) ?> Module</button>
    </div>
    <div class="mt-1">
        <button onclick="doReset()">Okay</button>
    </div>
</div>