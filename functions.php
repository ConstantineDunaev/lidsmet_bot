<?php
	function getIMAP($server, $port, $email, $password, $folder = 'INBOX')
	{
		try
		{
			$imap = imap_open("{{$server}:{$port}/imap/ssl}$folder", "{$email}", "{$password}");
		}
		catch (Throwable $t)
		{
			$imap = false;
		}
		return $imap;
	}
	
	function getIMAPfolders($imap, $server)
	{
		$list = imap_list($imap, '{' . $server . '}', '*');		
		foreach ($list as $val) 
		{
			$arr[] = str_replace('{' . $server . '}', '', imap_mutf7_to_utf8($val));
		}
		return $arr;
	}
	

	
	//разбиение полученного сообщения на ассоциативный массив
	function getMessage($str)
	{
		$json = @json_decode($str, true);
		
		$data = @json_decode($json['callback_query']['data'], true);
		if (!isset($data))
		{
			$arr['inline'] = false;
			$arr['action'] = @$json['message']['text'];
			$arr['chat_id'] = @$json['message']['chat']['id'];
			$arr['username'] = @$json['message']['from']['username'];
			$arr['firstname'] = @$json['message']['chat']['first_name'];
			$arr['lastname'] = @$json['message']['chat']['last_name'];
			$arr['message_id'] = @$json['message']['message_id'];
			$arr['param'] = '';
			$arr['caption'] = @$json['message']['caption'];
			$arr['file_id'] = @$json['message']['photo'][0]['file_id'];
			
			if ((!isset($arr['action'])) and (isset($arr['caption'])))
			{
				$arr['action'] = $arr['caption'];
			}
		}
		else
		{
			$arr['inline'] = true;
			$arr['action'] = $data['action'];
			$arr['param'] = $data['param'];
			$arr['chat_id'] = @$json['callback_query']['from']['id'];
			$arr['message_id'] = @$json['callback_query']['message']['message_id'];
			$arr['entities'] = @$json['callback_query']['message']['entities'];
			$arr['source_text'] = @$json['callback_query']['message']['text'];
			
			if(!isset($arr['source_text']))
			{
				$arr['source_text'] = @$json['callback_query']['message']['caption'];
			}
			
			if ((isset($arr['entities'])) and (isset($arr['source_text'])))
			{
				$arr['source_text'] = ApplyEntities($arr['source_text'], $arr['entities']);
			}
		}
		
		$arr['channel'] = false;
		
		if (isset($json['my_chat_member']))
		{
			$arr['channel'] = true;
			$arr['channel_id'] = @$json['my_chat_member']['chat']['id'];
			$arr['channel_title'] = @$json['my_chat_member']['chat']['title'];
			$arr['channel_status'] = @$json['my_chat_member']['new_chat_member']['status'];
		}
		
		return $arr;
	}


	//получение страницы
	function GetPage($url)
	{
		$opts = array(
		  'http'=>array(
			'method'=>"GET",
			'header'=> "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/85.0.4183.121 Safari/537.36"
		  )
		);
		$context = stream_context_create($opts);
		$contents = @file_get_contents($url, false, $context);
		unset($opts);
		return $contents;
	}


	//получение данных из страницы
	function GetFromHTML($sub1, $sub2, $source, &$i )
	{
		if ($i < 0)
		{
			return '';
		}
		
		$iPosSub1=0;
		$iPosFinish=0;
			
		$iPosSub1=stripos($source, $sub1, $i);
		if (!$iPosSub1 == false) 
		{
			$iLengthSub1=strlen($sub1);
			$iPosStart=$iPosSub1+$iLengthSub1;
			if ($sub2<>'')
			{
				$iPosFinish=stripos($source, $sub2, $iPosStart);
			}
			else
			{
				$iPosFinish=strlen($source)+1;
			}
		}
		
		if (!$iPosFinish == false) 
		{
			$iCount=$iPosFinish-$iPosStart;
			$sTmp=trim(substr($source, $iPosStart, $iCount));
			$i=$iPosFinish;
		}
		else
		{
			$i=-1;
			$sTmp='';
		}
		return $sTmp;
	}
	
	//применение форматирования
	function ApplyEntities($text, $entities)
	{

		if (!isset($entities))
		{
			return $text;
		}

		$arr = Array();
		array_pad($arr, mb_strlen($text), '');
		for($i=0;$i<count($entities);$i++)
		{
			$off = $entities[$i]['offset'];
			$len = $entities[$i]['length'];
			switch ($entities[$i]['type'])
			{
				case 'bold':
				{
					$tag1 = '<b>';
					$tag2 = '</b>';
					break;
				}
				case 'underline':
				{
					$tag1 = '<u>';
					$tag2 = '</u>';
					break;
				}
				case 'italic':
				{
					$tag1 = '<i>';
					$tag2 = '</i>';
					break;
				}
				
			}
			
			$arr[$off] .= $tag1;
			$arr[$off + $len] = $tag2 . $arr[$off + $len];
		}
		
		for($i=0;$i<mb_strlen($text);$i++)
		{
			$result .= $arr[$i] . mb_substr($text, $i, 1);
		}
		unset($arr);
		return $result;
	}
	
	//функция преобразования многомерного ассоциативного массива в одномерный
	function ConvertArray($arr)
	{
		$result = Array();
		for ($i = 0; $i<count($arr); $i++) 
		{
			$result[$arr[$i]['code']] = $arr[$i]['value'];
		}
		return $result;
	}
	

?>