<?php
/*==================================================================================================
Title	: Request class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



class Request{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#Кеш $_GET, $_POST, $_COOKIE, $_FILES и заголовки
	public $headers = array();

	#Запрос клиента
	public $request = array();






	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){

		#Метод запроса:  'GET', 'HEAD', 'POST', 'PUT'
		$this->request['method'] = $_SERVER['REQUEST_METHOD'];

		#SSL соединение
		$this->request['ssl'] = $this->isSSL();

		#Протокол
		$this->request['protocol'] = ($this->request['ssl'] ? 'https' : 'http');

		#AJAX запрос
		$this->request['ajax'] = ($this->isAjax() ? true : ($this->getInt('ajax',0) == 1 ? true : false));

		#Action - действие
		$this->request['action'] = $this->action = $this->getStr('action');
		

		#Запрошенный документ
		$this->request['document'] = preg_replace('/\/\/+/', '/', strtok($_SERVER['REQUEST_URI'],'?#'));

		#Переданные GET параметры
		$this->request['query'] = $_SERVER['QUERY_STRING'];
		$this->request['get'] = $_GET;

		#Предопределенный путь
		$this->request['path'] = $this->getPath('/main/index');

		#Маршрут запроса
		$this->request['route'] = $this->getRoute($this->request['path']);

		#IP адрес клиента
		$this->request['ip_addr'] = $this->getIP(false);	#IP адрес клиента
		$this->request['ip_real'] = $this->getIP(true);		#Реальный IP адрес клиента

	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function






	/*==============================================================================================
	Получение параметров запроса
	==============================================================================================*/




	/*
	 * Получение информации о запросе клиента
	 */
	public function get($key, $default=null){
		switch($key){
			case 'scheme': return ($this->request['ssl'] ? 'https' : 'http');
			case 'ajax': return $this->request['ajax'];
			case 'all': return $this->request;
			case 'files': return $this->getFiles();
			case 'headers': return $this->getHeaders();
			case 'module': return $this->request['route']['module'];
			case 'page': return $this->request['route']['page'];
			case 'dir': return $this->request['route']['dir'];
			case 'way': return $this->request['route']['way'];
			default:
				if(!isset($this->request[$key])) return $default;
				return $this->request[$key];
		}
	}#end function




	/*
	 * Функция, проверяющая произведен ли запрос по XMLHttpRequest (AJAX)
	 */
	public function isAjax(){
		return (strcasecmp($this->getHeader('x-requested-with'), 'XMLHttpRequest') == 0);
	}#end function



	/*
	 * Функция, проверяющая произведен ли запрос с SSL
	 */
	public function isSSL(){
		return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
	}#end function



	/*
	 * Функция определяет, что браузер предоставил сертификат SSL клиента
	 */
	public function hasValidClientCert(){
		if(
			!$this->isSSL() ||
			!isset($_SERVER['SSL_CLIENT_CERT']) ||
			!isset($_SERVER['SSL_CLIENT_M_SERIAL']) ||
			!isset($_SERVER['SSL_CLIENT_V_END']) ||
			!isset($_SERVER['SSL_CLIENT_VERIFY']) ||
			$_SERVER['SSL_CLIENT_VERIFY'] !== 'SUCCESS' ||
			!isset($_SERVER['SSL_CLIENT_I_DN'])
		) return false;
		if($_SERVER['SSL_CLIENT_V_REMAIN'] <= 0) return false;
		return true;
	}




	/*
	 * Определение IP адреса клиента
	 */
	public function getIP($real_ip = false){
		if($real_ip == true) return $_SERVER['REMOTE_ADDR'];
		$user_ip = '';
		if ( getenv('HTTP_FORWARDED_FOR') ) $user_ip = getenv('HTTP_FORWARDED_FOR');
		elseif ( getenv('HTTP_X_FORWARDED_FOR') ) $user_ip = getenv('HTTP_X_FORWARDED_FOR');
		elseif ( getenv('HTTP_X_COMING_FROM') ) $user_ip = getenv('HTTP_X_COMING_FROM');
		elseif ( getenv('HTTP_VIA') ) $user_ip = getenv('HTTP_VIA');
		elseif ( getenv('HTTP_XROXY_CONNECTION') ) $user_ip = getenv('HTTP_XROXY_CONNECTION');
		elseif ( getenv('HTTP_CLIENT_IP') ) $user_ip = getenv('HTTP_CLIENT_IP');
		elseif ( getenv('REMOTE_ADDR') ) $user_ip = getenv('REMOTE_ADDR');
		$user_ip = trim($user_ip);
		if ( empty($user_ip) ) return $_SERVER['REMOTE_ADDR'];
		if ( !preg_match("/^([1-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])(\.([0-9]|[1-9][0-9]|1[0-9][0-9]|2[0-4][0-9]|25[0-5])){3}$/", $user_ip) ) return $_SERVER['REMOTE_ADDR'];
		return $user_ip;
	}#end function



	/*
	 * Возвращает путь из URI документа
	 */
	public function getPath($default=''){
		$path = trim($this->getStr('path'));
		$path = (!empty($path) ?  preg_replace('/\/\/+/', '/', $path) : $this->request['document']);
		if(empty($path)) $path = $default;
		$path = '/'.trim($path,'/');

		#Фильтр Alias:
		#Замена текущего document_uri предопределенными значениями из файла конфигурации [DIR_CONFIG]/aliases.config.php
		if( ($alias = Config::getOption('aliases', $path)) !== false) $path = $alias;

		return $path;
	}#end function



	/*
	 * Возвращает маршрут запроса из заданного пути
	 */
	public function getRoute($path=''){

		$list = explode('/',trim($path,'/'));
		$module	= null;
		$page	= null;
		$dir	= array();
		$way	= array();
		$count	= 0;
		if(!empty($list)){
			$count = count($list);
			if($count == 1){
				if(strcasecmp('main',$list[0])!=0){
					$this->request['path'] = $this->path = '/main/'.$list[0];
					$list = array('main',$list[0]);
				}
				else{
					$this->request['path'] = $this->path = '/main/index';
					$list = array('main','index');
				}
				$count = 2;
			}
			$module = ucwords($list[0]);
			$page = $list[$count-1];
			$dir = array_slice($list,1,$count-2);
			$way = array_slice($list,1,$count-1);
		}

		return array(
			'list'	=> $list,
			'module'=> $module,
			'dir'	=> $dir,
			'way'	=> $way,
			'page'	=> $page
		);
	}#end function



	/*
	 * Получение заголовков запроса
	 */
	public function getHeaders(){

		if(!empty($this->headers)) return $this->headers;
		if(!function_exists('getallheaders')){
			$this->headers = array();
			foreach ($_SERVER as $k=>$v){
				if (strncmp($k, 'HTTP_', 5) == 0){ 
					$k = strtr(ucwords(strtolower(strtr(substr($k, 5), '_', ' '))),' ','-'); 
					$this->headers[$k] = $v; 
				} else if ($k == 'CONTENT_TYPE') { 
					$this->headers['Content-Type'] = $v;
				} else if ($k == 'CONTENT_LENGTH') { 
					$this->headers['Content-Length'] = $v;
				}
			}#foreach
		}
		else{
			$this->headers = getallheaders();
		}

		return $this->headers;
	}#end function


 
	/*
	 * Получение определенного заголовка
	 */
	public function getHeader($key, $default=null){

		if(empty($key)) return $default;
		$this->getHeaders();
		$key = strtr(ucwords(strtolower(strtr($key, '-', ' '))),' ','-');

		return isset($this->headers[$key]) ? $this->headers[$key] : $default;
	}#end function





	/*
	 * Получение информации о загруженных файлах
	 *
	 * Тут же выполняется преобразование массива $_FILES во внутренний формат.
	 *
	 * Исходный формат $_FILES:
	 * Array (
	 *	[image] => Array(
	 *		[name] => Array([0] => 400.png)
	 *		[type] => Array([0] => image/png)
	 *		[tmp_name] => Array([0] => /tmp/php5Wx0aJ)
	 *		[error] => Array([0] => 0)
	 *		[size] => Array([0] => 15726)
	 *	)
	 * )
	 *
	 * Получаемый формат:
	 * Array(
	 *	[image] => Array(
	 *		[0] => Array(
	 *			[name] => 400.png
	 *			[type] => image/png
	 *			[tmp_name] => /tmp/php5Wx0aJ
	 *			[error] => 0
	 *			[size] => 15726
	 *		)
	 *	)
	 * )
	 */
	public function getFiles(){

		if(empty($_FILES)) return array();
		if(isset($this->request['files'])) return $this->request['files'];

		$this->request['files'] = array();
		foreach ($_FILES as $key => $items){
			foreach ($items as $i => $value){
				$this->request['files'][$i][$key] = $value;
			}
		}

		return $this->request['files'];
	}#end function











	/*
	 * ==============================================================================================
	 * Работа с массивами _GET _POST _COOKIE
	 * ==============================================================================================
	 */



	/**
	 * Получение значения из массива GPC
	 *
	 * Функция просмотривает массивы _GET _POST _COOKIE в заданной последовательности и 
	 * пытается вернуть значение по указанному ключу, если в массивах 
	 * 
	 * 
	 * @param mixed $key Ключ, по которому требуется получить значение, 
	 * может быть задан в виде массива (путь ключа) или в виде текста (сам ключ),
	 * 
	 * @param mixed $default Значение, возвращаемое если ничего не найдено по ключу в _GET _POST _COOKIE
	 * 
	 * @param string $gpc Последовательность просмотра массивов, может состоять из дрех символов в любых вариациях: 
	 * "g" - поиск в массиве _GET
	 * "p" - поиск в массиве _POST
	 * "c" - поиск в массиве _COOKIE
	 * Примеры:
	 * 'pgc' - будет осуществлен поиск значения сначала в _POST, потом в _GET, потом в _COOKIE, если не найдено - вернет $default
	 * 'pg' - будет осуществлен поиск значения сначала в _POST, потом в _GET, если не найдено - вернет $default
	 * 'gc' - будет осуществлен поиск значения сначала в _GET, потом в _COOKIE, если не найдено - вернет $default
	 * 
	 * @return string
	 */
	public function getGPC($key=null, $default=null, $gpc='pg'){

		if(empty($gpc)) return $default;

		$arr = str_split((string)$gpc, 1);

		foreach($arr as $v){

			switch($v){

				#_GET
				case 'g':
					if( ($result = (isset($_GET[$key]) ? $_GET[$key] : null)) !== null) return $result;
				break;

				#_POST
				case 'p':
					if( ($result = (isset($_POST[$key]) ? $_POST[$key] : null)) !== null) return $result;
				break;

				#_COOKIE
				case 'c':
					if( ($result = (isset($_COOKIE[$key]) ? $_COOKIE[$key] : null)) !== null) return $result;
				break;

			}

		}

		return $default;
	}#end function



	/*
	 * Получение параметра запроса: Массив
	 */
	public function getArray($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, $default, $gpc);
		return (empty($value)||!is_array($value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: INT -2147483647 to 2147483647
	 */
	public function getInt($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, $default, $gpc);
		return $value === 'on' ? 1 : ($value === 'off' ? $default : intval($value));
	}#end function


	/*
	 * Получение параметра запроса: Date в формате dd.mm.YYYY
	 */
	public function getDate($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, false, $gpc);
		return $value === false ? $default : (!preg_match("/^[0-9]{2}\.[0-9]{2}\.[0-9]{4}$/", $value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: Date в формате SQL YYYY-mm-dd
	 */
	public function getSQLDate($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, false, $gpc);
		return $value === false ? $default : (!preg_match("/^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$/", $value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: email
	 */
	public function getEmail($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, false, $gpc);
		return $value === false ? $default : (!preg_match("/^([a-z0-9\+_\-]+)(\.[a-z0-9\+_\-]+)*@([a-z0-9\-]+\.)+[a-z]{2,6}$/ix", $value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: IP
	 */
	public function getIPAddress($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, false, $gpc);
		return $value === false ? $default : (!Validator::isIP($value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: URL
	 */
	public function getURL($key=null, $default=0, $gpc='pg'){
		$value = $this->getGPC($key, false, $gpc);
		return $value === false ? $default : (!Validator::isURL($value) ? $default : $value);
	}#end function


	/*
	 * Получение параметра запроса: BOOL true / false
	 */
	public function getBool($key=null, $default=0, $gpc='pg'){
		$value = strtolower($this->getGPC($key, $default, $gpc));
		return in_array($value, array('on','1','true'));
	}#end function


	/*
	 * Получение параметра запроса как числа: BOOL true=1 / false=0
	 */
	public function getBoolAsInt($key=null, $default=0, $gpc='pg'){
		$value = strtolower($this->getGPC($key, $default, $gpc));
		return (in_array($value, array('on','1','true')) ? 1 : 0);
	}#end function


	/*
	 * Получение параметра запроса: Выбор из предопределенного списка или $default
	 */
	public function getEnum($key=null, $enum=array(), $default=0, $gpc='pg'){
		$value = (string)$this->getGPC($key, $default, $gpc);
		return in_array($value, $enum, true) ? $value : $default;
	}#end function




	/*
	 * Получение параметра запроса: FLOAT
	 */
	public function getFloat($key=null, $default=0, $gpc='pg'){
		return floatval(str_replace(',','.',$this->getGPC($key, $default, $gpc)));
	}#end function


	/*
	 * Получение параметра запроса: BIGINT
	 */
	public function getId($key=null, $default=0, $gpc='pg'){
		$value = @ltrim($this->getGPC($key, $default, $gpc),'-');
		return (preg_match('/^\d+$/', $value) ? $value : $default);
	}#end function


	/*
	 * Получение параметра запроса: STRING
	 */
	public function getStr($key=null, $default='', $gpc='pg'){
		return strval($this->getGPC($key, $default, $gpc));
	}#end function


	/*
	 * Получение параметра запроса из COOKIE
	 */
	public function getCookie($key=null, $default=''){
		return strval($this->getGPC($key, $default, 'c'));
	}#end function


	/*
	 * Получение параметра запроса: STRING
	 */
	public function getHash($key=null, $default=false, $gpc='pg'){
		$pwd = strval($this->getGPC($key, false, $gpc));
		return empty($pwd) ? $default : sha1($pwd.'-'.Config::getOption('general','salt',''));
	}#end function


}#end class


?>
