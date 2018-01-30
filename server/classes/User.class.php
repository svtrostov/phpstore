<?php
/*==================================================================================================
Title	: User class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class User{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Справочник прав доступа пользователей и администраторов:
	array(
	'can_view_users' 	=> array(1, 'Может просматривать пользователей', array('can_create_users','can_edit_users')), #Читается как: для успеха у пользователя должны быть access_level минимум 1 И права can_view_users или can_edit_users или can_create_users
	'can_create_users'	=> array(1, 'Может создавать пользователей'), #Читается как: для успеха у пользователя должны быть access_level минимум 1
	)
	==============================================================================================*/

	private $alist = array(
		'can_protocol_view'		=> array('Просмотр протокола действий',array('can_full_access')),
		'can_user_edit'			=> array('Редактирование администраторов',array('can_full_access')),		#Читается как: для успеха у пользователя должны быть access_level минимум 1 И права ( "can_edit_users" или "can_full_access")
		'can_client_add'		=> array('Добавление новых клиентов',array('can_full_access','can_client_edit')),
		'can_client_edit'		=> array('Редактирование клиентов',array('can_full_access')),
		'can_ticket_answer'		=> array('Может отвечать на сообщения клиентов',array('can_full_access','can_client_edit')),
		'can_ticket_delete'		=> array('Может удалять сообщения клиентов',array('can_full_access','can_client_edit')),

		'can_property_edit'		=> array('Редактирование характеристик',array('can_full_access')),
		'can_product_add'		=> array('Добавление новых товаров',array('can_full_access')),
		'can_product_edit'		=> array('Редактирование товаров',array('can_full_access','can_product_add')),
		'can_product_wh_change'	=> array('Изменение остатков товара на складах',array('can_full_access','can_product_edit','can_product_add')),
		'can_catalog_edit'		=> array('Редактирование структуры каталога',array('can_full_access')),

		'can_order_add'			=> array('Создание новых заказов',array('can_full_access')),
		'can_order_edit'		=> array('Редактирование заказов',array('can_full_access','can_order_add')),

		'can_account_edit'		=> array('Редактирование банковских реквизитов',array('can_full_access')),
		'can_delivery_edit'		=> array('Редактирование способов доставки',array('can_full_access')),
		'can_currency_edit'		=> array('Редактирование курса валют',array('can_full_access')),
		'can_config_edit'		=> array('Редактирование настроек магазина',array('can_full_access')),
		'can_news_edit'			=> array('Редактирование новостей',array('can_full_access')),
		'can_discount_edit'		=> array('Редактирование скидок',array('can_full_access')),
		'can_warehouse_edit'	=> array('Редактирование складов',array('can_full_access')),
		'can_citilink_upload'	=> array('Загрузка прайс-листа Citilink',array('can_full_access')),
		'can_full_access'		=> array('Полный доступ')
	);


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $xcache = null;

	#db
	public $db = null;

	#Идентификатор клиента
	public $client_id = 0;

	#Идентификатор пользователя
	public $user_id = 0;

	#Признак того, что статус клиента был проверен
	public $auth_status_checked = false;

	#Информация о клиента
	public $info = null;

	#Названия COOKIEs
	public $session_name = null;
	public $cookie_auth = null;
	public $cookie_lang = null;
	public $cookie_theme = null;

	#Язык интерфейса пользователя
	public $user_language=false;

	#Тема интерфейса пользователя
	public $user_theme=false;

	#Массив супер администраторов
	public $super_users=false;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		$this->db = Database::getInstance('main');
		$this->super_users	= Config::getOption('general', 'super_users', array());
		$this->xcache		= XCache::getInstance();
		$this->session_name	= strtoupper(Config::getOption('general','session_name','DTBOX'));
		$this->cookie_auth	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_auth_info','UAUTH'));
		$this->cookie_lang	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_language','LANG'));
		$this->cookie_theme	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_theme','THEME'));
	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function


	public function __destruct(){
		if($this->auth_status_checked && !$this->user_id) Session::_stop();
	}#end function


	/*==============================================================================================
	Информация
	==============================================================================================*/


	/*
	 * Получение записи о клиенте из базы данных
	 */
	public function getUserInfo($read_from_db=false){
		if(empty($this->user_id)){
			if(!$this->checkAuthStatus()) return false;
		}
		//if(!$read_from_db&&!empty($this->info))return $this->info;
		$this->db->prepare('SELECT * FROM `users` WHERE `user_id`=? LIMIT 1');
		$this->db->bind($this->user_id);
		if(($this->info = $this->db->selectRecord()) === false) return false;
		if(empty($this->info['enabled'])) return false;
		$this->info['is_admin'] = true;
		$this->info['is_super'] = $this->isSuper();
		$this->info['access'] = $this->db->selectAsKV('access','level','SELECT `access`,`level` FROM `user_access` WHERE `user_id`='.$this->user_id);
		if($this->info['updated']>0) $this->db->update('UPDATE `users` SET `updated`=0 WHERE `user_id`='.$this->user_id);
		return $this->info;
	}#end function



	/*
	 * Обновление записи о клиенте в базе данных
	 */
	public function dbUpdate($key=null,$value=null){
		if(empty($key))return false;
		if(!is_array($key)){
			$this->db->prepare('UPDATE `users` SET `?`=? WHERE `user_id`=?');
			$this->db->bindField($key);
			$this->db->bindText($value);
			$this->db->bindNum($this->user_id);
			if($this->db->update() === false) return false;
		}
		return true;
	}#end function




	/*
	 * Получение информации о клиенте
	 */
	public function get($key, $default=null){
		if(empty($this->info)) $this->getUserInfo();
		if(is_array($key)) return arrayCustomFields($this->info, $key);
		if(!isset($this->info[$key])) return $default;
		return $this->info[$key];
	}#end function



	/*
	 * Возвращает ID пользователя
	 */
	public function getUserID(){
		if(empty($this->user_id)) $this->checkAuthStatus();
		return $this->user_id;
	}#end function



	/*
	 * Проверка, является ли пользователь - суперадминистратором
	 */
	public function isSuper($user_id=0){
		$user_id = intval($user_id);
		if(empty($user_id)) $user_id=$this->user_id;
		if(empty($this->super_users)) return false;
		if(!is_array($this->super_users)) return ($user_id == $this->super_users);
		return in_array($user_id, $this->super_users, true);
	}#end function



	/*
	 * Возвращает журнал входа сотрудника в админку
	 */
	public function getAccessLog($user_id=0, $filter=null, $fields=null, $limit=100){
		if(empty($user_id)) $user_id = $this->user_id;
		if(is_array($user_id)) $user_id = implode(',',$user_id);
		$limit = intval($limit);
		$filter_sql = (is_array($filter) ? $this->db->buildSqlConditions($filter,'EAUTH') : '');

		$this->db->prepare('SELECT * FROM `user_authlog` WHERE `user_id` IN (?) '.(empty($filter_sql) ? '' : ' AND '.$filter_sql).' ORDER BY `login_time` DESC '.($limit > 0 ? 'LIMIT '.$limit:'').'');
		$this->db->bindSql($user_id);
		$result = $this->db->select();

		if(!is_array($fields)) return $result;
		$return = array();
		foreach($result as $record){
			$return[] = arrayCustomFields($record, $fields);
		}

		return $return;
	}#end function



	/*
	 * Проверка cуществования пользователя у клиента
	 */
	public function loginExists($username){
		if(empty($username)) return false;
		$this->db->prepare('SELECT count(*) FROM `users` WHERE `username` LIKE ? LIMIT 1');
		$this->db->bind($username);
		return ($this->db->result() > 0);
	}#end function


	/*
	 * Добавление пользователя
	 */
	public function userNew($fields=array()){
		if(empty($fields)) return false;
		return $this->db->addRecord('users', $fields);
	}#end function




	/*
	 * Обновление пользователя
	 */
	public function userUpdate($user_id=0, $fields=array(), $client_id=0){
		if(empty($fields)) return false;
		$client_id = intval($client_id);
		if(empty($client_id)) $client_id = $this->getClientID();
		return $this->db->updateRecord('users', array(
			'user_id'	=> $user_id
		), $fields);
	}#end function






	/*==============================================================================================
	Аутентификация
	==============================================================================================*/



	/*
	 * Проверяет статус аутентификации клиента
	 */
	public function checkAuthStatus(){

		if($this->auth_status_checked) return (!empty($this->user_id) ? true : false);
		
		$this->auth_status_checked = true;

		$session_id = Request::_getGPC($this->session_name, false, 'c');

		#Проверка существования COOKIE с идентификатором сессии
		if($session_id !== false){

			#Если сессии хранятся в XCACHE
			if(Session::_xcacheSession()){
				#проверяем существование сессии в XCACHE
				if(Session::_xexists($session_id)){
					#Проверка сессии
					if(Session::_badSession(array('session_name'=>$this->session_name,'user_id'=>null))===false){
						return $this->startUserSession(null);
					}
				}
			}else{
				#Проверка сессии
				if(Session::_badSession(array('session_name'=>$this->session_name,'user_id'=>null))===false){
					return $this->startUserSession(null);
				}
			}
		}

		#попытка проведения автологина - cookie
		if(Config::getOption('general','user_cookie_login',false)){
			return $this->authFromCookie();
		}

		return false;
	}#end function




	/*
	 * Аутентификация через COOKIE
	 */
	public function authFromCookie(){

		$cookie = Request::_getCookie($this->cookie_auth, false);
		if(empty($cookie)) return false;

		list($user_id, $name, $pwd_hash, $remember) = explode('/', $cookie);
		$user_id = abs(intval($user_id));
		$name = strval($name);
		$pwd_hash = strval($pwd_hash);
		$remember = ($remember == 1 ? 1 : 0);
		
		if(empty($user_id)||empty($name)||empty($pwd_hash))return false;
		
		$this->db->prepare('SELECT * FROM `users` WHERE `user_id`=? AND `username` LIKE ? AND `enabled`>0 LIMIT 1');
		$this->db->bind($user_id);
		$this->db->bind($name);

		if(($info = $this->db->selectRecord()) === false) return false;
		if(empty($info)) return false;

		if(strcasecmp($info['password'], $pwd_hash) != 0){
			if(strcasecmp($this->getHash($info['password']), $pwd_hash) !== 0) return false;
		}

		$session_uid = $this->authLog('login',$info);
		if(empty($session_uid)) return false;
		$info['remember_me'] = $remember;
		$info['password']	 = $pwd_hash;
		$info['session_uid'] = $session_uid;

		return $this->startUserSession($info);
	}#end function




	/*
	 * Запись в COOKIE информации для автологина
	 */
	public function setAuthCookie($remember=0){

		if(!empty($remember)){
			$expire_time = time() + 31536000;
			$remember = 1;
		}else{
			$expire_time = 1;
			$remember = 0;
		}
		$pwd_hash = $this->getHash($this->info['password']);
		$cookie = $this->info['user_id'].'/'.$this->info['username'].'/'.$pwd_hash.'/'.$remember;
		Response::_addCookie($this->cookie_auth, $cookie, $expire_time);
		Response::_addCookie($this->cookie_lang, $this->getLanguage(), time() + 31536000);
		Response::_addCookie($this->cookie_theme, $this->getTheme(), time() + 31536000);

	}#end function



	/*
	 * Удаление COOKIE информации для автологина
	 */
	public function deleteAuthCookie(){
		$this->setAuthCookie(0);
		Response::_addCookie($this->session_name,'',1);
	}#end function



	/*
	 * Начало сессии для аутентифицированного клиента,
	 * если передан массив $user, то производится запись в сессию, в противном случае - чтение
	 */
	private function startUserSession($user=null){
		
		#Передан массив для записи в сессию - новая сессия
		if(!empty($user)&&is_array($user)){

			$user['session_name']=$this->session_name;
			$this->info 		= $user;
			$this->user_id 		= intval($user['user_id']);
			$this->info['is_admin'] = true;
			$this->info['is_super'] = $this->isSuper();

			#Запись в сессию информации о клиенте
			#Пишется целиком все поля таблицы users
			Session::_set($user);
			Session::_set($user['session_uid']);

			#Если сессии хранятся в XCACHE
			#Удаляем предыдущую сессию, если она есть
			if(Session::_xcacheSession()){
				$session_id = Session::_getSessionID();
				$var_uid = 'sess/'.$this->session_name.'/'.$this->user_id.'/last_uid';
				if(xcache_isset($var_uid)){
					$last_uid = xcache_get($var_uid);
					if(strcmp($session_id,$last_uid)!=0){
						#Если записи о предыдущих сессиях найдены - удаляем их
						if(xcache_isset('sess/'.$this->session_name.'/'.$last_uid)){
							xcache_unset('sess/'.$this->session_name.'/'.$last_uid);
						}
					}
				}
				xcache_set($var_uid, Session::_getSessionID());
			}
			
		}else{
			$this->user_id 	= intval(Session::_get('user_id'));
			if($this->getUserInfo()===false) return false;
		}

		return true;
	}#end function



	/*
	 * Аутентификация клиента
	 */
	public function auth($username='', $password='', $remember=0){

		if(empty($username)||empty($password))return array('result'=>false,'desc'=>'Не заданы имя пользователя и/или пароль');

		$pwd_hash = sha1($password);

		$this->db->prepare('SELECT * FROM `users` WHERE `username`=? AND `password` IN (?,?) LIMIT 1');
		$this->db->bind($username);
		$this->db->bind($password);
		$this->db->bind($pwd_hash);

		#Ошибка получения данных из БД
		if(($info = $this->db->selectRecord()) === false) return array('result'=>false,'desc'=>'Сервис временно недоступен');

		#Неправильный логин/пароль
		if(empty($info)) return array('result'=>false,'desc'=>'Неправильный логин или пароль');

		#Заблокирован
		if(empty($info['enabled'])) return array('result'=>false,'desc'=>'Заблокирован');

		#Логирование входа
		$session_uid = $this->authLog('login',$info);
		if(empty($session_uid)) return array('result'=>false,'desc'=>'Сервис временно недоступен');

		$info['password']	= $pwd_hash;
		$info['remember_me'] = $remember;
		$info['session_uid'] = $session_uid;

		$info['access'] = $this->db->selectAsKV('access','level','SELECT `access`,`level` FROM `user_access` WHERE `user_id`='.$info['user_id']);

		$this->startUserSession($info);
		$this->setAuthCookie($remember);

		return array('result'=>true,'desc'=>null);
	}#end function





	/*
	 * Логирование аутентификации клиента
	 */
	private function authLog($auth_type='login', $info=array()){

		#Логирование в историю
		return
		$this->db->addRecord('user_authlog',array(
			'user_id'	=> $info['user_id'],
			'ip_addr'	=> Request::_getIP(false),
			'ip_real'	=> Request::_getIP(true),
			'auth_type'	=> $auth_type
		));

	}#end function








	/*==============================================================================================
	COOKIE HASH
	==============================================================================================*/

	/*
	 * Возвращает Хеш строки в указанном формате с солью
	 */
	public function getHash($string='',$type='sha1'){
		$client_salt = Request::_getIP(false).Request::_getIP(true).$_SERVER['HTTP_USER_AGENT'];
		 switch($type){
			case 'md5': return md5($client_salt.$string.'@->-'.Config::getOption('general','salt','#!$DefaulT@SalT$!#'));
			default: return sha1($client_salt.$string.'@->-'.Config::getOption('general','salt','#!$DefaulT@SalT$!#'));
		 }
	}#end function





	/*==============================================================================================
	Права доступа
	==============================================================================================*/


	/*
	 * Проверяет права доступа пользователя
	 * 
	 * $object - идентификатор объекта
	 * 
	 * Возвращает TRUE, если пользователю разрешен доступ к указанному объекту
	*/
	public function checkAccess($object=''){

		#Учетная запись заблокирована
		if(!$this->get('enabled',false)) return false;

		#Если пользователь - суперадминистратор
		if($this->isSuper($this->user_id)) return true;

		#Непонятно, к чему запрошен доступ
		if(!isset($this->alist[$object])) return false;

		$access = $this->alist[$object];
		if(!is_array($access)) return false;

		#У пользователя нет доступов 
		$user_access = $this->get('access',false);
		if(empty($user_access)) return false;

		#Есть доступ к объекту
		if(!empty($user_access[$object])) return true;

		#Проверяем доступ к альтернативным объектам
		if(!empty($access[1])&&is_array($access[1])){
			foreach($access[1] as $a){
				if(!empty($user_access[$a])) return true;
			}
		}

		return false;
	}#end function


	/*
	 * Возвращает справочник прав доступа
	 * если $assoc = true, возвращается ассоциированный массив ключ=>значение, иначе -> двумерный массив
	 */
	public function getAccessList($assoc=false){
		$result = array();
		if($assoc){
			foreach($this->alist as $k=>$v){
				$result[$k]=$v[0];
			}
		}else{
			foreach($this->alist as $k=>$v){
				$result[]=array(
					'access'	=> $k,
					'name'		=> $v[0],
					'more'		=> (!empty($v[1])?$v[1]:null)
				);
			}
		}
		return $result;
	}#end function


	/*
	 * Проверяет, существует ли объект доступа с указанным именем
	 */
	public function accessObjectExists($name){
		return array_key_exists($name, $this->alist);
	}


	/*
	 * Добавление записи в протокол
	 */
	public function actionLog($fields=array()){

		if(empty($fields)) return false;
		if(empty($this->user_id)) $this->checkAuthStatus();
		$fields['user_id']		= $this->user_id;
		$fields['session_uid']	= intval(Session::_get('session_uid'));

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
	ФУНКЦИИ: Работа с меню
	==============================================================================================*/



	/*
	 * Построение меню для пользователя
	 */
	public function getUserMenu($menu_id=0){

		$this->db->prepare('SELECT * FROM `menu_map` WHERE `menu_id`=?');
		$this->db->bind(intval($menu_id));
		if(($list = $this->db->select())===false) return false;

		$menu = array();
		$filter = array();
		$item_ids = array();
		$childs = array();

		foreach($list as $i=>$item){
			$childs[$item['parent_id']] = true;
			if(!empty($item['is_lock'])) continue;
			$filter[]=$item;
			$item_ids[$item['item_id']] = $item['item_id'];
		}

		foreach($filter as $item){
			if(!empty($item['parent_id']) && !isset($item_ids[$item['parent_id']])) continue;
			if($item['is_folder'] && !isset($childs[$item['item_id']])) continue;
			$item['is_folder'] = ($item['is_folder'] ? true : false);
			$menu[]=$item;
		}

		return $menu;
	}#end function



}#end class

?>
