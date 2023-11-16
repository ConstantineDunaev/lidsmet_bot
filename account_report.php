<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" >
<html>
<head>
	<link rel="stylesheet" href="style.css">
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>
	<?php
		ini_set('error_reporting', E_ALL);
		ini_set('display_errors', 1);
		ini_set('display_startup_errors', 1);
		ini_set('html_errors', 1);
		if (isset($_GET['from']) and isset($_GET['to'])) {
			$from = $_GET['from'];
			$to = $_GET['to'];
		} else {
			$from = date('Y-m-d');
			$to = $from;
		}
		$title = "Отчет по аккаунтам за период " . date_format(date_create($from), 'd.m.Y') . " - " . date_format(date_create($to), 'd.m.Y'); 
		echo $title;
	?>
	</title>
</head>
	<body>
		<h2><?php echo $title; ?></h2>
		<form action="account_report.php">
			<label for="from">Дата начала периода</label>
			<input type="date" id="from" name="from" value="<?php echo $from;?>"/>
			<label for="to">    Дата конца периода</label>
			<input type="date" id="to" name="to" value="<?php echo $to;?>"/>
			<button type="submit">Сформировать</button>
		</form>
		</br>
		<div class="grid">
			<div class="scroll-table">
				<table class=task_table>
					<thead>
						<tr>
							<th class=email>Аккаунт</th>
							<th class=created>Создана</th>
							<th class=in_work>В работе</th>
							<th class=not_actual>Не актуально</th>
							<th class=send_kp>Отправил КП</th>
							<th class=send_check>Выставил счет</th>
							<th class=buring>Сожжена</th>
							<th class=spam>Спам</th>
							<th class=total>Всего</th>
						</tr>
					</thead>
				</table>	
			</div>
			<div class="scroll-table-body">
				<table class=task_table>
					<tbody>
						<?php
							$config = parse_ini_file("settings.ini");
							$mysqli = new mysqli($config['host_db'], $config['user_db'], $config['password_db'], $config['schema_db']);
							$mysqli->set_charset("utf8mb4");
							$where = " and created between '$from 00:00:00' and '$to 23:59:59' ";
							$sql = "select a.login as email, 
							q.* 
							from t_account a 
							right join 
							(select account_id, count(1) as total,
							sum(case when state_id = 1 then 1 else 0 end) as created, 
							sum(case when state_id = 2 then 1 else 0 end) as in_work, 
							sum(case when state_id = 3 then 1 else 0 end) as not_actual, 
							sum(case when state_id = 4 then 1 else 0 end) as send_kp, 
							sum(case when state_id = 5 then 1 else 0 end) as send_check, 
							sum(case when state_id = 6 then 1 else 0 end) as buring, 
							sum(case when state_id = 7 then 1 else 0 end) as spam 
							from t_letter 
							where 1 = 1
							$where 
							group by account_id 
							) q on a.id = q.account_id";
							
							$table = '';
							$result = $mysqli->query($sql);
							while ($row = $result->fetch_assoc()) {
								//$param = "&manager_id={$row['account_id']}&from={$from}&to={$to}";
								$table .= '<tr><td class=email>' . $row['email'] . 
								'</td><td class=created>'.$row['created'].'</a>'.
								'</td><td class=in_work>'.$row['in_work'].'</a>'.
								'</td><td class=not_actual>'.$row['not_actual'].'</a>'. 
								'</td><td class=send_kp>'.$row['send_kp'].'</a>'.
								'</td><td class=send_check>'.$row['send_check'].'</a>'.
								'</td><td class=buring>'.$row['buring'].'</a>'.
								'</td><td class=spam>'.$row['spam'].'</a>'.
								'</td><td class=total>'.$row['total'].
								'</td></tr>';
							}
							echo $table;
						?>
					</tbody>
				</table>
			</div>			
		</div>	
	</body>
</html>