<?php
foreach($validation_tests as $validation_test_row) {
    if ($validation_test_row !== '') {
        echo '            '.$validation_test_row.PHP_EOL;
    }
}