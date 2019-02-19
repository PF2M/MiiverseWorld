<?php
require_once('inc/connect.php');
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(empty($_SESSION['username'])) {
        showJSONError(401, 0000000, 'You must log in to view this page.');
    }
    if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
        showJSONError(400, 1234321, 'The CSRF check failed.');
    }
    $stmt = $db->prepare('SELECT created_by, community, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
    $stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 3430696, 'An error occurred while grabbing the post from the database.');
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        showJSONError(400, 1212121, 'The post could not be found.');
    }
    $row = $result->fetch_assoc();
    if($row['reply_count'] >= 1000) {
        showJSONError(403, 1211211, 'This post has reached the maximum amount of comments.');
    }
    if($row['created_by'] !== $_SESSION['id'] && checkCommunityBan($row['community']) !== null) {
        showJSONError(403, 1211212, 'You have been banned from commenting on posts made in this community.');
    }
    if(!isset($_POST['body'])) {
        showJSONError(400, 1216969, 'You must add a body.');
    }
    $_POST['body'] = trim($_POST['body']);
    if(empty($_POST['body'])) {
        showJSONError(400, 1216970, 'Your body is empty.');
    }
    if(mb_strlen($_POST['body']) > 2000) {
        showJSONError(400, 1219309, 'Your body is too long.');
    }
    if(empty($_POST['feeling_id']) || $_POST['feeling_id'] > 5 || $_POST['feeling_id'] < 0) {
        $_POST['feeling_id'] = 0;
    }
    if(!empty($_POST['image'])) {
        $image = uploadImage(base64_decode($_POST['image']));
        if($image === null) {
            showJSONError(500, 1202109, 'An error occurred while uploading the image.');
        }
    }
    if(empty($_POST['sensitive_content']) || $_POST['sensitive_content'] != 1) {
        $_POST['sensitive_content'] = 0;
    }
    $stmt = $db->prepare('SELECT COUNT(*) FROM replies LEFT JOIN posts ON post = posts.id WHERE replies.created_by = ? AND replies.created_at > NOW() - INTERVAL 15 SECOND AND posts.created_by != replies.created_by');
    $stmt->bind_param('i', $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 5820194, 'There was an error while grabbing your recent replies.');
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if($row['COUNT(*)'] > 0) {
        showJSONError(403, 2192920, 'You\'re making too many comments in quick succession. Please try again in a moment.');
    }
    
    $stmt = $db->prepare('INSERT INTO replies (post, created_by, feeling, body, image, sensitive_content) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('iisssi', $_GET['id'], $_SESSION['id'], $_POST['feeling_id'], $_POST['body'], $image, $_POST['sensitive_content']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 9999999, 'An error occurred while inserting the reply into the database.');
    }
    
    $stmt = $db->prepare('SELECT replies.id, created_by, replies.created_at, feeling, body, image, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1) AS empathy_count, -1 AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1 AND source = ?) AS empathy_added, (SELECT created_by FROM posts WHERE id = replies.post) AS op FROM replies LEFT JOIN users ON created_by = users.id WHERE replies.post = ? AND replies.created_by = ? ORDER BY replies.id DESC LIMIT 1');
    $stmt->bind_param('iii', $_SESSION['id'], $_GET['id'], $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 7654321, 'An error occurred while fetching the reply from the database.');
    }
    $result = $stmt->get_result();
    $row = $result->fetch_array();
    $_GET['type'] = null;
    require('elements/post.php');
    
    if($_SESSION['id'] === $row['op']) {
        $stmt = $db->prepare('SELECT DISTINCT created_by FROM replies WHERE post = ? AND created_by != ?');
        $stmt->bind_param('ii', $_GET['id'], $row['op']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 4352617, 'An error occurred while grabbing a list of users to send notifications to.');
        }
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                if(sendNotification($row['created_by'], 3, $_GET['id']) === false) {
                    showJSONError(500, 1726354, 'An error occurred while sending a notification.');
                }
            }
        }
    } else {
        if(sendNotification($row['op'], 2, $_GET['id']) === false) {
            showJSONError(500, 4536271, 'An error occurred while sending a notification.');
        }
    }
} else {
    $stmt = $db->prepare('SELECT created_by FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
    $stmt->bind_param('ii', $_GET['id'], $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1211337, 'An error occurred while grabbing the post from the database.');
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        showJSONError(404, 4041337, 'The post could not be found.');
    }
    $row = $result->fetch_assoc();
    $stmt = $db->prepare('SELECT replies.id, created_by, replies.created_at, feeling, body, image, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1) AS empathy_count, -1 AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1 AND source = ?) AS empathy_added, ? AS op FROM replies LEFT JOIN users ON created_by = users.id WHERE replies.post = ? AND replies.status = 0 ORDER BY replies.id ASC');
    $stmt->bind_param('iii', $_SESSION['id'], $row['created_by'], $_GET['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1211338, 'An error occurred while grabbing the replies from the database.');
    }
    $result = $stmt->get_result();
    echo '<div class="list reply-list">';
    if($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            require('elements/post.php');
        }
    }
    echo '</div>';
}
?>