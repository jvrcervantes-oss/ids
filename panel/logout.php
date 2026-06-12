<?php
require_once __DIR__ . '/../includes/auth.php';
logout();
redirect(base_url() . '/panel/login.php');
