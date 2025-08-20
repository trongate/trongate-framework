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
  <main>
    <h1>Welcome to Trongate</h1>
    <h2>You’re all set — let’s build something extraordinary</h2>

    <p>
      <i>Congratulations!</i> You’ve successfully installed Trongate.  
      This is your default homepage. Trongate is built for lightning-fast performance and minimal dependencies, ensuring exceptional speed and rock-solid stability.
    </p>

    <div>
      <a class="btn" href="tg-admin">Admin Panel</a>
      <a class="btn" href="https://trongate.io/docs" target="_blank">Documentation</a>
      <a class="btn" href="https://trongate.io/help_bar" target="_blank">Forum</a>
    </div>

    <div class="text-left">
      <h2>Getting Started</h2>
      <p>
        To begin, log in to the <a href="tg-admin">Admin Panel</a>.  
        The default login credentials are:
      </p>
      <ul style="list-style-type:none; padding:0;">
        <li><b>Username:</b> admin</li>
        <li><b>Password:</b> admin</li>
      </ul>

      <h2>About Trongate</h2>
      <p>
        <a href="https://trongate.io/" target="_blank">Trongate</a> is an open-source PHP framework.  
        The GitHub repository can be found <a href="https://github.com/trongate/trongate-framework" target="_blank">here</a>.  
        Contributions are welcome! If you enjoy working with Trongate, please consider giving the project a ⭐ on GitHub.
      </p>
      <p>
        Need help? Visit our free <a href="https://trongate.io/help_bar" target="_blank">Help Bar</a> to get support from the community.
      </p>
    </div>

    <footer>
      <p>
        <a class="github-link" href="https://github.com/trongate/trongate-framework" target="_blank">
          ⭐ Please give Trongate a star on GitHub
        </a>
      </p>
      <p>&copy; <?= date('Y') ?> Your Company Name - Powered by Trongate</p>
    </footer>
  </main>
</body>
</html>
