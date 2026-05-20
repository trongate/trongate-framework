<h1>Manage <?= ucwords($record_name_plural) ?></h1>
&lt;?= flashdata() ?&gt;
<?php $has_searchable = $this->model->has_searchable_property($properties);
if ($has_searchable):
?>
&lt;?php if (!empty($search_query)): ?&gt;
    &lt;p&gt;Showing results for &lt;strong&gt;&lt;?= out($search_query) ?&gt;&lt;/strong&gt;&lt;/p&gt;
&lt;?php endif; ?&gt;
<?php endif; ?>
&lt;?php
echo '<p class="flex-row justify-between">';
echo anchor('<?= $module_folder_name ?>/create', 'Create New <?= ucwords($record_name_singular) ?> Record', ['class' => 'button alt']);
<?php if ($has_searchable): ?>
if ((count($rows) > 9) || (!empty($search_query))) {
    $btn_attr = [
        'class' => 'alt',
        'mx-get' => '<?= $module_folder_name ?>/search_modal',
        'mx-build-modal' => json_encode([
            'id' => 'search-modal',
            'modalHeading' => 'Search <?= ucwords($record_name_plural) ?>',
            'modalFooter' => '<button class="alt" onclick="closeModal()">Cancel</button><button form="search-form">Search</button>'
        ])
    ];
    echo form_button('search_btn', 'Search <i class="tg tg-search"></i>', $btn_attr);
}
<?php endif; ?>
echo '</p>';
if (empty($rows)) {
    echo '<p>There are currently no records to display.</p>';
    return;
}
echo Modules::run('pagination/display', $pagination_data);
?&gt;

<div class="table-container">
<?= $dynamic_table ?>
</div>

&lt;?php 
if(count($rows)>9) {
    unset($pagination_data['include_showing_statement']);
    echo Modules::run('pagination/display', $pagination_data);
}
?&gt;

<script>
function setPerPage() {
    const selectedIndex = document.querySelector('select[name="per_page"]').value;
    window.location.href = '&lt;?= BASE_URL ?&gt;<?= $module_folder_name ?>/set_per_page/' + selectedIndex;
}
</script>
