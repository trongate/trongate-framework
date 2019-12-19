<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Trongate Admin</title>
        <link rel="stylesheet" href="<?= BASE_URL ?>trongate_administrators_module/css/normalize.css">
        <link rel="stylesheet" href="<?= BASE_URL ?>trongate_administrators_module/css/skeleton.css">
    </head>
    <body>
        
        <div class="container">
            <?= Template::display($data) ?>
        </div>
        <style>
        body {
            background: rgb(0,0,0);
            background: -webkit-linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
            background: -o-linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
            background: linear-gradient(rgba(0,0,0,1) 0%, rgba(51,51,51,1) 35%, rgba(111,111,111,1) 100%);
            background-repeat: no-repeat;
            background-size: cover;
            min-height: 100vh;
            color: #eee;
            font-family: "Lucida Sans Unicode", "Lucida Grande", sans-serif;
        }

        form {
            color: black;
        }

        form label {
            color: #eee;
        }

        .validation-error {
            background-color: red;
            color: white;
            margin: 1em 0;
            padding: 0.6em;
        }

        #logout {
            margin: 1em;
            text-align: right;
            position: relative;
        }

        #logout a {
            text-decoration: none;
            color: #fff;
        }
        #logout a:hover {
            text-decoration: underline;
        }
        
        </style>
    </body>
</html>