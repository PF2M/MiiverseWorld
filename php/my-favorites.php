<?php
require_once('inc/connect.php');
requireAuth();
$_GET['username'] = $_SESSION['username'];
require_once('user-favorites.php');
?>