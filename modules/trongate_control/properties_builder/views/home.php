<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Properties Builder</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="<?= BASE_URL ?>trongate_control-properties_builder_module/css/w3.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>trongate_control-properties_builder_module/css/properties_builder.css">
</head>
<body style="overflow:hidden; background-color: #000;">
    <div id="particles-div" style="opacity: 0;">
        <div class="container">
            <div class="w3-row stage">
                <div class="w3-col s3 w3-center left-side">
                    <div class="properties-selector cloak" style="opacity: 0;">
                        <h2>Properties Fields</h2>
                        <div id="properties_fields"></div>
                        <button onclick="clickAddressBtn('address')" class="w3-button w3-block w3-white w3-border w3-border-blue">address</button>
                    </div>
                </div>
                <div class="w3-col s9 w3-center right-side">
                    <div class="logo cloak" style="opacity: 0;">Properties BuilDER</div>
                    <h2 class="cloak" style="opacity: 0;">Click A Property Field To Add A New Property</h2>

                    <div class="center-stage">
                        <div id="current-properties"></div>
                        <div class="close-btns" id="close-btns" style="opacity: 0; margin-left: 600px;">
                            <button onclick="submitProperties('submit')" class="w3-button w3-large w3-blue-grey submit-btn" id="submit-btn">Submit Properties</button>
                            <button onclick="submitProperties('cancel')" class="w3-button w3-large cancel-btn">Cancel &amp; Close</button>
                        </div>

                        <div id="id01" class="w3-modal">
                            <div class="w3-modal-content w3-animate-left w3-card-4" style="width: 30em;">
                                <header class="w3-container w3-blue-grey">
                                    <span onclick="document.getElementById('id01').style.display='none'" class="w3-button w3-display-topright">&times;</span>
                                    <h2 id="new-property-headline"></h2>
                                </header>
                                <div class="w3-container modal-body">
                                    <p id="modal-instructions"></p>

                                    <form class="demo" id="btn-add-address-dd">
                                        <select class="option3" id="address-type-selector"></select>
                                    </form>

                                    <form id="add-property-form" autocomplete="off" action="/action_page.php">
                                        <div class="autocomplete" style="width:100%;">
                                            <input id="new-property-title" type="text" name="myNewProperty" placeholder="Enter property title. For example, 'First Name'">
                                        </div>
                                    </form>
                                    <p id="btn-add-new-property">
                                        <button onclick="addNewProperty()" class="w3-button w3-blue">Add New Property</button>
                                    </p>
                                    <p id="btn-add-address">
                                        <button onclick="addAddress()" style="width: 90%;" class="w3-button w3-blue">Add Address</button>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        localStorage.setItem('properties', '[]');
        var apiBaseUrl = '<?= BASE_URL ?>';
        var properties = [];
        var current_suggestions = [];
        var allPropertiesDataFromApi = [];
        var allPropertiesDataActiveIndex = 0;
        var allowDeletes = true;
        var initCloseAccordions = true;

        window.onload = function() {
            fetch_properties();
        }

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                // Close the add-property modal if open
                var modal = document.getElementById('id01');
                if (modal && modal.style.display !== 'none') {
                    modal.style.display = 'none';
                }
                // Close the properties builder overlay
                window.parent.postMessage('close_properties_builder', '*');
            }
        });
    </script>
    <script src="<?= BASE_URL ?>trongate_control-properties_builder_module/js/properties_builder.js"></script>
</body>
</html>
