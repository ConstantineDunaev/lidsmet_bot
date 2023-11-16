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
			$manager_id = $_GET['manager_id'];
			$state_id = $_GET['state_id'];
		} else {
			exit();
		}
		$title = "Отчет за период " . date_format(date_create($from), 'd.m.Y') . " - " . date_format(date_create($to), 'd.m.Y'); 
		echo $title;
	?>
	</title>
</head>
	<body>
		<h2><?php echo $title; ?></h2>
		</br>
		<div class="grid">
			<div class="scroll-table">
				<table class=task_table>
					<thead>
						<tr>
							<th class=manager>Дата создания</th>
							<th class=in_work>Дата обработки</th>
							<th class=not_actual>Тема</th>
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
							$sql = "select a.id, a.created, processed, username, fullname, subj from t_letter a inner join t_state b on a.state_id = b.id inner join t_user c on a.manager_id = c.id  where manager_id = $manager_id and state_id = $state_id and processed between '$from 00:00:00' and '$to 23:59:59'";
							
							$table = '';
							$result = $mysqli->query($sql);
							while ($row = $result->fetch_assoc()) {
								$table .= '<tr><td class=manager>' . $row['created'] . 
								'</td><td class=in_work>' . $row['processed'] .
								'</td><td class=not_actual><a href="view_letter.php?letter_id='.$row['id'].'">' . $row['subj'] . 
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