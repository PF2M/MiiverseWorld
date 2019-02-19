<?php
require_once('inc/connect.php');
requireAuth();
if($_SERVER['REQUEST_METHOD'] !== 'POST') {
    showError(405, 'You must use a POST request.');
}
if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
    showJSONError(400, 1234321, 'The CSRF check failed.');
}
if(empty($_GET['id'])) {
    showError(400, 'You must specify a community ID.');
}
if(empty($_GET['action'])) {
    showError(400, 'You must specify an action.');
}
$stmt = $db->prepare('SELECT COUNT(*) FROM communities WHERE id = ? AND (permissions = 0 OR privacy = 1) AND status = 0');
$stmt->bind_param('i', $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showError(500, 'An error occurred while grabbing your community membership status from the database.');
}
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if($row['COUNT(*)'] === 0) {
    showError(404, 'The community could not be found.');
}
if($_GET['action'] === 'join') {
    $stmt = $db->prepare('REPLACE INTO community_members (user, community) VALUES (?, ?)');
} else if($_GET['action'] === 'leave') {
    $stmt = $db->prepare('DELETE FROM community_admins WHERE user = ? AND community = ?');
    $stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
    $stmt->execute();
    if($stmt->error) {
        showError(500, 'An error occurred while leaving the admin team.');
    }
    $stmt = $db->prepare('DELETE FROM community_members WHERE user = ? AND community = ?');
} else {
    showError(400, 'The action could not be found.');
}
$stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showError(500, 'An error occurred while performing that action.');
}
http_response_code(302);
header('Location: /communities/' . $_GET['id']);
?>