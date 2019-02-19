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
    $stmt = $db->prepare('SELECT created_by, community FROM replies LEFT JOIN posts ON posts.id = post LEFT JOIN communities ON communities.id = community WHERE replies.id = ? AND replies.sensitive_content = 0 AND replies.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
} else {
    $stmt = $db->prepare('SELECT created_by, community FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.sensitive_content = 0 AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
}
$stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 9999999, 'There was an error while checking for the post/reply\'s existence.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(500, 4040404, 'The post/reply could not be found.');
}
$row = $result->fetch_array();
if($_SESSION['id'] !== $row['created_by']) {
    $stmt = $db->prepare('SELECT level FROM users WHERE id = ?');
    $stmt->bind_param('i', $row['created_by']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1939418, 'There was an error while checking for the post/reply\'s ownership.');
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if($_SESSION['level'] < $row['level']) {
        if($row['community'] === null) {
            showJSONError(403, 4030403, 'You don\'t have permission to set this post/reply as sensitive.');
        }
        $stmt = $db->prepare('SELECT level FROM community_admins WHERE user = ? AND community = ?');
        $stmt->bind_param('ii', $_SESSION['id'], $row['community']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 5000000, 'An error occurred while checking your community administrator status.');
        }
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            showJSONError(403, 4030404, 'You don\'t have permission to set this post/reply as sensitive.');
        }
        $row = $result->fetch_assoc();
        $stmt->bind_param('ii', $row['created_by'], $row['community']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 5000001, 'An error occurred while checking the creator of the post/reply\'s community administrator status.');
        }
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $crow = $result->fetch_assoc();
            if($crow['level'] >= $row['level']) {
                showJSONError(403, 4030405, 'You don\'t have permission to set this post/reply as sensitive.');
            }
        }
    }
}

if($type === 1) {
    $stmt = $db->prepare('UPDATE replies SET sensitive_content = 1 WHERE id = ?');
} else {
    $stmt = $db->prepare('UPDATE posts SET sensitive_content = 1 WHERE id = ?');
}
if(!$stmt) {
    showJSONError(500, 1234567, 'There was an error while preparing to set the post/reply as sensitive.');
}
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 7654321, 'There was an error while setting the post/reply as sensitive.');
}
require_once('posts.php');
?>