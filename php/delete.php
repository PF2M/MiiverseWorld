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
    $stmt = $db->prepare('SELECT replies.created_by, community, privacy FROM replies LEFT JOIN posts ON post = posts.id LEFT JOIN communities ON communities.id = community WHERE replies.id = ? AND replies.status = 0');
} else {
    $stmt = $db->prepare('SELECT created_by, community, privacy FROM posts LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.status = 0');
}
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 9999999, 'There was an error while checking for the post/reply\'s existence.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(500, 4040404, 'The post/reply could not be found.');
}
$row = $result->fetch_assoc();
if($_SESSION['id'] !== $row['created_by']) {
    $stmt = $db->prepare('SELECT level FROM users WHERE id = ?');
    if(!$stmt) {
        showJSONError(500, 8929214, 'There was an error while preparing to check for the post/reply\'s ownership.');
    }
    $stmt->bind_param('i', $row['created_by']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1939418, 'There was an error while checking for the post/reply\'s ownership.');
    }
    $result = $stmt->get_result();
    $urow = $result->fetch_assoc();
    if($_SESSION['level'] <= $urow['level']) {
        if($row['community'] !== null) {
            $stmt = $db->prepare('SELECT level FROM community_admins WHERE user = ? AND community = ?');
            $stmt->bind_param('ii', $_SESSION['id'], $row['community']);
            $stmt->execute();
            if($stmt->error) {
                showJSONError(500, 4443234, 'An error occurred while checking your community administrator status.');
            }
            $result = $stmt->get_result();
            if($result->num_rows > 0) {
                $crow = $result->fetch_assoc();
                $stmt->bind_param('ii', $row['created_by'], $row['community']);
                $stmt->execute();
                if($stmt->error) {
                    showJSONError(500, 4443234, 'An error occurred while checking the creator of the post\'s community administrator status.');
                }
                $result = $stmt->get_result();
                if($result->num_rows > 0) {
                    $skip = true;
                } else {
                    $lrow = $result->fetch_assoc();
                    if($crow['level'] > $lrow['level']) {
                        $skip = true;
                    }
                }
            }
        }
        if(!isset($skip) && $type === 1) {
            $stmt = $db->prepare('SELECT created_by FROM posts WHERE id = (SELECT post FROM replies WHERE id = ?)');
            if(!$stmt) {
                showJSONError(500, 8138912, 'There was an error while preparing to check for the reply\'s post\'s ownership.');
            }
            $stmt->bind_param('i', $_GET['id']);
            $stmt->execute();
            if($stmt->error) {
                showJSONError(500, 4928493, 'There was an error while checking for the reply\'s post\'s ownership.');
            }
            $stmt->bind_result($creator);
            if($_SESSION['id'] !== $creator) {
                showJSONError(403, 3040304, 'You don\'t have permission to delete this reply.');
            }
        } else {
            showJSONError(403, 4030403, 'You don\'t have permission to delete this post.');
        }
    } else {
        $status = 2;
    }
} else {
    $status = 1;
}

if($row['privacy'] !== null && $status === 2) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM community_members WHERE user = ? AND community = ? AND status = 1');
    $stmt->bind_param('ii', $_SESSION['id'], $row['community']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 5409832, 'An error occurred while checking your community membership status.');
    }
    $result = $stmt->get_result();
    $crow = $result->fetch_assoc();
    if($crow['COUNT(*)'] === 0) {
        showJSONError(403, 3958302, 'You must be a member of that community to delete that post.');
    }
}

if($type === 1) {
    $stmt = $db->prepare('UPDATE replies SET status = ? WHERE id = ?');
} else {
    $stmt = $db->prepare('UPDATE posts SET status = ? WHERE id = ?');
}
if(!$stmt) {
    showJSONError(500, 1234567, 'There was an error while preparing to delete the post/reply.');
}
$stmt->bind_param('ii', $status, $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 7654321, 'There was an error while deleting the post/reply.');
}

$stmt = $db->prepare('SELECT favorite_post FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
if(!$stmt->error) {
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if($row['favorite_post'] == $_GET['id']) {
        require('unset-profile-post.php');
    }
}

require_once('posts.php');
?>