<?php

session_start();

require 'db_connection.php';

$email = !empty($_POST['email']) ? $_POST['email'] : '';
$password = !empty($_POST['password']) ? $_POST['password'] : '';
$password = sha1($password);

$c = new db_connection();
$sql = "SELECT * FROM user WHERE email = '$email' AND password = '$password'";
$row = $c->query($sql)[0];
$_SESSION['user'] = $row;
if(empty($row)) {
	$login_alert = 'Failed to login!';
   	header("Location: index.php?login_alert={$login_alert}");
}
else if($row['email'] == $email and $row['password'] == $password) {
	header("Location: my_account.php");
}