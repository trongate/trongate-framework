<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="<?= BASE_URL ?>trongate_control-query_builder_module/css/table-joins.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>trongate_control-query_builder_module/css/query_builder.css">
    <title>Query Builder</title>
</head>
<body>

<style>
@font-face {
    font-family: 'VT323';
    src: url('<?= BASE_URL ?>trongate_control-query_builder_module/fonts/VT323-Regular.ttf') format('truetype');
}
html, body {
    height: 100%;
}
body {
    background-color: silver;
    font-family: 'VT323', monospace;
    font-size: 24px;
    color: #000;
    display: flex;
    flex-direction: column;
    justify-content: center;
    margin: 0;
    padding: 0;
}

/* Keep #main in flex flow so justify-content can centre it */
#main {
    position: relative !important;
    top: auto !important;
}

/* Overlay popups as fixed, not in flex flow */
#join-selector, #sql-code {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    transform: translate(-50%, -50%) !important;
    z-index: 9999 !important;
}

/* Position buttons at top via absolute; match original centre + 200px offset */
#buttons {
    position: absolute !important;
    top: 30px !important;
    left: calc(50% + 200px) !important;
    transform: translateX(-50%) !important;
    margin: 0 !important;
}

#loading-msg {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 28px;
    color: #333;
    text-align: center;
    z-index: 999;
}

.spinner {
    border: 6px solid #ccc;
    border-top: 6px solid #333;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
    margin: 20px auto;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<div id="loading-msg">
    <div>Loading tables...</div>
    <div class="spinner"></div>
</div>

<script>
var tables = [];

function setTables(tablesStr) {
    tables = JSON.parse(tablesStr);
    var loadingMsg = document.getElementById('loading-msg');
    if (loadingMsg) {
        loadingMsg.remove();
    }
    startQueryBuilder();
}

document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        window.parent.postMessage('close_query_builder', '*');
    }
});

document.addEventListener('DOMContentLoaded', function() {
    // Tables data was injected by PHP; kick off immediately.
    setTables('<?= str_replace("'", "\\'", $tables_json) ?>');
});
</script>
<script src="<?= BASE_URL ?>trongate_control-query_builder_module/js/query_builder.js"></script>

<!-- Preload join images -->
<div style="display: none;">
<?php
$dir1 = BASE_URL . 'trongate_control-query_builder_module/images/joins/active/';
$dir2 = BASE_URL . 'trongate_control-query_builder_module/images/joins/dark/';
for($i = 1; $i <= 7; $i++) {
    echo '<img src="' . $dir1 . 'join' . $i . '.png">';
    echo '<img src="' . $dir2 . 'join' . $i . '.png">';
}
?>
</div>
</body>
</html>
