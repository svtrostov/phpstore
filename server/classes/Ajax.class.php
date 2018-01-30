<?php
/*==================================================================================================
Title	: Ajax response class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


/*

array(

	#Установить заголовок страницы 
	'title' => "",
	
	#Провести редирект на указанный URL
	'location' => "",
	
	#Выполнить запрос по AJAX на указанный URL посредством POST
	'request' => array(
		'location'	=> '',						#URL для выполнения POST запроса
		'data'		=> array([key]=>[value]),	#параметры, передаваемые в POST запросе
		'callback'	=> ''						#функция JavaScript, которая должна быть вызвана по завершении запроса
	),

	#Подключить на страницу медиа-файл (JS, CSS  и т.п.)
	'required' => array(
		array(
			'name'		=> "",	#путь и имя файла относительно корневой директории
			'callback'	=> "",	#Функция, которая должна быть вызвана из подключенного скрипта
			'os'		=> "",	#проверка на соответствие ОС. Формат: win|mac|linux|ios|android|webos, ..., ..., если не задано - для всех ОС
			'browser'	=> ""	#проверка на соответствие браузера. Формат: chrome|firefox|ie|opera|safari lt|lte|gt|gte [version], ..., ..., если не задано - для всех браузеров
		)
	),

	#Сообщения, отправляемые клиенту
	'messages' => array(
		array(
			'id'		=> null,	#Идентификатор сообщения, если установлено, то у пользователя будет возможность игнорировать сообщение в дальнейшем (например, записью ID сообщения в cookie)
			'title'		=> "",		#Заголовок сообщения
			'text'		=> "",		#Текст сообщения
			'type'		=> "",		#Тип сообщения: success, error, warning, info		
			'display'	=> ''		#Тип отображения клиенту на экране: none|window|hint
		)
	),

	#Произвольные данные для клиента
	'data' => [object, array, string, ...],

	#Функция JavaScript, которая должна быть вызвана по завершении запроса
	'callback' => "",

	#Статус обработки запроса
	'status' => "",
	
	#Действие, которое было запрошено со стороны клиента
	'action' => "",

	#Параметры GET запроса
	'get' => "",

	#Путь запроса (/main/login, например)
	'document' => "",

	#HTML контент, возвращаемый через AJAX запрос
	'content' => "",

	#timestamp
	'timestamp' => 1234567890 #timestamp
	 
	#Дополнительный стек данных для обработки на клиенте
	#в отличии от data, являющегося результатом обработки запроса и массивом данных ответа,
	#в этот массив пишутся смежные данные
	#Массив stack обрабатывается на клиенте исключительно в конкретно определенной функции
	#в момент инициализации приложения клиента и не может быть обработан какими-либо callback функциями
	#При этом обработка происходит вне зависимости от статуса обработки основного запроса
	'stack' => array()
	

);



*/

class Ajax{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#Массив данных, отправляемых в AJAX ответе
	private $data = array();



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		
		$this->data = array(
			'action' 	=> Request::_get('action'),
			'document' 	=> Request::_get('document'),
			'get' 		=> Request::_get('get'),
			'data'		=> null,
			'status'	=> 'undefined',
			'debug'		=> '',
			'content'	=> null,
			'stack'		=> array(
				'RuId'	=> Request::_getStr('RuId',null)
			),
			'timestamp' => time()
		);

	}#end function






	/*==============================================================================================
	Функции
	==============================================================================================*/


	/*
	 * Возвращает итоговый массив в JSON формате, готовый для отправки клиенту
	 * Помечает ответ как выполненных с ошибкой
	 */
	public function responseError($title, $message){
		$this->data['status'] = 'error';
		$this->addMessage($title, $message, 'error', null, null);
		return true;
	}#end function




	/*
	 * Возвращает итоговый массив в JSON формате, готовый для отправки клиенту
	 * Помечает ответ как выполненных успешно
	 */
	public function responseSuccess($title, $message, $display='window'){
		if($this->data['status'] != 'undefined' && $this->data['status'] != 'success') return false;
		$this->addMessage($title, $message, 'success', $display, null);
		return true;
	}#end function



	/*
	 * Устанавливает статус обработки запроса как успешный
	 */
	public function commit(){
		if($this->data['status'] != 'undefined' && $this->data['status'] != 'success') return false;
		$this->data['status'] = 'success';
		return true;
	}#end function



	/*
	 * Возвращает итоговый массив в JSON формате, готовый для отправки клиенту
	 */
	public function getResponseData(){
		return json_encode($this->data);
	}#end function













	/*==============================================================================================
	Функции работы с массивом data
	==============================================================================================*/


	/*
	 * Устанавливает заголовок TITLE
	 */
	public function setTitle($title=null){
		$this->data['title'] = (empty($title)? '' : $title);
	}#end function



	/*
	 * Устанавливает редирект на другую страницу посредством GET
	 */
	public function setLocation($location=null){
		if(empty($location)) return false;
		$this->data['location'] = $location;
	}#end function



	/*
	 * Выполнить запрос по AJAX на указанный URL посредством POST
	 * 
	 * $location - URL для выполнения POST запроса
	 * $data - параметры, передаваемые в POST запросе
	 * $callback - функция JavaScript, которая должна быть вызвана по завершении запроса
	 */
	public function addAJAXRequest($location=null, $data=array(), $callback=null){

		if(empty($location)) return false;
		$this->data['request'] = array(
			'location'	=> $location,
			'data'		=> $data,
			'callback'	=> $callback
		);

		return true;
	}#end function






	/*
	 * Подключить на страницу медиа-файл (JS, CSS  и т.п.)
	 * 
	 * $file - путь и имя файла относительно корневой директории
	 * callback - Функция, которая должна быть вызвана из подключенного скрипта
	 * $os - проверка на соответствие ОС. Формат: win|mac|linux|ios|android|webos, ..., ..., если не задано - для всех ОС
	 * $browser - проверка на соответствие браузера. Формат: chrome|firefox|ie|opera|safari lt|lte|gt|gte [version], ..., ..., если не задано - для всех браузеров
	 */
	public function addRequired($file=null, $callback=null, $os=null, $browser=null){

		if(empty($file)) return false;
		if(!isset($this->data['required'])) $this->data['required'] = array();

		$this->data['required'][] = array(
			'file'		=> $file,
			'callback'	=> $callback,
			'os'		=> $os,
			'browser'	=> $browser
		);

		return true;
	}#end function




	/*
	 * Очистка списка подключаемых файлов
	 */
	public function clearRequires(){
		if(isset($this->data['required'])) unset($this->data['required']);
		return true;
	}#end function




	/*
	 * Устанавливает произвольные данные
	 */
	public function setData($data=null){
		$this->data['data'] = $data;
		return true;
	}#end function




	/*
	 * Возвращает произвольные данные
	 */
	public function getData($data=null){
		return $this->data['data'];
	}#end function 



	/*
	 * Устанавливает отладочную информацию
	 */
	public function setDebug($data=null){
		$this->data['debug'] = $data;
		return true;
	}#end function



	/*
	 * Устанавливает данные для стека
	 */
	public function setStack($key='', $data=null){
		if(empty($key)) return false;
		$this->data['stack'][$key] = $data;
		return true;
	}#end function



	/*
	 * Добавляет сообщение для вывода клиенту
	 * 
	 * $title - Заголовок сообщения
	 * $message - Сообщение в формате HTML
	 * $type - тип сообщения:
	 * 			info - информационное
	 * 			warning - внимание
	 * 			error - ошибка
	 * 			success - успешно
	 * 			confirm - запрос подтверждения (ДА/НЕТ)
	 * $display - Тип отображения клиенту на экране: none|window|hint
	 * $id - Идентификатор сообщения, если установлено, то у пользователя будет возможность игнорировать сообщение в дальнейшем (например, записью ID сообщения в cookie)
	 */
	public function addMessage($title='', $message='', $type='info', $display='window', $id=null){

		if(empty($title)||empty($message)) return false;
		if(!isset($this->data['messages'])) $this->data['messages'] = array();
		$this->data['messages'][] = array(
			'id'		=> $id,
			'title'		=> $title,
			'text'		=> $message,
			'type'		=> $type,
			'display' 	=> $display
		);

		return true;
	}#end function





	/*
	 * Устанавливает функцию JavaScript, которая должна быть вызвана по завершении запроса
	 */
	public function setCallback($callback=null){
		$this->data['callback'] = $callback;
		return true;
	}#end function




	/*
	 * Устанавливает статус обработки запроса
	 */
	public function setStatus($status=null){
		if($status == 'success' && ($this->data['status'] != 'undefined' || $this->data['status'] != 'success')) return false;
		$this->data['status'] = $status;
		return true;
	}#end function



	/*
	 * Устанавливает HTML контент для возврата клиенту
	 */
	public function setContent($content=null){
		$this->data['content'] = $content;
		return true;
	}#end function



	/*
	 * Добавляет HTML контент для возврата клиенту
	 */
	public function addContent($parent=null, $content='', $type='add'){
		if(empty($parent)) return false;
		if(!is_array($this->data['content'])) $this->data['content'] = array();
		 $this->data['content'][]=array($parent,$content,$type);
		return true;
	}#end function



}#end class


?>
