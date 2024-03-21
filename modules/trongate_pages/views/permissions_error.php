<!DOCTYPE html>
<html>

<head>
  <title>Permissions Error</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
</head>

<body>
  <div class="container">
    <h1>Permissions Error <span class="xs">(don't panic!)</span></h1>
    <p>The folder 'modules/trongate_pages/assets/images/uploads' is not writable. Please change the folder permissions to allow write access.</p>
    <h3>Mac Users</h3>
    <ol>
      <li>Open the Terminal app.</li>
      <li>Navigate to the folder that needs to be modified by typing <code>cd /path/to/folder</code>.</li>
      <li>Run the following command to change the folder permissions to allow write access: <code>chmod -R 755 foldername</code>.</li>
      <li>If you still get a permissions error, try running the following command instead: <code>chmod -R 777 foldername</code>.</li>
    </ol>
    <h3>Windows Users</h3>
    <ol>
      <li>Right-click on the folder that needs to be modified and select "Properties".</li>
      <li>Click on the "Security" tab.</li>
      <li>Click on the "Edit" button.</li>
      <li>Click on the "Add" button and type "Everyone" in the "Enter the object names to select" field.</li>
      <li>Click on the "Check Names" button and then click "OK".</li>
      <li>Select "Everyone" from the list of users and check the "Full Control" checkbox.</li>
      <li>Click "OK" to save the changes.</li>
    </ol>
    <h3>Linux Users</h3>
    <ol>
      <li>Open the Terminal app.</li>
      <li>Navigate to the folder that needs to be modified by typing <code>cd /path/to/folder</code>.</li>
      <li>Run the following command to change the folder permissions to allow write access: <code>chmod -R 755 foldername</code>.</li>
      <li>If you still get a permissions error, try running the following command instead: <code>chmod -R 777 foldername</code>.</li>
    </ol>
    <h2><i>Please refresh this page once you've carried out the steps described above.</i></h2>
  </div>
</body>

</html>