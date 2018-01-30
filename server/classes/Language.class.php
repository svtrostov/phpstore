<?php
/*==================================================================================================
Title	: Language class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');
if(!defined('APP_LANGUAGE')) define('APP_LANGUAGE', 'ru');



class Language{

	use Trait_SingletonUnique, Trait_Array;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#Массив языковых переменных
	protected $lang = array();
	protected $user = null;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		$this->user = User::getInstance();
	}#end function




	/*==============================================================================================
	Функции
	==============================================================================================*/



	/*
	 * Алиас для getInstance
	 */
	public static function get($page, $key, $values=null, $lang=null){
		return Language::getInstance()->getLang($page, $key, $values, $lang);
	}#end function



	/*
	 * Поиск и загрузка файла
	 */
	protected function loadLang($page='', $lang=APP_LANGUAGE){

		if(!empty($this->lang[$lang])) return true;

		$filename = realpath(DIR_LANGUAGES.'/'.$lang.'/'.trim($page, " .\r\n\t\\/").'.'.$lang.'.php');
		if(!is_file($filename)||!is_readable($filename)) return false;

		#Получение конфигурации
		try{
			$result = include($filename);
		}catch(Exception $e){
			return false;
		}

		$this->lang[$page][$lang] = $result;

		return true;
	}#end function



	/*
	 * Вернуть значение параметра
	 */
	public function getLang($page='', $key='', $values=null, $lang=null){
		if(!$lang) $lang = $this->user->getLanguage();
		if(!isset($this->lang[$page][$lang])) $this->loadLang($page, $lang);
		if(!is_array($key)){
			$defkey = $key;
			$key = (strpbrk($key,'/')===false ? $key : explode('/',trim($key,'/')));
		}else{
			$defkey = implode('/',$key);
		}
		$default = '-[UNDEFINED: '.$defkey.' IN '.$page.']-';
		if(is_array($key)){
			$text = $this->arrayGetValue($this->lang[$page][$lang], $key, false);
		}else{
			$text = (isset($this->lang[$page][$lang][$key]) ? $this->lang[$page][$lang][$key] : false);
		}
		if($text !== false) return (!empty($values) ? vsprintf($text, $values) : $text);

		return $default;
	}#end function



}#end class


?>
