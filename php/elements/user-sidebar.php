<div id="sidebar" class="user-sidebar">
    <?php if(empty($is_general) || !empty($_SESSION['username'])) { ?>
    <div class="sidebar-container">
        <?php if($row['favorite_post'] !== null) { ?>
            <a href="/posts/<?=$row['favorite_post']?>" id="sidebar-cover" style="background-image:url(<?=htmlspecialchars($row['favorite_post_image'])?>)"><img src="<?=htmlspecialchars($row['favorite_post_image'])?>" class="sidebar-cover-image"></a>
        <?php } ?>
        <div id="sidebar-profile-body"<?=$row['favorite_post'] !== null ? ' class="with-profile-post-image"' : ''?>>
            <div class="icon-container<?=$row['level'] > 0 ? ' official-user' : ''?>">
                <a href="/users/<?=htmlspecialchars($row['username'])?>">
                    <img src="<?=getAvatar($row['avatar'], $row['has_mh']) . '" alt="' . htmlspecialchars($row['nickname'])?>" class="icon">
                </a>
            </div>
            <?php $stmt = $db->prepare('SELECT COUNT(*) FROM bans WHERE user = ? AND NOW() < banned_at + INTERVAL length DAY');
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
            if(!$stmt->error) {
                $result = $stmt->get_result();
                $brow = $result->fetch_assoc();
                if($brow['COUNT(*)'] > 0) {
                    $banned = true;
                    $organization = 'Banned ';
                } else {
                    $banned = false;
                    if(time() - 30 < strtotime($row['last_seen'])) {
                        $organization = 'Online ';
                    } else {
                        $organization = '';
                    }
                }
                if(!empty($row['organization']) || $row['organization'] === '0') {
                    $organization .= htmlspecialchars($row['organization']);
                }
                if(!empty($organization)) {
                    echo '<span class="user-organization">' . $organization . '</span>';
                }
            } ?>
            <a href="/users/<?=htmlspecialchars($row['username'])?>" class="nick-name"><?=htmlspecialchars($row['nickname'])?></a>
            <p class="id-name"><?=htmlspecialchars($row['username'])?></p>
        </div>
        <?php if(!empty($_SESSION['username'])) {
            if($_SESSION['id'] === $row['id']) { ?>
                <div id="edit-profile-settings">
                    <a class="button symbol" href="/settings/profile">Profile Settings</a>
                </div>
                <div class="report-buttons-content">
                    <button type="button" class="report-button" data-modal-open="#manage-page">Manage</button>
                </div>
            <?php } else { ?>
                <div class="user-action-content">
                    <div class="toggle-button">
                        <button type="button" data-action="/users/<?=htmlspecialchars($row['username'])?>.follow.json" class="follow-button button symbol<?=$row['is_following'] === 1 ? ' none' : ''?>">Follow</button>
                        <button type="button" data-action="/users/<?=htmlspecialchars($row['username'])?>.unfollow.json" class="unfollow-button button symbol<?=$row['is_following'] === 0 ? ' none' : ''?>" data-screen-name="<?=$row['nickname']?>">Follow</button>
                    </div>
                    <div class="report-buttons-content">
                        <button type="button" class="report-button" data-modal-open="#report-violator-page" data-can-report-spoiler="1">Report Violation</button>
                        <button type="button" class="report-button" data-modal-open="#block-page">Block</button>
                        <?php if($_SESSION['level'] > $row['level']) { ?>
                            <button type="button" class="report-button" data-modal-open="#manage-page">Manage</button>
                        <?php } ?>
                    </div>
                </div>
                <div id="report-violator-page" class="dialog none" data-modal-types="report report-violation">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Report this User to Miiverse World Administrators</h1>
                            <div class="window-body">
                                <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/violators">
                                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                    <p class="description">You are about to report a user for violating the Miiverse World Rules. This report will be sent to the Miiverse World administrators and not to the user you are reporting or any community staff.</p>
                                    <select name="type" class="can-report-spoiler none">
                                        <option value="1" selected data-body-required="1"></option>
                                    </select>
                                    <textarea name="body" class="textarea" maxlength="200" placeholder="Enter a reason for the report here."></textarea>
                                    <p class="violator-id">Username: <?=htmlspecialchars($row['username'])?></p>
                                    <div class="form-buttons">
                                        <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                        <input type="submit" class="post-button black-button" value="Submit Report">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="block-page" class="dialog none" data-modal-types="report">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Block this User</h1>
                            <div class="window-body">
                                <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/blacklist.create.json">
                                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                    <p class="window-body-content">Are you sure you want to block this user? You won't be able to view each other's posts, comments, or profiles until you unblock them.</p>
                                    <div class="form-buttons">
                                        <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                        <input type="submit" class="post-button black-button" value="Block">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
            if($_SESSION['level'] > $row['level'] || $_SESSION['id'] === $row['id']) { ?>
                <div id="manage-page" class="dialog none">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">User Management</h1>
                            <div class="window-body">
                                <p class="description">Select a management option below.</p>
                                <div class="form-buttons">
                                    <?php if($_SESSION['level'] > $row['level']) { ?><input type="submit" class="post-button black-button sidebar-social-container" value="<?=$banned ? 'Unb' : 'B'?>an" data-modal-open="#manage-ban-page"><?php } ?>
                                    <input type="submit" class="post-button black-button sidebar-social-container" value="Purge" data-modal-open="#manage-purge-page">
                                    <input type="submit" class="post-button black-button sidebar-social-container" value="Delete" data-modal-open="#manage-delete-page">
                                    <input type="button" class="olv-modal-close-button gray-button sidebar-social-container" value="Cancel">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="manage-ban-page" class="dialog none" data-modal-types="report<?=!$banned ? ' report-violator' : ''?>">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title"><?=$banned ? 'Unb' : 'B'?>an User</h1>
                            <div class="window-body tleft">
                                <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/manage">
                                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                    <input type="hidden" name="action" value="2">
                                    <?php if($banned) { ?>
                                        <p class="description">Unban this user?</p>
                                    <?php } else { ?>
                                        <p class="description">How long should the user be banned for?</p>
                                        <select name="type">
                                            <option value selected>Select a ban length.</option>
                                            <option value="1">One Day</option>
                                            <option value="2">Two Days</option>
                                            <option value="3">Three Days</option>
                                            <option value="4">Four Days</option>
                                            <option value="5">Five Days</option>
                                            <option value="6">Six Days</option>
                                            <option value="7">One Week</option>
                                            <option value="14">Two Weeks</option>
                                            <option value="21">Three Weeks</option>
                                            <option value="30">One Month</option>
                                            <option value="90">Three Months</option>
                                            <option value="180">Six Months</option>
                                            <option value="365">One Year</option>
                                            <option value="-1">Permanent</option>
                                        </select><br><br>
                                        <p><label><input type="checkbox" name="purge" value="1"> Delete posts and replies</label></p>
                                    <?php } ?>
                                    <div class="form-buttons">
                                        <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                        <input type="submit" class="post-button black-button" value="<?=$banned ? 'Unb' : 'B'?>an">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="manage-purge-page" class="dialog none" data-modal-types="report">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Purge User</h1>
                            <div class="window-body">
                                <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/manage">
                                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                    <input type="hidden" name="action" value="1">
                                    <p class="description">Which would you like to purge? This cannot be undone.</p>
                                    <ul class="tleft">
                                        <li><label><input type="checkbox" name="posts" value="1"> Posts</label></li>
                                        <li><label><input type="checkbox" name="replies" value="1"> Comments</label></li>
                                        <li><label><input type="checkbox" name="empathies" value="1"> Yeahs</label></li>
                                        <li><label><input type="checkbox" name="follows" value="1"> Follows</label></li>
                                        <li><label><input type="checkbox" name="communities" value="1"> Communities</label></li>
                                        <li><label><input type="checkbox" name="community_favorites" value="1"> Favorites</label></li>
                                        <li><label><input type="checkbox" name="reports" value="1"> Reports</label></li>
                                        <li><label><input type="checkbox" name="blocks" value="1"> Blocks</label></li>
                                    </ul>
                                    <div class="form-buttons">
                                        <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                        <input type="submit" class="post-button black-button" value="Purge">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="manage-delete-page" class="dialog none">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Delete User</h1>
                            <div class="window-body tleft">
                                <p class="description">Are you sure you want to <strong>permanently</strong> delete this account and everything on it? This cannot be undone.</p>
                                <div class="form-buttons">
                                    <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                    <input type="submit" class="post-button black-button" value="Delete" data-modal-open="#manage-delete-confirm-page">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div id="manage-delete-confirm-page" class="dialog none" data-modal-types="report">
                    <div class="dialog-inner">
                        <div class="window">
                            <h1 class="window-title">Are you sure?</h1>
                            <div class="window-body tleft">
                                <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/manage">
                                    <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                    <input type="hidden" name="action" value="3">
                                    <p class="description">Are you <em>really</em> sure you want this account deleted? Think about it! There is no turning back from this!</p>
                                    <p class="description">You can also mass-delete posts, comments and more by going back and entering the "Purge" menu. Account deletion is not always necessary.</p>
                                    <p class="description"><label>To confirm this action, please enter your password: <input type="password" name="password" placeholder="Password"></label></p>
                                    <div class="form-buttons">
                                        <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                        <input type="submit" class="post-button black-button" value="Confirm" data-modal-open="#manage-delete-confirm-page">
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            <?php }
        } ?>
        <ul id="sidebar-profile-status">
            <li><a href="/users/<?=htmlspecialchars($row['username'])?>/following"<?=!empty($_GET['type']) && $_GET['type'] === 'following' ? ' class="selected"' : ''?>"><span><span class="number"><?=$row['following_count']?></span>Following</span></a></li>
            <li><a href="/users/<?=htmlspecialchars($row['username'])?>/followers"<?=!empty($_GET['type']) && $_GET['type'] === 'followers' ? ' class="selected"' : ''?>"><span><span class="number"><?=$row['follower_count']?></span>Followers</span></a></li>
        </ul>
    </div>
    <?php }
    if(!empty($is_general)) { ?>
        <div class="sidebar-setting sidebar-container">
            <ul>
                <li><a href="/settings/profile" class="sidebar-menu-empathies symbol"><span>Profile Settings</span></a></li>
                <li><a href="/settings/account" class="sidebar-menu-setting symbol"><span>Account Settings</span></a></li>
                <li><a href="/my_blacklist" class="sidebar-menu-relation symbol"><span>Blocked Users</span></a></li>
                <li><a href="/rules" class="sidebar-menu-post symbol"><span>Site Rules</span></a></li>
                <li><a href="https://pf2m.com/contact/" class="sidebar-menu-guide symbol"><span>Contact Us</span></a></li>
            </ul>
        </div>
    <?php } else { ?>
        <div class="sidebar-setting sidebar-container">
            <div class="sidebar-post-menu">
                <a href="/users/<?=htmlspecialchars($row['username'])?>/posts" class="sidebar-menu-post with-count symbol<?=!empty($_GET['type']) && $_GET['type'] === 'posts' ? ' selected' : ''?>">
                    <span>All Posts</span>
                    <span class="post-count">
                        <span class="test-post-count"><?=$row['post_count']?></span>
                    </span>
                </a>
                <a href="/users/<?=htmlspecialchars($row['username'])?>/empathies" class="sidebar-menu-empathies with-count symbol<?=!empty($_GET['type']) && $_GET['type'] === 'empathies' ? ' selected' : ''?>">
                    <span>Yeahs</span>
                    <span class="post-count">
                        <span class="test-empathy-count"><?=$row['empathy_count']?></span>
                    </span>
                </a>
                <!--<a href="/users/<\?=htmlspecialchars($row['username'])?>/diary" class="sidebar-menu-diary symbol">
                    <span>Play Journal Entries</span>
                </a>-->
            </div>
        </div>
        <div class="sidebar-container sidebar-profile">
            <?php if(!empty($row['profile_comment'])) { ?>
                <div class="profile-comment">
                    <?php if(mb_strlen($row['profile_comment']) > 103) { ?>
                        <p class="js-truncated-text"><?=nl2br(htmlspecialchars(mb_substr($row['profile_comment'], 0, 100)))?>...</p>
                        <p class="js-full-text none"><?=nl2br(htmlspecialchars($row['profile_comment']))?></p>
                        <button type="button" class="description-more-button js-open-truncated-text-button">Show More</button>
                    <?php } else { ?>
                        <p class="js-truncated-text"><?=nl2br(htmlspecialchars($row['profile_comment']))?></p>
                    <?php } ?>
                </div>
            <?php } ?>
            <div class="user-data">
                <!--<div class="user-main-profile data-content">
                    <h4><span>Country</span></h4>
                    <div class="note">-----</div>
                    <h4><span>Birthday</span></h4>
                    <div class="note birthday">Private</div>
                </div>
                <div class="game-skill data-content">
                    <h4><span>Game Experience</span></h4>
                    <div class="note">
                        <span class="test-game-skill intermediate">Intermediate</span>
                    </div>
                </div>
                <div class="game data-content">
                    <h4><span>Systems Owned</span></h4>
                    <div class="note">
                        <div class="device-wiiu"><img src="https://d13ph7xrk1ee39.cloudfront.net/img/wiiu.png?QGR7_Xj-RRrTprcfcKcSWg" class="wiiu-icon"><span>Wii U</span></div>
                        <div class="device-3ds"><img src="https://d13ph7xrk1ee39.cloudfront.net/img/n3ds.png?vQ99K1ReXZjO7Jo44_bNMg" class="n3ds-icon"><span>System in the Nintendo 3DS Family</span></div>
                    </div>
                </div>
                <div class="favorite-game-genre">
                    <h4><span>Favorite Game Genres</span></h4>
                    <div class="note">
                        <span data-test-genre-code="adventure">Adventure</span>
                        <span data-test-genre-code="racing">Racing</span>
                        <span data-test-genre-code="rpg">RPG</span>
                    </div>
                </div>-->
                <div class="data-content">
                    <h4><span>ID</span></h4>
                    <div class="note">#<?=number_format($row['id'])?></div>
                </div>
                <div class="data-content">
                    <h4><span>NNID</span></h4>
                    <div class="note"><?=htmlspecialchars($row['nnid'])?></div>
                </div>
                <div class="data-content">
                    <h4><span>Joined</span></h4>
                    <div class="note"><?=getTimestamp($row['created_at'])?></div>
                </div>
                <div class="data-content">
                    <h4><span>Last Seen</span></h4>
                    <div class="note"><?=getTimestamp($row['last_seen'])?></div>
                </div>
            </div>
        </div>
        <?php
        $stmt = $db->prepare('SELECT communities.id, icon FROM communities LEFT JOIN community_favorites ON communities.id = community WHERE user = ? ORDER BY id DESC LIMIT 10');
        $stmt->bind_param('i', $row['id']);
        $stmt->execute();
        if(!$stmt->error) {
            $result = $stmt->get_result();
            if($result->num_rows > 0) { ?>
                <div class="sidebar-container sidebar-favorite-community">
                    <h4><a href="/users/<?=htmlspecialchars($row['username'])?>/favorites" class="favorite-community-button symbol"><span>Favorite Communities</span></a></h4>
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
                        for($i = $result->num_rows; $i < 10; $i++) { ?>
                            <li class="favorite-community empty">
                                <span class="icon-container empty-icon">
                                    <img class="icon" src="/assets/img/empty.png">
                                </span>
                            </li>
                        <?php } ?>
                    </ul>
                </div>
            <?php }
        }
    } ?>
</div>