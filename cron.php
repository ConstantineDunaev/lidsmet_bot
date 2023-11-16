<?php
	include_once __DIR__ . "/functions.php";
	include_once __DIR__ . "/classes/db.php";

	$ini = parse_ini_file("settings.ini");
	$db = new db($ini['host_db'], $ini['user_db'], $ini['password_db'], $ini['schema_db']);
	
	$charset = $htmlmsg = $plainmsg = $attachments = $tmp = '';
	
	function getmsg($mbox,$mid) 
	{
		global $charset,$htmlmsg,$plainmsg,$attachments,$db;
		$htmlmsg = $plainmsg = $charset = $tmp ='';
		$attachments = array();
		$h = imap_header($mbox,$mid);
		$s = imap_fetchstructure($mbox,$mid);
		if (!$s->parts) 
			getpart($mbox,$mid,$s,0);  
		else 
		{ 
			foreach ($s->parts as $partno0=>$p)
				getpart($mbox,$mid,$p,$partno0+1);
		}
	}

	function getpart($mbox,$mid,$p,$partno) 
	{
		global $htmlmsg,$plainmsg,$charset,$attachments, $tmp,$db;
		
		$params = array();
		if ($p->parameters)
			foreach ($p->parameters as $x)
				$params[strtolower($x->attribute)] = $x->value;
		if ($p->dparameters)
			foreach ($p->dparameters as $x)
				$params[strtolower($x->attribute)] = $x->value;
		$charset = $params['charset']; 
		
		
		$data = ($partno) ? imap_fetchbody($mbox,$mid,$partno):  imap_body($mbox,$mid);  
		

		//echo '<br><br>';
		
		if ($charset)
		{	//echo 'до:' . $data;
			//echo '<br><br>';
			if ($p->encoding==4)
			{
				$data = iconv($charset, 'utf-8', quoted_printable_decode($data));
			}
			elseif ($p->encoding==3)
			{
				$data =  iconv($charset, 'utf-8', base64_decode($data));
			}
			//$db->ExecSQL('insert into t_log (message) values ("'.$data.'")');
			//$str = mb_substr($data, mb_stripos($str, "\n \n") + 3);
			//$data = iconv('windows-1251', 'utf-8', $str);
			//echo 'после:' . $data;
			//echo '<br><br>';
		}
		
		/*
		if ($params['filename'] || $params['name']) 
		{
			$filename = ($params['filename'])? $params['filename'] : $params['name'];
			$attachments[$filename] = $data;  
		}
		*/
		if ($p->type==0 && $data) 
		{
			if (strtolower($p->subtype)=='plain')
			{
				$plainmsg .= trim($data) ."\n\n";
			}
			else
			{
				$htmlmsg .= trim($data) ."<br><br>";	
			}
		}
		elseif ($p->type==2 && $data) 
		{
			$plainmsg  .= trim($data)."\n\n";
		}
		

		if ($p->parts) 
		{
			foreach ($p->parts as $partno0=>$p2)
				getpart($mbox,$mid,$p2,$partno.'.'.($partno0+1));  
		}
		
		echo '<hr>';
	}	

	
	$date = date("Y-m-d");
	echo $date;
	echo '<br>';
	$accounts = $db->ExecSQL("select * from t_account where deleted = 0 and active = 1");
	if (count($accounts) > 0)
	{
		for($i=0;$i<count($accounts);$i++)
		{
			echo "account: {$accounts[$i]['login']}";
			echo "<br>";
			echo "folder: ". $accounts[$i]['folder'];
			echo "<br>";
			$imap = getIMAP($accounts[$i]['server'], $accounts[$i]['port'], $accounts[$i]['login'], 
								$accounts[$i]['password'], imap_utf8_to_mutf7($accounts[$i]['folder']) );

			//$folders = getIMAPfolders($imap, $accounts[$i]['server']);
			//print_r($folders);
			echo "<br>";
			$mails_id = imap_search($imap, 'SINCE '.$date);
			//$mails_id = imap_search($imap, 'SINCE 2022-05-26');
			//print_r($mails_id);
			//echo "<br>";
			
			if (is_array($mails_id ))
			{
				foreach ($mails_id as $num) 
				{
					$header = imap_header($imap, $num);
					
					$subj = imap_utf8($header->subject);
					if (substr($subj, 0, 7) == '=?utf-8')
					{
						$subj = mb_decode_mimeheader($subj);
					}
					$sender = imap_utf8($header->from[0]->mailbox) . '@' . imap_utf8($header->from[0]->host);
					$message_id = imap_utf8($header->message_id);
					
					$exists = $db->ExecSQL("select count(1) as cnt from t_letter where account_id = {$accounts[$i]['id']} and message_id = '{$message_id}'");
					//echo $exists[0]['cnt'];
					if ($exists[0]['cnt'] == 0)
					{
						getmsg($imap, $num);
						
						if ($htmlmsg != '')
							$html_body = $htmlmsg;
						else 
							$html_body = $plainmsg; 
						
						$html_body = trim(str_replace("'", '"', $html_body));
						
						//$body = strip_tags($html_body);
						//$pattern = '/\s{2,}/';
						//$replacement = "\n";
						//$body = preg_replace($pattern, $replacement, $body);
						//$pattern = '/(^.+{(\r\n|\n|\r))(^.+(\r\n|\n|\r)){1,}(})/m';
						//$replacement = '';
						//$body = preg_replace($pattern, $replacement, $body);
						//$body = mb_substr(trim($body), 0, 500);
						$body = '';
						$body = trim(str_replace("'", '"', strip_tags($tmp)));
						$subj = trim(str_replace("'", '"', strip_tags($subj)));
						$sender = trim(str_replace("'", '"', strip_tags($sender)));
						
						$db->ExecSQL("insert into t_letter (account_id, message_id, subj, sender, body, html_body, charset) values 
						({$accounts[$i]['id']}, '$message_id', '$subj', '$sender', '$body', '$html_body', '$charset')");
					}	
				}
			}
			imap_close($imap);
		}
	}

	
	 
	
	