<?php
require_once('inc/connect.php');
if(empty($_GET['id'])) {
    showError(400, 'You must specify a community ID.');
}
$stmt = $db->prepare('SELECT name, description, icon, banner, (SELECT username FROM users WHERE id = owner) AS owner_username, (SELECT nickname FROM users WHERE id = owner) AS owner_nickname, permissions, privacy, (SELECT COUNT(*) FROM community_favorites WHERE user = ? AND community = communities.id) AS favorite_given, (SELECT status FROM community_members WHERE user = ? AND community = communities.id) AS member_status FROM communities WHERE id = ? AND status = 0');
$stmt->bind_param('iii', $_SESSION['id'], $_SESSION['id'], $_GET['id']);
$stmt->execute();
if($stmt->error) {
    showError(500, 'An error occurred while grabbing the community from the database.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showError(404, 'The community could not be found.');
}
$row = $result->fetch_assoc();
$title = $row['name'];
$class = 'community-top';
require_once('inc/header.php');
?>
<div id="sidebar">
    <section class="sidebar-container" id="sidebar-community">
        <?php if(!empty($row['banner'])) { ?>
            <span id="sidebar-cover">
                <a href="/communities/<?=$_GET['id']?>"><img src="<?=htmlspecialchars($row['banner'])?>"></a>
            </span>
        <?php } ?>
        <header id="sidebar-community-body">
            <span id="sidebar-community-img">
                <span class="icon-container">
                    <a href="/communities/<?=$_GET['id']?>"><img src="<?=getCommunityIcon($row['icon'])?>" class="icon"></a>
                </span>
            </span>
            <h1 class="community-name"><a href="/communities/<?=$_GET['id']?>"><?=htmlspecialchars($row['name'])?></a></h1>
        </header>
        <?php if(!empty($row['description'])) { ?>
            <div class="community-description js-community-description">
                <?php if(mb_strlen($row['description']) > 103) { ?>
                    <p class="text js-truncated-text"><?=nl2br(htmlspecialchars(mb_substr($row['description'], 0, 100)))?>...</p>
                    <p class="text js-full-text none"><?=nl2br(htmlspecialchars($row['description']))?></p>
                    <button type="button" class="description-more-button js-open-truncated-text-button">Show More</button>
                <?php } else { ?>
                    <p class="text js-truncated-text"><?=nl2br(htmlspecialchars($row['description']))?></p>
                <?php } ?>
            </div>
        <?php }
        if(!empty($row['owner_username']) && ($row['privacy'] !== 1 || $row['member_status'] === 1)) {
            echo '<p class="user-name center">Owner: <a href="/users/' . htmlspecialchars($row['owner_username']) . '">' . htmlspecialchars($row['owner_nickname']) . '</a></p>';
        } ?>
        <div class="sidebar-setting">
            <?php if(!empty($_SESSION['username']) && ($row['privacy'] !== 1 || $row['member_status'] === 1)) { ?>
                <button type="button" class="symbol button favorite-button<?=$row['favorite_given'] === 1 ? ' checked' : ''?>" data-action-favorite="/communities/<?=$_GET['id']?>/favorite.json" data-action-unfavorite="/communities/<?=$_GET['id']?>/unfavorite.json">
                    <span class="favorite-button-text">Favorite</span>
                </button>
            <?php }
            if(!empty($_SESSION['username'])) {
                $banned = checkCommunityBan($_GET['id']);
                if($row['permissions'] === 0 && $banned === null) { ?>
                    <form method="post" action="/communities/<?=$_GET['id'] . ($row['member_status'] !== null ? '/leave' : '/join')?>">
                        <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                        <button type="submit" class="button">
                            <span><?=$row['member_status'] !== null ? ($row['member_status'] === 0 ? 'Cancel Request' : 'Leave Community') : 'Request to Join'?></span>
                        </button>
                    </form>
                <?php }
            } ?>
        </div>
    </section>
    <?php
    if(!empty($_SESSION['username'])) {
        $stmt = $db->prepare('SELECT level FROM community_admins WHERE user = ? AND community = ?');
        $stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
        $stmt->execute();
        if(!$stmt->error) {
            $result = $stmt->get_result();
            if($result->num_rows > 0) {
                $arow = $result->fetch_assoc();
                if($arow['level'] > 1) {
                    echo '<!--<a class="button" href="/communities/' . $_GET['id'] . '/edit">Edit Community</a>-->';
                }
            }
        }
    }
    ?>
    <!--<div class="sidebar-social-container social-buttons-container">
        <div class="social-buttons-content social-buttons-content-primary">
            <div class="social-buttons-content-cell facebook">
                <button type="button" class="button social-button facebook" data-service-name="facebook" data-is-popup="1" data-share-url="https://www.facebook.com/sharer.php?u=https%3A%2F%2Fmiiverse.nintendo.net%2Ftitles%2F14866558072985245728%2F14866558073038702637%3Ffb_ref%3Dscfbp&amp;display=popup" data-width="626" data-height="436">
                    <span class="social-button-inner symbol" style="background-image:url(https://pf2m.000webhostapp.com:443/mini.php?https://d13ph7xrk1ee39.cloudfront.net/img/button-facebook.png?X-blfws5L_C2q4El1QGkTQ);" alt="Share">Share</span>
                </button>
            </div>
        </div>
        <div class="social-buttons-content social-buttons-content-secondary">
            <div class="social-buttons-content-cell google">
                <button type="button" class="button social-button google" data-service-name="google" data-is-popup="1" data-share-url="https://plus.google.com/share?url=https%3A%2F%2Fmiiverse.nintendo.net%2Ftitles%2F14866558072985245728%2F14866558073038702637" data-width="500" data-height="500">
                    <span class="social-button-inner symbol" style="background-image:url(https://pf2m.000webhostapp.com:443/mini.php?https://d13ph7xrk1ee39.cloudfront.net/img/button-google.png?8MWVjQWfo_-Y9rC6SCvxqA);" alt="Google+">Google+</span>
                </button>
            </div>
            <div class="social-buttons-content-cell tumblr">
                <button type="button" class="button social-button tumblr" data-service-name="tumblr" data-is-popup="1" data-share-url="http://www.tumblr.com/share/link?url=https%3A%2F%2Fmiiverse.nintendo.net%2Ftitles%2F14866558072985245728%2F14866558073038702637%3Fsctm%3D1&amp;name=New%20Super%20Luigi%20U%20Community%20-%20Miiverse%20%7C%20Nintendo&amp;description=" data-width="450" data-height="430">
                    <span class="social-button-inner symbol" style="background-image:url(https://pf2m.000webhostapp.com:443/mini.php?https://d13ph7xrk1ee39.cloudfront.net/img/button-tumblr.png?EY_z6Qza95DsSqvzzVwLag);" alt="Tumblr">Tumblr</span>
                </button>
            </div>
        </div>
    </div>-->
</div>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label">Posts</h2>
        <?php if(!empty($_SESSION['username'])) {
            if($banned !== null) showNoContent('You have been banned from interacting with this community.<br>Ban ' . ($banned === 'Permanent' ? 'length: <strong>Permanent' : 'expiration date: <strong>' . $banned) . '</strong>');
            else if(($row['permissions'] === null || (($row['permissions'] === 0 && $row['member_status'] === 1) || ($row['permissions'] > 0 && $_SESSION['level'] > 0 && ($row['privacy'] === 0 || $row['member_status'] === 1)) || ($row['permissions'] > 0 && isset($brow) && $brow['level'] >= $row['permissions'])))) { ?>
                <form id="post-form" method="post" action="/posts" class="for-identified-user folded" data-post-subtype="default">
                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                    <input type="hidden" name="community" value="<?=$_GET['id']?>">
                    <div class="feeling-selector js-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>
                    <div class="textarea-with-menu">
                        <div class="textarea-container">
                            <textarea name="body" class="textarea-text textarea" maxlength="2000" placeholder="Share your thoughts in a post to this community." data-open-folded-form data-required></textarea>
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
            <?php }
        } ?>
        <div class="body-content" id="community-post-list">
            <?php
            if($row['privacy'] === 1 && ($row['member_status'] === null || $row['member_status'] === 0)) {
                showNoContent('You don\'t have permission to view this community.');
            } else {
                if(empty($_GET['offset'])) {
                    $_GET['offset'] = 0;
                }
                $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, users.level, community_admins.level AS community_level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN community_admins ON community_admins.user = users.id AND posts.community = community_admins.community WHERE posts.community = ? AND IF(users.level < ? OR (SELECT IFNULL(community_admins.level, 0) FROM community_admins WHERE user = ? AND community = posts.community LIMIT 1) < (SELECT IFNULL(community_admins.level, 0) FROM community_admins WHERE user = created_by AND community = posts.community LIMIT 1), 1, created_by NOT IN (SELECT target FROM blocks WHERE source = ? UNION SELECT source FROM blocks WHERE target = ?)) AND posts.status = 0 ORDER BY posts.id DESC LIMIT 20 OFFSET ?');
                $stmt->bind_param('iiiiiii', $_SESSION['id'], $_GET['id'], $_SESSION['level'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id'], $_GET['offset']);
                $stmt->execute();
                if($stmt->error) {
                    showNoContent('An error occurred while grabbing posts from the database.');
                }
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    if($_GET['offset'] == 0) {
                        showNoContent('No posts.');
                    }
                } else {
                    echo '<div class="list post-list js-post-list" data-next-page-url="?offset=' . ($_GET['offset'] + 20) . '">';
                    while($row = $result->fetch_assoc()) {
                        require('elements/post.php');
                    }
                    echo '</div>';
                }
            } ?>
        </div>
    </div>
</div>
<?php
showMiniFooter();
?>