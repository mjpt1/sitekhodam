<?php
require '_boot.php';
csrf_check();
$_SESSION = [];
session_destroy();
redirect('login.php');
