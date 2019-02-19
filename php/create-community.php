<?php
require_once('inc/connect.php');
requireAuth();
if($_SERVER['REQUEST_METHOD'] === 'POST') {
    if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
        $error = 'The CSRF check failed.';
        goto showForm;
    }
    if(!isset($_POST['name'])) {
        $error = 'You must specify a name.';
        goto showForm;
    }
    if(!isset($_POST['privacy'])) {
        $error = 'You must set your community\'s privacy.';
        goto showForm;
    }
    $values = [];
    if(file_exists($_FILES['icon']['tmp_name']) && is_uploaded_file($_FILES['icon']['tmp_name'])) {
        $values['icon'] = uploadImage(file_get_contents($_FILES['icon']['tmp_name']), 128, 128);
        if($values['icon'] === null) {
            $error = 'An error occurred while uploading the icon.';
            goto showForm;
        }
    }
    if(file_exists($_FILES['banner']['tmp_name']) && is_uploaded_file($_FILES['banner']['tmp_name'])) {
        $values['banner'] = uploadImage(file_get_contents($_FILES['banner']['tmp_name']), 400, 168);
        if($values['banner'] === null) {
            $error = 'An error occurred while uploading the banner.';
            goto showForm;
        }
    }
    foreach($_POST as $key => &$value) {
        switch($key) {
            case 'name':
                if(mb_strlen($value) > 127) {
                    $error = 'Your community\'s name is too long.';
                    goto showForm;
                }
                break;
            case 'description':
                if(mb_strlen($value) > 1024) {
                    $error = 'Your community\'s description is too long.';
                    goto showForm;
                }
                break;
            case 'permissions':
                if(strcasecmp($value, 'null')) {
                    continue 2;
                }
                if(!is_numeric($value) || $value < 0 || $value > 3) {
                    $error = 'Your community\'s permissions are invalid.';
                    goto showForm;
                }
                break;
            case 'privacy':
                if($value !== '0' && $value !== '1') {
                    $error = 'Your community\'s privacy is invalid.';
                }
                if((!isset($_POST['permissions']) || $_POST['permissions'] === 'null') && $value === '1') {
                    $_POST['permissions'] = '0';
                    $values['permissions'] = '0';
                }
                break;
            default:
                continue 2;
        }
        $values[$key] = $db->real_escape_string($value);
    }
    if(count($values) > 0) {
        $result = $db->query('INSERT INTO communities (' . implode(', ', array_keys($values)) . ') VALUES ("' . implode('", "', $values) . '")');
        if($db->error) {
            $error = 'An error occurred while creating your community.';
            goto showForm;
        }
        $id = $db->insert_id;
        $stmt = $db->prepare('INSERT INTO community_admins (user, community, level) VALUES (?, ?, 3)');
        $stmt->bind_param('ii', $_SESSION['id'], $id);
        $stmt->execute();
        if($stmt->error) {
            $error = 'An error occurred while adding yourself as an admin in your community.';
            goto showForm;
        }
        if($values['permissions'] === 0) {
            $stmt = $db->prepare('INSERT INTO community_members (user, community, status) VALUES (?, ?, 1)');
            $stmt->bind_param('ii', $_SESSION['id'], $id);
            $stmt->execute();
            if($stmt->error) {
                $error = 'An error occurred while adding yourself as a member of your community.';
                goto showForm;
            }
        }
        http_response_code(302);
        header('Location: /communities/' . $id);
    }
} else {
    showForm:
    $title = 'Create Community';
    require_once('inc/header.php');
    initUser($_SESSION['username'], true);
    ?><div class="main-column messages">
        <div class="post-list-outline">
            <h2 class="label">Create Community</h2>
            <form class="setting-form" action="/communities/create" method="post" enctype="multipart/form-data">
                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                <ul class="settings-list">
                    <?php if(!empty($error)) echo '<p class="post-tag post-topic-category symbol">' . $error . '</p>'; ?>
                    <li>
                        <p class="settings-label">Name</p>
                        <input type="text" class="url-form" name="name" maxlength="127" placeholder="The name of your community." required>
                    </li>
                    <li>
                        <p class="settings-label">Description</p>
                        <textarea class="textarea" name="description" maxlength="1024" placeholder="Write a description of your community here."></textarea>
                        <p class="note">The description of your community. This is optional, though it can help your community get found in search queries.</p>
                    </li>
                    <li>
                        <label class="file-button-container">
                            <span class="input-label">Icon <span>A square image with a 128x128 size is recommended.</span></span>
                            <input accept="image/*" type="file" name="icon" class="file-button">
                        </label>
                    </li>
                    <li>
                        <label class="file-button-container">
                            <span class="input-label">Banner <span>An image with a 400x168 size is recommended.</span></span>
                            <input accept="image/*" type="file" name="banner" class="file-button">
                        </label>
                    </li>
                    <li>
                        <p class="settings-label">Who should be able to post to your community?</p>
                        <div class="select-content">
                            <div class="select-button">
                                <select name="permissions">
                                    <option value="null" selected>Everybody</option>
                                    <option value="0">Community Members Only (minimum required for private communities)</option>
                                    <option value="1">Moderators+ Only</option>
                                    <option value="2">Admins+ Only</option>
                                    <option value="3">Owner Only</option>
                                </select>
                            </div>
                        </div>
                    </li>
                    <li>
                        <p class="settings-label">Should your community be private?</p>
                        <div class="select-content">
                            <div class="select-button">
                                <select name="privacy">
                                    <option value="1">Yes</option>
                                    <option value="0" selected>No</option>
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