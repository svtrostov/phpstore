<?php
/*==================================================================================================
Title	: Config class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Config{

	use Trait_SingletonArray, Trait_Array;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#Массив конфигурационных опций
	protected $values = array();



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init($connection=null, $values=null){

		#Идентификатор соединения
		$this->connection = $connection;

		#Загрузка конфигурационного файла
		$this->load($connection);

		#Установка параметров
		$this->add($values);

	}#end function




	/*
	 * Запись данных в недоступные свойства
	 */
	public function __set($key, $value){
		$this->values[$key] = $value;
	}#end function



	/*
	 * Чтение данных из недоступных свойств
	 */
	public function __get($key){
		return $this->get($key);
	}#end function



	/*
	 * будет выполнен при использовании isset() или empty() на недоступных свойствах.
	 */
	public function __isset($key){
		return isset($this->values[$key]);
	}#end function



	/*
	 * будет выполнен при вызове unset() на недоступном свойстве
	 */
	public function __unset($key){
		if(isset($this->values[$key])) unset($this->values[$key]);
		return true;
	}#end function






	/*==============================================================================================
	Функции
	==============================================================================================*/



	/*
	 * Алиас для getInstance
	 */
	public static function getConfig($connection=null){
		return Config::getInstance($connection);
	}#end function



	/*
	 * Возвращает массив параметров
	 */
	public static function getOptions($connection=null){
		return Config::getInstance($connection)->getAll();
	}#end function



	/*
	 * Возвращает параметр
	 */
	public static function getOption($connection=null, $name='', $default=false){
		return Config::getInstance($connection)->get($name,$default);
	}#end function



	/*
	 * Возвращает параметр или указанное значение по-умолчанию, если параметра нет
	 */
	public static function pickOption($connection=null, $name='', $default=false){
		return Config::getInstance($connection)->pick($name, $default);
	}#end function



	/*
	 * Задает значение опции
	 */
	public static function setOption($connection=null, $key='', $value=null){
		return Config::getInstance($connection)->set($key, $value);
	}#end function



	/*
	 * Поиск и загрузка файла
	 */
	protected function load($filename=''){

		$filename = realpath(DIR_CONFIG.'/'.trim($filename, " .\r\n\t\\/").'.config.php');
		if(!is_file($filename)||!is_readable($filename))return false;

		#Получение конфигурации
		try{
			$result = include($filename);
		}catch(Exception $e){
			return debugError(array(
				'id'		=> 'ECONF001',
				'desc'		=> 'Error on include file = '.$filename,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}

		$this->add( $result );

		return true;
	}#end function



	/*
	 * Вернуть массив параметров
	 */
	public function getAll(){
		return $this->values;
	}#end function



	/*
	 * Очистить массив параметров
	 */
	public function clearAll(){
		unset($this->values);
		$this->values = array();
		return true;
	}#end function




	/*
	 * Очистить массив параметров
	 */
	public function getCount(){
		return sizeof($this->values);
	}#end function



	/*
	 * Вернуть значение параметра
	 */
	public function get($key='', $default = false){
		return is_array($key) ? $this->arrayGetValue($this->values, $key, $default) : (isset($this->values[$key]) ? $this->values[$key] : $default);
	}#end function




	/*
	 * Задать значение параметра
	 */
	public function set($key, $value){
		if(is_array($key)) $this->arraySetValue($this->values, $key, $value);
		else $this->values[$key] = $value;
	}#end function




	/*
	 * Проверка существования
	 */
	public function exists($key){
		return isset($this->values[$key]);
	}#end function



	/*
	 * Удаление параметра
	 */
	public function delete($key){
		if(isset($this->values[$key])) unset($this->values[$key]);
		return true;
	}#end function



	/*
	 * Добавление параметров
	 */
	public function add($values=null){

		#Установка опций
		if(!is_array($values)) return false;
		$type = (isset($values['__type__'])) ? $values['__type__'] : 'vars';
		if(isset($values['__type__'])) unset($values['__type__']);

		switch($type){

			case 'vars':
				$this->values = array_merge($this->values, $values);
			break;

			case 'defines':
				foreach($values as $key=>$value){
					if($key != '__type__'){
						if(!defined($key))define($key, $value);
						$this->values[$key] = $value;
					}
				}
			break;

		}

		return true;
	}#end function


}#end class


?>
