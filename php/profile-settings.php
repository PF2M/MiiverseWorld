<?php
require_once('inc/connect.php');
requireAuth();
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
        showJSONError(400, 1234321, 'The CSRF check failed.');
    }
    $stmt = $db->prepare('SELECT nickname, email, has_mh, nnid, mh, profile_comment FROM users WHERE id = ?');
    $stmt->bind_param('i', $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showJSONError(500, 1203091, 'An error occurred while grabbing your settings from the database.');
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $edits = [];
    $sessionEdits = [];
    foreach($_POST as $key => &$value) {
        if(array_key_exists($key, $row) && $row[$key] !== $value) {
            switch($key) {
                case 'nickname':
                    if(mb_strlen($value) > 64) {
                        showJSONError(400, 1211337, 'Your nickname is too long.');
                    }
                    break;
                case 'profile_comment':
                    if(mb_strlen($value) > 2000) {
                        showJSONError(400, 1337121, 'Your profile comment is too long.');
                    }
                    break;
                case 'nnid':
                    if(!preg_match('/^[A-Za-z0-9-._]{6,16}$/', $value)) {
                        showJSONError(400, 1212121, 'Your Nintendo Network ID is invalid.');
                    }
                    $ch = curl_init('https://pf2m.com/hash/' . $value);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    $miiHash = curl_exec($ch);
                    $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
                    if($responseCode < 200 || $responseCode > 299) {
                        showJSONError(400, 4041337, 'The Nintendo Network ID could not be found.');
                    }
                    $edits[] = 'mh = "' . $db->real_escape_string($miiHash) . '"';
                    if((!empty($_POST['has_mh']) && $_POST['has_mh'] === '1') || (empty($_POST['has_mh']) && $row['has_mh'] === '1')) {
                        $edits[] = 'avatar = "' . $db->real_escape_string($miiHash) . '"';
                        $sessionEdits['avatar'] = $miiHash;
                        $sessionEdits['has_mh'] = 1;
                    }
                    break;
                case 'has_mh':
                    if(!in_array($value, ['0', '1'])) {
                        showJSONError(400, 1038843, 'Your avatar setting is invalid.');
                    }
                    if($value === '1') {
                        $sessionEdits['has_mh'] = 1;
                        if(empty($_POST['nnid']) || $_POST['nnid'] === $row['nnid']) {
                            if(empty($row['mh'])) {
                                $edits[] = 'avatar = NULL';
                                $sessionEdits['avatar'] = null;
                            } else {
                                $edits[] = 'avatar = "' . $db->real_escape_string($row['mh']) . '"';
                                $sessionEdits['avatar'] = $row['mh'];
                            }
                        }
                    } else {
                        $sessionEdits['has_mh'] = 0;
                        if(empty($row['email'])) {
                            $edits[] = 'avatar = NULL';
                            $sessionEdits['avatar'] = null;
                        } else {
                            $edits[] = 'avatar = "https://gravatar.com/avatar/' . md5($row['email']) . '?s=96"';
                            $sessionEdits['avatar'] = 'https://gravatar.com/avatar/' . md5($row['email']) . '?s=96';
                        }
                    }
                    break;
                default:
                    goto next;
            }
            $edits[] = $key . ' = "' . $db->real_escape_string($value) . '"';
            next:
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
    if(count($sessionEdits) > 0) {
        foreach($sessionEdits as $key => &$value) {
            $_SESSION[$key] = $value;
        }
    }
} else {
    $title = 'Profile Settings';
    require_once('inc/header.php');
    $row = initUser($_SESSION['username']);
    ?><div class="main-column messages">
        <div class="post-list-outline">
            <h2 class="label">Profile Settings</h2>
            <form class="setting-form" action="/settings/profile" method="post">
                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                <ul class="settings-list">
                    <li>
                        <p class="settings-label">Nickname</p>
                        <input type="text" class="url-form" name="nickname" maxlength="64" value="<?=htmlspecialchars($row['nickname'])?>" placeholder="Nickname">
                    </li>
                    <li class="setting-profile-comment">
                        <p class="settings-label">Profile Comment</p>
                        <textarea id="profile-text" class="textarea" name="profile_comment" maxlength="2000" placeholder="Write about yourself here."><?=htmlspecialchars($row['profile_comment'])?></textarea>
                        <p class="note">What you write here will appear on your profile. Feel free to write anything that doesn't violate <a href="/rules">Miiverse World's rules</a>.</p>
                    </li>
                    <li class="setting-profile-post">
                        <p class="settings-label">Favorite Post</p>
                        <p class="note">You can set one of your own screenshot posts as your favorite from the settings button on that post.</p>
                        <?php
                        if($row['favorite_post'] !== null) {
                            $stmt = $db->prepare('SELECT image FROM posts WHERE id = ?');
                            $stmt->bind_param('i', $row['favorite_post']);
                            $stmt->execute();
                            $result = $stmt->get_result();
                            $prow = $result->fetch_assoc();
                            echo '<div class="select-content"><button id="profile-post" type="button" class="submit"><img src="' . htmlspecialchars($prow['image']) . '"><span class="symbol">Remove</span></button></div>';
                        }
                        ?>
                    </li>
                    <li>
                        <p class="settings-label">Nintendo Network ID</p>
                        <input type="text" class="url-form" name="nnid" minlength="6" maxlength="16" value="<?=htmlspecialchars($row['nnid'])?>" placeholder="Nintendo Network ID">
                    </li>
                    <li>
                        <p class="settings-label">Avatar</p>
                        <label><input type="radio" name="has_mh" value="1"<?=$row['has_mh'] === 1 ? ' checked' : ''?>> Mii</label>
                        <label><input type="radio" name="has_mh" value="0"<?=$row['has_mh'] === 0 ? ' checked' : ''?>> Gravatar</label>
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