<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="trongate_control-flo_module/css/flo.css">
    <script src="js/trongate-mx.min.js"></script>
    <title>Trongate</title>
</head>
<body>
    <div class="blue-frame">
        <header>*** Flo ***</header>
        <div class="spinner mt-4 mx-indicator" style="display: none;"></div>
        <main id="flo-main" mx-get="trongate_control-evo/home" mx-trigger="load" mx-indicator=".spinner"></main>
        <script>
            // Check if returning from Properties Builder — redirect to next step
            (function() {
                var state = localStorage.getItem('flo_wizard_state');
            if (state === 'choose_url_column') {
                var main = document.getElementById('flo-main');
                if (main) {
                    main.setAttribute('mx-get', 'trongate_control-evo/choose_url_column');
                }
            }
            if (state) {
                localStorage.removeItem('flo_wizard_state');
            }
            })();
        </script>
        <footer>
            <div>*</div>
            <div onclick="doReset()">Reset</div>
            <div>*</div>
            <div onclick="parent.postMessage('open_url:https://trongate.io/documentation|new_tab', '*')">Documentation</div>
            <div>*</div>
            <div onclick="parent.closeModal()">Close</div>
            <div>*</div>
        </footer>
    </div>
    <script src="trongate_control-flo_module/js/flo.js"></script>
</body>
</html>