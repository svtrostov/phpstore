<?php
/*==================================================================================================
Title	: Admin Clients AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){

	/*******************************************************************
	 * Добавление скидки
	 ******************************************************************/
	case 'discount.new':
		if(!$user->checkAccess('can_discount_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$name = $request->getStr('name','');
		$description = $request->getStr('description','');
		$percent = abs($request->getFloat('percent', 0));
		$percent = min(100,max($percent,0));

		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование скидки');
		if(empty($description)) return Ajax::_responseError('Ошибка', 'Не задано описание скидки');

		$db->prepare('INSERT INTO `discounts` (`name`,`description`,`percent`)VALUES(?,?,?)');
		$db->bind($name);
		$db->bind($description);
		$db->bind($percent);
		if(($discount_id = $db->insert())===false) return Ajax::_responseError('Ошибка', 'Ошибка добавления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление скидки',
			'data'		=> array(
				'discount_id'	=> $discount_id,
				'name'=> $name,
				'description'	=> $description,
				'percent'	=> $percent
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'discount_id'	=> $discount_id,
			'discounts'		=> $db->select('SELECT * FROM `discounts`')
		));

	break;#Добавление скидки


	/*******************************************************************
	 * Изменение скидки
	 ******************************************************************/
	case 'discount.edit':
		if(!$user->checkAccess('can_discount_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$discount_id = $request->getId('discount_id',0);
		$name = $request->getStr('name','');
		$description = $request->getStr('description','');
		$percent = abs($request->getFloat('percent', 0));
		$percent = min(100,max($percent,0));

		if(empty($discount_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор скидки');
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование скидки');
		if(empty($description)) return Ajax::_responseError('Ошибка', 'Не задано описание скидки');

		$db->prepare('UPDATE `discounts` SET `name`=?,`description`=?,`percent`=? WHERE `discount_id`=?');
		$db->bind($name);
		$db->bind($description);
		$db->bind($percent);
		$db->bind($discount_id);
		if($db->update()===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменение скидки',
			'data'		=> array(
				'discount_id'	=> $discount_id,
				'name'			=> $name,
				'description'	=> $description,
				'percent'		=> $percent
			)
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование скидки','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'discount_id'	=> $discount_id,
			'discounts'		=> $db->select('SELECT * FROM `discounts`')
		));

	break;#Изменение скидки


	/*******************************************************************
	 * Удаление скидки
	 ******************************************************************/
	case 'discount.delete':
		if(!$user->checkAccess('can_discount_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$discount_id = $request->getId('discount_id',0);
		if(empty($discount_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор скидки');

		$db->transaction();
		if($db->delete('DELETE FROM `discounts` WHERE `discount_id`='.$discount_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');
		}
		if($db->delete('UPDATE `clients` SET `discount_id`=0 WHERE `discount_id`='.$discount_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление скидки',
			'data'		=> array(
				'discount_id'	=> $discount_id
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'discounts'		=> $db->select('SELECT * FROM `discounts`')
		));

	break;#Удаление скидки



	/*******************************************************************
	 * Добавление доставки
	 ******************************************************************/
	case 'delivery.new':
		if(!$user->checkAccess('can_delivery_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$name = $request->getStr('name','');
		$desc = $request->getStr('desc','');
		$price = abs($request->getFloat('price', 0));
		$order_min = abs($request->getFloat('order_min', 0));
		$order_max = abs($request->getFloat('order_max', 0));
		$enabled = $request->getBoolAsInt('enabled',0);

		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование доставки');
		if(empty($desc)) return Ajax::_responseError('Ошибка', 'Не задано описание доставки');

		$db->prepare('INSERT INTO `deliveries` (`name`,`desc`,`price`,`order_min`,`order_max`,`enabled`)VALUES(?,?,?,?,?,?)');
		$db->bind($name);
		$db->bind($desc);
		$db->bind($price);
		$db->bind($order_min);
		$db->bind($order_max);
		$db->bind($enabled);
		if(($delivery_id = $db->insert())===false) return Ajax::_responseError('Ошибка', 'Ошибка добавления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление доставки',
			'data'		=> array(
				'delivery_id'	=> $delivery_id,
				'name'=> $name,
				'description'	=> $desc,
				'price'	=> $price,
				'order_min'	=> $order_min,
				'order_max'	=> $order_max,
				'enabled'	=> $enabled
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'delivery_id'	=> $delivery_id,
			'deliveries'		=> $db->select('SELECT * FROM `deliveries`')
		));

	break;#Добавление доставки



	/*******************************************************************
	 * Редактирование доставки
	 ******************************************************************/
	case 'delivery.edit':
		if(!$user->checkAccess('can_delivery_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$delivery_id = $request->getId('delivery_id',0);
		$name = $request->getStr('name','');
		$desc = $request->getStr('desc','');
		$price = abs($request->getFloat('price', 0));
		$order_min = abs($request->getFloat('order_min', 0));
		$order_max = abs($request->getFloat('order_max', 0));
		$enabled = $request->getBoolAsInt('enabled',0);

		if(empty($delivery_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор доставки');
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование доставки');
		if(empty($desc)) return Ajax::_responseError('Ошибка', 'Не задано описание доставки');

		$db->prepare('UPDATE `deliveries` SET `name`=?,`desc`=?,`price`=?,`order_min`=?,`order_max`=?,`enabled`=? WHERE `delivery_id`=?');
		$db->bind($name);
		$db->bind($desc);
		$db->bind($price);
		$db->bind($order_min);
		$db->bind($order_max);
		$db->bind($enabled);
		$db->bind($delivery_id);
		if($db->update()===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование доставки',
			'data'		=> array(
				'delivery_id'	=> $delivery_id,
				'name'=> $name,
				'description'	=> $desc,
				'price'	=> $price,
				'order_min'	=> $order_min,
				'order_max'	=> $order_max,
				'enabled'	=> $enabled
			)
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование доставки','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'delivery_id'	=> $delivery_id,
			'deliveries'		=> $db->select('SELECT * FROM `deliveries`')
		));

	break;#Редактирование доставки



	/*******************************************************************
	 * Удаление доставки
	 ******************************************************************/
	case 'delivery.delete':
		if(!$user->checkAccess('can_delivery_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$delivery_id = $request->getId('delivery_id',0);
		if(empty($delivery_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор доставки');

		if($delivery_id==1) return Ajax::_responseError('Ошибка', 'Этот метод доставки нельзя удалить');

		if($db->delete('DELETE FROM `deliveries` WHERE `delivery_id`='.$delivery_id)===false) return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление доставки',
			'data'		=> array(
				'delivery_id'	=> $delivery_id
			)
		));


		#Выполнено успешно
		return Ajax::_setData(array(
			'deliveries'		=> $db->select('SELECT * FROM `deliveries`')
		));

	break;#Удаление доставки



	/*******************************************************************
	 * Добавление валюты
	 ******************************************************************/
	case 'currency.new':
		if(!$user->checkAccess('can_currency_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$name = $request->getStr('name','');
		$code = strtolower($request->getStr('code',''));
		$exchange = abs($request->getFloat('exchange', 0));


		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование валюты');
		if(empty($code)) return Ajax::_responseError('Ошибка', 'Не задан код валюты');
		if(empty($exchange)) return Ajax::_responseError('Ошибка', 'Не задан обменный курс валюты');

		$db->prepare('SELECT * FROM `currencies` WHERE `code` LIKE ? LIMIT 1');
		$db->bind($code);
		$c = $db->selectRecord();
		if(!empty($c)) return Ajax::_responseError('Ошибка', 'Ошибка добавления валюты, валюта с таким кодом уже существует');

		$db->prepare('INSERT INTO `currencies` (`code`,`name`,`exchange`)VALUES(?,?,?)');
		$db->bind($code);
		$db->bind($name);
		$db->bind($exchange);
		if($db->insert()===false) return Ajax::_responseError('Ошибка', 'Ошибка добавления валюты, возможно, валюта с таким кодом уже существует');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление валюты',
			'data'		=> array(
				'code'	=> $code,
				'name'=> $name,
				'exchange'	=> $exchange
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'code'			=> $code,
			'currencies'	=> $db->select('SELECT * FROM `currencies`')
		));

	break;#Добавление валюты



	/*******************************************************************
	 * Обновление валюты
	 ******************************************************************/
	case 'currency.edit':
		if(!$user->checkAccess('can_currency_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$name = $request->getStr('name','');
		$code = strtolower($request->getStr('code',''));
		$exchange = abs($request->getFloat('exchange', 0));


		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование валюты');
		if(empty($code)) return Ajax::_responseError('Ошибка', 'Не задан код валюты');
		if($code=='rub') $exchange = 1;
		if(empty($exchange)) return Ajax::_responseError('Ошибка', 'Не задан обменный курс валюты');

		$db->prepare('UPDATE `currencies` SET `name`=?,`exchange`=? WHERE `code` LIKE ?');
		$db->bind($name);
		$db->bind($exchange);
		$db->bind($code);
		if($db->update()===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления валюты');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование валюты',
			'data'		=> array(
				'code'	=> $code,
				'name'=> $name,
				'exchange'	=> $exchange
			)
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование валюты','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'code'			=> $code,
			'currencies'	=> $db->select('SELECT * FROM `currencies`')
		));

	break;#Обновление валюты



	/*******************************************************************
	 * Удаление валюты
	 ******************************************************************/
	case 'currency.delete':
		if(!$user->checkAccess('can_currency_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$code = strtolower($request->getStr('code',''));
		if(empty($code)) return Ajax::_responseError('Ошибка', 'Не задан код валюты');
		if($code=='rub'||$code=='usd') return Ajax::_responseError('Ошибка', 'Эту валюту нельзя удалить');

		$db->prepare('DELETE FROM `currencies` WHERE `code` LIKE ?');
		$db->bind($code);
		if($db->delete()===false) return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление валюты',
			'data'		=> array(
				'code'	=> $code
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'currencies'		=> $db->select('SELECT * FROM `currencies`')
		));

	break;#Удаление валюты



	/*******************************************************************
	 * Обновление параметра магазина
	 ******************************************************************/
	case 'config.edit':
		if(!$user->checkAccess('can_config_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$param = $request->getStr('param','');
		$value = $request->getStr('value','');

		if(empty($param)) return Ajax::_responseError('Ошибка', 'Не задано параметр');

		$db->prepare('UPDATE `config` SET `value`=? WHERE `param` LIKE ?');
		$db->bind($value);
		$db->bind($param);
		if($db->update()===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления параметра');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Обновление параметра магазина',
			'data'		=> array(
				'param'	=> $param,
				'value'	=> $value
				)
		));


		#Выполнено успешно
		Ajax::_responseSuccess('Обновление параметра','Значение параметра '.$param.' успешно обновлено','hint');
		return Ajax::_setData(array(
			'settings'	=> $db->select('SELECT * FROM `config`')
		));

	break;#Обновление параметра магазина



	/*******************************************************************
	 * Добавление склада
	 ******************************************************************/
	case 'warehouse.new':
		if(!$user->checkAccess('can_warehouse_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$name = $request->getStr('name','');
		$desc = $request->getStr('desc','');
		$enabled = $request->getBoolAsInt('enabled',0);

		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование склада');
		if(empty($desc)) return Ajax::_responseError('Ошибка', 'Не задано описание склада');

		$db->prepare('INSERT INTO `warehouses` (`name`,`desc`,`enabled`)VALUES(?,?,?)');
		$db->bind($name);
		$db->bind($desc);
		$db->bind($enabled);
		if(($warehouse_id = $db->insert())===false) return Ajax::_responseError('Ошибка', 'Ошибка добавления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление склада',
			'data'		=> array(
				'warehouse_id'	=> $warehouse_id,
				'name'=> $name,
				'description'	=> $desc,
				'enabled'	=> $enabled
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'warehouse_id'	=> $warehouse_id,
			'warehouses'		=> $db->select('SELECT * FROM `warehouses`')
		));

	break;#Добавление склада



	/*******************************************************************
	 * Редактирование склада
	 ******************************************************************/
	case 'warehouse.edit':
		if(!$user->checkAccess('can_warehouse_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$warehouse_id = $request->getId('warehouse_id',0);
		$name = $request->getStr('name','');
		$desc = $request->getStr('desc','');
		$enabled = $request->getBoolAsInt('enabled',0);

		if(empty($warehouse_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор склада');
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование склада');
		if(empty($desc)) return Ajax::_responseError('Ошибка', 'Не задано описание склада');

		$db->prepare('UPDATE `warehouses` SET `name`=?,`desc`=?,`enabled`=? WHERE `warehouse_id`=?');
		$db->bind($name);
		$db->bind($desc);
		$db->bind($enabled);
		$db->bind($warehouse_id);
		if($db->update()===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование склада',
			'data'		=> array(
				'warehouse_id'	=> $warehouse_id,
				'name'=> $name,
				'description'	=> $desc,
				'enabled'	=> $enabled
			)
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование склада','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'warehouse_id'	=> $warehouse_id,
			'warehouses'		=> $db->select('SELECT * FROM `warehouses`')
		));

	break;#Редактирование склада



	/*******************************************************************
	 * Удаление склада
	 ******************************************************************/
	case 'warehouse.delete':
		if(!$user->checkAccess('can_warehouse_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$warehouse_id = $request->getId('warehouse_id',0);
		if(empty($warehouse_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор склада');
		if($warehouse_id < 8) return Ajax::_responseError('Ошибка', 'Этот склад нельзя удалить');

		$db->prepare('DELETE FROM `warehouses` WHERE `warehouse_id`=?');
		$db->bind($warehouse_id);
		if($db->delete()===false) return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление склада',
			'data'		=> array(
				'code'	=> $warehouse_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'warehouses'		=> $db->select('SELECT * FROM `warehouses`')
		));

	break;#Удаление склада



	/*******************************************************************
	 * Загрузка прайс-листа Citilink
	 ******************************************************************/
	case 'price.citilink.upload':
		if(!$user->checkAccess('can_citilink_upload')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$citilink = Config::getOption('sources','citilink',false);
		$price_file = (!empty($citilink['file']) ? $citilink['file'] : '');
		if(empty($price_file)) return Ajax::_responseError('Ошибка', 'В настройках сервера не задан путь к файлу прайс-листа Citilink');
		if(file_exists($price_file)&&!is_writable($price_file)) return Ajax::_responseError('Ошибка', 'Ошибка доступа к файлу прайс-листа Citilink, скорее всего, прямо сейчас существующий файл прайс-листа обрабатывается сервером. Попробуйте позже.');

		#Файл не задан
		if(!$_FILES['price']['size']) return Ajax::_responseError('Ошибка','Не задан файл прайс-листа');

		#Ошибка загрузки файла
		if($_FILES['price']['error']) return Ajax::_responseError('Ошибка','Ошибка загрузки файла: '.$_FILES['price']['error']);

		//if($_FILES['price']['type']!='text/csv') return Ajax::_responseError('Ошибка','Загруженный файл не опознан как файл в формате CSV. Получен MIME TYPE = ['.$_FILES['price']['type'].']');

		if(file_exists($price_file)){
			if(!unlink($price_file)) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла прайс-листа');
		}

		if(!@copy($_FILES['price']['tmp_name'], $price_file)) Ajax::_responseError('Ошибка','Не удалось сохранить загруженный файл прайс-листа');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Загрузка прайс-листа Citilink',
			'data'		=> $_FILES['price']
		));

		Ajax::_responseSuccess('Загрузка прайс-листа Citilink','Выполнено успешно','hint');
		$ajax->setData(array(
			'price_info' => array(
					'time'	=> date('Y-m-d H:i:s',filectime($price_file))
				)
		));

	break; #Загрузка прайс-листа Citilink




	/*******************************************************************
	 * Загрузка прайс-листа CET
	 ******************************************************************/
	case 'price.cet.upload':
		if(!$user->checkAccess('can_cet_upload')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$cet = Config::getOption('sources','cet',false);
		$price_file = (!empty($cet['file']) ? $cet['file'] : '');
		if(empty($price_file)) return Ajax::_responseError('Ошибка', 'В настройках сервера не задан путь к файлу прайс-листа cet');
		if(file_exists($price_file)&&!is_writable($price_file)) return Ajax::_responseError('Ошибка', 'Ошибка доступа к файлу прайс-листа cet, скорее всего, прямо сейчас существующий файл прайс-листа обрабатывается сервером. Попробуйте позже.');

		#Файл не задан
		if(!$_FILES['price']['size']) return Ajax::_responseError('Ошибка','Не задан файл прайс-листа');

		#Ошибка загрузки файла
		if($_FILES['price']['error']) return Ajax::_responseError('Ошибка','Ошибка загрузки файла: '.$_FILES['price']['error']);

		//if($_FILES['price']['type']!='text/csv') return Ajax::_responseError('Ошибка','Загруженный файл не опознан как файл в формате CSV. Получен MIME TYPE = ['.$_FILES['price']['type'].']');

		if(file_exists($price_file)){
			if(!unlink($price_file)) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла прайс-листа');
		}

		if(!@copy($_FILES['price']['tmp_name'], $price_file)) Ajax::_responseError('Ошибка','Не удалось сохранить загруженный файл прайс-листа');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Загрузка прайс-листа cet',
			'data'		=> $_FILES['price']
		));

		Ajax::_responseSuccess('Загрузка прайс-листа cet','Выполнено успешно','hint');
		$ajax->setData(array(
			'price_info' => array(
					'time'	=> date('Y-m-d H:i:s',filectime($price_file))
				)
		));

	break; #Загрузка прайс-листа CET




	/*******************************************************************
	 * Добавление реквизитов
	 ******************************************************************/
	case 'account.new':
		if(!$user->checkAccess('can_account_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$code = $request->getStr('code','');
		$name = $request->getStr('name','');
		$bank_name 			= $request->getStr('bank_name','');			#[char, 255] Банковские реквизиты: Наименование банка, используется для подстановки в платежные документы
		$bank_bik			= $request->getStr('bank_bik','');			#[char, 255] Банковские реквизиты: БИК, используется для подстановки в платежные документы
		$bank_account		= $request->getStr('bank_account','');		#[char, 255] Банковские реквизиты: номер счета, используется для подстановки в платежные документы
		$bank_account_corr	= $request->getStr('bank_account_corr','');	#[char, 255] Банковские реквизиты: номер корреспондентского счета, используется для подстановки в платежные документы
		$company			= $request->getStr('company','');			#[char, 255] Наименование организации, подставляемое в счета, счета-фактуры и прочие платежные документы
		$address			= $request->getStr('address','');			#[char, 255] Адрес организации, подставляемый в платежные документы
		$address_real		= $request->getStr('address_real','');		#[char, 255] Адрес организации, подставляемый в платежные документы
		$phone				= $request->getStr('phone','');				#[char, 255] Номер телефона, проставляемый в платежных документах
		$ogrn				= $request->getStr('ogrn','');				#[char, 255] ОГРН, подставляемый в платежные документы
		$inn				= $request->getStr('inn','');				#[char, 255] ИНН, подставляемый в платежные документы
		$kpp				= $request->getStr('kpp','');				#[char, 255] КПП, подставляемый в платежные документы
		$okpo				= $request->getStr('okpo','');				#[char, 255] ОКПО
		$sign_name			= $request->getStr('sign_name','');			#[char, 255] Фамилия И.О. подписанта, подставляемое в платежные документы
		$sign_post			= $request->getStr('sign_post','');			#[char, 255] Должность подписанта, подставляемая в платежные документы
		$certificate		= $request->getStr('certificate','');		#[char, 255] Свидетельство о регистрации ИП

		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование реквизитов');
		if(empty($code)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(empty($bank_name)) return Ajax::_responseError('Ошибка', 'Не задано Наименование банка');
		if(empty($bank_bik)) return Ajax::_responseError('Ошибка', 'Не задан БИК банка');
		if(!is_numeric($bank_bik)) return Ajax::_responseError('Ошибка', 'БИК счета должен состоять только из цифр');
		if(empty($bank_account)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(!is_numeric($bank_account)) return Ajax::_responseError('Ошибка', 'Номер счета должен состоять только из цифр');
		if(empty($bank_account_corr)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(!is_numeric($bank_account_corr)) return Ajax::_responseError('Ошибка', 'Номер корреспондентского счета должен состоять только из цифр');
		if(empty($company)) return Ajax::_responseError('Ошибка', 'Не задано Наименование организации');
		if(empty($address)) return Ajax::_responseError('Ошибка', 'Не задан Адрес организации');
		if(empty($phone)) return Ajax::_responseError('Ошибка', 'Не задан Номер телефона');
		if(empty($sign_name)) return Ajax::_responseError('Ошибка', 'Не задана Фамилия И.О. подписанта');
		if(empty($sign_post)) return Ajax::_responseError('Ошибка', 'Не задана  Должность подписанта');

		$exists = $db->result('SELECT IFNULL(count(*),0) FROM `accounts` WHERE `code` LIKE "'.$code.'"');
		if($exists>0) return Ajax::_responseError('Ошибка', 'Указанный код реквизитов уже задан для других реквизитов');

		$add = array(
			'code'		=> $code,
			'name'		=> $name,
			'bank_name'			=> $bank_name,
			'bank_bik'			=> $bank_bik,
			'bank_account'		=> $bank_account,
			'bank_account_corr'	=> $bank_account_corr,
			'company'		=> $company,
			'address'		=> $address,
			'address_real'	=> $address_real,
			'phone'			=> $phone,
			'ogrn'			=> $ogrn,
			'inn'			=> $inn,
			'kpp'			=> $kpp,
			'okpo'			=> $okpo,
			'sign_name'		=> $sign_name,
			'sign_post'		=> $sign_post,
			'certificate'	=> $certificate
		);

		$account_id =  $db->addRecord('accounts', $add);
		if(empty($account_id)) return Ajax::_responseError('Ошибка', 'Ошибка добавления записи');

		$add['account_id'] = $account_id; 

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление реквизитов',
			'data'		=> $add
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'account_id'	=> $account_id,
			'accounts'		=> $db->select('SELECT * FROM `accounts`')
		));

	break;#Добавление реквизитов



	/*******************************************************************
	 * Редактирование реквизитов
	 ******************************************************************/
	case 'account.edit':
		if(!$user->checkAccess('can_account_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$account_id = $request->getId('account_id',0);
		$code = $request->getStr('code','');
		$name = $request->getStr('name','');
		$bank_name 			= $request->getStr('bank_name','');			#[char, 255] Банковские реквизиты: Наименование банка, используется для подстановки в платежные документы
		$bank_bik			= $request->getStr('bank_bik','');			#[char, 255] Банковские реквизиты: БИК, используется для подстановки в платежные документы
		$bank_account		= $request->getStr('bank_account','');		#[char, 255] Банковские реквизиты: номер счета, используется для подстановки в платежные документы
		$bank_account_corr	= $request->getStr('bank_account_corr','');	#[char, 255] Банковские реквизиты: номер корреспондентского счета, используется для подстановки в платежные документы
		$company			= $request->getStr('company','');			#[char, 255] Наименование организации, подставляемое в счета, счета-фактуры и прочие платежные документы
		$address			= $request->getStr('address','');			#[char, 255] Адрес организации, подставляемый в платежные документы
		$phone				= $request->getStr('phone','');				#[char, 255] Номер телефона, проставляемый в платежных документах
		$inn				= $request->getStr('inn','');				#[char, 255] ИНН, подставляемый в платежные документы
		$kpp				= $request->getStr('kpp','');				#[char, 255] КПП, подставляемый в платежные документы
		$okpo				= $request->getStr('okpo','');				#[char, 255] ОКПО
		$sign_name			= $request->getStr('sign_name','');			#[char, 255] Фамилия И.О. подписанта, подставляемое в платежные документы
		$sign_post			= $request->getStr('sign_post','');			#[char, 255] Должность подписанта, подставляемая в платежные документы
		$address_real		= $request->getStr('address_real','');		#[char, 255] Адрес организации, подставляемый в платежные документы
		$ogrn				= $request->getStr('ogrn','');				#[char, 255] ОГРН, подставляемый в платежные документы
		$certificate		= $request->getStr('certificate','');		#[char, 255] Свидетельство о регистрации ИП

		if(empty($account_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор реквизитов');
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование реквизитов');
		if(empty($code)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(empty($bank_name)) return Ajax::_responseError('Ошибка', 'Не задано Наименование банка');
		if(empty($bank_bik)) return Ajax::_responseError('Ошибка', 'Не задан БИК банка');
		if(!is_numeric($bank_bik)) return Ajax::_responseError('Ошибка', 'БИК счета должен состоять только из цифр');
		if(empty($bank_account)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(!is_numeric($bank_account)) return Ajax::_responseError('Ошибка', 'Номер счета должен состоять только из цифр');
		if(empty($bank_account_corr)) return Ajax::_responseError('Ошибка', 'Не задан код реквизитов');
		if(!is_numeric($bank_account_corr)) return Ajax::_responseError('Ошибка', 'Номер корреспондентского счета должен состоять только из цифр');
		if(empty($company)) return Ajax::_responseError('Ошибка', 'Не задано Наименование организации');
		if(empty($address)) return Ajax::_responseError('Ошибка', 'Не задан Адрес организации');
		if(empty($phone)) return Ajax::_responseError('Ошибка', 'Не задан Номер телефона');
		if(empty($sign_name)) return Ajax::_responseError('Ошибка', 'Не задана Фамилия И.О. подписанта');
		if(empty($sign_post)) return Ajax::_responseError('Ошибка', 'Не задана  Должность подписанта');

		$exists = $db->result('SELECT IFNULL(count(*),0) FROM `accounts` WHERE `code` LIKE "'.$code.'" AND `account_id`<>'.$account_id);
		if($exists>0) return Ajax::_responseError('Ошибка', 'Указанный код реквизитов уже задан для других реквизитов');

		$upd = array(
			'code'		=> $code,
			'name'		=> $name,
			'bank_name'			=> $bank_name,
			'bank_bik'			=> $bank_bik,
			'bank_account'		=> $bank_account,
			'bank_account_corr'	=> $bank_account_corr,
			'company'		=> $company,
			'address'		=> $address,
			'address_real'	=> $address_real,
			'phone'			=> $phone,
			'ogrn'			=> $ogrn,
			'inn'			=> $inn,
			'kpp'			=> $kpp,
			'okpo'			=> $okpo,
			'sign_name'		=> $sign_name,
			'sign_post'		=> $sign_post,
			'certificate'	=> $certificate
		);

		if($db->updateRecord('accounts', array('account_id'=>$account_id), $upd)===false) return Ajax::_responseError('Ошибка', 'Ошибка обновления записи');

		$upd['account_id'] = $account_id;
		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование реквизитов',
			'data'		=> $upd
		));

		#Выполнено успешно
		Ajax::_responseSuccess('Редактирование реквизитов','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'account_id'	=> $account_id,
			'accounts'		=> $db->select('SELECT * FROM `accounts`')
		));

	break;#Редактирование реквизитов



	/*******************************************************************
	 * Удаление реквизитов
	 ******************************************************************/
	case 'account.delete':
		if(!$user->checkAccess('can_account_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$account_id = $request->getId('account_id',0);
		if(empty($account_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор реквизитов');
		if($account_id < 3) return Ajax::_responseError('Ошибка', 'Эти реквизиты нельзя удалить');

		$db->prepare('DELETE FROM `accounts` WHERE `account_id`=?');
		$db->bind($account_id);
		if($db->delete()===false) return Ajax::_responseError('Ошибка', 'Ошибка удаления реквизитов');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление реквизитов',
			'data'		=> array(
				'account_id'	=> $account_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'accounts'		=> $db->select('SELECT * FROM `accounts`')
		));

	break;#Удаление реквизитов



	/*******************************************************************
	 * Получение новости
	 ******************************************************************/
	case 'news.get':
		$news_id = $request->getId('news_id',0);
		if(empty($news_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор новости');
		$news_info = $db->selectRecord('SELECT * FROM `news` WHERE `news_id`='.$news_id.' LIMIT 1');
		if(empty($news_info)) return Ajax::_responseError('Ошибка', 'Новость ID:'.$news_id.' не найдена');
		#Выполнено успешно
		return Ajax::_setData(array(
			'news_info'		=> $news_info
		));
	break; #Получение новости



	/*******************************************************************
	 * Удаление новости
	 ******************************************************************/
	case 'news.delete':
		if(!$user->checkAccess('can_news_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$news_id = $request->getId('news_id',0);
		if(empty($news_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор новости');
		$news_info = $db->selectRecord('SELECT * FROM `news` WHERE `news_id`='.$news_id.' LIMIT 1');
		if(empty($news_info)) return Ajax::_responseError('Ошибка', 'Новость ID:'.$news_id.' не найдена');

		$db->transaction();

		if($db->delete('DELETE FROM `news` WHERE `news_id`='.$news_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка удаления записи');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление новости',
			'data'		=> array(
				'news_id'	=> $news_id,
				'date'		=> $news_info['date'],
				'theme'		=> $news_info['theme']
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'news' => $db->select('SELECT `news_id`,`timestamp`,`date`,`enabled`,`theme` FROM `news`')
		));

	break;#Удаление новости



	/*******************************************************************
	 * Добавление / редактирование новости
	 ******************************************************************/
	case 'news.save':
		if(!$user->checkAccess('can_news_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$news_id	= $request->getId('news_id',0);
		$enabled	= $request->getBoolAsInt('enabled', 0);
		$date		= $request->getDate('date',false);
		$theme		= $request->getStr('theme','');
		$content	= $request->getStr('content','');

		if(empty($date)) return Ajax::_responseError('Ошибка', 'Не задана дата новости');
		if(empty($theme)) return Ajax::_responseError('Ошибка', 'Не задана тема новости');
		if(empty($content)) return Ajax::_responseError('Ошибка', 'Не задан текст новости');

		$fields = array(
			'date'		=> date2sql($date),
			'enabled'	=> $enabled,
			'theme'		=> $theme,
			'content'	=> $content
		);

		$db->transaction();

		//Добавление новости
		if(empty($news_id)){
			$action_text = 'Добавление новости';
			if(($news_id =  $db->addRecord('news', $fields))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка','Ошибка добавления новости');
			}
		}
		//Редактирование новости
		else{
			$action_text = 'Редактирование новости';
			if($db->updateRecord('news', array('news_id'=>$news_id), $fields)===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка','Ошибка редактирования новости');
			}
		}

		$fields['news_id'] = $news_id;
		$fields['content'] = '[content]';

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> $action_text,
			'data'		=> $fields
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess($action_text,'Выполнено успешно','hint');
		return Ajax::_setData(array(
			'news' => $db->select('SELECT `news_id`,`timestamp`,`date`,`enabled`,`theme` FROM `news`')
		));

	break;#Добавление / редактирование новости


	/*******************************************************************
	 * Добавление стикера
	 ******************************************************************/
	case 'sticky.add':
		$sticky_id = $db->addRecord('stickies', array(
			'user_id'	=> User::_getUserId(),
			'left'		=> rand(50,200),
			'top'		=> rand(50,150)
		));
		if(empty($sticky_id)) return Ajax::_responseError('Ошибка','Ошибка добавления стикера');

		$ajax->setData(array(
			'stickies' => $db->select('SELECT S.*,IFNULL(U.`name`,S.`user_id`) as `author` FROM `stickies` as S LEFT JOIN `users` as U ON U.`user_id`=S.`user_id`')
		));

	break; #Добавление стикера


	/*******************************************************************
	 * Обновление стикера
	 ******************************************************************/
	case 'sticky.update':
		$sticky_id = $request->getId('sticky_id',0);
		if(empty($sticky_id)) return Ajax::_responseError('Ошибка','Ошибка обновления стикера');
		$upd = null;
		switch($request->getStr('type','')){
			case 'color': $upd = array(
				'color'			=> $request->getStr('color','sticky-default'),
				'last_update'	=> time()
			); break;
			case 'pos': $upd = array(
				'left'			=> max(5,$request->getId('left',100)),
				'top'			=> max(5,$request->getId('top',100)),
				'last_update'	=> time()
			); break;
			case 'size': $upd = array(
				'width'			=> max(100, $request->getId('width',150)),
				'height'		=> max(100, $request->getId('height',150)),
				'last_update'	=> time()
			); break;
			case 'content': $upd = array(
				'content'		=> $request->getStr('content',''),
				'last_update'	=> time()
			); break;
			default: return;
		}

		if($db->updateRecord('stickies', array('sticky_id'=>$sticky_id), $upd)===false) return Ajax::_responseError('Ошибка','Ошибка обновления стикера');

		$ajax->setData(array(
			'stickies' => $db->select('SELECT S.*,IFNULL(U.`name`,S.`user_id`) as `author` FROM `stickies` as S LEFT JOIN `users` as U ON U.`user_id`=S.`user_id`')
		));

	break; #Обновление стикера


	/*******************************************************************
	 * Удаление стикера
	 ******************************************************************/
	case 'sticky.delete':
		$sticky_id = $request->getId('sticky_id',0);
		if(empty($sticky_id)) return Ajax::_responseError('Ошибка','Ошибка обновления стикера');
		$db->delete('DELETE FROM `stickies` WHERE `sticky_id`='.$sticky_id);
		$ajax->setData(array(
			'stickies' => $db->select('SELECT S.*,IFNULL(U.`name`,S.`user_id`) as `author` FROM `stickies` as S LEFT JOIN `users` as U ON U.`user_id`=S.`user_id`')
		));

	break; #Удаление стикера


	default:
	Ajax::_responseError('/admin/ajax/admin','Not found: '.Request::_get('action'));
}
?>
