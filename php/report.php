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
if(empty($_GET['id'])) {
    showJSONError(400, 1111111, 'You must specify an ID.');
}
if(empty($_GET['type']) || !isset($_POST['type'])) {
    showJSONError(400, 1111112, 'You must set a type.');
}
if(($_GET['type'] !== 'posts' && $_GET['type'] !== 'replies') || ($_POST['type'] !== '0' && $_POST['type'] !== '1')) {
    showJSONError(400, 1111113, 'Your type is invalid.');
}
if(empty($_POST['body'])) {
    showJSONError(400, 1111114, 'You must set a message.');
}
if(mb_strlen($_POST['body']) > 200) {
    showJSONError(400, 1111115, 'Your message is too long.');
}
$type = $_GET['type'] === 'replies' ? 1 : 0;

if($type === 1) {
    $stmt = $db->prepare('SELECT community FROM replies LEFT JOIN posts ON post = posts.id WHERE replies.id = ? AND replies.created_by != ? AND replies.status = 0');
} else {
    $stmt = $db->prepare('SELECT community FROM posts WHERE id = ? AND created_by != ? AND status = 0');
}
$stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 1234567, 'An error occurred while grabbing the post/reply.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The post/reply could not be found.');
}
$row = $result->fetch_assoc();
if($_GET['type'] === '1') {
    $row['community'] = null;
}

$stmt = $db->prepare('REPLACE INTO reports (source, target, type, body, community) VALUES (?, ?, ?, ?, ?)');
$stmt->bind_param('iiisi', $_SESSION['id'], $_GET['id'], $type, $_POST['body'], $row['community']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5000000, 'An error occurred while reporting that post/reply.');
}
header('Content-Type: application/json');
echo '{"success":1}';
?>