<div class="center-stage cloak">
    <div class="mt-1"><strong>Module Relation Successfully Created</strong></div>

    <div class="mt-1">
        <button onclick="window.open('<?= $module_a_manage_url ?>','_blank');setTimeout(function(){doReset();},1000)" class="success">View <?= out($parent_label) ?> Module</button>
    </div>
    <div class="mt-1">
        <button onclick="window.open('<?= $module_b_manage_url ?>','_blank');setTimeout(function(){doReset();},1000)">View <?= out($child_label) ?> Module</button>
    </div>
</div>
