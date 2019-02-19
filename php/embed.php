<?php
require_once('inc/connect.php');
$stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, posts.status, username, nickname, avatar, has_mh, level, organization, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
$stmt->bind_param('iii', $_GET['id'], $_GET['id'], $_SESSION['id']);
$stmt->execute();
$result = $stmt->get_result();
?><!DOCTYPE html>
<html>
    <head>
        <title><?=$result->num_rows > 0 ? htmlspecialchars($row['nickname']) . '\'s post' : 'Error'?> - Miiverse World</title>
        <link rel="stylesheet" href="/assets/css/embedded.css">
        <script src="/assets/js/inframe.min.js"></script>
    </head>
    <body>
        <div id="post-content">
            <a href="/" target="_blank"><img id="service-logo" src="/assets/img/menu-logo.png" alt="Miiverse World" width="165" height="30"></a>
            <?php
            if($result->num_rows === 0) {
                showNoContent('The post could not be found.');
            } else {
                $row = $result->fetch_assoc();
                require_once('elements/post.php');
            }
            ?>
        </div>
    </body>
</html>