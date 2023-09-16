<div class="page-content">
<?php

declare(strict_types=1);

echo flashdata() ?>
<?php echo validation_errors() ?>
<?php echo $page_body ?>
</div>
<?php echo Modules::run('trongate_pages/_attempt_enable_page_edit', $data) ?>
