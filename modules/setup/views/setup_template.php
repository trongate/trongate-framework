<?php
/**
 * The setup wizard's single page.
 *
 * Drawn by Setup::run(). Everything the page needs is inlined: the stylesheet
 * and the JavaScript are read from the module and printed into the document,
 * so the page needs no routing, no module asset serving and no HTTP requests
 * of its own.
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= out($this->get_base_url()) ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/trongate.css">
    <style><?php readfile(__DIR__ . '/../css/setup.css'); ?></style>
    <script><?php readfile(__DIR__ . '/../js/setup.js'); ?></script>
    <title>Setup</title>
</head>
<body>
    <canvas id="confetti-canvas"></canvas>

    <div class="setup-container">
<?php require __DIR__ . '/step_' . $this->get_view_name() . '.php'; ?>
    </div>
<?php if ($this->is_celebrating() === true) { ?>
    <script>startConfetti();</script>
<?php } ?>
</body>
</html>
