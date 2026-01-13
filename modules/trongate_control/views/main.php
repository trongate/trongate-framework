<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trongate Module Import Wizard</title>
    <link rel="stylesheet" href="trongate_control_module/css/transferer.css">
</head>
<body>
    <h1 id="headline">SQL Files Found</h1>
    <div id="info"><?= $file_list_html ?></div>
    
    <script>
        // Pass PHP variables to JavaScript
        const current_url = '<?= $current_url ?>';
        const base_url = '<?= $base_url ?>';
        const first_file = '<?= addslashes($first_file) ?>';
        const api_url = base_url + 'trongate_control/process';
    </script>
    <script src="trongate_control_module/js/transferer.js"></script>
</body>
</html>