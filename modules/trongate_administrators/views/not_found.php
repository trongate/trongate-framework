<h1><?= $headline ?></h1>
<div class="card">
    <div class="card-heading">
        <?= $headline ?>
    </div>
    <div class="card-body">
        <p><?= $message ?></p>
        <div class="text-center">
            <?= anchor($back_url, $back_label, array('class' => 'button alt')) ?>
        </div>
    </div>
</div>