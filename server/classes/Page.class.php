<?php
/*==================================================================================================
Title	: Page controller class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Page{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	private $user 			= null;
	private $request 		= null;
	private $is_ajax 		= false;
	private $is_custom 		= false;
	private $module_name 	= null;
	private $template		= null;
	private $is_auth_user	= false;



	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	*/
	protected function init(){

		ob_start();

		$this->user			= User::getInstance();
		
		#Если это стандартный или AJAX запрос
		$this->template		= Template::getInstance('main');
		$this->request		= Request::getInstance();
		$this->module_name	= $this->request->get('module', null);
		$this->is_ajax		= $this->request->get('ajax', false);
		$this->is_custom	= $this->request->getBool('custom', false);

	}#end function



	/*
	 * Завершение работы класса
	 */
	public function __destruct(){

		Response::_sendHeaders();
		$content = ob_get_contents();
		ob_end_clean();

		if($this->is_ajax && !$this->is_custom){
			if(APP_DEBUG && strlen($content)>0) Ajax::_setDebug($content);
			echo Ajax::_getResponseData();
		}else{
			echo $content;
		}
	}#end function








	/*==============================================================================================
	Построение ответа
	==============================================================================================*/




	/*
	 * Конструктор страницы
	 */
	public function build(){

		#Модуль не задан - выход
		if(empty($this->module_name)) return $this->httpError(404);

		#Получение информации о модуле
		$module = Config::getOption('modules', $this->module_name, false);
		if(empty($module)||!is_array($module)||empty($module['active'])||empty($module['controller'])) return $this->httpError(404);
		if(!is_callable($module['controller'])) return $this->httpError(503);

		#Заголовки - общие
		if(!$this->is_custom){
			if($this->is_ajax){
				Response::_add('Expires: Mon, 26 Jul 1997 05:00:00 GMT'); 
				Response::_add('Cache-Control: no-store, no-cache, must-revalidate'); 
				Response::_add('Pragma: no-cache');
				header('Content-Type: application/json; charset=utf-8');
			}else{
				header('Content-Type: text/html; charset=utf-8');
			}
		}

		#Вызов функции контроллера модуля для обработки запроса
		call_user_func($module['controller'], array(
			'module'	=> $this->module_name,
			'request' 	=> $this->request,
			'template'	=> $this->template,
			'user'		=> $this->user
		));

		return true;
	}#end function




	/*
	 * Выход, завершение сеанса
	 */
	public function doLogout($location='/'){

		Session::_stop();
		Client::_deleteAuthCookie();
		$this->doLocation($location);

		return true;
	}#end function




	/*
	 * Ошибка
	 */
	public function httpError($errno=404, $parent='#mainarea'){

		$errstr = Response::_getStatus($errno);
		
		if($this->is_ajax){
			Ajax::_addRequired('/client/css/ui-error.css');
			Ajax::_addContent($parent,'<h1 class="errorpage_title">'.$errno.': '.$errstr.'</h1>','set');
			Ajax::_commit();
		}else{
			Response::_status($errno);
			$this->template->setTemplate(Config::getOption('general',array('templates','errors',$errno),'Main/templates/_http_error.tpl'));
			$this->template->assign('errno', $errno);
			$this->template->assign('errstr', $errstr);
			$this->template->display();
		}

		return false;
	}#end function



	/*
	 * Редирект
	 */
	public function doLocation($location='/'){

		if($this->is_ajax){
			Ajax::_setLocation($location);
		}else{
			Response::_location($location);
		}

		return true;
	}#end function


}#end class


?>
