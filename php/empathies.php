<?php
require_once('inc/connect.php');
if($_SERVER['REQUEST_METHOD'] != 'POST') {
    showJSONError(405, 6969696, 'You must use a POST request.');
}
if(empty($_SESSION['username'])) {
    showJSONError(401, 0000000, 'You must log in to view this page.');
}
if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
    showJSONError(400, 1234321, 'The CSRF check failed.');
}
if(empty($_GET['type']) || empty($_GET['id'])) {
    showJSONError(400, 1111111, 'You must specify a type and ID.');
}

$type = $_GET['type'] === 'replies' ? 1 : 0;
if($type === 1) {
    $stmt = $db->prepare('SELECT replies.created_by, community, privacy FROM replies LEFT JOIN posts ON post = posts.id LEFT JOIN communities ON communities.id = community WHERE replies.id = ? AND replies.created_by != ? AND replies.status = 0');
} else {
    $stmt = $db->prepare('SELECT created_by, community, privacy FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.created_by != ? AND posts.status = 0');
}
$stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 9999999, 'An error occurred while checking for the ' . ($type === 1 ? 'reply' : 'post')  .'\'s existence.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 1219999, 'The ' . ($type === 1 ? 'reply' : 'post') . ' could not be found.');
}
$row = $result->fetch_assoc();
if($row['community'] !== null && $row['privacy'] !== 0) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM community_members WHERE user = ? AND community = ? AND status = 1');
    $stmt->bind_param('ii', $_SESSION['id'], $row['community']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 8008135, 'An error occurred while checking your community membership status.');
    }
    $result = $stmt->get_result();
    $crow = $result->fetch_assoc();
    if($crow['COUNT(*)'] === 0) {
        showJSONError(403, 5318008, 'You don\'t have permission to yeah that post.');
    }
}

if($_GET['delete'] === '.delete') {
    $stmt = $db->prepare('DELETE FROM empathies WHERE source = ? AND target = ? AND type = ?');
} else {
    $stmt = $db->prepare('REPLACE INTO empathies (source, target, type) VALUES (?, ?, ?)');
}
$stmt->bind_param('iii', $_SESSION['id'], $_GET['id'], $type);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 7654321, 'An error occurred while inserting/deleting the empathy.');
}

if($_GET['delete'] !== '.delete') {
    if(sendNotification($row['created_by'], $type, $_GET['id']) === false) {
        showJSONError(500, 1726354, 'An error occurred while sending a notification.');
    }
}
?>