<div id="<?=htmlspecialchars($row['id'])?>" data-href<?=($row['sensitive_content'] && $row['created_by'] !== $_SESSION['id'] ? '-hidden' : '') . '="/' . ($row['reply_count'] > -1 ? 'posts/' : 'replies/') . htmlspecialchars($row['id'])?>" class="post trigger<?=($row['sensitive_content'] && $row['created_by'] !== $_SESSION['id'] ? ' hidden' : '') . ($row['reply_count'] <= -1 && $_GET['type'] !== 'empathies' ? ($row['created_by'] === $row['op'] ? ' my' : ' other') : '') . (isset($is_profile) && !empty($row['image']) ? ' with-image' : '')?>" tabindex="0">
    <?php if(isset($row['community']) && $row['community'] !== null) { ?>
        <p class="community-container">
            <a href="/<?=$row['community'] !== 0 ? 'communities/' . $row['community'] : 'posts/' . $row['post']?>"><img src="<?=getCommunityIcon($row['icon'])?>" class="community-icon"><?=htmlspecialchars($row['name'])?></a>
        </p>
    <?php }
    if(isset($is_profile)) echo '<div class="body"><div class="post-content">'; ?>
    <a href="/users/<?=htmlspecialchars($row['username'])?>" class="icon-container<?=$row['level'] > 0 ? ' official-user' : ''?>">
        <img src="<?=getAvatar($row['avatar'], $row['has_mh'], $row['feeling'])?>" class="icon">
    </a>
    <?php if(!empty($row['community_level'])) {
        echo '<p class="user-organization right">';
        switch($row['community_level']) {
            case 1:
                echo 'Moderator';
                break;
            case 3:
                echo 'Owner';
                break;
            default:
                echo 'Administrator';
        }
        echo '</p>';
    } ?>
    <p class="user-name"><a href="/users/<?=htmlspecialchars($row['username'])?>"><?=htmlspecialchars($row['nickname'])?></a></p>
    <p class="timestamp-container">
        <a class="timestamp" href="/<?=($row['reply_count'] > -1 ? 'posts/' : 'replies/') . htmlspecialchars($row['id']) . '">' . getTimestamp($row['created_at'])?></a>
        <span class="spoiler-status<?=$row['sensitive_content'] ? ' spoiler' : ''?>">&#775; Sensitive</span>
    </p>
    <?=!isset($is_profile) ? '<div class="body ' . ($row['reply_count'] > -1 && (!isset($_GET['type']) || $_GET['type'] !== 'empathies') ? 'post' : 'reply') . '-content">' : ''?>
        <?php if(!empty($row['yt'])) { ?>
            <a href="/posts/<?=htmlspecialchars($row['id'])?>" class="screenshot-container video">
                <img height="48" src="https://i.ytimg.com/vi/<?=htmlspecialchars($row['yt'])?>/default.jpg">
            </a>
        <?php } ?>
        <p class="<?=$row['reply_count'] !== -1 || (isset($_GET['type']) && $_GET['type'] === 'empathies') ? 'post' : 'reply'?>-content-text"><?=nl2br(getBody($row['body'], ($row['reply_count'] !== -1 || (isset($_GET['type']) && $_GET['type'] === 'empathies') ? true : false)))?></p>
        <?php if(!empty($row['image'])) { ?>
            <?php if(!isset($is_profile)) { ?><br class="screenshot-container video none"><?php } ?>
            <div class="screenshot-container still-image">
                <img src="<?=htmlspecialchars($row['image'])?>">
            </div>
        <?php }
        if($row['sensitive_content'] && $row['created_by'] !== $_SESSION['id']) { ?>
            <div class="hidden-content">
                <p>This <?=$row['reply_count'] !== -1 ? 'post' : 'reply'?> may contain sensitive content.</p>
                <button type="button" class="hidden-content-button">View <?=$row['reply_count'] !== -1 ? 'Post' : 'Reply'?></button>
            </div>
        <?php } ?>
        <div class="<?=$row['reply_count'] !== -1 || $_GET['type'] === 'empathies' ? 'post' : 'reply'?>-meta">
            <button type="button" class="symbol submit empathy-button<?=$row['empathy_added'] ? ' empathy-added' : ''?>"<?=$row['created_by'] === $_SESSION['id'] ? ' disabled' : ''?> data-feeling="<?=getFeelingName($row['feeling'])?>" data-action="/<?=($row['reply_count'] !== -1 ? 'posts/' : 'replies/') . htmlspecialchars($row['id'])?>/empathies" data-url-id="<?=htmlspecialchars($row['id']) . ($row['reply_count'] === -1 && $_GET['type'] !== 'empathies' ? '" data-is-in-reply-list="1"' : '"')?>>
                <span class="empathy-button-text"><?=$row['empathy_added'] ? 'Unyeah' : getEmpathyText($row['feeling'])?></span>
            </button>
            <div class="empathy symbol"><span class="symbol-label">Yeahs</span><span class="empathy-count"><?=htmlspecialchars($row['empathy_count'])?></span></div>
            <?php if($row['reply_count'] !== -1) { ?><div class="reply symbol"><span class="symbol-label">Replies</span><span class="reply-count"><?=htmlspecialchars($row['reply_count'])?></span></div><?php } ?>
        </div>
        <?php if($row['reply_count'] > 0 && !isset($is_profile)) {
            $stmt = $db->prepare('SELECT replies.id, replies.created_at, feeling, body, username, nickname, avatar, has_mh, level FROM replies LEFT JOIN users ON created_by = users.id WHERE post = ? AND created_by != ? AND replies.status = 0 ORDER BY id DESC LIMIT 1');
            $stmt->bind_param('ii', $row['id'], $row['created_by']);
            $stmt->execute();
            $rresult = $stmt->get_result();
            if($rresult->num_rows > 0) {
                echo '<div class="recent-reply-content">';
                    if($row['reply_count'] > 1) {
                        echo '<div class="recent-reply-read-more-container" tabindex="0">View all comments (' . $row['reply_count'] . ')</div>';
                    }
                    $row = $rresult->fetch_assoc();
                    ?>
                    <div id="<?=htmlspecialchars($row['id'])?>" tabindex="0" class="recent-reply trigger">
                        <a href="/users/<?=htmlspecialchars($row['username'])?>" class="icon-container<?=$row['level'] > 0 ? ' official-user' : ''?>"><img class="icon" src="<?=getAvatar($row['avatar'], $row['has_mh'], $row['feeling'])?>"></a>
                        <p class="user-name"><a href="/users/<?=htmlspecialchars($row['username'])?>"><?=htmlspecialchars($row['nickname'])?></a></p>
                        <p class="timestamp-container">
                            <a class="timestamp" href="/comments/<?=htmlspecialchars($row['id'])?>"><?=getTimestamp($row['created_at'])?></a>
                        </p>
                        <div class="body">
                            <div class="post-content">
                                <p class="recent-reply-content-text"><?=htmlspecialchars($row['body'])?></p>
                            </div>
                        </div>
                    </div>
                </div><?php
            }
        }
        if(isset($is_profile)) {
            echo '</div>';
        } ?>
    </div>
</div>