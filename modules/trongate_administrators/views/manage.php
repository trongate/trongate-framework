<h1>Manage Trongate Administrators</h1>
<?php
echo flashdata();
echo '<p>'.anchor('trongate_administrators/create', 'Create New Record', array('class' => 'button alt')).'</p>';
if (empty($rows)) {
    echo '<p>There are currently no records to display.</p>';
    return;
}
echo Modules::run('pagination/display', $pagination_data);
?>

<table class="records-table">
    <thead>
        <tr>
            <th colspan="4">
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
            <th>Username</th>
            <th>Trongate User ID</th>
            <th>Active</th>
            <th style="width: 20px;">Action</th>            
        </tr>
    </thead>
    <tbody>
        <?php 
        $attr['class'] = 'button alt';
        foreach($rows as $row) { ?>
        <tr>
            <td><?= out($row->username) ?></td>
            <td><?= out($row->trongate_user_id) ?></td>
            <td><?= out($row->active_formatted) ?></td>
            <td><?= anchor('trongate_administrators/show/'.$row->id, 'View', $attr) ?></td>        
        </tr>
        <?php
        }
        ?>
    </tbody>
</table>

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