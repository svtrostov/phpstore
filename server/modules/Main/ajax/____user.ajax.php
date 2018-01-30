<?php
/*==================================================================================================
Title	: User AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');

LABEL_user_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){


	/*******************************************************************
	 * Проверка занятости логина
	 ******************************************************************/
	case 'user.check.username':
		$username = trim($request->getStr('username', ''));
		$found = true;
		if(!empty($username)){
			$found = $user->loginExists($username);
		}

		#Выполнено успешно
		Ajax::_setData(array(
			'username_exists' => $found
		));
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'));
	break;




	/*******************************************************************
	 * Добавление пользователя
	 ******************************************************************/
	case 'user.new':

		#Проверка прав доступа
		if(!$user->checkAccess('can_edit_users')){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/access/create_users'));
		}

		$username		= trim($request->getStr('username', ''));
		$password		= trim($request->getStr('password', ''));
		$name			= trim($request->getStr('name', ''));
		$email			= trim($request->getEmail('email', ''));

		if(empty($username)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/username_empty'));
		if(empty($password)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/password_empty'));
		if(empty($name)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/name_empty'));
		if($user->userExists($username)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/username_exists'));

		$db->transaction();

		$unew = array(
			'user_id'						=> null,					#[uint, autoinc] Идентификатор пользователя
			'client_id'						=> $user->getClientID(),	#[uint] Идентификатор клиента
			'is_inactive'					=> 0,						#[uint, 1] Признак, указывающий что запись заблокирована
			'username'						=> $username,				#[char, 32] Логин
			'password'						=> sha1($password),			#[char, 40] Пароль
			'name'							=> $name,					#[char, 128] Полное имя пользователя
			'email'							=> $email,					#[char, 128] Контактный email
			'phone'							=> trim($request->getStr('phone', '')),
			'country'						=> trim($request->getStr('country', '')),
			'city'							=> trim($request->getStr('city', '')),
			'address'						=> trim($request->getStr('address', '')),
			'zip'							=> trim($request->getStr('zip', '')),
			'can_view_users'				=> $request->getBoolAsInt('can_view_users', 0),
			'can_edit_users'				=> $request->getBoolAsInt('can_edit_users', 0),
			'can_edit_hosts'				=> $request->getBoolAsInt('can_edit_hosts', 0),
			'can_view_stats'				=> $request->getBoolAsInt('can_view_stats', 0),
			'can_view_payments'				=> $request->getBoolAsInt('can_view_payments', 0),
			'can_edit_payment_details'		=> $request->getBoolAsInt('can_edit_payment_details', 0),
			'create_user'					=> $user->getUserID(),
			'update_user'					=> $user->getUserID()
		);

		#Добавление пользователя
		if(($user_id = $user->userNew($unew))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/add'));
		}
		$unew['user_id'] = $user_id;

		if(Protocol::_add(array(
			'action'	=> 'user.new',
			'data'		=> $unew
		))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/add'));
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'user_id'	=> $user_id,
			'record'	=> $user->getUsersList(array('user_id'=>$user_id), null, true)
		));
		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Добавление пользователя






	/*******************************************************************
	 * Редактирование пользователя
	 ******************************************************************/
	case 'user.edit':

		#Проверка прав доступа
		if(!$user->checkAccess('can_edit_users')){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/access/edit_users'));
		}

		$user_id		= $request->getId('user_id', 0);
		$name			= trim($request->getStr('name', ''));
		$password		= trim($request->getStr('password', ''));
		$email			= trim($request->getEmail('email', ''));
		$is_inactive	= $request->getBoolAsInt('is_inactive', 0);
		$phone			= trim($request->getStr('phone', ''));
		$country		= trim($request->getStr('country', ''));
		$city			= trim($request->getStr('city', ''));
		$address		= trim($request->getStr('address', ''));
		$zip			= trim($request->getStr('zip', ''));

		if(empty($user_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));
		if(empty($name)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/name_empty'));
		if(!empty($password)) $password=sha1($password);

		if($user_id == $user->getUserID() && $is_inactive == 1) return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/lock_self'));

		#Проверка существования пользователя
		$record = $user->getUsersList(array('user_id'=>$user_id), null, true);
		if(empty($record)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/object_not_found'));
		}

		#Получение сведений о клиенте
		$client_info = $client->getClientInfo($record['client_id']);
		if(empty($client_info)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/object_not_found'));
		}
		$is_main_user = ($client_info['main_user'] == $user_id);
		if($is_main_user && $is_inactive == 1) return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/lock_main'));


		$updcount=0;
		$upd_prev=array();
		$upd_new=array();
		if($name!=$record['name']){$upd_prev['name'] = $record['name'];$upd_new['name'] = $name;$updcount++;}
		if(!empty($password)){$upd_prev['password'] = '*prev password*';$upd_new['password'] = $password;$updcount++;}
		if($is_inactive!=$record['is_inactive']){$upd_prev['is_inactive'] = $record['is_inactive'];$upd_new['is_inactive'] = $is_inactive;$updcount++;}
		if($email!=$record['email']){$upd_prev['email'] = $record['email'];$upd_new['email'] = $email;$updcount++;}
		if($phone!=$record['phone']){$upd_prev['phone'] = $record['phone'];$upd_new['phone'] = $phone;$updcount++;}
		if($country!=$record['country']){$upd_prev['country'] = $record['country'];$upd_new['country'] = $country;$updcount++;}
		if($city!=$record['city']){$upd_prev['city'] = $record['city'];$upd_new['city'] = $city;$updcount++;}
		if($address!=$record['address']){$upd_prev['address'] = $record['address'];$upd_new['address'] = $address;$updcount++;}
		if($zip!=$record['zip']){$upd_prev['zip'] = $record['zip'];$upd_new['zip'] = $zip;$updcount++;}

		$flds=array('can_view_users','can_edit_users','can_edit_hosts','can_view_stats','can_view_payments','can_edit_payment_details');
		foreach($flds as $field){
			$val = $request->getBoolAsInt($field, 0);
			if($val != $record[$field]){
				if($is_main_user){
					return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/access_main'));
				}
				$upd_prev[$field] = $record[$field];
				$upd_new[$field] = $val;
				$updcount++;
			}
		}

		if($updcount==0) return Ajax::_responseSuccess(Language::_get('general','errors/nothing_happens'),Language::_get('general','errors/no_made_changes'),'hint');

		$db->transaction();

		#Обновление группы
		if($user->userUpdate($user_id, $upd_new)===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/edit'));
		}

		if(isset($upd_new['password'])) $upd_new['password'] = '*new password*';

		if(Protocol::_add(array(
			'action'	=> 'user.edit',
			'data'		=> array(
				'user_id'	=> $user_id,
				'prev'		=> $upd_prev,
				'new'		=> $upd_new
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/edit'));
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'record'	=> $user->getusersList(array('user_id'=>$user_id), null, true)
		));

		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Редактирование пользователя






	/*******************************************************************
	 * Удаление пользователя
	 ******************************************************************/
	case 'user.delete':

		#Проверка прав доступа
		if(!$user->checkAccess('can_edit_users')){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/access/edit_users'));
		}

		$user_id = $request->getId('user_id', 0);

		if(empty($user_id)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/incorrect_request'));

		if($user_id == $user->getUserID()) return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/delete_self'));

		#Проверка существования
		$record = $user->getUsersList(array('user_id'=>$user_id), null, true);
		if(empty($record)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/object_not_found'));
		}
		#Получение сведений о клиенте
		$client_info = $client->getClientInfo($record['client_id']);
		if(empty($client_info)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/object_not_found'));
		}
		$is_main_user = ($client_info['main_user'] == $user_id);
		if($is_main_user) return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/delete_main'));

		$db->transaction();

		#Обновление группы
		if($user->userUpdate($user_id, array(
			'is_deleted'	=> 1
		))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/delete'));
		}

		if(Protocol::_add(array(
			'action'	=> 'user.delete',
			'data'		=> array(
				'user_id'	=> $user_id
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/delete'));
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'can_edit'	=> $user->checkAccess('can_edit_campaign_users'),
			'records'	=> $user->getusersList()
		));

	break; #Удаление пользователя




	/*******************************************************************
	 * Редактирование собственного профиля
	 ******************************************************************/
	case 'user.profile':

		$user_id		= $user->getUserID();
		$name			= trim($request->getStr('name', ''));
		$password		= trim($request->getStr('password', ''));
		$email			= trim($request->getEmail('email', ''));
		$phone			= trim($request->getStr('phone', ''));
		$country		= trim($request->getStr('country', ''));
		$city			= trim($request->getStr('city', ''));
		$address		= trim($request->getStr('address', ''));
		$zip			= trim($request->getStr('zip', ''));

		if(empty($name)) return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/user.ajax/name_empty'));
		if(!empty($password)) $password=sha1($password);

		#Проверка существования пользователя
		$record = $user->getUsersList(array('user_id'=>$user_id), null, true);
		if(empty($record)){
			return Ajax::_responseError(Language::_get('general','errors/executing'), Language::_get('general','errors/object_not_found'));
		}

		$updcount=0;
		$upd_prev=array();
		$upd_new=array();
		if($name!=$record['name']){$upd_prev['name'] = $record['name'];$upd_new['name'] = $name;$updcount++;}
		if(!empty($password)){$upd_prev['password'] = '*prev password*';$upd_new['password'] = $password;$updcount++;}
		if($email!=$record['email']){$upd_prev['email'] = $record['email'];$upd_new['email'] = $email;$updcount++;}
		if($phone!=$record['phone']){$upd_prev['phone'] = $record['phone'];$upd_new['phone'] = $phone;$updcount++;}
		if($country!=$record['country']){$upd_prev['country'] = $record['country'];$upd_new['country'] = $country;$updcount++;}
		if($city!=$record['city']){$upd_prev['city'] = $record['city'];$upd_new['city'] = $city;$updcount++;}
		if($address!=$record['address']){$upd_prev['address'] = $record['address'];$upd_new['address'] = $address;$updcount++;}
		if($zip!=$record['zip']){$upd_prev['zip'] = $record['zip'];$upd_new['zip'] = $zip;$updcount++;}

		if($updcount==0) return Ajax::_responseSuccess(Language::_get('general','errors/nothing_happens'),Language::_get('general','errors/no_made_changes'),'hint');

		$db->transaction();

		#Обновление группы
		if($user->userUpdate($user_id, $upd_new)===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/edit'));
		}

		if(isset($upd_new['password'])) $upd_new['password'] = '*new password*';

		if(Protocol::_add(array(
			'action'	=> 'user.edit.profile',
			'data'		=> array(
				'user_id'	=> $user_id,
				'prev'		=> $upd_prev,
				'new'		=> $upd_new
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError(Language::_get('general','errors/executing'),Language::_get('general','errors/edit'));
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_setData(array(
			'record'	=> $user->getusersList(array('user_id'=>$user_id), null, true)
		));

		return Ajax::_responseSuccess(Language::_get('general','ajax/success'),Language::_get('general','ajax/action_success'),'hint');

	break; #Редактирование пользователя




	default:
	Ajax::_responseError('/main/ajax/user',Language::_get('general','errors/handler_not_found').': '.Request::_get('action'));
}
?>
