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
	 * Cоздание учетной записи
	 ******************************************************************/
	case 'user.add':

		if(!$user->checkAccess('can_user_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$username		= trim($request->getStr('username', ''));
		$password		= trim($request->getStr('password', ''));
		$name			= trim($request->getStr('name', ''));
		if(empty($username)) return Ajax::_responseError('Ошибка','Не задан логин пользователя');
		if(empty($password)) return Ajax::_responseError('Ошибка','Не задан пароль пользователя');
		if(empty($name)) return Ajax::_responseError('Ошибка','Не задано имя пользователя');

		if($db->result('SELECT IFNULL(count(*),0) FROM `users` WHERE `username` LIKE "'.addslashes($username).'" LIMIT 1') > 0) return Ajax::_responseError('Ошибка','Указанны логин уже занят');

		$db->transaction();

		$user_id = $db->addRecord('users',array(
			'username'	=> $username,
			'name'		=> $name,
			'password'	=> sha1($password),
			'enabled'	=> $request->getBoolAsInt('enabled', 0),
			'updated'	=> 1
		));

		if(empty($user_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка добавления пользователя');
		}

		$uaccess = array();
		$raccess = $request->getArray('access', array());
		foreach($raccess as $ra){
			if($user->accessObjectExists($ra)) $uaccess[]=$ra;
		}


		foreach($uaccess as $ua){
			if($db->addRecord('user_access',array(
				'user_id'	=> $user_id,
				'access'	=> $ua,
				'level'		=> 1
			))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка','Ошибка добавления пользователя');
			}
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавлена учетная запись пользователя',
			'data'		=> array(
				'user_id'	=> $user_id,
				'username'	=> $username,
				'name'		=> $name,
				'access'	=> $uaccess
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'user_id'		=> $user_id
		));

		return Ajax::_responseSuccess('Создание учетной записи пользователя','Выполнено успешно','hint');

	break; #Cоздание учетной записи








	/*******************************************************************
	 * Редактирование учетной записи
	 ******************************************************************/
	case 'user.edit':

		if(!$user->checkAccess('can_user_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$user_id		= $request->getId('user_id', 0);
		$username		= trim($request->getStr('username', ''));
		$password		= trim($request->getStr('password', ''));

		if($user_id == 1) return Ajax::_responseError('Ошибка','Редактирование учетной записи разработчика запрещено');

		if(empty($user_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор пользователя');
		$user_info = $db->selectRecord('SELECT * FROM `users` WHERE `user_id`='.$user_id);
		if(empty($user_info)){
			return Ajax::_responseError('Ошибка','Пользователь ID:'.$user_id.' не найден');
		}

		$updates = array();

		//Смена пароля
		if(!empty($password)){
			$updates['password'] = sha1($password);
		}

		//Смена логина
		if(!empty($username) && $username!= $user_info['username']){
			if($db->result('SELECT IFNULL(count(*),0) FROM `users` WHERE `username` LIKE "'.addslashes($username).'" AND `user_id`<>'.$user_id.' LIMIT 1') > 0) return Ajax::_responseError('Ошибка','Указанны логин уже занят');
			$updates['username'] = $username;
		}

		$updates['name']		= trim($request->getStr('name', ''));
		$updates['enabled']		= $request->getBoolAsInt('enabled', 0);
		$updates['updated']		= 1;

		$uaccess = array();
		$raccess = $request->getArray('access', array());
		foreach($raccess as $ra){
			if($user->accessObjectExists($ra)) $uaccess[]=$ra;
		}

		$db->transaction();

		#Обновление сведений
		if($db->updateRecord('users',array('user_id'=>$user_id), $updates)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка обновления сведений пользователя');
		}

		#Обновление прав доступа
		if($db->delete('DELETE FROM `user_access` WHERE `user_id`='.$user_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка обновления сведений пользователя');
		}

		foreach($uaccess as $ua){
			if($db->addRecord('user_access',array(
				'user_id'	=> $user_id,
				'access'	=> $ua,
				'level'		=> 1
			))===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка','Ошибка обновления сведений пользователя');
			}
		}

		$updates['password'] = (isset($updates['password']) ? '[changed]' : '[not modified]');
		$user_info['password'] = '[*****]';
		$updates['access'] = $uaccess;
		unset($user_info['updated']);
		unset($updates['updated']);
		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменена учетная запись пользователя',
			'data'		=> array(
				'user_id'	=> $user_id,
				'prev'		=> $user_info,
				'new'		=> $updates
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'user_info'			=> $db->selectRecord('SELECT *,"" as `password` FROM `users` WHERE `user_id`='.$user_id),
			'user_access'		=> $db->select('SELECT * FROM `user_access` WHERE `user_id`='.$user_id.' AND `level`>0')
		));

		return Ajax::_responseSuccess('Редактирование учетной записи','Выполнено успешно','hint');

	break; #Редактирование учетной записи





	/*******************************************************************
	 * Поиск записей протокола
	 ******************************************************************/
	case 'protocol.search':
		if(!$user->checkAccess('can_protocol_view')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$user_id = $request->getId('user_id',0);
		$date_from = $request->getDate('date_from',date('d.m.Y'));
		$date_to = $request->getDate('date_to',date('d.m.Y'));
		$limit = $request->getId('limit',100);

		$db = Database::getInstance('main');

		$sql = 'SELECT * FROM `user_actionlog`
			WHERE '.($user_id > 0 ? '`user_id` ='.$user_id.' AND ' : '').' `timestamp` BETWEEN "'.date2sql($date_from).' 00:00:00" AND "'.date2sql($date_to).' 23:59:59"
			ORDER BY `action_uid` DESC LIMIT '.$limit;

		#Выполнено успешно
		return Ajax::_setData(array(
			'protocol' => $db->select($sql)
		));

	break; #Поиск записей протокола






	/*******************************************************************
	 * Получение информации о сессии
	 ******************************************************************/
	case 'protocol.session.info':
		if(!$user->checkAccess('can_protocol_view')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$session_id = $request->getId('session_id',0);
		if(empty($session_id)) return Ajax::_responseError('Ошибка выполнения','Не задан идентификатор сессии');

		$db->prepare('SELECT * FROM `user_authlog` WHERE `session_uid`=? LIMIT 1');
		$db->bind($session_id);
		$info = $db->selectRecord();
		if(empty($info)) return Ajax::_responseError('Ошибка выполнения','Не найдена информация по сессии ID:'.$session_id);

		#Выполнено успешно
		return Ajax::_setData(array(
			'session_info' => $info
		));

	break; #Получение информации о сессии




	/*******************************************************************
	 * Получение информации о действии пользователя
	 ******************************************************************/
	case 'protocol.action.data':
		if(!$user->checkAccess('can_protocol_view')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$action_uid = $request->getId('action_uid',0);
		if(empty($action_uid)) return Ajax::_responseError('Ошибка выполнения','Не задан идентификатор действия');

		$db->prepare('SELECT * FROM `user_actiondata` WHERE `action_uid`=? LIMIT 1');
		$db->bind($action_uid);
		$info = $db->selectRecord();

		$result = array(
			'action_uid'	=> $action_uid,
			'data'			=> (empty($info['data']) ? null : unserialize($info['data']))
		);

		#Выполнено успешно
		return Ajax::_setData(array(
			'action_data' => $result
		));

	break; #Получение информации о действии пользователя





	default:
	Ajax::_responseError('/admin/ajax/users','Not found: '.Request::_get('action'));
}
?>