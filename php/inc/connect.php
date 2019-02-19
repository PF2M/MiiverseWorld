<?php
require_once('../settings.php');
$db = mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if(!$db) {
    http_response_code(500);
    header('Content-Type: text/plain');
    exit('Error: ' . mysqli_connect_error());
}
date_default_timezone_set(TIMEZONE);
$db->query('SET time_zone = "' . $db->real_escape_string(TIMEZONE) . '"');

function checkCommunityBan($id) {
    global $db;
    $stmt = $db->prepare('SELECT banned_at, length FROM community_bans WHERE (user = ? OR ip = ?) AND community = ?'); // TODO: functionize this and add it to create-post.php, posts.php, and replies.php
    global $cidr;
    $stmt->bind_param('isi', $_SESSION['id'], $cidr, $_GET['id']);
    $stmt->execute();
    if($stmt->error) {
        return $stmt->error;
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        return null;
    }
    $row = $result->fetch_assoc();
    if($row['length'] === -1) {
        return 'Permanent';
    }
    $expires = strtotime($row['banned_at'] . ' + ' . $row['length'] . ' days');
    if(time() > $expires) {
        $stmt = $db->prepare('DELETE FROM bans WHERE (user = ? OR ip = ?) AND community = ?');
        $stmt->bind_param('isi', $_SESSION['id'], $cidr, $_GET['id']);
        $stmt->execute();
        return null;
    } else {
        return date('m/d/Y g:i A', $expires);
    }
}
function checkBlocked($source, $target, $either = false) {
    global $db;
    if($either) {
        $stmt = $db->prepare('SELECT COUNT(*) FROM blocks WHERE (source = ? AND target = ?) OR (source = ? AND target = ?)');
        $stmt->bind_param('iiii', $source, $target, $target, $source);
    } else {
        $stmt = $db->prepare('SELECT COUNT(*) FROM blocks WHERE source = ? AND target = ?');
        $stmt->bind_param('ii', $source, $target);
    }
    $stmt->execute();
    if($stmt->error) {
        return null;
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    if($row['COUNT(*)'] > 0) {
        return true;
    }
    return false;
}
function decodeTag($tag) {
    return htmlspecialchars(str_replace('_', ' ', str_replace('%2C', ',', $tag)));
}
function encodeTag($tag) {
    return str_replace('\,', '%2C', str_replace(' ', '_', $tag));
}
function getAvatar($avatar, $has_mh, $feeling = 0) {
    if($has_mh == 0) {
        if(empty($avatar)) {
            return '/assets/img/anonymous-mii.png';
        }
        return htmlspecialchars($avatar);
    }
    return htmlspecialchars('https://mii-secure.cdn.nintendo.net/' . urlencode($avatar) . '_' . urlencode(getFeelingName($feeling)) . '_face.png');
}
function getBody($body, $cutoff = false) {
    if($cutoff === true && mb_strlen($body) > 203) {
        $body = mb_substr($body, 0, 200) . '...';
    }
    return preg_replace('|(https?://([\d\w\.-]+\.[\w\.]{2,6})[^\s\]\[\<\>]*/?)|i', '<a href="$1" target="_blank">$1</a>', htmlspecialchars($body));
}
function getCIDR($ip) {
    if(filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
        $subnets = explode('.', $ip);
        return $subnets[0] . '.' . $subnets[1] . '.' . $subnets[2];
    } else {
        $subnets = explode(':', $ip);
        return $subnets[0] . ':' . $subnets[1] . ':' . $subnets[2] . ':' . $subnets[3];
    }
}
function getCommunityIcon($url) {
    if(!empty($url)) {
        return htmlspecialchars($url);
    } else {
        return '/assets/img/title-icon-default.png';
    }
}
function getEmpathyText($feeling) {
    switch($feeling) {
        case 2:
            return 'Yeah&#10084;';
        case 3:
            return 'Yeah!?';
        case 4:
        case 5:
            return 'Yeah...';
        default:
            return 'Yeah!';
    }
}
function getFeelingName($feeling) {
    switch($feeling) {
        case 1:
            return 'happy';
        case 2:
            return 'like';
        case 3:
            return 'surprised';
        case 4:
            return 'frustrated';
        case 5:
            return 'puzzled';
        default:
            return 'normal';
    }
}
function getIP() {
    if(HTTPS_PROXY) {
        return array_values(array_filter(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])))[0];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
function getPreview($body) {
    if(mb_strlen($body) > 18) {
        return htmlspecialchars(mb_substr($body, 0, 15)) . '...';
    } else {
        return htmlspecialchars($body);
    }
}
function getTimestamp($datetime) {
    $timeSincePost = time() - strtotime($datetime);
    if($timeSincePost < 1){
        return 'Less than a second ago';
    } elseif($timeSincePost < 2) {
        return '1 second ago';
    } elseif($timeSincePost < 60) {
        return strtok($timeSincePost, '.') . ' seconds ago';
    } elseif($timeSincePost < 120) {
        return '1 minute ago';
    } elseif($timeSincePost < 3600) {
        return strtok($timeSincePost / 60, '.') . ' minutes ago';
    } elseif($timeSincePost < 7200) {
        return '1 hour ago';
    } elseif($timeSincePost < 86400) {
        return strtok($timeSincePost / 3600, '.') . ' hours ago';
    } elseif($timeSincePost < 172800) {
        return '1 day ago';
    } elseif($timeSincePost < 341600) {
        return strtok($timeSincePost / 86400, '.') . ' days ago';
    } else {
        return date('m/d/Y g:i A', strtotime($datetime));
    }
}
function initUser($username, $is_general = false) {
    global $db;
    $stmt = $db->prepare('SELECT id, username, nickname, avatar, has_mh, nnid, level, organization, profile_comment, favorite_post, created_at, last_seen, (SELECT image FROM posts WHERE id = favorite_post) AS favorite_post_image, (SELECT IF(COUNT(*) = 0, 0, 1) FROM follows WHERE source = ? AND target = users.id) AS is_following, (SELECT COUNT(*) FROM follows WHERE source = users.id) AS following_count, (SELECT COUNT(*) FROM follows WHERE target = users.id) AS follower_count, (SELECT COUNT(*) FROM posts WHERE created_by = users.id) AS post_count, (SELECT COUNT(*) FROM empathies WHERE source = users.id) AS empathy_count FROM users WHERE username = ?');
    if(!$stmt) {
        showError(500, 'There was an error while preparing to grab that user.');
    }
    $stmt->bind_param('is', $_SESSION['id'], $username);
    $stmt->execute();
    if($stmt->error) {
        showError(500, 'There was an error while grabbing that user.');
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        showError(404, 'The user could not be found.');
    }
    $row = $result->fetch_assoc();
    if($_SESSION['id'] === $row['id']) {
        $title = 'Your Profile';
        $selected = 'mymenu';
    } else {
        if(checkBlocked($_SESSION['id'], $row['id'], true)) {
            $title = 'Error';
            require_once('inc/header.php');
            echo '<div class="no-content"><div class="window-create-new-topic"><p>This user is block';
            if(checkBlocked($_SESSION['id'], $row['id'])) {
                echo 'ed.</p><div class="post-buttons-content"><button type="button" class="button" data-modal-open="#block-page">Unblock</button></div>';
            } else {
                echo 'ing you.</p>';
            }
            ?></div></div>
            <div id="block-page" class="dialog none" data-modal-types="report">
                <div class="dialog-inner">
                    <div class="window">
                        <h1 class="window-title">Unblock this User</h1>
                        <div class="window-body">
                            <form method="post" action="/users/<?=htmlspecialchars(urlencode($row['username']))?>/blacklist.delete.json">
                                <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                <p class="window-body-content">Are you sure you want to unblock this user?</p>
                                <div class="form-buttons">
                                    <input type="button" class="olv-modal-close-button gray-button" value="Cancel">
                                    <input type="submit" class="post-button black-button" value="Unblock">
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            require_once('inc/footer.php');
            exit();
        }
        $title = $row['nickname'] . '\'s Profile';
    }
    global $class;
    require_once('inc/header.php');
    require_once('elements/user-sidebar.php');
    return $row;
}
function requireAuth() {
    if(empty($_SESSION['username'])) {
        http_response_code(401);
        $title = 'Error';
        require_once('inc/header.php');
        ?><div class="warning-content warning-content-forward">
            <div>
                <strong>Welcome to Miiverse World!</strong>
                <p>You must sign in to view this page.</p>
                <a class="button" href="/login">Sign In</a>
            </div>
        </div><?php
        require_once('inc/footer.php');
        exit();
    }
}
function sendNotification($target, $type, $origin = null) {
    global $db;
    if($type < 2) {
        $stmt = $db->prepare('SELECT yeah_notifications FROM users WHERE id = ?');
        $stmt->bind_param('i', $target);
        $stmt->execute();
        if($stmt->error) {
            return false;
        }
        $result = $stmt->get_result();
        if($result->num_rows === 0) {
            return false;
        }
        $row = $result->fetch_assoc();
        if($row['yeah_notifications'] === 0) {
            return true;
        }
    }
    $stmt = $db->prepare('SELECT id, merged FROM notifications WHERE source = ? AND target = ?' . ($origin !== null ? ' AND origin = ' . $origin : '') . ' AND type = ? AND merged IS NOT NULL AND created_at > NOW() - 86400');
    $stmt->bind_param('iii', $_SESSION['id'], $target, $type);
    $stmt->execute();
    if($stmt->error) {
        return false;
    }
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stmt = $db->prepare('UPDATE notifications SET created_at = NOW(), seen = 0 WHERE id = ?');
        $stmt->bind_param('i', $row['merged']);
        $stmt->execute();
        if($stmt->error) {
            return false;
        }
    } else {
        $stmt = $db->prepare('SELECT id FROM notifications WHERE target = ?' . ($origin !== null ? ' AND origin = ' . $origin : '') . ' AND type = ? AND created_at > NOW() - 86400 ORDER BY created_at DESC LIMIT 1');
        $stmt->bind_param('ii', $target, $type);
        $stmt->execute();
        if($stmt->error) {
            return false;
        }
        $result = $stmt->get_result();
        if($result->num_rows === 1) {
            $row = $result->fetch_assoc();
            $stmt = $db->prepare('INSERT INTO notifications (source, target, ' . ($origin !== null ? 'origin, ' : '') . 'type, merged) VALUES (?, ?, ' . ($origin !== null ? $origin . ', ' : '') . '?, ?)');
            $stmt->bind_param('iiii', $_SESSION['id'], $target, $type, $row['id']);
            $stmt->execute();
            if($stmt->error) {
                return false;
            }
            $stmt = $db->prepare('UPDATE notifications SET created_at = NOW(), seen = 0 WHERE id = ?');
            $stmt->bind_param('i', $row['id']);
            $stmt->execute();
            if($stmt->error) {
                return false;
            }
        } else {
            $stmt = $db->prepare('INSERT INTO notifications (source, target, ' . ($origin !== null ? 'origin, ' : '') . 'type) VALUES (?, ?, ' . ($origin !== null ? $origin . ', ' : '') . '?)');
            $stmt->bind_param('iii', $_SESSION['id'], $target, $type);
            $stmt->execute();
            if($stmt->error) {
                return false;
            }
        }
    }
    return true;
}
function showError($responseCode, $message) {
    http_response_code($responseCode);
    $title = 'Error';
    require_once('inc/header.php');
    showNoContent($message); // TODO: check if this needs to be htmlspecialchars'd anywhere, also use this function more it's not used in a lot of no-content places
    require_once('inc/footer.php');
    exit();
}
function showJSONError($responseCode, $errorCode, $message) {
    http_response_code($responseCode);
    header('Content-Type: application/json');
    exit(json_encode(['success' => 0, 'errors' => [['error_code' => $errorCode, 'message' => $message]]]));
}
function showMiniFooter() {
    echo '</div></div></body></html>';
}
function showNoContent($text) {
    echo '<div class="no-content"><p>' . $text . '</p></div>';
}
function uploadImage($file, $width = null, $height = null) {
    if($width !== null && $height !== null && extension_loaded('imagick')) {
        $imagick = new Imagick();
        $imagick->readImageBlob($file);
        if($imagick->getImageFormat() === 'GIF') {
            $imagick = $imagick->coalesceImages();
            $imagick->cropThumbnailImage($width, $height);
            while($imagick->nextImage()) {
                $imagick->cropThumbnailImage($width, $height);
            }
            $imagick = $imagick->deconstructImages();
        } else {
            $imagick->cropThumbnailImage($width, $height);
        }
        $file = $imagick->getImagesBlob();
    }
    if(empty(CLOUDINARY_CLOUDNAME) || empty(CLOUDINARY_UPLOADPRESET)) {
        // TODO: make this support local file uploading?
        return null;
    }
    $mime = finfo_buffer(finfo_open(), $file, FILEINFO_MIME_TYPE);
    $ch = curl_init('https://api.cloudinary.com/v1_1/' . urlencode(CLOUDINARY_CLOUDNAME) . '/image/upload');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query(['upload_preset' => CLOUDINARY_UPLOADPRESET, 'file' => 'data:' . $mime . ';base64,' . base64_encode($file)]));
    $response = curl_exec($ch);
    $responseJSON = json_decode($response);
    $responseCode = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
    if($responseCode > 299 || $responseCode < 200) {
        return null;
    }
    curl_close($ch);
    return $responseJSON->secure_url;
}

session_name('mwsess');
session_start();
if(empty($_SESSION['token'])) {
    $_SESSION['token'] = str_replace('+', '-', str_replace('/', '_', substr(base64_encode(openssl_random_pseudo_bytes(16)), 0, 22)));
}
if(empty($_SESSION['username']) && !empty($_COOKIE['mwauth'])) {
    $stmt = $db->prepare('SELECT source FROM tokens WHERE value = ?');
    if(!$stmt) {
        goto skip;
    }
    $stmt->bind_param('s', $_COOKIE['mwauth']);
    $stmt->execute();
    if($stmt->error) {
        goto skip;
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        unset($_COOKIE['mwauth']);
        goto skip;
    }
    $row = $result->fetch_array();
    $stmt = $db->prepare('SELECT username, avatar, has_mh, level FROM users WHERE id = ?');
    $stmt->bind_param('i', $row['source']);
    $stmt->execute();
    if($stmt->error) {
        goto skip;
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        $stmt = $db->prepare('DELETE FROM tokens WHERE value = ?');
        $stmt->bind_param('s', $_COOKIE['mwauth']);
        $stmt->execute();
        unset($_COOKIE['mwauth']);
        goto skip;
    }

    $_SESSION['id'] = $row['source'];
    $row = $result->fetch_array();
    $_SESSION['username'] = $row['username'];
    $_SESSION['avatar'] = $row['avatar'];
    $_SESSION['has_mh'] = $row['has_mh'];
    $_SESSION['level'] = $row['level'];
}
skip:
if(HTTPS_PROXY) {
    $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
} else {
    $ip = $_SERVER['REMOTE_ADDR'];
}
if(!empty($_SESSION['username'])) {
    $stmt = $db->prepare('UPDATE users SET last_seen = NOW(), ip = ? WHERE id = ?');
    $stmt->bind_param('si', $ip, $_SESSION['id']);
    $stmt->execute();
}
$cidr = getCIDR($ip);
if(!empty($_SESSION['username'])) {
    $stmt = $db->prepare('SELECT banned_at, length FROM bans WHERE user = ? OR ip = ?');
    $stmt->bind_param('is', $_SESSION['id'], $cidr);
} else {
    $stmt = $db->prepare('SELECT banned_at, length FROM bans WHERE ip = ?');
    $stmt->bind_param('s', $cidr);
}
$stmt->execute();
if(!$stmt->error) {
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        if($row['length'] === -1) {
            require_once('ban.php');
            exit();
        }
        $expires = strtotime($row['banned_at'] . ' + ' . $row['length'] . ' days');
        if($row['length'] !== -1 && time() > $expires) {
            if(!empty($_SESSION['username'])) {
                $stmt = $db->prepare('DELETE FROM bans WHERE user = ? OR ip = ?');
                $stmt->bind_param('is', $_SESSION['id'], $cidr);
            } else {
                $stmt = $db->prepare('DELETE FROM bans WHERE ip = ?');
                $stmt->bind_param('s', $cidr);
            }
            $stmt->execute();
            if($stmt->error) {
                showError(500, 'An error occurred while deleting a ban.');
            }
        } else {
            require_once('ban.php');
            exit();
        }
    }
}
?>