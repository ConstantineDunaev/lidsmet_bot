<?php
	class Telegram
	{
		private $token;
		private $api_url;
		private $db;
		private $log;
		
		public function __construct($token, $log = null)
		{
			$this->token = $token;
			$this->api_url = 'https://api.telegram.org/bot' . $this->token . '/';
			$this->log = $log;
		}
		
		private function request($method, $params = array()) 
		{
			if (!empty($params) ) 
			{
				$url = $this->api_url . $method . "?" . http_build_query($params);
			} 
			else 
			{
				$url = $this->api_url . $method;
			}
			$answer = file_get_contents($url);
			if (isset($this->log))
			{
				$this->log->ToLog($url);
			}
			return $answer;
		}
		
		private function SendMessage($method, $chat_id, $text, $buttons = null, $inline_buttons = null, $message_id = 0, $photo = null, $gif = null, $sticker = null, 		$disable_web_page_preview = true, $parse_mode = 'html')
		{
			if (!isset($buttons))
			{
				$buttons = array();
			}
			
			$keyboard = json_encode(
									array( 
										   'keyboard' => $buttons,
										   'resize_keyboard' => true,
										   'one_time_keyboard'  => false,
										   'selective'  => false
										  )
								);	
								
			if ($method == 'forceReply')
			{
				$keyboard = json_encode(Array("force_reply" => true));
				$method = 'sendMessage';
			}
			
			if ($method == 'ReplyKeyboardRemove')
			{
				$keyboard = json_encode(Array("remove_keyboard" => true));
				$method = 'sendMessage';
			}
		
			
			if (isset($inline_buttons))
			{
				$keyboard = json_encode(
									array(
										'inline_keyboard' => $inline_buttons	
										)
									);
			}
			
			
			switch ($method)
			{
				case 'sendMessage':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'photo' => $photo,
								  'text' => $text,
								  'disable_web_page_preview' => $disable_web_page_preview,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
								);
					break;
				}
				
				case 'editMessageText':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'message_id' => $message_id,
								  'photo' => $photo,
								  'text' => $text,
								  'disable_web_page_preview' => $disable_web_page_preview,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
							 );
					break;
				}
				
				case 'editMessageCaption':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'message_id' => $message_id,
								  'caption' => $text,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
							 );
					break;
				}
				
				case 'sendPhoto':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'photo' => $photo,
								  'caption' => $text,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
							 );
					break;
				}
				
				case 'sendAnimation':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'animation' => $gif,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
							 );
					break;
				}
				
				case 'sendSticker':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'sticker' => $sticker,
								  'parse_mode'=>$parse_mode,
								  'reply_markup' => $keyboard
							 );
					break;
				}
				
				case 'deleteMessage':
				{
					$msg = array( 
								  'chat_id' => $chat_id,
								  'message_id' => $message_id
							 );
					break;
				}
			}
			
			return $this->request($method, $msg);
		}
		
			//отправка текстового сообщения
		public function SendText($chat_id, $text, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('sendMessage', $chat_id, $text, $buttons, $inline_buttons), true);
		}
		
		public function SendTextMarkdownV2($chat_id, $text, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('sendMessage', $chat_id, $text, $buttons, $inline_buttons, null, null, null, null, null, 'MarkdownV2'), true);
		}
		
		
		//изменение текстового сообщения
		public function EditText($chat_id, $text, $message_id, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('editMessageText', $chat_id, $text, $buttons, $inline_buttons, $message_id), true);
		}
		
		public function EditCaption($chat_id, $text, $message_id, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('editMessageCaption', $chat_id, $text, $buttons, $inline_buttons, $message_id), true);
		}
		
		//отправка фото + возможно  текстовое сообщение
		public function SendPhoto($chat_id, $photo, $text = null, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('sendPhoto', $chat_id, $text, $buttons, $inline_buttons, 0, $photo), true);
		}
		
		//отправка GIF
		public function SendGIF($chat_id, $gif, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('sendAnimation', $chat_id, null, $buttons, $inline_buttons, 0, null, $gif), true);
		}
		
		//отправка стикера
		public function SendSticker($chat_id, $sticker, $buttons = null, $inline_buttons = null)
		{
			return json_decode($this->SendMessage('sendSticker', $chat_id, null, $buttons, $inline_buttons, 0, null, null, $sticker), true);
		}
		
		public function DeleteMessage($chat_id, $message_id)
		{
			return json_decode($this->SendMessage('deleteMessage', $chat_id, null, null, null, $message_id), true);
		}
		
		public function ForceReply($chat_id, $text)
		{
			return json_decode($this->SendMessage('forceReply', $chat_id, $text), true);
		}
		
		public function KeyboardRemove($chat_id, $text)
		{
			return json_decode($this->SendMessage('ReplyKeyboardRemove', $chat_id, $text), true);
		}
		
		public function banChatMember($channel, $chat_id)
		{
			$params = array( 
								'chat_id' => $channel,
								'user_id' => $chat_id,
								'until_date' => (time() + 10),
								'revoke_messages' => true
							 );
			return json_decode($this->request('banChatMember', $params), true);
		}
		
		public function unbanChatMember($channel, $chat_id)
		{
			$params = array( 
								'chat_id' => $channel,
								'user_id' => $chat_id,
								'only_if_banned' => true
							 );
			return json_decode($this->request('unbanChatMember', $params), true);
		}
		
		public function getUpdates($webhook = true)
		{
			
			if ($webhook)
			{
				try
				{
					$updates = file_get_contents('php://input');
				}
				catch (Exception $e)
				{
					$updates = '';
				}
			}
			else
			{	
				try
				{
					$old_update_id = file_get_contents('last_update_id.txt');
				}
				catch (Exception $e)
				{
					$old_update_id = 0;
				}
				echo "old_update_id: $old_update_id\n";
				$old_update_id = $old_update_id + 1;
				$params = array('offset' => $old_update_id);
				$answer = $this->request('getUpdates', $params);
				echo "answer: $answer\n";
				$json = json_decode($answer, true);
				$new_update_id = @$json['result'][0]['update_id'];
				if (isset($new_update_id))
				{
					file_put_contents('last_update_id.txt', $new_update_id);
					$updates = json_encode($json['result'][0]);
				}
				else
				{
					$updates  = '';
				}
				
			}
			return $updates;
		}
		
	}