<?php
/*==================================================================================================
Title	: Modules config
Author	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

/*
 * Настройки модулей
 * Здесь указывается информация по модулям приложения а также
 * список файлов модуля, которые необходимо загрузить автоматически в момент старта
 */
return array(

	#Основной модуль платформы
	#Должен быть всегда активен
	'Main' => array(

		#Признак активности модуля
		'active' => true,

		#Функция или метод класса, который должен вызываться для обработки запросов клиентов
		#в текущем модуле, может быть задано в виде строки (имя функции) или массива (класс::метод)
		#файл с функцией контроллера должен быть подключен через автоматически подключаемые файлы
		#Если контроллер не задан, будет сгенерирована ошибка 404
		#В функцию будут передан массив со следующими предопределенными аргументами:
		#array(
		#	'is_ajax'		=> [true, false] //Признак, что запрос по AJAX
		#	'is_auth_user'	=> [true, false] //Признак, что клиент аутентифицирован
		#	'module'		=> [имя запрошенного модуля]
		#);
		'controller' => 'mainController',

		#Автоматически подключаемые файлы настроек (выполняется в первую очередь)
		#Все настройки хранятся в папке DIR_CONFIG = [ROOT]/server/config
		#Указанное имя настройки должно соответствовать физическому расположению файла настройки относительно DIR_CONFIG
		#Имя настройки указывается без суффикса, например, указывая "Core/System/settings" будет подключена
		#настройка [ROOT]/server/config/Core/System/settings.config.php
		#Подключение происходит последовательно от первого до последнего
		'config' => array(
			'defines',
			'general',
			'database'
		),

		#Автоматически подключаемые функции модуля (выполняется во вторую очередь)
		#Все функции хранятся в папке DIR_FUNCTIONS = [ROOT]/server/functions
		#Указанное имя должно соответствовать физическому расположению файла относительно DIR_FUNCTIONS
		#Указывая "clients" будет подключен файл [ROOT]/server/functions/clients.functions.php
		#Подключение происходит последовательно от первого до последнего
		'functions' => array(
			'mainController'
		),

		#Автоматически подключаемые файлы классов (выполняется в третью очередь)
		#Предполагается, что в данной секции будут указаны файлы классов, для загрузки во время инициализации
		#В списке указываются полные пути и имена файлов скриптов, окносительно DIR_CLASSES
		#Указывая "/Core/Loader" будет подключен файл [ROOT]/server/classes/Core/Loader.class.php
		#Подключение происходит последовательно от первого до последнего
		'classes' => array(
		),

		#Автоматически подключаемые скрипты модуля (выполняется в четвертую очередь)
		#Предполагается, что в данной секции будут указаны исполняемые при старте приложения скрипты
		#В списке указываются полные пути и имена файлов скриптов, окносительно DIR_MODULES
		#Указывая "Core/Loader.php" будет подключен файл [ROOT]/server/modules/Core/Loader.php
		#Подключение происходит последовательно от первого до последнего
		'scripts' => array(
		),

		#Автоматически запускаемые функции, после загрузки всех вышеуказанных скриптов.
		#Все нижеуказанные функции будут выполнены с помощью call_user_func_array()
		#Каждый элемент массива в execute в свою очередь представляет собой массив с двумя параметрами:
		#названием вызываемой функции и массивом передаваемых в функцию аргументов
		'call' => array(
		)
	
	),

	#Модуль администрирования
	'Admin' => array(

		#Признак активности модуля
		'active' => true,
		'controller' => 'adminController',
		'config' => array(
		),
		'functions' => array(
			'adminController'
		),
		'classes' => array(
		),
		'scripts' => array(
		),
		'call' => array(
		)
	
	),

	#Магазин
	'Shop' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),
	#Магазин
	'Catalog' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),
	#Магазин
	'Product' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),

	#Заказы
	'Order' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),

	#Пользователи
	'Users' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),

	#Прайс
	'Price' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),


	#Прочие страницы
	'Page' => array(
		'active' => true,
		'controller' => 'mainController',
		'config' => array(),
		'functions' => array(),
		'classes' => array(),
		'scripts' => array(),
		'call' => array()
	),



	#Тип данных: 
	#vars - переменные, 
	#defines - константы, декларируются через define(), все переменные массива должны быть скалярными
	'__type__' => 'vars'

);#end $OPTIONS

?>
