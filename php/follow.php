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

$stmt = $db->prepare('SELECT id FROM users WHERE username = ? AND status = 0 AND id != ?');
$stmt->bind_param('si', $_GET['username'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 1234567, 'There was an error while fetching the user.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The user could not be found.');
}
$row = $result->fetch_array();

if($_GET['un'] === 'un') {
    $stmt = $db->prepare('DELETE FROM follows WHERE source = ? AND target = ?');
} else {
    $stmt = $db->prepare('REPLACE INTO follows (source, target) VALUES (?, ?)');
}
$stmt->bind_param('ii', $_SESSION['id'], $row['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5000000, 'There was an error while inserting the follow.');
}

if($_GET['un'] !== 'un') {
    if(sendNotification($row['id'], 4) === false) {
        showJSONError(500, 1010101, 'There was an error while sending a notification.');
    }
}
?>