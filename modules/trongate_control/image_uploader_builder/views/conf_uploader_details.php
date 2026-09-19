<div class="container">
    <h1>Image Uploader Details</h1>
    <p>Your uploader details are displayed below. If required, you can modify these details after generation via the settings file.</p>

    <?php
    // Read-only summary — decided in the earlier "choose module" step,
    // plus the current value of every editable setting below.
    $module_label = ucwords(str_replace('_', ' ', $wizard['module'] ?? ''));

    $fields = [
        'column' => ['Column Name', 'The database column that stores the file name (default: picture).'],
        'table' => ['Table Name', 'The database table to alter (defaults to the module name).'],
        'destination' => ['Destination Folder', 'Module-relative storage root (default: uploads).'],
        'max_size' => ['Max Upload Size (KB)', 'Rejects larger files (default: 2000 KB).'],
        'max_width' => ['Max Source Width (px)', 'Rejects wider source images (default: 1200).'],
        'max_height' => ['Max Source Height (px)', 'Rejects taller source images (default: 1200).'],
        'resize_max_width' => ['Downscale-to Width (px)', 'Large images are shrunk to fit; never enlarged (default: 450).'],
        'resize_max_height' => ['Downscale-to Height (px)', 'Large images are shrunk to fit; never enlarged (default: 450).'],
        'thumbnail_max_width' => ['Thumbnail Max Width (px)', 'Used when thumbnails are enabled (default: 120).'],
        'thumbnail_max_height' => ['Thumbnail Max Height (px)', 'Used when thumbnails are enabled (default: 120).']
    ];

    $thumbs_on = ($wizard['thumbnails'] ?? true);

    $summary_rows = ['Module' => $module_label];
    foreach ($fields as $name => [$label, $hint]) {
        $summary_rows[$label] = $wizard[$name] ?? '';
    }
    $summary_rows['Generate Thumbnail'] = $thumbs_on ? 'Yes' : 'No';

    echo '<table class="iu-summary">';
    foreach ($summary_rows as $label => $value) {
        echo '<tr><td class="row-key"><strong>' . out($label) . '</strong></td><td>' . out($value) . '</td></tr>';
    }
    echo '</table>';

    echo '<div class="text-center form-buttons">';

    $attributes = [
      'class'   => 'close-btn alt',
      'onclick' => "window.parent.postMessage('reload_iframe:' + '{$after_close_url}' + '|' + '{$after_close_width}' + '|' + '{$after_close_height}', '*')"
    ];
    echo form_button('close-btn', 'Close Window', $attributes);

    echo '</div>';
    ?>
</div>

<style>
/* ==========================================================
   Table reset — undoes the global stylesheet's table rules.
   Selectors are prefixed with `body` and the :hover /
   :nth-child pseudo-classes are mirrored, so every override
   wins on specificity regardless of stylesheet load order.
   ========================================================== */

/* Undo: table { border-collapse: collapse; width: 100%; } */
body table {
  border-collapse: separate;   /* CSS initial value */
  width: auto;                 /* shrink-to-fit, browser default */
}

/* Undo: th { background-color: var(--primary); color: var(--primary-color); } */
body table th {
  background-color: transparent;
  color: inherit;
}

/* Undo: th, td { border: 1px var(--primary-darker) solid; padding: 0.7em; } */
body table th,
body table td {
  border: none;
  padding: 0;                  /* true UA default is 1px, if you want a pixel-faithful revert */
}

/* Undo: tr:nth-child(odd) { background-color: var(--row-odd-bg); }
         tr:hover, tr:nth-child(odd):hover { background-color: var(--row-hover-bg); }
   The pseudo-class variants MUST be restated: a pseudo-class adds
   specificity, so plain `tr { background: transparent }` can't beat them. */
body table tr,
body table tr:nth-child(odd),
body table tr:hover,
body table tr:nth-child(odd):hover {
  background-color: transparent;
}

/* Undo: td:hover { cursor: auto; }
   Nothing to do — `auto` is the initial value of `cursor`,
   so that rule was already a no-op. */
.iu-summary {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 1.5em;
}

.iu-summary td {
    border: 1px solid #ccc;
    padding: 8px 12px;
}

.iu-summary .row-key {
    width: 35%;
    background: #f4f4f4;
}

.form-buttons {
    margin-top: 2em;
    display: flex;
    flex-direction: row;
    align-items: center;
    justify-content: center;
}

.form-buttons button {
    margin-left: 1em;
}
</style>