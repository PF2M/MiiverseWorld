<?php
require_once('inc/connect.php');
$class = 'profile-top';
$urow = initUser($_GET['username']);
$is_profile = true;
?>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label">Recent Posts</h2>
        <?php
        $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE created_by = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY posts.id DESC LIMIT 3');
        $stmt->bind_param('iii', $_SESSION['id'], $urow['id'], $_SESSION['id']);
        $stmt->execute();
        if($stmt->error) {
            echo '<div class="post-list empty"><p>There was an error while fetching posts.</p></div>';
        } else {
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo '<div class="post-list empty"><p>This user hasn\'t posted anything yet.</p></div>';
            } else {
                ?><div class="post-body">
                    <div class="list multi-timeline-post-list">
                        <?php
                        while($row = $result->fetch_assoc()) {
                            require('elements/post.php');
                        }
                        ?>
                    </div>
                </div><?php
            }
        }
        ?>
    </div>
    <?php if($result->num_rows > 0) { ?><a href="/users/<?=htmlspecialchars($urow['username'])?>/posts" class="big-button">View Posts</a><?php } ?>
    <div class="post-list-outline">
        <h2 class="label">Recent Yeahs</h2>
        <?php
        $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community LEFT JOIN empathies ON posts.id = target AND type = 0 WHERE source = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY empathies.id DESC LIMIT 3');
        $stmt->bind_param('iii', $_SESSION['id'], $urow['id'], $_SESSION['id']);
        $stmt->execute();
        if($stmt->error) {
            echo '<div class="post-list empty"><p>There was an error while fetching posts.</p></div>';
        } else {
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                echo '<div class="post-list empty"><p>This user hasn\'t given a Yeah to any posts yet.</p></div>';
            } else {
                ?><div class="post-body">
                    <div class="list multi-timeline-post-list">
                        <?php
                        while($row = $result->fetch_assoc()) {
                            require('elements/post.php');
                        }
                        ?>
                    </div>
                </div><?php
            }
        }
        ?>
    </div>
    <?php if($result->num_rows > 0) { ?><a href="/users/<?=htmlspecialchars($urow['username'])?>/empathies" class="big-button">View Yeahs</a><?php } ?>
</div>
<?php
require_once('inc/footer.php');
?>