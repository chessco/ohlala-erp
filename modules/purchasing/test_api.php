<?php
// Mocking $_GET for CLI test
$_GET['id'] = 2; // Testing with ID 2 which we saw in the screenshot
include(__DIR__ . '/../api/get_request.php');
?>
