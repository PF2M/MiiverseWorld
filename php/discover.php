<?php
require_once('inc/connect.php');
requireAuth();
$title = 'Discover';
$selected = 'feed';
require_once('inc/header.php');
?>
<div id="sidebar">
    <ul class="sidebar-container list community-list community-card-list">
        <h2 class="label">Recommended Communities</h2>
        <?php
        $stmt = $db->prepare('SELECT id, name, icon, banner FROM communities WHERE id NOT IN (SELECT community FROM community_favorites WHERE user = ?) AND id NOT IN (SELECT community FROM community_members WHERE user = ?) AND status = 0 ORDER BY (SELECT COUNT(*) FROM community_favorites WHERE user IN (SELECT user FROM community_favorites WHERE community IN (SELECT community FROM community_favorites WHERE user = ?)) AND community = communities.id UNION SELECT user FROM community_members WHERE user IN (SELECT user FROM community_members WHERE community IN (SELECT community FROM community_members WHERE user = ?)) AND community = communities.id AND status = 1) DESC, (SELECT COUNT(*) FROM community_favorites WHERE community = communities.id) DESC, id ASC LIMIT 5');
        $stmt->bind_param('iiii', $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id']);
        $stmt->execute();
        if($stmt->error) {
            showNoContent('An error occurred while fetching communities.');
        } else {
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                showNoContent('You\'ve added all the communities you can for now.');
            } else {
                while($row = $result->fetch_array()) {
                    require('elements/list-community.php');
                }
            }
        }
        ?>
    </ul>
</div>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label">Recommended Users</h2>
        <ul class="list list-content-with-icon-and-text arrow-list" data-next-page-url="<?php
            if(empty($_GET['offset']) || !is_numeric($_GET['offset'])) {
                $_GET['offset'] = '0';
            }
            $stmt = $db->prepare('SELECT id, username, nickname, avatar, has_mh, level, profile_comment, IF(favorite_post = NULL, NULL, (SELECT image FROM posts WHERE id = favorite_post)) AS favorite_post_image, 0 AS status, 0 AS is_following FROM users WHERE id NOT IN (SELECT target FROM follows WHERE source = ?) AND id != ? AND status = 0 ORDER BY (SELECT COUNT(*) FROM follows WHERE source = users.id AND target IN (SELECT target FROM follows WHERE source = ?)) DESC, (SELECT COUNT(*) FROM follows WHERE target = users.id) DESC, id ASC LIMIT 20 OFFSET ?');
            $stmt->bind_param('iiii', $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_GET['offset']);
            $stmt->execute();
            if($stmt->error && $_GET['offset'] === '0') {
                showNoContent('An error occurred while fetching users.');
                echo '">';
            } else {
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    if($_GET['offset'] === '0') {
                        showNoContent('You\'ve followed all the users you can for now.');
                    }
                    echo '">';
                } else {
                    echo '?offset=' . ($_GET['offset'] + 20) . '">';
                    while($row = $result->fetch_array()) {
                        require('elements/list-user.php');
                    }
                }
            }
            ?>
        </ul>
    </div>
</div>
<?php
require_once('inc/footer.php');
?>