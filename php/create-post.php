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
if(empty($_POST['community'])) {
    $_POST['community'] = null;
} else {
    $banned = checkCommunityBan($_POST['community']);
    if($banned !== null) {
        showJSONError(403, 1234321, 'You have been banned from interacting with this community.');
    }
    $stmt = $db->prepare('SELECT permissions, privacy FROM communities WHERE id = ? AND status = 0');
    $stmt->bind_param('i', $_POST['community']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1298572, 'An error occurred while grabbing the community from the database.');
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        showJSONError(400, 1021932, 'The community could not be found.');
    }
    $row = $result->fetch_assoc();
    if($row['permissions'] !== null) {
        if($row['permissions'] === 0 && ($_SESSION['level'] === 0 || $row['privacy'] === 1)) {
            $stmt = $db->prepare('SELECT COUNT(*) FROM community_members WHERE user = ? AND community = ? AND status = 1');
        } else if($row['permissions'] > 0 && $_SESSION['level'] === 0) {
            $stmt = $db->prepare('SELECT level FROM community_admins WHERE user = ? AND community = ?');
        } else {
            $skip = true;
        }
        if(!isset($skip)) {
            $stmt->bind_param('ii', $_SESSION['id'], $_POST['community']);
            $stmt->execute();
            if($stmt->error) {
                showJSONError(500, 1209410, 'An error occurred while checking community permissions.');
            }
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                showJSONError(403, 4030666, 'You don\'t have permission to post to that community.');
            }
            if($row['permissions'] > 0 && $_SESSION['level'] === 0) {
                $arow = $result->fetch_assoc();
                if($row['permissions'] > $arow['level']) {
                    showJSONError(403, 4030777, 'You don\'t have a high enough admin level to post to that community.');
                }
            }
        }
    }
}
if(empty($_POST['feeling_id']) || $_POST['feeling_id'] > 5 || $_POST['feeling_id'] < 0) {
    $_POST['feeling_id'] = 0;
}
if(empty($_POST['sensitive_content'])) {
    $_POST['sensitive_content'] = 0;
}
if(empty($_POST['tags'])) {
    $tags = null;
} else {
    $tags = [];
    foreach(array_map('trim', explode(',', encodeTag($_POST['tags']))) as $i => &$tag) {
        if(mb_strlen($tag) > 20) {
            showJSONError(400, 1214444, 'Tag "' . $tag . '" is too long.');
        }
        if($tag === '0') {
            $tag = 'zero';
        }
        if(mb_strlen($tag) === 0 || in_array(strtolower($tag), array_map('strtolower', $tags))) {
            continue;
        }
        $tags[] = $tag;
        if($i === 19) {
            break;
        }
    }
    $tags = ',' . implode(',', $tags) . ',';
}
if(!empty($_POST['image'])) {
    $image = uploadImage(base64_decode($_POST['image']));
    if($image === null) {
        showJSONError(500, 2310924, 'An error occurred while uploading the image.');
    }
}
$yt;
if(preg_match('/(?:youtube\.com\/\S*(?:(?:\/e(?:mbed))?\/|watch\/?\?(?:\S*?&?v\=))|youtu\.be\/)([a-zA-Z0-9_-]{6,11})/', $_POST['body'], $matches)) {
    $yt = $matches[1];
}

if(empty($_POST['sensitive_content']) || $_POST['sensitive_content'] !== '1') {
    $_POST['sensitive_content'] = 0;
}
$stmt = $db->prepare('SELECT COUNT(*) FROM posts WHERE created_by = ? AND created_at > NOW() - INTERVAL 15 SECOND');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5820194, 'There was an error while grabbing your recent posts.');
}
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if($row['COUNT(*)'] > 0) {
    showJSONError(403, 1213005, 'You\'re making too many posts in quick succession. Please try again in a moment.');
}

$stmt = $db->prepare('INSERT INTO posts (created_by, community, feeling, body, image, yt, sensitive_content, tags) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
$stmt->bind_param('iiisssis', $_SESSION['id'], $_POST['community'], $_POST['feeling_id'], $_POST['body'], $image, $yt, $_POST['sensitive_content'], $tags);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 9999999, 'There was an error while inserting the post into the database.');
}

$stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id WHERE created_by = ? ORDER BY posts.id DESC LIMIT 1');
$stmt->bind_param('ii', $_SESSION['id'], $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 7654321, 'There was an error while fetching the post from the database.');
}
$result = $stmt->get_result();
$row = $result->fetch_array();
$_GET['type'] = null;
require('elements/post.php');
?>