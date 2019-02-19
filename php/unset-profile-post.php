<?php
require_once('inc/connect.php');
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showJSONError(405, 6969696, 'You must use a POST request.');
}
if(empty($_SESSION['username'])) {
    showJSONError(401, 0000000, 'You must log in to view this page.');
}
if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
    showJSONError(400, 1234321, 'The CSRF check failed.');
}

$stmt = $db->prepare('UPDATE users SET favorite_post = NULL WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 1111922, 'An error occurred while trying to unset the favorite post.');
}
?>