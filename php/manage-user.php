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
if(empty($_POST['action'])) {
    showJSONError(400, 1111112, 'You must specify an action.');
}
if($_POST['action'] < 1 || $_POST['action'] > 3) {
    showJSONError(400, 1111113, 'Your action is invalid.');
}
$stmt = $db->prepare('SELECT id, level, ip FROM users WHERE username = ? AND status = 0');
$stmt->bind_param('s', $_GET['username']);
$stmt->execute();
if($stmt->error) {
    showJSONError(500, 5000001, 'An error occurred while grabbing the account.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showJSONError(404, 4040404, 'The user could not be found.');
}
$row = $result->fetch_assoc();
if($_SESSION['id'] !== $row['id'] && $_SESSION['level'] <= $row['level']) {
    showJSONError(403, 4030403, 'You don\'t have permission to manage this user.');
}

switch($_POST['action']) {
    case '1':
        $fields = ['blocks', 'communities', 'community_favorites', 'empathies', 'follows', 'posts', 'replies', 'reports'];
        $metadata = [['source', null], ['user', 'status'], ['user', null], ['source', null], ['source', null], ['created_by', 'status'], ['created_by', 'status'], ['source', null]];
        foreach($_POST as $key => &$field) {
            $search = array_search($key, $fields);
            if($search && $field) {
                $sql = $key . ' WHERE ' . $metadata[$search][0] . ' = ?';
                if($metadata[$search][1] !== null) {
                    $sql .= ' AND ' . $metadata[$search][1] . ' = 0';
                }
                $stmt = $db->prepare('DELETE FROM ' . $sql);
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
                if($stmt->error) {
                    showJSONError(500, 5012030, 'An error occurred while purging the account.');
                }
            }
        }
        break;
    case '2':
        if($_SESSION['id'] === $row['id']) {
            showJSONError(400, 1111114, 'You cannot ban yourself.');
        }
        $stmt = $db->prepare('SELECT COUNT(*) FROM bans WHERE user = ? AND NOW() < banned_at + INTERVAL length DAY');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 5012031, 'An error occurred while grabbing the account\'s ban status.');
        }
        $result = $stmt->get_result();
        $brow = $result->fetch_assoc();
        if($brow['COUNT(*)'] === 0) {
            if(empty($_POST['type']) || !is_numeric($_POST['type']) || strlen($_POST['type']) > 10) {
                showJSONError(400, 1111115, 'You have specified an invalid ban length.');
            }
            $cidr = getCIDR($row['ip']);
            $stmt = $db->prepare('INSERT INTO bans (user, ip, length) VALUES (?, ?, ?)');
            $stmt->bind_param('isi', $row['id'], $cidr, $_POST['type']);
            $stmt->execute();
            if($stmt->error) {
                showJSONError(500, 5012032, 'An error occurred while banning the account.');
            }
            if(!empty($_POST['purge'])) {
                $stmt = $db->prepare('UPDATE posts SET status = 1 WHERE created_by = ?');
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
                if($stmt->error) {
                    showJSONError(500, 5012033, 'An error occurred while purging the account\'s posts.');
                }
                $stmt = $db->prepare('UPDATE replies SET status = 1 WHERE created_by = ?');
                $stmt->bind_param('i', $row['id']);
                $stmt->execute();
                if($stmt->error) {
                    showJSONError(500, 5012034, 'An error occurred while purging the account\'s comments.');
                }
            }
        } else {
            if(!empty($_POST['type'])) {
                showJSONError(400, 1111116, 'This user is already banned.');
            }
            $stmt = $db->prepare('DELETE FROM bans WHERE user = ?');
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
            if($stmt->error) {
                showJSONError(500, 5012035, 'An error occurred while unbanning the account.');
            }
        }
        break;
    case '3':
        if(empty($_POST['password']) && $_POST['password'] !== '0') {
            showJSONError(400, 1111116, 'You must enter your password.');
        }
        $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['id']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 5012036, 'An error occurred while grabbing your password.');
        }
        $result = $stmt->get_result();
        $prow = $result->fetch_assoc();
        if(!password_verify($_POST['password'], $prow['password'])) {
            showJSONError(400, 1111117, 'The password you entered is not correct.');
        }
        $stmt = $db->prepare('DELETE FROM users WHERE id = ?');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 5012037, 'An error occurred while deleting the account.');
        }
}

header('Content-Type: application/json');
echo '{"success":1}';
?>