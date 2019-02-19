<?php
require_once('connect.php');
?><!DOCTYPE html>
<html>
    <head>
        <meta charset="utf-8">
        <title><?=isset($title) ? htmlspecialchars($title) . ' - ' : ''?>Miiverse World</title>
        <meta http-equiv="content-style-type" content="text/css">
        <meta http-equiv="content-script-type" content="text/javascript">
        <meta name="format-detection" content="telephone=no">
        <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
        <meta name="apple-mobile-web-app-title" content="Miiverse World">
        <meta name="description" content="Miiverse World is a service that lets you communicate with other users from around the world.">
        <meta name="keywords" content="Miiverse,clone,Indigo,Closedverse,Cedar,PF2M,Nintendo,Hatena">
        <meta property="og:locale" content="en_US">
        <!--<meta property="og:locale:alternate" content="ja_JP">
        <meta property="og:locale:alternate" content="es_LA">
        <meta property="og:locale:alternate" content="fr_CA">
        <meta property="og:locale:alternate" content="pt_BR">
        <meta property="og:locale:alternate" content="en_GB">
        <meta property="og:locale:alternate" content="fr_FR">
        <meta property="og:locale:alternate" content="es_ES">
        <meta property="og:locale:alternate" content="de_DE">
        <meta property="og:locale:alternate" content="it_IT">
        <meta property="og:locale:alternate" content="nl_NL">
        <meta property="og:locale:alternate" content="pt_PT">
        <meta property="og:locale:alternate" content="ru_RU">-->
        <meta property="og:title" content="<?=isset($title) ? htmlspecialchars($title) . ' - ' : ''?>Miiverse World">
        <meta property="og:type" content="article">
        <meta property="og:url" content="http<?=($_SERVER['HTTPS'] || HTTPS_PROXY) ? 's' : ''?>://<?=$_SERVER['SERVER_NAME']?>">
        <meta property="og:description" content="Miiverse World is a service that lets you communicate with other users from around the world.">
        <meta property="og:site_name" content="Miiverse World">
        <meta name="twitter:card" content="summary">
        <meta name="twitter:domain" content="<?=$_SERVER['SERVER_NAME']?>">
        <!--<link rel="alternate" hreflang="x-default" href="https://miiverse.nintendo.net/">
        <link rel="alternate" hreflang="ja-JP" href="https://miiverse.nintendo.net/?locale.lang=ja-JP">
        <link rel="alternate" hreflang="en-US" href="https://miiverse.nintendo.net/?locale.lang=en-US">
        <link rel="alternate" hreflang="es-MX" href="https://miiverse.nintendo.net/?locale.lang=es-MX">
        <link rel="alternate" hreflang="fr-CA" href="https://miiverse.nintendo.net/?locale.lang=fr-CA">
        <link rel="alternate" hreflang="pt-BR" href="https://miiverse.nintendo.net/?locale.lang=pt-BR">
        <link rel="alternate" hreflang="en-GB" href="https://miiverse.nintendo.net/?locale.lang=en-GB">
        <link rel="alternate" hreflang="fr-FR" href="https://miiverse.nintendo.net/?locale.lang=fr-FR">
        <link rel="alternate" hreflang="es-ES" href="https://miiverse.nintendo.net/?locale.lang=es-ES">
        <link rel="alternate" hreflang="de-DE" href="https://miiverse.nintendo.net/?locale.lang=de-DE">
        <link rel="alternate" hreflang="it-IT" href="https://miiverse.nintendo.net/?locale.lang=it-IT">
        <link rel="alternate" hreflang="nl-NL" href="https://miiverse.nintendo.net/?locale.lang=nl-NL">
        <link rel="alternate" hreflang="pt-PT" href="https://miiverse.nintendo.net/?locale.lang=pt-PT">
        <link rel="alternate" hreflang="ru-RU" href="https://miiverse.nintendo.net/?locale.lang=ru-RU">-->
        <link rel="shortcut icon" href="/assets/img/favicon.ico">
        <link rel="apple-touch-icon" sizes="57x57" href="/assets/img/apple-touch-icon-57x57.png">
        <link rel="apple-touch-icon" sizes="114x114" href="/assets/img/apple-touch-icon-114x114.png">
        <link rel="apple-touch-icon" sizes="72x72" href="/assets/img/apple-touch-icon-72x72.png">
        <link rel="apple-touch-icon" sizes="144x144" href="/assets/img/apple-touch-icon-144x144.png">
        <link rel="stylesheet" type="text/css" href="/assets/css/offdevice.css">
        <script src="/assets/js/complete-en.js"></script>
        <!--<script type="application/json" class="js-ad-slot-spec" data-spec='{"laptop_size":[300,250],"tablet_size":[468,60],"smartphone_size":[320,100],"ad_key":"div-gpt-ad-1438756213573-1","ad_unit_path":"/94393651/miiverse-communities-top"}'></script>
        <script type="application/json" class="js-ad-slot-spec" data-spec='{"laptop_size":[300,250],"tablet_size":[468,60],"smartphone_size":[320,100],"ad_key":"div-gpt-ad-1438756213573-0","ad_unit_path":"/94393651/miiverse-communities-bottom"}'></script>-->
    </head>
    <body class="<?php if(empty($_SESSION['username'])) echo 'guest '; if(!empty($class)) echo $class; if(isset($reborn)) echo '" id="miiverse-will-reborn'; echo '" data-token="' . $_SESSION['token']; ?>">
        <div id="wrapper">
            <div id="sub-body">
                <menu id="global-menu">
                    <li id="global-menu-logo">
                        <h1><a href="/"><img src="/assets/img/menu-logo.png" alt="Miiverse World" width="165" height="30"></a></h1>
                    </li>
                    <?php if(empty($_SESSION['username'])) { ?><li id="global-menu-login">
                            <form id="login_form" action="/login" method="post">
                                <input type="image" alt="Sign in" src="/assets/img/en/signin_base.png">
                            </form>
                        </li>
                    <?php } else { ?><li id="global-menu-list">
                        <ul>
                            <li id="global-menu-mymenu"<?php if(!empty($selected) && $selected == 'mymenu') echo ' class="selected"'; ?>>
                                <a href="/users/<?=htmlspecialchars($_SESSION['username'])?>">
                                    <span class="icon-container">
                                        <img src="<?=getAvatar($_SESSION['avatar'], $_SESSION['has_mh'], 0)?>" alt="User Page">
                                    </span>
                                    <span>User Page</span>
                                </a>
                            </li>
                            <li id="global-menu-community"<?php if(!empty($selected) && $selected == 'community') echo ' class="selected"'; ?>>
                                <a href="/" class="symbol">
                                    <span>Homepage</span>
                                </a>
                            </li>
                            <li id="global-menu-feed"<?php if(!empty($selected) && $selected == 'feed') echo ' class="selected"'; ?>>
                                <a href="/discover" class="symbol">
                                    <span>Discover</span>
                                </a>
                            </li>
                            <li id="global-menu-news"<?php if(!empty($selected) && $selected == 'news') echo ' class="selected"'; ?>>
                                <a href="/news/my_news" class="symbol"></a>
                            </li>
                            <li id="global-menu-my-menu">
                                <button class="symbol js-open-global-my-menu open-global-my-menu" id="my-menu-btn"></button>
                                <menu id="global-my-menu" class="invisible none">
                                    <li><a href="/settings/profile" class="symbol my-menu-profile-setting"><span>Profile Settings</span></a></li>
                                    <li><a href="/settings/account" class="symbol my-menu-miiverse-setting"><span>Account Settings</span></a></li>
                                    <li><a href="/my_blacklist" class="symbol my-menu-miiverse-setting"><span>Blocked Users</span></a></li>
                                    <li><a href="/rules" class="symbol my-menu-guide"><span>Site Rules</span></a></li>
                                    <li><a href="https://pf2m.com/contact/" class="symbol my-menu-guide"><span>Contact Us</span></a></li>
                                    <li>
                                        <form action="/logout" method="post" id="my-menu-logout" class="symbol">
                                            <input type="hidden" name="token" value="<?=$_SESSION['token']?>">
                                            <input type="submit" value="Log Out">
                                        </form>
                                    </li>
                                </menu>
                            </li>
                    <?php } ?>
                </menu>
            </div>
            <div id="main-body">