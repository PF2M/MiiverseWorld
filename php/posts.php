<?php
require_once('inc/connect.php');

$type = ($_GET['type'] === 'replies' ? 1 : 0);
$object = ($type === 1 ? 'reply' : 'post');
if($type === 1) {
    $stmt = $db->prepare('SELECT replies.id, post, created_by, replies.created_at, feeling, body, image, sensitive_content, replies.status, username, nickname, avatar, has_mh, level, organization, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1) AS empathy_count, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1 AND source = ?) AS empathy_added FROM replies LEFT JOIN users ON created_by = users.id WHERE replies.id = ?');
    $stmt->bind_param('ii', $_SESSION['id'], $_GET['id']);
} else {
    $stmt = $db->prepare('SELECT posts.id, created_by, posts.created_at, community, name, icon, privacy, feeling, body, image, yt, sensitive_content, posts.tags, posts.status, username, nickname, avatar, has_mh, level, organization, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0) AS empathy_count, (SELECT COUNT(*) FROM replies WHERE post = posts.id AND status = 0) AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = posts.id AND type = 0 AND source = ?) AS empathy_added FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
    $stmt->bind_param('iii', $_SESSION['id'], $_GET['id'], $_SESSION['id']);
}
$stmt->execute();
if($stmt->error) {
    showError(500, 'There was an error while fetching the ' . $object . '.');
}
$result = $stmt->get_result();
if($result->num_rows === 0) {
    showError(404, 'The ' . $object . ' could not be found.');
}
$row = $result->fetch_array();
$created_by = $row['created_by']; // TODO: is this even necessary
$sensitive_content = $row['sensitive_content'];
if($type !== 1) {
    $community = $row['community'];
    $reply_count = $row['reply_count'];
} else {
    $stmt = $db->prepare('SELECT community, name, icon, feeling, body, posts.status, nickname, avatar, has_mh FROM posts LEFT JOIN users ON created_by = users.id LEFT JOIN communities ON communities.id = community WHERE posts.id = ? AND (community IS NULL OR privacy = 0 OR (SELECT COUNT(*) FROM community_members WHERE user = ? AND community = communities.id AND status = 1) = 1)');
    $stmt->bind_param('ii', $row['post'], $_SESSION['id']);
    $stmt->execute();
    if($stmt->error) {
        showError(500, 'There was an error while fetching the reply\'s post.');
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        showError(404, 'The reply could not be found.');
    } else {
        $oprow = $result->fetch_assoc();
    }
}
$title = ($_SESSION['id'] === $row['created_by'] ? 'Your ' . ($type === 1 ? 'comment' : 'post') : $row['nickname'] . '\'s ' . ($type === 1 ? 'Comment' : 'Post'));
require_once('inc/header.php');
?>
<div <?=$type !== 1 ? 'id="post-permlink" ' : ''?>class="main-column<?=empty($_SESSION['username']) ? ' guest' : ''?>">
    <?php if($type === 1) { ?>
        <div class="post-list-outline">
            <a class="post-permalink-button info-ticker" href="/posts/<?=htmlspecialchars($row['post'])?>">
                <span class="icon-container"><img src="<?=getAvatar($oprow['avatar'], $oprow['has_mh'], $oprow['feeling'])?>" class="icon"></span>
                <span><span class="post-user-description"><?=htmlspecialchars($oprow['nickname'])?>'s post (<?=getPreview($oprow['body'])?>)</span></span>
            </a>
        </div>
    <?php } ?>
    <div class="post-list-outline">
        <section id="post-content" class="post <?=$type === 1 ? 'reply-permalink-post' : 'post-subtype-default'?>">
            <?php if(isset($row['community']) && $row['community'] !== null) { ?>
                <p class="community-container">
                    <a href="/communities/<?=$row['community']?>"><img src="<?=getCommunityIcon($row['icon'])?>" class="community-icon"><?=htmlspecialchars($row['name'])?></a>
                </p>
            <?php } else if(isset($oprow['community']) && $oprow['community'] !== null) { ?>
                <p class="community-container">
                    <a href="/communities/<?=$oprow['community']?>"><img src="<?=getCommunityIcon($oprow['icon'])?>" class="community-icon"><?=htmlspecialchars($oprow['name'])?></a>
                </p>
            <?php } ?>
            <div class="user-content">
                <a href="/users/<?=htmlspecialchars($row['username'])?>" class="icon-container<?=$row['level'] > 0 ? ' official-user' : ''?>">
                    <img src="<?=getAvatar($row['avatar'], $row['has_mh'], $row['feeling'])?>" class="icon">
                </a>
                <div class="user-name-content">
                    <?php
                    $organizations = [];
                    if(!empty($row['organization'])) {
                        array_push($organizations, htmlspecialchars($row['organization']));
                    }
                    $community = $type === 1 ? $oprow['community'] : $row['community'];
                    if($community !== null) {
                        $stmt = $db->prepare('SELECT level FROM community_admins WHERE user = ? AND community = ?');
                        $stmt->bind_param('ii', $row['created_by'], $community);
                        $stmt->execute();
                        if(!$stmt->error) {
                            $result = $stmt->get_result();
                            if($result->num_rows > 0) {
                                $lrow = $result->fetch_assoc();
                                switch($lrow['level']) {
                                    case 1:
                                        array_push($organizations, 'Community Moderator');
                                        break;
                                    case 3:
                                        array_push($organizations, 'Community Owner');
                                        break;
                                    default:
                                        array_push($organizations, 'Community Administrator');
                                }
                            }
                        }
                    }
                    if(count($organizations) > 0) {
                        echo '<p class="user-organization">' . implode(', ', $organizations) . '</p>';
                    } ?>
                    <p class="user-name">
                        <a href="/users/<?=htmlspecialchars($row['username'])?>"><?=htmlspecialchars($row['nickname'])?></a>
                        <span class="user-id"><?=htmlspecialchars($row['username'])?></span>
                    </p>
                    <p class="timestamp-container">
                        <span class="timestamp"><?=getTimestamp($row['created_at'])?></span>
                        <span class="spoiler-status<?=$row['sensitive_content'] ? ' spoiler' : ''?>">&#775; Sensitive</span>
                    </p>
                </div>
            </div>
            <div class="body">
                <?php if($row['status'] === 1) { ?>
                    <p class="deleted-message">Deleted by <?=$type === 1 ? 'the creator of the comment' : 'poster'?>.</p>
                <?php } else if($row['status'] === 2) { ?>
                    <p class="deleted-message">
                        Deleted by administrator.<br>
                        Post ID: #<?=htmlspecialchars($row['id'])?>
                    </p>
                <?php }
                if($row['status'] === 0 || ($row['status'] === 2 && ($_SESSION['id'] === $row['created_by'] || $_SESSION['level'] > $row['level']))) { ?>
                    <div class="<?=$object?>-content-text"><?=getBody($row['body'], false)?></div>
                    <?php if(!empty($row['image'])) { ?>
                        <div class="screenshot-container still-image">
                            <img src="<?=htmlspecialchars($row['image'])?>">
                        </div>
                    <?php }
                    if(!empty($row['yt'])) { ?>
                        <div class="screenshot-container video">
                            <iframe width="490" height="276" src="https://www.youtube.com/embed/<?=htmlspecialchars($row['yt'])?>" frameborder="0" allowfullscreen="true"></iframe>
                        </div>
                    <?php }
                    if($row['status'] === 0) { ?>
                        <div class="post-meta">
                            <button type="button" class="symbol submit empathy-button<?=$row['empathy_added'] ? ' empathy-added' : ''?>"<?=$row['created_by'] === $_SESSION['id'] ? ' disabled' : ''?> data-feeling="<?=getFeelingName($row['feeling'])?>" data-action="/<?=($type === 1 ? 'replie' : 'post') . 's/' . htmlspecialchars($row['id'])?>/empathies" data-url-id="<?=htmlspecialchars($row['id'])?>">
                                <span class="empathy-button-text"><?=$row['empathy_added'] ? 'Unyeah' : getEmpathyText($row['feeling'])?></span>
                            </button>
                            <div class="empathy symbol"><span class="symbol-label">Yeahs</span><span class="empathy-count"><?=htmlspecialchars($row['empathy_count'])?></span></div>
                            <?php
                            if($type !== 1) {
                                ?><div class="reply symbol"><span class="symbol-label">Replies</span><span class="reply-count"><?=htmlspecialchars($row['reply_count'])?></span></div><?php
                                if($row['tags'] !== null) {
                                    echo '<span class="list-content-with-icon-and-text report-buttons-content">';
                                    foreach(explode(',', mb_substr($row['tags'], 1, -1)) as $tag) {
                                        echo '<a class="id-name left" href="/search?tag=' . htmlspecialchars(urlencode(str_replace('%2C', ',', $tag))) . '"><font face="MiiverseSymbols" size="1">t</font> ' . htmlspecialchars(str_replace('_', ' ', str_replace('%2C', ',', $tag))) . '</a>';
                                        //echo '<span class="post-tag symbol" data-href="/posts/61">' . htmlspecialchars(str_replace('_', ' ', str_replace('%2C', ',', $tag))) . '</span>';
                                    }
                                    echo '</span>';
                                }
                            }
                        echo '</div>';
                    }
                }
                if($type !== 1) { ?>
                </div>
            </section>
        <?php }
        if($row['status'] === 0) { ?>
            <div id="empathy-content"<?=$row['empathy_count'] === 0 ? ' class="none"' : ''?>>
                <a href="/users/<?=$_SESSION['username']?>" class="post-permalink-feeling-icon visitor"<?=$row['empathy_added'] === 0 ? ' style="display: none;"' : ''?>><img src="<?=getAvatar($_SESSION['avatar'], $_SESSION['has_mh'], $row['feeling'])?>" class="user-icon"></a>
                <?php
                $stmt = $db->prepare('SELECT users.id, username, avatar, has_mh, level FROM users LEFT JOIN empathies ON source = users.id WHERE target = ? AND type = ? ORDER BY empathies.id DESC LIMIT 14');
                $stmt->bind_param('ii', $row['id'], $type);
                $stmt->execute();
                $result = $stmt->get_result();
                while($empathy = $result->fetch_assoc()) {
                    if($empathy['id'] !== $_SESSION['id']) {
                        echo '<a href="/users/' . htmlspecialchars($empathy['username']) . '" class="post-permalink-feeling-icon"><img src="' . getAvatar($empathy['avatar'], $empathy['has_mh'], $row['feeling']) . '" class="user-icon"></a>';
                    }
                }
                ?>
            </div>
            <?php if($type !== 1) { 
                $url = htmlspecialchars(urlencode(((isset($_SERVER['HTTPS']) || HTTPS_PROXY) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']));
                ?><div class="buttons-content">
                    <h5 class="social-buttons-heading">Share this Post</h5>
                    <div class="post-social-buttons-wrapper social-buttons-container">
                        <div class="social-buttons-content social-buttons-content-primary">
                            <div class="social-buttons-content-cell twitter">
                                <button type="button" class="button social-button twitter" data-service-name="twitter" data-is-popup="1" data-share-url="https://twitter.com/intent/tweet?url=<?=$url?>&text=<?=htmlspecialchars(urlencode($title))?>" data-width="600" data-height="260">
                                    <span class="social-button-inner symbol" style="background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAFfklEQVR4Ae2bA3QkSxRA+2utGN+2bfsfrG3FdtZmkk3Wtm0rtm3bHuT9epXpLMLJVNSTd849apy+U35TxTUVX1luGPSJ9S7nt2xOp7xod1+sYx8IWvYh9djxBFM0bWXYNEbDOgg0rOpR57GUYRFIUeMxDwRVMxmmAQ2omMgwRvwpw4xkGPrDUBkqBu51WnpXal/QO5Ty+twNTu/MWTaIa0t8YrVj1Uv290QvrY6Et7bEwQf7kuCjwynw8ZHuzYcHk+G9XYnw+oYY0J0fChpzr4lem+2ypmlLWqoufd6zOeLz0qpIeGdHAr6kR/Pm5jjQcQyBF2fv8Hl71uI+jYRR9tX10fDRoeSeLNqo1J9bEg4vzNrh81g13rkOq3BjWWFIazuGwqszndY1dFAv2LtL3lWgGveE6q0y64bkzZmLB3EfW+/e/PKaKMHK8ujMC4WXZzhv4d6yOZv+9tZ4wQtj760z42AG96L9fTEOPUIXfm9PIqhMvyrmdOwC+XFW0GCHPHCGJ3A4exK6LM+AGT5KJjzdmwjbKZHwNOUUDlYa4f5KJzyVCGva9goLln5TvIiwTdcLT72RDTsjS+BScgWciC+DZf6F8OuZ9Eb3fXEsFela4e9OpMHEa1ntevaPs+ngk1MNTUWNpA7WBBbS+/4+lwEuIUXgnV0NnyhSwpMZCK8MKISyWimMuyqf9C+n0yGtXAytRX6VBDBE0jqYRmrCx10tfD2tEjAqxFIwvpfb5ueuplZAW6OkVgprg4pgqV8BrAoo7FphrGZ8kEKA3VEl8NXxltvZb2fS8V65IzS/Br47mdbub+07iQhjKlUR4XNJ5fB4YFV18MqHz442/Yz5/Ty5ZW+QmvTNiVR8vv3CEz2JsJViwhbuzX98TqUYtoYXw+grmY8840h+DHkCmwvfWXW58KekFMMLaqC1KKiWwP3MKtgXXQpnEsvlEi6pkcLHrITVFRSefD0b/jufAfHFIuigwCbSfYTdSalhlUsu7Thhr+wqJsJ9JqCwpWLCTsFF0NGxPaKEjfB4BsI/nEqDohpJhwrPupXDUNgiUOEXzb2dQ6eC7IPOtLBjZCL8zHgPNsIIzqeji2qZC28Nx+rMUFiNkTBWO5xX49DDqqwrRFL46XQaO+FxDIVxcsG6VrsEF+G7GQubsxFG8AMZBU5msO2yFR5LhFXN2Anz00ZFe218/q9zmADoAcIIrpZsPPLgdEK5vO0Z19Z8QoE5T1Nh0wDmL8Y0DK5di2ukcsnmVEpgPJ9I6O7C2JuakATAwZhSOnbKGx5ZVbhO7jBZKjzGXTHh6TezaQIA0y/tjZQyEdh55ndKsvCp0URYxUSxEv6EYHQ3F26mVUJ1G8clvO9ORiUmAvgkQU8QbsyXpO1iqeMEZE9UKZxKKKc5L1z/HootgxUk/TrzZg7ex05EbmFjKqwM8ML+ghXsFR5FhIcZ9QoLlidH3ifChkomPFSZhEcoo7Aq2UmOO06VYWPaUyPuAKepd7X2vd2Jghd+d1cC9B15Tsw9r3ck/XW3WMELv7I2CoaM2pHBvT7XdbPugjDBC2MqS33Usi0cnv5Q07spwQMdQt463GfEBYnuGKshHMZrc1yccAO1EDuvDw8k02NAGqOWOHEPBzn94YsHIj4+LBxZdNGwDgaVkc6+3OOBR12en7UrVNshBN7cFCeIaoxL32EjXcN0Rlv04ZoLcvpjrdqsKyKs4vgQDlkfHe4Z4+y7OxNob6xGMrF9R5wWaYxavI5rS+DpD3IgYjOeEVCZcUU8cIYXbq7G/cZ0RyrSH5lKoRu+KJMbwA0k+Af0I/SZQBhPof/zUMbJGFvP0zxj3HH92phRFDovpoyg4KSCSJ4V49CjPmrpZt0xloOacvsf2iUXFYatq0AAAAAASUVORK5CYII=');" alt="Tweet">Tweet</span>
                                </button>
                            </div>
                            <div class="social-buttons-content-cell facebook">
                                <button type="button" class="button social-button facebook" data-service-name="facebook" data-is-popup="1" data-share-url="https://www.facebook.com/sharer.php?u=<?=$url?>%3Ffb_ref%3Dscfbp&amp;display=popup" data-width="626" data-height="436">
                                    <span class="social-button-inner symbol" style="background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAEhUlEQVR4Ae2bA5TsWBCG79g22u6kbbx5Gtte27att7Zt27Zt2zZrU0k/ZnrRJ6ObrnO+8ST54uT+RaaqaPum+b7GDY9mw+u8o3NN/K6wDkOdJYZ5iKfWNBhjgKfGuAqG/hh9UK3vFdD18FTpugW0XTyVmuV0QoW6Q0DVHqMNypWtAooWKFM0C9QLlNY1cTTylCub/qrRtf2qYXvesfiHjrJHx/PJfyl/44aH6t2TvxmDm4OjeU/w9RwGwcGjITR0zJwm0L8MPJ0HArNoR1CyE1CtafnN7Bs8PK5otH2TTGfDeo8YOFFX2344kXmNvXF3UDBjoLX3PGKLjGaKhFHWHN0WggNHzmdR0VbXuNYDja37kdVkueN1Ge7ClMiKpOuto2D09C9bcYLSOif+cLXH3Y2p2L3L6xf/wYRG8olv6YYnGUNb4C+oRsGMg97VezJhQuu872jZi3phPHvXG1s/IDrn+O++3sOoF/Z2HQxldQt/JwpmBIKDR1EvHBw4CgrKw0Dw7olOSTH5pSGZCZcEOWHzkGyE80oCiQnvd/wt8N0Pv8AsFc4blyExYXzi+Z//KJKdJen/L1zsT0x4jtT/Xu7cIh8KD9ArnBT2Aqkxzg3hdz/8Ck684AHYfJ/LYXTbc2Fk63NhvV0ugk33ugzOvuJReoT/+gs40fshMnxs3PntcMi1kgjnFHpmX/iMyx6OPy+phQvcnLChf9aEP/z0W1gwepx8hPHYjDePxZMnQPO6J/PsdfRNkghnC8J9sya83UFXi6bdst4p8MzLH+KxvWpJI5zvAoLvjGdLeN2dLxJN+6iz7gGhKBSe3OEC0bTPu/rx6RPOc3LCup6kMK3CWXkOIDjOMxPCX3/3Ezz23LurMbDFWaJpH3zy7aK/e//jr6URzrXPnPCDT76V8IP7pTc+LaGwtmvOC9/32JuSCGfm2OaH8OvvfC6dcKVm7gv/+NOvEgmzMyf85Avvw8CWZ69Gw9jxU95prfo36+x8IWBJKNwpm8tSRjYDBCMGSWFahbNQWNWeFKZVOD3LisJt8hHOtADBDFRSmFbhDDMnrGiRlzBG+ZLClAqnZZg44frmxIdLZ1EYl2HGhHEwGt9gzJIwzjuhAfG0dCMQjODSHXUQCTfKRjg1zSBD4XJl81+YOKVdFpPCaRlGIBibxyQ57cLujgMgO5/9naiZ7vfZxTtTL2wKbwXFVYEPiMU3dJLKvhb1wpWaDqjWLTmZYPdHparpD0fTHlRHhzNzmT+UbHshwTL7Bo9SsONA48nL33cE3wZUrV9yFFm1tPaeR7EhItayQwecCyYcyuqjj5I1C1td1GzXs9gQgT0CNOzGZYpWKK0NP6dg2jJJvDJ6+o+oUC79DXt+8J88XQdhgHxeBMDdHfvzZ+NKdQdk59t+43bjZeS/FHZ/cA0RJ2GPQDkXmy8oC2O4GvPGmEgVKPZjlC+GF8EcFCZleLKRfJdAnhPB8VkcwUNwnCcGi+A7Y4EsHnzhhq9kBDLMK8AnHp50I4J3Twj3MwN/ncVLD3c2PknJtudP5fY35nJdmPXZC84AAAAASUVORK5CYII=');" alt="Share">Share</span>
                                </button>
                            </div>
                        </div>
                        <div class="social-buttons-content social-buttons-content-secondary">
                            <div class="social-buttons-content-cell google">
                                <button type="button" class="button social-button google" data-service-name="google" data-is-popup="1" data-share-url="https://plus.google.com/share?url=<?=$url?>" data-width="500" data-height="500">
                                    <span class="social-button-inner symbol" style="background-image:url('data:image/png;base64,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');" alt="Google+">Google+</span>
                                </button>
                            </div>
                            <div class="social-buttons-content-cell tumblr">
                                <button type="button" class="button social-button tumblr" data-service-name="tumblr" data-is-popup="1" data-share-url="http://www.tumblr.com/share/link?url=<?=$url?>&name=<?=htmlspecialchars(urlencode($title))?>&description=<?=htmlspecialchars(urlencode($row['body']))?>" data-width="450" data-height="430">
                                    <span class="social-button-inner symbol" style="background-image:url('data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAADwAAAA8CAYAAAA6/NlyAAAErElEQVR4Ae2bA3QraRTH5/W92LZtJ7W1tm3btm3btm3btu3dpKd3537zap7dJE1nes/5FUnzTX9j/C81WTV1rijNNfedFMm0fuwOZ0tWTwzMrugYTM7IUsIMjhGMjhCDPQgGe4DBxqC3+RmsPgaLl6BDzB6C1uxmMLlBY3IxGJ00DoLaMIQdVHobQWt0DhrtgX+c/sTHwUTjibFcu5SaTeWa+4/2RPJFb7wFYg3LQ6ZjLcj3rA+F3g1qmlz3upBqXQ1CuV6w+ZJgtHqKwXjDsVN54lLlJwqdT6NoonllHGReE61fDqyeBLiDqaejmTb+BGGUDaQ7Id+9Hn6AFeS61gVHMAdOf/Lpcatx3/HeRAurZEek1wGrJw6+aP744R2UK5QdSLaswjLZsau3xmAfCKdbpFS2ue9MX6KVtbJDWD1J8IazZ1GRdMtn8cYVWC8cyvaCxRn6nHIHs6VM51qsF063rQ5qg61E4UnF0HGWxdCO64FMqQcKz5rYLjuEVKHllrBEruGasLp6wq++8S58/uU3Y1hz0z2rL4xXPJWeUO/q28Bkte5W+1ZVWCyrkvBO+x5bI8IqFA5XfEK33/Mod4S7V90K/vzzr5oQFkmVtLCjssLX3XIfunFDeM+DT4bBwcEaElZUTvjgY86GYqmEXrUjLKGF8WZbOQddYd2d4P5HnoHZ1Ba7HArdq249JS3Lb1rbwrfc+RBZquWqMy64pqzCQrGcFrYHyzbgV998D1i1KywDCu8ZLwizVFggktLCtgXh/8wxp14Mp59/DeG8S2+cUeiqG+/Gv52SzXY6pPzCepu/Ise8rlW2QqeaOg7zhRIuClt9HBIWc1HY4uWOsEDEQWEdh4R5RNjsWRBmrTBfCBSGR+ZKeINt9uOW8Ja7HlZV4SU8AS1smjvhvQ45pfrCmIGqxODNy20yo/BNdzw44zgrrb/z/BBG8H70dIV3NM+66DqcOWM+19C/Eeyw9zHw7Iuvww233V+2/2fxEj4tbHRWTPj1t96H2dSvv/0Oz730Brn59/zLb5Lfh6oCwo6KCZ941uXwf2teCeOq+v5Hn9WQMA8otaFywsxOZxf4+LMvuSOMdK68JXnGVCyWZi3619//wM13PgRrbb5X+YQXL0Fhe9WOg31rbAunnHMlPPrkC/Dtdz+OmQG//vYHvPXuR3Dr3Y/AvoefhjOp7NMnwpg1nsvcxdDjlGpQN1aY/RBhTJJjuJrtspgUJjsto933T6ptNdYLp1pXBaFIVqIc/vhn4Xwf64X9yXZQak2fU8F4w5l2f5r1wjqLBww2/1kUdn/oLa4BbOhgqSw2fuBN+AGbNymnsALx+hOt3iR2hrCyBUBjcmIr0YnU6HIHU89gQ0SBRVFidMGEg8bgeIYaX9jq4vQnXrF44tgjwIoEPF76qvW2V63eBJ+aqnzR/HE6k6uIPT/4IUySz4cAOQbAky2rkr0x3okVSmRFejU+nppNYfeHN5w9E3sENAZbSabUYbga88Y0aoJYhqgQEvhiUGBSBsEACY0MweeyQ+DOYyliBoGIwBuCLyTg7Zgh8Dp2BB7D4iWEOgL+zCPHWTz0GGz+M23epHQyt38Bt/ciE3opegkAAAAASUVORK5CYII=');" alt="Tumblr">Tumblr</span>
                                </button>
                            </div>
                        </div>
                    </div>
                    <button type="button" class="embed-link-button" data-modal-open="#view-embed-link-code">Embed</button>
                    <?php if(!empty($_SESSION['username']) && ($_SESSION['id'] === $row['created_by'] || $_SESSION['level'] > $row['level'])) { ?>
                        <div class="edit-buttons-content">
                            <button type="button" class="symbol button edit-button edit-post-button" data-modal-open="#edit-post-page">
                                <span class="symbol-label">Edit</span>
                            </button>
                        </div>
                    <?php } else if(!empty($_SESSION['username'])) { ?>
                        <div class="report-buttons-content">
                            <button type="button" class="report-violation-button" data-modal-open="#report-violation-page" data-screen-name="<?=htmlspecialchars($row['nickname'])?>" data-action="/posts/<?=$_GET['id']?>/violations" data-can-report-spoiler="1">Report Violation</button>
                        </div>
                    <?php } ?>
                </div>
            <?php } else {
                ?><div class="post-meta">
                    <button type="button" class="symbol button edit-button edit-reply-button" data-modal-open="#edit-post-page"><span class="symbol-label">Edit</span></button>
                    <div class="report-buttons-content">
                        <button type="button" class="report-button" data-modal-open="#report-violation-page" data-screen-name="<?=htmlspecialchars($row['nickname'])?>" data-support-text="<?=htmlspecialchars($row['id'])?>" data-action="/<?=htmlspecialchars($_GET['type']) . '/' . htmlspecialchars($row['id'])?>/violations" data-is-permalink="1" data-can-report-spoiler="<?=$row['sensitive_content'] === 0 ? '1' : '0'?>" data-track-label="<?=$object?>" data-track-action="openReportModal" data-track-category="reportViolation">Report Violation</button>
                    </div>
                </div>
            </div>
        </div>
    </section><?php
            }
            if($type !== 1) { ?>
                <div id="reply-content">
                    <h2 class="reply-label">Comments</h2>
                    <?php
                    echo '<div class="no-reply-content';
                    $stmt = $db->prepare('SELECT * FROM (SELECT replies.id, created_by, replies.created_at, feeling, body, image, sensitive_content, username, nickname, avatar, has_mh, level, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1) AS empathy_count, -1 AS reply_count, (SELECT COUNT(*) FROM empathies WHERE target = replies.id AND type = 1 AND source = ?) AS empathy_added, ? AS op FROM replies LEFT JOIN users ON created_by = users.id WHERE replies.post = ? AND replies.status = 0 ORDER BY replies.id DESC LIMIT 20) AS replies ORDER BY replies.id ASC');
                    $stmt->bind_param('iii', $_SESSION['id'], $row['created_by'], $_GET['id']);
                    $stmt->execute();
                    if(!$stmt->error) {
                        $result = $stmt->get_result();
                        if($result->num_rows > 0) {
                            echo ' none';
                        }
                    } ?>">
                        <p>This post has no comments.</p>
                    </div>
                    <?=$row['reply_count'] > 20 ? '<button data-fragment-url="/posts/' . $row['id'] . '/replies" class="more-button oldest-replies-button"><span class="symbol">View all comments (' . $row['reply_count'] . ')</span></button>' : ''?>
                    <div class="list reply-list"><?php
                    if(!$stmt->error && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            require('elements/post.php');
                        }
                    }
                    ?></div>
                </div>
                <h2 class="reply-label">Add a Comment</h2>
                <?php if(empty($_SESSION['username'])) { ?>
                    <div class="guest-message">
                        <p>You must sign in to post a comment.<br><br>Sign in with a Miiverse World account to connect to users around the world by writing posts and comments and by giving Yeahs to other people's posts. You can sign up for a Miiverse World account <a href="/signup">here</a>.</p>
                        <a href="/login" class="arrow-button"><span>Log In</span></a>
                        <a href="/signup" class="arrow-button"><span>Sign Up</span></a>
                        <a href="/rules" class="arrow-button"><span>Miiverse World Rules</span></a>
                    </div>
                <?php } else if($reply_count < 1000 && ($created_by === $_SESSION['id'] || checkCommunityBan($community) === null)) { ?>
                    <form id="reply-form" method="post" action="/posts/<?=htmlspecialchars($_GET['id'])?>/replies" class="for-identified-user" data-post-subtype="default">
                        <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                        <div class="feeling-selector js-feeling-selector"><label class="symbol feeling-button feeling-button-normal checked"><input type="radio" name="feeling_id" value="0" checked><span class="symbol-label">normal</span></label><label class="symbol feeling-button feeling-button-happy"><input type="radio" name="feeling_id" value="1"><span class="symbol-label">happy</span></label><label class="symbol feeling-button feeling-button-like"><input type="radio" name="feeling_id" value="2"><span class="symbol-label">like</span></label><label class="symbol feeling-button feeling-button-surprised"><input type="radio" name="feeling_id" value="3"><span class="symbol-label">surprised</span></label><label class="symbol feeling-button feeling-button-frustrated"><input type="radio" name="feeling_id" value="4"><span class="symbol-label">frustrated</span></label><label class="symbol feeling-button feeling-button-puzzled"><input type="radio" name="feeling_id" value="5"><span class="symbol-label">puzzled</span></label></div>
                        <div class="textarea-with-menu">
                            <div class="textarea-container">
                                <textarea name="body" class="textarea-text textarea" maxlength="2000" placeholder="Add a comment here." data-required></textarea>
                            </div>
                        </div>
                        <label class="file-button-container">
                            <span class="input-label">Image
                                <span>PNG, JPEG and GIF files are allowed.</span>
                            </span>
                            <input accept="image/*" type="file" class="file-button">
                            <input type="hidden" name="image">
                        </label>
                        <div class="post-form-footer-options">
                            <div class="post-form-footer-option-inner post-form-spoiler js-post-form-spoiler">
                                <label class="spoiler-button symbol"><input type="checkbox" name="sensitive_content" value="1"> Sensitive Content</label>
                            </div>
                        </div>
                        <div class="form-buttons">
                            <input type="submit" class="black-button post-button disabled" value="Send" data-post-content-type="text" data-post-with-screenshot="nodata" disabled>
                        </div>
                    </form>
                <?php } else { ?>
                    <div class="cannot-reply">
                        <div><p>You cannot comment on this post.</p></div>
                    </div>
                <?php } ?>
                <div id="view-embed-link-code" class="dialog none" data-modal-types="view_embed_link" data-is-template="1">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Embed post on website</h1>
                            <div class="window-body">
                                <div class="embed-warn">To embed this post on a website, copy the code below. Note that the design and content of the embedded post may change at any time.</div>
                                <textarea name="body" class="textarea-text textarea" onclick="this.focus(); this.select();" readonly>&lt;div class="miiverse-post" lang="en" data-miiverse-cite="<?=urldecode($url)?>" data-miiverse-embedded-version="1"&gt;&lt;noscript&gt;You must have JavaScript enabled on your device to view Miiverse World posts that have been embedded in a website. &lt;a class="miiverse-post-link" href="<?=urldecode($url)?>"&gt;View post in Miiverse World&lt;/a&gt;&lt;/noscript&gt;&lt;/div&gt;&lt;script async src="<?=htmlspecialchars('http' . ((isset($_SERVER['HTTPS']) || HTTPS_PROXY) ? 's' : '') . '://' . $_SERVER['HTTP_HOST'])?>/assets/js/embedded.min.js" charset="utf-8"&gt;&lt;/script&gt;</textarea>
                                <input type="button" class="olv-modal-close-button gray-button" value="Close">
                            </div>
                        </div>
                        </div>
                    </div>
                </div>
            <?php } ?>
            <div id="edit-post-page" class="dialog none" data-modal-types="edit-post">
                <div class="dialog-inner">
                    <div class="window">
                        <h1 class="window-title">Edit Post</h1>
                        <div class="window-body">
                        <form method="post" class="edit-post-form">
                            <p class="select-button-label">Select an action:</p>
                            <select name="edit-type">
                                <option value selected>Select an option.</option>
                                <?php if($type === 0 && !empty($_SESSION['username']) && $_SESSION['id'] === $row['created_by'] && !empty($row['image']) && $row['sensitive_content'] === 0 && $row['privacy'] !== 1) { ?><option value="screenshot-profile-post" data-action="/posts/<?=htmlspecialchars($_GET['id'])?>/screenshot.set_profile_post">Set Image as Favorite Post</option><?php }
                                if($sensitive_content === 0) { ?><option value="spoiler" data-action="/<?=htmlspecialchars($_GET['type'] . '/' . $_GET['id'])?>.set_sensitive">Set as Sensitive</option><?php } ?>
                                <option value="delete" data-action="/<?=htmlspecialchars($_GET['type'] . '/' . $_GET['id'])?>.delete" data-track-action="deletePost" data-track-category="post">Delete</option>
                            </select>
                            <div class="form-buttons">
                                <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                <input type="submit" class="post-button black-button" value="Confirm">
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div id="report-violation-page" class="dialog none" data-modal-types="report report-violation" data-is-template="1">
                <div class="dialog-inner">
                    <div class="window">
                        <h1 class="window-title">Report Violation to Miiverse World Administrators</h1>
                        <div class="window-body">
                            <p class="description">You are about to report a <?=$object?> with content which violates the Miiverse Code of Conduct. This report will be sent to the Miiverse World administrators and not to the creator of the post.</p>
                            <form method="post" action="/<?=htmlspecialchars($_GET['type'] . '/' . $_GET['id'])?>/violations">
                                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                <select name="type" class="can-report-spoiler<?=(isset($row['community']) && $row['community'] === null) || (isset($oprow['community']) && $oprow['community'] === null) ? ' none' : ''?>">
                                    <option value>Select who should see the report.</option>
                                    <option value="0" data-body-required="1">Community Administrators</option>
                                    <option value="1" data-body-required="1"<?=(isset($row['community']) && $row['community'] === null) || (isset($oprow['community']) && $oprow['community'] === null) ? ' selected' : ''?>>Miiverse World Staff</option>
                                </select>
                                <textarea name="body" class="textarea" maxlength="100" data-placeholder="Enter a reason for the report here." placeholder="Enter a reason for the report here."></textarea>
                                <div class="form-buttons">
                                    <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                    <input type="submit" class="post-button black-button disabled" value="Submit Report" disabled>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    </div>
</div>
<?php require_once('inc/footer.php'); ?>