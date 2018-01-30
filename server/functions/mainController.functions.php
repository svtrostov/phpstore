<?php
/*==================================================================================================
Title	: Page controller function
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



/*
 * Контроллер модуля MAIN
 */
function mainController($data){

	//return Page::_httpError(999);

	$user = $data['user'];
	$client = Client::getInstance();
	$db		= Database::getInstance('main');
	$template = $data['template'];
	$request = $data['request'];
	$ajax = Ajax::getInstance();
	$is_ajax = $request->get('ajax', false);
	$is_custom = $request->getBool('custom', false);
	$is_post = ($request->get('method', 'GET') == 'POST' ? true : false);
	$is_auth_client = $client->checkAuthStatus();
	$route_way = $request->get('way', false);
	$page = $request->get('page', false);
	$action = $request->get('action', false);
	$module = $request->get('module', false);
	$is_login_page = ($page == 'login' ? true : false);

	$js_lang_array = array();

	$session_id = Session::_getSessionID();
	$db->addRecord('request_log',array(
		'client_id'		=> $client->getClientId(),
		'session_id'	=> (!empty($session_id) ? $session_id : ''),
		'referer'		=> (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
		'url'			=> (!empty($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : ''),
		'ip_addr'		=> (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : ''),
		'user_agent'	=> (!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : ''),
		'is_post'		=> ($is_post ? 1 : 0),
		'query'			=> ($is_post ? getRequestString($_POST) : getRequestString($_GET))
	));

	$result = array();

	#Запрошена аутентификация через LOGIN
	if($is_post && $action == 'login'){

		#Аутентификация без сертификата
		$username = trim($request->getStr('login', false,'p'));
		$password = $request->getStr('password',false,'p');
		$remember = 1;
		$result = $client->auth($username, $password, $remember);

		#Аутентификация прошла успешно
		if($result['result']===false){
			$template->assign(array(
				'login_error'	=> (isset($result['desc']) ? $result['desc'] : '')
			));
		}

	}#Запрошена аутентификация через LOGIN


	#Неизвестно что запрошено - 404
	if(empty($route_way)) return Page::_httpError(404);


	#Запрошен произвольный контент
	if($is_custom || $route_way[0]=='customcontent'){


		return true;
	}#Запрошен произвольный контент

	$template_name = null;


	//Модули
	switch($module){

		#Прочие страницы
		case 'Page':
			switch($route_way[0]){
				case 'contacts':
				case 'contacts.html':
					$template->setTemplate('Main/templates/contacts.tpl');
					$template->display();
					return true;
				break;

				#доставка
				case 'delivery':
				case 'delivery.html':
					$template->setTemplate('Main/templates/delivery.tpl');
					$template->display();
					return true;
				break;


			}

			return Page::_httpError(404);
		break; //Page


		#Прайс лист
		case 'Price':
			$GLOBALS['PRICE_CID'] = 0;
			if(!empty($route_way[0])&&$route_way[0]!='all'){
				$GLOBALS['PRICE_CID'] = intval($route_way[0]);
			}
			$template->setTemplate('Main/templates/price.tpl');
			$template->display();
			return true;
		break; //Price

		case 'Catalog':
			$GLOBALS['SEO_LINKS'] = true;
			if(!empty($route_way[0])&&!empty($route_way[1])){
				$GLOBALS['CID'] = Shop::_categoryExists(intval($route_way[0]),true,true);
				if(!empty($GLOBALS['CID'])){
					$GLOBALS['CID_PAGE'] = (empty($route_way[2]) ? 1 : abs(intval($route_way[2])));
					$orderby = $request->getEnum('fsort',array('nameasc','namedesc','priceasc','pricedesc'),'');
					if(!empty($orderby)){
						Session::_set('orderby', $orderby);
					}else{
						$orderby = Session::_get('orderby', $orderby);
						if(empty($orderby)) $orderby = 'nameasc';
					}
					$GLOBALS['CID_ORDERBY'] = $orderby;
					mc_CID_Filters($GLOBALS['CID']['category_id']);
					$template->assign('TITLE', $GLOBALS['CID']['name']);
					$template->setTemplate('Main/templates/catalog.tpl');
					$template->display();
					return true;
				}
			}

			$GLOBALS['CID'] = array(
				'name'	=> 'Каталог товаров',
				'desc'	=> 'Каталог товаров',
				'parent_id'	=> 0,
				'enabled'	=> 1,
				'category_id'	=> 0,
				'hide_filters'	=> 1,
				'records'		=> 0
			);
			$GLOBALS['CID_FILTERS']=array();
			$template->assign('TITLE', 'Каталог товаров');
			$template->setTemplate('Main/templates/catalog.tpl');
			$template->display();
			return true;

		break;

		case 'Product':
			if(empty($route_way[0])) return Page::_httpError(404);
			$GLOBALS['UID'] = Shop::_productExists(intval($route_way[0]),true);
			if(empty($GLOBALS['UID'])) return Page::_httpError(404);
			$GLOBALS['SEO_LINKS'] = true;
			$template->assign('TITLE', $GLOBALS['UID']['name']);
			$template->setTemplate('Main/templates/product.tpl');
			$template->display();
			return true;
		break;


		#Магазин
		case 'Shop':
			$GLOBALS['SEO_LINKS'] = false;

			if(strncasecmp('CID_',$route_way[0], 4)===0){
				$matches=array();
				if(preg_match('/^CID\_(\d+)[\_]?(\d*)\.html*$/', $route_way[0], $matches)){
					$GLOBALS['CID'] = Shop::_categoryExists(intval($matches[1]),true,true);
					$GLOBALS['CID_PAGE'] = (empty($matches[2]) ? 1 : abs(intval($matches[2])));
					$orderby = $request->getEnum('fsort',array('nameasc','namedesc','priceasc','pricedesc'),'');
					if(!empty($orderby)){
						Session::_set('orderby', $orderby);
					}else{
						$orderby = Session::_get('orderby', $orderby);
						if(empty($orderby)) $orderby = 'nameasc';
					}
					$GLOBALS['CID_ORDERBY'] = $orderby;
					if(!empty($GLOBALS['CID'])){
						mc_CID_Filters($GLOBALS['CID']['category_id']);
						$template->assign('TITLE', $GLOBALS['CID']['name']);
						$template->setTemplate('Main/templates/catalog.tpl');
						$template->display();
						return true;
					}
				}
			}else
			if(strncasecmp('UID_',$route_way[0],4)===0){
				if(preg_match('/^UID\_(\d+)\.html*$/', $route_way[0], $matches)){
					$GLOBALS['UID'] = Shop::_productExists(intval($matches[1]),true);
					if(!empty($GLOBALS['UID'])){
						$template->assign('TITLE', $GLOBALS['UID']['name']);
						$template->setTemplate('Main/templates/product.tpl');
						$template->display();
						return true;
					}
				}
			}

			$GLOBALS['CID'] = array(
				'name'	=> 'Каталог товаров',
				'desc'	=> 'Каталог товаров',
				'parent_id'	=> 0,
				'enabled'	=> 1,
				'category_id'	=> 0,
				'hide_filters'	=> 1,
				'records'		=> 0
			);
			$GLOBALS['CID_FILTERS']=array();
			$template->assign('TITLE', 'Каталог товаров');
			$template->setTemplate('Main/templates/catalog.tpl');
			$template->display();
			return true;


			return Page::_httpError(404);
		break; #Магазин


		#Заказ
		case 'Order':
			switch($route_way[0]){

				#Корзина
				case 'cart':
					if($is_post){
						switch($action){
							//Очистка корзины
							case 'clean':
								Session::_set('cart', null);
								return Page::_doLocation('/order/cart');
							break;

							//Оформление заказа
							case 'order':
								return mc_doOrder();
							break;
						}

						$product_id = $request->getId('product_id', 0);
						$cart = Session::_get('cart');
						if(!empty($product_id)&&!empty($cart)&&is_array($cart)&&array_key_exists('p'.$product_id,$cart)){
							switch($action){
								case 'delete':
									unset($cart['p'.$product_id]);
									Session::_set('cart', $cart);
								break;
								case 'update':
									$new_count = $request->getId('new_count',1);
									if(empty($new_count)) unset($cart['p'.$product_id]);
									else{
										$cart['p'.$product_id]['count'] = $new_count;
									}
									Session::_set('cart', $cart);
								break;
							}
						}
						return Page::_doLocation('/order/cart');
					}
					$template->setTemplate('Main/templates/order.tpl');
					$template->display();
					return true;
				break;

				//Заказ успешно оформлен
				case 'complete':
					$order = Session::_get('lastorder');
					if(empty($order)||!is_array($order)) return Page::_doLocation('/');
					$GLOBALS['LASTORDER'] = $order;
					$template->setTemplate('Main/templates/order_complete.tpl');
					$template->display();
					return true;
				break;

				//Информация о заказе
				case 'info':
					$template->setTemplate('Main/templates/order_info.tpl');
					$template->display();
					return true;
				break;


				//Документы
				case 'documents':
				if(empty($route_way[1])) return Page::_httpError(404);
					switch($route_way[1]){
						case 'bill':
							mc_orderDocsBill($template);
							return true;
						break;
						case 'invoice':
							mc_orderDocsInvoice($template);
							return true;
						break;
						case 'check':
							mc_orderDocsCheck($template);
							return true;
						break;
						case 'torg12':
							mc_orderDocsTorg12($template);
							return true;
						break;
					}
				break; //Документы



			}//switch $route_way[0]

		break; //Order

		#Пользователи
		case 'Users':
			$client_id = $client->getClientID();
			if(!$client_id) return Page::_doLocation('/');

			switch($route_way[0]){

				//Личная информация
				case 'account':
					$template->setTemplate('Main/templates/users_account.tpl');
					$template->display();
					return true;
				break;

				//Заказы
				case 'orders':
					$template->setTemplate('Main/templates/users_orders.tpl');
					$template->display();
					return true;
				break;

				//Переписка с менеджером (тикеты)
				case 'messages':
					//POST запрос - добавление сообщения
					if($is_post){
						$subject = htmlspecialchars(trim($request->getStr('subject', '')));
						$message = htmlspecialchars(trim($request->getStr('message', '')));
						if(!empty($subject)&&!empty($message)){
							$db = Database::getInstance('main');
							$db->addRecord('tickets',array(
								'client_id'		=> $client_id,
								'subject'		=> $subject,
								'message'		=> $message
							));
						}
						return Page::_doLocation('/users/messages');
					}

					$template->setTemplate('Main/templates/users_messages.tpl');
					$template->display();
					return true;
				break;

			}//switch $route_way[0]

		break; //Users


	}//Модули



	#Обработка AJAX запроса
	switch($route_way[0]){

		#Main страница
		case '':
		case 'index':
		case 'index.php':
		case 'main':
		case 'main.php':
			$template->setTemplate('Main/templates/index.tpl');
			$template->display();
			return true;
		break;

		#Logout страница
		case 'logout':
			return Page::_doLogout('/index.php');
		break;

		#Главная страница каталога
		case 'shop':
			$GLOBALS['CID'] = array(
				'name'	=> 'Каталог товаров',
				'desc'	=> 'Каталог товаров',
				'parent_id'	=> 0,
				'enabled'	=> 1,
				'category_id'	=> 0,
				'hide_filters'	=> 1,
				'records'		=> 0
			);
			$GLOBALS['CID_FILTERS']=array();
			$template->assign('TITLE', 'Каталог товаров');
			$template->setTemplate('Main/templates/catalog.tpl');
			$template->display();
			return true;
		break;

		#Карта сайта
		case 'map':
			$template->setTemplate('Main/templates/map.tpl');
			$template->display();
			return true;
		break;


		#Новости
		case 'news':
			$template->setTemplate('Main/templates/news.tpl');
			$template->display();
			return true;
		break;


		#AJAX операции
		case 'ajax':
			if(empty($route_way[1]) || !$is_ajax) return Page::_httpError(404);

			switch($route_way[1]){

				case 'cart':
				case 'user':
					require_once(DIR_MODULES.'/Main/ajax/'.$route_way[1].'.ajax.php');
				break;

				default: return Page::_httpError(404);
			}

		break;

		#Поиск по сайту
		case 'search':
			//$term = str_replace(array('\\','+','*','?','[',']','^','(',')','{','}','=','!','<','>','|',':','-'), ' ',$request->getStr('words', ''));
			$GLOBALS['SEARCH_TERM'] = str_replace('  ',' ',trim(strtr(htmlspecialchars($request->getStr('words', '')),'-',' ')));
			$GLOBALS['SEARCH_CID'] = $request->getId('cid', 0);
			$GLOBALS['SEARCH_SET'] = $request->getId('set', 0);
			$GLOBALS['SEARCH_CF'] = $request->getEnum('cf', array('noempty','all'),'all');
			$GLOBALS['SEARCH_US'] = $request->getBoolAsInt('us', 0);
			$GLOBALS['SEARCH_SA'] = $request->getBoolAsInt('sa', 0);
			$GLOBALS['SEARCH_SN'] = $request->getBoolAsInt('sn', 1);
			$GLOBALS['SEARCH_SD'] = $request->getBoolAsInt('sd', 0);
			$GLOBALS['SEARCH_SP'] = $request->getBoolAsInt('sp', 0);

			$template->setTemplate('Main/templates/search.tpl');
			$template->display();
			return true;
		break;


		#Регистрация нового клиента
		case 'registration':
			$client_id = $client->getClientID();
			if($client_id) return Page::_doLocation('/');
			if($is_post){
				if($action=='registration'){
				$db = Database::getInstance('main');
				$client_username	= trim(htmlspecialchars($request->getStr('client_login', '')));
				$client_password	= trim($request->getStr('client_password', ''));
				$client_email	= htmlspecialchars($request->getEmail('client_email', ''));
				$client_name	= htmlspecialchars($request->getStr('client_name', ''));
				$client_company	= htmlspecialchars($request->getStr('client_company', ''));
				$client_inn		= htmlspecialchars($request->getStr('client_inn', ''));
				$client_kpp		= htmlspecialchars($request->getStr('client_kpp', ''));
				$client_phone	= htmlspecialchars($request->getStr('client_phone', ''));
				$client_address	= htmlspecialchars($request->getStr('client_address', ''));
				$client_country	= htmlspecialchars($request->getStr('client_country', ''));
				$client_city	= htmlspecialchars($request->getStr('client_city', ''));
				$client_zip		= htmlspecialchars($request->getStr('client_zip', ''));
				if(empty($client_email))	return Page::_doLocation('/registration?e=email');
				if(empty($client_name))		return Page::_doLocation('/registration?e=name');
				if(empty($client_phone))	return Page::_doLocation('/registration?e=phone');
				if(empty($client_address))	return Page::_doLocation('/registration?e=address');
				if(empty($client_city))		return Page::_doLocation('/registration?e=city');
				if(strlen($client_username)<5)	return Page::_doLocation('/registration?e=login');
				if(strlen($client_password)<5)	return Page::_doLocation('/registration?e=password');
				if(in_array($client_username,array('root','admin','support','test'))) return Page::_doLocation('/registration?e=exists');
				$db->prepare('SELECT count(*) FROM `clients` WHERE `username` LIKE ? LIMIT 1');
				$db->bind($client_username);
				if($db->result() > 0) return Page::_doLocation('/registration?e=exists');
				$db->prepare('SELECT count(*) FROM `clients` WHERE `email` LIKE ? LIMIT 1');
				$db->bind($client_email);
				if($db->result() > 0) return Page::_doLocation('/registration?e=emailexists');
				$client_id = Client::_clientNew(array(
					'username'		=> $client_username,
					'password'		=> sha1($client_password),
					'company'		=> $client_company,		#[char, 255] Имя организации клиента
					'name'			=> $client_name,		#[char, 255] Имя клиента
					'email'			=> $client_email,		#[char, 128] Контактный email, указанный при регистрации
					'phone'			=> $client_phone,		#[char, 32] Номер телефона
					'address'		=> $client_address,		#[char, 255] Почтовый адрес
					'city'			=> $client_city,		#[char, 64] Город
					'country'		=> $client_country,		#[char, 64] Страна
					'zip'			=> $client_zip,			#[char, 16] Почтовый индекс
					'inn'			=> $client_inn,			#[char, 32] ИНН
					'kpp'			=> $client_kpp,			#[char, 32] КПП
					'create_ip_addr'=> (empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'])
				));
				if(!$client_id) return Page::_doLocation('/registration?e=internal');
				Client::_auth($client_username, $client_password, 1);

				$mail_tmpl = Template::getInstance('clientmail');
				$mail_tmpl->setTemplate('Main/templates/mail/client_registration.html');
				$mail_tmpl->assign('client_id', $client_id);
				$mail_tmpl->assign('client_username', $client_username);
				$mail_tmpl->assign('client_password', $client_password);
				$mail_tmpl->assign('client_name', $client_name);
				$mail_tmpl->assign('client_email', $client_email);
				$mail_tmpl->assign('client_phone', $client_phone);
				$mail_content = $mail_tmpl->parseTemplate();
				$header = "MIME-Version: 1.0\r\n";
				$header.= "From: DTBox.ru <info@dtbox.ru>\r\n";
				$header.= "Reply-To: info@dtbox.ru\r\n";
				$header.= "Content-Type: text/html; charset=utf-8\r\n";
				$header.= "Content-Transfer-Encoding: 8bit\r\n";
				mail($client_email, 'Регистрация на dtbox.ru', $mail_content, $header);
				return Page::_doLocation('/registrationcomplete');
				}
			}
			$template->setTemplate('Main/templates/registration.tpl');
			$template->display();
			return true;
		break;


		#Регистрация успешно завершена
		case 'registrationcomplete':
			$template->setTemplate('Main/templates/registrationcomplete.tpl');
			$template->display();
			return true;
		break;


		#Отправка забытого пароля
		case 'sendpassword':
			$client_id = $client->getClientID();
			if($client_id) return Page::_doLocation('/');
			if($is_post){
				if($action=='restore'){
					$db = Database::getInstance('main');
					$client_email	= htmlspecialchars($request->getEmail('client_email', ''));
					if(empty($client_email)) return Page::_doLocation('/sendpassword?e=email');
					$db->prepare('SELECT * FROM `clients` WHERE `email` LIKE ? LIMIT 1');
					$db->bind($client_email);
					$restore = $db->selectRecord();
					if(empty($restore)) return Page::_doLocation('/sendpassword?e=exists');
					$client_password = getUniqueID(10,4);
					$link_addr = 'http://'.$_SERVER["SERVER_NAME"].'/restorepassword/?key='.rawurlencode(encrypt(array($restore['client_id'], $restore['username'],time(),$client_password)));
					$mail_tmpl = Template::getInstance('clientmail');
					$mail_tmpl->setTemplate('Main/templates/mail/sendpassword.html');
					$mail_tmpl->assign('client_id', $restore['client_id']);
					$mail_tmpl->assign('client_username', $restore['username']);
					$mail_tmpl->assign('client_name', $restore['name']);
					$mail_tmpl->assign('client_email', $restore['email']);
					$mail_tmpl->assign('client_phone', $restore['phone']);
					$mail_tmpl->assign('client_ip', $request->getIP(true));
					$mail_tmpl->assign('link_addr', $link_addr);
					$mail_content = $mail_tmpl->parseTemplate();
					$header = "MIME-Version: 1.0\r\n";
					$header.= "From: DTBox.ru <info@dtbox.ru>\r\n";
					$header.= "Reply-To: info@dtbox.ru\r\n";
					$header.= "Content-Type: text/html; charset=utf-8\r\n";
					$header.= "Content-Transfer-Encoding: 8bit\r\n";
					mail($client_email, 'Восстановление пароля на dtbox.ru', $mail_content, $header);
					return Page::_doLocation('/sendpasswordcomplete');

				}
				return Page::_doLocation('/sendpassword');
			}

			$template->setTemplate('Main/templates/sendpassword.tpl');
			$template->display();
			return true;
		break;

		case 'sendpasswordcomplete':
			$template->setTemplate('Main/templates/sendpasswordcomplete.tpl');
			$template->display();
			return true;
		break;


		#Восстановление пароля
		case 'restorepassword':
			$data = decrypt(rawurldecode($request->getStr('key','')));
			if(empty($data)||!is_array($data)) return Page::_doLocation('/?e=key');
			$db = Database::getInstance('main');
			$db->prepare('SELECT * FROM `clients` WHERE `client_id` = ? AND `username` LIKE ? LIMIT 1');
			$db->bind($data[0]);
			$db->bind($data[1]);
			$restore = $db->selectRecord();
			if(empty($restore)) return Page::_doLocation('/?e=restore');
			if(time()-$data[2] > 86400) return Page::_doLocation('/?e=time');
			if($db->update('UPDATE `clients` SET `password`="'.sha1($data[3]).'" WHERE `client_id`='.$data[0])===false) return Page::_doLocation('/?e=internal');
			$template->assign('client_id', $data[0]);
			$template->assign('client_login', $data[1]);
			$template->assign('client_password', $data[3]);
			$template->setTemplate('Main/templates/restorepassword.tpl');
			$template->display();
			return true;
		break;


		default:
			return Page::_httpError(404);
	}


	#Если задан шаблон - возвращаем его
	if(!empty($template_name)){
		$template->setTemplate('Main/templates/'.$template_name);
		$ajax->addContent('#mainarea',$template->display(true),'set');
	}

	$ajax->commit();

	return true;
}#end function







/*
 * Построение основного меню для пользователя
 */
function mc_getUserMainMenu(){
	//$seo_links = empty($GLOBALS['SEO_LINKS']) ? false : true;
	$seo_links = true;
	$out='';
	$db = Database::getInstance('main');
	$categories = $db->getTableName('categories');
	$top = $db->select('SELECT `category_id`,`name`,`seo` FROM `'.$categories.'` WHERE `parent_id`=0 AND `enabled`=1 ORDER BY `name`');
	foreach($top as $row){
		$sub = $db->select('SELECT `category_id`,`name`,`seo` FROM `'.$categories.'` WHERE `parent_id`='.$row['category_id'].' AND `enabled`=1 ORDER BY `name`');
		$columns = ceil(count($sub) / 15);
		if($columns<1)$columns = 1;
		if($columns>3)$columns = 3;
		$link = ($seo_links && !empty($row['seo']) ? '/catalog/'.$row['category_id'].'/'.$row['seo'].'/' : '/shop/CID_'.$row['category_id'].'.html');
		$out.='<li><a href="'.$link.'">'.$row['name'].'</a><ul class="columns'.$columns.'">';
		foreach($sub as $subrow){
			$link = ($seo_links && !empty($subrow['seo']) ? '/catalog/'.$subrow['category_id'].'/'.$subrow['seo'].'/' : '/shop/CID_'.$subrow['category_id'].'.html');
			$out.='<li><a href="'.$link.'">'.$subrow['name'].'</a></li>';
		}
		$out.='</ul></li>';
	}
	return $out;
}#end function


/*
 * Построение карусели товаров для главной страницы
 */
function mc_getUserStockgallery(){
	//$seo_links = empty($GLOBALS['SEO_LINKS']) ? false : true;
	$seo_links = true;
	$li='';
	$db = Database::getInstance('main');
	$products = $db->getTableName('products');
	$product_info = $db->getTableName('product_info');
	$categories = $db->getTableName('categories');
	$shop = Shop::getInstance();
	$top = $db->select(
	'SELECT 
		PP.`product_id` as `product_id`,
		PP.`category_id` as `category_id`,
		PP.`name` as `name`,
		PP.`seo` as `seo`,
		PP.`bridge_id` as `bridge_id`,
		PP.`currency` as `currency`,
		PP.`base_price` as `base_price`,
		(PP.`offer`*PP.`offer_discount`) as `offer_discount`,
		PP.`pic_big` as `pic_big`,
		PI.`description` as `description`
		FROM `'.$products.'` as PP 
		LEFT JOIN `'.$product_info.'` as PI ON PI.`product_id`=PP.`product_id`
		WHERE PP.`stockgallery`=1 AND PP.`enabled`>0'
	);
	$rarr=array();
	foreach($top as $row){
		$ok=true;
		$parent_id = $row['category_id'];
		do{
			$info = $db->selectRecord('SELECT `parent_id`,`enabled` FROM `'.$categories.'` WHERE `category_id`='.$parent_id.' LIMIT 1');
			if(empty($info)||empty($info['enabled'])) $ok = false;
			else  $parent_id = $info['parent_id'];
		}while($parent_id>0&&$ok==true);
		if($ok==true) $rarr[] = $row;
	}
	if(!empty($rarr)){
		$li='
		<script type="text/javascript" language="javascript" src="/client/js/Main/uSlider-1.1.js"></script>
		<script type="text/javascript" language="javascript">
		window.addEvent("domready", function(){
			var slider = $("slider");
			var uslider = new uSlider(slider, {
				directionnav: false,
				centercrop: false,
				effect: "slide"
			});
		});
		</script>
		<div id="slides_wrapper"><div class="wrapper"><div id="slider" class="uSlider"><ul class="uSlider-slides">';
		foreach ($rarr as $row){

			$bridge_info = ($row['bridge_id']>0 ? $shop->getBridgeInfo($row['bridge_id'], true) : null);
			if(!empty($bridge_info)){
				$price = $bridge_info['price'];
			}else{
				$price = $shop->getPrice($row['currency'],$row['base_price'], true, $row['offer_discount']);
			}

			$is_offer = $row['offer_discount'] > 0;

			$link = ($seo_links && !empty($row['seo']) ? '/product/'.$row['product_id'].'/'.$row['seo'].'/' : '/shop/UID_'.$row['product_id'].'.html');
			$li.='
			<li'.($is_offer?' class="offer"':'').'>
				<a class="info" href="'.$link.'">
					<div class="title'.($is_offer?' offer':'').'">'.$row['name'].'</div>
					<div class="desc">'.$row['description'].'</div>
					<div class="price"><small>Купить за</small><b>'.$price.'.00 руб</b></div>
				</a>
				<div class="pic"><img src="'.$row['pic_big'].'" class="load" /></div>
			</li>';
		}//foreach
		$li.='</ul></div></div></div>';
	}
	return $li;
}



/*
 * Выводит меню для клиента или окно входа, если клиент не аутентифицирован
 */
function mc_getUserAreaMenu(){
	$db = Database::getInstance('main');
	$client = Client::getInstance();
	$is_auth_client = $client->checkAuthStatus();
	$out='';
	//Клиент аутентифицирован
	if($is_auth_client){
		$out='<div class="siteMiddleDivContent">
		<div class="userMenu">
		<ul>
			<li><a href="/users/account">Персональные данные</a></li>
			<li><a href="/users/orders">Мои заказы</a></li>
			<li><a href="/users/messages">Написать менеджеру</a></li>
			<li><a href="/logout">Выход</a></li>
		</ul>
		</div>
		</div>';
	}
	//Клиент не аутентифицирован
	else{
		$out='<div class="siteMiddleDivContent"><br>
		<div class="loginBox">
			<div class="errors">{%login_error%}</div>
			<form method="post">
			<input type="hidden" name="action" value="login">
			<div class="loginArea">
				<ul>
				<li class="login"><input type="text" name="login" value="" class="input" placeholder="Введите Ваш логин..."/></li>
				<li class="password"><input type="password" name="password" value="" class="input" placeholder="Введите пароль..."/></li>
				<li class="button"><button type="submit" class="ui-button-light" role="button" aria-disabled="false"><span>Войти</span></button><a href="/sendpassword">Забыли пароль?</a></li>
			</div>
			</form>
			<div class="newuser">Не зарегистрированы?<br><a href="/registration">Нажмите сюда для регистрации</a></div>
		</div>
		</div>';
	}
	
	
	return $out;
}



/*
 * Вычисление фильтров для каталога и задание параметров фильтров
 */
function mc_CID_Filters($category_id=0){
	$category_id = intval($category_id);
	if(empty($category_id)) return;
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$filters = $db->select('SELECT P.`property_id` as `property_id`, P.`name` as `name`, P.`type` as `type`, P.`measure` as `measure` FROM `category_properties` as CP INNER JOIN `properties` as P ON P.`property_id`=CP.`property_id` WHERE CP.`category_id`='.$category_id);
	if(empty($filters)) $filters = array();
	array_unshift($filters,array(
		'property_id'	=> 'exists',
		'name'			=> 'Наличие',
		'type'			=> 'bool',
		'values'		=> array(
			array('value_id'=>'0', 'name'=>'Показывать все товары'),
			array('value_id'=>'1', 'name'=>'Товары только в наличии')
		)
	));
	array_unshift($filters,array(
		'property_id'	=> 'price',
		'name'			=> 'Цена товара, рублей',
		'type'			=> 'num'
	));
	array_unshift($filters,array(
		'property_id'	=> 'term',
		'name'			=> 'Название или описание товара содержит',
		'type'			=> 'str',
		'placeholder'	=> 'Название или описание содержит...'
	));

	foreach($filters as &$f){
		switch($f['type']){
			case 'list':
			case 'multilist':
				$f['selected']	= $request->getId('f'.$f['property_id'],0);
				$f['values']	= (!empty($f['values']) ? $f['values'] : $db->select('SELECT `value_id`,`name` FROM `property_values` WHERE `property_id`='.$f['property_id']));
			break;
			case 'bool':
				$f['selected']	= $request->getEnum('f'.$f['property_id'],array('all','0','1'),'all');
				$f['values']	= (!empty($f['values']) ? $f['values'] : array(
					array('value_id'=>'all', 'name'=>'-[Не задано]-'),
					array('value_id'=>'0', 'name'=>'Нет'),
					array('value_id'=>'1', 'name'=>'Да')
				));
			break;
			case 'num':
				$from = $request->getFloat('f'.$f['property_id'].'from',0);
				$to = $request->getFloat('f'.$f['property_id'].'to',0);
				$f['values'] = (!empty($f['values']) ? $f['values'] : array($from, $to));
			break;
			case 'str':
				$selected = str_replace('  ',' ',trim(strtr(htmlspecialchars($request->getStr('f'.$f['property_id'], '')),'-',' ')));
				$f['values'] = (!empty($f['values']) ? $f['values'] : $selected);
			break;
		}
	}//foreach $filters
	$GLOBALS['CID_FILTERS'] = $filters;
}



/*
 * Построение фильтров каталога для пользователя
 */
function mc_getCatalogFilters(){
	//if($_SERVER['REMOTE_ADDR']!='195.151.242.9') return '';
	if(empty($GLOBALS['CID_FILTERS'])||empty($GLOBALS['CID'])||!empty($GLOBALS['CID']['hide_filters'])) return '';
	$out = '<div class="darkBox"><div class="title">Фильтр товаров в каталоге</div></div><div class="miniform" id="filters" style="width:260px;">';
	foreach($GLOBALS['CID_FILTERS'] as $f){
		switch($f['type']){
			case 'list':
			case 'multilist':
				$values = $f['values'];
				if(!empty($values)){
					array_unshift($values,array('value_id'=>'0', 'name'=>'-[Не задано]-'));
					$out.='<fieldset><legend>'.$f['name'].(empty($f['measure'])?'':' ('.$f['measure'].')').'</legend><select style="width:100%;" id="f'.$f['property_id'].'">';
					foreach($values as $v){
						$out.='<option value="'.$v['value_id'].'"'.($f['selected'] == $v['value_id'] ? ' selected="true"':'').'>'.$v['name'].'</option>';
					}
					$out.='</select></fieldset>';
				}
			break;
			case 'bool':
				$out.='<fieldset><legend>'.$f['name'].(empty($f['measure'])?'':' ('.$f['measure'].')').'</legend><select style="width:100%;" id="f'.$f['property_id'].'">';
				foreach($f['values'] as $v){
					$out.='<option value="'.$v['value_id'].'"'.($f['selected'] == $v['value_id'] ? ' selected="true"':'').'>'.$v['name'].'</option>';
				}
				$out.='</select></fieldset>';
			break;
			case 'num':
				$out.='<fieldset><legend>'.$f['name'].(empty($f['measure'])?'':' ('.$f['measure'].')').'</legend><div style="padding-right:5px;text-align:center;">
				<input style="float:right; width:90px;text-align:center;" maxlength="15" type="text" value="'.$f['values'][1].'" id="f'.$f['property_id'].'to"/>
				<input style="float:left; width:90px;text-align:center;" maxlength="15" type="text" value="'.$f['values'][0].'" id="f'.$f['property_id'].'from"/>до</div></fieldset>';
			break;
			case 'str':
				$out.='<fieldset><legend>'.$f['name'].(empty($f['measure'])?'':' ('.$f['measure'].')').'</legend><div style="padding-right:5px;">
				<input type="text" id="fterm" value="'.$f['values'].'" style="width:100%;" placeholder="'.(empty($f['placeholder'])?'':$f['placeholder']).'"></div></fieldset>';
			break;
		}
	}//foreach $filters

	$out.='</div><button type="button" class="ui-button-light" role="button" aria-disabled="false" id="fapply"><span>Применить фильтр</span></button><button type="button" class="ui-button-light" role="button" aria-disabled="false" id="fclear"><span>Сбросить</span></button>';
	$out.='<script language="JavaScript">
		$("fapply").addEvent("click",function(){
			var filters = $("filters").getElements("select, input");
			var qs ={};
			for(var i=0; i<filters.length; i++){
				var el = filters[i];
				var key = el.get("id");
				if(key !="") qs[key] = el.getValue();
			}
			App.Location.setLocation(REQUEST_INFO["document"]+"?"+Object.toQueryString(qs));
		});
		$("fclear").addEvent("click",function(){
			App.Location.setLocation(REQUEST_INFO["document"]);
		});
	
	</script>';

	return $out;
}



/*
 * Построение содержимого каталога для пользователя
 */
function mc_getCatalogItems(){
	if(empty($GLOBALS['CID'])) return mc_get404();
	$seo_links = empty($GLOBALS['SEO_LINKS']) ? false : true;

	$per_page = 30;
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();

	$category = $GLOBALS['CID'];
	$out ='<div class="catalogName">'.$category['name'].'</div>';

	//Путь к каталогу
	$out.='<div class="breadcrumb"> <a href="/">Главная</a> <img src="/client/images/next.gif" alt="" width="16" height="16" border="0" align="absmiddle"> ';
	if(!empty($category['parents'])){
		foreach($category['parents'] as $parent){
			$link = ($seo_links && !empty($parent[2]) ? '/catalog/'.$parent[0].'/'.$parent[2].'/' : '/shop/CID_'.$parent[0].'.html');
			$out.='<a href="'.$link.'">'.$parent[1].'</a> <img src="/client/images/next.gif" alt="" width="16" height="16" border="0" align="absmiddle"> ';
		}
	}
	$out.=' <b>'.$category['name'].'</b></div>';


	//Дочерние подкаталоги
	$childs = Shop::_categoryChilds($category['category_id'], true);
	if(!empty($childs)){
		$out.='<div class="catalogList"><ul>';
		foreach($childs as $child){
			if(empty($child['pic_small'])){
				if(file_exists(DIR_ROOT.'/client/images/catalog/'.$child['category_id'].'.png')) $photo = '/client/images/catalog/'.$child['category_id'].'.png';
				else if(file_exists(DIR_ROOT.'/client/images/catalog/'.$child['category_id'].'.jpg')) $photo = '/client/images/catalog/'.$child['category_id'].'.jpg';
				else if(file_exists(DIR_ROOT.'/client/images/catalog/'.$child['category_id'].'.gif')) $photo = '/client/images/catalog/'.$child['category_id'].'.gif';
				else $photo = '/client/images/no_photo.gif';
			}else{
				$photo = $child['pic_small'];
			}
			$link = ($seo_links && !empty($child['seo']) ? '/catalog/'.$child['category_id'].'/'.$child['seo'].'/' : '/shop/CID_'.$child['category_id'].'.html');
			$out.='<li><a href="'.$link.'"><div class="img"><img src="'.$photo.'"/></div><div class="title">'.$child['name'].'</div></a></li>';
		}
		$out.='</ul></div>';
	}

	if(empty($category['category_id'])) return $out;


	$products = $shop->shopCategoryProducts($category['category_id'], $GLOBALS['CID_PAGE'], $per_page, $GLOBALS['CID_FILTERS'], $GLOBALS['CID_ORDERBY']);

	if(!empty($products['products'])||empty($childs)){
		if(empty($products['products'])){
			$out.='
			<div class="lightBox">
				<div class="fl_right layoutView">
					<a class="row" href="/price/'.$category['category_id'].'" title="Прайс-лист каталога '.$category['name'].'"></a>
				</div>
				<div class="title">'.$category['name'].'</div>
			</div>
			<br>';
		}else{
			$out.='
			<div class="lightBox">
				<div class="fl_right layoutView">
					<a class="row" href="/price/'.$category['category_id'].'" title="Прайс-лист каталога '.$category['name'].'"></a>
				</div>
				<div class="fl_right miniform">
					<select id="fsort" style="margin:6px 10px 0px 0px;">
						<optgroup label="Сортировка товаров">';
						$values = array(
							array('nameasc','По наименованию от А до Я'),
							array('namedesc','По наименованию от Я до А'),
							array('priceasc','По увеличению цены'),
							array('pricedesc','По уменьшению цены')
						);
					foreach($values as $v){
						$out.='<option value="'.$v[0].'"'.($GLOBALS['CID_ORDERBY'] == $v[0] ? ' selected="true"':'').'>'.$v[1].'</option>';
					}
			$out.='</optgroup>
					</select>
				</div>
				<div class="fl_right" style="margin:8px 10px 0px 0px;color:#666;">
				Найдено товаров: <b>'.$products['records'].'</b>
				</div>
				<div class="title">'.$category['name'].'</div>
			</div>
			<br>';
			$out.='<script language="JavaScript">
				$("fsort").addEvent("change",function(){
					REQUEST_INFO["get"]["fsort"]=$("fsort").getValue();
					App.Location.setLocation(REQUEST_INFO["document"]+"?"+Object.toQueryString(REQUEST_INFO["get"]));
				});
			</script>';
		}
	}


	if(!empty($products['products'])){

		if($products['page_max']>=2){
			if($seo_links && !empty($category['seo'])){
				$category_prefix = '/catalog/'.$category['category_id'].'/'.$category['seo'].'/';
				$category_suffix = '/';
			}else{
				$category_prefix = '/shop/CID_'.$category['category_id'].'_';
				$category_suffix = '.html';
			}
			$out.='<div class="productPageNav">';

			$p_count = min(20, $products['page_max']);	//Количество ссылок в навигации
			$pages = array($products['page']);
			$p_need = $p_count-1;
			$i_prev = $products['page'];
			$i_next = $products['page'];
			while($p_need>0){
				$i_prev--;
				$i_next++;
				if($i_prev > 0){
					$pages[]=$i_prev;
					$p_need--;
				}
				if($p_need > 0 && $i_next <= $products['page_max']){
					$pages[]=$i_next;
					$p_need--;
				}
			}
			sort($pages,SORT_NUMERIC);

			$navigat = ' | ';

			$qs = (empty($_SERVER['QUERY_STRING'])? '' : '?'.$_SERVER['QUERY_STRING']);

			foreach($pages as $i){
				if($i == $products['page']){
					$navigat.='<b>'.$i.'</b> | ';
				}else{
					$navigat.='<a href="'.($category_prefix.$i.$category_suffix.$qs).'">'.$i.'</a> | ';
				}
			}

			if($products['page'] > 1){
				$navigat = 
				'<a href="'.($category_prefix . '1'.$category_suffix.$qs).'"><img src="/client/images/dtbox/first.png" hspace="0" border="0" width="16" height="16"></a> &nbsp; ' .
				'<a href="'.($category_prefix . ($products['page']-1) . $category_suffix.$qs).'"><img src="/client/images/dtbox/prev.png" hspace="0" border="0" width="16" height="16"></a>' . $navigat;
			}

			if($products['page'] < $products['page_max']){
				$navigat.= '<a href="'.($category_prefix . ($products['page']+1) . $category_suffix.$qs).'"><img src="/client/images/dtbox/next.png" hspace="0" border="0" width="16" height="16"></a>'.
				' &nbsp; <a href="'.($category_prefix . $products['page_max'] . $category_suffix.$qs).'"><img src="/client/images/dtbox/last.png" hspace="0" border="0" width="16" height="16"></a>';
			}

			$out.=$navigat.'</div>';
		}//page_max >= 2


		//Построение плиток товаров
		$out.='<div class="products"><ul class="g2">';
		foreach($products['products'] as $p){

			if(empty($p['pic_big'])){
				$photo = '/client/images/no_photo.gif';
			}else{
				$photo = $p['pic_big'];
			}

			$bridge_info = ($p['bridge_id']>0 ? $shop->getBridgeInfo($p['bridge_id'], true) : null);
			if(!empty($bridge_info)){
				$price = $bridge_info['price'];
				$count = intval($bridge_info['count']);
			}else{
				$price = $shop->getPrice($p['currency'],$p['base_price'], true, $p['offer_discount']); //$p['price_rub'];//$shop->getPrice($p['currency'],$p['base_price'], true);
				$count = intval($p['count']);
			}

			$teko_cnt = $db->result('SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`='.$p['product_id'].' AND `warehouse_id` IN (5,3,7,9,10,11)');
			if($teko_cnt>0 && $teko_cnt == $count){
				$pcount = array(
					'class'	=> '0',
					'text'	=> 'Заказ 4-7 дней'
				);
			}else{
				$pcount = $shop->productCountInfo($count);
			}

			//$pcount = $shop->productCountInfo($count);

			$is_offer = $p['offer_discount']>0;

			if(empty($p['description'])) $p['description'] = 'Извините, описание данного товара временно отсутствует.';
			$link = ($seo_links && !empty($p['seo']) ? '/product/'.$p['product_id'].'/'.$p['seo'].'/' : '/shop/UID_'.$p['product_id'].'.html');
			$out.='<li>
				<div class="block">
					<a href="'.$link.'"><div class="pic">'.($is_offer?'<div class="offer"><img src="/client/images/offer2.png"></div>':'').'
						<img src="'.$photo.'" alt="'.$p['name'].'" title="'.$p['name'].'" border="0">
					</div>
					<div class="info">
						<div '.($is_offer ? 'class="title offer" title="СПЕЦИАЛЬНОЕ ПРЕДЛОЖЕНИЕ"': 'class="title"').'>'.$p['name'].'</div>
						<div class="desc">'.$p['description'].'</div>
						<div class="price">
							<div class="fl_right count">'.$pcount['text'].'</div>
							<div class="fl_right count">
								<img src="/client/images/pb'.$pcount['class'].'.png" alt="" border="0">
							</div>
							<strong>'.$price.'.00 руб</strong>
						</div>
					</div>
					</a>
					<div class="button">
						<a href="javascript:AddToCart('.$p['product_id'].')"><div class="cart_button"></div></a>
						<a href="'.$link.'"><div class="detail_button"><span>Инфо</span></div></a>
					</div>
				</div>
			</li>';

		}
		$out.='</ul></div>';
	}else{
		if(empty($childs))
		$out.='
			<div class="siteMiddleDivContent" style="margin-left:50px;">
				<div id="bg_catalog_1" style="margin-top:30px;font-size:36px;">Товары не найдены :-\</div>
				<div id="bglist"></div>

				<div style="margin-top:30px;">
				<div id="bg_catalog_1">Возможные причины:</div>
				<ol style="margin-left:30px;"> 
					<li>Каталог товаров находится на стадии заполнения
					<li>Заданы устовия фильтра, под которые не подходит ни один товар
				</ol>
				<br>
				<div id="bg_catalog_1">Что делать:</div>
				<ul style="margin-left:30px;"> 
					<li>Если Вы используете фильтрацию товаров: сбросьте фильтр или задайте более мягкие условия фильтрации.</li>
				</ul>
				</div>
			</div>
			';
	}

	return $out;

}








/*
 * Возвращение корзины пользователя
 */
function mc_getUserCart(){
	$cart = Shop::_cartInfo();
	return '<div class="title"><label>Моя Корзина</label><b id="cart_count">'.$cart['count'].'</b> товаров<br><b id="cart_sum">'.$cart['sum'].'.00</b> руб</div>';
}




/*
 * Возвращение контента для 404 ошибки
 */
function mc_get404(){
	return '
	<div class="siteMiddleDivContent" style="margin-left:50px;">
		<div id="bg_catalog_1" style="margin-top:30px;font-size:36px;">404: Страница не найдена</div>
		<div id="bglist"></div>

		<div style="margin-top:30px;">
		<div id="bg_catalog_1">Возможные причины:</div>
		<ol style="margin-left:30px;"> 
			<li>Страница доступна только для авторизованных пользователей
			<li>Ссылка набрана неправильно.
			<li>Ссылка, из поисковой машины или с сайта, устарела.
			<li>Страница удалена или перемещена администратором.
		</ol>
		<br>
		<div id="bg_catalog_1">Что делать:</div>
		<ul style="margin-left:30px;"> 
			<li>Пройти <a href="/users/">авторизацию</a></li>
			<li>Проверьте, правильно ли набран адрес.</li>
			<li>Воспользуйтесть <a href="/search/" class="link">поиском по сайту</a>.</li>
			<li>Обратитесь к администратору.</li>
		</ul>
		</div>
	</div>
	';
}


/*
 * Возвращает контент информации о выбранном товаре
 */
function mc_getProductInfo(){
	if(empty($GLOBALS['UID'])) return mc_get404();
	$seo_links = empty($GLOBALS['SEO_LINKS']) ? false : true;
	$db = Database::getInstance('main');
	$p = $GLOBALS['UID'];
	$out='<div class="breadcrumb"> <a href="/">Главная</a> <img src="/client/images/next.gif" alt="" width="16" height="16" border="0" align="absmiddle"> ';
	//Путь к каталогу
	if(!empty($p['parents'])){
		foreach($p['parents'] as $parent){
			$link = ($seo_links && !empty($parent[2]) ? '/catalog/'.$parent[0].'/'.$parent[2].'/' : '/shop/CID_'.$parent[0].'.html');
			$out.='<a href="'.$link.'">'.$parent[1].'</a> <img src="/client/images/next.gif" alt="" width="16" height="16" border="0" align="absmiddle"> ';
		}
	}

	$images = $db->select('SELECT * FROM `product_images` WHERE `product_id`='.$p['product_id']);

	$shop = Shop::getInstance();

	$bridge_info = ($p['bridge_id']>0 ? $shop->getBridgeInfo($p['bridge_id'], true) : null);
	if(!empty($bridge_info)){
		$price = $bridge_info['price'];
		$count = intval($bridge_info['count']);
	}else{
		$price = $shop->getPrice($p['currency'],$p['base_price'], true, $p['offer_discount']);
		$count = intval($p['count']);
	}

	$is_offer = $p['offer_discount']>0;

	$teko_cnt = $db->result('SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`='.$p['product_id'].' AND `warehouse_id` IN (5,3,7,9,10,11)');
	if($teko_cnt>0 && $teko_cnt == $count){
		$pcount = array(
			'class'	=> '0',
			'text'	=> 'Заказ 4-7 дней'
		);
	}else{
		$pcount = $shop->productCountInfo($count);
	}

	//$pcount = $shop->productCountInfo($count);

	$out.=' <b>'.$p['name'].'</b></div><div class="lightBox"><div class="title'.($is_offer?' offer':'').'">'.$p['name'].($is_offer?' <font color="red">**СПЕЦИАЛЬНОЕ ПРЕДЛОЖЕНИЕ**</font>':'').'</div></div><br>';

	$out.='<div class="pcard"><div class="pic">'.($is_offer?'<div class="offer"><img src="/client/images/offer2.png" wifth="48" height="48"></div>':'').'<div class="picBig"><img id="ppicbig" style="cursor:pointer;" src="'.$p['pic_big'].'" onerror="this.src=\'/client/images/no_photo.gif\'" onclick="jsSlimbox.open(this.src, 0, {});"></div>';
	if(!empty($images)){
		$img_arr='["'.(empty($p['pic_big'])?"/client/images/no_photo.gif":$p['pic_big']).'"';
		$out.='<ul class="imglist">';
		$i=1;
		if(!empty($p['pic_big'])) $out.='<li><img style="cursor:pointer;" src="'.$p['pic_big'].'" onclick="productShowImage(0)"></li>';
		foreach($images as $img){
			$out.='<li><img style="cursor:pointer;" src="'.$img['image_file'].'" onclick="productShowImage('.$i.')"></li>';
			$img_arr.=',"'.$img['image_file'].'"';
			$i++;
		}
		$img_arr.=']';
		$js='<script>
			var product_images = '.$img_arr.';
			function productShowImage(index){
			$("ppicbig").src = product_images[index];
			//jsSlimbox.open(product_images[index], 0, {});
		}
		</script>';
		$out.='</ul>'.$js;
	}
	$out.='</div>
		<div class="info">
			<div class="general">
				<table>
					<tr><td class="param">Идентификатор</td><td class="value">'.$p['product_id'].'</td></tr>
					'./*<tr><td class="param">Артикульный номер</td><td class="value">'.$p['article'].'</td></tr>*/''.'
					<tr><td class="param">Производитель</td><td class="value">'.$p['vendor'].'</td></tr>
					<tr><td class="param">Парт-номера</td><td class="value">'.(empty($p['partnums'])?'-':$p['partnums']).'</td></tr>
					<tr><td class="param">Наличие</td><td class="value"><div class="pbsklad pb'.$pcount['class'].'">'.$pcount['text'].'</div></td></tr>
					<tr><td class="param">Цена</td><td class="price">'.$price.'.00 руб</td></tr>
				</table>
			</div>
			<div class="add2cart"><a href="javascript:AddToCart('.$p['product_id'].')"><div class="cart_button_full"><span>В корзину</span></div></a></div>
			<div class="description">'.$p['description'].'</div>

			<div class="tab-pane tab_content" id="tabs_area">

				<ul class="tabs">
					<li class="tab">Характеристики</li>
					<li class="tab">Cовместимость</li>
				</ul>

				<div class="tab-page tab_content">'.(empty($p['content'])?'Описание технических характеристик пока не доступно':$p['content']).'</div>
				<div class="tab-page tab_content">'.(empty($p['compatible'])?'Таблица совместимости пока не доступна':$p['compatible']).'</div>

			</div>

			<script type="text/javascript">
			new jsTabPanel("tabs_area",{
				"onchange": null
			});
			</script>

		</div>

	</div>
	<br>';

	return $out;
}



/*
 * Возвращает информацию о корзине покупок
 */
function mc_getCartInfo(){
	$cart = Session::_get('cart');
	if(empty($cart)||!is_array($cart)){
		return '<div class="lightBox"><div class="title">Оформление Заказа</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ВАША КОРЗИНА ПУСТА</font></b><br><font color="black" style="font-size:12px">Оформление заказа невозможно</font></td></tr></table>';
	}

	$new_cart = array();
	$shop = Shop::getInstance();

	//Проверяем актуальность товаров и цен на товары в корзине
	foreach($cart as $c){
		$product = $shop->productExists($c['product_id'], true);
		if(empty($product)) continue;
		$bridge_info = ($product['bridge_id']>0 ? $shop->getBridgeInfo($product['bridge_id'], true) : null);
		if(!empty($bridge_info)){
			$price = $bridge_info['price'];
			$base_price = $bridge_info['base_price'];
			$bridge_id = $bridge_info['bridge_id'];
		}else{
			$base_price = $product['base_price'];
			$price = $shop->getPrice($product['currency'],$base_price, true, $product['offer_discount']);
			$bridge_id = 0;
		}
		$new_cart['p'.$product['product_id']] = array(
			'product_id' 	=> $product['product_id'],
			'bridge_id'		=> $bridge_id,
			'name'			=> $product['name'],
			'available'		=> $product['count'],
			'currency'		=> $product['currency'],
			'base_price'	=> $base_price,
			'exchange'		=> $shop->currencyExchange($product['currency'], 1),
			'price'			=> $price,	//Цена товара
			'count'			=> $c['count'],		//Количество товаров
			'timestamp'		=> time()			//Время добавления/обновления товара в корзине
		);
	}

	Session::_set('cart', $new_cart);
	Session::_set('cart_ordering_time', time());
	$cart = $new_cart;

	$cart_sha1 = sha1(serialize($cart));

	$out='
	<div class="lightBox"><div class="title"><b>Ваша корзина</b></div></div>
	<div class="ordercart"><table><thead>
		<th>Наименование</td>
		<th width=90>Цена</td>
		<th width=100>Количество</td>
		<th width=80>Операции</td>
		<th width=90>Сумма</td>
	</thead><tbody>';

	$total = 0;
	$sum = 0;

	foreach($cart as $p){

		$sum = ceil($p['price']*$p['count']);
		$total += $sum;
		$out.='
		<tr><td class="productName"><a class="'.($p['available']>0?'':'zero_count').'" href="/shop/UID_'.$p['product_id'].'.html" target="_blank" title="'.$p['name'].'">'.$p['name'].($p['available']>0?'':' <font color="#999">(Под заказ)</font>').'</a></td>
		<td class="productPrice">'.$p['price'].'.00 руб</td>
		<td class="productQty">
			<form method="post"><div>
				<input type="text" class="input" style="text-align:center;" value="'.$p['count'].'" size="3" maxlength="5" name="new_count" onchange="this.form.submit()">
				</div>
				<div style="padding-top:10px;">
					<input type="image" src="/client/images/dtbox/refresh.gif" value="edit" alt="Пересчитать">
				</div>
				<input type=hidden name="action" value="update">
				<input type=hidden name="product_id" value="'.$p['product_id'].'">
			</form>
		</td>
		<td>
			<form method="post">
				<input type="image" src="/client/images/dtbox/cart_delete.gif" value="delet" alt="Удалить">
				<input type=hidden name="action" value="delete">
				<input type=hidden name="product_id" value="'.$p['product_id'].'">
			</form>
		</td>

			<td class="productPriceTotal">'.$sum.'.00 руб</td>
		</tr>';
	}

	$out.='</tbody></table></div>
	<div class="general">
		<table style="width:100%;">
			<tr>
				<td class="paramCart">Выбранно товаров на сумму:</td>
				<td class="priceCart">'.$total.'.00 руб</td>
			</tr>
			<tr>
				<td class="paramCart">Стоимость доставки:</td>
				<td class="priceCart"><span id="delivery">0</span>.00 руб</td>
			</tr>
			<tr>
				<td class="paramCartTotal">Всего в оплате:</td>
				<td class="priceCartTotal"><span id="total">'.$total.'</span>.00 руб</td>
			</tr>
		</table>
	</div>
	<form id="cleanform" method="post">
	<input type=hidden name="action" value="clean">
	</form>
	<div align="right" style="margin: 10px 0 20px 0;">
		<div class="light_button" style="width:150px;" onclick="cartClean();"><span>Очистить корзину</span></div>
	</div>';

	$delivery_id = 1;
	$client_name = '';
	$client_email = '';
	$client_company = '';
	$client_inn = '';
	$client_kpp = '';
	$client_phone = '';
	$client_address = '';
	$client_additional = '';
	$ordering = Session::_get('ordering');
	$info = Client::_getClientInfo(true);
	if(is_array($ordering)&&!empty($ordering)){
		$delivery_id		= $ordering['delivery_id'];
		$client_email		= $ordering['order_email'];
		$client_name		= $ordering['order_name'];
		$client_company		= $ordering['order_company'];
		$client_inn			= $ordering['order_inn'];
		$client_kpp			= $ordering['order_kpp'];
		$client_phone		= $ordering['order_phone'];
		$client_address		= $ordering['order_address'];
		$client_additional	= $ordering['order_additional'];
	}
	else
	if(!empty($info)){
		$client_name 	= $info['name'];
		$client_email 	= $info['email'];
		$client_company = $info['company'];
		$client_inn 	= $info['inn'];
		$client_kpp 	= $info['kpp'];
		$client_phone 	= $info['phone'];
		$client_address = $info['city'].",\n".$info['address'];
	}
	$out.='
	<div class="darkBox"><div class="title">Оформление Заказа</div></div><br>
	<form method="post" id="orderform">
		<input type="hidden" name="action" value="order">
		<input type="hidden" name="security" value="'.$cart_sha1.'">
		<div class="orderClientInfo">
			<table class="utable">
				<tr>
					<td class="param">Доставка:</td>
					<td class="value"><select id="delivery_id" name="delivery_id" style="width:312px;"></select><br><div id="delivery_desc" style="display:none;color:#818181;"></div></td>
				</tr>
				<tr>
					<td class="param">E-mail*:</td>
					<td class="value">
						<input type="text" class="input" name="order_email" id="order_email" style="width:300px;" maxlength="30" value="'.$client_email.'" placeholder="mymail@example.com">
					</td>
				</tr>
				<tr>
					<td class="param">Контактное лицо*:</td>
					<td class="value">
						<input type="text" class="input" name="order_name" id="order_name" style="width:300px;" maxlength="30" value="'.$client_name.'" placeholder="Фамилия Имя Отчество">
					</td>
				</tr>
				<tr>
					<td class="param">Компания:</td>
					<td class="value">
						<input type="text" class="input" name="order_company" id="order_company" style="width:300px;" maxlength="100" value="'.$client_company.'" placeholder="Название организации">
					</td>
				</tr>
				<tr>
					<td class="param">ИНН:</td>
					<td class="value">
						<input type="text" class="input" name="order_inn" id="order_inn" style="width:150px;" maxlength="50" value="'.$client_inn.'">
					</td>
				</tr>
				<tr>
					<td class="param">КПП:</td>
					<td class="value">
						<input type="text" class="input" name="order_kpp" id="order_kpp" style="width:150px;" maxlength="50" value="'.$client_kpp.'">
					</td>
				</tr>
				<tr>
					<td class="param">Телефон*:</td>
					<td class="value">
						<input type="text" class="input" name="order_phone" id="order_phone" style="width:150px;" maxlength="30" value="'.$client_phone.'" placeholder="8 (800) 123-45-67">
					</td>
				</tr>
				<tr id="delivery_address_tr">
					<td class="param">Адрес доставки*:</td>
					<td class="value">
							<textarea style="width:300px; height:100px;" name="order_address" id="order_address">'.$client_address.'</textarea>
					</td>
				</tr>
				<tr>
					<td class="param">Примечание к заказу:</td>
					<td class="value">
							<textarea style="width:300px; height:100px;" name="order_additional">'.$client_additional.'</textarea>
					</td>
				</tr>
				<tr>
					<td class="param">Тип оплаты покупки:</td>
					<td class="value"><select id="paymethod_id" name="paymethod_id" style="width:312px;">
						<option value="cash">Наличная оплата</option>
						<option value="wire">Банковский перевод</option>
					</select></td>
				</tr>
				<tr>
					<td class="param"></td>
					<td class="value">
					Данные, отмеченные * обязательны для заполнения.<br>
					<br>
				</tr>
				<tr>
					<td class="param"></td>
					<td class="value">
						<div><div class="dark_button" style="width:150px;" onclick="orderSubmit();"><span>Оформить покупку</span></div></div>
						<div><div class="light_button" style="width:150px;" onclick="orderReset();"><span>Очистить форму</span></div></div>
					</td>
				</tr>
			</table>
		</div>
	</form>';

	$deliveries = Shop::_deliveryAvailableList($total);
	$out.='<script>
		var ordersum = '.$total.';
		var orderformvalidator = new jsValidator("orderform");
		var deliveries = '.json_encode($deliveries).';
		orderformvalidator.required("order_email").email("order_email").
		required("order_name").required("order_phone").phone("order_phone");
		function cartClean(){$("cleanform").submit();}
		function orderReset(){
			orderformvalidator.empty();
			$("orderform").reset();
		}
		function orderSubmit(){
			if(!orderformvalidator.validate())return false;
			$("orderform").submit();
		}
		function deliveryChange(){
			var delivery_id = select_getValue($("delivery_id"));
			var delivery_cost = parseInt(deliveries.filterResult("price", "delivery_id", delivery_id));
			var delivery_desc = deliveries.filterResult("desc", "delivery_id", delivery_id);
			$("delivery").set("text",String(delivery_cost));
			$("total").set("text",String(ordersum+delivery_cost));
			$("delivery_desc").set("text",delivery_desc);
			if(delivery_desc == "") $("delivery_desc").hide(); else $("delivery_desc").show();
			if(delivery_id == 1){
				$("delivery_address_tr").hide();
			}else{
				$("delivery_address_tr").show();
			}
		}
		document.addEvent("appbegin", function() {
			select_add({
				"list": "delivery_id",
				"key": "delivery_id",
				"value": "name",
				"options": deliveries,
				"default": '.$delivery_id.',
				"clear": true
			});
			$("delivery_id").addEvent("change", deliveryChange);
			deliveryChange();
			if(typeOf(REQUEST_INFO["get"]["e"])=="string"){
				switch(REQUEST_INFO["get"]["e"]){
					case "empty": App.message("Оформление заказа","Корзина заказа пуста","WARNING"); break;
					case "changed": App.message("Оформление заказа","Во время оформления заказа содержимое корзины изменилось","WARNING"); break;
					case "email": App.message("Оформление заказа","Не задан адрес электронной почты","WARNING"); break;
					case "name": App.message("Оформление заказа","Не задано контактное имя","WARNING"); break;
					case "address": App.message("Оформление заказа","Не задан адрес доставки","WARNING"); break;
					case "phone": App.message("Оформление заказа","Не задан контактный номер телефона","WARNING"); break;
					case "delivery": App.message("Оформление заказа","Не выбран тип доставки","WARNING"); break;
					case "timeout": App.message("Оформление заказа","Время ожидания истекло, пожалуйста, отправте заказ повторно","ERROR"); break;
					case "internal": App.message("Оформление заказа","При оформлении заказа произошла ошибка, попробуйте повторить процедуру оформления чуть позже","ERROR"); break;
				}
			}
		});
	</script>';

	return $out;
}


/*
 * Оформление заказа
 */
function mc_doOrder(){
	$request = Request::getInstance();
	
	$delivery_id		= $request->getId('delivery_id', 1);
	$order_email		= htmlspecialchars($request->getEmail('order_email', ''));
	$order_name			= htmlspecialchars($request->getStr('order_name', ''));
	$order_company		= htmlspecialchars($request->getStr('order_company', ''));
	$order_inn			= htmlspecialchars($request->getStr('order_inn', ''));
	$order_kpp			= htmlspecialchars($request->getStr('order_kpp', ''));
	$order_phone		= htmlspecialchars($request->getStr('order_phone', ''));
	$order_address		= htmlspecialchars($request->getStr('order_address', ''));
	$order_additional	= htmlspecialchars($request->getStr('order_additional', ''));
	$order_paytype		= htmlspecialchars($request->getStr('order_additional', ''));
	$paymethod_id		= $request->getEnum('paymethod_id',array('cash','wire'),'cash');
	$order_okpo			= htmlspecialchars($request->getStr('okpo', ''));
	$bank_name 			= htmlspecialchars($request->getStr('bank_name',''));
	$bank_bik			= htmlspecialchars($request->getStr('bank_bik',''));
	$bank_account		= htmlspecialchars($request->getStr('bank_account',''));
	$bank_account_corr	= htmlspecialchars($request->getStr('bank_account_corr',''));
	$legal_address		= htmlspecialchars($request->getStr('legal_address', ''));

	Session::_set('ordering',array(
		'delivery_id'		=> $delivery_id,
		'order_email'		=> $order_email,
		'order_name'		=> $order_name,
		'order_company'		=> $order_company,
		'order_inn'			=> $order_inn,
		'order_kpp'			=> $order_kpp,
		'order_phone'		=> $order_phone,
		'order_address'		=> $order_address,
		'order_additional'	=> $order_additional,
		'order_okpo'		=> $order_okpo,
		'bank_name'			=> $bank_name,
		'bank_bik'			=> $bank_bik,
		'bank_account'		=> $bank_account,
		'bank_account_corr'	=> $bank_account_corr,
		'legal_address'		=> $legal_address,
		'paymethod_id'		=> $paymethod_id
	));
	$cart_ordering_time = intval(Session::_get('cart_ordering_time'));
	$cart = Session::_get('cart');
	if(empty($cart)||!is_array($cart)) return Page::_doLocation('/order/cart?e=empty');
	if(time() - $cart_ordering_time > 900) return Page::_doLocation('/order/cart?e=timeout');
	$cart_sha1 = sha1(serialize($cart));
	if(strcmp($request->getStr('security', ''), $cart_sha1)!=0) return Page::_doLocation('/order/cart?e=changed');
	if(empty($order_email)) return Page::_doLocation('/order/cart?e=email');
	if(empty($order_name)) return Page::_doLocation('/order/cart?e=name');
	if(empty($order_phone)) return Page::_doLocation('/order/cart?e=phone');
	if(empty($order_address)&&$delivery_id>1) return Page::_doLocation('/order/cart?e=address');
	$delivery = Shop::_deliveryExists($delivery_id);
	if(empty($delivery)) return Page::_doLocation('/order/cart?e=delivery');

	$total = 0;
	foreach($cart as $p){$total += ceil($p['price']*$p['count']);}
	if($delivery['order_min'] > $total || $delivery['order_max'] < $total) return Page::_doLocation('/order/cart?e=delivery');

	$client_id = Client::_getClientId();

	$add = array(
		'order_num'		=> '',						#[char, 32] Номер заказа
		'status'		=> 10,						#[uint] Текущий статус заказа: 10- Не обработан
		'delivery_id'	=> $delivery_id,			#[uint] Тип доставки товара для клиента из таблицы deliveries
		'delivery_cost'	=> $delivery['price'],		#[double] Сумма оплаты за доставку
		'client_id'		=> $client_id,				#[uint] Идентификатор клиента
		'company'		=> $order_company,			#[char, 255] Имя организации клиента
		'name'			=> $order_name,				#[char, 255] Имя клиента
		'email'			=> $order_email,			#[char, 128] Контактный email, указанный при регистрации
		'phone'			=> $order_phone,			#[char, 32] Номер телефона
		'address'		=> $order_address,			#[char, 255] Почтовый адрес
		'additional'	=> $order_additional,		#[char, 255] Дополнительная информация
		'inn'			=> $order_inn,				#[char, 32] ИНН
		'kpp'			=> $order_kpp,				#[char, 32] КПП
		'order_okpo'		=> $order_okpo,
		'bank_name'			=> $bank_name,
		'bank_bik'			=> $bank_bik,
		'bank_account'		=> $bank_account,
		'bank_account_corr'	=> $bank_account_corr,
		'legal_address'		=> $legal_address,
		'paymethod'		=> $paymethod_id,			#[char, 32] Метод оплаты
		'ip_addr'		=> $request->getIP(false),	#[char, 15] IP адрес
		'ip_real'		=> $request->getIP(true)	#[char, 15] IP адрес
	);

	$db = Database::getInstance('main');
	$db->transaction();
	if(!empty($client_id)){
		$client_info = $db->selectRecord('SELECT * FROM `clients` WHERE `client_id`='.$client_id.' LIMIT 1');
		$need = array('okpo','bank_name','bank_bik','bank_account','bank_account_corr','legal_address');
		foreach($need as $n){
			if(empty($add[$n])) $add[$n] = $client_info[$n];
		}
	}
	
	$order_id = $db->addRecord('orders', $add);
	if(empty($order_id)){
		$db->rollback();
		return Page::_doLocation('/order/cart?e=internal');
	}
	$order_num = $order_id.'-'.date("ymd");
	$db->update('UPDATE `orders` SET `order_num`="'.$order_num.'" WHERE `order_id`='.$order_id);
	$added = 0;
	$mail_cart = '';
	$total=0;
	foreach($cart as $p){
		if(empty($p['count'])||empty($p['price'])||empty($p['product_id'])) continue;
		$sum = ceil($p['price']*$p['count']);
		$id = $db->addRecord('order_products', array(
			'order_id'		=> $order_id,						#[uint, index] Идентификатор заказа из таблицы orders
			'product_id'	=> $p['product_id'],				#[uint] Идентификатор товара из таблицы products
			'currency'		=> $p['currency'],					#[char, 3] Валюта заказа
			'base_price'	=> $p['base_price'],				#[double] Базовая цена за единицу товара
			'exchange'		=> $p['exchange'],					#[double] Курс валюты по отношению к рублю на момент заказа
			'price'			=> $p['price'],						#[double] Цена за единицу товара для клиента
			'count'			=> $p['count'],						#[double] Количество единиц товара
			'sum'			=> $sum								#[double] Общая сумма оплаты за позицию
		));
		$mail_cart.='
		<tr>
			<td>'.$p['name'].'</td>
			<td>'.$p['price'].'.00</td>
			<td>'.$p['count'].'</td>
			<td>'.$sum.'.00</td>
		</tr>';
		$total+=$sum;
		$added++;
	}
	if(!$added){
		$db->rollback();
		return Page::_doLocation('/order/cart?e=empty');
	}

	$mail_cart.='<tr><td colspan="3">Итого товаров на сумму (руб):</td><td>'.$total.'.00</td></tr>';
	if($delivery['price']>0){
		$mail_cart.='<tr><td colspan="3">Стоимость доставки (руб):</td><td>'.$delivery['price'].'.00</td></tr>';
	}

	$db->commit();
	Session::_set('cart', null);
	Session::_set('lastorder', array(
		'order_id'		=> $order_id,
		'order_num'		=> $order_num,
		'order_email'	=> $order_email
	));

	$mail_tmpl = Template::getInstance('ordermail');
	$mail_tmpl->setTemplate('Main/templates/mail/client_new_order.html');
	$mail_tmpl->assign('order_id', $order_id);
	$mail_tmpl->assign('order_num', $order_num);
	$mail_tmpl->assign('order_email', $order_email);
	$mail_tmpl->assign('order_name', $order_name);
	$mail_tmpl->assign('order_phone', $order_phone);
	$mail_tmpl->assign('order_delivery', $delivery['name'].(!empty($order_address)?'<br>'.$order_address:''));
	$mail_tmpl->assign('order_paymethod', ($paymethod_id == 'cash' ? 'Наличная оплата' : 'Банковский платеж'));
	$mail_tmpl->assign('order_cart',$mail_cart);
	$mail_tmpl->assign('order_link','http://dtbox.ru/order/info?email='.rawurlencode($order_email).'&order='.rawurlencode($order_num));

	$mail_content = $mail_tmpl->parseTemplate();

	$header = "MIME-Version: 1.0\r\n";
	$header.= "From: DTBox.ru <info@dtbox.ru>\r\n";
	$header.= "Reply-To: info@dtbox.ru\r\n";
	$header.= "Content-Type: text/html; charset=utf-8\r\n";
	$header.= "Content-Transfer-Encoding: 8bit\r\n";
	mail($order_email,'Заказ товаров на dtbox.ru', $mail_content, $header);


	return Page::_doLocation('/order/complete');
}


/*
 * Вывод информации об оформленном заказе
 */
function mc_orderComplete(){
	$order = $GLOBALS['LASTORDER'];
	$client_id = Client::_getClientId();
	return '
	<div class="lightBox"><div class="title">Заказ оформлен</div></div>
	<br>
	<table width="100%" align="center">
		<tr><td align="center" style="padding:50;font-size:12px">
			<img src="/client/images/dtbox/order_complete.png" height="100" border="0">
			<br><br>
			<b style=""><font color="green" style="font-size:14px">ЗАКАЗ УСПЕШНО ОФОРМЛЕН</font></b>
			<br><font color="black" style="font-size:14px">Номер Вашего заказа: <b>'.$order['order_num'].'</b></font>
			<br><br><br>
			На Ваш адрес электронной почты <b>'.$order['order_email'].'</b> было направлено письмо с информацией по заказу.
			<br><br>
			Вы можете проверить статус заказа и загрузить платежные документы, перейдя по ссылке:<br><br><a class="link" href="http://'.$_SERVER["SERVER_NAME"].'/order/info?email='.rawurlencode($order['order_email']).'&order='.rawurlencode($order['order_num']).'" target="_blank">http://'.$_SERVER["SERVER_NAME"].'/order/info?email='.rawurlencode($order['order_email']).'&order='.rawurlencode($order['order_num']).'</a>
			'.(!empty($client_id) ? '<br><br>Либо через Личный кабинет в разделе &laquo;Мои заказы&raquo;' : '').'</td></tr></table>';
}



/*
 * Построение сведений об учетной записи клиента
 */
function mc_userAccountInfo(){

	$db = Database::getInstance('main');
	$info = Client::_getClientInfo(true);
	if(empty($info)) return Page::_doLocation('/');
	$discount = $db->selectRecord('SELECT * FROM `discounts` WHERE `discount_id`='.$info['discount_id'].' LIMIT 1');
	if(empty($discount)||!is_array($discount)){
		$client_status = 'Зарегистрированный клиент';
	}else{
		$client_status = $discount['name'];
	}

	$request = Request::getInstance();
	$is_post = ($request->get('method', 'GET') == 'POST' ? true : false);
	$action = $request->get('action', false);

	if($is_post){
		switch($action){

			//Обновление логина и пароля
			case 'updateauth':
				$client_username	= trim(htmlspecialchars($request->getStr('client_login', '')));
				$client_password	= trim($request->getStr('client_password', ''));
				if(strlen($client_username)<5)	return Page::_doLocation('/users/account?e=login');
				if(strlen($client_password)<5)	return Page::_doLocation('/users/account?e=password');
				if(in_array($client_username,array('root','admin','support','test'))) return Page::_doLocation('/users/account?e=exists');
				$db->prepare('SELECT count(*) FROM `clients` WHERE `username` LIKE ? AND `client_id`<>? LIMIT 1');
				$db->bind($client_username);
				$db->bind($info['client_id']);
				if($db->result() > 0) return Page::_doLocation('/users/account?e=exists');
				Client::_clientUpdate($info['client_id'],array(
					'username'	=> $client_username,
					'password'	=> sha1($client_password)
				));
				return Page::_doLocation('/users/account?e=success');
			break;

			//Обновление информации
			case 'updateinfo':
				$client_email	= htmlspecialchars($request->getEmail('client_email', ''));
				$client_name	= htmlspecialchars($request->getStr('client_name', ''));
				$client_company	= htmlspecialchars($request->getStr('client_company', ''));
				$client_inn		= htmlspecialchars($request->getStr('client_inn', ''));
				$client_kpp		= htmlspecialchars($request->getStr('client_kpp', ''));
				$client_phone	= htmlspecialchars($request->getStr('client_phone', ''));
				$client_address	= htmlspecialchars($request->getStr('client_address', ''));
				$client_country	= htmlspecialchars($request->getStr('client_country', ''));
				$client_city	= htmlspecialchars($request->getStr('client_city', ''));
				$client_zip		= htmlspecialchars($request->getStr('client_zip', ''));
				if(empty($client_email))	return Page::_doLocation('/users/account?e=email');
				if(empty($client_name))		return Page::_doLocation('/users/account?e=name');
				if(empty($client_phone))	return Page::_doLocation('/users/account?e=phone');
				if(empty($client_address))	return Page::_doLocation('/users/account?e=address');
				if(empty($client_city))		return Page::_doLocation('/users/account?e=city');

				$db->prepare('SELECT count(*) FROM `clients` WHERE `email` LIKE ? AND `client_id`<>? LIMIT 1');
				$db->bind($client_email);
				$db->bind($info['client_id']);
				if($db->result() > 0) return Page::_doLocation('/users/account?e=emailexists');

				Client::_clientUpdate($info['client_id'],array(
					'company'	=> $client_company,		#[char, 255] Имя организации клиента
					'name'		=> $client_name,		#[char, 255] Имя клиента
					'email'		=> $client_email,		#[char, 128] Контактный email, указанный при регистрации
					'phone'		=> $client_phone,		#[char, 32] Номер телефона
					'address'	=> $client_address,		#[char, 255] Почтовый адрес
					'city'		=> $client_city,		#[char, 64] Город
					'country'	=> $client_country,		#[char, 64] Страна
					'zip'		=> $client_zip,			#[char, 16] Почтовый индекс
					'inn'		=> $client_inn,			#[char, 32] ИНН
					'kpp'		=> $client_kpp			#[char, 32] КПП
				));
				return Page::_doLocation('/users/account?e=success');
			break;

		}
		return Page::_doLocation('/users/account');
	}

	$out='
	<div class="lightBox">
		<div class="title"><b>Статус</b></div>
	</div>
	<br><div class="user_status" style="text-align:center;">'.$client_status.'</div><br>
	<div class="lightBox">
		<div class="title"><b>Данные для входа</b></div>
	</div><br>
	<form method="post" id="authform">
	<input type="hidden" name="action" value="updateauth">
	<div class="orderClientInfo">
		<table class="utable">
			<tr>
				<td width="150" class="param">Логин*:</td>
				<td class="value"><input type="text" class="input" name="client_login" id="client_login" value="'.$info['username'].'" style="width:250px;"></td>
			</tr>
			<tr>
				<td class="param">Пароль*:</td>
				<td class="value"><input type="password" class="input" name="client_password" id="client_password" style="width:250px;" value="">
					<br><input type="password" class="input" name="client_password2" id="client_password2" style="width:250px;" value=""> (Повторите пароль)
				</td>
			</tr>
			<tr>
				<td></td>
				<td><input type="checkbox" id="update_password" value="1" name="update_password" onClick="updatePasswordCheck()"><label for="update_password"> Изменить логин / пароль</label>
					<button type="button" id="update_password_button" class="ui-button-light" style="display:inline;" role="button" aria-disabled="false" onclick="updateUserPassword()"><span>Изменить</span></button>
				</td>
			</tr>
		</table>
	</form>

	<form method="post" id="infoform">
	<input type="hidden" name="action" value="updateinfo">
	<div class="lightBox">
		<div class="title"><b>Личные данные</b></div>
	</div>
	<br>
		<table class="utable">
			<tr>
				<td width="150" class="param">Контактное лицо*:</td>
				<td class="value"><input type="text" class="input" name="client_name" id="client_name" value="'.$info['name'].'" style="width:300px"></td>
			</tr>
			<tr>
				<td class="param">E-mail*:</td>
				<td class="value"><input type="text" class="input" name="client_email" id="client_email" value="'.$info['email'].'" style="width:300px"></td>
			</tr>
			<tr>
				<td class="param">Компания:</td>
				<td class="value"><input type="text" class="input" name="client_company" id="client_company" style="width:300px;" value="'.$info['company'].'"></td>
			</tr>
			<tr>
				<td class="param">ИНН:</td>
				<td class="value"><input type="text" class="input" name="client_inn" id="client_inn" style="width:300px;" value="'.$info['inn'].'"></td>
			</tr>
			<tr>
				<td class="param">КПП:</td>
				<td class="value"><input type="text" class="input" name="client_kpp" id="client_kpp" style="width:300px;" value="'.$info['kpp'].'"></td>
			</tr>
			<tr>
				<td class="param">Телефон*:</td>
				<td class="value"><input type="text" class="input" name="client_phone" id="client_phone" style="width:300px;" value="'.$info['phone'].'"></td>
			</tr>
			<tr>
				<td class="param">Страна:</td>
				<td class="value"><input type="text" class="input" name="client_country" id="client_country" style="width:300px;" value="'.$info['country'].'"></td>
			</tr>
			<tr>
				<td class="param">Почтовый индекс:</td>
				<td class="value"><input type="text" class="input" name="client_zip" id="client_zip" style="width:300px;" value="'.$info['zip'].'"></td>
			</tr>
			<tr>
				<td class="param">Город*:</td>
				<td class="value"><input type="text" class="input" name="client_city" id="client_city" style="width:300px;" value="'.$info['city'].'"></td>
			</tr>
			<tr>
				<td class="param">Адрес*:</td>
				<td class="value"><textarea style="width:300px; height:100px;" name="client_address" id="client_address">'.$info['address'].'</textarea></td>
			</tr>
			<tr>
				<td></td>
				<td>
					Поля, отмеченные * обязательны для заполнения.<br>
					</div>
				<br></td>
		</tr>
		<tr>
			<td></td>
			<td>
					<input type="hidden" value="1" name="update_user">
					<button type="button" class="ui-button-light" role="button" aria-disabled="false" onclick="updateUserInfo()"><span>Изменить данные</span></button>
					<br>
				</td>
			</tr>
		</table>
	</div>
	</form>';
	$out.='<script>
		var infoformvalidator = new jsValidator("infoform");
		infoformvalidator.required("client_email").email("client_email").required("client_city").
		required("client_address").required("client_name").required("client_phone").phone("client_phone");

		var authformvalidator = new jsValidator("authform");
		authformvalidator.required("client_login").username("client_login").range("client_login",5,20).range("client_password",5,20).
		required("client_password").matches("client_password","client_password2");

		function updatePasswordCheck(){
			if($("update_password").checked) $("update_password_button").show(); else $("update_password_button").hide();
		}
		function updateUserPassword(){
			if(!authformvalidator.validate())return false;
			$("authform").submit();
		}
		function updateUserInfo(){
			if(!infoformvalidator.validate())return false;
			$("infoform").submit();
		}
		function infoReset(){
			infoformvalidator.empty();
			$("infoform").reset();
		}
		document.addEvent("appbegin", function() {
			updatePasswordCheck();
			if(typeOf(REQUEST_INFO["get"]["e"])=="string"){
				switch(REQUEST_INFO["get"]["e"]){
					case "email": App.message("Личные данные","Не задан адрес электронной почты","WARNING"); break;
					case "emailexists": App.message("Личные данные","Указанный адрес электронной почты уже используется одним из клиентов магазина.","WARNING"); break;
					case "name": App.message("Личные данные","Не задано контактное имя","WARNING"); break;
					case "address": App.message("Личные данные","Не задан почтовый адрес","WARNING"); break;
					case "city": App.message("Личные данные","Не задан город","WARNING"); break;
					case "phone": App.message("Личные данные","Не задан контактный номер телефона","WARNING"); break;
					case "login": App.message("Личные данные","Не задано имя пользователя или задано некорреткно","WARNING"); break;
					case "password": App.message("Личные данные","Не задан пароль  или задан некорреткно","WARNING"); break;
					case "exists": App.message("Личные данные","Выбранное имя пользователя уже занято","WARNING"); break;
					case "internal": App.message("Личные данные","При обновлении данных произошла ошибка, попробуйте повторить чуть позже","ERROR"); break;
					case "success": App.tip("Личные данные","Изменение данных успешно выполнено","SUCCESS"); break;
				}
			}
		});
	</script>';

	return $out;
}


/*
 * Получение информации о заказах клиента
 */
function mc_userOrdersInfo(){
	$db = Database::getInstance('main');
	$out='<br>
	<div id="bg_catalog_1">Мои заказы</div>
	<div class="lightBox"><div class="title">Архив заказов</div></div>
	<div class="orderlist"><table>
		<thead>
			<th>&nbsp;</th>
			<th>№ Заказа</th>
			<th>Дата создания</th>
			<th>Количество товаров</th>
			<th>Сумма заказа, руб.</th>
			<th>Статус заказа</th>
			<th>Документы</th>
		</thead><tbody>';
	$orders = $db->select(
		'SELECT 
			O.`order_id` as `order_id`,
			O.`order_num` as `order_num`,
			O.`email` as `email`,
			O.`status` as `status`,
			O.`paymethod` as `paymethod`,
			O.`timestamp` as `timestamp`,
			(SELECT sum(OC.`count`) FROM `order_products` as OC WHERE OC.`order_id`=O.`order_id`) as `count`,
			(SELECT sum(OP.`price`*OP.`count`) FROM `order_products` as OP WHERE OP.`order_id`=O.`order_id`) as `total`
		FROM `orders` as O WHERE O.`client_id`='.Client::_getClientID());
	if(!empty($orders)){
		foreach($orders as $o){
			$status_text = Shop::_orderStatus($o['status'], true);
			if($o['paymethod']=='wire'&&$o['status'] == 25){
				$docs = '<a href="/order/documents/bill?email='.rawurlencode($o['email']).'&order='.rawurlencode($o['order_num']).'" target="_blank">Cчет</a>';
			}else{
				$docs = '---';
			}
			$out.= 
			'<tr>
				<td width="50"><a href="/order/info?email='.rawurlencode($o['email']).'&order='.rawurlencode($o['order_num']).'"><img src="/client/images/info-b.png" border="0"></a></td>
				<td align="center">'.$o['order_num'].'</td>
				<td align="center">'.$o['timestamp'].'</td>
				<td align="center">'.$o['count'].'</td>
				<td align="right">'.ceil($o['total']).'.00</td>
				<td align="left">'.$status_text.'</td>
				<td align="center">'.$docs.'</td>
			</tr>';
		}
	}
	$out.='</tbody></table></div>';
	
	return $out;
}



/*
 * Информация по заказу
 */
function mc_orderInfo(){
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$email = htmlspecialchars($request->getEmail('email', ''));
	$order_num = htmlspecialchars($request->getStr('order', ''));
	$not_found = false;
	if(empty($order_num)||empty($email)) $not_found = true;

	if(!$not_found){
		$order_data = explode('-',$order_num);
		$order_id = intval($order_data[0]);
		if(empty($order_id)) $not_found = true;
	}

	if(!$not_found){
		$db->prepare('SELECT * FROM `orders` WHERE `order_id`=? AND `order_num` LIKE ? AND `email` LIKE ? LIMIT 1');
		$db->bind($order_id);
		$db->bind($order_num);
		$db->bind($email);
		$order = $db->selectRecord();
		if(empty($order)||!is_array($order)) $not_found = true;
	}

	if($not_found) return '<div class="lightBox"><div class="title">Информация о заказе</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ЗАКАЗ НЕ НАЙДЕН</font></b><br><font color="black" style="font-size:12px">К сожалению, данный заказ не найден</font></td></tr></table>';

	$delivery = Shop::_deliveryExists($order['delivery_id']);

	$order_date = explode(' ',$order['timestamp'])[0];
	$out='';
	$out.= '<div class="darkBox"><div class="title">Информация по заказу №'.$order['order_num'].' от '.$order_date.'</div></div>';
	$out.='
	<div class="general">
		<table style="width:100%;">
			<tr><td class="orderParam" width="200">Номер заказа:</td><td class="orderValue">'.$order['order_num'].'</td></tr>
			<tr><td class="orderParam">Время заказа:</td><td class="orderValue">'.$order['timestamp'].'</td></tr>
			<tr><td class="orderParam">Статус заказа:</td><td class="orderValue">'.Shop::_orderStatus($order['status'], true).'</td></tr>
			<tr><td class="orderParam">Контактное лицо:</td><td class="orderValue">'.$order['name'].'</td></tr>
			<tr><td class="orderParam">Электронная почта:</td><td class="orderValue">'.$order['email'].'</td></tr>
			<tr><td class="orderParam">Номер телефона:</td><td class="orderValue">'.$order['phone'].'</td></tr>
			<tr><td class="orderParam">Компания:</td><td class="orderValue">'.$order['company'].'</td></tr>
			<tr><td class="orderParam">ИНН:</td><td class="orderValue">'.$order['inn'].'</td></tr>
			<tr><td class="orderParam">КПП:</td><td class="orderValue">'.$order['kpp'].'</td></tr>
			<tr><td class="orderParam">Тип оплаты:</td><td class="orderValue">'.($order['paymethod']=='cash'?'Наличная оплата':'Банковский платеж').'</td></tr>
			<tr><td class="orderParam">Доставка:</td><td class="orderValue">'.$delivery['name'].'</td></tr>
			<tr><td class="orderParam">Адрес:</td><td class="orderValue">'.$order['address'].'</td></tr>
			<tr><td class="orderParam">Дополнительные сведения:</td><td class="orderValue">'.$order['additional'].'</td></tr>
		</table>
	</div>';

	//Если метод оплаты - банковский перевод и статус заказа - "ожидается оплата" -> отображаем область получения счета
	if($order['paymethod']=='wire'&&$order['status'] == 25){
		$out.='<div class="darkBox"><div class="title">Счет на оплату</div></div><div style="margin:15px 0px;text-align:center;font-size:14px;"><a href="/order/documents/bill?email='.rawurlencode($order['email']).'&order='.rawurlencode($order['order_num']).'" target="_blank">Нажмите здесь, чтобы просмотреть и распечатать счет на оплату</a></div>';
	}

	$out.='<br><div class="lightBox"><div class="title">Товары в заказе</div></div>';
	$products = $db->select(
	'SELECT 
		OP.`product_id` as `product_id`,
		P.`name` as `name`,
		OP.`price` as `price`,
		OP.`count` as `count`
	FROM `order_products` as OP 
	INNER JOIN `products` as P ON P.`product_id`= OP.`product_id`
	WHERE OP.`order_id`='.$order['order_id']);
	if(!empty($products)){
		$out.='<div class="orderinfo"><table><thead><th>Наименование</th><th>Цена, руб</th><th>Количество</th><th>Сумма, руб.</th></thead><tbody>';
		$total = 0;
		foreach($products as $p){
			$sum = ceil($p['price'] * $p['count']);
			$total += $sum;
			$out.='
			<tr class="cart">
				<td class="orderProduct"><a href="/shop/UID_'.$p['product_id'].'.html">'.$p['name'].'</a></td>
				<td class="orderSum">'.$p['price'].'.00</td>
				<td class="orderCount">'.$p['count'].'</td>
				<td class="orderSum">'.$sum.'.00</td>
			</tr>';
		}
		$out.='<tr class="total"><td class="orderProduct" colspan="3">Итого, руб:</td><td class="orderSum">'.$total.'.00</td></tr>';
		if($order['delivery_cost']>0){
			$out.='<tr class="delivery"><td class="orderProduct" colspan="3">Доставка, руб:</td><td class="orderSum">'.ceil($order['delivery_cost']).'.00</td></tr>';
			$out.='<tr class="total"><td class="orderProduct" colspan="3">Всего, руб:</td><td class="orderSum">'.($total+ceil($order['delivery_cost'])).'.00</td></tr>';
		}
		$out.='</tbody></table></div>';
	}else{
		$out.='<table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ТОВАРЫ В ЗАКАЗЕ НЕ НАЙДЕНЫ</font></b></td></tr></table>';
	}
	
	return $out.'<br>';
}


/*
 * Список сообщений пользователя и администратора
 */
function mc_userMessages(){
	$client_id = Client::_getClientID();
	$db = Database::getInstance('main');
	$messages = $db->select('SELECT * FROM `tickets` WHERE `client_id`='.$client_id.' ORDER BY `ticket_id`');
	if(empty($messages)){
		$out='<br>В настоящий момент нет сообщений<br>';
	}else{
		$out='<div class="userMessagesHistory"><table cellpadding="1" cellspacing="1" width="100%"><thead><th class="date">Автор</td><th class="message">Сообщение</td></thead><tbody>';
		$i=0;
		foreach($messages as $m){
			$bg = ($i%2==0?'bgffffff':'bgdcdcdc');
			$out.='<tr><td width="150" class="'.$bg.'"><b>'.($m['is_support']>0?'DTBox.ru':'Вы').'</b><br>'.$m['timestamp'].'</td><td class="'.$bg.'"><b>'.$m['subject'].'</b><br>'.$m['message'].'</td></tr>';
			$i++;
		}
		$out.='</tbody></table></div><br>';
	}
	
	
	
	return $out;
}


/*
 * Построение прайс листа
 */
function mc_getPrice(){
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();
	$cat_found = false;
	$cat_name = '';
	$categories = $shop->categoryList(0,' &raquo; ');
	if(empty($categories)) return '<table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">К СОЖАЛЕНИЮ,<br>ПРАЙС-ЛИСТ ВРЕМЕННО НЕДОСТУПЕН</font></b></td></tr></table>';
	$out='<div style="width:100%;display:inline-block;"><select class="catalogPriceCategories" name="catId" id="catId" onChange="DoPrice();"><option value="ALL">Выберите каталог</option>';
	foreach($categories as $c){
		if($GLOBALS['PRICE_CID'] == $c[0]){
			$cat_found = true;
			$cat_name = $c[1];
			$out.='<option value="'.$c[0].'" selected="true">'.$c[1].'</option>';
		}else{
			$out.='<option value="'.$c[0].'">'.$c[1].'</option>';
		}
	}
	$out.='</select>';
	if(!$GLOBALS['PRICE_CID'] || ($GLOBALS['PRICE_CID'] > 0 && !$cat_found)){
		$GLOBALS['PRICE_CID'] = 0;
		$out='<div class="lightBox"><div class="title">Прайс-лист</div></div><br>'.$out;
		$cats = array();
	}else{
		$out='<div class="lightBox"><div class="fl_right layoutView"><a class="grid" href="/shop/CID_'.$GLOBALS['PRICE_CID'].'.html" title="Каталог товаров"></a></div><div class="title">'.$cat_name.'</div></div><br>'.$out;
		$cats = array(array($GLOBALS['PRICE_CID'], $cat_name));
	}

	$out.='<br><div class="catalogPrice">';

	if(!$GLOBALS['PRICE_CID']) $out.='<br><h3>Пожалуйста, выберите интересуемый каталог товаров в списке выше.</h3><br>';

	foreach($cats as $c){
		$products = $shop->categoryProducts($c[0],true,0,0);
		if(!empty($products)){
			$out.='<div class="categoryName">'.$c[1].'</div><table><thead><th>Наименование</th><th>Цена (руб.)</th><th colspan="2">Наличие</th><th>&nbsp;</th></thead><tbody>';
			foreach($products as $p){
				$count = 0;
				$price = 0;
				$bridge_info = ($p['bridge_id']>0 ? $shop->getBridgeInfo($p['bridge_id'], true) : null);
				if(!empty($bridge_info)){
					$price = $bridge_info['price'];
					$count = intval($bridge_info['count']);
				}else{
					$price = $shop->getPrice($p['currency'],$p['base_price'], true, $p['offer_discount']);
					$count = intval($p['count']);
				}

				$is_offer = $p['offer_discount']>0;
				$teko_cnt = $db->result('SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`='.$p['product_id'].' AND `warehouse_id` IN (3,5,7,9,10,11)');
				if($teko_cnt>0 && $teko_cnt == $count){
					$pcount = array(
						'class'	=> '0',
						'text'	=> 'Заказ 4-7 дней'
					);
				}else{
					$pcount = $shop->productCountInfo($count);
				}
				$out.='<tr>
						<td class="name'.($is_offer?' offer':'').'"><a href="/shop/UID_'.$p['product_id'].'.html" title="'.$p['name'].'" target="_blank">'.($is_offer?'<img src="/client/images/offer2.png" width="16" height="16">':'').$p['name'].'</a></td>
						<td class="price">'.$price.'.00</td>
						<td class="count_pic"><img src="/client/images/pb'.$pcount['class'].'.png" border="0"></td>
						<td class="count_text"><small>'.$pcount['text'].'</small></td>
						<td class="cart"><a href="javascript:AddToCart('.$p['product_id'].')"><img src="/client/images/dtbox/cart_add.gif" hspace="" align="absMiddle" border="0"></a></td>
					</tr>';
			}
		$out.='</tbody></table><br>';
		}
	}

	$out.='</div></div>';

	return $out;
}


/*
 * Построение карты сайта
 */
function mc_getMapThree(&$node,&$out){
	foreach($node as $n){
		$out.='<li><a href="/shop/CID_'.$n['category_id'].'.html">'.$n['name'].'</a>';
		if(!empty($node['childs'])){
			$out.='<ul>';
			mc_getMapThree($node['childs'], $out);
			$out.='</ul>ul>';
		}
		$out.='</li>';
	}
}
function mc_getMap(){
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();
	$categories = $shop->categoryTree(0);
	if(empty($categories)) return '<table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">К СОЖАЛЕНИЮ,<br>КАРТА МАГАЗИНА ВРЕМЕННО НЕДОСТУПНА</font></b></td></tr></table>';
	$out='<div class="lightBox"><div class="title">Карта магазина</div></div><div class="sitemap">';
	foreach($categories as $c){
		if(empty($c['childs'])) continue;
		$out.='<br><b>'.$c['name'].'</b><ul>';
		mc_getMapThree($c['childs'],$out);
		$out.='</ul>';
	}
	$out.='</div>';
	return $out;
}



/*
 * Построение списка новостей
 */
function mc_getNews($limit=0){
	$limit = intval($limit);
	$db = Database::getInstance('main');
	$news = $db->select('SELECT * FROM `news` WHERE `enabled`>0 ORDER BY `date` DESC'.($limit>0?' LIMIT '.$limit:''));
	if(empty($news)) return '<table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">К СОЖАЛЕНИЮ,<br>ПОКА НЕТ НОВОСТЕЙ</font></b></td></tr></table>';
	$out = '<div class="lightBox"><div class="title"><b>Новости</b></div></div><div class="newslist">';

	foreach($news as $n){
		$out.='<div class="newsitem"><div class="newsheader"><div class="date">'.sql2date($n['date']).'</div><div class="title"><b>'.$n['theme'].'</b></div></div><div class="newstext">'.$n['content'].'</div></div>';
	}

	$out.='</div>';
	return $out;
}


/*
 * Построение формы поиска
 */
function mc_searchForm(){
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();
	if(empty($GLOBALS['SEARCH_TERM'])) $GLOBALS['SEARCH_TERM'] = '';
	if(empty($GLOBALS['SEARCH_CID'])) $GLOBALS['SEARCH_CID'] = 0;
	if(empty($GLOBALS['SEARCH_SET'])) $GLOBALS['SEARCH_SET'] = 2;
	if(empty($GLOBALS['SEARCH_CF'])) $GLOBALS['SEARCH_CF'] = 'all';
	if(empty($GLOBALS['SEARCH_SA'])) $GLOBALS['SEARCH_SA'] = 0;
	if(empty($GLOBALS['SEARCH_SN'])) $GLOBALS['SEARCH_SN'] = 1;
	if(empty($GLOBALS['SEARCH_SD'])) $GLOBALS['SEARCH_SD'] = 0;
	if(empty($GLOBALS['SEARCH_SP'])) $GLOBALS['SEARCH_SP'] = 0;


	//Логирование запроса
	$db->addRecord('search_log',array(
		'client_id'		=> Client::_getClientId(),
		'term'			=> $GLOBALS['SEARCH_TERM'],
		'referer'		=> (!empty($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : ''),
		'ip_addr'		=> (!empty($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '')
	));


	$words = strtolower($GLOBALS['SEARCH_TERM']);
	$words = str_replace(array('\\','+','*','?','[',']','^','(',')','{','}','=','!','<','>','|',':','-','\'','"','union','select','insert','delete'), ' ',$words);
	$words = trim(preg_replace('/\s\s+/', ' ', $words));
	$words = $db->getQuotedValue($words, false);

	$set = intval($GLOBALS['SEARCH_SET']);
	$cat = intval($GLOBALS['SEARCH_CID']);
	$cf_all = ($GLOBALS['SEARCH_CF'] == 'all' ? true : false);
	$search_articul		= false;
	$search_name		= true;
	$search_desc		= ($GLOBALS['SEARCH_SD']==1 ? true : false);
	$search_part		= ($GLOBALS['SEARCH_SP']==1 ? true : false);


	$enabled_cats = array();
	$categories = $shop->categoryList(0,' &raquo; ');
	$cat_options='';
	foreach($categories as $c){
		$enabled_cats[$c[0]] = $c[1];
		if($GLOBALS['SEARCH_CID'] == $c[0]){
			$cat_options.='<option value="'.$c[0].'" selected="true">'.$c[1].'</option>';
		}else{
			$cat_options.='<option value="'.$c[0].'">'.$c[1].'</option>';
		}
	}

	$out='
	<form method="post" id="searchform" action="/search/">
		<input type="hidden" value="1" name="us">
		<div class="searchblock">
			<div class="seachline">
				<div class="search_term">
					<input type="text" class="input" name="words" value="'.htmlentities($GLOBALS['SEARCH_TERM']).'" placeholder="Введите название искомого товара..." size="10" maxlength="300" class="go fl_left">
				</div>
				<div class="search_button">
					<button type="submit" class="" role="button" aria-disabled="false"><span>Найти</span></button>
				</div>
			</div>
			<div class="seachline" style="height:8px;"></div>
			<div class="seachline">
				<select class="catalogPriceCategories" name="cid" id="cid""><option value="0">Все разделы</option>'.$cat_options.'</select>
			</div>
			<div class="seachline">
				<select name="set" style="width:234px;">
					<option value="1" '.($set==1?' selected="selected"':'').'>Точное совпадение</option>
					<option value="2" '.($set!=1&&$set!=3?' selected="selected"':'').'>Совпадение всех слов</option>
					<option value="3" '.($set==3?' selected="selected"':'').'>Совпадение любого из слов</option>
				</select>
				<select name="cf" style="width:300px;">
					<option value="all" '.($cf_all?' selected="selected"':'').'>Искать все товары</option>
					<option value="noempty" '.(!$cf_all?' selected="selected"':'').'>Искать только товары в наличии</option>
				</select>
			</div>
			<div class="seachline">
				<input type="checkbox" id="si" name="si" value="1" checked="true" disabled="disabled"><label for="si">Поиск по идентификатору</label><span>|</span>
				<input type="checkbox" id="sn" name="sn" value="1" checked="true" disabled="disabled"><label for="sn">Поиск в названии</label><span>|</span>
				<input type="checkbox" id="sd" name="sd" value="1" '.($search_desc?' checked="true"':'').'><label for="sd">Поиск в описании</label><span>|</span>
				<input type="checkbox" id="sp" name="sp" value="1" '.($search_part?' checked="true"':'').'><label for="sp">Поиск по парт-номерам</label>
			</div>
		</div>
	</form>';


	$filter = 'P.`enabled`>0';

	switch($set){

		case 1:
			$s =(is_numeric($words)?"(P.`product_id` = '$words' ":'');
			if($search_articul)  $s.=(empty($s)?"(":" or ") . "P.`article` REGEXP '$words' ";
			if($search_name) $s.=(empty($s)?"(":" or ") ." P.`name` REGEXP '$words'";
			if($search_desc) $s.=(empty($s)?"(":" or ") ." PI.`description` REGEXP '$words'";
			if($search_part) $s.=(empty($s)?"(":" or ") ." PI.`part_nums` REGEXP '$words'";
			if(!empty($s)) $filter .= ' AND '. $s.')';
		break;

		case 2:
			$_WORDS = explode(" ", $words);
			$sql_name = '';
			$sql_description = '';
			$sql_keywords = '';
			$sql_article = '';
			$sql_partnums = '';
			$sql_id = '';
			foreach ($_WORDS as $w){
				$w=trim($w);if(empty($w)) continue;
				$sql_id =(is_numeric($w)&&count($_WORDS)==1?"(P.`product_id` = '$w')":'');
				$sql_article.=(empty($sql_article)?"(":" and ") . "P.`article` REGEXP '$w'";
				$sql_name.=(empty($sql_name)?"(":" and ") . "P.`name` REGEXP '$w'";
				$sql_description.=(empty($sql_description)?"(":" and ") . "PI.`description` REGEXP '$w'";
				$sql_partnums.=(empty($sql_partnums)?"(":" and ") . "PI.`part_nums` LIKE '$w'";
			}
			$sql_article.=(empty($sql_article)?"":")");
			$sql_name.=(empty($sql_name)?"":")");
			$sql_description.=(empty($sql_description)?"":")");
			$sql_partnums.=(empty($sql_partnums)?"":")");
			$s = '';
			if(!empty($sql_id)) $s.= (empty($s)?"(":" or ") . $sql_id;
			if($search_articul&&!empty($sql_article)) $s.= (empty($s)?"(":" or ") . $sql_article;
			if($search_name&&!empty($sql_name)) $s.= (empty($s)?"(":" or ") . $sql_name;
			if($search_desc&&!empty($sql_description)) $s.= (empty($s)?"(":" or ") . $sql_description;
			if($search_part&&!empty($sql_partnums)) $s.= (empty($s)?"(":" or ") . $sql_partnums;
			if(!empty($s)) $filter .= ' AND '. $s.')';
		break;

		case 3:
			$_WORDS = explode(" ", $words);
			$sql_name = '';
			$sql_partnums = '';
			$sql_description = '';
			$sql_keywords = '';
			$sql_article = '';
			$sql_id = '';
			foreach ($_WORDS as $w){
				$w=trim($w);if(empty($w)) continue;
				$sql_id =(is_numeric($w)&&count($_WORDS)==1?"(P.`product_id` = '$w')":'');
				$sql_article.=(empty($sql_article)?"(":" or ") . "P.`article` REGEXP '$w'";
				$sql_name.=(empty($sql_name)?"(":" or ") . "P.`name` REGEXP '$w'";
				$sql_description.=(empty($sql_description)?"(":" or ") . "PI.`description` REGEXP '$w'";
				$sql_partnums.=(empty($sql_partnums)?"(":" or ") . "PI.`part_nums` REGEXP '$w'";
			}
			$sql_article.=(empty($sql_article)?"":")");
			$sql_name.=(empty($sql_name)?"":")");
			$sql_description.=(empty($sql_description)?"":")");
			$sql_partnums.=(empty($sql_partnums)?"":")");
			$s = '';
			if(!empty($sql_id)) $s.= (empty($s)?"(":" or ") . $sql_id;
			if($search_articul&&!empty($sql_article)) $s.= (empty($s)?"(":" or ") . $sql_article;
			if($search_name&&!empty($sql_name)) $s.= (empty($s)?"(":" or ") . $sql_name;
			if($search_desc&&!empty($sql_description)) $s.= (empty($s)?"(":" or ") . $sql_description;
			if($search_part&&!empty($sql_partnums)) $s.= (empty($s)?"(":" or ") . $sql_partnums;
			if(!empty($s)) $filter .= ' AND '. $s.')';
		break;
	}

	if($cat>0) $filter.=' AND P.`category_id`='.$cat;

	$sql = 
	'SELECT 
		P.`product_id` as `product_id`,
		P.`bridge_id` as `bridge_id`,
		P.`category_id` as `category_id`,
		P.`name` as `name`,
		P.`article` as `article`,
		P.`currency` as `currency`,
		P.`base_price` as `base_price`,
		(SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=P.`product_id`) as `count`
	FROM `products` as P 
		INNER JOIN `product_info` as PI ON PI.`product_id` = P.`product_id`
		INNER JOIN `categories` as C ON C.`category_id` = P.`category_id`
	WHERE '.$filter.' AND C.`enabled`>0 ORDER BY P.`name` LIMIT 1000';

	//$out.="<!--\n".$sql."\n-->";

	$data = $db->select($sql);
	if(empty($data)) return $out. '<table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">К СОЖАЛЕНИЮ НИЧЕГО НЕ НАЙДЕНО</font></b></td></tr></table>';
	$products_count = 0;
	$categories_count = 0;
	$dis='';
	$results=array();
	foreach($data as $row){
		if(!array_key_exists($row['category_id'], $enabled_cats)) continue;
		$bridge_info = ($row['bridge_id']>0 ? $shop->getBridgeInfo($row['bridge_id'], true) : null);
		if(!empty($bridge_info)){
			$price = $bridge_info['price'];
			$count = intval($bridge_info['count']);
		}else{
			$price = $shop->getPrice($row['currency'],$row['base_price'], true);
			$count = intval($row['count']);
		}
		if((empty($count)) && !$cf_all) continue;

		$teko_cnt = $db->result('SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`='.$row['product_id'].' AND `warehouse_id` IN (3,5,7,9)');
		if($teko_cnt>0 && $teko_cnt == $count && !$cf_all) continue;

		if(empty($results[$row['category_id']])){
			$results[$row['category_id']] = array();
			$categories_count++;
		}
		$results[$row['category_id']][]=array(
			'product_id'=> $row['product_id'],
			'article'	=> $row['article'],
			'name'		=> $row['name'],
			'price'		=> $price,
			'count'		=> $count 
		);
		$products_count++;
	}

	$out.='<div class="darkBox"><div class="title"><b>Результаты поиска: '.$products_count.' товаров из '.$categories_count.' категорий</b></div></div><br><div class="catalogPrice">';

	foreach($results as $cid=>$products){
		$out.='<div class="categoryName">'.$enabled_cats[$cid].'</div><table><thead><th>ID</th><th>Наименование</th><th>Цена (руб.)</th><th colspan="2">Наличие</th><th>&nbsp;</th></thead><tbody>';
		foreach($products as $p){
			$teko_cnt = $db->result('SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`='.$p['product_id'].' AND `warehouse_id` IN (5,3,7,9,10,11)');
			if($teko_cnt>0 && $teko_cnt == $p['count']){
				$pcount = array(
					'class'	=> '0',
					'text'	=> 'Заказ 4-7 дней'
				);
			}else{
				$pcount = $shop->productCountInfo($p['count']);
			}
			$out.='<tr>
					<td class="id">'.$p['product_id'].'</td>
					<td class="name"><a href="/shop/UID_'.$p['product_id'].'.html" target="_blank">'.$p['name'].'</a></td>
					<td class="price">'.$p['price'].'.00</td>
					<td class="count_pic"><img src="/client/images/pb'.$pcount['class'].'.png" border="0"></td>
					<td class="count_text"><small>'.$pcount['text'].'</small></td>
					<td class="cart"><a href="javascript:AddToCart('.$p['product_id'].')"><img src="/client/images/dtbox/cart_add.gif" hspace="" align="absMiddle" border="0"></a></td>
				</tr>';
		}
		$out.='</tbody></table><br>';
	}

	$out.='</div>';

	return $out;
}



/*
 * Регистрация нового клиента
 */
function mc_getRegistration(){
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();
	$out='';
	return $out;
}



/*
 * Форма, счет
 */
function mc_orderDocsBill($template){
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$email = htmlspecialchars($request->getEmail('email', ''));
	$order_num = htmlspecialchars($request->getStr('order', ''));
	$account_code = htmlspecialchars($request->getStr('account_code', ''));
	$not_found = false;
	if(empty($order_num)||empty($email)) $not_found = true;

	if(!$not_found){
		$order_data = explode('-',$order_num);
		$order_id = intval($order_data[0]);
		if(empty($order_id)) $not_found = true;
	}

	if(!$not_found){
		$db->prepare('SELECT * FROM `orders` WHERE `order_id`=? AND `order_num` LIKE ? AND `email` LIKE ? LIMIT 1');
		$db->bind($order_id);
		$db->bind($order_num);
		$db->bind($email);
		$order = $db->selectRecord();
		if(empty($order)||!is_array($order)) $not_found = true;
	}

	if($not_found){
		$template->assign('order_error', '<div class="lightBox"><div class="title">Информация о заказе</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ЗАКАЗ НЕ НАЙДЕН</font></b><br><font color="black" style="font-size:12px">К сожалению, данный заказ не найден</font></td></tr></table>');
		$template->setTemplate('Main/templates/order_error.tpl');
		$template->display();
		return;
	}

	$buyer = trim($request->getStr('buyer',''));
	if(empty($buyer)){
		$order['company'] = trim($order['company']);
		$buyer = (empty($order['company']) ? $order['name'] : $order['company']);
	}

	$shop = Shop::getInstance();

	if(empty($account_code)){
		$account_code = $shop->getConfigValue(($order['paymethod']=='cash' ? 'orderBillCash' : 'orderBillWire'));
	}

	$account = $db->selectRecord('SELECT * FROM `accounts` WHERE `code` LIKE "'.addslashes($account_code).'" LIMIT 1');
	if(empty($account)){
		$account = $db->selectRecord('SELECT * FROM `accounts` ORDER BY `account_id` LIMIT 1');
	}

	//$delivery = $shop->deliveryExists($order['delivery_id']);

	$products = $db->select(
	'SELECT 
		OP.`product_id` as `product_id`,
		P.`name` as `name`,
		OP.`price` as `price`,
		OP.`count` as `count`
	FROM `order_products` as OP 
	INNER JOIN `products` as P ON P.`product_id`= OP.`product_id`
	WHERE OP.`order_id`='.$order['order_id']
	);

	$tbl='';
	$item_index = 0;
	$total = 0;
	foreach($products as $p){
		$sum = ceil($p['price'] * $p['count']);
		$total += $sum;
		$item_index++;
		$tbl.=
		'<tr style="height:28px;"><th style="height: 28px;" class="row-headers-background"><div class="row-header-wrapper" style="line-height: 28px;"></div></th>'.
		'<td class="s3"></td>'.
		'<td class="s15" colspan="2">'.$item_index.'</td>'.
		'<td class="s16" colspan="17">'.$p['name'].'</td>'.
		'<td class="s17" colspan="4">'.$p['count'].'</td>'.
		'<td class="s18" colspan="3">шт</td>'.
		'<td class="s17" colspan="6">'.$p['price'].'.00</td>'.
		'<td class="s17" colspan="5">'.$sum.'.00</td>'.
		'</tr>';
	}

	$order['delivery_cost'] = ceil(floatval($order['delivery_cost']));
	if($order['delivery_cost']>0){
		$item_index++;
		$tbl.=
		'<tr style="height:28px;"><th style="height: 28px;" class="row-headers-background"><div class="row-header-wrapper" style="line-height: 28px;"></div></th>'.
		'<td class="s3"></td>'.
		'<td class="s15" colspan="2">'.$item_index.'</td>'.
		'<td class="s16" colspan="17">Доставка</td>'.
		'<td class="s17" colspan="4">1</td>'.
		'<td class="s18" colspan="3">шт</td>'.
		'<td class="s17" colspan="6">'.$order['delivery_cost'].'.00</td>'.
		'<td class="s17" colspan="5">'.$order['delivery_cost'].'.00</td>'.
		'</tr>';
	}

	$total = $total + $order['delivery_cost'];
	$nds = round(round($total * 0.18  / 1.18,3),2);

	$template->assign('orderNum',$order['order_num']);
	$template->assign('orderDate',sql2date($order['timestamp']));
	$template->assign('orderBankName',$account['bank_name']);
	$template->assign('orderBankBik',$account['bank_bik']);
	$template->assign('orderBankAccount',$account['bank_account']);
	$template->assign('orderBankAccountCorr',$account['bank_account_corr']);
	$template->assign('orderCompany',$account['company']);
	$template->assign('orderInn',$account['inn']);
	$template->assign('orderKpp',$account['kpp']);
	$template->assign('orderPhone',$account['phone']);
	$template->assign('orderAddress',$account['address']);
	$template->assign('orderSignName',$account['sign_name']);
	$template->assign('orderSignPost',$account['sign_post']);
	$template->assign('orderNds',$nds);
	$template->assign('orderTotal',$total.'.00');
	$template->assign('orderItems',$item_index);
	$template->assign('orderTotalText',num2str($total));
	$template->assign('orderProductList',$tbl);
	$template->assign('orderBuyer',$buyer);

	$template->setTemplate('Main/templates/docs/bill.html');
	$template->display();
	return;
}


/*
 * Форма, счет-фактура
 */
function mc_orderDocsInvoice($template){
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$email = htmlspecialchars($request->getEmail('email', ''));
	$order_num = htmlspecialchars($request->getStr('order', ''));
	$account_code = htmlspecialchars($request->getStr('account_code', ''));
	$not_found = false;
	if(empty($order_num)||empty($email)) $not_found = true;

	if(!$not_found){
		$order_data = explode('-',$order_num);
		$order_id = intval($order_data[0]);
		if(empty($order_id)) $not_found = true;
	}

	if(!$not_found){
		$db->prepare('SELECT * FROM `orders` WHERE `order_id`=? AND `order_num` LIKE ? AND `email` LIKE ? LIMIT 1');
		$db->bind($order_id);
		$db->bind($order_num);
		$db->bind($email);
		$order = $db->selectRecord();
		if(empty($order)||!is_array($order)) $not_found = true;
	}

	if($not_found){
		$template->assign('order_error', '<div class="lightBox"><div class="title">Информация о заказе</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ЗАКАЗ НЕ НАЙДЕН</font></b><br><font color="black" style="font-size:12px">К сожалению, данный заказ не найден</font></td></tr></table>');
		$template->setTemplate('Main/templates/order_error.tpl');
		$template->display();
		return;
	}

	$buyer = trim($request->getStr('buyer',''));
	if(empty($buyer)){
		$order['company'] = trim($order['company']);
		$buyer = (empty($order['company']) ? $order['name'] : $order['company']);
	}

	$buyerAddress = trim($request->getStr('buyerAddress',''));
	if(empty($buyerAddress)){
		$buyerAddress = $order['address'];
	}

	$buyerInn = trim($request->getStr('buyerInn',''));
	if(empty($buyerInn)){
		$buyerInn = $order['inn'];
	}

	$buyerKpp = trim($request->getStr('buyerKpp',''));
	if(empty($buyerKpp)){
		$buyerKpp = $order['kpp'];
	}

	$orderNum = trim($request->getStr('orderNum',''));
	if(empty($orderNum)){
		$orderNum = $order['order_num'];
	}

	$orderDate = trim($request->getStr('orderDate',''));
	if(empty($orderDate)){
		$orderDate = sql2date($order['timestamp']);
	}

	$orderDocNum = trim($request->getStr('orderDocNum',''));
	if(empty($orderDocNum)){
		$orderDocNum = $order['order_num'];
	}

	$orderDocDate = trim($request->getStr('orderDocDate',''));
	if(empty($orderDocDate)){
		$orderDocDate = sql2date($order['timestamp']);
	}

	$shop = Shop::getInstance();

	if(empty($account_code)){
		$account_code = $shop->getConfigValue(($order['paymethod']=='cash' ? 'orderBillCash' : 'orderBillWire'));
	}

	$account = $db->selectRecord('SELECT * FROM `accounts` WHERE `code` LIKE "'.addslashes($account_code).'" LIMIT 1');
	if(empty($account)){
		$account = $db->selectRecord('SELECT * FROM `accounts` ORDER BY `account_id` LIMIT 1');
	}

	//$delivery = $shop->deliveryExists($order['delivery_id']);

	$products = $db->select(
	'SELECT 
		OP.`product_id` as `product_id`,
		P.`name` as `name`,
		OP.`price` as `price`,
		OP.`count` as `count`
	FROM `order_products` as OP 
	INNER JOIN `products` as P ON P.`product_id`= OP.`product_id`
	WHERE OP.`order_id`='.$order['order_id']
	);

	$tbl='';
	$item_index = 0;
	$total_nds = 0;
	$total_price = 0;
	$total_sum = 0;
	foreach($products as $p){
		$item_index++;

		$item_nds = round(round($p['price'] * 0.18  / 1.18 ,3),2);
		$item_price = $p['price'] - $item_nds;
		$item_sum = $item_price * $p['count'];
		$item_nds = $item_nds * $p['count'];
		$sum = ceil($p['price'] * $p['count']);

		$total_nds+=$item_nds;
		$total_price+=$item_sum;
		$total_sum+=$sum;

		$tbl.=
		'<tr style="height:20px;"><th id="0R16" style="height: 20px;" class="row-headers-background"><div class="row-header-wrapper" style="line-height: 20px;"></div></th>'.
		'<td class="s6 bltd" dir="ltr">'.$p['name'].'</td>'.
		'<td class="s4" dir="ltr">796</td>'.
		'<td class="s4" dir="ltr">шт</td>'.
		'<td class="s4" dir="ltr">'.intval($p['count']).'</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_price).'</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_sum).'</td>'.
		'<td class="s4" dir="ltr">без акциза</td>'.
		'<td class="s4" dir="ltr">18%</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_nds).'</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$sum).'</td>'.
		'<td class="s6"></td><td class="s6"></td><td class="s6"></td></tr>';
	}

	$order['delivery_cost'] = ceil(floatval($order['delivery_cost']));
	if($order['delivery_cost']>0){
		$item_index++;
		$item_nds = round(round($order['delivery_cost'] * 0.18  / 1.18 ,3),2);
		$item_price = $order['delivery_cost'] - $item_nds;
		$item_sum = $item_price;
		$item_nds = $item_nds;
		$sum = $order['delivery_cost'];
		$total_nds+=$item_nds;
		$total_price+=$item_sum;
		$total_sum+=$sum;
		$tbl.=
		'<tr style="height:20px;"><th id="0R16" style="height: 20px;" class="row-headers-background"><div class="row-header-wrapper" style="line-height: 20px;"></div></th>'.
		'<td class="s6 bltd" dir="ltr">'.$p['name'].'</td>'.
		'<td class="s4" dir="ltr">796</td>'.
		'<td class="s4" dir="ltr">шт</td>'.
		'<td class="s4" dir="ltr">1</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_price).'</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_sum).'</td>'.
		'<td class="s4" dir="ltr">без акциза</td>'.
		'<td class="s4" dir="ltr">18%</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$item_nds).'</td>'.
		'<td class="s7" dir="ltr">'.sprintf("%01.2f",$sum).'</td>'.
		'<td class="s6"></td><td class="s6"></td><td class="s6"></td></tr>';
	}

	$template->assign('orderNum',$orderNum);
	$template->assign('orderDate', $orderDate);
	$template->assign('orderDocNum',$orderDocNum);
	$template->assign('orderDocDate', $orderDocDate);
	$template->assign('orderBankName',$account['bank_name']);
	$template->assign('orderBankBik',$account['bank_bik']);
	$template->assign('orderBankAccount',$account['bank_account']);
	$template->assign('orderBankAccountCorr',$account['bank_account_corr']);
	$template->assign('orderCompany',$account['company']);
	$template->assign('orderInn',$account['inn']);
	$template->assign('orderKpp',$account['kpp']);
	$template->assign('orderPhone',$account['phone']);
	$template->assign('orderAddress',$account['address']);
	$template->assign('orderSignName',$account['sign_name']);
	$template->assign('orderSignPost',$account['sign_post']);
	$template->assign('orderItems',$item_index);
	$template->assign('orderProductList',$tbl);
	$template->assign('orderBuyer',$buyer);
	$template->assign('orderBuyerAddress',$buyerAddress);
	$template->assign('orderBuyerFull',$buyer.(!empty($buyerAddress)?', '.$buyerAddress:''));
	$template->assign('orderBuyerInn',$buyerInn);
	$template->assign('orderBuyerKpp',$buyerKpp);

	$template->assign('orderTotalNds',sprintf("%01.2f",$total_nds));
	$template->assign('orderTotalPrice',sprintf("%01.2f",$total_price));
	$template->assign('orderTotalSum',sprintf("%01.2f",$total_sum));

	$template->setTemplate('Main/templates/docs/invoice.html');
	$template->display();
	return;
}




/*
 * Форма, товарный чек
 */
function mc_orderDocsCheck($template){
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$email = htmlspecialchars($request->getEmail('email', ''));
	$order_num = htmlspecialchars($request->getStr('order', ''));
	$account_code = htmlspecialchars($request->getStr('account_code', ''));
	$not_found = false;
	if(empty($order_num)||empty($email)) $not_found = true;

	if(!$not_found){
		$order_data = explode('-',$order_num);
		$order_id = intval($order_data[0]);
		if(empty($order_id)) $not_found = true;
	}

	if(!$not_found){
		$db->prepare('SELECT * FROM `orders` WHERE `order_id`=? AND `order_num` LIKE ? AND `email` LIKE ? LIMIT 1');
		$db->bind($order_id);
		$db->bind($order_num);
		$db->bind($email);
		$order = $db->selectRecord();
		if(empty($order)||!is_array($order)) $not_found = true;
	}

	if($not_found){
		$template->assign('order_error', '<div class="lightBox"><div class="title">Информация о заказе</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ЗАКАЗ НЕ НАЙДЕН</font></b><br><font color="black" style="font-size:12px">К сожалению, данный заказ не найден</font></td></tr></table>');
		$template->setTemplate('Main/templates/order_error.tpl');
		$template->display();
		return;
	}

	$orderDate = trim($request->getStr('orderDate',''));
	if(empty($orderDate)){
		$orderDate = sql2date($order['timestamp']);
	}

	$orderNum = trim($request->getStr('orderNum',''));
	if(empty($orderNum)){
		$orderNum = $order['order_num'];
	}

	$shop = Shop::getInstance();

	if(empty($account_code)){
		$account_code = $shop->getConfigValue(($order['paymethod']=='cash' ? 'orderBillCash' : 'orderBillWire'));
	}

	$account = $db->selectRecord('SELECT * FROM `accounts` WHERE `code` LIKE "'.addslashes($account_code).'" LIMIT 1');
	if(empty($account)){
		$account = $db->selectRecord('SELECT * FROM `accounts` ORDER BY `account_id` LIMIT 1');
	}

	//$delivery = $shop->deliveryExists($order['delivery_id']);

	$products = $db->select(
	'SELECT 
		OP.`product_id` as `product_id`,
		P.`name` as `name`,
		OP.`price` as `price`,
		OP.`count` as `count`
	FROM `order_products` as OP 
	INNER JOIN `products` as P ON P.`product_id`= OP.`product_id`
	WHERE OP.`order_id`='.$order['order_id']
	);

	$tbl='';
	$item_index = 0;
	$total = 0;
	foreach($products as $p){
		$sum = ceil($p['price'] * $p['count']);
		$total += $sum;
		$item_index++;
		$tbl.=
			'<tr style="height:20px;">'.
			'<th id="0R10" style="height: 20px;" class="row-headers-background">'.
			'<div class="row-header-wrapper" style="line-height: 20px;"></div></th>'.
			'<td class="s5 bltd" dir="ltr">'.$item_index.'</td>'.
			'<td class="s6" dir="ltr">'.$p['name'].'</td>'.
			'<td class="s5" dir="ltr">шт.</td>'.
			'<td class="s5" dir="ltr">'.$p['count'].'</td>'.
			'<td class="s7" dir="ltr">'.$p['price'].'.00</td>'.
			'<td class="s7" dir="ltr">'.$sum.'.00</td>'.
			'</tr>';
	}

	$order['delivery_cost'] = ceil(floatval($order['delivery_cost']));
	if($order['delivery_cost']>0){
		$item_index++;
		$tbl.=
			'<tr style="height:20px;">'.
			'<th id="0R10" style="height: 20px;" class="row-headers-background">'.
			'<div class="row-header-wrapper" style="line-height: 20px;"></div></th>'.
			'<td class="s5 bltd" dir="ltr">'.$item_index.'</td>'.
			'<td class="s6" dir="ltr">Доставка</td>'.
			'<td class="s5" dir="ltr">шт.</td>'.
			'<td class="s5" dir="ltr">1</td>'.
			'<td class="s7" dir="ltr">'.$order['delivery_cost'].'.00</td>'.
			'<td class="s7" dir="ltr">'.$order['delivery_cost'].'.00</td>'.
			'</tr>';
	}

	$total = $total + $order['delivery_cost'];
	$nds = round(round($total * 0.18  / 1.18,3),2);

	$template->assign('orderNum',$orderNum);
	$template->assign('orderDate',$orderDate);
	$template->assign('orderOgrn',$account['ogrn']);
	$template->assign('orderBankName',$account['bank_name']);
	$template->assign('orderBankBik',$account['bank_bik']);
	$template->assign('orderBankAccount',$account['bank_account']);
	$template->assign('orderBankAccountCorr',$account['bank_account_corr']);
	$template->assign('orderCompany',$account['company']);
	$template->assign('orderInn',$account['inn']);
	$template->assign('orderKpp',$account['kpp']);
	$template->assign('orderPhone',$account['phone']);
	$template->assign('orderAddress',$account['address']);
	$template->assign('orderAddressReal',$account['address_real']);
	$template->assign('orderSignName',$account['sign_name']);
	$template->assign('orderSignPost',$account['sign_post']);
	$template->assign('orderNds',$nds);
	$template->assign('orderTotal',$total.'.00');
	$template->assign('orderItems',$item_index);
	$template->assign('orderTotalText',num2str($total));
	$template->assign('orderProductList',$tbl);

	$template->setTemplate('Main/templates/docs/check.html');
	$template->display();
	return;
}



/*
 * Форма, Торг12
 */
function mc_orderDocsTorg12($template){
	$db = Database::getInstance('main');
	$request = Request::getInstance();
	$email = htmlspecialchars($request->getEmail('email', ''));
	$order_num = htmlspecialchars($request->getStr('order', ''));
	$account_code = htmlspecialchars($request->getStr('account_code', ''));
	$not_found = false;
	if(empty($order_num)||empty($email)) $not_found = true;

	if(!$not_found){
		$order_data = explode('-',$order_num);
		$order_id = intval($order_data[0]);
		if(empty($order_id)) $not_found = true;
	}

	if(!$not_found){
		$db->prepare('SELECT * FROM `orders` WHERE `order_id`=? AND `order_num` LIKE ? AND `email` LIKE ? LIMIT 1');
		$db->bind($order_id);
		$db->bind($order_num);
		$db->bind($email);
		$order = $db->selectRecord();
		if(empty($order)||!is_array($order)) $not_found = true;
	}

	if($not_found){
		$template->assign('order_error', '<div class="lightBox"><div class="title">Информация о заказе</div></div><br><table width="100%" align="center"><tr><td align="center" style="padding:50"><IMG src="/client/images/dtbox/empty_cart.png" height="100" border="0"><br><br><b style=""><font color="red" style="font-size:14px">ЗАКАЗ НЕ НАЙДЕН</font></b><br><font color="black" style="font-size:12px">К сожалению, данный заказ не найден</font></td></tr></table>');
		$template->setTemplate('Main/templates/order_error.tpl');
		$template->display();
		return;
	}

	$buyer = trim($request->getStr('buyer',''));
	$payer = trim($request->getStr('payer',''));
	$buyerOkpo = trim($request->getStr('buyerOkpo',''));
	$payerOkpo = trim($request->getStr('payerOkpo',''));
	$why = trim($request->getStr('why',''));

	$orderNum = trim($request->getStr('orderNum',''));
	if(empty($orderNum)){
		$orderNum = $order['order_num'];
	}

	$orderDate = trim($request->getStr('orderDate',''));
	if(empty($orderDate)){
		$orderDate = sql2date($order['timestamp']);
	}

	$shop = Shop::getInstance();

	if(empty($account_code)){
		$account_code = $shop->getConfigValue(($order['paymethod']=='cash' ? 'orderBillCash' : 'orderBillWire'));
	}

	$account = $db->selectRecord('SELECT * FROM `accounts` WHERE `code` LIKE "'.addslashes($account_code).'" LIMIT 1');
	if(empty($account)){
		$account = $db->selectRecord('SELECT * FROM `accounts` ORDER BY `account_id` LIMIT 1');
	}

	//$delivery = $shop->deliveryExists($order['delivery_id']);

	$products = $db->select(
	'SELECT 
		OP.`product_id` as `product_id`,
		P.`name` as `name`,
		OP.`price` as `price`,
		OP.`count` as `count`
	FROM `order_products` as OP 
	INNER JOIN `products` as P ON P.`product_id`= OP.`product_id`
	WHERE OP.`order_id`='.$order['order_id']
	);

	//Индивидуальный предприниматель Хоршева Юлия Александровна, ИНН 616605539320, свидетельство 61 №007300008 от 25.01.2011, 344056, Ростовская обл., Ростов-на-Дону гор., 2 Киргизская ул., дом № 58/19, 
	//тел.: 2904-006, р/с 40802810600000014788, в банке ОАО КБ "ЦЕНТР-ИНВЕСТ", БИК 046015762, к/с 30101810100000000762

	$fullAccount = $account['company'];
	if(!empty($account['inn'])) $fullAccount .= ', ИНН: '.$account['inn'];
	if(!empty($account['certificate'])) $fullAccount .= ', '.$account['certificate'];
	if(!empty($account['address'])) $fullAccount .= ', '.$account['address'];
	if(!empty($account['phone'])) $fullAccount .= ', тел.: '.$account['phone'];
	if(!empty($account['bank_account'])) $fullAccount .= ', р/с '.$account['bank_account'];
	if(!empty($account['bank_name'])) $fullAccount .= ' в банке '.$account['bank_name'];
	if(!empty($account['bank_bik'])) $fullAccount .= ', БИК '.$account['bank_bik'];
	if(!empty($account['bank_account_corr'])) $fullAccount .= ', к/с '.$account['bank_account_corr'];

	$tbl='';
	$item_index = 0;
	$total_nds = 0;
	$total_price = 0;
	$total_sum = 0;
	$total_count = 0;
	foreach($products as $p){
		$item_index++;

		$item_nds = round(round($p['price'] * 0.18 / 1.18,3),2);
		$item_price = $p['price'] - $item_nds;
		$item_sum = $item_price * $p['count'];
		$item_nds = $item_nds * $p['count'];
		$sum = ceil($p['price'] * $p['count']);

		$total_nds+=$item_nds;
		$total_price+=$item_sum;
		$total_sum+=$sum;
		$total_count+=$p['count'];

		$tbl.=
		'<TR CLASS=R7>'.
		'<TD CLASS="R22C0" STYLE=" border-left-style: none;"><SPAN></SPAN></TD>'.
		'<TD CLASS="R22C1"><SPAN STYLE="white-space:nowrap;">'.$item_index.'</SPAN></TD>'.
		'<TD CLASS="R22C2">'.$p['name'].'</TD>'.
		'<TD CLASS="R22C3">00000000787</TD>'.
		'<TD CLASS="R22C4"><SPAN STYLE="white-space:nowrap;">шт</SPAN></TD>'.
		'<TD CLASS="R22C5">796</TD>'.
		'<TD CLASS="R22C6"><SPAN></SPAN></TD>'.
		'<TD CLASS="R22C7"><SPAN></SPAN></TD>'.
		'<TD CLASS="R22C7"><SPAN></SPAN></TD>'.
		'<TD CLASS="R22C7"><SPAN></SPAN></TD>'.
		'<TD CLASS="R22C10"><SPAN STYLE="white-space:nowrap;">'.sprintf("%01.3f",intval($p['count'])).'</SPAN></TD>'.
		'<TD CLASS="R22C10"><SPAN STYLE="white-space:nowrap;">'.sprintf("%01.2f",$item_price).'</SPAN></TD>'.
		'<TD CLASS="R22C12"><SPAN STYLE="white-space:nowrap;">'.sprintf("%01.2f",$item_sum).'</SPAN></TD>'.
		'<TD CLASS="R22C13"><SPAN STYLE="white-space:nowrap;">18%</SPAN></TD>'.
		'<TD CLASS="R22C14"><SPAN STYLE="white-space:nowrap;">'.sprintf("%01.2f",$item_nds).'</SPAN></TD>'.
		'<TD CLASS="R22C15"><SPAN STYLE="white-space:nowrap;">'.sprintf("%01.2f",$sum).'</SPAN></TD>'.
		'<TD></TD></TR>';
	}



	$template->assign('orderFullAccount',$fullAccount);
	$template->assign('orderNum',$orderNum);
	$template->assign('orderDate', $orderDate);
	$template->assign('orderSignName',$account['sign_name']);
	$template->assign('orderOkpo',$account['okpo']);
	$template->assign('orderItems',$item_index);
	$template->assign('orderItemsText',num2str($item_index,false));
	$template->assign('orderProductList',$tbl);
	$template->assign('orderBuyer',$buyer);
	$template->assign('orderBuyerOkpo',$buyerOkpo);
	$template->assign('orderPayer',$payer);
	$template->assign('orderPayerOkpo',$payerOkpo);
	$template->assign('orderWhy',$why);


	$template->assign('orderTotalNds',sprintf("%01.2f",$total_nds));
	$template->assign('orderTotalPrice',sprintf("%01.2f",$total_price));
	$template->assign('orderTotalSum',sprintf("%01.2f",$total_sum));
	$template->assign('orderTotalCount',sprintf("%01.3f",$total_count));
	$template->assign('orderTotalText',num2str($total_sum));

	$template->setTemplate('Main/templates/docs/torg12.html');
	$template->display();
	return;
}

?>