<h1><?= $headline ?></h1>
<?= validation_errors() ?>
<div class="card">
    <div class="card-heading">
        Trongate Page Details
    </div>
    <div class="card-body">
        <?php
        echo form_open($form_location);
        echo form_label('Page Title');
        echo form_input('page_title', $page_title, array("placeholder" => "Enter Page Title"));
        echo form_label('Meta Keywords <span>(optional)</span>');
        echo form_textarea('meta_keywords', $meta_keywords, array("placeholder" => "Enter Meta Keywords"));
        echo form_label('Meta Description <span>(optional)</span>');
        echo form_textarea('meta_description', $meta_description, array("placeholder" => "Enter Meta Description"));
        echo form_label('Page Body <span>(optional)</span>');
        echo form_textarea('page_body', $page_body, array("placeholder" => "Enter Page Body"));
        echo form_label('Date Created <span>(optional)</span>');
        echo form_number('date_created', $date_created, array("placeholder" => "Enter Date Created"));
        echo form_label('Last Updated <span>(optional)</span>');
        echo form_number('last_updated', $last_updated, array("placeholder" => "Enter Last Updated"));
        echo '<div>';
        echo 'Published ';
        echo form_checkbox('published', 1, $checked=$published);
        echo '</div>';
        echo form_label('Created By <span>(optional)</span>');
        echo form_number('created_by', $created_by, array("placeholder" => "Enter Created By"));
        echo form_submit('submit', 'Submit');
        echo anchor($cancel_url, 'Cancel', array('class' => 'button alt'));
        echo form_close();
        ?>
    </div>
</div>