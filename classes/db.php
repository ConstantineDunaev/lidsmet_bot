<?php	
	class db 
	{
		private $mysqli;
		
		public function __construct($host_db, $user_db, $password_db, $schema_db) 
		{
			$this->mysqli = new mysqli($host_db, $user_db, $password_db, $schema_db);
			$this->mysqli->set_charset("utf8mb4");
		}
		
		public function ExecSQL($query)
		{
			$result = null;
			try
			{
				$answer = $this->mysqli->query($query);
			}
			catch (Exception $e)
			{
				$error = $this->mysqli->error;
				if ($error)
				{
					echo "\n\nОшибка ExecSQL: \n$error\n$query\n\n";
					//file_put_contents('mysqli_error.txt',  "\n\n\n".date("d.m.Y H:i:s")." $query \n $error", FILE_APPEND);
				}
			}
			
			
			if (is_object($answer))
			{
				while(($row = $answer->fetch_assoc()) != false)
				{
					$result[] = $row;
				}
				unset($answer);
			}
			else
			{
				$result = $this->mysqli->insert_id;
			}
			return $result;
		}
		
		public function ExecPrepareSQL($query, $data)
		{
			$stmt = $this->mysqli->prepare($query);
			
		}
		
		function __destruct() 
		{
			$this->mysqli->close();
			unset($this->mysqli);
		}
	}
?>