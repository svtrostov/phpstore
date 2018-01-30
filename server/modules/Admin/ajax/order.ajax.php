<?php
/*==================================================================================================
Title	: Admin Order AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');
$shop = Shop::getInstance();


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){

	/*******************************************************************
	 * Поиск заказов по выбранным критериям
	 ******************************************************************/
	case 'orders.search':

		$status = $request->getStr('status','all');
		$period = $request->getStr('period','all');
		$delivery = $request->getStr('delivery','all');
		$limit = $request->getStr('limit','all');
		$term = $request->getStr('term','');

		$result = array(
			'orders_search' => $shop->ordersSearch(array(
				'status'	=> ($status=='all' ? 'all' : intval($status)),
				'delivery'	=> ($delivery=='all' ? 'all' : intval($delivery)),
				'period'	=> ($period=='all' ? 'all' : intval($period)),
				'limit'		=> ($limit=='all' ? 'all' : intval($limit)),
				'manager_id'=> $request->getStr('manager_id',''),
				'term'		=> $term
			))
		);

		#Выполнено успешно
		return Ajax::_setData($result);

	break;#Поиск заказов по выбранным критериям



	/*******************************************************************
	 * Создание заказа
	 ******************************************************************/
	case 'order.add':

		if(!$user->checkAccess('can_order_add')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$client_id			= $request->getId('client_id', 0);
		$discount_percent	= 0;

		if(!empty($client_id)){
			$client = $db->selectRecord('SELECT * FROM `clients` WHERE `client_id`='.$client_id.' LIMIT 1');
			if(empty($client)) return Ajax::_responseError('Ошибка', 'Клиент ID '.$client_id.' не найден');
			if ($client['discount_id'] > 0) $discount_percent = $db->result('SELECT IFNULL(`percent`,0) FROM `discounts` WHERE `discount_id`='.$client['discount_id'].' LIMIT 1');
		}

		$db->transaction();

		if(!empty($client)){
			$add = array(
				'status'		=> 10,
				'discount_percent'	=> $discount_percent,
				'delivery_id'	=> 1,
				'shop_percent'	=> floatval($shop->getConfigValue('shopPercent', 0)),
				'client_id'		=> $client_id,
				'is_company'	=> $client['is_company'],	#[uint, 1] Признак, указывающий что клиент - юридическое лицо или ИП
				'company'		=> $client['company'],		#[char, 255] Имя организации клиента
				'name'			=> $client['name'],			#[char, 255] Имя клиента
				'email'			=> $client['email'],		#[char, 128] Контактный email, указанный при регистрации
				'phone'			=> $client['phone'],		#[char, 32] Номер телефона
				'address'		=> $client['address'],		#[char, 255] Почтовый адрес
				'inn'			=> $client['inn'],			#[char, 32] ИНН
				'kpp'			=> $client['kpp'],			#[char, 32] КПП
				'paymethod'		=> 'cash',					#[char, 32] Метод оплаты
				'additional'	=> 'Заказ создан администратором',
				'ip_addr'		=> $request->getIP(false),				#[char, 15] IP адрес
				'ip_real'		=> $request->getIP(true),				#[char, 15] IP адрес
				'bank_name'			=> $client['bank_name'],			#[char, 255] Банковские реквизиты: Наименование банка, используется для подстановки в платежные документы
				'bank_bik'			=> $client['bank_bik'],				#[char, 32] Банковские реквизиты: БИК, используется для подстановки в платежные документы
				'bank_account'		=> $client['bank_account'],			#[char, 64] Банковские реквизиты: номер счета, используется для подстановки в платежные документы
				'bank_account_corr'	=> $client['bank_account_corr'],	#[char, 64] Банковские реквизиты: номер корреспондентского счета, используется для подстановки в платежные документы
				'legal_address'		=> $client['legal_address'],		#[char, 255] Юридический адрес организации
				'okpo'				=> $client['okpo']					#[char, 64] Код по ОКПО
			);
		}else{
			$add = array(
				'status'		=> 10,
				'client_id	'	=> $client_id
			);
		}


		if(($order_id=$db->addRecord('orders',$add))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка создания заказа');
		}

		$order_num = $order_id.'-'.date("ymd");
		if($db->update('UPDATE `orders` SET `order_num`="'.$order_num.'" WHERE `order_id`='.$order_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка создания заказа');
		}

		$add['order_id'] = $order_id;
		$add['order_num'] = $order_num;
		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Создание нового заказа',
			'data'		=> $add
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Создание нового заказа','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'created_order_id'	=>  $order_id
		));

	break; #Создание заказа




	/*******************************************************************
	 * Редактирование информации заказа
	 ******************************************************************/
	case 'order.info.edit':

		if(!$user->checkAccess('can_order_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$order_id			= $request->getId('order_id', 0);
		$order_status		= $request->getId('status', 0);
		$delivery_id		= $request->getId('delivery_id', 1);
		$delivery_cost		= abs($request->getFloat('delivery_cost', 0));
		$paymethod			= $request->getEnum('paymethod',array('cash','wire'),'cash');
		$order_email		= htmlspecialchars($request->getEmail('email', ''));
		$order_name			= htmlspecialchars($request->getStr('name', ''));
		$order_company		= htmlspecialchars($request->getStr('company', ''));
		$order_inn			= htmlspecialchars($request->getStr('inn', ''));
		$order_kpp			= htmlspecialchars($request->getStr('kpp', ''));
		$order_phone		= htmlspecialchars($request->getStr('phone', ''));
		$order_address		= htmlspecialchars($request->getStr('address', ''));
		$order_additional	= htmlspecialchars($request->getStr('additional', ''));
		$order_okpo			= htmlspecialchars($request->getStr('okpo', ''));
		$bank_name 			= htmlspecialchars($request->getStr('bank_name',''));
		$bank_bik			= htmlspecialchars($request->getStr('bank_bik',''));
		$bank_account		= htmlspecialchars($request->getStr('bank_account',''));
		$bank_account_corr	= htmlspecialchars($request->getStr('bank_account_corr',''));
		$legal_address		= htmlspecialchars($request->getStr('legal_address', ''));

		if(empty($order_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор заказа');
		if(empty($order_name)) return Ajax::_responseError('Ошибка', 'Не задано контактное имя клиента');
		$order = $db->selectRecord('SELECT * FROM `orders` WHERE `order_id`='.$order_id.' LIMIT 1');
		if(empty($order)) return Ajax::_responseError('Ошибка', 'Заказ ID '.$order_id.' не найден');

		$db->transaction();

		$updates = array(
			'status'		=> $order_status,		#[uint] Текущий статус заказа: 0 - Отменен, 1 - Не обработан, 2 - Обрабатывается, 3 - Доставляется курьером, 4 - На точке выдачи, 100 - Выполнен
			'delivery_id'	=> $delivery_id,		#[uint] Тип доставки товара для клиента из таблицы deliveries
			'delivery_cost'	=> $delivery_cost,		#[double] Сумма оплаты за доставку
			'company'		=> $order_company,		#[char, 255] Имя организации клиента
			'name'			=> $order_name,			#[char, 255] Имя клиента
			'email'			=> $order_email,		#[char, 128] Контактный email, указанный при регистрации
			'phone'			=> $order_phone,		#[char, 32] Номер телефона
			'address'		=> $order_address,		#[char, 255] Почтовый адрес
			'additional'	=> $order_additional,	#[char, 255] Дополнительная информация
			'inn'			=> $order_inn,			#[char, 32] ИНН
			'kpp'			=> $order_kpp,			#[char, 32] КПП
			'paymethod'		=> $paymethod,			#[char, 32] Метод оплаты
			'okpo'			=> $order_okpo,
			'bank_name'			=> $bank_name,
			'bank_bik'			=> $bank_bik,
			'bank_account'		=> $bank_account,
			'bank_account_corr'	=> $bank_account_corr,
			'legal_address'		=> $legal_address
		);

		if($db->updateRecord('orders',array('order_id'=>$order_id),$updates)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка обновления информации о заказе');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование заказа',
			'data'		=> array(
				'order_id'	=> $order_id,
				'prev'		=> $order,
				'new'		=> $updates
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование информации заказа','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'order_info'	=>  $db->selectRecord('SELECT * FROM `orders` WHERE `order_id`='.$order_id.' LIMIT 1')
		));

	break; #Редактирование информации заказа



	/*******************************************************************
	 * Получение списка товаров в заказе
	 ******************************************************************/
	case 'order.products':
		$order_id			= $request->getId('order_id', 0);
		if(empty($order_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор заказа');
		$order = $db->selectRecord('SELECT * FROM `orders` WHERE `order_id`='.$order_id.' LIMIT 1');
		if(empty($order)) return Ajax::_responseError('Ошибка', 'Заказ ID '.$order_id.' не найден');

		#Выполнено успешно
		return Ajax::_setData(array(
			'order_products'	=> $shop->orderProducts($order_id)
		));
	break; #Получение списка товаров в заказе



	/*******************************************************************
	 * Сохранение нового ассортимента продукции в заказе
	 ******************************************************************/
	case 'order.products.edit':
		if(!$user->checkAccess('can_order_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$order_id = $request->getId('order_id', 0);
		if(empty($order_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор заказа');
		$order = $db->selectRecord('SELECT * FROM `orders` WHERE `order_id`='.$order_id.' LIMIT 1');
		if(empty($order)) return Ajax::_responseError('Ошибка', 'Заказ ID '.$order_id.' не найден');
		$products = $request->getArray('products', array());

		$db->transaction();

		$prev = $db->select('SELECT * FROM `order_products` WHERE `order_id`='.$order_id);

		if($db->simple('DELETE FROM `order_products` WHERE `order_id`='.$order_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка обновления ассртимента товаров в заказе: E_DELETE');
		}

		$updates = array();

		foreach($products as $product){
			if(empty($product)||!is_array($product)){
				$db->rollback();
				return Ajax::_responseError('Ошибка', 'Ошибка обновления ассртимента товаров в заказе: E_EMPTY');
			}
			$product['price'] = floatval($product['price']);
			$product['count'] = floatval($product['count']);
			$sum = ceil(ceil($product['price']) * $product['count']);
			$record = array(
				'order_id'		=> $order_id,							#[uint, index] Идентификатор заказа из таблицы orders
				'product_id'	=> intval($product['product_id']),		#[uint] Идентификатор товара из таблицы products
				'currency'		=> $product['currency'],				#[char, 3] Валюта заказа
				'base_price'	=> floatval($product['base_price']),	#[double] Базовая цена за единицу товара
				'exchange'		=> floatval($product['exchange']),		#[double] Курс валюты по отношению к рублю на момент заказа
				'price'			=> $product['price'],					#[double] Цена за единицу товара для клиента
				'count'			=> $product['count'],					#[double] Количество единиц товара
				'sum'			=> $sum									#[double] Общая сумма оплаты за позицию
			);
			$updates[] = $record;
			if($db->addRecord('order_products',$record)===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка', 'Ошибка обновления ассртимента товаров в заказе: E_ADD<br><pre>'.print_r($product, true).'</pre>');
			}
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование ассортимента продукции в заказе',
			'data'		=> array(
				'order_id'	=> $order_id,
				'prev'		=> $prev,
				'new'		=> $updates
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Обновление заказа','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'order_products'	=> $shop->orderProducts($order_id)
		));
	break; #Получение списка товаров в заказе






	default:
	Ajax::_responseError('/admin/ajax/order','Not found: '.Request::_get('action'));
}
?>
