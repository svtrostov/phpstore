<?php
/*==================================================================================================
Title	: Page template class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Template{

	use Trait_SingletonArray;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $template 		= '';		#Контент шаблона
	private $content 		= '';		#Готовый результат обработки
	private $variables		= array();	#Массив переменных для подстановки в шаблон
	private $internal_init	= false;	#признак инициализации внутренних переменных





	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	*/
	public function init($connection=''){
		if(defined('APP_CRON')) $this->internal_init = true;

	}#end function





	/*==============================================================================================
	Получение шаблона
	==============================================================================================*/


	/*
	 * Очистка внутренних переменных
	 */
	public function clear(){
		$this->template = '';
		$this->variables= array();
		$this->content = '';
	}#end function




	/*
	 * Устанавливает текущий шаблон из переменной
	 */
	public function setTemplateContent($content=''){
		
		$this->template = $content;

		return true;
	}#end function
	
	


	/*
	 * Устанавливает текущий шаблон из файла
	 */
	public function setTemplate($file=null, $as_include=true){

		if(empty($file)) return false;
		if($as_include) $as_include = (substr(strrchr($file, '.'), 1) == 'php' ? true : false);
		
		$this->setTemplateContent((empty($as_include) ? $this->loadContent($file) : $this->loadInclude($file)));
	
		return true;
	}#end function




	/*
	 * Получает темплейт через file_get_contents
	 */
	private function loadContent($file=null){

		if(empty($file)) return false;
		$file = DIR_MODULES.'/'.ltrim($file, " .\r\n\t\\/");
		if(!file_exists($file)||!is_readable($file))return false;

		return file_get_contents($file);
	}#end function




	/*
	 * Получает темплейт через include
	 */
	private function loadInclude($file=null){

		if(empty($file)) return false;
		$file = DIR_MODULES.'/'.ltrim($file, " .\r\n\t\\/");
		if(!file_exists($file)||!is_readable($file))return false;
		ob_start();
			include($file);
			$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}#end function






	/*==============================================================================================
	Переменные и макроподстановки
	==============================================================================================*/

	/*
	 * Добавление макроподстановки
	 */
	public function assign($key=null, $value=null, $type=T_VARIABLE){

		if(empty($key)) return false;
		
		switch($type){
			
			#Переменная
			case T_VARIABLE:
				if(is_array($key)){
					foreach($key as $k=>$v) $this->variables[$k] = $v;
				}else{
					$this->variables[$key] = $value;
				}
			break;
			
			
			
			#Неизвестный тип
			default:
				return false;
		}
		


		return true;
	}#end function





	/*==============================================================================================
	Обработка шаблона
	==============================================================================================*/


	/*
	 * Парсинг HTML темплейта и замена макроподстановок на соответствующие значения
	 */
	public function parseTemplate(){

		if(!$this->internal_init){
			$this->variables['REQUEST_MODULE'] 	= addslashes(Request::_get('module'));
			$this->variables['SESSION_COOKIE'] 	= strtoupper(Config::getOption('general','session_name','SMARTFISH'));
			$this->variables['REQUEST_INFO']	= json_encode(Request::_get('all'));
			$this->variables['TIMESTAMP'] 		= time();
			$this->internal_init = true;
		}

		$count = 0;
		$level = 0;
		$template = $this->template;
		
		
		#Запуск цикла замены макроподстановок их значениями
		#Будем заменять до последней макроподстановки
		#В цикле сделано потому как вложеннойсть макроподстановок неизвестна
		#Но не более 10 раз
		do{
			$template = preg_replace_callback('/\{\%([a-zA-Z0-9_\:\-\,\/\s]+)\%\}/s', array($this,'parseTemplateCallback'), $template, -1, $count);
			$level++;
		}while($count>0&&$level<10);
		
		
		//$template = preg_replace_callback('/\{\%([a-zA-Z0-9_\:\-\,\/\s]+)\%\}/s', array($this,'parseTemplateCallback'), $template, -1, $count);
		
		#Результат
		return $template;
	}




	/*
	 * Разбор темплейта и замена макроподстановок
	 */
	private function parseTemplateCallback($matches){

		#Вычленение имени переменной/функции/файла
		$key = trim($matches[1]);
		
		if(strlen($key)==0) return '';

		$result='';

		if(isset($this->variables[$key])){
			if(is_array($this->variables[$key])){
				foreach($this->variables[$key] as $v) $result.=$v;
			}else{
				$result = $this->variables[$key];
			}
		}else{
			$data = explode('::',$key);
			if(count($data) == 2){
				switch(strtoupper(trim($data[0]))){
					case 'LANG':
						list($file, $term) = explode(',', $data[1]);
						$result = Language::get(trim($file), trim($term));
					break;

					case 'REQUEST':
						$result = Request::_get($data[1],'');
					break;

					case 'GET':
						list($key, $encoded) = explode(',', $data[1]);
						$value = isset($_GET[$key]) ? $_GET[$key] : (isset($_POST[$key]) ? $_POST[$key] : '');
						$result = ($encoded=='1'? rawurlencode($value) : $value);
					break;

					case 'USER':
						$result = User::_get($data[1],'');
					break;

					case 'FN':
						$arr = explode(',', $data[1]);
						$result = call_user_func_array(array_shift($arr), $arr);
					break;
				}
				if(!empty($result)) $this->variables[$key] = $result;
			}
		}
		
		return $result;
	}#end function



	/*
	 * Вывод на экран
	 */
	public function display($return=false){
		
		$this->content = $this->parseTemplate();
		if($return) return $this->content;
		
		#Если AJAX запрос
		if(Request::_get('ajax', false)){
			Ajax::_setContent($this->content);
		}else{
			echo $this->content;
		}
		
		return true;
	}#end function


}#end class


?>
