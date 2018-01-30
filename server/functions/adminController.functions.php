<?php
/*==================================================================================================
Описание: Контроллер страниц модуля Admin
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



/*
 * Контроллер модуля ADMIN
 */
function adminController($data){

	$user		= $data['user'];
	$client		= Client::getInstance();
	$db			= Database::getInstance('main');
	$template	= $data['template'];
	$request	= $data['request'];
	$ajax		= Ajax::getInstance();
	$is_ajax	= $request->get('ajax', false);
	$is_custom	= $request->getBool('custom', false);
	$is_post	= ($request->get('method', 'GET') == 'POST' ? true : false);
	$route_way = $request->get('way', false);
	$page = $request->get('page', false);
	$action = $request->get('action', false);
	$module = $request->get('module', false);
	$is_auth_user = $user->checkAuthStatus();
	$is_login_page = ($page == 'login' ? true : false);


	#Неизвестно что запрошено - 404
	if(empty($route_way)) return Page::_httpError(404, '#adminarea');

	#Проверка доступа к админке по IP адресу
	$allowed = false;
	$remote_addr = (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '');
	$admin_ips = Config::getOption('general','admin_ips',array());
	foreach($admin_ips as $ip){
		if(strpos($remote_addr, $ip) !== false){
			$allowed = true;
			break;
		}
	}
	if(!$allowed){
		$session_id = Session::_getSessionID();
		$db->addRecord('request_log',array(
			'error_code'	=> 403,
			'client_id'		=> Client::_getClientId(),
			'session_id'	=> (!empty($session_id) ? $session_id : ''),
			'referer'		=> (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
			'url'			=> (!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
			'ip_addr'		=> $remote_addr,
			'user_agent'	=> (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''),
			'is_post'		=> ($is_post ? 1 : 0),
			'query'			=> ($is_post ? getRequestString($_POST) : getRequestString($_GET))
		));
		return Page::_httpError(404);
	}

	#Если пользователь не аутентифицирован
	if(!$is_auth_user){

		$result = array();

		#Запрошена аутентификация через LOGIN
		if($is_post && ($action == 'login' || $is_login_page)){

			#Аутентификация без сертификата
			$key = trim($request->getStr('key', '','p'));
			$username = trim($request->getStr('u'.$key, false,'p'));
			$password = $request->getStr('p'.$key,false,'p');
			$remember = $request->getInt('r'.$key, 0,'p');
			$result = $user->auth($username, $password, $remember);

			#Аутентификация прошла успешно
			if($result['result']!==false){
				$to_page = (isset($_POST['page']) ? trim(rawurldecode($_POST['page'])) : '');
				if(empty($to_page) || strlen($to_page)<3 || !preg_match('/^[a-zA-Z0-9\=\?\%\/\-\_\@\!\&\.\,\:\\\;\{\}\[\]\#]+$/',$to_page)) $to_page = '/admin/index';
				if($is_ajax){
					$ajax->commit();
				}
				return Page::_doLocation($to_page);
			}else{
				#Через AJAX
				if($is_ajax){
					$ajax->addRequired('/client/css/ui-login.css');
					$ajax->responseError('Ошибка аутентификации',(isset($result['desc']) ? $result['desc'] : 'Сервис временно недоступен'));
					return true;
				}
			}

		}#Запрошена аутентификация через LOGIN

		#Страница LOGIN
		if($is_login_page){
			$template->assign(array(
				'error'	=> isset($result['desc']) ? '<div class="login_error"><h3>'.$result['desc'].'</h3></div>' : ''
			));
			$template->setTemplate('Admin/templates/login.tpl');
			$template->display();
			return true;
		}
		
		/*
		#Обработка запросов на просмотр страниц не аутентифицированным пользователям
		#может быть размещена здесь
		switch($page){
		}
		*/


		#Прочие страницы
		return Page::_doLocation('/admin/login'.(strlen($_SERVER['REQUEST_URI']) <3?'':'?page='.rawurlencode($_SERVER['REQUEST_URI'])));
	}#Если пользователь не аутентифицирован


	#Запрошен произвольный контент
	if($is_custom || $route_way[0]=='customcontent'){

		switch($route_way[0]){
			#Прочий произвольный контент
			case 'customcontent':
				if(empty($route_way[1])) return true;
			break;
		}

		return true;
	}#Запрошен произвольный контент


	#Если запрос не по AJAX - возвращаем контент index.tpl
	#и далее через AJAX делается запрос интересуемой страницы
	if(!$is_ajax){
		if($route_way[0] == 'logout') return Page::_doLogout('/');
		$request->request['get']['init']=1;
		$template->setTemplate('Admin/templates/index.php');
		$template->display();
		return true;
	}

	$template_name = null;
	$language_name = null;

	#Если происходит инициализация приложения
	if($request->getInt('init',0) == 1){

		#Подключение оcновного словаря
		$js_lang_array['general']=Language::get('general','js');

		ac_getAdminTopMenu();
		$ajax->setStack('adminmenu',$user->getUserMenu(2));

	}#происходит инициализация приложения


	#Обработка AJAX запроса
	switch($route_way[0]){


		#Main страница
		case 'index':
		case 'main':
			$ajax->addRequired('/client/css/ui-admin-main.css');
			$ajax->addRequired('/client/js/Admin/admin_main.js','admin_main');
			$ajax->addRequired('/client/css/ui-jstable.css');
			$template_name = 'main.php';
			Ajax::_setData(array(
				'order_statuses'	=> Shop::_orderStatuses(),
				'orders'=> Shop::_ordersSearch(array('limit'=>10)),
				'stats' => ac_statistics(),
				'num2str' => num2str(1234567890)
			));
		break;

		#Logout страница
		case 'login':
			return Page::_doLocation('/admin/index');
		break;#Logout страница


		#Logout страница
		case 'logout':
			return Page::_doLogout('/');
		break;#Logout страница



		#AJAX операции
		case 'ajax':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				case 'admin':
				case 'clients':
				case 'catalog':
				case 'order':
				case 'users':
				case 'property':
				case 'source':
					require_once(DIR_MODULES.'/Admin/ajax/'.$route_way[1].'.ajax.php');
				break;

				default: return Page::_httpError(404,'#adminarea');
			}
		break;#AJAX операции


		#Новости
		case 'news':
			$template_name = 'news.php';
			$language_name = 'news';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-admin-news.css');
			$ajax->addRequired('/client/js/Admin/admin_news.js','admin_news');
			$ajax->setData(array(
				'news' => $db->select('SELECT `news_id`,`timestamp`,`date`,`enabled`,`theme` FROM `news`')
			));
		break; #Новости


		#Доставка
		case 'deliveries':
			$template_name = 'deliveries.php';
			$language_name = 'deliveries';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-deliveries.css');
			$ajax->addRequired('/client/js/Admin/admin_deliveries.js','admin_deliveries');
			$ajax->setData(array(
				'deliveries' => $db->select('SELECT * FROM `deliveries`')
			));
		break; #Доставка


		#Курсы валют
		case 'currencies':
			$template_name = 'currencies.php';
			$language_name = 'currencies';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-currencies.css');
			$ajax->addRequired('/client/js/Admin/admin_currencies.js','admin_currencies');
			$curl = new Curl();
			$curl->init(array(
					"url"		=> "http://www.cbr.ru/scripts/XML_daily.asp",
					"host"		=> "www.cbr.ru",
					"header"	=> "",
					"method"	=> "GET",
					"referer"	=> "http://cbr.ru",
					"cookie"	=> "",
					"timeout"	=> 5,
					'cookie_jar'=> ''
				));
				$data = $curl->exec();
			$ajax->setData(array(
				'currencies'	=> $db->select('SELECT * FROM `currencies`'),
				'cbr'			=> simplexml_load_string(@$data['body'])//@json_decode(@json_encode(@simplexml_load_string(@$data['body'])),true)
			));
		break; #Курсы валют


		#Скидки
		case 'discounts':
			$template_name = 'discounts.php';
			$language_name = 'discounts';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-discounts.css');
			$ajax->addRequired('/client/js/Admin/admin_discounts.js','admin_discounts');
			$ajax->setData(array(
				'discounts' => $db->select('SELECT * FROM `discounts`')
			));
		break; #Скидки


		#Настройки
		case 'settings':
			$template_name = 'settings.php';
			$language_name = 'settings';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/js/Admin/admin_settings.js','admin_settings');
			$ajax->setData(array(
				'settings' => $db->select('SELECT * FROM `config`')
			));
		break; #Настройки


		#Склады
		case 'warehouses':
			$template_name = 'warehouses.php';
			$language_name = 'warehouses';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-warehouses.css');
			$ajax->addRequired('/client/js/Admin/admin_warehouses.js','admin_warehouses');
			$ajax->setData(array(
				'warehouses' => $db->select('SELECT * FROM `warehouses`')
			));
		break; #Склады


		#Реквизиты и счета
		case 'accounts':
			$template_name = 'accounts.php';
			$language_name = 'accounts';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-accounts.css');
			$ajax->addRequired('/client/js/Admin/admin_accounts.js','admin_accounts');
			$ajax->setData(array(
				'accounts' => $db->select('SELECT * FROM `accounts`')
			));
		break; #Реквизиты и счета


		#Каталог товаров
		case 'catalog':
			$template_name = 'catalog.php';
			$language_name = 'catalog';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-catalog.css');
			$ajax->addRequired('/client/js/Admin/admin_catalog.js','admin_catalog');
			$ajax->setData(array(
				'categories'	=> Shop::_categoryTree(0,true),
				'currencies'	=> $db->select('SELECT * FROM `currencies`'),
				'properties_tree' => Shop::_getPropertiesTree()
			));
		break;


		#Каталог товаров
		case 'citilink':
			$template_name = 'citilink.php';
			$language_name = 'citilink';
			$ajax->addRequired('/client/js/Admin/admin_citilink.js','admin_citilink');
			$price_info = null;
			$citilink = Config::getOption('sources','citilink',false);
			$price_file = (!empty($citilink['file']) ? $citilink['file'] : '');
			if(!empty($price_file)&&file_exists($price_file)){
				$price_info = array(
					'time'	=> date('Y-m-d H:i:s',filectime($price_file))
				);
			}
			$ajax->setData(array(
				'price_info' => $price_info
			));
		break;

		#Каталог товаров
		case 'cet':
			$template_name = 'cet.php';
			$language_name = 'cet';
			$ajax->addRequired('/client/js/Admin/admin_cet.js','admin_cet');
			$price_info = null;
			$citilink = Config::getOption('sources','cet',false);
			$price_file = (!empty($citilink['file']) ? $citilink['file'] : '');
			if(!empty($price_file)&&file_exists($price_file)){
				$price_info = array(
					'time'	=> date('Y-m-d H:i:s',filectime($price_file))
				);
			}
			$ajax->setData(array(
				'price_info' => $price_info
			));
		break;

		#phpinfo
		case 'phpinfo':
			phpinfo();
		break;


		#test
		case 'seo':
			$products = $db->select('SELECT `category_id`,`name` FROM `categories` WHERE `seo`=""');
			foreach($products as $p){
				$translit = rus2translit($p['name']);
				$db->update('UPDATE `categories` SET `seo`="'.addslashes($translit).'" WHERE `category_id`='.$p['category_id'].' LIMIT 1');
			}
			echo 'SEO Complete';
		break;


		#Администраторы
		case 'users':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Список
				case 'list':
					$template_name = 'users_list.php';
					$language_name = 'admin_users_list';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_users_list.js','admin_users_list');
					$ajax->setData(array(
						'users'	=> $db->select('SELECT `user_id`, `enabled`, `username`, `name` FROM `users`')
					));
				break;

				case 'info':
					$template_name = 'user_info.php';
					$language_name = 'admin_user_info';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_user_info.js','admin_user_info');
					$user_id = $request->getId('user_id',0);
					$ajax->setData(array(
						'user_info'			=> $db->selectRecord('SELECT *,"" as `password` FROM `users` WHERE `user_id`='.$user_id),
						'access_list'		=> $user->getAccessList(false),
						'user_access'		=> $db->select('SELECT * FROM `user_access` WHERE `user_id`='.$user_id.' AND `level`>0'),
						'login_log'			=>  $db->select('SELECT * FROM `user_authlog` WHERE `user_id`='.$user_id.' ORDER BY `session_uid` DESC LIMIT 50'),
					));
				break;


				case 'add':
					$template_name = 'user_add.php';
					$language_name = 'admin_user_add';
					$ajax->addRequired('/client/js/Admin/admin_user_add.js','admin_user_add');
					$ajax->setData(array(
						'access_list'		=> $user->getAccessList(false)
					));
				break;


				case 'protocol':
					if(!$user->checkAccess('can_protocol_view')) return Page::_httpError(404,'#adminarea');
					
					$template_name = 'user_protocol.php';
					$language_name = 'user_protocol.php';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-admin-user-protocol.css');
					$ajax->addRequired('/client/js/Admin/admin_user_protocol.js','admin_user_protocol');
					$ajax->setData(array(
						'users'	=> $db->select('SELECT `user_id`, `name` FROM `users`')
					));
				break;

				default: return Page::_httpError(404,'#adminarea');

			}
		break;#Администраторы




		#Клиенты
		case 'clients':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){

				#Список
				case 'list':
					$template_name = 'clients_list.php';
					$language_name = 'admin_clients_list';
					$ajax->addRequired('/client/css/ui-admin-clients-list.css');
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_clients_list.js','admin_clients_list');
					$ajax->setData(array(
						'clients_search'	=> $client->searchClients(),
						'discounts'			=> $db->select('SELECT `discount_id`,`name` FROM `discounts`'),
						'managers'			=> $db->select('SELECT `user_id`,`name` FROM `users`')
					));
				break;

				#Информация о клиенте
				case 'info':
					$template_name = 'client_info.php';
					$language_name = 'admin_client_info';
					$ajax->addRequired('/client/css/ui-admin-client-info.css');
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_client_info.js','admin_client_info');
					$client_id = $request->getId('client_id',0);
					if(!empty($client_id)){
						$ajax->setData(array(
							'discounts'			=> $db->select('SELECT `discount_id`,`name` FROM `discounts`'),
							'client_info'		=> $client->clientRecord($client_id),
							'order_statuses'	=> Shop::_orderStatuses(),
							'orders'			=> Shop::_ordersSearch(array('client_id'=>$client_id)),
							'managers'			=> $db->select('SELECT `user_id`,`name` FROM `users`'),
							'client_tickets'	=> $db->select('SELECT * FROM `tickets` WHERE `client_id`='.$client_id.' ORDER BY `ticket_id`')
						));
					}
				break;


				#Информация о клиенте
				case 'new':
					$template_name = 'client_new.php';
					$language_name = 'admin_client_new';
					$ajax->addRequired('/client/css/ui-admin-client-new.css');
					$ajax->addRequired('/client/js/Admin/admin_client_new.js','admin_client_new');
					$ajax->setData(array(
						'discounts'			=> $db->select('SELECT `discount_id`,`name` FROM `discounts`'),
						'managers'			=> $db->select('SELECT `user_id`,`name` FROM `users`'),
					));
				break;


				default: return Page::_httpError(404, '#adminarea');
			}

		break;#Клиенты



		#Заказы
		case 'orders':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');
			switch($route_way[1]){
				#Список
				case 'list':
					$template_name = 'orders_list.php';
					$language_name = 'orders_list';
					$ajax->addRequired('/client/css/ui-admin-orders-list.css');
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_orders_list.js','admin_orders_list');
					$shop = Shop::getInstance();
					$ajax->setData(array(
						'order_statuses'	=> $shop->orderStatuses(),
						'orders_search'		=> $shop->ordersSearch(array(
							'status'	=> 'all',
							'delivery'	=> 'all',
							'manager_id'=> 'self',
							'limit'		=> 50
						)),
						'deliveries'	=> $db->select('SELECT `delivery_id`,CONCAT_WS(", ",`name`,`price`) as `name` FROM `deliveries`'),
						'managers'		=> $db->select('SELECT `user_id`,`name` FROM `users`')
					));
				break;

				#Информация о заказе
				case 'info':
					$template_name = 'order_info.php';
					$language_name = 'order_info';
					$ajax->addRequired('/client/css/ui-admin-order-info.css');
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_order_info.js','admin_order_info');
					$order_id = $request->getId('order_id',0);
					$order_info = null;
					if($order_id > 0) $order_info = $db->selectRecord('SELECT * FROM `orders` WHERE `order_id`='.$order_id.' LIMIT 1');
					if(empty($order_info)){
						$ajax->setData(null);
					}else{
						$shop = Shop::getInstance();
						$client_info = (empty($order_info['client_id']) ? null : $db->selectRecord('SELECT *,"" as `password` FROM `clients` WHERE `client_id`='.$order_info['client_id'].' LIMIT 1'));
						if(!empty($client_info)){
							$client_info['discount'] = (empty($client_info['discount_id']) ? null : $db->selectRecord('SELECT * FROM `discounts` WHERE `discount_id`='.$client_info['discount_id'].' LIMIT 1') );
						}
						$ajax->setData(array(
							'order_info'		=> $order_info,
							'order_products'	=> $shop->orderProducts($order_id),
							'order_statuses'	=> $shop->orderStatuses(),
							'order_paymethods'	=> $shop->orderPaymethods(),
							'deliveries'		=> $db->select('SELECT `delivery_id`,CONCAT_WS(", ",`name`,`price`) as `name` FROM `deliveries`'),
							'currencies'		=> $db->select('SELECT * FROM `currencies`'),
							'accounts'			=> $db->select('SELECT * FROM `accounts`'),
							'managers'			=> $db->select('SELECT `user_id`,`name` FROM `users`'),
							'client_info'		=> $client_info
						));
					}
				break;

				default: return Page::_httpError(404,'#adminarea');
			}

		break;#Заказы


		#Товары специального предложения
		case 'offers':
			$template_name = 'offers.php';
			$language_name = 'offers';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-admin-offers.css');
			$ajax->addRequired('/client/js/Admin/admin_offers.js','admin_offers');
			$ajax->setData(array(
				'offers'	=> Shop::_getOffersList()
			));
		break;


		#Тикеты
		case 'support':
		case 'tickets':
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-admin-tickets.css');
			$ajax->addRequired('/client/js/Admin/admin_tickets.js','admin_tickets');
			$template_name = 'tickets.php';
			$language_name = 'admin_tickets';
			$ajax->setData(array(
				'client_tickets'	=> $db->select('
					SELECT
						T.`ticket_id` as `ticket_id`,
						T.`client_id` as `client_id`,
						T.`subject` as `subject`,
						T.`message` as `message`,
						T.`timestamp` as `timestamp`,
						C.`name` as `name`,
						C.`username` as `username`,
						C.`company` as `company`,
						C.`email` as `email`,
						C.`phone` as `phone`
					FROM `tickets` as T 
					INNER JOIN `clients` as C ON C.`client_id`=T.`client_id`
					WHERE T.`enabled`>0 AND T.`is_support`=0
				')
			));
		break;#Тикеты


		#Товары
		case 'products':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');

			switch($route_way[1]){

				#Добавить товар
				case 'add':
					$template_name = 'product_add.php';
					$language_name = 'product_add';
					$ajax->addRequired('/client/css/ui-admin-product-add.css');
					$ajax->addRequired('/client/js/Admin/admin_product_add.js','admin_product_add');
					$ajax->setData(array(
						'categories'	=> Shop::_categoryTree(0,true),
						'currencies'	=> $db->select('SELECT * FROM `currencies`')
					));
				break;

				#Информация о товаре
				case 'search':
					$template_name = 'products_search.php';
					$language_name = 'products_search';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-admin-products-search.css');
					$ajax->addRequired('/client/js/Admin/admin_products_search.js','admin_products_search');
					$search_term = trim($request->getSrt('term',0));
					$ajax->setData(array(
						'categories'	=> Shop::_categoryTree(0,true),
						'category_list'	=> Shop::_categoryList(0,' / ', false),
						'search_term'	=> (empty($search_term)?null:$search_term),
						'currencies'	=> $db->select('SELECT * FROM `currencies`')
					));
				break;

				#Информация о товаре
				case 'info':
					$template_name = 'product_info.php';
					$language_name = 'product_info';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-admin-product-info.css');
					$ajax->addRequired('/client/js/Admin/admin_product_info.js','admin_product_info');
					$ajax->setData(array(
						'product_id'	=> $request->getId('product_id',0),
						'categories'	=> Shop::_categoryTree(0,true),
						'currencies'	=> $db->select('SELECT * FROM `currencies`'),
						'properties_tree' => Shop::_getPropertiesTree()
					));
				break;

				default: return Page::_httpError(404,'#adminarea');

			}#Switch

		break; #Товары


		#Объединение товаров
		case 'bridge':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');

			switch($route_way[1]){

				#Список объединений
				case 'list':
					$template_name = 'bridge_list.php';
					$language_name = 'bridge_list';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-admin-bridge-list.css');
					$ajax->addRequired('/client/js/Admin/admin_bridge_list.js','admin_bridge_list');
					$search_term = trim($request->getSrt('term',0));
					$ajax->setData(array(
						'bridges'	=> Shop::_getBridgeList()
					));
				break;

				#Информация о товаре
				case 'info':
					$template_name = 'bridge_info.php';
					$language_name = 'bridge_info';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/js/Admin/admin_bridge_info.js','admin_bridge_info');
					$bridge_id = $request->getId('bridge_id',0);
					$ajax->setData(array(
						'bridge_info'	=> ($bridge_id > 0 ? Shop::_getBridgeInfo($bridge_id) : null)
					));
				break;

				default: return Page::_httpError(404,'#adminarea');

			}#Switch

		break; #Объединение товаров


		#Свойства товаров
		case 'properties':
			$template_name = 'properties.php';
			$language_name = 'properties';
			$ajax->addRequired('/client/css/ui-jstable.css');
			$ajax->addRequired('/client/css/ui-properties.css');
			$ajax->addRequired('/client/js/Admin/admin_properties.js','admin_properties');
			$ajax->setData(array(
				'properties_tree' => Shop::_getPropertiesTree(),
				'categories'	=> Shop::_categoryTree(0,true)
			));
		break; #Свойства товаров


		#Стикеры
		case 'stickies':
			$template_name = 'stickies.php';
			$language_name = 'stickies';
			$ajax->addRequired('/client/css/ui-stickies.css');
			$ajax->addRequired('/client/js/Admin/admin_stickies.js','admin_stickies');
			$ajax->setData(array(
				'stickies' => $db->select('SELECT S.*,IFNULL(U.`name`,S.`user_id`) as `author` FROM `stickies` as S LEFT JOIN `users` as U ON U.`user_id`=S.`user_id`')
			));
		break;


		#Журналы
		case 'log':
			if(empty($route_way[1])) return Page::_httpError(404,'#adminarea');

			switch($route_way[1]){

				#Журнал поисковых запросов
				case 'search':
					$template_name = 'search_log.php';
					$language_name = 'search_log';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-log.css');
					$ajax->addRequired('/client/js/Admin/admin_search_log.js','admin_search_log');
				break;

				#Журнал запросов
				case 'request':
					$template_name = 'request_log.php';
					$language_name = 'request_log';
					$ajax->addRequired('/client/css/ui-jstable.css');
					$ajax->addRequired('/client/css/ui-log.css');
					$ajax->addRequired('/client/js/Admin/admin_request_log.js','admin_request_log');
				break;

				default: return Page::_httpError(404,'#adminarea');

			}#Switch

		break; #Журналы


		#О программе
		case 'about':
			$template_name = 'about.php';
			$language_name = 'about';
			$ajax->addRequired('/client/css/ui-admin-about.css');
			$ajax->addRequired('/client/js/Admin/admin_about.js','admin_about');
		break; #О программе

		default:
			return Page::_httpError(404,'#adminarea');
	}


	#Если задан шаблон - возвращаем его
	if(!empty($template_name)){
		$template->setTemplate('Admin/templates/'.$template_name);
		$ajax->addContent('#adminarea',$template->display(true),'set');
	}

	if(!empty($language_name)){
		$js_lang_array[$language_name]=Language::get($language_name,'js');
	}

	if(!empty($js_lang_array)){
		Ajax::_setStack('lang',$js_lang_array);
	}

	Ajax::_commit();
	return true;
}#end function




/*
 * Построение основного меню для пользователя
 */
function ac_getAdminTopMenu(){

	$menu=array();
	$menu[] = array('id'=> 1, 'name'=> 'Главная', 'link'=>'/admin/index', 'class'=> 'icon_home', 'section' => 0);
	$menu[] = array('id'=> 2, 'name'=> 'Заметки', 'link'=>'/admin/stickies', 'class'=> 'icon_square', 'section' => 0);
	$menu[] = array('id'=> 3, 'name'=> 'Клиенты', 'link'=>'/admin/clients/list', 'class'=> 'icon_users', 'section' => 0);
	$menu[] = array('id'=> 31, 'name'=> 'Новый клиент', 'link'=>'/admin/clients/new', 'class'=> '', 'section' => 3);
	$menu[] = array('id'=> 32, 'name'=> 'Список клиентов', 'link'=>'/admin/clients/list', 'class'=> '', 'section' => 3);
	$menu[] = array('id'=> 33, 'name'=> 'Новые сообщения', 'link'=>'/admin/tickets', 'class'=> '', 'section' => 3);

	$menu[] = array('id'=> 4, 'name'=> 'Заказы', 'link'=>'/admin/orders/list', 'class'=> 'icon_list', 'section' => 0);
	$menu[] = array('id'=> 6, 'name'=> 'Каталог', 'link'=>'/admin/catalog', 'class'=> 'icon_folder', 'section' => 0);
	$menu[] = array('id'=> 7, 'name'=> 'Поиск товаров', 'link'=>'/admin/products/search', 'class'=> 'icon_search', 'section' => 0);
	$menu[] = array('id'=> 8, 'name'=> 'Настройки', 'link'=>'#', 'class'=> 'icon_settings', 'section' => 0);
	$menu[] = array('id'=> 81, 'name'=> 'Администраторы', 'link'=>'/admin/users/list', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 82, 'name'=> 'Валюты', 'link'=>'/admin/currencies', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 83, 'name'=> 'Доставка', 'link'=>'/admin/deliveries', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 84, 'name'=> 'Настройки', 'link'=>'/admin/settings', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 85, 'name'=> 'Новости', 'link'=>'/admin/news', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 86, 'name'=> 'Протокол действий', 'link'=>'/admin/users/protocol', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 87, 'name'=> 'Реквизиты', 'link'=>'/admin/accounts', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 88, 'name'=> 'Скидки', 'link'=>'/admin/discounts', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 89, 'name'=> 'Склады', 'link'=>'/admin/warehouses', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 810, 'name'=> 'Характеристики', 'link'=>'/admin/properties', 'class'=> '', 'section' => 8);
	$menu[] = array('id'=> 811, 'name'=> 'Спецпредложения', 'link'=>'/admin/offers', 'class'=> '', 'section' => 8);

	$menu[] = array('id'=> 9, 'name'=> 'Выход', 'link'=>'/logout', 'class'=> 'icon_logout', 'section' => 0);

	Ajax::_setStack('menu',$menu);
}#end function




/*
 * Ошибка доступа
 */
function ac_checkPageAccess($object_name, &$template_name){
	return true;
}#end function



/*
 * Статистика системы заявок
 */
function ac_statistics(){

	$db =  Database::getInstance('main');
	return array(
		//'Publishers'			=> $db->result('SELECT count(*) FROM `clients`'),
		//'Users'					=> $db->result('SELECT count(*) FROM `users` WHERE `is_deleted`=0'),
		//'Advertising domains'	=> $db->result('SELECT count(*) FROM `domains` WHERE `is_deleted`=0')
	);

}#end function

?>
