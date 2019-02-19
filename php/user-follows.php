<?php
require_once('inc/connect.php');
$urow = initUser($_GET['username']);
$type = ($_GET['type'] === 'followers' ? 1 : 0);
?>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label"><?=$_SESSION['id'] === $urow['id'] ? ($type === 1 ? 'Your Followers' : 'Users You\'re Following') : ($type === 1 ? htmlspecialchars($urow['nickname']) . '\'s Followers' : 'Users ' . htmlspecialchars($urow['nickname']) . ' is Following')?></h2>
        <div class="list follow-list <?=htmlspecialchars($_GET['type'])?>">
            <ul class="list-content-with-icon-and-text arrow-list" id="friend-list-content" data-next-page-url="<?php
                $offset = (!empty($_GET['offset']) ? $_GET['offset'] : 0);
                if($type === 1) {
                    $stmt = $db->prepare('SELECT users.id, username, nickname, avatar, has_mh, level, profile_comment, IF(favorite_post = NULL, NULL, (SELECT image FROM posts WHERE id = favorite_post)) AS favorite_post_image, status, (SELECT COUNT(*) FROM follows WHERE source = ? AND target = users.id) AS is_following FROM users LEFT JOIN follows ON users.id = source WHERE target = ? ORDER BY follows.id DESC LIMIT 20 OFFSET ?');
                } else {
                    $stmt = $db->prepare('SELECT users.id, username, nickname, avatar, has_mh, level, profile_comment, IF(favorite_post = NULL, NULL, (SELECT image FROM posts WHERE id = favorite_post)) AS favorite_post_image, status, (SELECT COUNT(*) FROM follows WHERE source = ? AND target = users.id) AS is_following FROM users LEFT JOIN follows ON users.id = target WHERE source = ? ORDER BY follows.id DESC LIMIT 20 OFFSET ?');
                }
                $stmt->bind_param('iii', $_SESSION['id'], $urow['id'], $offset);
                $stmt->execute();
                if($stmt->error && $offset === 0) {
                    echo '"><div class="no-content"><p>There was an error while fetching the user\'s ' . htmlspecialchars($_GET['type']) . '.</p></div>';
                } else {
                    $result = $stmt->get_result();
                    if($result->num_rows === 0) {
                        echo '">';
                        if($offset === 0) {
                            echo '<div class="no-content"><p>This user has' . ($type === 1 ? ' no followers' : 'n\'t followed anyone') . ' yet.</p></div>';
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
        </div>
    </div>
</div>
<?php
showMiniFooter();
?>