<?php
require_once('inc/connect.php');
requireAuth();
initUser($_SESSION['username'], true);
?>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label">Blocked Users</h2>
        <div class="list follow-list">
            <ul class="list-content-with-icon-and-text arrow-list" data-next-page-url="<?php
                $offset = (!empty($_GET['offset']) ? $_GET['offset'] : 0);
                $stmt = $db->prepare('SELECT users.id, username, nickname, avatar, has_mh, level, profile_comment, -1 AS is_following FROM users LEFT JOIN blocks ON users.id = target WHERE source = ? ORDER BY blocks.id DESC LIMIT 20 OFFSET ?');
                $stmt->bind_param('ii', $_SESSION['id'], $offset);
                $stmt->execute();
                if($stmt->error && $offset === 0) {
                    echo '">';
                    showNoContent('An error occurred while grabbing your blocked users.');
                } else {
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) {
                        echo '">';
                        if($offset === 0) {
                            showNoContent('You haven\'t blocked anyone yet.');
                        }
                    } else {
                        echo '?offset=' . ($offset + 20) . '">';
                        while($row = $result->fetch_assoc()) {
                            require('elements/list-user.php');
                        }
                    }
                }
                ?>
            </ul>
            <div id="block-page" class="dialog none" data-modal-types="report report-violation" data-is-template="1">
                <div class="dialog-inner">
                    <div class="window">
                        <h1 class="window-title">Unblock this User</h1>
                        <div class="window-body">
                            <form method="post">
                                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                <p class="window-body-content">Are you sure you want to unblock this user?</p>
                                <div class="form-buttons">
                                    <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                    <input type="submit" class="post-button black-button" value="Unblock">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php
showMiniFooter();
?>