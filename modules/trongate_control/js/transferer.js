// State management
let target_file = '';
let sql_code = '';

/**
 * View SQL file contents
 */
function view_sql(file, has_warning) {
    update_display('Reading SQL', '');
    
    const params = {
        controllerPath: file,
        action: 'viewSql'
    };
    
    send_request(params, function(response) {
        sql_code = response;
        draw_show_sql_page(response, file, has_warning);
    });
}

/**
 * Display SQL file preview page
 */
function draw_show_sql_page(sql, file, has_warning) {
    target_file = file;
    
    if (has_warning === true) {
        alert("Trongate detected some potentially dangerous code embedded within this SQL file. Be extra careful!");
    }
    
    let html = '<p>The contents of the SQL file is displayed below:</p>';
    html += '<p>';
    html += '<a href="' + current_url + '"><button>Go Back</button></a>';
    html += '<button class="success" onclick="draw_confirm_run()">Run SQL</button>';
    html += '<button class="danger" onclick="draw_confirm_delete()">Delete File</button>';
    html += '</p>';
    html += '<div><textarea id="sql-preview"></textarea></div>';
    
    update_display('Displaying SQL', html);
    document.getElementById('sql-preview').innerHTML = sql;
}

/**
 * Explain file is too big
 */
function explain_too_big(filename, filepath) {
    target_file = filepath;
    
    let html = 'The file, ' + filename + ', is too big. ';
    html += '<p>For automatic database setup, Trongate has filesize limit of 1MB (1,000kb).</p>';
    html += '<button class="danger" onclick="delete_sql_file()">Delete File</button>';
    html += '<a href="' + current_url + '"><button>Go Back</button></a>';
    html += '</p>';
    
    update_display('SQL File Is Too Big!', html);
}

/**
 * Show delete confirmation
 */
function draw_confirm_delete() {
    let html = '<p>You are about to delete an SQL file.</p>';
    html += '<p>Location: ' + target_file + '</p>';
    html += '<p>Are you sure?</p>';
    html += '<button class="danger" onclick="delete_sql_file()">Delete File</button>';
    html += ' <a href="' + current_url + '"><button>Cancel</button></a>';
    html += '</p>';
    
    update_display('<span class="danger">DELETE FILE</span>', html);
}

/**
 * Show run SQL confirmation
 */
function draw_confirm_run() {
    sql_code = document.getElementById('sql-preview').value;
    
    let html = '<p>You are about to run the SQL file.</p>';
    html += '<p>Location: ' + target_file + '</p>';
    html += '<p>Are you sure?</p>';
    html += '<button class="success" onclick="run_sql()">I Understand The Risks - Execute the SQL</button>';
    html += ' <button onclick="preview_sql()">Preview SQL</button>';
    html += ' <a href="' + current_url + '"><button>Cancel</button></a>';
    html += '</p>';
    html += '<div><textarea id="sql-preview" style="display: none;" disabled></textarea></div>';
    
    update_display('RUN SQL', html);
}

/**
 * Execute SQL file
 */
function run_sql() {
    update_display('PLEASE WAIT', '<p class="blink">Executing SQL...</p>');
    
    const params = {
        sqlCode: sql_code,
        action: 'runSql',
        targetFile: target_file
    };
    
    send_request(params, function(response, status) {
        handle_sql_execution_response(response, status);
    });
}

/**
 * Handle SQL execution response
 */
function handle_sql_execution_response(response, status) {
    if (status === 403) {
        response = response.replace('Finished.', '');
        let html = '<p>Please delete the file, ' + response + '.</p>';
        html += '<p>After you have deleted the file, press \'Okay\'</p>';
        html += '<p><button class="success" onclick="click_okay()">Okay</button></p>';
        update_display('Finished', html);
    } else if (response === 'Finished.') {
        const html = '<p>The SQL file was successfully processed.</p>' +
                     '<p><button class="success" onclick="click_okay()">Okay</button></p>';
        update_display('Finished', html);
    } else {
        show_error(response, 'SQL ERROR');
    }
}

/**
 * Delete SQL file
 */
function delete_sql_file() {
    update_display('PLEASE WAIT', '<p class="blink">Deleting SQL...</p>');
    
    const params = {
        targetFile: target_file,
        action: 'deleteFile'
    };
    
    send_request(params, function(response) {
        if (response === 'Finished.') {
            const html = '<p>The SQL file was successfully deleted.</p>' +
                         '<p><button class="success" onclick="click_okay()">Okay</button></p>';
            update_display('Finished', html);
        } else {
            show_error(response, 'Delete Error');
        }
    });
}

/**
 * Preview SQL before execution
 */
function preview_sql() {
    document.getElementById('sql-preview').innerHTML = sql_code;
    document.getElementById('sql-preview').style.display = 'block';
}

/**
 * Handle final okay click
 */
function click_okay() {
    const params = {
        action: 'checkFinish'
    };
    
    send_request(params, function(response) {
        if (response === 'reload') {
            // More SQL files remain
            location.reload();
        } else {
            // All done - redirect to finish URL
            window.location.href = response;
        }
    });
}

/**
 * Show error message
 */
function show_error(error_msg, title) {
    let html = '<p>Oh dear, there appears to be an error.</p>';
    html += '<p><a href="' + current_url + '"><button>Go Back</button></a></p>';
    html += '<p>The following response was generated:</p>';
    html += '<p><textarea id="error-msg" style="height: 30vh; background-color: #ffe9e8;"></textarea></p>';
    
    update_display('<span class="danger">' + title + '</span>', html);
    document.getElementById('error-msg').innerHTML = error_msg;
}

/**
 * Update page display
 */
function update_display(headline, info_html) {
    document.getElementById('headline').innerHTML = headline;
    document.getElementById('info').innerHTML = info_html;
}

/**
 * Send AJAX request to the API endpoint
 */
function send_request(params, callback) {
    const http = new XMLHttpRequest();
    http.open('POST', api_url);
    http.setRequestHeader('Content-type', 'application/json');
    http.send(JSON.stringify(params));
    
    http.onload = function() {
        callback(http.responseText, http.status);
    };
}