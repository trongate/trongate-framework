<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link rel="stylesheet" href="css/trongate.css">
	<link rel="stylesheet" href="setup_module/css/setup.css">
	<script src="js/trongate-mx.min.js"></script>
	<title>Setup</title>
</head>
<body>
    <canvas id="confetti-canvas"></canvas>
    <div class="spinner loading-spinner mx-indicator"></div>
    <div class="setup-container">
        <h1 class="text-center">Welcome to Your New Trongate Application</h1>
        <p class="text-center mt-1">This wizard will guide you through the initial setup of your application.</p>
        <?= Modules::run('setup/draw_steps_indicator', 1) ?>

        <button  class="btn btn-primary" 
                    mx-get="setup/database_config" 
                    mx-target=".setup-container" 
                    mx-indicator=".spinner" 
                    mx-target-loading="cloak">Get Started</button>
        <?= Modules::run('setup/draw_help_link') ?>
    </div>
    <script src="setup_module/js/setup.js"></script>
</body>
</html>