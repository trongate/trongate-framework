<div id="modal" style="display: none;">
    <div class="row">
        <div class="four columns offset-by-four" style="top: -19em; position: relative;">
            <h1>Update Record</h1>
            <div id="validation-errors-update"></div>
            <form>
                <div id="update-form-fields">
                <label>Username</label>
                <input id="update-username" name="username" class="u-full-width" type="text" placeholder="Enter username here">
               
                <label>Password</label>
                <input id="update-password" name="password" class="u-full-width" type="password" placeholder="Enter new password here">

                <label>Repeat Password</label>
                <input id="update-repeat-password" name="repeat_password" class="u-full-width" type="password" placeholder="Repeat new password here">
                

                <input class="button-primary" type="button" name="submit" value="Update Record" onClick="updateRecord()"> 
                <input class="button button-cancel" value="Cancel" onclick="hideModal()">
                <input class="button button-danger u-pull-right" value="Delete" onclick="confDelete()">
                </div>

                <div id="conf-delete-btns" style="display: none;">
                    <input class="button button-danger" value="Yes - Delete Now" onclick="submitDelete()">
                    <input class="button button-cancel u-pull-right" value="Cancel" onclick="cancelDelete()">    
                </div>
            </form>
        </div>
    </div>
</div>

<div class="row">
    <div class="twelve columns">
<div id="logout">
    <?= anchor('trongate_administrators/logout', 'Logout') ?> <span style="font-size: 1.2em;">&#8594;</span>
</div>

        <h1>Manage Administrators</h1>
        <p>
            <button class="button-primary" onclick="showCreateForm()" id="create-new-btn">
                Create New Record
            </button>
        </p>

        <div class="row" id="new-record-details" style="display: none; margin-top: 3em; margin-bottom: 5em;">
            <div class="eight columns offset-by-two">
                <h3>Create New Record</h3>
                <div id="validation-errors-create"></div>
                <p>Please fill out the form below and then hit 'Create New Record'.</p>
                <?= validation_errors('<div class="validation-error">', '</div>') ?>
                <form>
                    <label>Username</label>
                    <input id="create-username" name="username" class="u-full-width" type="text" placeholder="Enter username here">
                   
                    <label>Password</label>
                    <input id="create-password" name="password" class="u-full-width" type="password" placeholder="Enter password here">

                    <label>Repeat Password</label>
                    <input id="create-repeat-password" name="repeat_password" class="u-full-width" type="password" placeholder="Repeat password here">

                    <input class="button-primary" type="button" name="submit" value="Create New Record" onclick="createRecord()"> 
                    <input class="button button-cancel u-pull-right" value="Cancel" onclick="hideCreateForm()">
                </form>
            </div>
        </div>

        <table class="u-full-width">
          <thead>
            <tr>
              <th>ID</th>
              <th>Username</th>
              <th>Trongate User ID</th>
              <th style="text-align: center;" onclick="showModal()">Action</th>
            </tr>
          </thead>
          <tbody id="records">
          </tbody>
        </table>

    </div>
</div>

<script>
    var token = '<?= $token ?>';
    var targetRecordId = 0;
    var updateFormInnerHTML = '';

    function showCreateForm() {
        document.getElementById("create-new-btn").style.display='none';
        document.getElementById("new-record-details").style.display='block';
    }

    function hideCreateForm() {
        document.getElementById("create-username").value = '';
        document.getElementById("create-password").value = '';
        document.getElementById("create-repeat-password").value = '';
        document.getElementById("new-record-details").style.display='none';
        document.getElementById("create-new-btn").style.display='block';
    }

    function showModal(id, username) {
        targetRecordId = id;
        document.getElementById("modal").style.display = 'block';
        document.getElementById("update-username").value = username;
    }

    function hideModal() {
        document.getElementById('validation-errors-update').innerHTML = '';
        document.getElementById("update-username").value = '';
        document.getElementById("update-password").value = '';
        document.getElementById("update-repeat-password").value = '';
        document.getElementById("modal").style.display = 'none';
    }

    function cancelDelete() {
        document.getElementById("conf-delete-btns").style.display = 'none';
        document.getElementById("update-form-fields").innerHTML = updateFormInnerHTML;
    }

    function confDelete() {

        var recordsTblBody = document.getElementById("records");
        var records = recordsTblBody.children;

        if (records.length<2) {
            alert("Delete is only allowed if you have more than one user." + records.length);
        } else {
            updateFormInnerHTML = document.getElementById("update-form-fields").innerHTML;
            document.getElementById("update-form-fields").innerHTML = '<p>Deletion of users cannot be undone.  Are you sure?</p>';
            document.getElementById("conf-delete-btns").style.display = 'block';
        }

    }

    function submitDelete() {

        cancelDelete();
        hideModal();

        var apiUrl = '<?= BASE_URL ?>api/delete/trongate_administrators/' + targetRecordId;
        const http = new XMLHttpRequest()
        http.open('DELETE', apiUrl)
        http.setRequestHeader('Content-type', 'application/json')
        http.setRequestHeader("trongateToken", token)
        http.send()
        http.onload = function() {

            if (http.status == 200) {
                fetchRecords();
            }

        }

    }

    function drawRecordsTbl(records) {
        var htmlStr = '';
        for (var i = 0; i < records.length; i++) {
            htmlStr+= '<tr class="record-row" id="record-id-' + records[i]['id'] + '">';
            htmlStr+= '<td>' + records[i]['id'] + '</td>';
            htmlStr+= '<td>' + records[i]['username'] + '</td>';
            htmlStr+= '<td>' + records[i]['trongate_user_id'] + '</td>';
            htmlStr+= '<td style="text-align: center;"><button class="button-primary" onclick="showModal(\'' + records[i]['id'] + '\', \'' + records[i]['username'] + '\')">Update</button></td>';
            htmlStr+= '</tr>';
        }

        document.getElementById("records").innerHTML = htmlStr;
    }

    function fetchRecords() {
        var apiUrl = '<?= BASE_URL ?>api/get/trongate_administrators/?orderBy=username';
        const http = new XMLHttpRequest()
        http.open('GET', apiUrl)
        http.setRequestHeader('Content-type', 'application/json')
        http.setRequestHeader("trongateToken", token)
        http.send()
        http.onload = function() {

            if (http.status == 200) {
                var records = JSON.parse(http.responseText);
                drawRecordsTbl(records);
            } else {
                window.location.href = "<?= BASE_URL ?>trongate_administrators/login";
            }

        }
    }

    function createRecord() {
        var username = document.getElementById("create-username").value;
        var password = document.getElementById("create-password").value;
        var repeatPassword = document.getElementById("create-repeat-password").value;

        var validationErrors = [];

        if (username.length<4) {
            validationErrors.push('The username must be at least four characters long.');
        }

        if (password.length<5) {
            validationErrors.push('The password must be at least five characters long.');
        } else if (password !== repeatPassword) {
            //error, since hashed string may not fit into varchar db column!
            validationErrors.push('The password did not match the repeat password.');
        }

        if (validationErrors.length>0) {

            var errorsHtml = '';
            for (var i = 0; i < validationErrors.length; i++) {
                errorsHtml+= '<div class="validation-error">' + validationErrors[i] + '</div>';
            }

            document.getElementById('validation-errors-create').innerHTML = errorsHtml;
        } else {
            hideCreateForm();
            submitCreateRecord(username, password);
        }
    }

    function submitCreateRecord(username, password) {
        var params = {
            username,
            password
        }

        var apiUrl = '<?= BASE_URL ?>api/create/trongate_administrators';
        const http = new XMLHttpRequest()
        http.open('POST', apiUrl)
        http.setRequestHeader('Content-type', 'application/json')
        http.setRequestHeader("trongateToken", token)
        http.send(JSON.stringify(params))
        http.onload = function() {

            if (http.status == 200) {
                fetchRecords();
            }

        }

    }

    function updateRecord() {
        var username = document.getElementById("update-username").value;
        var password = document.getElementById("update-password").value;
        var repeatPassword = document.getElementById("update-repeat-password").value;

        var validationErrors = [];

        if (username.length<4) {
            validationErrors.push('The username must be at least four characters long.');
        }

        if (password.length<5) {
            validationErrors.push('The password must be at least five characters long.');
        } else if (password !== repeatPassword) {
            //error, since hashed string may not fit into varchar db column!
            validationErrors.push('The password did not match the repeat password.');
        }

        if (validationErrors.length>0) {

            var errorsHtml = '';
            for (var i = 0; i < validationErrors.length; i++) {
                errorsHtml+= '<div class="validation-error">' + validationErrors[i] + '</div>';
            }

            document.getElementById('validation-errors-update').innerHTML = errorsHtml;
        } else {
            hideModal();
            submitUpdateRecord(username, password);
        }
    }

    function submitUpdateRecord(username, password) {
        var params = {
            username,
            password
        }

        var apiUrl = '<?= BASE_URL ?>api/update/trongate_administrators/' + targetRecordId;
        const http = new XMLHttpRequest()
        http.open('PUT', apiUrl)
        http.setRequestHeader('Content-type', 'application/json')
        http.setRequestHeader("trongateToken", token)
        http.send(JSON.stringify(params))
        http.onload = function() {

            if (http.status == 200) {
                fetchRecords();
            }

        }

    }

    fetchRecords();

</script>

<style>
.container {
    margin-top: 1vh;
}
.button-cancel {
    background-color: white;
}
.button-danger {
    color: white;
    border: 1px solid #990000;
    background: #990000;
    transition: 0.3s;
}
.button-danger:hover {
    color: #fff;
    border-color: #ff0000;
    background: #ff0000;
    transition: 0.3s;
}
.button-danger:focus {
    color: #fff;
}

#modal {
    top: 0;
    left: 0;
    width: 100%;
    height: 100vh;
    position: fixed;
    opacity: 1;
    background-color: black;
    text-align: center;
}

#modal .row {
    text-align: left;
    height: 100vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

#update-form-fields p {
    color: white;
    min-height: 12em;
}
</style>