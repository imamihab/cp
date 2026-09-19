<?php
if(session_status()===PHP_SESSION_NONE) session_start();
function require_login():void{if(empty($_SESSION["user"])){header("Location: /snack-inventory/auth/login.php");exit;}}
