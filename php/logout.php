<?php
require_once('inc/connect.php');
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showError(405, 'You must use a POST request.');
}
if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
    showError(400, 'The CSRF check failed.');
}
if(!empty($_COOKIE['mwauth'])) {
    $stmt = $db->prepare('DELETE FROM tokens WHERE value = ?');
    $stmt->bind_param('s', $_COOKIE['mwauth']);
    $stmt->execute();
    if($stmt->error) {
        showError(500, 'An error occurred while deleting your login token.');
    }
    setcookie('mwauth', '', 1, '/');
}
session_destroy();
http_response_code(302);
if(!empty($_GET['callback']) && substr($_GET['callback'], 0, 1) === '/') {
    header('Location: ' . $_GET['callback']);
} else {
    header('Location: /');
}
?>