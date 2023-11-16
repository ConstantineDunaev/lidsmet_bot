<?php
	class log
	{
		private $db;
		
		public function __construct($db)
		{
			$this->db = $db;
		}
		
		public function ToLog($message, $user_id = 0)
		{
			if ($message) 
			{
				$this->db->ExecSQL("insert into t_log (message, user_id) values ('$message', $user_id)");
			}
		}
	}
	