<h1><?= $headline ?></h1>
<?= flashdata() ?>
<div class="card">
    <div class="card-heading">
        Administrator Details
    </div>
    <div class="card-body">
        <div class="text-right mb-3">
            <?= anchor($back_url, 'Back', array('class' => 'button alt')) ?>
            <?= anchor(BASE_URL.'trongate_administrators/create/'.$update_id, 'Edit', array('class' => 'button')) ?>
            <?= anchor($update_password_url, 'Update Password', array('class' => 'button alt')) ?>
            <?php if ($is_own_account === false): ?>
                <?= anchor('trongate_administrators/delete_conf/'.$update_id, 'Delete', array('class' => 'button danger')) ?>
            <?php endif; ?>
        </div>
        <div class="detail-grid">
            <div class="detail-row">
                <div class="detail-label">Username</div>
                <div class="detail-value"><?= out($username) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Email Address</div>
                <div class="detail-value"><?= empty($email) ? '—' : out($email) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Trongate User ID</div>
                <div class="detail-value"><?= out($trongate_user_id) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Status</div>
                <div class="detail-value"><?= out($active_formatted) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Failed Login Attempts</div>
                <div class="detail-value"><?= out($failed_login_attempts) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Last Failed Attempt</div>
                <div class="detail-value"><?= out($last_failed_attempt_formatted) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Login Blocked Until</div>
                <div class="detail-value"><?= out($login_blocked_until_formatted) ?></div>
            </div>
            <div class="detail-row">
                <div class="detail-label">Failed Login IP</div>
                <div class="detail-value"><?= empty($failed_login_ip) ? '—' : out($failed_login_ip) ?></div>
            </div>
        </div>
    </div>
</div>
