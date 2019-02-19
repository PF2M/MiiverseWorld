<li class="trigger" data-href="/users/<?=htmlspecialchars($row['username'])?>">
    <a href="/users/<?=htmlspecialchars($row['username'])?>" class="icon-container<?=$row['level'] > 0 ? ' official-user' : ''?>">
        <img src="<?=getAvatar($row['avatar'], $row['has_mh'], 0)?>" class="icon">
    </a>
    <?php if(($row['is_following'] === 0 && $row['status'] === 0 && $_SESSION['id'] !== $row['id']) || ($row['is_following'] > 0 && $row['status'] > 0)) { ?>
        <div class="toggle-button">
            <button type="button" class="<?=$row['status'] > 0 ? 'un' : ''?>follow-button button symbol relationship-button" data-action="/users/<?=htmlspecialchars($row['username']) . ($row['status'] > 0 ? '.un' : '.')?>follow.json">Follow</button>
        </div>
    <?php } else if($row['is_following'] === -1) { ?>
        <div class="toggle-button">
            <button type="button" class="button unfollow-button symbol" data-action="/users/<?=htmlspecialchars($row['username'])?>/blacklist.delete.json" data-modal-open="#block-page">Unblock</button>
        </div>
    <?php } ?>
    <div class="body">
        <p class="title">
            <span class="nick-name"><a href="/users/<?=htmlspecialchars($row['username'])?>"><?=htmlspecialchars($row['nickname'])?></a></span>
            <span class="id-name"><?=htmlspecialchars($row['username'])?></span>
        </p>
        <?php if(!empty($row['profile_comment'])) { ?><p class="text"><?=htmlspecialchars($row['profile_comment'])?></p><?php }
        if(!empty($row['favorite_post_image'])) { ?><div class="user-profile-memo-content"><img src="<?=htmlspecialchars($row['favorite_post_image'])?>" class="user-profile-memo"></div><?php } ?>
    </div>
</li>