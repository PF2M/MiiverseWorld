<?php
$title = 'Home';
$selected = 'community';
require_once('inc/header.php');
if(empty($_GET['offset'])) {
    $_GET['offset'] = '0';
}
if($_GET['offset'] === '0') { ?>
    <div class="community-top-sidebar">
        <form action="/search" class="search">
            <input type="text" name="query" placeholder="Search" maxlength="255"><input type="submit" value="q" title="Search">
        </form>
        <div class="post-list-outline general-sidebar">
            <h2 class="label">Recommended Users</h2>
            <?php
            $stmt = $db->prepare('SELECT id, username, nickname, avatar, has_mh, level, profile_comment, IF(favorite_post = NULL, NULL, (SELECT image FROM posts WHERE id = favorite_post)) AS favorite_post_image, 0 AS status, 0 AS is_following FROM users WHERE id NOT IN (SELECT target FROM follows WHERE source = ?) AND id != ? AND status = 0 ORDER BY (SELECT COUNT(*) FROM follows WHERE source = users.id AND target IN (SELECT target FROM follows WHERE source = ?)) DESC, (SELECT COUNT(*) FROM follows WHERE target = users.id) DESC, id ASC LIMIT 5');
            $stmt->bind_param('iii', $_SESSION['id'], $_SESSION['id'], $_SESSION['id']);
            $stmt->execute();
            if($stmt->error) {
                showNoContent('An error occurred while fetching users.');
            } else {
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    showNoContent('You\'ve followed all the users you can for now.');
                } else {
                    echo '<ul class="list list-content-with-icon-and-text arrow-list tleft">';
                    while($row = $result->fetch_array()) {
                        require('elements/list-user.php');
                    }
                    echo '<a class="more-button trigger" href="/discover">More Recommendations</a></ul>';
                }
            }
        echo '</div>';
        $time = microtime();
        $result = $db->query('SELECT tags FROM posts WHERE created_at >= NOW() - INTERVAL 1 DAY AND tags IS NOT NULL AND status = 0'); // TODO: possibly rewrite this in pure SQL and/or cache it???
        if($result->num_rows > 0) {
            ?><div class="post-list-outline general-sidebar">
                <h2 class="label">Trending Tags</h2>
                <ul class="list tleft">
                    <?php
                    $trends = [];
                    while($row = $result->fetch_assoc()) {
                        foreach(explode(',', strtolower(mb_substr($row['tags'], 1, -1))) as &$tag) {
                            if(isset($trends[$tag])) {
                                $trends[$tag] += 1;
                            } else {
                                $trends[$tag] = 1;
                            }
                        }
                    }
                    arsort($trends);
                    foreach(array_slice($trends, 0, 10, true) as $tag => &$count) {
                        echo '<a class="trigger" href="/search?tag=' . htmlspecialchars(urlencode($tag)) . '"><p class="post-tag symbol">' . decodeTag($tag) . '</p><p class="timestamp-container">' . number_format($count) . ' posts</p></a>';
                    }
                    unset($trends);
                    ?>
                </ul>
            </div>
        <?php } ?>
    </div>
<?php } ?>
<div class="community-main community-top post-list-outline">
    <form id="post-form" method="post" action="/posts" class="for-identified-user folded" data-post-subtype="default">
        <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
        <div class="feeling-selector js-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>
        <div class="textarea-with-menu">
            <div class="textarea-container">
                <textarea name="body" class="textarea-text textarea" maxlength="2000" placeholder="Share your thoughts in a post to Miiverse World." data-open-folded-form data-required></textarea>
            </div>
        </div>
        <details class="select-from-album-button headline">
            <a class="right" data-modal-open="#about-tags"><strong>Help</strong></a>
            <div id="about-tags" class="dialog none">
                <div class="dialog-inner">
                    <div class="window">
                        <h1 class="window-title">About Tags</h1>
                        <div class="window-body">
                            <p class="window-body-content">A post can optionally have up to 20 tags, each of up to 20 characters of length. These tags will be displayed on your post's page, and are useful in helping people interested in these topics find your post. To add tags to your post, simply enter a comma-seperated list of tags (spaces after commas are optional) such as "art,yosafire,the gray garden". Please note that using tags deceptively may get your post removed.</p>
                            <div class="form-buttons">
                                <input type="button" class="olv-modal-close-button black-button" value="Close">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <summary class="timestamp-container left"><p class="post-tag post-official-tag symbol left">Tags</p></summary>
            <input type="text" class="textarea-line url-form" maxlength="438" name="tags" placeholder="A list of tags, seperated by commas">
        </details>
        <label class="file-button-container">
            <span class="input-label">Image
                <span>PNG, JPEG and GIF files are allowed.</span>
            </span>
            <input accept="image/*" type="file" class="file-button">
            <input type="hidden" name="image">
        </label>
        <div class="post-form-footer-options">
            <div class="post-form-footer-option-inner post-form-spoiler js-post-form-spoiler">
                <label class="spoiler-button symbol"><input type="checkbox" name="sensitive_content" value="1"> Sensitive</label>
            </div>
        </div>
        <div class="form-buttons">
            <input type="submit" class="black-button post-button disabled" value="Send" data-community-id="1" data-post-content-type="text" data-post-with-screenshot="nodata" disabled>
        </div>
    </form>
    <div class="body-content" id="community-post-list">
        <?php
        $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE ((created_by IN (SELECT target FROM follows WHERE source = ?) OR created_by = ?) OR (community IN (SELECT community FROM community_favorites WHERE user = ?))) AND IF(level < ? OR IF(community IS NULL, 0, (SELECT IFNULL(level, 0) FROM community_admins WHERE user = ? AND community = community LIMIT 1) < (SELECT IFNULL(level, 0) FROM community_admins WHERE user = created_by AND community = community LIMIT 1)), 1, created_by NOT IN (SELECT target FROM blocks WHERE source = ? UNION SELECT source FROM blocks WHERE target = ?)) AND posts.status = 0 AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1) ORDER BY posts.id DESC LIMIT 20 OFFSET ?');
        $stmt->bind_param('iiiiiiiiii', $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['level'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_GET['offset']);
        $stmt->execute();
        if($stmt->error) {
            showNoContent('An error occurred while grabbing posts.');
        } else {
            $result = $stmt->get_result();
            if($result->num_rows === 0) {
                if($_GET['offset'] === '0') {
                    ?><div class="no-content">
                        <p>Your feed is currently empty. Why not follow some people?<br>You can find some users to follow in your recommendations.</p>
                    </div><?php
                }
            } else {
                echo '<div class="list post-list js-post-list" data-next-page-url="?offset=' . ($_GET['offset'] + 20) . '">';
                $_GET['type'] = null;
                while($row = $result->fetch_assoc()) {
                    require('elements/post.php');
                }
                echo '</div>';
            }
        }
        ?>
    </div>
</div><?php
showMiniFooter();
?>
