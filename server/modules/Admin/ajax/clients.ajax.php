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
	 * Поиск клиентов
	 ******************************************************************/
	case 'clients.search':

		#Выполнено успешно
		return Ajax::_setData(array(
			'clients_search' => $client->searchClients(array(
				'term'	=> trim($request->getStr('term','')),
				'limit'	=> $request->getId('limit',0),
				'manager_id'=> $request->getStr('manager_id','all')
			))
		));

	break;#Поиск клиентов



	/*******************************************************************
	 * Добавление комментария на сообщение клиента
	 ******************************************************************/
	case 'comment.add':

		if(!$user->checkAccess('can_ticket_answer')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$ticket_id = $request->getId('ticket_id',0);
		$client_id = $request->getId('client_id',0);
		$comment   = trim($request->getStr('comment', ''));
		if(empty($ticket_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор сообщения клиента');
		if(empty($client_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор клиента');
		if(empty($comment))   return Ajax::_responseError('Ошибка','Не задан текст ответа на сообщение клиента');
		$client = $db->selectRecord('SELECT * FROM `clients` WHERE `client_id`='.$client_id.' LIMIT 1');
		if(empty($client)) return Ajax::_responseError('Ошибка', 'Клиент ID '.$client_id.' не найден');
		$ticket = $db->selectRecord('SELECT * FROM `tickets` WHERE `ticket_id`='.$ticket_id.' LIMIT 1');
		if(empty($ticket)) return Ajax::_responseError('Ошибка', 'Сообщение клиента ID '.$ticket_id.' не найдено');
		$support = $request->getBool('support',false);

		$db->transaction();

		if($db->addRecord('tickets',array(
			'client_id'	=> $client_id,
			'is_support'=> 1,
			'subject'	=> 'Ответ на сообщение от '.$ticket['timestamp'],
			'message'	=> $comment
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка записи сообщения в базу данных');
		}

		$db->update('UPDATE `tickets` SET `enabled`=0 WHERE `ticket_id`='.$ticket_id);

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавлен ответ на сообщение клиента',
			'data'		=> array(
				'client_id' => $client_id,
				'ticket_id'	=> $ticket_id,
				'comment'	=> $comment
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Успешно','Ответ на сообщение клиента был успешно добавлен','hint');

		if($request->getBool('for_client',false)){
			return Ajax::_setData(array(
			'client_tickets' => $db->select('SELECT * FROM `tickets` WHERE `client_id`='.$client_id.' ORDER BY `ticket_id`')
			));
		}

		return Ajax::_setData(array(
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
				WHERE T.`enabled`>0 '.(!$support ? 'AND T.`is_support`=0' : ''))
		));

	break;#Добавление комментария на сообщение клиента



	/*******************************************************************
	 * Удаление сообщения
	 ******************************************************************/
	case 'comment.delete':

		if(!$user->checkAccess('can_ticket_delete')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$ticket_id = $request->getId('ticket_id',0);
		if(empty($ticket_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор сообщения');
		$ticket = $db->selectRecord('SELECT * FROM `tickets` WHERE `ticket_id`='.$ticket_id.' LIMIT 1');
		if(empty($ticket)) return Ajax::_responseError('Ошибка', 'Сообщение ID '.$ticket_id.' не найдено');
		$support = $request->getBool('support',false);

		$db->transaction();

		if($db->delete('DELETE FROM `tickets` WHERE `ticket_id`='.$ticket_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления сообщения');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удалено сообщение клиента',
			'data'		=> $ticket
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Успешно','Сообщение было успешно удалено','hint');

		if($request->getBool('for_client',false)){
			return Ajax::_setData(array(
			'client_tickets' => $db->select('SELECT * FROM `tickets` WHERE `client_id`='.$request->getId('client_id',0).' ORDER BY `ticket_id`')
			));
		}

		return Ajax::_setData(array(
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
				WHERE T.`enabled`>0 '.(!$support ? 'AND T.`is_support`=0' : ''))
		));
	break; #Удаление сообщения






	/*******************************************************************
	 * Cоздание учетной записи
	 ******************************************************************/
	case 'client.new':

		if(!$user->checkAccess('can_client_add')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$username		= trim($request->getStr('username', ''));
		if($db->result('SELECT IFNULL(count(*),0) FROM `clients` WHERE `username` LIKE "'.addslashes($username).'" LIMIT 1') > 0) return Ajax::_responseError('Ошибка','Указанный логин уже занят');

		$updates = array();
		$updates['username']	= $username;
		$updates['password']	= sha1(trim($request->getStr('password', '')));
		$updates['discount_id']	= $request->getId('discount_id', 0);
		$updates['manager_id']	= $request->getId('manager_id', 0);
		$updates['name']		= trim($request->getStr('name', ''));
		$updates['enabled']		= $request->getBoolAsInt('enabled', 0);
		$updates['email']		= trim($request->getEmail('email', ''));
		$updates['phone']		= trim($request->getStr('phone', ''));
		$updates['country']		= trim($request->getStr('country', ''));
		$updates['city']		= trim($request->getStr('city', ''));
		$updates['address']		= trim($request->getStr('address', ''));
		$updates['zip']			= trim($request->getStr('zip', ''));
		$updates['company']		= trim($request->getStr('company', ''));
		$updates['inn']			= trim($request->getStr('inn', ''));
		$updates['kpp']			= trim($request->getStr('kpp', ''));
		$updates['okpo']		= trim($request->getStr('okpo', ''));
		$updates['bank_name'] 			= trim($request->getStr('bank_name',''));
		$updates['bank_bik']			= trim($request->getStr('bank_bik',''));
		$updates['bank_account']		= trim($request->getStr('bank_account',''));
		$updates['bank_account_corr']	= trim($request->getStr('bank_account_corr',''));
		$updates['legal_address']		= trim($request->getStr('legal_address', ''));
		$updates['create_ip_addr']		= trim($_SERVER['REMOTE_ADDR']);


		if(($client_id = $db->addRecord('clients', $updates))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка добавления нового клиента');
		}


		Ajax::_setLocation('/admin/clients/info?client_id='.$client_id);
		return true;

	break; #Cоздание учетной записи








	/*******************************************************************
	 * Редактирование сведений клиента
	 ******************************************************************/
	case 'client.edit':

		if(!$user->checkAccess('can_client_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$client_id		= $request->getId('client_id', 0);
		$username		= trim($request->getStr('username', ''));
		$password		= trim($request->getStr('password', ''));

		if(empty($client_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор клиента');
		$client_info = $client->clientRecord($client_id, false);
		if(empty($client_info)){
			return Ajax::_responseError('Ошибка','Клиент ID:'.$client_id.' не найден');
		}

		$updates = array();

		//Смена пароля
		if(!empty($password)){
			$updates['password'] = sha1($password);
		}

		//Смена логина
		if(!empty($username) && $username!= $client_info['username']){
			if($db->result('SELECT IFNULL(count(*),0) FROM `clients` WHERE `username` LIKE "'.addslashes($username).'" AND `client_id`<>'.$client_id.' LIMIT 1') > 0) return Ajax::_responseError('Ошибка','Указанный логин уже занят');
			$updates['username'] = $username;
		}

		$updates['discount_id']	= $request->getId('discount_id', 0);
		$updates['manager_id']	= $request->getId('manager_id', 0);
		$updates['name']		= trim($request->getStr('name', ''));
		$updates['enabled']		= $request->getBoolAsInt('enabled', 0);
		$updates['email']		= trim($request->getEmail('email', ''));
		$updates['phone']		= trim($request->getStr('phone', ''));
		$updates['country']		= trim($request->getStr('country', ''));
		$updates['city']		= trim($request->getStr('city', ''));
		$updates['address']		= trim($request->getStr('address', ''));
		$updates['zip']			= trim($request->getStr('zip', ''));
		$updates['company']		= trim($request->getStr('company', ''));
		$updates['inn']			= trim($request->getStr('inn', ''));
		$updates['kpp']			= trim($request->getStr('kpp', ''));
		$updates['okpo']		= trim($request->getStr('okpo', ''));
		$updates['bank_name'] 			= trim($request->getStr('bank_name',''));
		$updates['bank_bik']			= trim($request->getStr('bank_bik',''));
		$updates['bank_account']		= trim($request->getStr('bank_account',''));
		$updates['bank_account_corr']	= trim($request->getStr('bank_account_corr',''));
		$updates['legal_address']		= trim($request->getStr('legal_address', ''));

		$db->transaction();

		#Обновление
		if($db->updateRecord('clients',array('client_id'=>$client_id), $updates)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка обновления сведений клиента');
		}

		$updates['password'] = (isset($updates['password']) ? '[changed]' : '[not modified]');
		$client_info['password'] = '[*****]';
		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование сведений клиента',
			'data'		=> array(
				'client_id'	=> $client_id,
				'prev'		=> $client_info,
				'new'		=> $updates
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'client_info' => $client->clientRecord($client_id)
		));

		return Ajax::_responseSuccess('Обновление сведений клиента','Выполнено успешно','hint');

	break; #Редактирование сведений клиента




	/*******************************************************************
	 * Журнал поисковых запросов
	 ******************************************************************/
	case 'log.search':

		$page_no	= max(1,$request->getId('page_no',1));
		$per_page	= min(1000,max(10,$request->getId('per_page',100)));
		$date_from	= $request->getDate('date_from',null);
		$date_to	= $request->getDate('date_to',null);
		$date_from	= (empty($date_from) ? DBDATE : date2sql($date_from));
		$date_to	= (empty($date_to) ? DBDATE : date2sql($date_to));

		$result = array();

		$count = $db->result('SELECT IFNULL(count(*),0) FROM `search_log` WHERE `timestamp` BETWEEN "'.$date_from.' 00:00:00" AND "'.$date_to.' 23:59:59"');
		$page_no = ($page_no > 0 ? $page_no - 1 : 0);
		$page_max = ceil($count / $per_page);
		if($page_max > 0 && $page_no >= $page_max) $page_no = $page_max-1;
		$offset = $page_no * $per_page;
		$result['navigator'] = array(
			'count'		=> intval($count),
			'page_no'	=> $page_no + 1,
			'per_page'	=> $per_page,
			'page_max'	=> $page_max,
			'offset'	=> $offset + 1
		);

		$result['logs'] = $db->select('SELECT * FROM `search_log` WHERE `timestamp` BETWEEN "'.$date_from.' 00:00:00" AND "'.$date_to.' 23:59:59" LIMIT '.$offset.','.$per_page);

		Ajax::_setData($result);

	break; #Журнал поисковых запросов



	/*******************************************************************
	 * Журнал запросов к серверу
	 ******************************************************************/
	case 'log.request':

		$page_no	= max(1,$request->getId('page_no',1));
		$per_page	= min(1000,max(10,$request->getId('per_page',100)));
		$date_from	= $request->getDate('date_from',null);
		$date_to	= $request->getDate('date_to',null);
		$ip_addr	= trim($request->getStr('ip_addr',''));
		$url		= trim($request->getStr('url',''));
		$code		= $request->getId('code','');
		$date_from	= (empty($date_from) ? DBDATE : date2sql($date_from));
		$date_to	= (empty($date_to) ? DBDATE : date2sql($date_to));

		$result = array();

		$where = '`timestamp` BETWEEN "'.$date_from.' 00:00:00" AND "'.$date_to.' 23:59:59"'.
				(!empty($code)?' AND `error_code`='.$code:'').
				(!empty($ip_addr)?' AND `ip_addr` LIKE "%'.addslashes($ip_addr).'%"':'').
				(!empty($url)?' AND `url` LIKE "%'.addslashes($url).'%"':'');
				

		$count = $db->result('SELECT IFNULL(count(*),0) FROM `request_log` WHERE '.$where);
		$page_no = ($page_no > 0 ? $page_no - 1 : 0);
		$page_max = ceil($count / $per_page);
		if($page_max > 0 && $page_no >= $page_max) $page_no = $page_max-1;
		$offset = $page_no * $per_page;
		$result['navigator'] = array(
			'count'		=> intval($count),
			'page_no'	=> $page_no + 1,
			'per_page'	=> $per_page,
			'page_max'	=> $page_max,
			'offset'	=> $offset + 1
		);

		$result['logs'] = $db->select('SELECT * FROM `request_log` WHERE '.$where.' LIMIT '.$offset.','.$per_page);

		Ajax::_setData($result);

	break; #Журнал запросов к серверу



	default:
	Ajax::_responseError('/admin/ajax/clients','Not found: '.Request::_get('action'));
}
?>
