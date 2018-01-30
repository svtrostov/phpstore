<?php
/*==================================================================================================
Title	: Session class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Session{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/


	private $options = array(

		#Системные
		'session_name'		=> null,			#Наименование переменной для идентификатора сессии в Cookie клиента
		'autostart'			=> false			#Признак автоматического запуска сессии
	);


	public static $session_expire	= 86400;				#Время действия сессии в секундах
	private $session_name 			= null;					#Внутреннее имя сессии, для обращения к $_SESSION[$this->session_name][*]*
	private $session_id				= null;					#Идентификатор текущей сессии из  session_id()
	private $prefixes				= array();				#Массив поддерживаемых префиксов
															#Внутренний массив поддерживаемых префиксов, имеет вид:
															#array( 'user_'=>5 );
	private $session_ip				= null;					#IP адрес клиента, для которого запущена сессия
	private $session_ip_real		= null;					#IP адрес клиента, для которого запущена сессия, указанный в HTTP_FORVARDED_FOR

	private $xcache_session			= false;				#Сессия через XCACHE




	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	private function init($options = null){

		#Применение пользовательских опций
		if(is_array($options)) 
			$this->options = array_merge($this->options, $options);

		#Использовать XCache для хранения сессий
		if(XCache::_isEnabled() && Config::getOption('general','php_xcache',false) && Config::getOption('general','session_xcache',false)){
			$this->xcache_session = true;
			session_set_save_handler(
				array($this, 'xopen'),
				array($this, 'xclose'),
				array($this, 'xread'),
				array($this, 'xwrite'),
				array($this, 'xdestroy'),
				array($this, 'xgc')
			);
		}

		Session::$session_expire = Config::getOption('general','session_expire',(int)get_cfg_var('session.gc_maxlifetime'));

		$this->session_name = strtoupper((empty($this->options['session_name']) ? Config::getOption('general','session_name','DARKAGE') : $this->options['session_name'] ));
		if($this->options['autostart']==true) $this->start();

	}#end function




	/*
	 * Деструктор класса
	 */
	public function __destruct(){
		if($this->getStatus(false)) session_write_close();
	}#end function




	/*
	 * Чтение данных из недоступных свойств
	 */
	public function __get($name){
		if(!$this->getStatus()) return false;
		if(!isset($_SESSION[$this->session_name][$name])) return false;
		return $_SESSION[$this->session_name][$name];
	}#end function



	/*
	 * Запись данных в недоступные свойства
	 */
	public function __set($name, $value){
		if(!$this->getStatus()) return false;
		$_SESSION[$this->session_name][$name] = $value;
		return true;
	}#end function



	/*
	 * будет выполнен при использовании isset() или empty() на недоступных свойствах.
	 */
	public function __isset($name){
		if(!$this->getStatus()) return false;
		return isset($_SESSION[$this->session_name][$name]);
	}#end function



	/*
	 * будет выполнен при вызове unset() на недоступном свойстве
	 */
	public function __unset($name){
		if(!$this->getStatus()) return false;
		if(!isset($_SESSION[$this->session_name][$name])) return true;
		unset($_SESSION[$this->session_name][$name]);
		return true;
	}#end function






	/*==============================================================================================
	Функции: работа с сессией через XCache
	==============================================================================================*/


	public function xopen($save_path, $session_name){
		return true;
	}

	public function xclose(){
		return true;
	}

	public function xread($session_id){
		return (string)xcache_get('s/'.$this->session_name.'/'.$session_id);
	}

	public function xwrite($session_id, $session_data){
		return xcache_set('s/'.$this->session_name.'/'.$session_id, $session_data, Session::$session_expire);
	}

	public function xdestroy($session_id){
		xcache_unset('s/'.$this->session_name.'/'.$session_id);
		return true;
	}

	public function xgc($max_lifetime){
		return true;
	}

	public function xexists($session_id){
		return xcache_isset('s/'.$this->session_name.'/'.$session_id);
	}


	public function xcacheSession(){
		return $this->xcache_session;
	}





	/*==============================================================================================
	Функции: работа с сессией
	==============================================================================================*/



	/*
	 * Старт сессии
	 */
	public function start($session_id=null){

		#Если сессия запущена - возвращаем true
		if(!empty($this->session_id)) return true;

		#Старт сессии
		session_name($this->session_name);
		session_cache_expire(floor(Session::$session_expire/60));
		if(!empty($session_id)) session_id($session_id);
		if( session_start() === false) return false;
		if(!isset($_SESSION[$this->session_name])) $_SESSION[$this->session_name] = array();

		$this->session_id		= session_id();
		$this->session_ip		= Request::_get('ip_addr');
		$this->session_ip_real	= Request::_get('ip_real');

		$_SESSION[$this->session_name]['session_id'] = $this->session_id;
		$_SESSION[$this->session_name]['session_ip'] = $this->session_ip;
		$_SESSION[$this->session_name]['session_ip_real'] = $this->session_ip_real;

		return true;
	}#end function



	/*
	 * Остановка сессии
	 */
	public function stop(){

		#Старт сессии
		if($this->getStatus(false)){
			$this->session_id		= null;
			$this->session_ip		= null;
			$this->session_ip_real	= null;
			return @session_destroy();
		}

		return true;
	}#end function




	/*
	 * Получение идентификатора сессии
	 */
	public function getSessionID(){
		if($this->getStatus(false)) return $this->session_id;
		return false;
	}#end function





	/*
	 * Проверка статсуа сессии
	 *
	 * Принимает аргументы:
	 * $autostart - признак автоматического старта сессии, если сессия отсутствует
	 *
	 * Возвращает:
	 * TRUE, если сессия запущена, FALSE - если сессия не запущена
	*/
	public function getStatus($autostart = true){

		#Проверка статуса сессии
		$sess_id = session_id();
		$session_exists = (empty($sess_id) ? false : true);
		if($autostart && !$session_exists) return $this->start();

		return $session_exists;
	}#end function




	/*
	 * Получение всех значений сессии
	 */
	public function getAll(){
		if(!$this->getStatus()) return false;
		return (isset($_SESSION[$this->session_name]) ? $_SESSION[$this->session_name] : false);
	}#end function



	/*
	 * Получение значения из сессии
	 *
	 * Принимает аргументы:
	 * $name - имя ключа интересуемого значения
	 * если $name линейный массив ключей array('key1','key2'), 
	 * функция вернет ассоциированный массив вида: array('key1'=>'value1','key2'=>'value2')
	 * если указан NULL, имя не задано или ключа не существует - функция вернет false
	*/
	public function get($name=null){

		#Проверка сессии
		if(!$this->getStatus()) return false;
		if(empty($name)) return false;
		if(!is_array($name)) return (isset($_SESSION[$this->session_name][$name]) ? $_SESSION[$this->session_name][$name] : false);
		$result = array();
		foreach($name as $item){
			if(isset($_SESSION[$this->session_name][$item])) $result[$item] = $_SESSION[$this->session_name][$item];
		}

		return (count($result)>0 ? $result : false);
	}#end function



	/*
	 * Запись значения в сессию
	 *
	 * Принимает аргументы:
	 * $name - имя ключа значения
	 * $value - значение
	 * если $name - ассоциированный массив пар ключей и значений вида: array('key1'=>'value1','key2'=>'value2')
	 * функция запишет его в сессию, игнорируя $value
	 * если указан NULL, имя не задано или ключа не существует - функция вернет false
	*/
	public function set($name=null, $value=null){

		#Проверка сессии
		if(!$this->getStatus()) return false;
		if(empty($name)) return false;
		if(is_array($name)){
			foreach($name as $key=>$item){
				$_SESSION[$this->session_name][$key] = $item;
			}
		}else{
			$_SESSION[$this->session_name][$name] = $value;
		}

		return true;
	}#end function





	/*
	 * Проверка существования значения в сессии
	 *
	 * Принимает аргументы:
	 * $name - имя ключа интересуемого значения
	 * Возвращает TRUE, если параметр существует и FALSE в противном случае
	*/
	public function exists($name=null){

		#Проверка сессии
		if(!$this->getStatus()) return false;
		if(empty($name)) return false;

		return (isset($_SESSION[$this->session_name][$name]) ? true : false);
	}#end function




	/*
	 * Удаляет из сессии значение
	 *
	 * Принимает аргументы:
	 * $key - ключ или линейный массив ключей вида: array('key1', 'key2', 'key3')
	 *
	 * Возвращает:
	 * TRUE, если элементы удалены, FALSE - в случае ошибки
	*/
	public function delete($name = null){

		if(!$this->getStatus()) return false;
		if(empty($name)) return false;

		if(!is_array($name)){
			if(isset($_SESSION[$this->session_name][$name])) unset($_SESSION[$this->session_name][$name]);
			return true;
		}

		foreach($name as $item){
			if(isset($_SESSION[$this->session_name][$item])) unset($_SESSION[$this->session_name][$item]);
		}

		return true;
	}#end function




	/*
	 * Проверка корректности сессии пользователя
	 *
	 * Принимает параметры:
	 * $params - ассоциированный массив параметров проверки, имеет вид
	 * array(
	 *  'key'=>'value'  *key - имя в сессии  $_SESSION[$this->session_name][key], value = проверяемое значение, если value = null, то проверяется только факт наличия в сессии значения
	 * )
	 * $ignore_unsets - признак, когда TRUE, то отсутсвие параметра key в сессии приведет к возврату ошибки 
	 *
	 * Возвращает FALSE если данные сессии корректны или значение, которое привело к выводу о некорректной сессии
	 * Если не удалось инициализировать сессию - будет возвращено 'session'
	 * Если IP некорректны - будет возвращено 'session_ip'
	 * Если ID сессии некорректны - будет возвращено 'session_id'
	 */
	public function badSession($params=null, $check_ips = true, $check_sesison_id = true, $ignore_unsets = false){

		if(!$this->getStatus()) return 'session';
		if(!is_array($params)) $params = array();

		#Проверка Идентификатора сессии в сессии и текущего идентификатора сессии
		if($check_sesison_id){
			if($this->session_id != session_id()) return 'session_id';
		}

		#Проверка IP в сессии и текущего IP пользователя
		if($check_ips){
			if(
				($this->session_ip != Request::_get('ip_addr')) || 
				($this->session_ip_real != Request::_get('ip_real'))
			) return 'session_ip';
		}

		foreach($params as $key=>$value){
			if(isset($_SESSION[$this->session_name][$key])){
				if(!is_null($value) && strcmp($_SESSION[$this->session_name][$key],$value)!=0) return $key;
			}else{
				if(!$ignore_unsets) return $key;
			}
		}

		return false;
	}#end function




}#end class


?>
