<?php
/*==================================================================================================
Title	: Admin Property AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');
$shop = Shop::getInstance();


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){

	/*******************************************************************
	 * Добавление группы характеристик
	 ******************************************************************/
	case 'property.group.add':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$name = trim($request->getStr('name',''));
		if(empty($name)) return Ajax::_responseError('Ошибка','Не задано название группы свойств');

		$db->transaction();

		$pgroup_id = $db->addRecord('property_groups',array(
			'name'	=> $name
		));
		if(empty($pgroup_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка добавления группы');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Создана группа характеристик',
			'data'		=> array(
				'pgroup_id'	=> $pgroup_id,
				'name'		=> $name
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'properties_tree'	=> $shop->getPropertiesTree(),
			'selected_pgroup_id'=> $pgroup_id
		));

	break;#Добавление группы характеристик



	/*******************************************************************
	 * Редактирование группы характеристик
	 ******************************************************************/
	case 'property.group.edit':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$pgroup_id = $request->getId('pgroup_id',0);
		$name = trim($request->getStr('name',''));
		if(empty($pgroup_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор группы свойств');
		if(empty($name)) return Ajax::_responseError('Ошибка','Не задано название группы свойств');
		$group = $db->selectRecord('SELECT * FROM `property_groups` WHERE `pgroup_id`='.$pgroup_id.' LIMIT 1');
		if(empty($group)) return Ajax::_responseError('Ошибка','Группа свойств ID:'.$pgroup_id.' не найдена');

		$db->transaction();

		if($db->updateRecord('property_groups',array('pgroup_id'=>$pgroup_id), array(
			'name'	=> $name
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка изменения группы');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменена группа характеристик',
			'data'		=> array(
				'pgroup_id'	=> $pgroup_id,
				'name_prev'	=> $group['name'],
				'name_new'	=> $name
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'properties_tree'	=> $shop->getPropertiesTree(),
			'selected_pgroup_id'=> $pgroup_id
		));

	break;#Редактирование группы характеристик



	/*******************************************************************
	 * Удаление группы характеристик
	 ******************************************************************/
	case 'property.group.delete':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$pgroup_id = $request->getId('pgroup_id',0);
		if(empty($pgroup_id)) return Ajax::_responseError('Ошибка','Не задан идентификатор группы свойств');
		$group = $db->selectRecord('SELECT * FROM `property_groups` WHERE `pgroup_id`='.$pgroup_id.' LIMIT 1');
		if(empty($group)) return Ajax::_responseError('Ошибка','Группа свойств ID:'.$pgroup_id.' не найдена');

		$db->transaction();

		if($db->delete('DELETE FROM `property_groups` WHERE `pgroup_id`='.$pgroup_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления группы');
		}

		if($db->update('UPDATE `properties` SET `pgroup_id`=0 WHERE `pgroup_id`='.$pgroup_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления группы');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удалена группа характеристик',
			'data'		=> $group
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'properties_tree'	=> $shop->getPropertiesTree()
		));

	break;#Удаление группы характеристик




	/*******************************************************************
	 * Добавление характеристики
	 ******************************************************************/
	case 'property.add':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$pgroup_id = $request->getId('pgroup_id',0);
		$name = trim($request->getStr('name',''));
		if(empty($name)) return Ajax::_responseError('Ошибка','Не задано название характеристики');

		if(!empty($pgroup_id)){
			$group = $db->selectRecord('SELECT * FROM `property_groups` WHERE `pgroup_id`='.$pgroup_id.' LIMIT 1');
			if(empty($group)) return Ajax::_responseError('Ошибка','Группа свойств ID:'.$pgroup_id.' не найдена');
		}

		$add = array(
			'pgroup_id'	=> $pgroup_id,
			'name'		=> $name
		);

		$db->transaction();

		$property_id = $db->addRecord('properties',$add);
		if(empty($property_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка добавления характеристики');
		}
		$add['property_id'] = $property_id;
		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавлена характеристика',
			'data'		=> $add
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'properties_tree'	=> $shop->getPropertiesTree(),
			'selected_property_id'=> $property_id
		));

	break;#Добавление характеристики




	/*******************************************************************
	 * Изменение характеристики
	 ******************************************************************/
	case 'property.edit':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$pgroup_id = $request->getId('pgroup_id',0);
		$type = $request->getEnum('type',array('list','multilist','bool','num'),'list');
		$name = trim($request->getStr('name',''));
		$measure = trim($request->getStr('measure',''));
		$admin_info = trim($request->getStr('admin_info',''));
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($name)) return Ajax::_responseError('Ошибка','Не задано название характеристики');
		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		if(!empty($pgroup_id)){
			$group = $db->selectRecord('SELECT * FROM `property_groups` WHERE `pgroup_id`='.$pgroup_id.' LIMIT 1');
			if(empty($group)) return Ajax::_responseError('Ошибка','Группа свойств ID:'.$pgroup_id.' не найдена');
		}

		$update = array(
			'pgroup_id'	=> $pgroup_id,
			'name'		=> $name,
			'admin_info'=> $admin_info,
			'type'		=> $type,
			'measure'	=> $measure
		);

		$db->transaction();

		$prev_is_list = ($type=='list'||$type=='multilist');
		$new_is_list = ($property['type']=='list'||$property['type']=='multilist');

		if($type != $property['type']){
			if($db->delete('DELETE FROM `product_properties` WHERE `property_id`='.$property_id)===false){
				$db->rollback();
				return Ajax::_responseError('Ошибка','Ошибка обновления характеристики');
			}
		}

		if($db->updateRecord('properties',array('property_id'=>$property_id),$update)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка обновления характеристики');
		}
		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Обновлена характеристика',
			'data'		=> array(
				'property_id'=>$property_id,
				'prev'	=> $property,
				'new'	=> $update
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Изменение характеристики','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'properties_tree'		=> $shop->getPropertiesTree(),
			'selected_property_id'	=> $property_id
		));

	break;#Изменение характеристики



	/*******************************************************************
	 * Удаление характеристики
	 ******************************************************************/
	case 'property.delete':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$property_id = $request->getId('property_id',0);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');
		$property_values = $db->selectFromField('name','SELECT * FROM `property_values` WHERE `property_id`='.$property_id);
		$db->transaction();

		if($db->delete('DELETE FROM `properties` WHERE `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления характеристики: properties');
		}

		if($db->delete('DELETE FROM `property_values` WHERE `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления характеристики: property_values');
		}

		if($db->delete('DELETE FROM `product_properties` WHERE `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления характеристики: product_properties');
		}

		if($db->delete('DELETE FROM `category_properties` WHERE `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления характеристики: category_properties');
		}

		$property['values'] = $property_values;

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удалена характеристика',
			'data'		=> $property
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Удаление характеристики','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'properties_tree'	=> $shop->getPropertiesTree()
		));

	break;#Удаление характеристики




	/*******************************************************************
	 * Получение свойств характеристики товара
	 ******************************************************************/
	case 'property.info':

		$property_id = $request->getId('property_id',0);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_info'		=> $property,
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`'),
			'property_categories'	=> $shop->getPropertyCategories($property_id)
		));

	break;#Получение свойств характеристики товара



	/*******************************************************************
	 * Добавление значения в список характеристики товара
	 ******************************************************************/
	case 'property.value.add':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$value = trim($request->getStr('value',''));
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($value)) return Ajax::_responseError('Ошибка','Нельзя добавить пустое значение');
		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		$max_value_pos = intval($db->result('SELECT IFNULL(max(`pos`),0) FROM `property_values` WHERE `property_id`='.$property_id));

		$value_id=$db->addRecord('property_values',array(
			'name'=>$value,
			'property_id' => $property_id,
			'pos'		=> $max_value_pos + 1
		));
		if(empty($value_id)) return Ajax::_responseError('Ошибка','Ошибка добавления значения в список характеристики');


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавлено значение в список характеристики товара',
			'data'		=> array(
				'value_id'	=> $value_id,
				'name'		=> $value,
				'property_id' => $property_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`'),
			'selected_value_id'	=> $value_id
		));

	break;#Добавление значения в список характеристики товара



	/*******************************************************************
	 * Редактирование значения в списке характеристики товара
	 ******************************************************************/
	case 'property.value.edit':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$value_id = $request->getId('value_id',0);
		$value = trim($request->getStr('value',''));
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($value_id)) return Ajax::_responseError('Ошибка','Не задан ID значения характеристики');
		if(empty($value)) return Ajax::_responseError('Ошибка','Нельзя задать пустое значение');
		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');
		$property_value = $db->selectRecord('SELECT * FROM `property_values` WHERE `value_id`='.$value_id.' AND `property_id`='.$property_id.' LIMIT 1');
		if(empty($property_value)) return Ajax::_responseError('Ошибка','Значение характеристики ID:'.$value_id.' не найдено');

		if($db->updateRecord('property_values',array(
			'value_id'		=> $value_id,
			'property_id'	=>  $property_id
		),array(
			'name'=>$value
		))===false) return Ajax::_responseError('Ошибка','Ошибка изменения значения в списке характеристики');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменено значение в списке характеристики товара',
			'data'		=> array(
				'value_id'		=> $value_id,
				'property_id'	=> $property_id,
				'name_prev'		=> $property_value['name'],
				'name_new'		=> $value
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`'),
			'selected_value_id'	=> $value_id
		));

	break; #Редактирование значения в списке характеристики товара




	/*******************************************************************
	 * Изменение позиции значения в списке характеристики товара
	 ******************************************************************/
	case 'property.value.pos':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$value_id = $request->getId('value_id',0);
		$position = $request->getEnum('pos',array('up','down'),'');
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($value_id)) return Ajax::_responseError('Ошибка','Не задан ID значения характеристики');
		if(empty($position)) return;

		$property_value = $db->selectRecord('SELECT * FROM `property_values` WHERE `value_id`='.$value_id.' AND `property_id`='.$property_id.' LIMIT 1');
		if(empty($property_value)) return Ajax::_responseError('Ошибка','Значение характеристики ID:'.$value_id.' не найдено');

		$sql = 'SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' AND `pos`'.($position=='up'?'<':'>').$property_value['pos'].' ORDER BY `pos` '.($position=='up'?'DESC':'ASC').' LIMIT 1';
		$satelite = $db->selectRecord($sql);
		if(!empty($satelite)){
			$db->update('UPDATE `property_values` SET `pos`='.$satelite['pos'].' WHERE `value_id`='.$property_value['value_id']);
			$db->update('UPDATE `property_values` SET `pos`='.$property_value['pos'].' WHERE `value_id`='.$satelite['value_id']);
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`'),
			'selected_value_id'	=> $value_id
		));

	break; # Изменение позиции значения в списке характеристики товара



	/*******************************************************************
	 * Удаление значения из списка характеристики товара
	 ******************************************************************/
	case 'property.value.delete':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$value_id = $request->getId('value_id',0);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($value_id)) return Ajax::_responseError('Ошибка','Не задан ID значения характеристики');

		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		$property_value = $db->selectRecord('SELECT * FROM `property_values` WHERE `value_id`='.$value_id.' AND `property_id`='.$property_id.' LIMIT 1');
		if(empty($property_value)) return Ajax::_responseError('Ошибка','Значение характеристики ID:'.$value_id.' не найдено');

		$db->transaction();

		if($db->delete('DELETE FROM `property_values` WHERE `value_id`='.$value_id.' AND `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления значения из списка характеристики товара');
		}

		if($db->delete('DELETE FROM `product_properties` WHERE `value_id`='.$value_id.' AND `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка удаления значения из списка характеристики товара');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удалено значение из списка характеристики товара',
			'data'		=> $property_value
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`')
		));

	break;#Удаление значения из списка характеристики товара




	/*******************************************************************
	 * Сорттировка списка характеристик товара
	 ******************************************************************/
	case 'property.value.sort':

		if(!$user->checkAccess('can_property_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$sort = strtoupper($request->getEnum('sort',array('asc','desc'),''));
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($sort)) return Ajax::_responseError('Ошибка','Не задано направление сортировки');

		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		$values = $db->select('SELECT `value_id` FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `name` '.$sort);
		if(!empty($values)){
			$pos = 1;
			foreach($values as $v){
				$db->update('UPDATE `property_values` SET `pos`='.$pos.' WHERE `value_id`='.$v['value_id']);
				$pos++;
			}
		}

		#Выполнено успешно
		return Ajax::_setData(array(
			'property_values'	=> $db->select('SELECT * FROM `property_values` WHERE `property_id`='.$property_id.' ORDER BY `pos`')
		));

	break; # Изменение позиции значения в списке характеристики товара





	/*******************************************************************
	 * Добавление характеристики каталогу в качестве фильтра
	 ******************************************************************/
	case 'property.category.add':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$category_id = $request->getId('category_id',0);
		$from_catalog	= $request->getBool('from_catalog', false);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($category_id)) return Ajax::_responseError('Ошибка','Не задан ID каталога');

		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		$category = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($category)) return Ajax::_responseError('Ошибка','Каталог ID:'.$category_id.' не найден');

		if(intval($db->result('SELECT IFNULL(count(*),0) FROM `category_properties` WHERE `category_id`='.$category_id.' AND `property_id`='.$property_id.' LIMIT 1'))>0){
			Ajax::_responseSuccess('Уведомление','Выбранная характеристика уже задана каталогу в качестве фильтра','hint');
			return;
		}

		$id=$db->addRecord('category_properties',array(
			'property_id'	=> $property_id,
			'category_id'	=> $category_id
		));
		if(empty($id)) return Ajax::_responseError('Ошибка','Ошибка добавления характеристики каталогу в качестве фильтра');


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Характеристика добавлена каталогу в качестве фильтра',
			'data'		=> array(
				'property_id'	=> $property_id,
				'category_id'	=> $category_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(
		$from_catalog ? 
			array('category_properties'	=> $shop->getCategoryProperties($category_id))
		:
			array('property_categories'	=> $shop->getPropertyCategories($property_id))
		);

	break;#Добавление характеристики каталогу в качестве фильтра



	/*******************************************************************
	 * Удаление характеристики из фильтров каталога
	 ******************************************************************/
	case 'property.category.delete':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$category_id = $request->getId('category_id',0);
		$from_catalog	= $request->getBool('from_catalog', false);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($category_id)) return Ajax::_responseError('Ошибка','Не задан ID каталога');


		if($db->delete('DELETE FROM `category_properties` WHERE `category_id`='.$category_id.' AND `property_id`='.$property_id)===false) return Ajax::_responseError('Ошибка','Ошибка удаления характеристики из фильтров каталога');


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Характеристика удалена из фильтров каталога',
			'data'		=> array(
				'property_id'	=> $property_id,
				'category_id'	=> $category_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(
		$from_catalog ? 
			array('category_properties'	=> $shop->getCategoryProperties($category_id))
		:
			array('property_categories'	=> $shop->getPropertyCategories($property_id))
		);

	break;#Удаление характеристики из фильтров каталога




	/*******************************************************************
	 * Добавление характеристики товару
	 ******************************************************************/
	case 'property.product.add':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$product_id = $request->getId('product_id',0);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($product_id)) return Ajax::_responseError('Ошибка','Не задан ID товара');

		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка','Товар ID:'.$product_id.' не найден');

		if(intval($db->result('SELECT IFNULL(count(*),0) FROM `product_properties` WHERE `product_id`='.$product_id.' AND `property_id`='.$property_id.' LIMIT 1'))>0){
			Ajax::_responseSuccess('Уведомление','Выбранная характеристика уже добавлена','hint');
			return;
		}

		$id=$db->addRecord('product_properties',array(
			'property_id'	=> $property_id,
			'product_id'	=> $product_id
		));
		if(empty($id)) return Ajax::_responseError('Ошибка','Ошибка добавления характеристики товару');


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Характеристика добавлена товару',
			'data'		=> array(
				'property_id'	=> $property_id,
				'product_id'	=> $product_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'product_properties' 	=> Shop::_getProductProperties($product_id),
			'selected_property_id'	=> $property_id
		));

	break;#Добавление характеристики товару



	/*******************************************************************
	 * Изменение значений характеристики товара
	 ******************************************************************/
	case 'property.product.applied':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$product_id = $request->getId('product_id',0);
		$type = $request->getEnum('type',array('list','multilist','bool','num'),false);
		switch($type){
			case 'multilist':
				$applied = $request->getArray('applied',null);
				if(!empty($applied)) $applied = array_map('intval',$applied);
			break;
			case 'list':
				$applied = $request->getId('applied',0);
			break;
			case 'bool':
				$applied = $request->getBoolAsInt('applied',0);
			break;
			case 'num':
				$applied = $request->getFloat('applied',0);
			break;
			default: $applied = null;
		}
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($product_id)) return Ajax::_responseError('Ошибка','Не задан ID товара');

		$property = $db->selectRecord('SELECT * FROM `properties` WHERE `property_id`='.$property_id.' LIMIT 1');
		if(empty($property)) return Ajax::_responseError('Ошибка','Характеристика ID:'.$property_id.' не найдена');

		if($property['type']!=$type) return Ajax::_responseError('Ошибка','Тип данных характеристики в запросе задан некорректно');

		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка','Товар ID:'.$product_id.' не найден');

		$db->transaction();

		if($db->delete('DELETE FROM `product_properties` WHERE `product_id`='.$product_id.' AND `property_id`='.$property_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка изменения значений характеристики: product_properties');
		}

		$add=array(
			'property_id'	=> $property_id,
			'product_id'	=> $product_id
		);

		switch($type){
			case 'list':
				$add['value_id'] = $applied;
				if($db->addRecord('product_properties',$add)===false){
					$db->rollback();
					return Ajax::_responseError('Ошибка','Ошибка изменения значений характеристики: list');
				}
			break;
			case 'num':
				$add['value_num'] = $applied;
				if($db->addRecord('product_properties',$add)===false){
					$db->rollback();
					return Ajax::_responseError('Ошибка','Ошибка изменения значений характеристики: num');
				}
			break;
			case 'bool':
				$add['value_bool'] = $applied;
				if($db->addRecord('product_properties',$add)===false){
					$db->rollback();
					return Ajax::_responseError('Ошибка','Ошибка изменения значений характеристики: bool');
				}
			break;
			case 'multilist':
				if(!empty($applied)&&is_array($applied)){
					$add['values'] = $applied;
					foreach($applied as $value_id){
						if($db->addRecord('product_properties',array(
							'property_id'	=> $property_id,
							'product_id'	=> $product_id,
							'value_id'		=> $value_id
						))===false){
							$db->rollback();
							return Ajax::_responseError('Ошибка','Ошибка изменения значений характеристики: multilist');
						}
					}
				}
			break;
		}


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменение значений характеристики товара',
			'data'		=> $add
		));

		$db->commit();

		#Выполнено успешно
		Ajax::_responseSuccess('Изменение значений характеристики товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_properties' 	=> Shop::_getProductProperties($product_id),
			'selected_property_id'	=> $property_id
		));

	break;#Изменение значений характеристики товара




	/*******************************************************************
	 * Удаление характеристики из товара
	 ******************************************************************/
	case 'property.product.delete':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$property_id = $request->getId('property_id',0);
		$product_id = $request->getId('product_id',0);
		if(empty($property_id)) return Ajax::_responseError('Ошибка','Не задан ID характеристики');
		if(empty($product_id)) return Ajax::_responseError('Ошибка','Не задан ID товара');

		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка','Товар ID:'.$product_id.' не найден');

		if($db->delete('DELETE FROM `product_properties` WHERE `product_id`='.$product_id.' AND `property_id`='.$property_id)===false) return Ajax::_responseError('Ошибка','Ошибка удаления характеристики из товара');


		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Характеристика удалена из товара',
			'data'		=> array(
				'property_id'	=> $property_id,
				'product_id'	=> $product_id
			)
		));

		#Выполнено успешно
		return Ajax::_setData(array(
			'product_properties' 	=> Shop::_getProductProperties($product_id),
		));

	break;#Удаление характеристики из товара




	default:
	Ajax::_responseError('/admin/ajax/property','Not found: '.Request::_get('action'));
}
?>
