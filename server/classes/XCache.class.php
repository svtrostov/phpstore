<?php
/*==================================================================================================
Title	: XCache (http://xcache.lighttpd.net/) class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class XCache{

	use Trait_SingletonUnique;



	/*==============================================================================================
	Переменные класса
	==============================================================================================*/


	private $active = false;
	private $f_mutex = array();





	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	private function init(){

		#Следует ли использовать XCache
		$this->active = (extension_loaded('XCache') && function_exists('xcache_get') && function_exists('xcache_set')) ? true : false;

	}#end function



	/*
	 * Запись данных в недоступные свойства
	 */
	public function __set($name, $value){
		if(!$this->active) return false;
		xcache_set($name, $value, 0);
	}#end function



	/*
	 * Чтение данных из недоступных свойств
	 */
	public function __get($name){
		if(!$this->active) return false;
		return xcache_get($name);
	}#end function



	/*
	 * будет выполнен при использовании isset() или empty() на недоступных свойствах.
	 */
	public function __isset($name){
		if(!$this->active) return false;
		return xcache_isset($name);
	}#end function



	/*
	 * будет выполнен при вызове unset() на недоступном свойстве
	 */
	public function __unset($name){
		if(!$this->active) return false;
		xcache_unset($name);
	}#end function



	/*
	 * будет выполнен при уничтожении класса
	 */
	public function __destruct(){
		foreach($this->f_mutex as $name=>$mutex){
			if($this->mutexOff($name)) fclose($mutex);
		}
	}#end function




	/*==============================================================================================
	Функции MUTEX
	==============================================================================================*/

	/*
	 * Блокировка выполнения кода
	 * 
	 * $name - имя блокировки
	 */
	public function mutexOn($name=null){
		if(empty($name))return false;
		if(!is_resource($this->f_mutex[$name])){
			$this->f_mutex[$name] = fopen(DIR_TEMP.'/mutex.'.$name.'.lock', "w");
		}
		flock($this->f_mutex[$name], LOCK_EX);
		return true;
	}#end function



	/*
	 * Снятие блокировки
	 * 
	 * $name - имя блокировки
	 */
	public function mutexOff($name=null){
		if(empty($this->f_mutex[$name]))return false;
		if(is_resource($this->f_mutex[$name])){
			flock($this->f_mutex[$name], LOCK_UN);
		}
		return true;
	}#end function



	/*==============================================================================================
	Функции XCAHCE
	==============================================================================================*/


	public function isEnabled(){
		return $this->active;
	}#end function


	/*
	 * Запись в переменную XCache данных
	 * 
	 * $key - имя переменной
	 * $value - записываемое значение
	 * $ttl - время жизни переменной в кеше, в секундах (0 - бесконечно)
	 */
	public function set($key, $value, $ttl=0, $ts=false){
		if(!$this->active) return false;
		return xcache_set($key, $value, $ttl);
	}#end function



	public function get($key, $ts=false){
		if(!$this->active) return false;
		return xcache_get($key);
	}#end function



	public function delete($key){
		if(!$this->active) return false;
		if(!$ts) return xcache_unset($key);
	}#end function



	public function exists($key){
		if(!$this->active) return false;
		return xcache_isset($key);
	}#end function


	/*
	 * Загрузка контента из кеша или из файла
	 */
	public function getFileContent($file=''){

		if(empty($file)) return false;
		if(!$this->active) return (!is_file($file)) ? false : file_get_contents($file);

		if(xcache_isset($file)){
			if(xcache_isset($file.'/actual')&&xcache_get($file.'/actual')==true) return xcache_get($file);
		}

		if(!is_file($file)||!is_readable($file)) return false;

		$data = file_get_contents($file);
		xcache_set($file, $data);
		xcache_set($file.'/actual', true);

		return $data;
	}#end function


}#end class

?>
