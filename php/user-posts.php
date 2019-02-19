<?php
require_once('inc/connect.php');
$urow = initUser($_GET['username']);
$type = ($_GET['type'] === 'empathies' ? 1 : 0);
?>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label"><?=($_SESSION['id'] === $urow['id'] ? 'Your ' : htmlspecialchars($urow['nickname']) . '\'s ') . ($type === 1 ? 'Yeahs' : 'Posts')?></h2>
        <div class="list post-list js-post-list" data-next-page-url="<?php
            $offset = (!empty($_GET['offset']) ? $_GET['offset'] : '0');
            if($type === 1) {
                $stmt = $db->prepare('SELECT posts.id, NULL AS post, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added, empathies.id AS empathy_id FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community LEFT JOIN empathies ON target = posts.id AND type = 0 WHERE source = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) UNION SELECT replies.id, post, replies.created_by, replies.created_at, 0, CONCAT("Comment on ", (SELECT nickname FROM users LEFT JOIN posts ON created_by = users.id WHERE posts.id = post), "\'s post"), IF((SELECT has_mh FROM users LEFT JOIN posts ON created_by = users.id WHERE posts.id = post) = 0, (SELECT avatar FROM users LEFT JOIN posts ON created_by = users.id WHERE posts.id = post), CONCAT("https://mii-secure.cdn.nintendo.net/", (SELECT avatar FROM users LEFT JOIN posts ON created_by = users.id WHERE posts.id = post), "_normal_face.png")), replies.feeling, replies.body, replies.image, null, replies.sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1), -1, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1 AND source = ?), empathies.id FROM replies LEFT JOIN users ON created_by = users.id LEFT JOIN posts ON posts.id = post LEFT JOIN communities ON communities.id = community LEFT JOIN empathies ON target = replies.id AND type = 1 WHERE source = ? AND replies.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY empathy_id DESC LIMIT 20 OFFSET ?');
                $stmt->bind_param('iiiiiii', $_SESSION['id'], $urow['id'], $_SESSION['id'], $_SESSION['id'], $urow['id'], $_SESSION['id'], $offset);
            } else {
                $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE created_by = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY posts.id DESC LIMIT 20 OFFSET ?');
                $stmt->bind_param('iiii', $_SESSION['id'], $urow['id'], $_SESSION['id'], $offset);
            }
            $stmt->execute();
            if($stmt->error && $offset === '0') {
                echo '">';
                showNoContent('There was an error while fetching the user\'s ' . ($type === 1 ? 'Yeahs' : 'posts') . '.');
            } else {
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    echo '">';
                    if($offset === '0') {
                        showNoContent('This user hasn\'t ' . ($type === 1 ? 'given any Yeahs' : 'posted anything') . ' yet.');
                    }
                } else {
                    echo '?offset=' . ($offset + 20) . '">';
                    while($row = $result->fetch_assoc()) {
                        require('elements/post.php');
                    }
                }
            }
            ?>
        </div>
    </div>
</div>
<?php
showMiniFooter();
?>