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
if(empty($_GET['username'])) {
    showJSONError(400, 1111111, 'You must specify a username.');
}
if(empty($_POST['body'])) {
    showJSONError(400, 1111112, 'You must set a message.');
}
if(mb_strlen($_POST['body']) > 200) {
    showJSONError(400, 1111113, 'Your message is too long.');
}

$stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND status = 0 AND id != ?');
$stmt->bind_param('si', $_GET['username'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 1234567, 'An error occurred while grabbing that user.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The user could not be found.');
}
$row = $result->fetch_assoc();

$stmt = $db->prepare('REPLACE INTO reports (source, target, type, body) VALUES (?, ?, 2, ?)');
$stmt->bind_param('iis', $_SESSION['id'], $row['id'], $_POST['body']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5000000, 'An error occurred while reporting that user.');
}
header('Content-Type: application/json');
echo '{"success":1}';
?>