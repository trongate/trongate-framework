<div class="page-content">
  <?= flashdata() ?>
  <?= validation_errors() ?>
  <?= $page_body ?>
</div>
<?= Modules::run('trongate_pages/_attempt_enable_page_edit', $data) ?>