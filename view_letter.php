<?php
	include_once __DIR__ . "/functions.php";
	include_once __DIR__ . "/classes/db.php";
	
	$ini = parse_ini_file("settings.ini");
	$db = new db($ini['host_db'], $ini['user_db'], $ini['password_db'], $ini['schema_db']);
	
	$letter_id = $_GET['letter_id'];
	
	if (!$letter_id)
		exit();
	
	$arr = $db->ExecSQL("select sender, subj, html_body from t_letter where id = $letter_id");
	if (is_array($arr))
		if (count($arr) == 1)
		{
			echo '<b>От кого:</b> <a href="mailto:'.$arr[0]['sender'].'">'.$arr[0]['sender'].'</a>';
			echo '<br>';
			echo '<br>';
			echo '<b>Тема:</b> '.$arr[0]['subj'];
			echo '<br>';
			echo '<hr>';
			echo '<br>';
			echo $arr[0]['html_body'];
		}