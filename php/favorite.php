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
if(empty($_GET['id'])) {
    showJSONError(400, 1111111, 'You must specify a community ID.');
}

$stmt = $db->prepare('SELECT privacy FROM communities WHERE id = ? AND status = 0');
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 1234567, 'An error occurred while fetching the community.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The community could not be found.');
}
$row = $result->fetch_array();
if($row['privacy'] !== 0) {
    $stmt = $db->prepare('SELECT COUNT(*) FROM community_members WHERE user = ? AND community = ? AND status = 1');
    $stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 8888888, 'An error occurred while checking your community membership status.');
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if($row['COUNT(*)'] === 0) {
        showJSONError(403, 4030304, 'You don\'t have permission to favorite that community.');
    }
}

if($_GET['un'] === 'un') {
    $stmt = $db->prepare('DELETE FROM community_favorites WHERE user = ? AND community = ?');
} else {
    $stmt = $db->prepare('REPLACE INTO community_favorites (user, community) VALUES (?, ?)');
}
$stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5000000, 'There was an error while inserting the favorite.');
}
?>