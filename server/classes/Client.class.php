<?php
/*==================================================================================================
Title	: Client class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Client{

	use Trait_SingletonUnique;


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $xcache = null;

	#db
	public $db = null;
	public $table_clients = null;

	#Идентификатор клиента
	public $client_id = 0;

	#Признак того, что статус клиента был проверен
	public $auth_status_checked = false;

	#Информация о клиента
	public $info = null;

	#Названия COOKIEs
	public $session_name = null;
	public $cookie_auth = null;
	public $cookie_lang = null;
	public $cookie_theme = null;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		$this->db = Database::getInstance('main');
		$this->table_clients = $this->db->getTableName('clients');
		$this->xcache		= XCache::getInstance();
		$this->session_name	= strtoupper(Config::getOption('general','session_name','DTBOX'));
		$this->cookie_auth	= $this->session_name.'_'.strtoupper(Config::getOption('general','user_cookie_auth_info','UAUTH'));
	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function


	public function __destruct(){
		if($this->auth_status_checked && !$this->client_id) Session::_stop();
	}#end function


	/*==============================================================================================
	Информация
	==============================================================================================*/


	/*
	 * Получение записи о клиенте из базы данных
	 */
	public function clientRecord($client_id=0, $hide_password=true){
		if(empty($client_id)) return false;
		$this->db->prepare('SELECT * FROM `clients` WHERE `client_id`=? LIMIT 1');
		$this->db->bind($client_id);
		if(($client = $this->db->selectRecord()) === false) return false;
		if(!empty($client)&&$hide_password) $client['password']='';
		return $client;
	}#end function



	/*
	 * Получение записи о клиенте из базы данных
	 */
	public function getClientInfo($read_from_db=false){
		if(empty($this->client_id)){
			if(!$this->checkAuthStatus()) return false;
		}
		if(!$read_from_db&&!empty($this->info))return $this->info;
		$this->db->prepare('SELECT * FROM `clients` WHERE `client_id`=? LIMIT 1');
		$this->db->bind($this->client_id);
		if(($this->info = $this->db->selectRecord()) === false) return false;
		return $this->info;
	}#end function



	/*
	 * Обновление записи о клиенте в базе данных
	 */
	public function dbUpdate($key=null,$value=null){
		if(empty($key))return false;
		if(!is_array($key)){
			$this->db->prepare('UPDATE `'.$this->table_clients.'` SET `?`=? WHERE `client_id`=?');
			$this->db->bindField($key);
			$this->db->bindText($value);
			$this->db->bindNum($this->client_id);
			if($this->db->update() === false) return false;
		}
		return true;
	}#end function




	/*
	 * Получение информации о клиенте
	 */
	public function get($key, $default=null){
		if(empty($this->info)) $this->getClientInfo();
		if(is_array($key)) return arrayCustomFields($this->info, $key);
		if(!isset($this->info[$key])) return $default;
		return $this->info[$key];
	}#end function


	/*
	 * Возвращает ID клиента
	 */
	public function getClientID(){
		if(empty($this->client_id)) $this->checkAuthStatus();
		return $this->client_id;
	}#end function



	/*
	 * Проверка занятости логина
	 */
	public function loginExists($username){
		if(empty($username)) return false;
		$this->db->prepare('SELECT count(*) FROM `clients` WHERE `username` LIKE ? LIMIT 1');
		$this->db->bind($username);
		return ($this->db->result() > 0);
	}#end function


	/*
	 * Добавление клиента
	 */
	public function clientNew($fields=array()){
		if(empty($fields)) return false;
		return $this->db->addRecord('clients', $fields);
	}#end function


	/*
	 * Обновление клиента
	 */
	public function clientUpdate($client_id=0, $fields=array()){
		if(empty($fields)) return false;
		$client_id = intval($client_id);
		if(empty($client_id)) $client_id = $this->getClientID();
		return $this->db->updateRecord('clients', array(
			'client_id' => $client_id
		), $fields);
	}#end function






	/*==============================================================================================
	Аутентификация
	==============================================================================================*/



	/*
	 * Проверяет статус аутентификации клиента
	 */
	public function checkAuthStatus(){

		if($this->auth_status_checked) return (!empty($this->client_id) ? true : false);

		$this->auth_status_checked = true;

		$session_id = Request::_getGPC($this->session_name, false, 'c');

		#Проверка существования COOKIE с идентификатором сессии
		if($session_id !== false){

			#Если сессии хранятся в XCACHE
			if(Session::_xcacheSession()){
				#проверяем существование сессии в XCACHE
				if(Session::_xexists($session_id)){
					#Проверка сессии
					if(Session::_badSession(array('session_name'=>$this->session_name,'client_id'=>null))===false){
						return $this->startClientSession(null);
					}
				}
			}else{
				#Проверка сессии
				if(Session::_badSession(array('session_name'=>$this->session_name,'client_id'=>null))===false){
					return $this->startClientSession(null);
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

		list($client_id, $name, $pwd_hash, $remember) = explode('/', $cookie);
		$client_id = abs(intval($client_id));
		$name = strval($name);
		$pwd_hash = strval($pwd_hash);
		$remember = ($remember == 1 ? 1 : 0);


		if(empty($client_id)||empty($name)||empty($pwd_hash))return false;

		$this->db->prepare('SELECT * FROM `clients` WHERE `client_id`=? AND `username` LIKE ? LIMIT 1');
		$this->db->bind($client_id);
		$this->db->bind($name);

		if(($info = $this->db->selectRecord()) === false) return false;
		if(empty($info)) return false;

		if(strcasecmp($this->getHash($info['password']), $pwd_hash) !== 0 &&
		  strcasecmp($this->getHash(sha1($info['password'])), $pwd_hash) !== 0) return false;
		if(empty($info['enabled'])) return false;

		$info['remember_me'] = $remember;
		$info['password']	 = $pwd_hash;

		return $this->startClientSession($info);
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
		$cookie = $this->info['client_id'].'/'.$this->info['username'].'/'.$pwd_hash.'/'.$remember;
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
	private function startClientSession($client=null){
		
		#Передан массив для записи в сессию - новая сессия
		if(!empty($client)&&is_array($client)){

			$client['session_name']	= $this->session_name;
			$this->info 			= $client;
			$this->client_id 		= intval($client['client_id']);

			#Запись в сессию информации о клиенте
			#Пишется целиком все поля таблицы users
			Session::_set($client);

			#Если сессии хранятся в XCACHE
			#Удаляем предыдущую сессию, если она есть
			if(Session::_xcacheSession()){
				$session_id = Session::_getSessionID();
				$var_uid = 'sess/'.$this->session_name.'/'.$this->client_id.'/last_uid';
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
			$this->client_id= intval(Session::_get('client_id'));
			$this->getClientInfo();
		}
		return true;
	}#end function



	/*
	 * Аутентификация клиента
	 */
	public function auth($username='', $password='', $remember=0){

		if(empty($username)||empty($password))return array('result'=>false,'desc'=>'Не заданы имя пользователя и/или пароль');

		$pwd_hash = sha1($password);

		$this->db->prepare('SELECT * FROM `clients` WHERE `username`=? AND `password` IN (?,?) LIMIT 1');
		$this->db->bind($username);
		$this->db->bind($password);
		$this->db->bind($pwd_hash);
		
		#Ошибка получения данных из БД
		if(($info = $this->db->selectRecord()) === false) return array('result'=>false,'desc'=>'Сервис временно недоступен');
		
		#Неправильный логин/пароль
		if(empty($info)) return array('result'=>false,'desc'=>'Неверное имя пользователя и/или пароль');
		if(empty($info['enabled'])) return array('result'=>false,'desc'=>'Учетная запись заблокирована');

		$info['password']		= $pwd_hash;
		$info['remember_me']	= $remember;

		$this->startClientSession($info);
		$this->setAuthCookie($remember);

		return array('result'=>true,'desc'=>null);
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



	/*
	 * Поиск клиентов
	 */
	public function searchClients($data=array()){
		$term = addslashes((empty($data['term']) ? '' : trim($data['term'])));
		$enabled = (!isset($data['enabled']) ? 1 : (intval($data['enabled'])==0 ? 0 : 1));
		$limit = (!isset($data['limit']) ? 100 : intval($data['limit']));
		$manager_id = (!isset($data['manager_id']) || $data['manager_id']=='all' ? -1 : abs(intval($data['manager_id'])));

		$where = '`enabled`>=0';//.$enabled;
		if(!empty($term)){
			$where.= ' AND ('.(is_numeric($term) ? '`client_id`="'.$term.'" OR ' : '').' `name` LIKE "%'.$term.'%" OR `email` LIKE "%'.$term.'%" OR `username` LIKE "%'.$term.'%" OR `company` LIKE "%'.$term.'%")';
		}
		if($manager_id > -1){
			$where.= ' AND `manager_id`='.$manager_id;
		}

		$sql = 'SELECT `client_id`, `enabled`, `username`, `is_company`, `company`, `name`, `email`, `phone`, `address`, `city`, `country`, `zip`, `inn`, `kpp`, `create_time`, `create_ip_addr`, `create_ip_real`, `discount_id`, `manager_id` FROM `clients` WHERE '.$where.' ORDER BY `client_id` DESC '.($limit>0 ?' LIMIT '.$limit : '');

		return $this->db->select($sql);

	}#end function



}#end class

?>
