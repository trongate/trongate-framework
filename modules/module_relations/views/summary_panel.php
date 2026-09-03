<?php
// Filter and map keys to compliant HTTP custom headers (hyphens instead of underscores)
$mx_headers = [
    'X-Calling-Module'      => $calling_module ?? '',
    'X-Alt-Module'          => $alt_module ?? '',
    'X-Update-ID'           => $update_id ?? 0,
    'X-Relation-Name'       => $relation_name ?? '',
    'X-Associated-Singular' => $associated_singular ?? '',
    'X-Associated-Plural'   => $associated_plural ?? '',
    'X-Panel-ID'            => $summary_panel_id ?? '',
    'X-Csrf-Token'          => $csrf_token ?? ''
];
?>

<div class="card">
    <div class="card-heading"><?= $card_heading ?></div>
    <div class="card-body">
        <div class="spinner mx-indicator indicator-<?= $summary_panel_id ?>"></div>

        <div id="<?= $summary_panel_id ?>" 
            mx-get="module_relations/render_panel_body" 
            mx-headers='<?= json_encode($mx_headers) ?>'
            mx-trigger="load" 
            mx-indicator=".indicator-<?= $summary_panel_id ?>"></div>

    </div>
</div>