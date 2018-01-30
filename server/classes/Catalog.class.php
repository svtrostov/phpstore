<?php
/*==================================================================================================
Описание: Каталог товаров
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class Catalog{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	public $options = array(
		'db' => null
	);
	private $is_init		= false;	#ПРизнак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных
	private $time_now;
	private $tmppa;

	private $defaultCatalogGroup = array(
		'group_id'			=> null,
		'parent_id'			=> 0,		//ID родительского элемента
		'name'				=> '',		//Наименование раздела
		'description'		=> ''		//Наименование раздела
	);


	private $defaultCatalogItem = null;
	private $defaultCatalogCategory = null;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($options=array()){
		$this->db = Database::getInstance('main');
		$this->is_init = (!empty($this->db) ? true : false);
		$this->defaultCatalogItem = $this->db->getTableDefaults('products');
		$this->defaultCatalogCategory = $this->db->getTableDefaults('categories');
		$this->time_now =  DBNOW;
		$this->tmppa = array();
	}#end function






	/*==============================================================================================
	ФУНКЦИИ: Работа с товарами
	==============================================================================================*/


	/*
	 * Проверяет существование товара источника в базе данных
	 */
	public function itemExists($keys=array()){
		if(empty($keys)||!$this->is_init) return false;

		$item = $this->itemGet($keys);
		return !empty($item);
	}#end function




	/*
	 * Возвращает запись товара или список записей товаров
	 */
	public function itemGet($keys=array(), $single=false, $fields=''){
		if(!$this->is_init) return false;
		$select_fields='';
		if(!empty($fields)&&is_array($fields)){
			$fields = array_intersect(array_keys($this->defaultCatalogItem), $fields);
			foreach($fields as $field){
				$select_fields.=(empty($select_fields)?'':',').'`'.$select_fields.'`';
			}
		}else{
			if($fields=='*' && !empty($single)){
				$select_fields='count(*)';
			}else{
				$fields=strval($fields);
				$select_fields=(array_key_exists($fields,$this->defaultCatalogItem) ? '`'.$fields.'`' : '*');
			}
		}

		$conditions = $this->db->buildSqlConditions($keys);
		$prepare = 'SELECT '.$select_fields.' FROM `products` '.($conditions!=''?' WHERE '.$conditions:'').(!empty($single)?' LIMIT 1': '');
		$this->db->prepare($prepare);

		return (empty($single) ? $this->db->select() : $this->db->selectRecord());
	}#end function




	/*
	 * Обновление записи о товаре
	 */
	public function itemUpdate($keys=array(), $fields=array()){
		if(!$this->is_init) return false;
		if(empty($keys)||empty($fields)) return false;
		return $this->db->updateRecord('products', $keys, $fields, $this->defaultCatalogItem);
	}#end function



	/*
	Накрутка на цену товара
	*/
	public function getItemPrice($price=0){
		$price = floatval($price);
		if($price < 0) return 0;
		if($price > 0 && $price <= 1.35) return round($price * 1.29, 2);
		if($price >= 1.36 && $price <= 40.54) return round($price * 1.04, 2);
		return round($price,2);
	}


	/*
	 * Обновление записи о товаре
	 */
	public function itemUpdatePriceAndWH($item_id=0, $upd_price=true, $upd_wh=true, $upd_second=true){
		if(!$this->is_init) return false;
		if(!$upd_price && !$upd_wh) return false;
		$item_id = intval($item_id);
		if(empty($item_id)) return false;
		$item = $this->db->selectRecord('SELECT * FROM `source_items` WHERE `catalog_item_id`=' . $item_id.' LIMIT 1');
		if(empty($item)) return false;
		
		$self_items = intval($this->db->result('SELECT IFNULL(sum(`items`),0) as `items` FROM `self_warehouse` WHERE `item_id`='.$item_id.' LIMIT 1'));
		
		$data = $this->db->selectRecord('SELECT count(*) as `records`, sum(`warehouse_rostov`) as `count`, max(`price`) as `price` FROM `source_items` WHERE `catalog_item_id`='.$item_id.' OR `catalog_second_item_id`='.$item_id);
		if(empty($data)) return false;
		$upd=array();
		if(!empty($item['price_formula'])) $data['price'] = $this->calculatePriceByFormula($item_id);
		if($upd_price == true){
			$upd['price'] = $this->getItemPrice($data['price']);
			$upd['enabled']		= ($upd['price'] > 0 ? 1 : 0);
			$upd['p_enabled']	= ($upd['price'] > 0 ? 1 : 0);
		}
		if($upd_wh == true){
			$upd['items']		= $data['count']+$self_items;
			if($upd['items'] > 0) $upd['sklad'] = 0;
		}
		if($this->db->updateRecord('products', array('id' => $item_id), $upd, $this->defaultCatalogItem)===false) return false;
		if(!$upd_second) return true;
		$second_id = $this->db->result('SELECT `catalog_second_item_id` FROM `source_items` WHERE `catalog_item_id`='.$item_id.' limit 1');
		if(empty($second_id)) return true;
		return $this->itemUpdatePriceAndWH($second_id, true, true, false);
		return true;
	}#end function


	/*
	 * Вычисление базовой цены товара по формуле
	 */
	public function calculatePriceByFormula($item_id=0){
		$item = $this->db->selectRecord('SELECT * FROM `source_items` WHERE `catalog_item_id`=' . $item_id.' LIMIT 1');
		if(empty($item)||empty($item['price_formula'])) return 0;
		$this->tmppa = array();
		$count = 0;
		$formula = trim(strtolower($item['price_formula']));
		$formula = str_replace(array('{wh}','{main:wh}','{self:wh}'), $item['warehouse_rostov'], $formula);
		$formula = str_replace(array('{main}','{self}'),$item['price'],$formula);
		$formula = preg_replace_callback('/\{([0-9]+)(\:([a-z]*)?)?\}/s', array($this,'calculatePriceByFormulaCallback'), $formula, -1, $count);
		$price = 0;
		try{
			eval('$price = '.$formula.';');
		} catch (Exception $e) {
			return 0;
		}
		return $price;
	}

	/*
	 * Вычисление базовой цены товара по формуле - возвратная функция
	 */
	public function calculatePriceByFormulaCallback($matches){
		$id = intval(trim($matches[1]));
		if($id == 0) return 0;
		$what = (!empty($matches[3]) ? trim($matches[3]) : 'price');
		switch($what){
			case 'wh': $field='warehouse_rostov';break;
			case 'price': 
			default: $field='price';
		}
		if(!empty($this->tmppa[$id]))return $this->tmppa[$id][$field];
		$item = $this->db->selectRecord('SELECT * FROM `source_items` WHERE `catalog_item_id`=' . $id.' LIMIT 1');
		if(empty($item)) $this->tmppa[$id] = array('price'=>0,'warehouse_rostov'=>0);
		else $this->tmppa[$id] = $item;
		return floatval($this->tmppa[$id][$field]);
	}



	/*
	 * Добавление записи о товаре
	 */
	public function itemAdd($fields=array()){
		if(!$this->is_init) return false;
		if(empty($fields)) return false;
		return $this->db->addRecord('products', $fields, $this->defaultCatalogItem);
	}#end function



	/*
	 * Добавление записи о серийном номере товара
	 */
	public function itemAddPartNumber($item_id=0, $part_number=''){
		if(!$this->is_init) return false;
		if(empty($item_id)||empty($part_number)) return false;

		$this->db->prepare('INSERT INTO `catalog_items_part_nums` (`item_id`,`part_number`)VALUES(?,?)');
		$this->db->bind($item_id);
		$this->db->bind($part_number);

		if(($id=$this->db->insert())===false) return false;
		return $id;
	}#end function




	/*
	 * Добавление/обновление записи о совместимых товарах
	 */
	public function itemSetCompatible($item_id=0, $compatible=''){
		if(!$this->is_init) return false;
		if(empty($item_id)||empty($compatible)) return false;

		$this->db->prepare('REPLACE INTO `catalog_items_compatible` (`item_id`,`compatible`)VALUES(?,?)');
		$this->db->bind($item_id);
		$this->db->bind($compatible);

		if($this->db->simple()===false) return false;
		return true;
	}#end function




	/*
	 * Добавление записи о товаре копированием из источника
	 */
	public function itemImportFromSource($source_item_id=0, $in_group=0){

		if(!$this->is_init) return false;
		$source_item_id = intval($source_item_id);
		if(empty($source_item_id)) return false;

		//Получение информации о записи из таблицы источника данных
		$this->db->prepare('SELECT * FROM `source_items` WHERE `item_id`=? LIMIT 1');
		$this->db->bind($source_item_id);
		$srecord = $this->db->selectRecord();
		if(empty($srecord)) return false;

		//ID источника данных
		$source_id = intval($srecord['source_id']);
		$sources_list = Config::getOption('sources','sources',false);
		if(empty($sources_list[$source_id])) return false;
		$source_name = $sources_list[$source_id];
		$source_info = Config::getOption('sources',$source_name,false);
		if(empty($source_info)) return false;
		if(intval($source_info['source_id']) != $source_id) return false;

		$this->db->transaction();

		//Создание копии записи в catalog_items 
		$item_id = $this->itemAdd(array(
			'name'					=> $srecord['name'],
			'code'					=> $srecord['code'],
			'brand_name'			=> $srecord['brand_name'],
			'group_id'				=> $in_group,
			'currency'				=> $srecord['currency'],
			'price'					=> $srecord['price'],
			'packing'				=> $srecord['packing'],
			'description'			=> $srecord['description'],
			'employer_id'			=> User::_getEmployerID()
		));

		if(empty($item_id)){
			$this->db->rollback();
			return false;
		}

		//Создание записи о складах
		$source_wh_center = (!empty($source_info['wh_center']) ? intval($source_info['wh_center']) : 0);
		$source_wh_rostov = (!empty($source_info['wh_rostov']) ? intval($source_info['wh_rostov']) : 0);
		//Задан склад Центр
		if(!empty($source_wh_center)){
			$this->db->prepare('INSERT INTO `warehouse_items` (`warehouse_id`,`item_id`,`count`,`update_time`)values(?,?,?,?)');
			$this->db->bind($source_wh_center);
			$this->db->bind($item_id);
			$this->db->bind($srecord['warehouse_center']);
			$this->db->bind($this->time_now);
			if($this->db->insert()===false){
				$this->db->rollback();
				return false;
			}
		}

		//Задан склад Ростов
		if(!empty($source_wh_rostov)){
			$this->db->prepare('INSERT INTO `warehouse_items` (`warehouse_id`,`item_id`,`count`,`update_time`)values(?,?,?,?)');
			$this->db->bind($source_wh_rostov);
			$this->db->bind($item_id);
			$this->db->bind($srecord['warehouse_center']);
			$this->db->bind($this->time_now);
			if($this->db->insert()===false){
				$this->db->rollback();
				return false;
			}
		}

		//Копирование совместимых товаров
		$this->db->prepare('SELECT * FROM `source_items_compatible` WHERE `item_id`=? LIMIT 1');
		$this->db->bind($source_item_id);
		$srecord = $this->db->selectRecord();
		if(!empty($srecord)&&is_array($srecord)){
			if($this->itemSetCompatible($item_id, $srecord['compatible']) === false){
				$this->db->rollback();
				return false;
			}
		}

		//Копирование парт номеров
		$this->db->prepare('SELECT * FROM `source_items_part_nums` WHERE `item_id`=?');
		$this->db->bind($source_item_id);
		$srecord = $this->db->select();
		if(!empty($srecord)&&is_array($srecord)){
			foreach($srecord as $row){
				if(!empty($row['part_number'])){
					if($this->itemAddPartNumber($item_id, $row['part_number'])===false){
						$this->db->rollback();
						return false;
					}
				}
			}
		}

		//Копирование изображения
		if(file_exists(DIR_ITEM_SCREENSHOTS.'/'.$source_item_id.'.jpg')){
			copy(DIR_ITEM_SCREENSHOTS.'/'.$source_item_id.'.jpg', DIR_CATALOG_IMAGES.'/'.$item_id.'.jpg');
		}

		$this->db->commit();
		return $item_id;
	}#end function





	/*==============================================================================================
	ФУНКЦИИ: Работа со структурой каталога
	==============================================================================================*/


	/*
	 * Добавление раздела в структуру каталога
	 */
	public function addItemGroup($fields=array()){
		if(!$this->is_init) return false;
		if(empty($fields)) return false;
		return $this->db->addRecord('phpshop_categories', $fields, $this->defaultCatalogCategory);
	}#end function




	public function getGroupInfo($parent_id=0){
		$parent_id = intval($parent_id);
		if(empty($parent_id)) return false;
		return $this->db->selectRecord('SELECT * FROM `source_items_groups` WHERE `group_id`='.$parent_id.' LIMIT 1');
	}

	/*
	 * Создание структуры каталога
	 */
	public function createItemGroups(){
		if(!$this->is_init) return false;

		#WHILE
		while(true){

			$group = $this->db->selectRecord('SELECT * FROM `source_items_groups` WHERE `catalog_group_id`=0 LIMIT 1');
			if(empty($group)) break;

			$parent_to = 0;
			if($group['parent_id']!=0 && $group['parent_is_phpshop_id']!=1){
				do{
				$parent = $this->getGroupInfo($group['parent_id']);
				}while($parent['parent_id']!=0 && $parent['catalog_group_id']==0);
				if($parent['group_id']!=$group['group_id']){
					if($parent['catalog_group_id'] == 0){
						$parent = $group;
					}else{
						$parent_to = $parent['catalog_group_id'];
					}
				}
			}

			$catalog_group_id = $this->addItemGroup(array(
				'id'					=> null,
				'name'					=> $group['name'],
				'parent_to'				=> ($group['parent_is_phpshop_id']==1 ? $group['parent_id'] : $parent_to),
				'descrip'				=> $group['description']
			));

			if(!empty($catalog_group_id)){
				$this->db->prepare('UPDATE `source_items_groups` SET `catalog_group_id`=? WHERE `group_id`=?');
				$this->db->bind($catalog_group_id);
				$this->db->bind($group['group_id']);
				$this->db->update();
			}

		}#WHILE

		return true;
	}#end function





}#end class

?>
