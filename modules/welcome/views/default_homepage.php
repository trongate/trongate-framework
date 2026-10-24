<h1 class="mt-2">Enjoy Trongate!</h1>
<h2>The Native PHP Framework</h2>
<p class="mt-2">You have successfully installed Trongate. You're now ready to start building fast, efficient web applications.</p>

<div class="mt-3">
    <?php
    if (strtolower(ENV) === 'dev') {
        echo anchor('trongate_administrators/manage', 'Admin Panel', ['class' => 'button']);
    } else {
        echo anchor('https://trongateframework.com', 'Visit Trongateframework.com', ['class' => 'button', 'target' => '_blank']);
    }

    echo anchor('https://trongateframework.com/docs', 'View Documentation', ['class' => 'button alt', 'target' => '_blank']);
    ?>
</div>