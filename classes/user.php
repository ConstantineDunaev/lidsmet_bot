<?php
	class User
	{
		public $id;
		public $chat_id;
		public $username;
		public $firstname;
		public $lastname;
		//public $active;
		public $role;
		public $created;
		private $db;
		//public $ending_payment;
		
		public function __construct($db, $chat_id, $username, $firstname, $lastname)
		{
			$this->db = $db;
			$this->chat_id = $chat_id;
			$this->username = $username;
			$this->firstname = $firstname;
			$this->lastname = $lastname;
		}
		
		public function save()
		{
			$chat_id = $this->chat_id;
			$username = $this->username;
			$firstname = $this->firstname;
			$lastname = $this->lastname;
			$this->id = $this->db->ExecSQL("insert into t_user (chat_id, username, firstname, lastname) values ($chat_id, '$username', '$firstname', '$lastname')");
		}
		
		public function load()
		{
			$chat_id = $this->chat_id;
			$arr = $this->db->ExecSQL("select id, role, created from t_user where chat_id = $chat_id");
			if (count($arr) == 1)
			{
				$this->id = $arr[0]['id'];
				$this->role = $arr[0]['role'];
				$this->created = $arr[0]['created'];
				//$this->active = $arr[0]['active'];
			}
			//$arr = $this->db->ExecSQL("select ifnull(max(ending), 0) as f from t_user u inner join t_payment p on p.user_id = u.id and u.chat_id = $chat_id");
			//$this->ending_payment = $arr[0]['f'];
			//unset($arr);
		}
		
		public function addOperation($operation, $out_message_id = 0, $param = '')
		{
			$user_id = $this->id;
			$this->db->ExecSQL("insert into t_operation (user_id, operation, out_message_id, param) values ($user_id, '$operation', $out_message_id, '$param')");
		}
		
		public function getLastOperation()
		{
			$user_id = $this->id;
			$arr = $this->db->ExecSQL("select operation, param, out_message_id from t_operation where user_id = $user_id order by id desc limit 1");
			return $arr;
		}	

		public function getLastOperationByName($operation)
		{
			$user_id = $this->id;
			$arr = $this->db->ExecSQL("select operation, param, out_message_id from t_operation where user_id = $user_id and operation = '$operation' order by id desc limit 1");
			return $arr;
		}	
	}