<h1>Manage Trongate Administrators</h1>
<?= flashdata() ?>
<?php if (!empty($search_query)): ?>
    <p>Showing results for <strong><?= out($search_query) ?></strong></p>
<?php endif; ?>
<?php
echo '<p class="flex-row justify-between">';
echo anchor('trongate_administrators/create', 'Create New Administrator Record', ['class' => 'button alt']);
if ((count($rows) > 9) || (!empty($search_query))) {
    $btn_attr = [
        'class' => 'alt',
        'mx-get' => 'trongate_administrators/search_modal',
        'mx-build-modal' => json_encode([
            'id' => 'search-modal',
            'modalHeading' => 'Search Administrators',
            'modalFooter' => '<button class="alt" onclick="closeModal()">Cancel</button><button form="search-form">Search</button>'
        ])
    ];
    echo form_button('search_btn', 'Search <i class="tg tg-search"></i>', $btn_attr);
}
echo '</p>';
if (empty($rows)) {
    echo '<p>There are currently no records to display.</p>';
    return;
}
echo Modules::run('pagination/display', $pagination_data);
?>

<div class="table-container">
    <table class="records-table">
        <thead>
            <tr>
                <th colspan="7">
                    <div>
                        <div>&nbsp;</div>
                        <div>Records Per Page: <?php
                        $dropdown_attr['onchange'] = 'setPerPage()';
                        echo form_dropdown('per_page', $per_page_options, $selected_per_page, $dropdown_attr); 
                        ?></div>
                    </div>                    
                </th>
            </tr>
            <tr>
                <th class="text-left">Username</th>
                <th class="text-left">Email Address</th>
                <th class="text-left">Trongate User ID</th>
                <th class="text-left">Active</th>
                <th class="text-left">Failed Attempts</th>
                <th class="text-left">Login Blocked Until</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($rows as $row): 
                $link_attr = [
                    'mx-get' => 'trongate_administrators/show/'.$row->id,
                    'mx-select' => '.detail-grid',
                    'mx-build-modal' => json_encode([
                        'id' => 'record-preview-modal',
                        'width' => '640px',
                        'modalHeading' => 'Record Preview',
                        'modalFooter' => '<button class="alt" onclick="closeModal()">Cancel</button><button onclick="window.location.href=\''.BASE_URL.'trongate_administrators/show/'.$row->id.'\'">View Record</button>'
                    ])
                ];
                ?>
                <tr>
                    <td><?= anchor('#', out($row->username), $link_attr) ?></td>
                    <td><?= empty($row->email) ? '—' : out($row->email) ?></td>
                    <td><?= out($row->trongate_user_id) ?></td>
                    <td><?= out($row->active_formatted) ?></td>
                    <td><?= out($row->failed_login_attempts) ?></td>
                    <td><?= out($row->login_blocked_until_formatted) ?></td>
                    <td>
                        <div class="actions">
                            <a href="trongate_administrators/show/<?= $row->id ?>" class="button alt button-round"><i class="tg tg-eye"></i></a>
                            <a href="trongate_administrators/create/<?= $row->id ?>" class="button alt button-round"><i class="tg tg-pencil"></i></a>
                            <?php if ((int) $row->id !== $logged_in_user_id): ?>
                                <a href="trongate_administrators/delete_conf/<?= $row->id ?>" class="button alt button-round"><i class="tg tg-trash"></i></a>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php 
if(count($rows)>9) {
    unset($pagination_data['include_showing_statement']);
    echo Modules::run('pagination/display', $pagination_data);
}
?>

<script>
function setPerPage() {
    const selectedIndex = document.querySelector('select[name="per_page"]').value;
    window.location.href = '<?= BASE_URL ?>trongate_administrators/set_per_page/' + selectedIndex;
}
</script>
