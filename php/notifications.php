<?php
require_once('inc/connect.php');
requireAuth();
$title = 'Notifications';
$selected = 'news';
require_once('inc/header.php');
$row = initUser($_SESSION['username'], true);
?><div class="main-column messages">
    <div class="post-list-outline">
        <h2 class="label">Notifications</h2>
        <div class="list news-list">
            <?php
            $stmt = $db->prepare('SELECT notifications.id, notifications.created_at, source, origin, type, merged, seen, username, nickname, avatar, has_mh, level, users.status, IF(type IN (0, 2, 3), posts.body, IF(type = 1, replies.body, NULL)) AS body FROM notifications LEFT JOIN users ON source = users.id AND type != 5 LEFT JOIN posts ON posts.id = origin AND type IN (0, 2, 3) LEFT JOIN replies ON replies.id = origin AND type = 1 WHERE target = ? AND merged IS NULL ORDER BY notifications.created_at DESC LIMIT 25');
            $stmt->bind_param('i', $_SESSION['id']);
            $stmt->execute();
            if($stmt->error) {
                echo '<div class="no-content"><p>An error occurred while grabbing notifications.</p></div>';
            } else {
                $result = $stmt->get_result();
                if($result->num_rows === 0) {
                    echo '<div class="no-content"><p>No notifications.</p></div>';
                } else {
                    while($row = $result->fetch_assoc()) {
                        echo '<div class="news-list-content trigger';
                        if($row['seen'] === 0) {
                            echo ' notify';
                        }
                        echo '" id="' . $row['id'] . '" data-href="/';
                        switch($row['type']) {
                            case 1:
                                echo 'replies/' . $row['origin'];
                                break;
                            case 4:
                                echo 'users/' . $_SESSION['username'] . '/followers';
                                break;
                            case 5:
                                echo 'admin_messages';
                                break;
                            default:
                                echo 'posts/' . $row['origin'];
                        }
                        echo '" tabindex="0"><a href="/';
                        if($row['type'] !== 5) {
                            echo 'users/' . htmlspecialchars($row['username']);
                        } else {
                            echo 'admin_messages';
                        }
                        echo '" class="icon-container';
                        if($row['level'] > 0) {
                            echo ' official-user';
                        }
                        echo '"><img class="icon" src="';
                        if($row['type'] !== 5) {
                            echo getAvatar($row['avatar'], $row['has_mh']);
                        } else {
                            echo '/assets/img/miiverse-administrator.png';
                        }
                        echo '"></a><div class="body">';
                        if($row['type'] !== 5) {
                            if($row['type'] === 4) {
                                echo 'Followed by ';
                            }
                            echo '<a href="/users/' . htmlspecialchars($row['username']) . '" class="nick-name">' . htmlspecialchars($row['nickname']) . '</a>';
                            $stmt = $db->prepare('SELECT username, nickname FROM notifications LEFT JOIN users ON source = users.id WHERE merged = ? AND source != ? GROUP BY source, notifications.created_at ORDER BY notifications.created_at LIMIT 20');
                            $stmt->bind_param('ii', $row['id'], $row['source']);
                            $stmt->execute();
                            if(!$stmt->error) {
                                $mresult = $stmt->get_result();
                                if($mresult->num_rows > 0) {
                                    $mrow = $mresult->fetch_assoc();
                                    if($mresult->num_rows === 1) {
                                        echo ' and <a href="/users/' . htmlspecialchars($mrow['username']) . '" class="nick-name">' . htmlspecialchars($mrow['nickname']) . '</a>';
                                    } else {
                                        echo ', <a href="/users/' . htmlspecialchars($mrow['username']) . '" class="nick-name">' . htmlspecialchars($mrow['nickname']) . '</a>';
                                        $mrow = $mresult->fetch_assoc();
                                        if($mresult->num_rows === 2) {
                                            echo ', and <a href="/users/' . htmlspecialchars($mrow['username']) . '" class="nick-name">' . htmlspecialchars($mrow['nickname']) . '</a>';
                                        } else {
                                            echo ', <a href="/users/' . htmlspecialchars($mrow['username']) . '" class="nick-name">' . htmlspecialchars($mrow['nickname']) . '</a> and ' . ($mresult->num_rows - 3) . ' others';
                                        }
                                    }
                                }
                                $mrow = $mresult->fetch_all(MYSQLI_ASSOC);
                            }
                            switch($row['type']) {
                                case 0:
                                    echo ' gave <a href="/posts/' . $row['origin'] . '" class="link">your post&nbsp;(' . getPreview($row['body']) . ')</a> a Yeah';
                                    break;
                                case 1:
                                    echo ' gave <a href="/replies/' . $row['origin'] . '" class="link">your comment&nbsp;(' . getPreview($row['body']) . ') a Yeah';
                                    break;
                                case 2:
                                    echo ' commented on <a href="/posts/' . $row['origin'] . '" class="link">your post&nbsp;(' . getPreview($row['body']) . ')</a>';
                                    break;
                                case 3:
                                    echo ' commented on <a href="/posts/' . $row['origin'] . '" class="link">' . htmlspecialchars($row['nickname']) . '\'s post&nbsp;(' . getPreview($row['body']) . ')</a>';
                            }
                            echo '. <span class="timestamp">' . getTimestamp($row['created_at']) . '</span>';
                            if($row['type'] === 4 && $row['status'] === 0) {
                                $stmt = $db->prepare('SELECT COUNT(*) FROM follows WHERE source = ? AND target = ?');
                                $stmt->bind_param('ii', $_SESSION['id'], $row['source']);
                                $stmt->execute();
                                if(!$stmt->error) {
                                    $fresult = $stmt->get_result();
                                    $frow = $fresult->fetch_assoc();
                                    if($frow['COUNT(*)'] === 0) {
                                        echo '<div class="toggle-button"><button type="button" data-action="/users/' . htmlspecialchars($row['username']) . '.follow.json" class="follow-button button symbol">Follow</button><button type="button" class="button unfollow-button relationship-button symbol none" disabled>Follow</button></div>';
                                    }
                                }
                            }
                        } else {
                            echo '<p class="title"><span class="nick-name">Miiverse World Administration</span><span class="id-name">mvworld_admin</span></p><p class="text">You have received a notification from the administrators.</p>';
                        }
                        echo '</div></div>';
                    }
                }
                $stmt = $db->prepare('DELETE FROM notifications WHERE created_at < ? AND target = ? AND merged IS NULL');
                $stmt->bind_param('si', $row['created_at'], $_SESSION['id']);
                $stmt->execute();
                $stmt = $db->prepare('UPDATE notifications SET seen = 1 WHERE target = ?');
                $stmt->bind_param('i', $_SESSION['id']);
                $stmt->execute();
            }
            ?>
        </div>
    </div>
</div><?php
require_once('inc/footer.php');
?>