<?php
/*==================================================================================================
Title	: Loader functions
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


/*
 * Инициализация
 */
function loader_init(){


	#Инициализация функции автоматического подключения классов
	spl_autoload_register('loader_autoloadClassHandler');

	#Режим обработки ошибок
	if(APP_DEBUG){
		#Режим обработки ошибок
		ini_set('display_errors', 'On');
		error_reporting(E_ALL);
	}else{
		ini_set('display_errors', 'Off');
		error_reporting(0);
	}

	#Языковый настройки интерфейса по-умолчанию
	if(!defined('APP_LANGUAGE')) define('APP_LANGUAGE', strtolower(Config::getOption('general','default_language','en')));

	#Тема интерфейса по-умолчанию
	if(!defined('APP_THEME')) define('APP_THEME', strtolower(Config::getOption('general','default_theme','default')));

	#Загрузка конфигураций
	if(loader_loadConfig()===false) return false;

	#Инициализация объектов работы с базами данных
	if(loader_initDatabases()===false) return false;

	if(!defined('APP_CRON')){
		Session::getInstance(array('autostart'=>true));
	}

	return true;
}#end function




/*
 * Автоподключение классов
 */
function loader_autoloadClassHandler($class_name){
	$class_name = strtr($class_name,'_','/');
	$class_file = DIR_CLASSES.'/'.$class_name.'.class.php';
	if(!file_exists($class_file)){
		debugError(array(
			'id'		=> 'ECLASS001',
			'desc'		=> 'Class file not found',
			'data'		=> $class_file,
			'return'	=> false,
			'file'		=> __FILE__,
			'line'		=> __LINE__,
			'class'		=> __CLASS__,
			'function'	=> __METHOD__
		));
		exit;
	}
	
	require($class_file);
	return true;
}#end function




/*
 * Загрузка конфигураций
 */
function loader_loadConfig(){

	#Загрузка конфигураций модулей
	#Подключение файла /server/config/modules.config.php
	$modules = Config::getOptions('modules',false);
	if(empty($modules)) return debugError(array(
		'id'		=> 'ELOAD001',
		'desc'		=> 'Settings of modules not found in config/modules.config.php',
		'data'		=> $modules,
		'return'	=> false,
		'file'		=> __FILE__,
		'line'		=> __LINE__,
		'class'		=> __CLASS__,
		'function'	=> __METHOD__
	));

	$aload = array(
		'functions' => array(DIR_FUNCTIONS, '.functions.php'), #2. Подключение функций
		'classes' => array(DIR_CLASSES, '.class.php'), #3. Подключение классов
		'scripts' => array(DIR_MODULES, '') #4. Подключение скриптов
	);

	#Обработка модулей и подключение файлов
	foreach($modules as $name=>$module){

		#Если не массив - пропускаем
		if(!is_array($module)) continue;

		#Если в настройках модуля явно не установлено, что модуль активен - пропускаем
		if(empty($module['active'])) continue;

		#1. Подключение конфигураций
		if(isset($module['config'])&&is_array($module['config'])){
			foreach($module['config'] as $incl) Config::getConfig($incl);
		}

		#2,3,4: Подключение функций, классов, скриптов
		foreach($aload as $k=>$v){
			if(isset($module[$k])&&is_array($module[$k])){
				foreach($module[$k] as $incl){
					$filename = $v[0].'/'.ltrim($incl, " .\r\n\t\\/").$v[1];
					if(is_file($filename)&&is_readable($filename)) require($filename);
				}
			}
		}

		#5: Вызов функций
		if(isset($module['call'])&&is_array($module['call'])){
			foreach($module['call'] as $funct){
				if(empty($funct)) continue;
				if(!is_array($funct)){
					$fname = $funct;
					$fargs = array();
				}else{
					$fname = $funct[0];
					$fargs = (isset($funct[1]) ? (is_array($funct[1])?$funct[1]:array($funct[1])) : array());
				}
				call_user_func_array($fname, $fargs);
			}
		}

	}#Обработка модулей и подключение файлов


	return true;
}#end function




/*
 * инициализация объектов работы с базами данных
 */
function loader_initDatabases(){

	#Инициализация класса работы с базой данных
	$dbs = Config::getOptions('databases',false);
	if(empty($dbs)) return true;

	foreach($dbs as $name=>$options){

		if(empty($options)||!is_array($options)||empty($options['host'])||empty($options['username'])||empty($options['database'])){
			return debugError(array(
				'id'		=> 'ELOAD003',
				'desc'		=> 'Incorrect settings for '.$name.' connection in config/databases.config.php',
				'data'		=> array('name'=>$name,'options'=>$options),
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		$GLOBALS['DB_'.strtoupper($name)] = Database::getInstance($name, $options);
	}

}#end function





?>
