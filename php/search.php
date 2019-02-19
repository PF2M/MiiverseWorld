<?php
$title = 'Search';
require_once('inc/header.php');
if(empty($_GET['query']) && empty($_GET['tag'])) {
    showError(400, 'You must specify a search term.');
}
if(!empty($_GET['query']) && mb_strlen($_GET['query']) > 255) {
    showError(400, 'Your search is too long.');
}
if(!empty($_GET['tag']) && mb_strlen($_GET['tag']) > 20) {
    showError(400, 'Your tag is too long.');
}
if(empty($_GET['offset'])) {
    $_GET['offset'] = '0';
}
?>
<div id="sidebar">
    <form action="/search" class="search">
        <input type="text" name="query" placeholder="Search" maxlength="255"<?=(!empty($_GET['query']) ? ' value="' . htmlspecialchars($_GET['query']) . '">' : '>') . (!empty($_GET['tag']) ? '<input type="hidden" name="tag" value="' . htmlspecialchars($_GET['tag']) . '">' : '')?><input type="submit" value="q" title="Search">
    </form>
    <div class="post-list-outline">
        <h2 class="label"><?=!empty($_GET['tag']) ? 'Tag' : ''?> Users</h2>
        <?php
        $sql = 'SELECT id, username, nickname, avatar, has_mh, level, profile_comment, IF(favorite_post = NULL, NULL, (SELECT image FROM posts WHERE id = favorite_post)) AS favorite_post_image, users.status, (SELECT COUNT(*) FROM follows WHERE source = ? AND target = users.id) AS is_following';
        if(!empty($_GET['tag'])) {
            $sql .= ' FROM users WHERE (@tag_count := (SELECT COUNT(*) FROM posts LEFT JOIN communities ON communities.id = community WHERE (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) AND tags LIKE CONCAT("%,", ?, ",%") AND created_by = users.id AND posts.status = 0)) > 0 ORDER BY @tag_count';
            $query = str_replace(',', '%2C', str_replace(' ', '_', $_GET['tag']));
        } else {
            $sql .= ', (username COLLATE utf8mb4_unicode_520_ci = @query) * 1000 + (username COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query)) * 100 + (username COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")) * 100 + (nickname COLLATE utf8mb4_unicode_520_ci = @query) * 500 + (nickname COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query)) * 50 + IF(profile_comment IS NOT NULL, (profile_comment COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")) * 50, 0) + (SELECT COUNT(*) FROM posts LEFT JOIN communities ON communities.id = community WHERE created_by = users.id AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) AND body COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")) AS relevancy FROM users, (SELECT @query := ?) AS query HAVING relevancy > 0 ORDER BY relevancy';
            $query = $_GET['query'];
        }
        $stmt = $db->prepare($sql . ' DESC, id DESC LIMIT 3');
        $stmt->bind_param('iis', $_SESSION['id'], $_SESSION['id'], $query);
        $stmt->execute();
        if($stmt->error) {
            showError(500, 'An error occurred while grabbing users from the database.');
        }
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            echo showNoContent('No users found.');
        } else {
            echo '<ul class="list list-content-with-icon-and-text arrow-list tleft">';
            while($row = $result->fetch_assoc()) {
                require('elements/list-user.php');
            }
            echo '</ul>';
        }
        ?>
    </div>
    <ul class="sidebar-container list community-list community-card-list">
        <h2 class="label"><?=!empty($_GET['tag']) ? 'Tag' : ''?> Communities</h2>
        <?php
        $sql = 'SELECT id, name, icon, banner';
        if(!empty($_GET['tag'])) {
            $sql .= ' FROM communities WHERE IF(privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1, (@tag_count := (SELECT COUNT(*) FROM posts WHERE tags LIKE CONCAT("%,", ?, ",%") AND community = communities.id AND status = 0)), 0) > 0 ORDER BY @tag_count';
        } else {
            $sql .= ', (name COLLATE utf8mb4_unicode_520_ci = @query) * 1000 + (name COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query)) * 100 + (name COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")) * 100 + IF(description IS NOT NULL, (description COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")) * 50, 0) + IF(privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1, (SELECT COUNT(*) FROM posts WHERE community = communities.id AND posts.status = 0 AND body COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", @query, "%")), 0) AS relevancy FROM communities, (SELECT @query := ?) AS query HAVING relevancy > 0 ORDER BY relevancy';
        }
        $stmt = $db->prepare($sql . ' DESC, id DESC LIMIT 3');
        $stmt->bind_param('is', $_SESSION['id'], $query);
        $stmt->execute();
        if($stmt->error) {
            showError(500, 'An error occurred while grabbing communities from the database.');
        }
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            echo showNoContent('No communities found.');
        } else {
            while($row = $result->fetch_assoc()) {
                require('elements/list-community.php');
            }
        }
        ?>
    </ul>
</div>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label"><?=!empty($_GET['tag']) ? 'Tagged ' : ''?>Posts</h2>
        <?php
        // TODO: make this suck less (and maybe also the other ones too)
        $sql = 'SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE posts.status = 0';
        if(!empty($_GET['query'])) {
            $sql .= ' AND body COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%", "' . $db->real_escape_string($_GET['query']) . '", "%")';
        }
        if(!empty($_GET['tag'])) {
            $sql .= ' AND tags COLLATE utf8mb4_unicode_520_ci LIKE CONCAT("%,", "' . $db->real_escape_string($query) . '", ",%")';
        }
        $sql .= ' AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY ';
        if(!empty($_GET['hot'])) {
            $sql .= 'empathy_count + reply_count DESC, empathy_count';
        } else {
            $sql .= 'posts.id';
        }
        $stmt = $db->prepare($sql . ' DESC LIMIT 20 OFFSET ?');
        $stmt->bind_param('iii', $_SESSION['id'], $_SESSION['id'], $_GET['offset']);
        $stmt->execute();
        if($stmt->error) {
            showError(500, 'An error occurred while grabbing posts from the database.');
        }
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            if($_GET['offset'] === '0') {
                showNoContent('No posts found.');
            }
        } else {
            ?><div id="posts-filter-tab-container" class="tab-container ">
                <div class="tab2">
                    <a<?=empty($_GET['hot']) ? ' class="selected"' : ''?> href="/search?<?=htmlspecialchars(http_build_query(['tag' => (!empty($_GET['tag']) ? $_GET['tag'] : null), 'query' => (!empty($_GET['query']) ? $_GET['query'] : null)]))?>"><span class="new-posts">All Posts</span></a>
                    <a<?=!empty($_GET['hot']) ? ' class="selected"' : ''?> href="/search?<?=htmlspecialchars(http_build_query(['tag' => (!empty($_GET['tag']) ? $_GET['tag'] : null), 'query' => (!empty($_GET['query']) ? $_GET['query'] : null), 'hot' => '1']))?>">Popular Posts</a>
                </div>
            </div>
            <div class="body-content" id="community-post-list">
                <?php
                if(!empty($_GET['tag'])) {
                    echo '<div class="filtering-label-container"><span class="filtering-label"><p>Showing ' . (!empty($_GET['hot']) ? 'popular' : 'all') . ' posts tagged with <span class="tag-name symbol">' . decodeTag($_GET['tag']) . '.</span></p></span></div>';
                }
                echo '<div class="list post-list js-post-list" data-next-page-url="?offset=' . ($_GET['offset'] + 20) . '">';
                while($row = $result->fetch_assoc()) {
                    require('elements/post.php');
                }
                ?></div>
            </div>
        <?php } ?>
    </div>
</div>
<?php
require_once('inc/footer.php');
?>