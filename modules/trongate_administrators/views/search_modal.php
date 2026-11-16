<?php
/**
 * Search modal form for filtering administrators.
 *
 * Loaded via MX into a modal when the Search button is clicked.
 */

$searchable_columns = [
    'username' => 'Username',
    'email' => 'Email Address',
];

$form_attr = ['id' => 'search-form'];

echo form_open('trongate_administrators/submit_search', $form_attr);
echo '<div class="form-group">';
echo form_label('Search Query');
echo form_input('search_query', '', ['placeholder' => 'Enter search term...', 'autocomplete' => 'off']);
echo '</div>';
echo '<div class="form-group">';
echo form_label('Search In');
echo form_dropdown('search_column', $searchable_columns);
echo '</div>';
echo form_close();
