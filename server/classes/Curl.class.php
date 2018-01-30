<?php
/*
======================================================================================
Описание: Класс для взаимодействия с библиотекой CURL
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
 
 
Параметры инициализации сессии CURL
$params = array(
		'url'=>'', //Запрашиваемый полный HTTP адрес
		'host'=>'', //Имя хоста
		'header'=>'', //Заголовки запроса
		'method'=>'', //Метод запроса (GET, POST)
		'referer'=>'', //
		'cookie'=>'', //Cookie данные, передаваемые в запросе
		'post_fields'=>'', //Параметры запроса, отправляемые методом POST
		'proxy'=>'', //Адрес прокси, через который требуется установить соединение(отправить запрос)
		'login'=>'', //Логин для HTTP аутентификации на удаленном сервере
		'password'=>'', //пароль для HTTP аутентификации на удаленном сервере
		'timeout'=>30 //Таймаут ожидания ответа от удаленного сервера
	);

 Пример:
 -----------------------------------------
<?php
require("curl.class.php");
$request = new _curl();
$request->init(array(
		"url"=>"http://yandex.ru",
		"host"=>"yandex.ru",
		"header"=>"",
		"method"=>"GET",
		"referer"=>"",
		"cookie"=>"",
		"proxy"=>"192.168.3.1:3128",
		"timeout"=>20
	));
	$data = $req->exec();
	echo $data["body"];
?>

======================================================================================
*/


#Функция декодирует строку, упакованную GZIP
if (!function_exists('gzdecode')) {
	function gzdecode ($data) {
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;
		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}
		if ($flags & 8)  $headerlen = strpos($data, chr(0), $headerlen) + 1; // Имя файла
		if ($flags & 16) $headerlen = strpos($data, chr(0), $headerlen) + 1; // Комментарий
		if ($flags & 2)  $headerlen += 2; // CRC по окончании данных
		$unpacked = gzinflate(substr($data, $headerlen));
		if ($unpacked === FALSE) $unpacked = $data;
		return $unpacked;
	}
}

class Curl{

	private $ch; #Идентификатор соединения

	public function __construct(){
		$this->ch = curl_init();
	}

	public function __destruct(){
		if($this->ch) curl_close($this->ch);
	}


	/*Инициализация CURL*/
	public function init($params){
		$user_agent = 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.9) Gecko/20061206 Firefox/1.5.0.9';
		$header = array(
		"Accept: text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5",
		"Accept-Language: ru-ru,ru;q=0.7,en-us;q=0.5,en;q=0.3",
		"Accept-Charset: windows-1251,utf-8;q=0.7,*;q=0.7",
		"Keep-Alive: 300");
		if (isset($params['host']) && $params['host']) $header[]="Host: ".$params['host'];
		if (isset($params['header']) && $params['header']) $header[]=$params['header'];
		
		#Настройки соединения через PROXY
		if (isset($params['proxy']) && $params['proxy']){
			@curl_setopt ($this->ch, CURLOPT_PROXY, $params['proxy']); 
			@curl_setopt($this->ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP);
		}
		
		#Настройки CURL
		@curl_setopt ( $this->ch , CURLOPT_RETURNTRANSFER , 1 );
		@curl_setopt ( $this->ch , CURLOPT_VERBOSE , 0 );
		@curl_setopt ( $this->ch , CURLOPT_HEADER , 1 );
		@curl_setopt ( $this->ch, CURLOPT_FOLLOWLOCATION, 1);
		@curl_setopt ( $this->ch , CURLOPT_HTTPHEADER, $header );
		if ($params['referer'])    @curl_setopt ($this->ch , CURLOPT_REFERER, $params['referer'] );
		@curl_setopt ( $this->ch , CURLOPT_USERAGENT, $user_agent);
		if ($params['cookie'])    @curl_setopt ($this->ch , CURLOPT_COOKIE, $params['cookie']);
		if ($params['cookie_jar']) 	 @curl_setopt ($this->ch, CURLOPT_COOKIEJAR, $params['cookie_jar']);
		if ($params['cookie_jar'])	 @curl_setopt($this->ch, CURLOPT_COOKIEFILE, $params['cookie_jar']);

		#Метод HEAD
		if ($params['method'] == "HEAD") @curl_setopt($this->ch,CURLOPT_NOBODY,1);

		#Метод POST
		if ( $params['method'] == "POST" ){
			curl_setopt( $this->ch, CURLOPT_POST, true );
			curl_setopt( $this->ch, CURLOPT_POSTFIELDS, $params['post_fields'] );
		}
		
		@curl_setopt( $this->ch, CURLOPT_URL, $params['url']);
		@curl_setopt ( $this->ch , CURLOPT_SSL_VERIFYPEER, 0 );
		@curl_setopt ( $this->ch , CURLOPT_SSL_VERIFYHOST, 0 );
		
		#Логин и пароль для HTTP аутентификации
		if (isset($params['login']) && isset($params['password']))
			@curl_setopt($this->ch , CURLOPT_USERPWD,$params['login'].':'.$params['password']);
		@curl_setopt ( $this->ch , CURLOPT_TIMEOUT, $params['timeout']);
	}

	/*Выполнение запроса к серверу
	Возвращается массив элементов 
		'header', //Заголовки ответа
		'body', //Тело ответа
		'curl_error', //Описание ошибки CURL
		'curl_errno', //Код ошибки CURL
		'http_code', //Код ответа удаленного сервера
		'last_url' //Рtальный URL, от которого был получен ответ (после редиректов с URL запроса)
	*/
	public function exec(){
		$response = curl_exec($this->ch);
		$errorno = curl_errno($this->ch);
		$result = array('header'=>'', 
						'body'=>'', 
						'curl_errno'=>0, 
						'curl_error'=>'', 
						'http_code'=>'',
						'last_url'=>'');
		if($errorno > 0){
			$result['curl_errno'] = $errorno;
			$result['curl_error'] = curl_error($this->ch);
			return $result;
		}

		$header_size = curl_getinfo($this->ch,CURLINFO_HEADER_SIZE);
		$result['header'] = substr($response, 0, $header_size);
		$result['body'] = substr( $response, $header_size );
		if(strpos($result['header'],'Content-Encoding: gzip')!==false) $result['body']=gzdecode($result['body']);
		$result['http_code'] = curl_getinfo($this->ch,CURLINFO_HTTP_CODE);
		$result['last_url'] = curl_getinfo($this->ch,CURLINFO_EFFECTIVE_URL);
		//curl_close($this->ch);
		return $result;
	}
}
?>