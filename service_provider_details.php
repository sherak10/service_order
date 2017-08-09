<?php

session_start();

require 'header.php';
require 'inc/html_form.php';
require 'inc/add_comment.php';

$form_add_comment = new html_form('add_comment');  
$conn = new db_connection();

$_SESSION['sp_id'] = 0;
$_SESSION['previous_location_search_engine'] = $_SERVER['REQUEST_URI'];

if(isset($_GET['user_id'])) {
	if(isset($_REQUEST['action']) && isset($_POST['add_comment'])) {
		$form_add_comment->set_values($_POST); 
		add_comment($form_add_comment);
	}
	$user_id = $_GET['user_id'];
	$sql = "SELECT sp_id, fk_occupation_id FROM service_provider WHERE fk_user_id = " . $user_id;
	$sp_id = $conn->query($sql)[0]['sp_id'];
	$_SESSION['sp_id'] = $sp_id;
	$fk_occupation_id = $conn->query($sql)[0]['fk_occupation_id'];
	$sql = "SELECT * FROM user INNER JOIN service_provider ON service_provider.fk_user_id = '$user_id' INNER JOIN occupation ON occupation.occupation_id = '$fk_occupation_id' WHERE user.user_id = '$user_id' ORDER BY category, type"; 
	$profile_details = $conn->query($sql)[0];
	echo '<b>Profile details</b><br>';
	echo 'Name: ' . $profile_details['name'] . '<br>';
	echo 'Surname: ' . $profile_details['surname'] . '<br>';
	echo 'Work address: ' . $profile_details['work_address'] . '<br>';
	echo 'City: ' . $profile_details['city'] . '<br>';
	echo 'Country: ' . $profile_details['country'] . '<br>';
	echo 'Email: ' . $profile_details['email'] . '<br>';
	echo 'Phone number: ' . $profile_details['phone_number'] . '<br>';
	// TODO: involve js
	if(isset($_POST['follow'])) {
		$datetime = date("Y-m-d H:i:s");
		$user_id = $_SESSION['user']['user_id'];
		$data = array("datetime" => $datetime, "fk_sp_id" => $sp_id, "fk_user_id" => $user_id);
		$conn->insert_data('follow', $data);	
		echo '<form action="" method="post">';
		echo "<input id='follow_btn' type='submit' name='unfollow' value='unfollow'/>";
		echo '</form>';
		//header("Location: " . $_SESSION['previous_location_search_engine']);
	}
	else if(isset($_POST['unfollow'])) {
		$user_id = $_SESSION['user']['user_id'];
		$sql = "SELECT follow_id FROM follow WHERE fk_sp_id = '$sp_id' AND fk_user_id = '$user_id'";
		$follow_id = $conn->query($sql)[0]['follow_id'];
		$sql = "DELETE FROM follow WHERE follow_id = '$follow_id'";
		$conn->query($sql);
		echo '<form action="" method="post">';
		echo "<input id='follow_btn' type='submit' name='follow' value='follow'/>";
		echo '</form>';
		//header("Location: " . $_SESSION['previous_location_search_engine']);
	}
	else {
		$user_id = $_SESSION['user']['user_id'];
		$sql = "SELECT fk_user_id FROM service_provider WHERE sp_id = '$sp_id'";
		$fk_user_id = $conn->query($sql)[0]['fk_user_id'];
		if($user_id != $fk_user_id) {
			echo '<form action="" method="post">';
			echo "<input id='follow_btn' type='submit' name='follow' value='follow' />";
			echo '</form>';
		}
	}
	echo '<br>';

	echo '<div id="nav">';
    echo '<a href="#general">General</a>&nbsp';
    echo '<a href="#posts">Posts</a>&nbsp';
    echo '<a href="#purchase">Purchase</a>&nbsp';
	echo '</div>';

	$general_str = 'Work details: ' . $profile_details['details'] . '<br>';
	$general_str .= 'Work experience: ' . $profile_details['experience'] . '<br>'; 
	$purchase_str = 'Service: ' . $profile_details['service'] . '<br>';
	$purchase_str .= 'Price: ' . $profile_details['price'] . '<br>'; 

	echo '<div id="general" class="toggle" style="display:block">' . $general_str . '</div>'; 
	$sql = "SELECT * FROM post WHERE fk_sp_id = '$sp_id'";
	$posts = $conn->query($sql);
	echo '<div id="posts" class="toggle" style="display:none">';
	foreach ($posts as $key => $value) 
		echo 'Content: ' . $value['content'] . ' Date: ' . $value['datetime'] . '<br>';
	echo '</div>';
	echo '<div id="purchase" class="toggle" style="display:none">' . $purchase_str . '</div>';	

	echo '<br><b>Comments</b><br>';
	$sql = "SELECT * FROM comment INNER JOIN user ON user.user_id = comment.fk_user_id WHERE comment.fk_sp_id = '$sp_id'";
	$comments = $conn->query($sql);
	foreach ($comments as $key => $value) {
		echo '<b>' . $value['name'] . ' ' . $value['surname'] . '</b> ';
		echo 'Content: ' . $value['content'] . ' Date: ' . $value['datetime'] . '<br>';
	}
	if(isset($_SESSION['user']) && isset($_POST['add_comment_btn']))
		echo $form_add_comment->get_html('service_provider_details.php?action=add_comment');
	else
		echo 'You have to sign to write comments.';
}
