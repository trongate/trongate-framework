    <table class="records-table">
        <thead>
            <tr>
                <th colspan="<?= count($properties) + 1 ?>">
                    <div>
                        <div>&nbsp;</div>
                        <div>Records Per Page: &lt;?php
                        $dropdown_attr['onchange'] = 'setPerPage()';
                        echo form_dropdown('per_page', $per_page_options, $selected_per_page, $dropdown_attr); 
                        ?&gt;</div>
                    </div>                    
                </th>
            </tr>
            <tr>
                <?php
                foreach($properties as $property_index => $property) {
                    $indent = ($property_index === 0) ? '' : '            ';
                    echo $indent.'<th class="text-left">'.out($property->property_name).'</th>'.PHP_EOL;
                }
                ?>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            &lt;?php foreach($rows as $row): 
                $link_attr = [
                    'mx-get' => '<?= $module_folder_name ?>/show/'.$row->id,
                    'mx-select' => '.detail-grid',
                    'mx-build-modal' => json_encode([
                        'id' => 'record-preview-modal',
                        'width' => '640px',
                        'modalHeading' => 'Record Preview',
                        'modalFooter' => '<a href="<?= $module_folder_name ?>/show/'.$row->id.'" class="button alt mt-0 xs">View Details</a>'
                    ])
                ];
                ?&gt;
                <tr><?php
                    foreach($properties as $property_index => $property) {
                        echo PHP_EOL.'                ';
                        if ($property_index === 0) {
                            ?><td>&lt;?= anchor('#', out($row-><?= $property->form_field_name ?>), $link_attr) ?&gt;</td><?php
                        } else {
                            ?><td>&lt;?= out($row-><?= $property->form_field_name ?>) ?&gt;</td><?php
                        }
                    } ?>
                    <td>
                        <div class="actions">
                            <a href="<?= $module_folder_name ?>/show/&lt;?= $row->id ?&gt;" class="button alt button-round"><i class="tg tg-eye"></i></a>
                            <a href="<?= $module_folder_name ?>/create/&lt;?= $row->id ?&gt;" class="button alt button-round"><i class="tg tg-pencil"></i></a>
                            <a href="<?= $module_folder_name ?>/delete_conf/&lt;?= $row->id ?&gt;" class="button alt button-round"><i class="tg tg-trash"></i></a>
                        </div>
                    </td>
                </tr>
            &lt;?php endforeach; ?&gt;
        </tbody>
    </table>
