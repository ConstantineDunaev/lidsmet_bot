<?php
	set_time_limit(30);
	
	ini_set('error_reporting', E_ALL);
	ini_set('display_errors', 1);
	ini_set('display_startup_errors', 1);
	ini_set('html_errors', 1);
	
	include_once __DIR__ . "/classes/log.php";
	include_once __DIR__ . "/classes/db.php";
	include_once __DIR__ . "/classes/user.php";
	include_once __DIR__ . "/classes/telegram.php";
	include_once __DIR__ . "/functions.php";
	
	$ini = parse_ini_file("settings.ini");
	
	$db = new db($ini['host_db'], $ini['user_db'], $ini['password_db'], $ini['schema_db']);
	$log = new log($db);
	$telegram = new telegram($ini['token'], $log);
	
	function getRequest($request_id) {
		global $db;
		$arr = $db->ExecSQL("select a.id, account_id, subj, body, sender, pseudonym, state_id, fullname 
		from t_letter a inner join t_account b on a.account_id = b.id inner join t_state c on a.state_id = c.id where a.id = $request_id");
		$message = "<b>Заявка №{$arr[0]['id']} {$arr[0]['fullname']}</b>\n\n<b>От:</b> {$arr[0]['sender']}\n<b>Тема:</b> {$arr[0]['subj']}\n<b>Куда</b>: {$arr[0]['pseudonym']}";
		$inline[] = [['text'=>'Посмотреть в браузере','url'=>"https://zayvka.ru/lidsmet_bot/view_letter.php?letter_id={$arr[0]['id']}"]];
		if (in_array($arr[0]['state_id'], [1, 2])) {
			$inline[] = [['text'=>'Спам','callback_data'=>'{"action":"set_state7","param":"' . $arr[0]['id'] . '"}']];
			$inline[] = [['text'=>'Не актуально','callback_data'=>'{"action":"set_state3","param":"' . $arr[0]['id'] . '"}']];
			$inline[] = [['text'=>'Отправил КП','callback_data'=>'{"action":"set_state4","param":"' . $arr[0]['id'] . '"}']];
			$inline[] = [['text'=>'Выставил счет','callback_data'=>'{"action":"set_state5","param":"' . $arr[0]['id'] . '"}']];
		}
		return ['message' => $message, 'inline' => $inline];
	}

	$str = $telegram->getUpdates(true);
	if ($str == '')
		exit();

	$log->ToLog("input: $str");
	
	$input_message = getMessage($str);
	$log->ToLog(print_r($input_message, true));
	
	$user = new User($db, $input_message['chat_id'], $input_message['username'], $input_message['firstname'], $input_message['lastname']);
	$user->load();
	if (!isset($user->id))
	{
		$user->save();
	}
	
	$action = $input_message['action'];
	$param = $input_message['param'];
	$arr = $user->getLastOperation();	
	$last_operation = $arr[0]['operation'];
	$last_param = $arr[0]['param'];
	$last_message_id = $arr[0]['out_message_id'];
	
	if(isset($input_message['file_id']))
	{
		$action = 'update_request';
	}
	
	switch ($action)
	{
		case '/start':
		case 'Отменить добавление аккаунта':
		{
			goto label_default;
		}
		
		case 'Добавить аккаунт электронной почты':
		{
			if ($user->role == 1)
			{
				$message = 'Введите адрес email:';
				$menu = array(array('Отменить добавление аккаунта'));
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Аккаунты электронной почты':
		{
			if ($user->role == 1)
			{
				$menu = array(array('Добавить аккаунт электронной почты'), array('Вернуться в главное меню'));
				$message = 'Аккаунты электронной почты:';
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				
				$arr = $db->ExecSQL("select * from t_account where user_id = {$user->id} and deleted = 0");
				if (count($arr) > 0)
				{
					for($i=0;$i<count($arr);$i++)
					{
						$message = "<b>Аккаунт №{$arr[$i]['id']}</b>\n\n<b>Адрес:</b> {$arr[$i]['login']}\n<b>Пароль:</b> {$arr[$i]['password']}\n<b>IMAP сервер:</b> {$arr[$i]['server']}\n<b>IMAP порт:</b> {$arr[$i]['port']}\n<b>Папка</b>: {$arr[$i]['folder']}\n<b>Псевдоним</b>: {$arr[$i]['pseudonym']}";
						if ($arr[$i]['active'] == 0)
						{
							$inline[] = [['text'=>'Активировать','callback_data'=>'{"action":"account_activate","param":"' . $arr[$i]['id'] . '"}']];
						}
						else
						{
							$inline[] = [['text'=>'Отключить','callback_data'=>'{"action":"account_deactivate","param":"' . $arr[$i]['id'] . '"}']];
							$inline[] = [['text'=>'Выбрать папку','callback_data'=>'{"action":"select_folder","param":"' . $arr[$i]['id'] . '"}']];
						}
						$inline[] = [['text'=>'Удалить','callback_data'=>'{"action":"account_delete","param":"' . $arr[$i]['id'] . '"}']];
						$sended = $telegram->SendText($user->chat_id, $message, null, $inline);
						unset($inline);
					}
				}
				else
				{
					$message = 'Нет добавленных аккаунтов электронной почты';
					$sended = $telegram->SendText($user->chat_id, $message, $menu);
				}
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Пользователи бота':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL("select * from t_user where id <> {$user->id}");
				if (count($arr) > 0)
				{
					for($i=0;$i<count($arr);$i++)
					{
						$message = "<b>ChatID:</b> {$arr[$i]['chat_id']}\n<b>Username:</b> {$arr[$i]['username']}\n<b>Firstname:</b> {$arr[$i]['firstname']}\n<b>Lastname</b>: {$arr[$i]['lastname']}\n<b>Дневной лимит заявок:</b> {$arr[$i]['day_limit']}";
						if ($arr[$i]['role'] == 2)
						{
							$inline[] = [['text'=>'✅ Менеджер','callback_data'=>'{"action":"unset_manager","param":"'. $arr[$i]['id'] . '"}']];
							$inline[] = [['text'=>'Лимит','callback_data'=>'{"action":"set_limit","param":"'. $arr[$i]['id'] . '"}']];
						}
						else
						{
							$inline[] = [['text'=>'Менеджер','callback_data'=>'{"action":"set_manager","param":"'. $arr[$i]['id'] . '"}']];
						}
						$sended = $telegram->SendText($user->chat_id, $message, null, $inline);
						unset($inline);
					}
				}
				else
				{
					$message = 'Нет пользователей';
					$sended = $telegram->SendText($user->id, $message);
				}
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Отчет':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL("select trim(concat(username, ' ', firstname, ' ', lastname)) as info_user, ifnull(cnt, 0) as cnt 
								from t_user a left join (select manager_id, count(1) as cnt from t_letter group by manager_id) b on a.id = b.manager_id
								where role = 2
								union all
								select '-----------' as info_user, '' as cnt
								union all
								select 'Всего заявок' as info_user, ifnull(count(1), 0) from t_letter
								union all
								select 'Из них необработанных' as user_info, ifnull(count(1), 0) from t_letter where state_id = 1");
				$max_length_info_user = 0;
				$spaces = '                                  ';
				for($i=0;$i<count($arr);$i++)
				{
					$max_length_user_info = max($max_length_info_user, $arr[$i]['info_user']);
				}
				
				for($i=0;$i<count($arr);$i++)
				{
					$message .= '`' . $arr[$i]['info_user'] . substr($spaces, 0, $max_length_info_user - mb_strlen($arr[$i]['info_user']) + 2) . $arr[$i]['cnt'] . "`\n";
				}
				$today = date('Y-m-d');
				$inline[] = [['text'=>'Отчет по менеджерам за день','url'=>"https://zayvka.ru/lidsmet_bot/time_report.php?from={$today}&to={$today}"]];
				
				$start_week = date('Y-m-d', strtotime('monday this week'));
				$end_week = date('Y-m-d', strtotime('monday this week') + (7 * 24 * 3600) - 1);
				$inline[] = [['text'=>'Отчет по менеджерам за неделю','url'=>"https://zayvka.ru/lidsmet_bot/time_report.php?from={$start_week}&to={$end_week}"]];
				
				$start_month = date('Y-m-01');
				$end_month = date('Y-m-t');
				$inline[] = [['text'=>'Отчет по менеджерам за месяц','url'=>"https://zayvka.ru/lidsmet_bot/time_report.php?from={$start_month}&to={$end_month}"]];
				
				////
				
				$today = date('Y-m-d');
				$inline[] = [['text'=>'Отчет по аккаунтам за день','url'=>"https://zayvka.ru/lidsmet_bot/account_report.php?from={$today}&to={$today}"]];
				
				$start_week = date('Y-m-d', strtotime('monday this week'));
				$end_week = date('Y-m-d', strtotime('monday this week') + (7 * 24 * 3600) - 1);
				$inline[] = [['text'=>'Отчет по аккаунтам за неделю','url'=>"https://zayvka.ru/lidsmet_bot/account_report.php?from={$start_week}&to={$end_week}"]];
				
				$start_month = date('Y-m-01');
				$end_month = date('Y-m-t');
				$inline[] = [['text'=>'Отчет по аккаунтам за месяц','url'=>"https://zayvka.ru/lidsmet_bot/account_report.php?from={$start_month}&to={$end_month}"]];
				
				$sended = $telegram->SendTextMarkdownV2($user->chat_id, $message, null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Дневной отчет':
		{
			if ($user->role == 2)
			{
				$arr = $db->ExecSQL("select count(1) as cnt from t_letter where date(processed) = current_date() and manager_id = {$user->id}");
				$today = date('d.m.Y');
				$message = "Количество обработанных заявок за $today: <b>{$arr[0]['cnt']} шт.</b>";
				$sended = $telegram->SendText($user->chat_id, $message);
				break;
			}
			goto label_default;
		}
		
		case 'Получить заявки':
		{
			if ($user->role == 2)
			{
				// общий параметр - количество заявок
				$arr = $db->ExecSQL('select ifnull((select value from t_param where code = "count_request"), 5) as count_request');
				$count_request = $arr[0]['count_request'];
				
				// индивидуальный параметр - остаток от дневного лимита
				$arr = $db->ExecSQL("SELECT (select day_limit from t_user where id = {$user->id}) as day_limit, count(1) as cnt FROM t_letter a where manager_id = {$user->id} and date(processed) = current_date()");
				$day_limit = $arr[0]['day_limit'];
				$cnt = $arr[0]['cnt'];
				$day_remain = $day_limit - $cnt;				
				
				if ($day_remain <= 0) {
					$message = "Исчерпан индивидуальный дневной лимит: $day_limit заявок";
					$sended = $telegram->SendText($user->chat_id, $message);
					break;
				}
				
				//проверяем наличие заявок в состоянии В работе
				$arr = $db->ExecSQL("select count(1) as cnt from t_letter where state_id = 2 and manager_id = {$user->id}");
				$in_work = $arr[0]['cnt'];
				
				if ($in_work != 0) {
					$message = "Есть заявки в состоянии <b>В работе</b> - {$in_work} шт.";
					$sended = $telegram->SendText($user->chat_id, $message);
					break;
				}
				
				$limit = min($day_remain, $count_request);
				
				$arr = $db->ExecSQL("select id from t_letter a " .
									" where state_id = 1 and ifnull(floor(time_to_sec(timediff(now(), " .
									" (select max(processed) from t_letter where manager_id = {$user->id})))), 999) > " .
									" ifnull((select value from t_param where code = 'count_minute'), 5) * 60  " .
									" order by id asc limit {$limit}");
				if (is_array($arr))
				{
					for($i=0;$i<count($arr);$i++)
					{
						$msg = getRequest($arr[$i]['id']);
						$message = $msg['message'];
						$inline = $msg['inline']; 
						$sended = $telegram->SendText($user->chat_id, $message, null, $inline);
						unset($inline);
						$db->ExecSQL("update t_letter set manager_id = {$user->id}, state_id = 2, processed = now() where id = {$arr[$i]['id']}");
					}
				}
				else
				{
					$arr = $db->ExecSQL("select ifnull((select value from t_param where code = 'count_minute'), 5) as count_minute");
					print_r($arr);
					$message = "Пока нет заявок\nОграничение на взятие: {$arr[0]['count_minute']} минут";
					$sended = $telegram->SendText($user->chat_id, $message);
				}
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'account_activate':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL("select * from t_account where id = $param");
				$log->ToLog(print_r($arr, true));
				$imap = getIMAP($arr[0]['server'], $arr[0]['port'], $arr[0]['login'], $arr[0]['password']);
				if ($imap)
				{
					$db->ExecSQL("update t_account set active = 1 where id = $param");
					$message = $input_message['source_text'] . "\n\n<b>Аккаунт активирован</b>.";
					$inline[] = [['text'=>'Выбрать папку','callback_data'=>'{"action":"select_folder","param":"' . $param . '"}']];
					imap_close($imap);
				}
				else
				{
					$message = $input_message['source_text'] . "\n\n<b>Не удалось активировать аккаунт. Проверьте указанные настройки, а так же возможность подключения к данному аккаунту по IMAP.</b>";
				}
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		
		case 'account_deactivate':
		{
			if ($user->role == 1)
			{
				$db->ExecSQL("update t_account set active = 0 where id = $param");
				$message = $input_message['source_text'] . "\n\n<b>Аккаунт отключен</b>";
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id']);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'account_delete':
		{
			if ($user->role == 1)
			{
				$db->ExecSQL("update t_account set deleted = 1 where id = $param");
				$message = $input_message['source_text'];
				$inline[] = [['text'=>'Восстановить','callback_data'=>'{"action":"account_recovery","param":"'. $param . '"}']];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'account_recovery':
		{
			if ($user->role == 1)
			{
				$db->ExecSQL("update t_account set deleted = 0 where id = $param");
				$message = $input_message['source_text'];
				$inline[] = [['text'=>'Удалить','callback_data'=>'{"action":"account_delete","param":"'. $param . '"}']];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'set_manager':
		{
			if ($user->role == 1)
			{
				$db->ExecSQL("update t_user set role = 2 where id = $param");
				
				$arr = $db->ExecSQL("select chat_id from t_user where id = $param");
				$message = 'Вам назначена роль Менеджер';
				$menu = array(array('Получить заявки'), array('Дневной отчет'));
				$telegram->SendText($arr[0]['chat_id'], $message, $menu);
				
				$message = $input_message['source_text'];
				$log->ToLog("message: $message");
				$inline[] = [['text'=>'✅ Менеджер','callback_data'=>'{"action":"unset_manager","param":"'. $param . '"}']];
				$inline[] = [['text'=>'Лимит','callback_data'=>'{"action":"set_limit","param":"'. $param . '"}']];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'unset_manager':
		{
			if ($user->role == 1)
			{
				$db->ExecSQL("update t_user set role = 0 where id = $param");
				
				$arr = $db->ExecSQL("select chat_id from t_user where id = $param");
				$message = 'С вас сняли роль Менеджер';
				$menu = array();
				$telegram->SendText($arr[0]['chat_id'], $message, $menu);
				
				$message = $input_message['source_text'];
				$log->ToLog("message: $message");
				$inline[] = [['text'=>'Менеджер','callback_data'=>'{"action":"set_manager","param":"'. $param . '"}']];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
	/*	case 'Добавить заявку':
		{
			if ($user->role == 1)
			{
				$message = 'Введите текст заявки:';
				$menu = array(array('Отменить добавление заявки'));
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				break;
			}
			else
			{
				goto label_default;
			}
		}
	*/	
		
		case 'select_folder':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL("select * from t_account where id = $param");
				$imap = getIMAP($arr[0]['server'], $arr[0]['port'], $arr[0]['login'], $arr[0]['password']);
				if ($imap)
				{
					$folders = getIMAPfolders($imap, $arr[0]['server']);

					if (count($folders) > 0)
					{
						$message = $input_message['source_text'];
						for($i=0;$i<min(count($folders), 25);$i++)
						{
							$inline[] = [['text'=>$folders[$i],'callback_data'=>'{"action":"sf","param":"' . $i . '_' . $param . '"}']]; 
						}
					}
					else
					{
						$message = $input_message['source_text'] . '\n\n<b>Не удалось получить список папок.</b>';
						$inline = null;
					}
		
					$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				}
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		
		case 'sf':
		{
			if ($user->role == 1)
			{
				$folder_num = substr($param, 0, stripos($param, '_'));
				$account_id = substr($param, stripos($param, '_') + 1);
				$log->ToLog("account_id: $account_id");
				$log->ToLog("folder_num: $folder_num");
				
				$arr = $db->ExecSQL("select * from t_account where id = $account_id");
				$imap = getIMAP($arr[0]['server'], $arr[0]['port'], $arr[0]['login'], $arr[0]['password']);
				$folders = getIMAPfolders($imap, $arr[0]['server']);
				$folder = $folders[$folder_num];
				$log->ToLog("folder: $folder");
				$db->ExecSQL("update t_account set folder = '$folder' where id = $account_id");
				
				$message = $input_message['source_text'] . ' ' . $folder . "\n\n<b>Папка успешно выбрана</b>";
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id']);
				
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Задать задержку':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL('select ifnull((select value from t_param where code = "count_minute"), 5) as count_minute');				
				$message = "Текущее значение задержки в минутах: <b>{$arr[0]['count_minute']}</b>\nВведите новое значение:";
				$menu = array(array('Вернуться в главное меню'));
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Задать количество':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL('select ifnull((select value from t_param where code = "count_request"), 5) as count_request');				
				$message = "Текущее значение количества заявок: <b>{$arr[0]['count_request']}</b>\nВведите новое значение:";
				$menu = array(array('Вернуться в главное меню'));
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Сжечь старые заявки':
		{
			if ($user->role == 1)
			{
				$arr = $db->ExecSQL("select date_format(min(created), '%d.%m.%Y %H:%m:%s') as date_old, date_format(max(created), '%d.%m.%Y %H:%m:%s') as date_new, count(1) as total_count from t_letter where processed is null");
				$message = "Всего необработанных заявок: <b>{$arr[0]['total_count']}</b>\nДиапазон дат: <b>{$arr[0]['date_old']} - {$arr[0]['date_new']}</b>\nУкажите число дней, заявки за которые требуется оставить:";
				$menu = array(array('Вернуться в главное меню'));
				$sended = $telegram->SendText($user->chat_id, $message, $menu);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'set_limit':
		{
			if ($user->role == 1)
			{				
				$message = $input_message['source_text'] . "\n\nВведите новое значение дневного лимита количества заявок:";
				$inline[] = [['text'=>'Отмена','callback_data'=>'{"action":"cancel_set_limit","param":"'. $param . '"}']];
				$sended = $telegram->SendText($user->chat_id, $message, null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'cancel_set_limit':
		{
			if ($user->role == 1)
			{				
				$sended = $telegram->DeleteMessage($user->chat_id, $input_message['message_id']);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		
		case 'set_state3':
		{
			if ($user->role == 2)
			{
				$db->ExecSQL("update t_letter set state_id = 3 where id = $param");
				$msg = getRequest($param);
				$message = $msg['message'];
				$inline = $msg['inline'];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'set_state4':
		{
			if ($user->role == 2)
			{
				$db->ExecSQL("update t_letter set state_id = 4 where id = $param");
				$msg = getRequest($param);
				$message = $msg['message'];
				$inline = $msg['inline'];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'set_state5':
		{
			if ($user->role == 2)
			{
				$db->ExecSQL("update t_letter set state_id = 5 where id = $param");
				$msg = getRequest($param);
				$message = $msg['message'];
				$inline = $msg['inline'];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'set_state7':
		{
			if ($user->role == 2)
			{
				$db->ExecSQL("update t_letter set state_id = 7 where id = $param");
				$msg = getRequest($param);
				$message = $msg['message'];
				$inline = $msg['inline'];
				$sended = $telegram->EditText($user->chat_id, $message, $input_message['message_id'], null, $inline);
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case '':
		{
			if ($user->role == 1)
			{
				
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case '':
		{
			if ($user->role == 1)
			{
				
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case '':
		{
			if ($user->role == 1)
			{
				
				break;
			}
			else
			{
				goto label_default;
			}
		}
		
		case 'Вернуться в главное меню':
		{
			goto label_default;
		}
		
		
		
		default:
		{		
			switch ($last_operation)
			{
				case 'Добавить аккаунт электронной почты':
				{
					$param = $action;
					$action = 'Добавление email - адрес';
					$message = "Введите пароль от электронной почты $param:";
					$menu = array(array('Отменить добавление аккаунта'));
					$sended = $telegram->SendText($user->chat_id, $message, $menu);
					break;
				}
				
				case 'Добавление email - адрес':
				{
					$param = $action;
					$action = 'Добавление email - псевдоним';
					
					$message = 'Укажите псевдоним для создаваемого аккаунта:';
					$menu = array(array('Отменить добавление аккаунта'));
					$sended = $telegram->SendText($user->chat_id, $message, $menu);		
					break;
				} 
				
				case 'Добавление email - пароль':
				{
					$param = $action;
					$action = 'Добавление email - псевдоним';
					
					$message = 'Укажите псевдоним для создаваемого аккаунта:';
					$menu = array(array('Отменить добавление аккаунта'));
					$sended = $telegram->SendText($user->chat_id, $message, $menu);	
					
					break;
				}
			
				
				case 'Добавление email - псевдоним':
				{
					$param = $action;
					$email = $user->getLastOperationByName('Добавление email - адрес')[0]['param'];
					$password = $user->getLastOperationByName('Добавление email - псевдоним')[0]['param'];
					$pseudonym = $param;
					
					$domen = mb_strtolower(mb_substr($email, mb_stripos($email,'@') + 1));
					$log->ToLog("email:$email password:$password domen:$domen");
					$imap_data = $db->ExecSQL("select * from t_imap_data");
					$server  = '';
					$port = '';
					$user_id = $user->id;
					
					for($i=0;$i<count($imap_data);$i++)
					{
						if ($imap_data[$i]['domen'] == $domen)
						{
							$server = $imap_data[$i]['server'];
							$port = $imap_data[$i]['port'];
							break;
						}
					}
					
					$account_id = $db->ExecSQL("insert t_account (user_id, login, password, server, port, pseudonym) values ($user_id, '$email','$password','$server','$port', '$pseudonym')");
					
					$message = "Аккаунт успешно добавлен, необходимо его активировать";
					$menu = array(array('Добавить аккаунт электронной почты'), 
									array('Аккаунты электронной почты'),
									array('Пользователи бота'), 
									array('Отчет'));
					$sended = $telegram->SendText($user->chat_id, $message, $menu);
					
					$message = "<b>Аккаунт №$account_id</b>\n\n<b>Адрес:</b> $email\n<b>Пароль:</b> $password\n<b>IMAP сервер:</b> $server\n<b>IMAP порт:</b> $port\n<b>Псевдоним</b>: $pseudonym";
					$inline[] = [['text'=>'Активировать','callback_data'=>'{"action":"account_activate","param":"' . $account_id . '"}']];
					$sended = $telegram->SendText($user->chat_id, $message, null, $inline);
					break;
				}
				
				case 'Задать задержку':
				{
					if ($user->role == 1)
					{
						$param = $action;
						$action = $last_operation;
						if (is_numeric($param))
						{
							$db->ExecSQL("update t_param set value = {$param} where code = 'count_minute'");
							$message = "Сохранено значение задержки: <b>{$param}</b>";
							$sended = $telegram->SendText($user->chat_id, $message, $menu);
							goto label_default;
							
						}
						else
						{
							$message = "Вы ввели некорректное значение: <b>{$param}</b>\nВведите корректное значение задержки:";
							$menu = array(array('Вернуться в главное меню'));
							$sended = $telegram->SendText($user->chat_id, $message, $menu);
						}
					}
					else
						goto label_default;
					break;
				}
				
				case 'Задать количество':
				{
					if ($user->role == 1)
					{
						$param = $action;
						$action = $last_operation;
						if (is_numeric($param))
						{
							$db->ExecSQL("update t_param set value = {$param} where code = 'count_request'");
							$message = "Сохранено значение количества заявок: <b>{$param}</b>";
							$telegram->SendText($user->chat_id, $message);
							goto label_default;
						}
						else
						{
							$message = "Вы ввели некорректное значение: <b>{$param}</b>\nВведите корректное значение количества заявок:";
							$menu = array(array('Вернуться в главное меню'));
							$telegram->SendText($user->chat_id, $message, $menu);
						}
					}
					else
						goto label_default;
					break;
				}
				
				case 'Сжечь старые заявки':
				{
					if ($user->role == 1)
					{
						$param = $action;
						$action = $last_operation;
						if (is_numeric($param))
						{
							$db->ExecSQL("update t_letter set processed = now(), state_id = 6 where processed is null and datediff(now(), created) > {$param}");
							$arr = $db->ExecSQL("select date_format(min(created), '%d.%m.%Y %H:%m:%s') as date_old, date_format(max(created), '%d.%m.%Y %H:%m:%s') as date_new, count(1) as total_count from t_letter where processed is null");
							$message = "Осталось необработанных заявок: <b>{$arr[0]['total_count']}</b>\nДиапазон дат: <b>{$arr[0]['date_old']} - {$arr[0]['date_new']}</b>";
							$telegram->SendText($user->chat_id, $message);
							goto label_default;
						}
						else
						{
							$message = "Вы ввели некорректное значение: <b>{$param}</b>\nВведите корректное значение количества дней:";
							$menu = array(array('Вернуться в главное меню'));
							$telegram->SendText($user->chat_id, $message, $menu);
						}
					}
					else
						goto label_default;
					break;
				}
				
				case 'set_limit':
				{
					if ($user->role == 1)
					{
						$param = $action;
						$action = $last_operation;
						if (is_numeric($param))
						{
							$db->ExecSQL("update t_user set day_limit = $param where id  = {$last_param}");
							$sended = $telegram->DeleteMessage($user->chat_id, $last_message_id);
							$message = 'Дневной лимит заявок обновлен';
							$telegram->SendText($user->chat_id, $message);
							goto label_default;
						}
						else
						{
							$message = "Вы ввели некорректное значение: <b>{$param}</b>\nВведите корректное значение лимита заявок:";
							$menu = array(array('Вернуться в главное меню'));
							$telegram->SendText($user->chat_id, $message, $menu);
						}
					}
					else
						goto label_default;
					break;
				}
				
			/*	case 'Добавить заявку':
				{
					$param = $action;
					$action = 'Заявка добавлена';
					$db->ExecSQL("insert into t_letter (body) values ('$param')");
					$sended = $telegram->SendText($user->chat_id, $action);
					goto label_default;
				} */
				
				default:
				{
					goto label_default;
				}
			}
		}
	}
	
	if (false)
	{
		label_default:
			if ($user->role == 1)
			{
				$message = 'Для дальнейших действий воспользуйтесь <b>Меню</b>:';
				$menu = array(array('Аккаунты электронной почты'),
							array('Пользователи бота'), 
							array('Отчет'),
							array('Задать задержку', 'Задать количество', 'Сжечь старые заявки'));
			}
			else if ($user->role == 2)
			{
				$message = 'Вы можете получить заявки:';
				$menu = array(array('Получить заявки'), array('Дневной отчет'));
			}
			else
			{
				$message = 'Вы добавлены в список пользователей бота. Для продолжения работы необходимо что бы администратор назначил вам роль.';
				$menu = null;
			}
			
			$sended = $telegram->SendText($user->chat_id, $message, $menu);
	}
	
	$user->addOperation($action, @$sended['result']['message_id'], $param);
				  
	
	
?>