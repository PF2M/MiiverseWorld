<?php
require_once('inc/connect.php');
if($_SERVER['REQUEST_METHOD'] != 'POST') {
    showJSONError(405, 6969696, 'You must use a POST request.');
}
if(empty($_SESSION['username'])) {
    showJSONError(401, 0000000, 'You must log in to view this page.');
}
if(empty($_GET['id'])) {
    showJSONError(400, 1219999, 'You must specify a post ID.');
}

$stmt = $db->prepare('SELECT created_by, posts.status FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND sensitive_content = 0 AND posts.status = 0 AND (community IS NULL OR privacy = 0)');
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 4206969, 'An error occurred while grabbing the post from the database.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The post could not be found.');
}
$row = $result->fetch_assoc();
if($row['status'] !== 0) {
    showJSONError(404, 4040405, 'The post could not be found.');
}
if($_SESSION['id'] !== $row['created_by']) {
    showJSONError(403, 4030403, 'You don\'t have permission to favorite this post.');
}

$stmt = $db->prepare('UPDATE users SET favorite_post = ? WHERE id = ?');
$stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 4206970, 'An error occurred while setting your favorite post.');
}
?>