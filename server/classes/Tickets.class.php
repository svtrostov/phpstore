<?php
/*==================================================================================================
Title	: Tickets class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Tickets{

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	private $db = null;

	#Идентификатор клиента
	private $client_id = 0;
	private $user_id = 0;
	private $table_tickets = '';
	private $table_comments = '';
	private $table_clients = '';
	private $table_users = '';

	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct(){
		$this->db = Database::getInstance('main');
		$this->table_users = $this->db->getTableName('users');
		$this->table_clients = $this->db->getTableName('clients');
		$this->table_tickets = $this->db->getTableName('tickets');
		$this->table_comments = $this->db->getTableName('ticket_comments');
		$this->client_id = User::_getClientID();
		$this->user_id = User::_getUserID();
	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function


	public function __destruct(){
		//
	}#end function


	/*==============================================================================================
	Информация
	==============================================================================================*/


	/*
	 * Получение списка тикетов
	 */
	public function getTicketsList($status=0, $client_id=0){
		$status = intval($status);
		$client_id = intval($client_id);
		return $this->db->select('SELECT
			T.`ticket_id` as `ticket_id`,
			T.`status` as `status`,
			T.`subject` as `subject`,
			T.`user_id` as `user_id`,
			T.`client_id` as `client_id`,
			DATE_FORMAT(T.`timestamp`,"%d.%m.%Y %H:%i:%s") as `timestamp`,
			IF(T.`user_id`=0,"-[System]-",IFNULL(U.`name`,"-[undefined]-")) as `user_name`,
			IF(T.`client_id`=0,"-[System]-",IFNULL(C.`name`,"-[undefined]-")) as `client_name`
			FROM `'.$this->table_tickets.'` as T
				LEFT JOIN `'.$this->table_users.'` as U ON U.`user_id`=T.`user_id`
				LEFT JOIN `'.$this->table_clients.'` as C ON C.`client_id`=T.`client_id`
			'.(!empty($client_id)?'WHERE T.`client_id`='.$client_id:'').(!empty($status)?(!empty($client_id)?' AND ':'WHERE ').'T.`status`='.$status:'').' ORDER BY T.`ticket_id` DESC');
	}#end function



	/*
	 * Получение информации по тикету
	 */
	public function getTicket($ticket_id=0, $client_id=0){
		$ticket_id = intval($ticket_id);
		if(empty($ticket_id)) return false;
		$client_id = intval($client_id);
		return $this->db->selectRecord('SELECT
			T.`ticket_id` as `ticket_id`,
			T.`status` as `status`,
			T.`subject` as `subject`,
			T.`message` as `message`,
			T.`user_id` as `user_id`,
			T.`client_id` as `client_id`,
			DATE_FORMAT(T.`timestamp`,"%d.%m.%Y %H:%i:%s") as `timestamp`,
			IF(T.`user_id`=0,"-[System]-",IFNULL(U.`name`,"-[undefined]-")) as `user_name`,
			IF(T.`client_id`=0,"-[System]-",IFNULL(C.`name`,"-[undefined]-")) as `client_name`
			FROM `'.$this->table_tickets.'` as T
				LEFT JOIN `'.$this->table_users.'` as U ON U.`user_id`=T.`user_id`
				LEFT JOIN `'.$this->table_clients.'` as C ON C.`client_id`=T.`client_id`
			WHERE T.`ticket_id`='.$ticket_id.(!empty($client_id)?' AND T.`client_id`='.$client_id:'').(!empty($status)?' AND T.`status`='.$status:'').' LIMIT 1');
	}#end function



	/*
	 * Получение списка комментариев на тикет
	 */
	public function getComments($ticket_id=0){
		$ticket_id = intval($ticket_id);
		return $this->db->select('SELECT
			C.`comment_id` as `comment_id`,
			C.`ticket_id` as `ticket_id`,
			C.`message` as `message`,
			C.`user_id` as `user_id`,
			C.`is_admin` as `is_admin`,
			DATE_FORMAT(C.`timestamp`,"%d.%m.%Y %H:%i:%s") as `timestamp`,
			IF(C.`user_id`=0,"-[System]-",IFNULL(U.`name`,"-[undefined]-")) as `user_name`
			FROM `'.$this->table_comments.'` as C
				LEFT JOIN `'.$this->table_users.'` as U ON U.`user_id`=C.`user_id`
			WHERE C.`ticket_id`='.$ticket_id.' ORDER BY C.`comment_id` ASC');
	}#end function


	/*
	 * Добавление тикета
	 */
	public function ticketNew($fields=array()){
		if(empty($fields)) return false;
		if(!isset($fields['client_id'])) $fields['client_id'] = $this->client_id;
		if(!isset($fields['user_id'])) $fields['user_id'] = $this->user_id;
		return $this->db->addRecord('tickets', $fields);
	}#end function


	/*
	 * Добавление комментария к тикету
	 */
	public function commentNew($fields=array()){
		if(empty($fields)||empty($fields['ticket_id'])) return false;
		if(!isset($fields['user_id'])) $fields['user_id'] = $this->user_id;
		return $this->db->addRecord('ticket_comments', $fields);
	}#end function


	/*
	 * Обновление тикета
	 */
	public function ticketUpdate($ticket_id=0, $fields=array()){
		$ticket_id = intval($ticket_id);
		if(empty($fields)) return false;
		return $this->db->updateRecord('tickets', array(
			'ticket_id'	=> $ticket_id
		), $fields);
	}#end function

}#end class

?>
