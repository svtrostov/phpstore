<?php
/*==================================================================================================
Title	: Protocol class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Protocol{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	private $db = null;
	private $user_id = null;
	private $session_uid = 0;
	private $object_types = null;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){

		$this->db 			= Database::getInstance('main');
		$this->table_log 	= $this->db->getTableName('user_actionlog');
		$this->table_data 	= $this->db->getTableName('user_actiondata');
		$this->table_users 	= $this->db->getTableName('users');
		$this->user			= User::getInstance();
		$this->user_id 		= $this->user->getUserID();
		$this->session_uid	= Session::_get('session_uid');
		if(!is_numeric($this->session_uid)) $this->session_uid = intval($this->session_uid);


	}#end function




	/*==============================================================================================
	ФУНКЦИИ: Добавление записи в протокол
	==============================================================================================*/



	/*
	 * Добавление записи в протокол
	 */
	public function add($fields=array()){

		if(empty($fields)) return false;
		$fields['user_id']		= $this->user_id;
		$fields['session_uid']	= $this->session_uid;

		$in_transaction = $this->db->inTransaction();
		if(!$in_transaction) $this->db->transaction();

		$action_uid = $this->db->addRecord('user_actionlog', $fields);
		if(empty($action_uid)){
			if(!$in_transaction) $this->db->rollback();
			return false;
		}

		if(!empty($fields['data'])){
			if($this->db->addRecord('user_actiondata', array(
				'action_uid'	=> $action_uid,
				'data'			=>	serialize($fields['data'])
			))===false){
				if(!$in_transaction) $this->db->rollback();
				return false;
			}
		}

		if(!$in_transaction) $this->db->commit();

		return $action_uid;
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Получение данных протокола
	==============================================================================================*/


	/*
	 * Получение списка событий протокола
	 */
	public function getProtocolEvents(){
		
		
		
	}#end function



}#end class

?>
