<?php
require_once('inc/connect.php');
if(empty($_SESSION['username'])) {
    http_response_code(403);
    require_once('403.php');
} else {
    http_response_code(302);
    header('Location: /users/' . $_SESSION['username']);
}
?>