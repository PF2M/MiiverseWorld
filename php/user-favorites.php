<?php
require_once('inc/connect.php');
$row = initUser($_GET['username']);
?>
<div class="main-column">
    <div class="post-list-outline">
        <h2 class="label"><?=$_SESSION['id'] === $row['id'] ? 'Your' : htmlspecialchars($row['nickname']) . '\'s'?> Favorite Communities</h2>
        <ul class="list community-list"<?php
            $offset = (!empty($_GET['offset']) ? $_GET['offset'] : 0);
            $stmt = $db->prepare('SELECT communities.id, name, icon, IF(privacy = 0, nickname, IF((SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) > 0, nickname, NULL)) AS owner_nickname FROM communities LEFT JOIN community_favorites ON communities.id = community LEFT JOIN users ON owner = users.id WHERE user = ? AND communities.status = 0 ORDER BY community_favorites.id DESC LIMIT 20 OFFSET ?');
            $stmt->bind_param('iii', $_SESSION['id'], $row['id'], $offset);
            $stmt->execute();
            if($stmt->error && $offset === 0) {
                echo '>';
                showNoContent('An error occurred while fetching the user\'s favorites.');
            } else {
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    echo '>';
                    if($offset === 0) {
                        showNoContent('This user hasn\'t favorited any communities yet.');
                    }
                } else {
                    echo ' data-next-page-url="?offset=' . ($offset + 20) . '">';
                    while($row = $result->fetch_assoc()) { ?>
                        <li class="trigger" data-href="/communities/<?=htmlspecialchars($row['id'])?>" tabindex="0">
                            <div class="community-list-body">
                                <span class="icon-container"><img src="<?=htmlspecialchars($row['icon'])?>" class="icon"></span>
                                <div class="body">
                                    <a class="title" href="/communities/<?=htmlspecialchars($row['id'])?>" tabindex="-1"><?=htmlspecialchars($row['name'])?></a>
                                    <?php if($row['owner_nickname'] !== null) { ?><span class="text"><?=htmlspecialchars($row['owner_nickname'])?></span><?php } ?>
                                </div>
                            </div>
                        </li>
                    <?php }
                }
            } ?>
        </ul>
    </div>
</div>
<?php
showMiniFooter();
?>