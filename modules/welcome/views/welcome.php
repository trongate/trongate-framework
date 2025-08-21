<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/trongate.css">
    <title>Welcome to Trongate</title>
</head>
<body>
    <main class="container">
        <h1 class="text-center">Ahoy!</h1>
        <h2>You’re all set - let’s build something extraordinary</h2>

        <h3>
            <i>Congratulations!</i> You’ve successfully installed Trongate.  
            This is your default homepage.
        </h3>

        <p>
            <a href="https://trongate.io/" target="_blank">Trongate</a> is an open-source PHP framework.  
            The GitHub repository can be found 
            <a href="https://github.com/trongate/trongate-framework" target="_blank">here</a>.  
            Contributions are welcome! If you enjoy working with Trongate, please consider giving the project a ⭐ on GitHub.
        </p>

        <p class="text-center mt-3">
            <a class="blink" href="https://github.com/trongate/trongate-framework" target="_blank">
                ⭐ Please give Trongate a star on GitHub
            </a>
        </p>

        <hr>

        <h2>Database Setup (Optional)</h2>
        <p>
            With Trongate, you can build powerful web applications with or without a database.  
            If your project requires storing or retrieving data, you can connect Trongate to a MySQL or MariaDB database.  
            If you do not need a database, you can safely skip this step.
        </p>

        <p class="text-center">
            <?= anchor('welcome/database_setup', 'Set Up Your Database', array('class' => 'button')) ?>
        </p>

        <hr>

        <h2>Need a Hand?</h2>
        <p>
            Whether you’re just getting started or you’re building something ambitious, help is always close at hand.  
            The official documentation covers everything you need to know about working with Trongate, and the community forums  
            are a great place to share ideas, ask questions, and get support from fellow developers.
        </p>

        <p class="text-center">
            <a class="button" href="https://trongate.io/documentation" target="_blank">Documentation</a>
            <a class="button alt" href="https://trongate.io/forums" target="_blank">Discussion Forums</a>
        </p>
    </main>
</body>
</html>