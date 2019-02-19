<?php
$title = 'Communities';
if(empty($_SESSION['username'])) {
    $class = 'guest-top';
} else {
	$class = 'community-top';
}
require_once('inc/header.php');
if(empty($_SESSION['username'])) { ?>
    <div id="about">
        <div id="about-inner">
            <div id="about-text">
                <h2 class="welcome-message">Welcome to Miiverse World!</h2>
                <p>Miiverse World is a Miiverse clone experience that lets you discuss whatever you want with friends and community members alike. It adds lots of features like a customizable post feed, a discovery panel, and the best community creation and search systems of any clone!</p>
                <div class="guest-terms-content">
                    <a class="guest-terms-link symbol" href="/rules">Site Rules</a>
                </div>
            </div>
            <img src="/assets/img/welcome-image.png">
        </div>
    </div>
<?php } ?>
<div class="body-content" id="community-top" data-region="USA">
    <div class="community-main">
        <?php
        $stmt = $db->prepare('SELECT posts.id, posts.community, name, icon, owner_nickname, feeling, body, image, nickname, avatar, has_mh FROM posts LEFT JOIN users ON created_by = users.id INNER JOIN (SELECT community, name, icon, nickname AS owner_nickname FROM communities LEFT JOIN users ON users.id = owner LEFT JOIN posts ON communities.id = community AND posts.created_at > NOW() - INTERVAL 24 HOUR AND posts.status = 0 WHERE privacy = 0 AND communities.status = 0 GROUP BY communities.id ORDER BY COUNT(posts.id) DESC LIMIT 4) AS post_communities ON post_communities.community = posts.community WHERE posts.created_at > NOW() - INTERVAL 1 HOUR AND image IS NOT NULL AND sensitive_content = 0 AND posts.status = 0 AND IF(level < ? OR IF(posts.community IS NULL, 0, (SELECT IFNULL(level, 0) FROM community_admins WHERE user = ? AND community = posts.community LIMIT 1) < (SELECT IFNULL(level, 0) FROM community_admins WHERE user = created_by AND community = posts.community LIMIT 1)), 1, created_by NOT IN (SELECT target FROM blocks WHERE source = ? UNION SELECT source FROM blocks WHERE target = ?)) ORDER BY (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) DESC, id DESC LIMIT 10');
        $stmt->bind_param('iiii', $_SESSION['level'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id']);
        $stmt->execute();
        if(!$stmt->error) {
            $result = $stmt->get_result();
            if($result->num_rows > 0) { ?>
                <div id="community-eyecatch">
                    <div id="community-eyecatch-main">
                        <?php for($i = 1; $row = $result->fetch_assoc(); $i++) { ?>
                            <div class="eyecatch-diary-post js-eyecatch-diary-post" data-index="<?=$i?>">
                                <a href="/posts/<?=$row['id']?>" class="community-eyecatch-image" style="background-image: url('<?=htmlspecialchars($row['image'])?>')">
                                    <span class="icon-container">
                                        <img src="<?=getAvatar($row['avatar'], $row['has_mh'], $row['feeling'])?>" alt="<?=htmlspecialchars($row['nickname'])?>" class="icon community-eyecatch-usericon">
                                    </span>
                                    <p class="community-eyecatch-balloon"><span><?=getBody($row['body'], true)?></span></p>
                                </a>
                                <a href="/communities/<?=$row['community']?>" class="community-eyecatch-info">
                                    <img src="<?=getCommunityIcon($row['icon'])?>" width="40" height="40" class="community-eyecatch-infoicon">
                                    <h4 class="community-game-title" data-index="<?=$i?>"><?=htmlspecialchars($row['name'])?></h4>
                                    <?php if(!empty($row['owner_nickname'])) { ?>
                                        <p class="community-game-device">
                                            <span class="text"><?=htmlspecialchars($row['owner_nickname'])?></span>
                                        </p>
                                    <?php } ?>
                                </a>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            <?php }
        }
        if(!empty($_SESSION['username'])) { ?>
            <h3 class="community-title symbol community-favorite-title">Favorite Communities</h3>
            <?php
            $stmt = $db->prepare('SELECT communities.id, icon FROM communities LEFT JOIN community_favorites ON communities.id = community WHERE user = ? ORDER BY id DESC LIMIT 8');
            $stmt->bind_param('i', $_SESSION['id']);
            $stmt->execute();
            if(!$stmt->error) {
                $result = $stmt->get_result();
                if($result->num_rows > 0) { ?>
                    <div class="card" id="community-favorite">
                        <ul>
                            <?php while($frow = $result->fetch_assoc()) { ?>
                                <li class="favorite-community">
                                    <a href="/communities/<?=$frow['id']?>">
                                        <span class="icon-container">
                                            <img class="icon" src="<?=getCommunityIcon($frow['icon'])?>">
                                        </span>
                                    </a>
                                </li>
                            <?php }
                            for($i = $result->num_rows; $i < 8; $i++) { ?>
                                <li class="favorite-community empty">
                                    <span class="icon-container empty-icon">
                                        <img class="icon" src="/assets/img/empty.png">
                                    </span>
                                </li>
                            <?php } ?>
                            <li class="read-more">
                                <a href="/communities/favorites" class="favorite-community-link symbol"><span class="symbol-label">Show More</span></a>
                            </li>
                        </ul>
                    </div>
                <?php } else { ?>
                    <div class="no-content no-content-favorites">
            		    <div>
            		        <p>Tap the &#9734; button on a community's page to have it show up as a favorite community here.</p>
            		        <a href="/communities/favorites" class="favorite-community-link symbol"><span class="symbol-label">Show More</span></a>
                        </div>
                    </div>
                <?php }
            }
        } ?>
    </div>
    <div class="community-top-sidebar">
        <form method="GET" action="/search" class="search">
            <input type="text" name="query" placeholder="Search" maxlength="255"><input type="submit" value="q" title="Search">
        </form>
        <div id="identified-user-banner">
            <a href="/identified_user_posts" data-pjax="#body" class="list-button us">
                <span class="title">Get the latest news here!</span>
                <span class="text">Posts from Verified Users</span>
            </a>
        </div>
        <?php
        $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, feeling, body, image, yt, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE posts.created_at > NOW() - INTERVAL 1 HOUR AND image IS NULL AND sensitive_content = 0 AND posts.status = 0 AND IF(level < ? OR IF(community IS NULL, 0, (SELECT IFNULL(level, 0) FROM community_admins WHERE user = ? AND community = community LIMIT 1) < (SELECT IFNULL(level, 0) FROM community_admins WHERE user = created_by AND community = community LIMIT 1)), 1, created_by NOT IN (SELECT target FROM blocks WHERE source = ? UNION SELECT source FROM blocks WHERE target = ?)) ORDER BY (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) DESC, id DESC LIMIT 10');
        $stmt->bind_param('iiiii', $_SESSION['id'], $_SESSION['level'], $_SESSION['id'], $_SESSION['id'], $_SESSION['id']);
        $stmt->execute();
        if(!$stmt->error) {
            $result = $stmt->get_result();
            if($result->num_rows > 0) { ?>
                <div class="digest-container">
                    <h3 class="community-title">Featured Posts</h3>
                    <div class="digest">
                        <?php while($row = $result->fetch_assoc()) require('elements/post.php'); ?>
                    </div>
                </div>
            <?php }
        } ?>
    </div>
    <div class="community-main">
        <?php
        $result = $db->query('SELECT communities.id, name, icon, banner, nickname FROM communities LEFT JOIN users ON users.id = owner LEFT JOIN posts ON communities.id = community AND posts.created_at > NOW() - INTERVAL 24 HOUR AND posts.status = 0 WHERE privacy = 0 AND communities.status = 0 GROUP BY communities.id ORDER BY COUNT(posts.id) DESC LIMIT 4');
        if(!$db->error && $result->num_rows > 0) { ?>
            <h3 class="community-title symbol">Popular Communities</h3>
            <div>
                <ul class="list community-list community-card-list">
                    <?php while($row = $result->fetch_assoc()) { ?>
                        <li class="trigger" data-href="/communities/<?=$row['id']?>" tabindex="0">
                            <?php if(!empty($row['banner'])) { ?><img src="<?=htmlspecialchars($row['banner'])?>" class="community-list-cover"><?php } ?>
                            <div class="community-list-body">
                                <span class="icon-container"><img src="<?=getCommunityIcon($row['icon'])?>" class="icon"></span>
                                <div class="body">
                                    <a class="title" href="/communities/<?=$row['id']?>" tabindex="-1"><?=htmlspecialchars($row['name'])?></a>
                                    <?php if(!empty($row['nickname'])) { ?><span class="text"><?=htmlspecialchars($row['nickname'])?></span><?php } ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
            </div>
        <?php }
        $result = $db->query('SELECT communities.id, name, icon, nickname FROM communities LEFT JOIN users ON users.id = owner WHERE privacy = 0 AND communities.status = 0 ORDER BY communities.id DESC LIMIT 6');
        if(!$db->error && $result->num_rows > 0) { ?>
            <h3 class="community-title symbol">New Communities</h3>
            <div>
                <ul class="list community-list community-card-list device-new-community-list">
                    <?php while($row = $result->fetch_assoc()) { ?>
                        <li class="trigger" data-href="/communities/<?=$row['id']?>" tabindex="0">
                            <div class="community-list-body">
                                <span class="icon-container"><img src="<?=getCommunityIcon($row['icon'])?>" class="icon"></span>
                                <div class="body">
                                    <a class="title" href="/communities/<?=$row['id']?>" tabindex="-1"><?=htmlspecialchars($row['name'])?></a>
                                    <?php if(!empty($row['nickname'])) { ?><span class="text"><?=htmlspecialchars($row['nickname'])?></span><?php } ?>
                                </div>
                            </div>
                        </li>
                    <?php } ?>
                </ul>
                <?php if(!empty($_SESSION['username'])) { ?><a href="/discover" class="big-button">Find Communities</a><?php } ?>
            </div>
        <?php }
        ?>
        <div id="community-guide-footer">
            <div id="guide-menu">
                <a href="https://github.com/PF2M/MiiverseWorld" class="arrow-button"><span>GitHub</span></a>
                <a href="/rules" class="arrow-button"><span>Site Rules</span></a>
                <a href="https://pf2m.com/contact/" class="arrow-button"><span>Contact Us</span></a>
            </div>
        </div>
    </div>
</div>
<?php
require_once('inc/footer.php');
?>