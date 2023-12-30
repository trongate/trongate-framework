<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>css/trongate.css">
    <title>Trongate API Explorer</title>
</head>

<body>
    <div id="top-row">
        <div>Trongate API Explorer</div>
        <div id="top-rhs">
            <span id="token-status"></span>
            <?php
            $token_attr['id'] = 'input-token';
            $token_attr['placeholder'] = 'Enter authorization token';
            $token_attr['autocomplete'] = 'off';
            echo form_input('input_token', '', $token_attr);
            $set_token_attr['id'] = 'set-token-btn';
            $set_token_attr['onclick'] = 'setToken()';
            echo form_button('set_token_btn', 'Set Token', $set_token_attr);
            ?>
        </div>
    </div>
    <main>

        <div class="container">
            <h4><?= $target_table ?></h4>
            <div id="overflow-container">
                <table id="endpoints-tbl">
                    <thead>
                        <tr>
                            <th>Request Type</th>
                            <th>Endpoint Name</th>
                            <th>URL Segments</th>
                            <th>Description</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $counter = -1;
                        foreach ($endpoints as $endpoint_name => $endpoint_row) {
                            $counter++;
                            $btn_class = 'btn-' . strtolower($endpoint_row['request_type']);
                            $btn_class .= ' open-api-tester';
                            $btn_key = str_replace(' ', '-', strtolower($endpoint_name));
                            $request_type = $endpoint_row['request_type'];
                        ?>
                            <tr class="row-<?= strtolower($request_type) ?>">
                                <td>
                                    <button id="btn-<?= $btn_key ?>" class="<?= $btn_class ?>" data-row="<?= $counter ?>">
                                        <?= $endpoint_row['request_type'] ?>
                                    </button>
                                </td>
                                <td><?= $endpoint_name ?></td>
                                <td><?= $endpoint_row['url_segments'] ?></td>
                                <td><?= $endpoint_row['description'] ?></td>
                            </tr>
                        <?php
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <div class="modal" id="test-endpoint-modal" style="display: none">
        <div class="modal-heading">
            <div id="endpoint-name"></div>
            <div><span class="close-modal" onclick="destroyModal()">&times;</span></div>
        </div>
        <div class="modal-body"></div>
    </div>
    <?php
    require_once('api_explorer_style.php');
    require_once('api_explorer_js.php');
    require_once('api_explorer_modal_js.php');
    ?>
</body>

</html>