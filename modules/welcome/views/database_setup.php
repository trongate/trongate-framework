<!DOCTYPE html>
<html lang="en">
<head>
    <base href="<?= BASE_URL ?>">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="css/trongate.css">
    <title>Welcome to Trongate</title>
    <style>
        /* Code block styling */
        pre {
            background-color: #2d2d2d;
            color: #f8f8f2;
            padding: 1rem;
            border-radius: 6px;
            overflow-x: auto;
            margin: 1.5rem 0;
            font-size: 0.95rem;
            line-height: 1.4;
            position: relative;
        }
        code {
            font-family: "Courier New", Courier, monospace;
        }
        /* Inline code */
        p code, li code {
            background: #f4f4f4;
            color: #d6336c;
            padding: 2px 5px;
            border-radius: 3px;
            font-size: 0.9rem;
        }
        /* Copy button */
        .copy-btn {
            position: absolute;
            top: 8px;
            right: 8px;
            background: #007bff;
            color: #fff;
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .copy-btn:hover {
            background: #0056b3;
        }
        .copy-btn:active {
            background: #00408f;
        }
    </style>
</head>
<body>
    <main class="container">
        <h1>Database Setup (Optional)</h1>
        <p>
            Trongate gives you complete flexibility – you can build powerful web
            applications with or without a database. If your project requires storing
            or retrieving data, you can connect Trongate to a MySQL or MariaDB
            database. If you do not need a database, you can safely skip this step.
        </p>
        <hr>

        <h2>Step 1: Create a Database (Optional)</h2>
        <p>
            If your application will use a database, the first step is to create one.
            This can be done via your hosting control panel (such as cPanel or Plesk),
            phpMyAdmin, or directly from the MySQL/MariaDB command line. For example,
            in phpMyAdmin:
        </p>
        <ol>
            <li>Open <strong>phpMyAdmin</strong> from your hosting control panel.</li>
            <li>Click on the <strong>Databases</strong> tab.</li>
            <li>Enter a name for your new database (for example,
                <code>trongate_app</code>).</li>
            <li>Click <strong>Create</strong>.</li>
        </ol>
        <p>
            Make a note of your database name, username, and password if you create one.
        </p>
        <hr>

        <h2>Step 2: Locate the Database Configuration File</h2>
        <p>
            Database connection settings are stored inside
            <code>config/database.php</code>. Open this file in your editor and you
            will see sample settings like the following:
        </p>

        <pre><code class="language-php">&lt;?php
// Database settings
define('HOST', '127.0.0.1');
define('PORT', '3306');
define('USER', 'root');
define('PASSWORD', '');
define('DATABASE', '');
</code></pre>

        <hr>
        <h2>Step 3: Update the Settings</h2>
        <p>
            If you created a database, replace the sample values with your actual
            connection details:
        </p>
        <ul>
            <li><strong>HOST</strong> – usually <code>127.0.0.1</code> or
                <code>localhost</code>. Some hosts may provide a different server name.</li>
            <li><strong>PORT</strong> – usually <code>3306</code> unless your host
                specifies otherwise.</li>
            <li><strong>USER</strong> – your database username.</li>
            <li><strong>PASSWORD</strong> – your database password.</li>
            <li><strong>DATABASE</strong> – the name of your database.</li>
        </ul>
        <hr>

        <h2>Step 4: Save and Continue</h2>
        <p>
            After saving your changes, Trongate will attempt to connect to the database
            whenever it is needed. If you do not intend to use a database, simply leave
            the default values in place – Trongate will continue to run without issue.
        </p>
        <hr>

        <h2>Step 5: Setting Up Starter Tables</h2>
        <p>
            To get the most out of Trongate, it is advisable to create a few starter
            database tables. These tables provide essential functionality for things
            like user management, tokens, and administrator access. 
        </p>
        <p>
            Below you’ll find a set of SQL statements that will create the recommended
            starter tables. Simply copy the SQL code into your database management
            tool (such as phpMyAdmin, Adminer, or the MySQL command line) and execute it.
        </p>

        <pre id="sql-block"><code class="language-sql">CREATE TABLE `trongate_administrators` (
  `id` int(11) NOT NULL,
  `username` varchar(65) DEFAULT NULL,
  `password` varchar(60) DEFAULT NULL,
  `trongate_user_id` int(11) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `trongate_administrators` (`id`, `username`, `password`, `trongate_user_id`) VALUES
(1, 'admin', '$2y$11$SoHZDvbfLSRHAi3WiKIBiu.tAoi/GCBBO4HRxVX1I3qQkq3wCWfXi', 1);

CREATE TABLE `trongate_comments` (
  `id` int(11) NOT NULL,
  `comment` text DEFAULT NULL,
  `date_created` int(11) DEFAULT 0,
  `user_id` int(11) DEFAULT NULL,
  `target_table` varchar(125) DEFAULT NULL,
  `update_id` int(11) DEFAULT NULL,
  `code` varchar(6) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trongate_tokens` (
  `id` int(11) NOT NULL,
  `token` varchar(125) DEFAULT NULL,
  `user_id` int(11) DEFAULT 0,
  `expiry_date` int(11) DEFAULT NULL,
  `code` varchar(3) DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `trongate_users` (
  `id` int(11) NOT NULL,
  `code` varchar(32) DEFAULT NULL,
  `user_level_id` int(11) DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `trongate_users` (`id`, `code`, `user_level_id`) VALUES
(1, 'rSGf3dn6UeDZzJ9spCWCrTmxzp6zc5w6', 1);

CREATE TABLE `trongate_user_levels` (
  `id` int(11) NOT NULL,
  `level_title` varchar(125) DEFAULT ''
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

INSERT INTO `trongate_user_levels` (`id`, `level_title`) VALUES
(1, 'admin');

ALTER TABLE `trongate_administrators`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `trongate_comments`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `trongate_tokens`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `trongate_users`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `trongate_user_levels`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `trongate_administrators`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `trongate_comments`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `trongate_tokens`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `trongate_users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `trongate_user_levels`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
</code><button class="copy-btn" data-target="sql-block">Copy</button></pre>

        <p>
            Once executed, your database will contain the essential tables needed for
            user authentication, access levels, administrator accounts, tokens, and
            comments. These starter tables can be customised later to suit your
            project’s requirements.
        </p>
        <hr>

        <h2>Troubleshooting & Getting Help</h2>
        <ul>
            <li>Check your database name, username, and password for typos.</li>
            <li>Confirm your database user has permission to access the database.</li>
            <li>If you are using shared hosting, the database host may not be <code>localhost</code> – check your host’s documentation.</li>
            <li>If you do not plan to use a database, you can safely ignore connection errors.</li>
        </ul>

        <p>
            If you encounter issues or want to learn more about working with databases in Trongate, help is always available:
        </p>

        <p class="text-center mb-7">
            <a class="button" href="https://trongate.io/documentation/display/php_framework/database-operations/introducing-the-model-class" target="_blank">Database Documentation</a>
            <a class="button alt" href="https://trongate.io/forums" target="_blank">Discussion Forums</a>
        </p>

    </main>

    <script>
        document.querySelectorAll('.copy-btn').forEach(button => {
            button.addEventListener('click', () => {
                const targetId = button.getAttribute('data-target');
                const codeBlock = document.querySelector(`#${targetId} code`);
                const text = codeBlock.innerText;

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(text).then(() => {
                        button.textContent = 'Copied!';
                        setTimeout(() => button.textContent = 'Copy', 2000);
                    });
                } else {
                    // Fallback for older browsers
                    const textarea = document.createElement('textarea');
                    textarea.value = text;
                    document.body.appendChild(textarea);
                    textarea.select();
                    document.execCommand('copy');
                    document.body.removeChild(textarea);
                    button.textContent = 'Copied!';
                    setTimeout(() => button.textContent = 'Copy', 2000);
                }
            });
        });
    </script>
</body>
</html>