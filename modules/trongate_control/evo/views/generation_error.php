<p class="error-msg"><?= $message ?></p>
<?php if (isset($more_info_url) && $more_info_url !== ''): ?>
<div class="mt-0">
    <button onclick="window.open('<?= $more_info_url ?>','_blank');setTimeout(function(){doReset();},1000)" class="learn-more">Learn More About This Error</button>
</div>
<?php endif; ?>
<div class="mt-1">
    <button onclick="doReset()">Okay</button>
</div>
