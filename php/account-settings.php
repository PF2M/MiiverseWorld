<?php
require_once('inc/connect.php');
requireAuth();
$stmt = $db->prepare('SELECT email, yeah_notifications FROM users WHERE id = ?');
$stmt->bind_param('i', $_SESSION['id']);
$stmt->execute();
if($stmt->error) {
    showError(500, 1203091, 'An error occurred while grabbing your settings from the database.');
}
$result = $stmt->get_result();
$row = $result->fetch_assoc();
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
        showJSONError(400, 1234321, 'The CSRF check failed.');
    }
    $edits = [];
    foreach($_POST as $key => &$value) {
        if(array_key_exists($key, $row) && $row[$key] !== $value) {
            switch($key) {
                case 'email':
                    if(!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        showJSONError(400, 1211337, 'Your email is invalid.');
                    }
                    $stmt = $db->prepare('SELECT COUNT(*) FROM users WHERE email = ? AND status = 0');
                    $stmt->bind_param('s', $value);
                    $stmt->execute();
                    if($stmt->error) {
                        showJSONError(500, 1231029, 'An error occurred while checking for users with that email.');
                    }
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();
                    if($row['COUNT(*)'] > 0) {
                        showJSONError(400, 1290302, 'An account already exists with that email.');
                    }
                    break;
                case 'yeah_notifications':
                    if(!in_array($value, ['0', '1'])) {
                        showJSONError(400, 1337121, 'Your Yeah notification setting is invalid.');
                    }
            }
            $edits[] = $key . ' = "' . $db->real_escape_string($value) . '"';
        }
    }
    if(count($edits) > 0) {
        $stmt = $db->prepare('UPDATE users SET ' . implode(', ', $edits) . ' WHERE id = ?');
        $stmt->bind_param('i', $_SESSION['id']);
        $stmt->execute();
        if($stmt->error) {
            showJSONError(500, 3928989, 'There was an error while saving your settings.');
        }
    }
} else {
    $title = 'Account Settings';
    require_once('inc/header.php');
    initUser($_SESSION['username']);
    ?><div class="main-column messages">
        <div class="post-list-outline">
            <h2 class="label">Account Settings</h2>
            <form class="setting-form" action="/settings/account" method="post">
                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                <ul class="settings-list">
                    <li>
                        <p class="settings-label">Email Address</p>
                        <input type="email" class="url-form" name="email" maxlength="254" value="<?=htmlspecialchars($row['email'])?>" placeholder="Email Address">
                    </li>
                    <li>
                        <p class="settings-label">Do you want to receive Yeah notifications?</p>
                        <div class="select-content">
                            <div class="select-button">
                                <select name="yeah_notifications">
                                    <option value="1"<?=$row['yeah_notifications'] === 1 ? ' selected' : ''?>>Receive</option>
                                    <option value="0"<?=$row['yeah_notifications'] === 0 ? ' selected' : ''?>>Don't Receive</option>
                                </select>
                            </div>
                        </div>
                    </li>
                </ul>
                <div class="form-buttons">
                    <input type="submit" class="black-button apply-button" value="Save Settings">
                </div>
            </form>
        </div>
    </div><?php
    require_once('inc/footer.php');
}
?>