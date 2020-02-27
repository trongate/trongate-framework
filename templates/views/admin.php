<!DOCTYPE html>
<html lang="en">
<title>Admin Template</title>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<head>
    <link rel="stylesheet" href="https://www.w3schools.com/w3css/4/w3.css">
    <link rel="stylesheet" href="https://www.w3schools.com/lib/w3-theme-black.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:400">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script>
    <link rel="stylesheet" href="<?= BASE_URL ?>admin_files/w3css/css/admin.css">
    <?= $additional_includes_top ?>
</head>
<body>

    <!-- Navbar -->
    <div class="w3-top">
        <div class="w3-bar w3-theme w3-top w3-left-align w3-large">
            <a class="w3-bar-item w3-button w3-right w3-hide-large w3-hover-white w3-large w3-theme-l1" href="javascript:void(0)" onclick="w3_open()"><i class="fa fa-bars"></i></a>
            <a href="#" class="w3-bar-item w3-button w3-theme-l1">Logo</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hover-white">About</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hover-white">Values</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hover-white">News</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hover-white">Contact</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hide-medium w3-hover-white">Clients</a>
            <a href="#" class="w3-bar-item w3-button w3-hide-small w3-hide-medium w3-hover-white">Partners</a>
            <a href="<?= BASE_URL ?>trongate_administrators/manage" class="w3-bar-item w3-button w3-hide-small w3-hide-medium w3-hover-white w3-right">
                <i class="fa fa-gears"></i> Admin Users</a>
        </div>
    </div>

    <!-- Sidebar -->
    <nav class="w3-sidebar w3-bar-block w3-collapse w3-large w3-theme-l5 w3-animate-left" id="mySidebar">
        <a href="javascript:void(0)" onclick="w3_close()" class="w3-right w3-xlarge w3-padding-large w3-hover-black w3-hide-large" title="Close Menu">
            <i class="fa fa-remove"></i>
        </a>
        <h4 class="w3-bar-item"><b>Menu</b></h4>
        <?= Template::partial('admin_files/dynamic_nav') ?>
    </nav>

    <!-- Overlay effect when opening sidebar on small screens -->
    <div class="w3-overlay w3-hide-large" onclick="w3_close()" style="cursor:pointer" title="close side menu" id="myOverlay"></div>

    <!-- Main content -->
    <div class="w3-main" style="margin-left:250px">
        <div class="center-stage">
            <div class="w3-row w3-padding-64">
                <div class="w3-container">
                <?= Template::display($data) ?>
                </div>
            </div>
        </div>
        <?= Template::partial('admin_files/footer') ?>
    </div>

    <script>
        // Get the Sidebar
        var mySidebar = document.getElementById("mySidebar");

        // Get the DIV with overlay effect
        var overlayBg = document.getElementById("myOverlay");

        // Toggle between showing and hiding the sidebar, and add overlay effect
        function w3_open() {
            if (mySidebar.style.display === 'block') {
                mySidebar.style.display = 'none';
                overlayBg.style.display = "none";
            } else {
                mySidebar.style.display = 'block';
                overlayBg.style.display = "block";
            }
        }

        // Close the sidebar with the close button
        function w3_close() {
            mySidebar.style.display = "none";
            overlayBg.style.display = "none";
        }
    </script>
    <?= $additional_includes_btm ?>
</body>
</html>