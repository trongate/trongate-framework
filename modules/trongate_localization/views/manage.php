<h1><?= out($headline) ?></h1>
<?php
flashdata();
echo '</p>';
if (count($rows)>0) { ?>
    <table id="results-tbl">
        <thead>
        <tr>
            <th colspan="3">
                <div>
                    <div><?php
                        echo form_open('trongate_localization/manage', array("method" => "get"));
                        echo form_input('searchphrase', '', array("placeholder" => "Search records..."));
                        echo form_submit('submit', 'Search', array("class" => "alt"));
                        echo form_close();
                        ?></div>
                </div>
            </th>
        </tr>
        <tr>
            <th colspan="1"><?= $t('Key') ?></th>
            <th colspan="2"><?= $t('Value') ?></th>
        </tr>
        </thead>
        <tbody>
        <?php
        $attr['class'] = 'button alt';
        foreach($rows as $language => $set) { ?>
                <tr>
                    <th colspan="3"><?= $t("languages.$language") ?></th>
                </tr>
            <?php foreach($set as $key => $value): ?>
                <tr>
                    <td><?= out($key) ?></td>
                    <td>
                        <pre><?= is_array($value)
                                ? htmlspecialchars(
                                    json_encode($value, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT),
                                    ENT_NOQUOTES
                                )
                                : out($value)
                            ?></pre>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <?php } ?>
    </table>
<?php } ?>