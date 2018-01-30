<?php
/*==================================================================================================
Title	: Admin Catalog AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');

function get_category_info($category_id, $what){
	$db = Database::getInstance('main');
	$shop = Shop::getInstance();
	$result = array();

	if(!empty($what['info'])){
		$result['category_info'] = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($result['category_info'])) return 'Каталог не найден';

		$parents=array();
		$parent_id = $result['category_info']['parent_id'];
		while($parent_id>0){
			$info = $db->selectRecord('SELECT `parent_id`,`name` FROM `categories` WHERE `category_id`='.$parent_id.' LIMIT 1');
			$parents[]=array('category_id'=>$parent_id, 'name'=>$info['name']);
			$parent_id = $info['parent_id'];
		}
		$result['category_info']['parents'] = array_reverse($parents);
		$result['category_properties'] = Shop::_getCategoryProperties($category_id);
	}

	if(!empty($what['products'])){
		$limit  = 0;
		$offset = 0;
		$count  = 0;
		$page_no= 0;
		if(!empty($what['navigator'])){
			$count = $db->result('SELECT IFNULL(count(*),0) FROM `products` WHERE `category_id`='.$category_id);
			$limit = $what['per_page'];
			$page_no = ($what['page_no'] > 0 ? $what['page_no'] - 1 : 0);
			$page_max = ceil($count / $limit);
			if($page_max > 0 && $page_no >= $page_max) $page_no = $page_max-1;
			$offset = $page_no * $limit;
			$result['navigator'] = array(
				'count'		=> intval($count),
				'page_no'	=> $page_no + 1,
				'per_page'	=> $limit,
				'page_max'	=> $page_max,
				'offset'	=> $offset + 1
			);
		}
		$p = $db->select('SELECT P.*,PI.`description` as `desc`, (SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=P.`product_id`) as `count` FROM `products` as P LEFT JOIN `product_info` as PI ON PI.`product_id`=P.`product_id` WHERE P.`category_id`='.$category_id.($limit>0 ? ' LIMIT '.($offset).','.$limit : ''));
		if(is_array($p)){
			for($i=0; $i<count($p); $i++){
				/*
				if(strlen($p[$i]['desc'])>128){
					$p[$i]['desc'] = substr($p[$i]['desc'],0,128).' ...';
				}
				*/
				$p[$i]['price'] = $shop->getPrice($p[$i]['currency'], $p[$i]['base_price'], false);
				$bridge_info = ($p[$i]['bridge_id']>0 ? $shop->getBridgeInfo($p[$i]['bridge_id'], false) : null);
				if(!empty($bridge_info)){
					$p[$i]['bridge_price'] = $bridge_info['price'];
					$p[$i]['bridge_count'] = $bridge_info['count'];
				}else{
					$p[$i]['bridge_price'] = $p[$i]['price'];
					$p[$i]['bridge_count'] = $p[$i]['count'];
				}
			}
		}
		$result['category_products'] = $p;
	}

	if(!empty($what['categories'])){
		$result['categories'] = Shop::_categoryTree(0,true);
	}

	return $result;
}


#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){

	/*******************************************************************
	 * Получение информации по каталогу
	 ******************************************************************/
	case 'category.info':

		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');

		$result = get_category_info($category_id, array(
			'info'		=> $request->getBool('info',false),
			'products'	=> $request->getBool('products',false),
			'navigator'	=> $request->getBool('navigator',false),
			'page_no'	=> max(1,$request->getId('page_no',1)),
			'per_page'	=> min(1000,max(10,$request->getId('per_page',100)))
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);

		#Выполнено успешно
		return Ajax::_setData($result);

	break;#Получение информации по каталогу



	/*******************************************************************
	 * Смена родительского каталога
	 ******************************************************************/
	case 'category.change.parent':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		$parent_id = $request->getId('parent_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$current = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($current)) return Ajax::_responseError('Ошибка', 'Каталог не найден');
		if($parent_id>0){
			$parent = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$parent_id.' LIMIT 1');
			if(empty($parent)) return Ajax::_responseError('Ошибка', 'Выбранного родительского каталога не существует');
		}

		$db->transaction();

		if($db->update('UPDATE `categories` SET `parent_id`='.$parent_id.' WHERE `category_id`='.$category_id)===false){
			$db->rollback();
			Ajax::_responseError('Ошибка', 'Ошибка смены родительского каталога');
		}


		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Смена родительского каталога у каталога',
			'data'		=> array(
				'category_id'		=> $category_id,
				'last_parent_id'	=> $current['parent_id'],
				'new_parent_id'		=> $parent_id
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$result = get_category_info($category_id, array(
			'info'		=> true,
			'categories'=> true
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);

		#Выполнено успешно
		return Ajax::_setData($result);

	break; #Смена родительского каталога



	/*******************************************************************
	 * Смена информации о каталоге
	 ******************************************************************/
	case 'category.change.info':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		$parent_id = $request->getId('parent_id',0);
		$name = $request->getStr('name','');
		$seo = $request->getStr('seo','');
		$desc = $request->getStr('desc','');
		$enabled = $request->getBoolAsInt('enabled',0);
		$hide_filters = $request->getBoolAsInt('hide_filters',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование каталога');
		if($parent_id>0){
			$parent = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$parent_id.' LIMIT 1');
			if(empty($parent)) return Ajax::_responseError('Ошибка', 'Выбранного родительского каталога не существует');
		}
		$current = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($current)) return Ajax::_responseError('Ошибка', 'Каталог не найден');

		$db->transaction();

		$db->prepare('UPDATE `categories` SET `parent_id`=?,`name`=?,`seo`=?,`desc`=?,`enabled`=?,`hide_filters`=? WHERE `category_id`=?');
		$db->bind($parent_id);
		$db->bind($name);
		$db->bind($seo);
		$db->bind($desc);
		$db->bind($enabled);
		$db->bind($hide_filters);
		$db->bind($category_id);
		if($db->update()===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка изменения информации о каталоге');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменение информации о каталоге',
			'data'		=> array(
				'category_id'	=> $category_id,
				'last'			=> $current,
				'new'			=> array(
					'parent_id'=>$parent_id,
					'name'=>$name,
					'desc'=>$desc,
					'seo'=>$seo,
					'enabled'=>$enabled,
					'hide_filters' => $hide_filters
				)
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$result = get_category_info($category_id, array(
			'info'		=> true,
			'categories'=> ($parent_id != $current['parent_id'] || $enabled != $current['enabled'] || $name != $current['name'] ? true : false),
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);

		#Выполнено успешно
		Ajax::_responseSuccess('Обновление каталога','Выполнено успешно','hint');
		$result['selected_category_id']	= $category_id;
		return Ajax::_setData($result);

	break; #Смена родительского каталога



	/*******************************************************************
	 * Удаление каталога
	 ******************************************************************/
	case 'category.delete':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$current = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($current)) return Ajax::_responseError('Ошибка', 'Каталог не найден');
		$count_products = intval($db->result('SELECT count(*) FROM `products` WHERE `category_id`='.$category_id));
		if(!empty($count_products)) return Ajax::_responseError('Ошибка', 'Нельзя удалить каталог, содержащий товары');
		$count_childs = intval($db->result('SELECT count(*) FROM `categories` WHERE `parent_id`='.$category_id));
		if(!empty($count_childs)) return Ajax::_responseError('Ошибка', 'Нельзя удалить каталог, содержащий подкаталоги');
		$count_sources = intval($db->result('SELECT count(*) FROM `source_items_groups` WHERE `catalog_group_id`='.$category_id));
		if(!empty($count_sources)) return Ajax::_responseError('Ошибка', 'Нельзя удалить каталог, т.к. он связан с одним из каталогов импортируемых товаров (ТЕКО, VTT и т.д.)');

		$db->transaction();

		if($db->delete('DELETE FROM `categories` WHERE `category_id`='.$category_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка удаления каталога');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление каталога',
			'data'		=> $current
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$result = get_category_info($category_id, array(
			'categories'=> true
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);
		return Ajax::_setData($result);

	break;#Удаление каталога



	/*******************************************************************
	 * Добавление каталога
	 ******************************************************************/
	case 'category.add':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$name = trim($request->getStr('name',''));
		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано наименование каталога');

		$db->transaction();

		$category_id = $db->addRecord('categories',array(
			'name'		=> $name,
			'desc'		=> $name,
			'seo'		=> rus2translit($name),
			'enabled'	=> false,
			'parent_id'	=> 0
		));
		if(empty($category_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка создания каталога');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление каталога',
			'data'		=> array(
				'category_id' => $category_id,
				'name'		=> $name
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$result = get_category_info($category_id, array(
			'categories'=> true,
			'info'		=> true
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);
		$result['selected_category_id']	= $category_id;
		$result['category_products'] = array();
		Ajax::_responseSuccess('Создание каталога','Выполнено успешно','hint');
		return Ajax::_setData($result);
	break;# Добавление каталога



	/*******************************************************************
	 * Удаление изображения каталога
	 ******************************************************************/
	case 'category.image.delete':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$category = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($category)) return Ajax::_responseError('Ошибка', 'Каталог ID '.$category_id.' не найден');
		if(!empty($category['pic_small']) && file_exists(DIR_ROOT.$category['pic_small'])){
			if(!unlink(DIR_ROOT.$category['pic_small'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения каталога');
		}
		$db->update('UPDATE `categories` SET `pic_small`="" WHERE `category_id`='.$category_id);
		Ajax::_responseSuccess('Удаление изображения каталога','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'category_image_link' => ''
		));
	break; #Удаление изображения каталога



	/*******************************************************************
	 * Загрузка изображения каталога
	 ******************************************************************/
	case 'category.image.upload':

		if(!$user->checkAccess('can_catalog_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		header('Content-Type: text/html; charset=utf-8', true);
		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$category = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($category)) return Ajax::_responseError('Ошибка', 'Каталог ID '.$category_id.' не найден');

		#Файл не задан
		if(!$_FILES['image']['size']) return Ajax::_responseError('Ошибка выполнения','Не задан файл изображения каталога');

		#Размер файла
		if($_FILES['image']['size'] > 2097152) return Ajax::_responseError('Ошибка выполнения','Слишком большой размер файла');

		#Ошибка загрузки файла
		if($_FILES['image']['error']) return Ajax::_responseError('Ошибка выполнения','Ошибка загрузки файла: '.$_FILES['image']['error']);

		$ext = 'jpg';
		switch($_FILES['image']['type']){
			case 'image/png': $ext = 'png'; break;
			case 'image/gif': $ext = 'gif'; break;
			default: $ext = 'jpg';
		}

		$link = '/client/images/catalog/c_'.$category_id.'_time_'.time().'.'.$ext;
		$filename = DIR_ROOT.$link;

		try{
			#Открытие файла картинки
			if(!($image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name'])))){
				return Ajax::_responseError('Ошибка','Ошибка открытия изображения из загруженного файла');
			}
			@imagealphablending($image, false);
			@imagesavealpha($image, true);

			if(!empty($category['pic_small']) && file_exists(DIR_ROOT.$category['pic_small'])){
				if(!unlink(DIR_ROOT.$category['pic_small'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения каталога');
			}
			#Сохранение файла
			switch($ext){
				case 'jpg':
					if(!imagejpeg($image, $filename, 70)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'gif':
					if(!imagegif($image, $filename)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'png':
					if(!imagepng($image, $filename, 7)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
			}
			imagedestroy($image);

		}catch (Exception $e){
			return Ajax::_responseError('Ошибка','Ошибка обработки файла изображения');
		}

		$db->update('UPDATE `categories` SET `pic_small`="'.addslashes($link).'" WHERE `category_id`='.$category_id);
		Ajax::_responseSuccess('Загрузка изображения каталога','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'category_image_link' => $link
		));
	break; #Загрузка изображения каталога





	/*******************************************************************
	 * Сделать товары видимыми / скрытыми
	 ******************************************************************/
	case 'products.status':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$products = $request->getArray('products',array());
		$status = $request->getBoolAsInt('status',0);
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');

		$db->transaction();

		if($db->update('UPDATE `products` SET `enabled`='.$status.' WHERE `category_id`='.$category_id.' AND `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка изменения статуса товаров');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Сделать товары '.($status > 0 ? 'видимыми':'скрытыми'),
			'data'		=> array(
				'enabled'	=> $status,
				'products'	=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();


		$result = get_category_info($category_id, array(
			'products'=> true,
			'navigator'	=> $request->getBool('navigator',false),
			'page_no'	=> max(1,$request->getId('page_no',1)),
			'per_page'	=> min(1000,max(10,$request->getId('per_page',100)))
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);
		Ajax::_responseSuccess('Изменение видимости товаров','Выполнено успешно','hint');
		return Ajax::_setData($result);
	break;#Сделать товары видимыми / скрытыми



	/*******************************************************************
	 * Сделать товары видимыми / скрытыми для Yandex.market
	 ******************************************************************/
	case 'products.yml':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$products = $request->getArray('products',array());
		$yml = $request->getBoolAsInt('yml',0);
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');

		$db->transaction();

		if($db->update('UPDATE `products` SET `yml_enabled`='.$yml.' WHERE `category_id`='.$category_id.' AND `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка изменения статуса товаров для Yandex.market');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Сделать товары '.($yml > 0 ? 'доступными':'не доступными').' для Yandex.market',
			'data'		=> array(
				'yml'	=> $yml,
				'products'	=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();


		$result = get_category_info($category_id, array(
			'products'=> true,
			'navigator'	=> $request->getBool('navigator',false),
			'page_no'	=> max(1,$request->getId('page_no',1)),
			'per_page'	=> min(1000,max(10,$request->getId('per_page',100)))
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);
		Ajax::_responseSuccess('Изменение видимости товаров для Yandex.market','Выполнено успешно','hint');
		return Ajax::_setData($result);
	break;#Сделать товары видимыми / скрытыми для Yandex.market



	/*******************************************************************
	 * Переместить товары в другой каталог
	 ******************************************************************/
	case 'products.move':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$category_id = $request->getId('category_id',0);
		$move_to = $request->getId('move_to',0);
		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		if(empty($move_to)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога в который следует перенести товары');
		if($category_id == $move_to) return Ajax::_responseError('Ошибка', 'Исходный каталог и каталог назначения совпадают');
		$products = $request->getArray('products',array());
		$status = $request->getBoolAsInt('status',0);
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');
		$current = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($current)) return Ajax::_responseError('Ошибка', 'Каталог не найден');
		$target = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$move_to.' LIMIT 1');
		if(empty($target)) return Ajax::_responseError('Ошибка', 'Каталог в который следует перенести товары не найден');

		$db->transaction();

		if($db->update('UPDATE `products` SET `category_id`='.$move_to.' WHERE `category_id`='.$category_id.' AND `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка переноса товаров');
		}
		if($db->update('UPDATE `categories` as C SET C.`records`=(SELECT count(*) FROM `products` as P WHERE P.`category_id`=C.`category_id`) WHERE C.`category_id` IN('.$category_id.','.$move_to.')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка переноса товаров');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Перенос товаров в другой каталог',
			'data'		=> array(
				'from_category_id'	=> $category_id,
				'to_category_id'	=> $move_to,
				'products'	=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$result = get_category_info($category_id, array(
			'products'=> true,
			'categories'=> true,
			'navigator'	=> $request->getBool('navigator',false),
			'page_no'	=> max(1,$request->getId('page_no',1)),
			'per_page'	=> min(1000,max(10,$request->getId('per_page',100)))
		));
		if(!is_array($result)) return Ajax::_responseError('Ошибка', $result);
		$result['selected_category_id']	= $category_id;
		Ajax::_responseSuccess('Перенос товаров','Выполнено успешно','hint');
		return Ajax::_setData($result);
	break; #Переместить товары в другой каталог



	/*******************************************************************
	 * Информация о товаре
	 ******************************************************************/
	case 'product.info':
		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT P.*,IFNULL(C.`name`,"-[Not found]-") as `category_name` FROM `products` as P LEFT JOIN `categories` as C ON C.`category_id`=P.`category_id` WHERE P.`product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		$product['seo_name'] = rus2translit($product['name']);
		$info = $db->selectRecord('SELECT * FROM `product_info` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(!empty($info)) $product= array_merge($product,$info);
		$source = $db->selectRecord('SELECT * FROM `source_items` WHERE `catalog_item_id`='.$product_id.' LIMIT 1');
		if(!empty($source)){
			$sources = Config::getOption('sources','sources', null);
			if(!empty($sources) && !empty($sources[$source['source_id']])){
				$source['source_name'] = strtoupper($sources[$source['source_id']]);
			}else{
				$source['source_name'] = '-[Неизвестный источник ID:'.$source['source_id'].']-';
			}
		}

		$bridge_info = ($product['bridge_id']>0 ? Shop::_getBridgeInfo($product['bridge_id'], false) : null);
		if(!empty($bridge_info)){
			$product['bridge_price'] = $bridge_info['price'];
			$product['bridge_count'] = $bridge_info['count'];
			$product['bridge_info'] = $bridge_info;
		}else{
			$product['bridge_price'] = 0;
			$product['bridge_count'] = 0;
		}

		return Ajax::_setData(array(
			'product_info'			=> $product,
			'product_warehouses'	=> $db->select('SELECT '.$product_id.' as `product_id`,WH.`warehouse_id` as `warehouse_id`,WH.`name` as `name`,IFNULL(PWH.`count`,0) as `count` FROM `warehouses` as WH LEFT JOIN `product_warehouse` as PWH ON PWH.`warehouse_id`=WH.`warehouse_id` AND PWH.`product_id`='.$product_id),
			'product_images'		=> $db->select('SELECT * FROM `product_images` WHERE `product_id`='.$product_id),
			'product_source'		=> $source,
			'product_properties' 	=> Shop::_getProductProperties($product_id)
		));
	break;



	/*******************************************************************
	 * Информация об остатках товара на складах
	 ******************************************************************/
	case 'product.warehouse.info':
		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');

		if($product['bridge_id']>0){
			$ids = $db->select('SELECT * FROM `products` WHERE `bridge_id`='.$product['bridge_id']);
		}else{
			$ids = array($product);
		}

		$warehouses = $db->selectAsKV('warehouse_id','name','SELECT * FROM `warehouses`');

		$result = array();

		foreach($ids as $p){
			$pwh = $db->selectAsKV('warehouse_id','count','SELECT * FROM `product_warehouse` WHERE `product_id`='.$p['product_id']);
			$row = array(
				'product_id'=> $p['product_id'],
				'name'		=> $p['name'],
				'article'	=> $p['article'],
				'source_id'	=> intval($p['source_id']),
			);
			foreach($warehouses as $whid=>$whname){
				$row[$whid] = (empty($pwh[$whid]) ? 0 : intval($pwh[$whid]));
			}
			$result[] = $row;
		}

		$html = '<table class="pwhTable" width="100%" cellspacing="0" cellpadding="0"><thead><tr>';
		$html.= '<th width="auto">Товар</th>';
		foreach($warehouses as $whid=>$whname){
			$html.= '<th align="center" width="70px">'.$whname.'</th>';
		}
		$html.= '</tr></thead><tbody>';
		foreach($result as $row){
			switch($row['source_id']){
				case 1: $s = 'TEKO'; break;
				case 2: $s = 'VTT'; break;
				case 3: $s = 'CITILINK'; break;
				default:$s = 'DTBOX';
			}
			$html.= '<tr'.($product_id == $row['product_id'] ? ' class="selected"' :'').'><td class="name">'.$s.': '.$row['article'].'<br><a href="/admin/products/info?product_id='.$row['product_id'].'" target="_blank">'.$row['name'].'</a></td>';
			foreach($warehouses as $whid=>$whname){
				$html.= '<td>'.(empty($row[$whid]) ? '-': $row[$whid]).'</td>';
			}
			$html.='</tr>';
		}
		$html.= '</tbody></table>';


		return Ajax::_setData(array(
			'pwh_html'	=> $html,
			'pwh_data'	=> $result
		));
	break;




	/*******************************************************************
	 * Изменение остатков товара на складах
	 ******************************************************************/
	case 'product.warehouse.edit':

		if(!$user->checkAccess('can_product_wh_change')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$warehouse_id = $request->getId('warehouse_id',0);
		if(empty($warehouse_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор склада');
		$count = abs($request->getFloat('count', 0));

		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');

		$warehouse = $db->selectRecord('SELECT * FROM `warehouses` WHERE `warehouse_id`='.$warehouse_id.' LIMIT 1');
		if(empty($warehouse)) return Ajax::_responseError('Ошибка', 'Склад с ID '.$warehouse_id.' не найден');

		if($db->result('SELECT IFNULL(count(*),0) FROM `product_warehouse` WHERE `product_id`='.$product_id.' AND `warehouse_id`='.$warehouse_id.' LIMIT 1') > 0){
			$db->update('UPDATE `product_warehouse` SET `count`='.$count.' WHERE `product_id`='.$product_id.' AND `warehouse_id`='.$warehouse_id);
		}else{
			$db->addRecord('product_warehouse',array(
				'product_id'	=> $product_id,
				'warehouse_id'	=> $warehouse_id,
				'count'			=> $count
			));
		}

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменение остатков товара на складе',
			'data'		=> array(
				'product_id'	=> $product_id,
				'warehouse_id'	=> $warehouse_id,
				'count'			=> $count
			)
		));

		Ajax::_responseSuccess('Обновление остатков на складах','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_warehouses' => $db->select('SELECT '.$product_id.' as `product_id`,WH.`warehouse_id` as `warehouse_id`,WH.`name` as `name`,IFNULL(PWH.`count`,0) as `count` FROM `warehouses` as WH LEFT JOIN `product_warehouse` as PWH ON PWH.`warehouse_id`=WH.`warehouse_id` AND PWH.`product_id`='.$product_id)
		));

	break; #Изменение остатков товара на складах



	/*******************************************************************
	 * Добавление нового товара
	 ******************************************************************/
	case 'product.add':

		if(!$user->checkAccess('can_product_add')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$enabled		= $request->getBoolAsInt('enabled',0);
		$name			= $request->getStr('name','');
		$article		= $request->getStr('article','');
		$vendor			= $request->getStr('vendor','');
		$yml			= $request->getBoolAsInt('yml',0);
		$stockgallery	= $request->getBoolAsInt('stockgallery',0);
		$measure		= $request->getStr('measure','');
		$weight			= $request->getFloat('weight','');
		$size_x			= $request->getFloat('size_x','');
		$size_y			= $request->getFloat('size_y','');
		$size_z			= $request->getFloat('size_z','');
		$currency_code	= $request->getStr('currency','');
		$base_price		= abs($request->getFloat('base_price',0));
		$category_id	= $request->getId('category_id',0);
		$description	= $request->getStr('description','');
		$part_nums		= $request->getStr('part_nums','');
		$admin_info		= $request->getStr('admin_info','');

		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано название товара');
		if(empty($article)) return Ajax::_responseError('Ошибка', 'Не задан артикульный номер товара');
		if(empty($vendor)) return Ajax::_responseError('Ошибка', 'Не задан бренд товара');

		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$category = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($category)) return Ajax::_responseError('Ошибка', 'Заданный каталог ID:'.$category_id.' не найден');

		if(empty($currency_code)) return Ajax::_responseError('Ошибка', 'Не задана валюта товара');
		$currency = $db->selectRecord('SELECT * FROM `currencies` WHERE `code` LIKE "'.addslashes($currency_code).'" LIMIT 1');
		if(empty($currency)) return Ajax::_responseError('Ошибка', 'Заданная валюта не найдена');

		$db->transaction();

		$add=array(
			'category_id'		=> $category_id,	#[uint] Идентификатор категории
			'source_id'			=> 0,				#[uint] Идентификатор источника информации
			'source_item_id'	=> 0,				#[uint] Идентификатор товара в таблице источников информации
			'bridge_id'			=> 0,				#[uint] Идентификатор моста, связывающего два и более товаров
			'enabled'			=> $enabled,		#[uint, 1] Признак, указывающий что продукт активен и отображается посетителям
			'yml_enabled'		=> $yml,			#[uint, 1] Признак, указывающий что продукт должен выгружаться для Яндекс-маркета и т.п. рекламных площадок
			'name'				=> $name,			#[char, 255] Наименование товара
			'seo'				=> rus2translit($name),
			'currency'			=> $currency_code,	#[char, 3] Валюта
			'base_price_real'	=> $base_price,		#[double] Цена товара в указанной валюте, базовая цена без накруток магазина и скидок клиентам
			'base_price_factor'	=> 1,				#
			'base_price'		=> $base_price,		#[double] Цена товара в указанной валюте, базовая цена без накруток магазина и скидок клиентам
			'article'			=> $article,		#[char, 128, unique] Артикульный номер товара
			'vendor'			=> $vendor,		#[char, 128] Производитель товара
			'weight'			=> $weight,		#[double] Вес товара, в кг
			'size_x'			=> $size_x,		#[double] Габариты товара в см, ширина
			'size_y'			=> $size_y,		#[double] Габариты товара в см, высота
			'size_z'			=> $size_z,		#[double] Габариты товара в см, глубина
			'measure'			=> $measure,		#[char, 32] Единица измерения товара: шт., кг., уп. и т.д.
			'pic_small'			=> '',				#[char, 255] Путь к иконке товара
			'pic_big'			=> '',				#[char, 255] Путь к большой картинке товара
			'stockgallery'		=> $stockgallery	#[uint, 1] Признак, указывающий что продукт должен отображаться в карусели на главной странице
		);

		$product_id = $db->addRecord('products',$add);
		if(empty($product_id)){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка добавления товара');
		}

		if($db->addRecord('product_info',array(
			'product_id'	=> $product_id,
			'description'	=> $description,
			'part_nums'		=> $part_nums,
			'admin_info'	=> $admin_info
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка добавления товара');
		}

		if($db->update('UPDATE `categories` as C SET C.`records`=(SELECT IFNULL(count(*),0) FROM `products` as P WHERE P.`category_id`=C.`category_id`) WHERE C.`category_id`='.$category_id)===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка добавления товара');
		}

		$add['product_id'] = $product_id;

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление нового товара',
			'data'		=> $add
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		Ajax::_responseSuccess('Добавление товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_id' => $product_id
		));
	break; #Добавление нового товара



	/*******************************************************************
	 * Изменение характеристик товара
	 ******************************************************************/
	case 'product.edit.info':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');

		$enabled		= $request->getBoolAsInt('enabled',0);
		$name			= $request->getStr('name','');
		$seo			= $request->getStr('seo','');
		$article		= $request->getStr('article','');
		$vendor			= $request->getStr('vendor','');
		$yml			= $request->getBoolAsInt('yml',0);
		$stockgallery	= $request->getBoolAsInt('stockgallery',0);

		$is_offer		= $request->getBoolAsInt('offer',0);
		$offer_discount	= $request->getFloat('offer_discount',0);

		$measure		= $request->getStr('measure','');
		$weight			= $request->getFloat('weight','');
		$size_x			= $request->getFloat('size_x','');
		$size_y			= $request->getFloat('size_y','');
		$size_z			= $request->getFloat('size_z','');
		$currency_code	= $request->getStr('currency','');
		//$base_price		= $request->getFloat('base_price','');
		$base_price_real	= $request->getFloat('base_price_real','');
		$base_price_factor	= $request->getFloat('base_price_factor','');

		$base_price		= $base_price_real * $base_price_factor;
		
		$pic_big		= $request->getStr('pic_big','');
		$category_id	= $request->getId('category_id',0);
		$description	= $request->getStr('description','');
		$content		= $request->getStr('content','');
		$compatible		= $request->getStr('compatible','');
		$part_nums		= $request->getStr('part_nums','');
		$admin_info		= $request->getStr('admin_info','');
		$need_update_price		= $request->getBoolAsInt('need_update_price',0);
		$need_update_warehouse	= $request->getBoolAsInt('need_update_warehouse',0);
		$image_checked	= $request->getBoolAsInt('image_checked',0);


		if(empty($name)) return Ajax::_responseError('Ошибка', 'Не задано название товара');
		if(empty($article)) return Ajax::_responseError('Ошибка', 'Не задан артикульный номер товара');
		if(empty($vendor)) return Ajax::_responseError('Ошибка', 'Не задан бренд товара');

		if(empty($category_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога');
		$category = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$category_id.' LIMIT 1');
		if(empty($category)) return Ajax::_responseError('Ошибка', 'Заданный каталог не найден');

		if(empty($currency_code)) return Ajax::_responseError('Ошибка', 'Не задана валюта товара');
		$currency = $db->selectRecord('SELECT * FROM `currencies` WHERE `code` LIKE "'.addslashes($currency_code).'" LIMIT 1');
		if(empty($currency)) return Ajax::_responseError('Ошибка', 'Заданная валюта не найдена');

		$db->transaction();

		$db->prepare('UPDATE `products` SET 
			`enabled`=?,
			`name`=?,
			`seo`=?,
			`article`=?,
			`vendor`=?,
			`yml_enabled`=?,
			`stockgallery`=?,
			`offer`=?,
			`offer_discount`=?,
			`measure`=?,
			`weight`=?,
			`size_x`=?,
			`size_y`=?,
			`size_z`=?,
			`currency`=?,
			`base_price`=?,
			`base_price_real`=?,
			`base_price_factor`=?,
			`category_id`=?,
			`update_time`=?
			WHERE `product_id`=?'
		);
		$db->bind($enabled);
		$db->bind($name);
		$db->bind($seo);
		$db->bind($article);
		$db->bind($vendor);
		$db->bind($yml);
		$db->bind($stockgallery);
		$db->bind($is_offer);
		$db->bind($offer_discount);
		$db->bind($measure);
		$db->bind($weight);
		$db->bind($size_x);
		$db->bind($size_y);
		$db->bind($size_z);
		$db->bind($currency_code);
		$db->bind($base_price);
		$db->bind($base_price_real);
		$db->bind($base_price_factor);
		$db->bind($category_id);
		$db->bind(DBNOW);
		$db->bind($product_id);
		if($db->update()===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка обновления характеристик товара');
		}

		$db->prepare('UPDATE `product_info` SET 
			`description`=?,
			`part_nums`=?,
			`content`=?,
			`compatible`=?,
			`admin_info`=?
			WHERE `product_id`=?'
		);
		$db->bind($description);
		$db->bind($part_nums);
		$db->bind($content);
		$db->bind($compatible);
		$db->bind($admin_info);
		$db->bind($product_id);
		if($db->update()===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка обновления характеристик товара');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Редактирование характеристик товара',
			'data'		=> array(
				'product_id'	=> $product_id,
				'prev'		=> $product,
				'new'		=> array(
					'enabled' => $enabled,
					'name' => $name,
					'seo' => $seo,
					'article' => $article,
					'vendor' => $vendor,
					'yml' => $yml,
					'stockgallery' => $stockgallery,
					'measure' => $measure,
					'weight' => $weight,
					'size_x' => $size_x,
					'size_y' => $size_y,
					'size_z' => $size_z,
					'currency_code' => $currency_code,
					'base_price' => $base_price,
					'base_price_real' => $base_price_real,
					'base_price_factor' => $base_price_factor,
					'pic_big' => $pic_big,
					'category_id' => $category_id,
					'description' => $description,
					'part_nums' => $part_nums,
					'admin_info' => $admin_info,
					'need_update_price' => $need_update_price,
					'need_update_warehouse' => $need_update_warehouse,
					'image_checked'			=> $image_checked
				)
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		$source = $db->selectRecord('SELECT * FROM `source_items` WHERE `catalog_item_id`='.$product_id.' LIMIT 1');
		if(!empty($source) && ($source['image_checked']!=$image_checked||$source['need_update_price']!=$need_update_price||$source['need_update_warehouse']!=$need_update_warehouse)){
			$db->update('UPDATE `source_items` SET `image_checked`='.$image_checked.', `need_update_price`='.$need_update_price.',`need_update_warehouse`='.$need_update_warehouse.' WHERE `item_id`='.$source['item_id']);
			$source['need_update_price'] = $need_update_price;
			$source['need_update_warehouse'] = $need_update_warehouse;
			$source['image_checked'] = $image_checked;
		}
		if(!empty($source)){
			$sources = Config::getOption('sources','sources', null);
			if(!empty($sources) && !empty($sources[$source['source_id']])){
				$source['source_name'] = strtoupper($sources[$source['source_id']]);
			}else{
				$source['source_name'] = '-[Неизвестный источник ID:'.$source['source_id'].']-';
			}
		}

		$result = array(
			'product_info'	=> $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1'),
			'updated_product_id' => $product_id,
			'product_source'		=> $source
		);

		if($product['category_id']!=$category_id){
			$db->update('UPDATE `categories` as C SET C.`records`=(SELECT count(*) FROM `products` as P WHERE P.`category_id`=C.`category_id`) WHERE C.`category_id` IN('.$category_id.','.$product['category_id'].')');
		}

		Ajax::_responseSuccess('Обновление характеристик товара','Выполнено успешно','hint');
		return Ajax::_setData($result);
	break;



	/*******************************************************************
	 * Удаление изображения товара
	 ******************************************************************/
	case 'product.image.delete':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		if(!empty($product['pic_big']) && file_exists(DIR_ROOT.$product['pic_big'])){
			if(!unlink(DIR_ROOT.$product['pic_big'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения товара');
		}
		if(!empty($product['pic_small']) && file_exists(DIR_ROOT.$product['pic_small'])){
			if(!unlink(DIR_ROOT.$product['pic_small'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения товара');
		}
		$db->update('UPDATE `products` SET `pic_big`="",pic_small="" WHERE `product_id`='.$product_id);
		Ajax::_responseSuccess('Удаление изображения товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_image_link' => ''
		));
	break; #Удаление изображения товара



	/*******************************************************************
	 * Загрузка изображения товара
	 ******************************************************************/
	case 'product.image.upload':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		header('Content-Type: text/html; charset=utf-8', true);
		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');

		#Файл не задан
		if(!$_FILES['image']['size']) return Ajax::_responseError('Ошибка выполнения','Не задан файл изображения товара');

		#Размер файла
		if($_FILES['image']['size'] > 2097152) return Ajax::_responseError('Ошибка выполнения','Слишком большой размер файла');

		#Ошибка загрузки файла
		if($_FILES['image']['error']) return Ajax::_responseError('Ошибка выполнения','Ошибка загрузки файла: '.$_FILES['image']['error']);

		$ext = 'jpg';
		switch($_FILES['image']['type']){
			case 'image/png': $ext = 'png'; break;
			case 'image/gif': $ext = 'gif'; break;
			default: $ext = 'jpg';
		}

		$link = '/client/images/products/p_'.$product_id.'_time_'.time().'.'.$ext;
		$filename = DIR_ROOT.$link;

		try{
			#Открытие файла картинки
			if(!($image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name'])))){
				return Ajax::_responseError('Ошибка','Ошибка открытия изображения из загруженного файла');
			}
			@imagealphablending($image, false);
			@imagesavealpha($image, true);

			if(!empty($product['pic_big']) && file_exists(DIR_ROOT.$product['pic_big'])){
				if(!unlink(DIR_ROOT.$product['pic_big'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения товара');
			}
			if(!empty($product['pic_small']) && file_exists(DIR_ROOT.$product['pic_small'])){
				if(!unlink(DIR_ROOT.$product['pic_small'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления имеющегося файла изображения товара');
			}

			#Сохранение файла
			switch($ext){
				case 'jpg':
					if(!imagejpeg($image, $filename, 70)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'gif':
					if(!imagegif($image, $filename)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'png':
					if(!imagepng($image, $filename, 7)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
			}

			imagedestroy($image);

		}catch (Exception $e){
			return Ajax::_responseError('Ошибка','Ошибка обработки файла изображения');
		}

		$db->update('UPDATE `products` SET `pic_big`="'.addslashes($link).'",pic_small="'.addslashes($link).'" WHERE `product_id`='.$product_id);
		Ajax::_responseSuccess('Загрузка изображения товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_image_link' => $link
		));
	break; #Загрузка изображения товара



	/*******************************************************************
	 * Поиск товаров по каталогу
	 ******************************************************************/
	case 'products.search':

		$term = trim($request->getStr('term',''));
		$limit = $request->getId('limit',100);
		$enabled = $request->getEnum('enabled',array('0','1','2','all'), '1');
		if($enabled == 'all') $enabled = 2;
		$enabled = intval($enabled);
		$search_product_id = $request->getId('product_id',0);
		$category_id = $request->getId('category_id',0);
		$subcategories = intval($request->getEnum('subcategories',array('0','1','2','3','4','5'), 0));
		$search_article = $request->getStr('article', null);
		$search_vendor = $request->getStr('vendor', null);
		$search_description = $request->getStr('description', null);
		$search_compatible = $request->getStr('compatible', null);
		$search_part_nums = $request->getStr('part_nums', null);
		$search_currency = $request->getStr('currency', 'all');
		$search_price_min = $request->getFLoat('price_min', 0);
		$search_price_max = $request->getFLoat('price_max', 0);
		$search_source = $request->getStr('source', 'all');
		$search_yml = $request->getEnum('yml', array('all','yml','stockgallery'), 'all');
		$search_date = $request->getDate('date', null);
		$search_datetype = $request->getStr('datetype', 'all');

		if(empty($search_product_id)&&empty($term)&&empty($search_article)&&empty($search_vendor)&&empty($search_description)&&empty($search_compatible)&&empty($search_part_nums)
		&&empty($search_price_min)&&empty($search_price_max)&&($search_datetype=='all')&&($search_yml=='all')) return Ajax::_responseError('Ошибка','Не задан поисковый запрос');

		$shop = Shop::getInstance();

		$childs = null;
		$enabled_cats = array();
		$all_cats = array();
		$categories = $shop->categoryList(0,' / ', ($enabled==1?true:false));
		foreach($categories as $c){
			$all_cats[$c[0]] = $c[1];
			if($c[3]) $enabled_cats[] = $c[0];
			if($subcategories>0 && $c[0] == $category_id) $childs = $c[2];
		}

		if($enabled == 2) $filter = 'P.`enabled`>=0';
		else
		if($enabled == 1) $filter = 'P.`enabled`>0';
		else $filter = 'P.`enabled`=0';

		$sql_name = '';
		$sql_description = '';
		$sql_keywords = '';
		$sql_article = '';
		$sql_partnums = '';
		$sql_id = '';
		$term = str_replace(array('\\','+','*','?','[',']','^','(',')','{','}','=','!','<','>','|',':','-','\'','"'), ' ',$term);
		$term = trim(preg_replace('/\s\s+/', ' ', $term));
		if(!empty($term)){
			$_WORDS = explode(" ", $term);
			foreach ($_WORDS as $w){
				$w=trim($w);if(empty($w)) continue;
				$sql_id =(is_numeric($w)&&count($_WORDS)==1?"(P.`product_id` = '$w')":'');
				$sql_article.=(empty($sql_article)?"(":" and ") . "P.`article` REGEXP '$w'";
				$sql_name.=(empty($sql_name)?"(":" and ") . "P.`name` REGEXP '$w'";
				$sql_description.=(empty($sql_description)?"(":" and ") . "PI.`description` REGEXP '$w'";
				$sql_partnums.=(empty($sql_partnums)?"(":" and ") . "PI.`part_nums` REGEXP '$w'";
			}
			$sql_article.=(empty($sql_article)?"":")");
			$sql_name.=(empty($sql_name)?"":")");
			$sql_description.=(empty($sql_description)?"":")");
			$sql_partnums.=(empty($sql_partnums)?"":")");
			$s = '';
			if(!empty($sql_id)) $s.= (empty($s)?"(":" or ") . $sql_id;
			if(!empty($sql_article)) $s.= (empty($s)?"(":" or ") . $sql_article;
			if(!empty($sql_name)) $s.= (empty($s)?"(":" or ") . $sql_name;
			if(!empty($sql_description)) $s.= (empty($s)?"(":" or ") . $sql_description;
			if(!empty($sql_partnums)) $s.= (empty($s)?"(":" or ") . $sql_partnums;
			if(!empty($s)) $filter .= ' AND '. $s.')';
		}

		if($category_id>0){
			switch($subcategories){
				case 0: $filter.=' AND P.`category_id`='.$category_id; break;
				case 1: $filter.=' AND (P.`category_id`='.$category_id.(!empty($childs) ? ' OR P.`category_id` IN ('.implode(',',$childs).')' : '').')'; break;
				case 2: if(!empty($childs)) $filter.=' AND P.`category_id` IN ('.implode(',',$childs).')'; break;
				case 3: $filter.=' AND P.`category_id`<>'.$category_id; break;
				case 4: $filter.=' AND (P.`category_id`<>'.$category_id.(!empty($childs) ? ' AND P.`category_id` NOT IN ('.implode(',',$childs).')' : '').')'; break;
				case 5: if(!empty($childs)) $filter.=' AND P.`category_id` NOT IN ('.implode(',',$childs).')'; break;
			}
		}

		if(!empty($search_product_id)) $filter.=' AND P.`product_id`='.$search_product_id;
		if(!empty($search_article)) $filter.=' AND P.`article` LIKE "%'.addslashes($search_article).'%"';
		if(!empty($search_vendor)) $filter.=' AND P.`vendor` LIKE "%'.addslashes($search_vendor).'%"';
		if(!empty($search_description)) $filter.=' AND PI.`description` LIKE "%'.addslashes($search_description).'%"';
		if(!empty($search_compatible)) $filter.=' AND PI.`compatible` LIKE "%'.addslashes($search_compatible).'%"';
		if(!empty($search_part_nums)) $filter.=' AND PI.`part_nums` LIKE "%'.addslashes($search_part_nums).'%"';
		if(!empty($search_currency)&&$search_currency!='all') $filter.=' AND P.`currency` LIKE "'.addslashes($search_currency).'"';
		if($search_price_min>0) $filter.=' AND P.`base_price` >= '.$search_price_min;
		if($search_price_max>0) $filter.=' AND P.`base_price` <= '.$search_price_max;
		if($search_source!='all') $filter.=' AND P.`source_id`='.intval($search_source);
		if($search_yml=='yml') $filter.=' AND P.`yml_enabled`=1';
		if($search_yml=='stockgallery') $filter.=' AND P.`stockgallery`=1';

		if(!empty($search_date)){
			$search_date = date2sql($search_date);
			switch($search_datetype){
				case 'addbefore': $filter.=' AND P.`create_time` <= "'.$search_date.'"'; break;
				case 'addafter': $filter.=' AND P.`create_time` >= "'.$search_date.'"'; break;
				case 'updbefore': $filter.=' AND P.`update_time` <= "'.$search_date.'"'; break;
				case 'updafter': $filter.=' AND P.`update_time` >= "'.$search_date.'"'; break;
			}
		}


		$sql = 
		'SELECT 
			P.`product_id` as `product_id`,
			P.`bridge_id` as `bridge_id`,
			P.`category_id` as `category_id`,
			P.`source_id` as `source_id`,
			P.`vendor` as `vendor`,
			P.`enabled` as `enabled`,
			P.`name` as `name`,
			P.`pic_big` as `pic_big`,
			P.`article` as `article`,
			P.`currency` as `currency`,
			P.`base_price` as `base_price`,
			P.`create_time` as `create_time`,
			P.`update_time` as `update_time`,
			P.`yml_enabled` as `yml_enabled`,
			CUR.`exchange` as `exchange`,
			PI.`part_nums` as `part_nums`,
			PI.`description` as `description`,
			(SELECT IFNULL(sum(PW.`count`),0) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=P.`product_id`) as `count`
		FROM `products` as P 
			INNER JOIN `product_info` as PI ON PI.`product_id` = P.`product_id`
			INNER JOIN `categories` as C ON C.`category_id` = P.`category_id`
			INNER JOIN `currencies` as CUR ON CUR.`code` = P.`currency`
		WHERE '.$filter.' '.($enabled==1?' AND C.`enabled` > 0':'').' ORDER BY P.`name` '.($limit > 0 ? 'LIMIT '.$limit : '');

		$data = $db->select($sql);

		$dis='';
		$shop = Shop::getInstance();
		$products=array();
		if(!empty($data))
		foreach($data as $row){
			$is_hidden = !in_array($row['category_id'], $enabled_cats);
			if($enabled==1 && $is_hidden) continue;
			$row['base_price_rub'] = $shop->currencyExchange($row['currency'], $row['base_price']);
			$row['price'] = $shop->getPrice($row['currency'], $row['base_price'], false);
			$row['client_enabled'] = ($is_hidden || $row['enabled']==0 ? 0 : 1);
			$row['category_name'] = $all_cats[$row['category_id']];

			$bridge_info = ($row['bridge_id']>0 ? $shop->getBridgeInfo($row['bridge_id'], false) : null);
			if(!empty($bridge_info)){
				$row['bridge_price'] = $bridge_info['price'];
				$row['bridge_count'] = $bridge_info['count'];
				$row['bridge_base_price'] = $bridge_info['base_price'];
			}else{
				$row['bridge_price'] = $row['price'];
				$row['bridge_count'] = $row['count'];
				$row['bridge_base_price'] = $row['base_price_rub'];
			}

			$products[] = $row;
		}
		return Ajax::_setData(array(
			'products_search'	=> $products,
			'sql'	=> $sql
		));

	break; #Поиск товаров по каталогу



	/*******************************************************************
	 * Сделать товары в результатах поиска видимыми / скрытыми
	 ******************************************************************/
	case 'products.search.status':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$products = $request->getArray('products',array());
		$status = $request->getBoolAsInt('status',0);
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');

		$db->transaction();

		if($db->update('UPDATE `products` SET `enabled`='.$status.' WHERE `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка изменения статуса товаров');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Сделать товары '.($status > 0 ? 'видимыми':'скрытыми'),
			'data'		=> array(
				'enabled'	=> $status,
				'products'	=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();


		Ajax::_responseSuccess('Изменение видимости товаров','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'search_refresh'	=> true
		));
	break;# Сделать товары в результатах поиска видимыми / скрытыми



	/*******************************************************************
	 * Сделать товары видимыми / скрытыми для Yandex.market
	 ******************************************************************/
	case 'products.search.yml':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$products = $request->getArray('products',array());
		$yml = $request->getBoolAsInt('yml',0);
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');

		$db->transaction();

		if($db->update('UPDATE `products` SET `yml_enabled`='.$yml.' WHERE `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка изменения статуса товаров для Yandex.market');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Сделать товары '.($yml > 0 ? 'доступными':'не доступными').' для Yandex.market',
			'data'		=> array(
				'yml'	=> $yml,
				'products'	=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();


		Ajax::_responseSuccess('Изменение видимости товаров для Yandex.market','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'search_refresh'	=> true
		));
	break;#Сделать товары видимыми / скрытыми для Yandex.market



	/*******************************************************************
	 * Переместить товары в результатах поиска в другой каталог
	 ******************************************************************/
	case 'products.search.move':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$move_to = $request->getId('move_to',0);
		if(empty($move_to)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор каталога в который следует перенести товары');
		$products = $request->getArray('products',array());
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');
		$target = $db->selectRecord('SELECT * FROM `categories` WHERE `category_id`='.$move_to.' LIMIT 1');
		if(empty($target)) return Ajax::_responseError('Ошибка', 'Каталог в который следует перенести товары не найден');

		$categories_upd = $db->selectFromField('category_id','SELECT DISTINCT `category_id` FROM `products` WHERE `product_id` IN ('.implode(',',$products).')');

		if(!in_array($move_to,$categories_upd)) $categories_upd[]=$move_to;

		$db->transaction();

		if($db->update('UPDATE `products` SET `category_id`='.$move_to.' WHERE `product_id` IN ('.implode(',',$products).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка переноса товаров товаров');
		}
		if($db->update('UPDATE `categories` as C SET C.`records`=(SELECT count(*) FROM `products` as P WHERE P.`category_id`=C.`category_id`) WHERE C.`category_id` IN('.implode(',',$categories_upd).')')===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка', 'Ошибка переноса товаров товаров');
		}

		if($user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Перенос товаров в другой каталог',
			'data'		=> array(
				'to_category_id'	=> $move_to,
				'products'			=> $products
			)
		))===false){
			$db->rollback();
			return Ajax::_responseError('Ошибка','Ошибка протоколирования');
		}

		$db->commit();

		Ajax::_responseSuccess('Перенос товаров','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'search_refresh'	=> true
		));
	break; #Переместить товары в результатах поиска в другой каталог


	/*******************************************************************
	 * Объединить товары в результатах поиска
	 ******************************************************************/
	case 'products.search.bridge':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$products = $request->getArray('products',array());
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');
		$bridge_id = time();
		if($db->update('UPDATE `products` SET `bridge_id`='.$bridge_id.' WHERE `product_id` IN ('.implode(',',$products).')')===false) return Ajax::_responseError('Ошибка', 'Ошибка объеданения товаров');

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Объединение товаров',
			'data'		=> array(
				'bridge_id'		=> $bridge_id,
				'products'		=> $products
			)
		));

		Ajax::_responseSuccess('Объединение товаров','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'search_refresh'	=> true
		));
	break;# Объединить товары в результатах поиска




	/*******************************************************************
	 * Исключить товар из объединения
	 ******************************************************************/
	case 'products.bridge.exclude':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		if($product['bridge_id'] == 0) return Ajax::_responseSuccess('Исключение товара из объединения','Выполнено успешно','hint');
		$count = $db->result('SELECT IFNULL(count(*),0) FROM `products` WHERE `bridge_id`='.$product['bridge_id']);
		if($count<3){
			$db->update('UPDATE `products` SET `bridge_id`=0 WHERE `bridge_id`='.$product['bridge_id']);
		}else{
			$db->update('UPDATE `products` SET `bridge_id`=0 WHERE `product_id`='.$product['product_id']);
		}

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Исключение товара из объединения',
			'data'		=> array(
				'bridge_id'		=> $product['bridge_id'],
				'product_id'	=> $product_id
			)
		));

		Ajax::_responseSuccess('Исключение товара из объединения','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'bridges'	=> Shop::_getBridgeList()
		));
	break; #Исключить товар из объединения



	/*******************************************************************
	 * Добавить товар к объединению
	 ******************************************************************/
	case 'products.bridge.include':
		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$product_id = $request->getId('product_id',0);
		$bridge_id = $request->getId('bridge_id',0);
		if(empty($bridge_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор объединения');
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		$db->update('UPDATE `products` SET `bridge_id`='.$bridge_id.' WHERE `product_id`='.$product['product_id']);

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Добавление товара к объединению',
			'data'		=> array(
				'bridge_id'		=> $product['bridge_id'],
				'product_id'	=> $product_id
			)
		));

		Ajax::_responseSuccess('Добавление товара к объединению','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'bridges'	=> Shop::_getBridgeList()
		));
	break; #Добавление товара к объединению




	/*******************************************************************
	 * Удалить объединение
	 ******************************************************************/
	case 'products.bridge.delete':
		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$bridge_id = $request->getId('bridge_id',0);
		if(empty($bridge_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор объединения');
		$products = $db->selectFromField('product_id', 'SELECT `product_id` FROM `products` WHERE `bridge_id`='.$bridge_id);
		$db->update('UPDATE `products` SET `bridge_id`='.$bridge_id.' WHERE `bridge_id`='.$bridge_id);
		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление объединения',
			'data'		=> array(
				'bridge_id'		=> $bridge_id,
				'products'		=> $products
			)
		));
		Ajax::_responseSuccess('Удаление объединения','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'bridges'	=> Shop::_getBridgeList()
		));
	break; #Удалить объединение



	/*******************************************************************
	 * Создать объединение
	 ******************************************************************/
	case 'products.bridge.create':
		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$products = $request->getArray('products',array());
		$products = array_map('intval', $products);
		if(empty($products)) return Ajax::_responseError('Ошибка', 'Не выбрано ни одного товара');
		$bridge_id = time();
		$db->update('UPDATE `products` SET `bridge_id`='.$bridge_id.' WHERE `product_id` IN ('.implode(',',$products).')');
		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Объединение товаров',
			'data'		=> array(
				'bridge_id'		=> $bridge_id,
				'products'		=> $products
			)
		));
		Ajax::_responseSuccess('Создание объединения','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'bridges'	=> Shop::_getBridgeList()
		));
	break; #Создать объединение



	/*******************************************************************
	 * Исключить товар из объединения на странице информации о товаре
	 ******************************************************************/
	case 'product.info.bridge.exclude':
		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$product_id = $request->getId('product_id',0);
		$exclude_id = $request->getId('exclude_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		if(empty($exclude_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$exclude = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$exclude_id.' LIMIT 1');
		if(empty($exclude)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		if($exclude['bridge_id'] == 0) return Ajax::_responseSuccess('Исключение товара из объединения','Выполнено успешно','hint');
		$count = $db->result('SELECT IFNULL(count(*),0) FROM `products` WHERE `bridge_id`='.$exclude['bridge_id']);
		if($count<3){
			$db->update('UPDATE `products` SET `bridge_id`=0 WHERE `bridge_id`='.$exclude['bridge_id']);
		}else{
			$db->update('UPDATE `products` SET `bridge_id`=0 WHERE `product_id`='.$exclude['product_id']);
		}
		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Исключение товара из объединения',
			'data'		=> array(
				'bridge_id'		=> $exclude['bridge_id'],
				'product_id'	=> $exclude['product_id']
			)
		));
		Ajax::_responseSuccess('Исключение товара из объединения','Выполнено успешно','hint');
		return Ajax::_setData(array(
		));
	break; #Исключить товар из объединения на странице информации о товаре



	/*******************************************************************
	 * Изменение ID объединения на странице информации о товаре
	 ******************************************************************/
	case 'product.info.bridge.change':
		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');
		$product_id = $request->getId('product_id',0);
		$bridge_id = $request->getId('bridge_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		if($product['bridge_id'] == $bridge_id) return Ajax::_responseSuccess('Изменение ID объединения','Выполнено успешно','hint');
		$count = $db->result('SELECT IFNULL(count(*),0) FROM `products` WHERE `bridge_id`='.$product['bridge_id']);
		if($count<3){
			$db->update('UPDATE `products` SET `bridge_id`=0 WHERE `bridge_id`='.$product['bridge_id']);
		}
		$db->update('UPDATE `products` SET `bridge_id`='.$bridge_id.' WHERE `product_id`='.$product['product_id']);
		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Изменение ID объединения на странице информации о товаре',
			'data'		=> array(
				'product_id'	=> $product_id,
				'prev_bridge_id'=> $product['bridge_id'],
				'new_bridge_id'	=> $bridge_id
			)
		));
		Ajax::_responseSuccess('Изменение ID объединения','Выполнено успешно','hint');
		return Ajax::_setData(array(
		));
	break; #Изменение ID объединения на странице информации о товаре




	/*******************************************************************
	 * Загрузка изображения товара для списка дополнительных изображений
	 ******************************************************************/
	case 'product.imglist.upload':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		header('Content-Type: text/html; charset=utf-8', true);
		$product_id = $request->getId('product_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');

		#Файл не задан
		if(!$_FILES['image']['size']) return Ajax::_responseError('Ошибка выполнения','Не задан файл изображения товара');

		#Размер файла
		if($_FILES['image']['size'] > 2097152) return Ajax::_responseError('Ошибка выполнения','Слишком большой размер файла');

		#Ошибка загрузки файла
		if($_FILES['image']['error']) return Ajax::_responseError('Ошибка выполнения','Ошибка загрузки файла: '.$_FILES['image']['error']);

		$ext = 'jpg';
		switch($_FILES['image']['type']){
			case 'image/png': $ext = 'png'; break;
			case 'image/gif': $ext = 'gif'; break;
			default: $ext = 'jpg';
		}

		$link = '/client/images/products/p_'.$product_id.'_time_'.time().'.'.$ext;
		$filename = DIR_ROOT.$link;

		try{
			#Открытие файла картинки
			if(!($image = imagecreatefromstring(file_get_contents($_FILES['image']['tmp_name'])))){
				return Ajax::_responseError('Ошибка','Ошибка открытия изображения из загруженного файла');
			}
			@imagealphablending($image, false);
			@imagesavealpha($image, true);

			#Сохранение файла
			switch($ext){
				case 'jpg':
					if(!imagejpeg($image, $filename, 70)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'gif':
					if(!imagegif($image, $filename)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
				case 'png':
					if(!imagepng($image, $filename, 7)) return Ajax::_responseError('Ошибка','Ошибка сохранения изображения на сервере');
				break;
			}

			imagedestroy($image);

		}catch (Exception $e){
			return Ajax::_responseError('Ошибка','Ошибка обработки файла изображения');
		}

		$db->addRecord('product_images',array(
			'product_id'	=> $product_id,
			'image_file'	=> $link
		));

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Загрузка дополнительного изображения товара',
			'data'		=> array(
				'product_id'	=> $product_id,
				'image_file'	=> $link
			)
		));

		Ajax::_responseSuccess('Загрузка изображения товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_images'		=> $db->select('SELECT * FROM `product_images` WHERE `product_id`='.$product_id)
		));
	break; #Загрузка изображения товара



	/*******************************************************************
	 * Удаление изображения товара
	 ******************************************************************/
	case 'product.imglist.delete':

		if(!$user->checkAccess('can_product_edit')) return Ajax::_responseError('Ошибка','Недостаточно прав для выполнения действия');

		$product_id = $request->getId('product_id',0);
		$image_id = $request->getId('image_id',0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор товара');
		if(empty($image_id)) return Ajax::_responseError('Ошибка', 'Не задан идентификатор изображения');
		$product = $db->selectRecord('SELECT * FROM `products` WHERE `product_id`='.$product_id.' LIMIT 1');
		if(empty($product)) return Ajax::_responseError('Ошибка', 'Товар с ID '.$product_id.' не найден');
		$image = $db->selectRecord('SELECT * FROM `product_images` WHERE `image_id`='.$image_id.' AND `product_id`='.$product_id.' LIMIT 1');
		if(empty($image)) return Ajax::_responseError('Ошибка', 'Изображение товара ID '.$image_id.' не найдено');
		if(!empty($image['image_file']) && file_exists(DIR_ROOT.$image['image_file'])){
			if(!unlink(DIR_ROOT.$image['image_file'])) return Ajax::_responseError('Ошибка','Недостаточно прав для удаления файла изображения товара');
		}
		$db->delete('DELETE FROM `product_images` WHERE `image_id`='.$image_id.' AND `product_id`='.$product_id);

		$user->actionLog(array(
			'action'	=> $request_action,
			'info'		=> 'Удаление дополнительного изображения товара',
			'data'		=> $image
		));

		Ajax::_responseSuccess('Удаление изображения товара','Выполнено успешно','hint');
		return Ajax::_setData(array(
			'product_images'	=> $db->select('SELECT * FROM `product_images` WHERE `product_id`='.$product_id)
		));
	break; #Удаление изображения товара



	default:
	Ajax::_responseError('/admin/ajax/catalog','Not found: '.Request::_get('action'));
}
?>
