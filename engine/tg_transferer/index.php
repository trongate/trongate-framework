<?php
require_once 'Transferer.php';
$transferer = new Transferer;
$current_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] .  $_SERVER['REQUEST_URI'];

if (REQUEST_TYPE == 'POST') {
    $transferer->process_post();
    die();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Trongate</title>
</head>

<body>

    <?php
    if (count($files) == 1) {
        $info = '<p>The following SQL file was found within the module directory:</p>';
    } else {
        $info = '<p>The following SQL files were found within the module directory:</p>';
    }

    $info .= '<ul>';
    foreach ($files as $file) {

        //display last segment only
        $bits = explode('/', $file);
        $target_file = $bits[count($bits) - 1];

        $filesize = filesize($file); // bytes
        $filesize = round($filesize / 1024, 2); // kilobytes with two digits
        $info .= '<li>* ' . $target_file . ' (' . $filesize . ' KB)';
        if ($filesize > 1000) {
            $info .= '<button class="danger" onclick="explainTooBig(\'' . $target_file . '\', \'' . $file . '\')">TOO BIG!</button></li>';
        } else {

            //check for dangerous SQL
            $file_contents = file_get_contents($file);
            $all_clear = $transferer->check_sql($file_contents);

            if ($all_clear == true) {
                $info .= '<button onclick="viewSql(\'' . $file . '\', false)">VIEW SQL</button></li>';
            } else {
                $info .= '<button class="warning" onclick="viewSql(\'' . $file . '\', true)">SUSPICIOUS!</button></li>';
            }
        }
    }
    $info .= '</ul>';
    ?>

    <h1 id="headline">SQL Files Found</h1>
    <div id="info"><?= $info ?></div>

    <style>
        body {
            font-size: 2em;
            background: #636ec6;
            color: #ddd;
            text-align: center;
            font-family: "Lucida Console", Monaco, monospace;
        }

        ul {
            list-style-type: none;
        }

        li {
            margin-bottom: 0.5em;
        }

        button {
            font-size: 0.7em;
            font-weight: bold;
            padding: 0.6em;
            margin-left: 1em;
            text-transform: uppercase;
        }

        h1 {
            margin-top: 2em;
        }

        h1,
        h2 {
            text-transform: uppercase;
        }

        textarea {
            width: 80%;
            margin: 0 auto;
            height: 50vh;
            font-size: 0.6em;
        }

        .warning {
            background: orange;
            color: white;
        }

        .danger {
            background: red;
            color: white;
        }

        .success {
            background: green;
            color: white;
        }

        p {
            max-width: 70vw;
            margin-bottom: 1em;
            margin-left: auto;
            margin-right: auto;
            line-height: 2em;
        }

        a:link {
            color: white;
        }

        @-webkit-keyframes blinker {
            from {
                opacity: 1.0;
            }

            to {
                opacity: 0.0;
            }
        }

        .blink {
            text-decoration: blink;
            -webkit-animation-name: blinker;
            -webkit-animation-duration: 0.5s;
            -webkit-animation-iteration-count: infinite;
            -webkit-animation-timing-function: ease-in-out;
            -webkit-animation-direction: alternate;
        }
    </style>

    <script type="text/javascript">
        var targetFile = '';
        var sqlCode = '';

        function viewSql(file, warning) {

            document.getElementById("headline").innerHTML = 'Reading SQL';
            document.getElementById("info").innerHTML = '';

            var params = {
                controllerPath: file,
                action: 'viewSql'
            }

            var http = new XMLHttpRequest()
            http.open('POST', '<?= $current_url ?>')
            http.setRequestHeader('Content-type', 'application/json')
            http.send(JSON.stringify(params)) // Make sure to stringify
            http.onload = function() {
                // Do whatever with response
                sqlCode = http.responseText;
                drawShowSQLPage(http.responseText, file, warning);
            }

        }

        function drawShowSQLPage(sql, file, warning) {

            targetFile = file;

            if (warning == true) {
                alert("Trongate detected some potentially dangerous code embedded within this SQL file.  Be extra careful!");
            }

            <?php
            $show_sql_content = '<p>The contents of the SQL file is displayed below:</p>';
            $show_sql_content .= '<p>';
            $show_sql_content .= '<a href="' . $current_url . '"><button>Go Back</button></a>';
            $show_sql_content .= '<button class="success" onclick="drawConfRun()">Run SQL</button>';
            $show_sql_content .= '<button class="danger" onclick="drawConfDelete()">Delete File</button>';
            $show_sql_content .= '</p>';
            $show_sql_content .= '<div><textarea id="sql-preview"></textarea></div>';
            ?>
            document.getElementById("headline").innerHTML = 'Displaying SQL';
            document.getElementById("info").innerHTML = '<?= $show_sql_content ?>';
            document.getElementById("sql-preview").innerHTML = sql;
        }

        function explainTooBig(target_file, filePath) {

            targetFile = filePath;

            <?php
            $page_content = '<p>For automatic database setup, Trongate has filesize limit of 1MB (1,000kb).</p>';
            $page_content .= '<button class="danger" onclick="deleteSqlFile()">Delete File</button>';
            $page_content .= '<a href="' . $current_url . '"><button>Go Back</button></a>';
            $page_content .= '</p>';
            $page_content .= '<div></div>';
            ?>

            document.getElementById("headline").innerHTML = 'SQL File Is Too Big!';
            document.getElementById("info").innerHTML = 'The file, ' + target_file + ', is too big. <?= $page_content ?>';
            document.getElementById("sql-preview").innerHTML = sql;
        }

        function drawConfDelete() {

            <?php
            $extra_conf_content = '<p>Are you sure?</p>';
            $extra_conf_content .= '<button class="danger" onclick="deleteSqlFile()">Delete File</button>';
            $extra_conf_content .= ' <a href="' . $current_url . '"><button>Cancel</button></a>';
            $extra_conf_content .= '</p>';
            ?>

            document.getElementById("headline").innerHTML = '<span class="danger">DELETE FILE</span>';
            document.getElementById("info").innerHTML = '<p>You are about to delete an SQL file.</p><p>Location: ' + targetFile + '</p><?= $extra_conf_content ?>';

        }

        function drawConfRun() {

            sqlCode = document.getElementById("sql-preview").value;

            <?php
            $run_conf_content = '<p>Are you sure?</p>';
            $run_conf_content .= '<button class="success" onclick="runSql()">I Understand The Risks - Execute the Sql</button>';
            $run_conf_content .= ' <button onclick="previewSql()">Preview SQL</button>';
            $run_conf_content .= ' <a href="' . $current_url . '"><button>Cancel</button></a>';
            $run_conf_content .= '</p>';
            $run_conf_content .= '<div><textarea id="sql-preview" style="display: none;" disabled></textarea></div>';
            ?>

            document.getElementById("headline").innerHTML = 'RUN SQL';
            document.getElementById("info").innerHTML = '<p>You are about to run the SQL file.</p><p>Location: ' + targetFile + '</p><?= $run_conf_content ?>';
        }

        function runSql() {
            document.getElementById("headline").innerHTML = 'PLEASE WAIT';
            document.getElementById("info").innerHTML = '<p class="blink">Executing SQL...</p>';

            <?php
            $finished_content = '<p><button class="success" onclick="clickOkay()">Okay</button></p>';
            ?>

            var params = {
                sqlCode,
                action: 'runSql',
                targetFile
            }

            var http = new XMLHttpRequest()
            http.open('POST', '<?= $current_url ?>')
            http.setRequestHeader('Content-type', 'application/json')
            http.send(JSON.stringify(params))

            http.onload = function() {

                var response = http.responseText;
                var status = http.status;

                if (status == 403) {
                    document.getElementById("headline").innerHTML = 'Finished';
                    response = response.replace('Finished.', '');
                    document.getElementById("info").innerHTML = '<p>Please delete the file, ' + response + '.</p>';
                    document.getElementById("info").innerHTML += '<p>After you have deleted the file, press \'Okay\'</p><?= $finished_content ?>';
                } else {

                    if (http.responseText == 'Finished.') {
                        document.getElementById("headline").innerHTML = 'Finished';
                        document.getElementById("info").innerHTML = '<p>The SQL file was successfully processed.</p><?= $finished_content ?>';
                    } else {

                        <?php
                        $error_content = '<p>Oh dear, there appears to be an error.</p>';
                        $error_content = '<p><a href="' . $current_url . '"><button>Go Back</button></a></p>';
                        $error_content .= '<p>The following response was generated by the SQL file:</p>';
                        $error_content .= '<p><textarea id="error-msg" style="height: 30vh; background-color: #ffe9e8;"></textarea></p>';
                        ?>

                        document.getElementById("headline").innerHTML = '<span class="danger">SQL ERROR</span>';
                        document.getElementById("info").innerHTML = '<?= $error_content ?>';
                        document.getElementById("error-msg").innerHTML = http.responseText;
                    }

                }

            }

        }

        function deleteSqlFile() {
            document.getElementById("headline").innerHTML = 'PLEASE WAIT';
            document.getElementById("info").innerHTML = '<p class="blink">Deleting SQL...</p>';

            var params = {
                targetFile,
                action: 'deleteFile'
            }

            var http = new XMLHttpRequest()
            http.open('POST', '<?= $current_url ?>')
            http.setRequestHeader('Content-type', 'application/json')
            http.send(JSON.stringify(params)) // Make sure to stringify
            http.onload = function() {

                if (http.responseText == 'Finished.') {
                    document.getElementById("headline").innerHTML = 'Finished';
                    document.getElementById("info").innerHTML = '<p>The SQL file was successfully deleted.</p><?= $finished_content ?>';
                } else {

                    <?php
                    $error_content = '<p>Oh dear, there appears to be an error.</p>';
                    $error_content = '<p><a href="' . $current_url . '"><button>Go Back</button></a></p>';
                    $error_content .= '<p>The following response was generated by file:</p>';
                    $error_content .= '<p><textarea id="error-msg" style="height: 30vh; background-color: #ffe9e8;"></textarea></p>';
                    ?>

                    document.getElementById("headline").innerHTML = '<span class="danger">SQL ERROR</span>';
                    document.getElementById("info").innerHTML = '<?= $error_content ?>';
                    document.getElementById("error-msg").innerHTML = http.responseText;
                }

            }
        }

        function previewSql() {
            document.getElementById("sql-preview").innerHTML = sqlCode;
            document.getElementById("sql-preview").style.display = 'block';
        }

        function clickOkay() {

            var params = {
                sampleFile: '<?= $files[0] ?>',
                action: 'getFinishUrl'
            }

            var http = new XMLHttpRequest()
            http.open('POST', '<?= $current_url ?>')
            http.setRequestHeader('Content-type', 'application/json')
            http.send(JSON.stringify(params)) // Make sure to stringify
            http.onload = function() {

                if (http.responseText == 'current_url') {
                    location.reload();
                } else {
                    window.location.href = '<?= BASE_URL ?>';
                }

            }

        }
    </script>
</body>

</html>