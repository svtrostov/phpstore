<?php
/*==================================================================================================
Описание: Работа с номенклатурой источника данных
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


class SourceItems{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	public $options = array(
		'db' => null
	);
	private $source_id		= 0;		#ID Источника данных
	private $is_init		= false;	#Признак корректной инициализации класса
	private $db 			= null;		#Указатель на экземпляр базы данных

	private $defaultSourceItem = array(
		'item_id'				=> null,
		'source_id'				=> 0,		//Идентификатор источника данных
		'source_item_id'		=> '',		//Идентификатор товара у источника
		'name'					=> '',		//Наименование товара
		'code'					=> '',		//Код номенклатуры у источника
		'currency'				=> 'rub',	//Код валюты, в которой указана цена
		'price'					=> 0,		//Цена товара у источника
		'price_formula'			=> '',		//Формула расчета цены товара
		'brand_name'			=> '',		//Бренд товара
		'group_id'				=> 0,		//Идентификатор группы товара в локальном каталоге
		'group_name'			=> '',		//Группа товара
		'subgroup_name'			=> '',		//Подгруппа товара
		'packing'				=> '',		//Упаковка товара
		'description'			=> '',		//Описание товара
		'warehouse_rostov'		=> 0,		//Количество товара в Ростове
		'warehouse_center'		=> 0,		//Количество товара в центральном офисе
		'add_time'				=> DBNOW,		//Время добавления записи в базу данных
		'update_time'			=> DBNOW,		//Время последнего обновления записи
		'need_update_price'		=> 1,		//Признак необходимости обновления цены товара
		'need_update_warehouse'	=> 1,		//Признак необходимости обновления остатков
		'image_checked'			=> 0,		//Признак проведения проверки на наличие изображения
		'compatible_checked'	=> 0,		//Признак проведения проверки на наличие информации о совместимости товара
		'catalog_item_id'		=> 0,		//Идентификатор связного товара в реальном каталоге
		'catalog_second_item_id'=> 0,		//Идентификатор дополнительного связного товара в реальном каталоге для обновления остатков и цены
		'need_catalog_update'	=> 0		//Признак необходимости обновления информации о товаре в каталоге
	);

	private $defaultSourceItemHistory = array(
		'item_id'			=> 0,		//Идентификатор записи ( `item_id` из source_items)
		'update_time'		=> DBNOW,
		'price'				=> 0,
		'warehouse_rostov'	=> 0,
		'warehouse_center'	=> 0,
		'who_update'		=> 0
	);


	private $defaultSourceItemCompatible = array(
		'item_id'	=> 0,
		'compatible'=> ''
	);


	private $defaultSourceItemPartNums = array(
		'item_id'	=> 0,
		'part_number'=> ''
	);


	private $defaultSourceItemGroup = array(
		'group_id'			=> null,	//Идентификатор группы
		'parent_id'			=> 0,		//ID родительского элемента
		'source_id'			=> 0,		//ID Источника получения информации
		'name'				=> '',		//Наименование раздела
		'description'		=> '',		//Наименование раздела
		'catalog_group_id'	=> 0,		//ID соответствующей группы в каталоге
		'parent_is_phpshop_id'	=> 0	//Признак, указывающий что указанный ID родителя (parent_id) - ID каталога PHPSHop
	);


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	public function __construct($source_id=0){
		$source_id = intval($source_id);
		$this->db = Database::getInstance('main');
		$this->is_init = (!empty($this->db) ? true : false);
		$this->source_id = $source_id;
		$this->defaultSourceItem['source_id'] = $source_id;
		$this->defaultSourceItem['add_time'] = DBNOW;
		$this->defaultSourceItem['update_time'] = DBNOW;
		$this->defaultSourceItemHistory['update_time'] = DBNOW;
		$this->defaultSourceItemGroup['source_id'] = $source_id;
	}#end function







	/*==============================================================================================
	ФУНКЦИИ: Работа с товарами
	==============================================================================================*/


	/*
	 * Проверяет существование товара источника в базе данных
	 */
	public function itemExists($keys=array()){
		if(empty($keys)||!$this->is_init) return false;

		$item = $this->itemGet($keys, true);
		return !empty($item);
	}#end function




	/*
	 * Возвращает запись товара или список записей товаров
	 */
	public function itemGet($keys=array(), $single=false, $fields=''){
		if(!$this->is_init) return false;
		$select_fields='';
		if(!empty($fields)&&is_array($fields)){
			if($this->source_id<>0) $fields['source_id'] = $this->source_id;
			$fields = array_intersect(array_keys($this->defaultSourceItem), $fields);
			foreach($fields as $field){
				$select_fields.=(empty($select_fields)?'':',').'`'.$select_fields.'`';
			}
		}else{
			if($fields=='*' && !empty($single)){
				$select_fields='count(*)';
			}else{
				$fields=strval($fields);
				$select_fields=(array_key_exists($fields,$this->defaultSourceItem) ? '`'.$fields.'`' : '*');
			}
		}

		$conditions = $this->db->buildSqlConditions($keys);
		$prepare = 'SELECT '.$select_fields.' FROM `source_items` '.($conditions!=''?' WHERE '.$conditions:'').(!empty($single)?' LIMIT 1': '');
		$this->db->prepare($prepare);

		return (empty($single) ? $this->db->select() : $this->db->selectRecord());
	}#end function




	/*
	 * Обновление записи о товаре
	 */
	public function itemUpdate($keys=array(), $fields=array()){
		if(!$this->is_init) return false;
		if(empty($keys)||empty($fields)) return false;
		if(isset($fields['group_name'])) $fields['group_name'] = trim($fields['group_name']);
		if(isset($fields['subgroup_name'])) $fields['subgroup_name'] = trim($fields['subgroup_name']);
		return $this->db->updateRecord('source_items', $keys, $fields, $this->defaultSourceItem);
	}#end function




	/*
	 * Добавление записи о товаре
	 */
	public function itemAdd($fields=array()){
		if(!$this->is_init) return false;
		if(empty($fields)) return false;
		if(isset($fields['group_name'])) $fields['group_name'] = trim($fields['group_name']);
		if(isset($fields['subgroup_name'])) $fields['subgroup_name'] = trim($fields['subgroup_name']);
		return $this->db->addRecord('source_items', $fields, $this->defaultSourceItem);
	}#end function



	/*
	 * Добавление записи в историю изменения цен о товаре
	 */
	public function itemAddInHistory($fields=array()){
		if(!$this->is_init) return false;
		if(empty($fields)||empty($fields['item_id'])) return false;
		return $this->db->addRecord('source_items_history', $fields, $this->defaultSourceItemHistory);
	}#end function



	/*
	 * Добавление записи о серийном номере товара
	 */
	public function itemAddPartNumber($item_id=0, $part_number=''){
		if(!$this->is_init) return false;
		if(empty($item_id)||empty($part_number)) return false;

		$this->db->prepare('INSERT INTO `source_items_part_nums` (`item_id`,`part_number`)VALUES(?,?)');
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

		$this->db->prepare('REPLACE INTO `source_items_compatible` (`item_id`,`compatible`)VALUES(?,?)');
		$this->db->bind($item_id);
		$this->db->bind($compatible);

		if($this->db->simple()===false) return false;
		return true;
	}#end function




	/*
	 * Список идентификаторов записей для обновления
	 */
	public function itemsForUpdate(){
		if(!$this->is_init) return false;

		$this->db->prepare('SELECT `item_id` FROM `source_items` WHERE `source_id`=? AND (`need_update_price`=1 OR `need_update_warehouse`=1 OR `image_checked`=0 OR `compatible_checked`=0)');
		$this->db->bind($this->source_id);

		return $this->db->selectFromField('item_id');
	}#end function



	/*
	 * Список идентификаторов записей для обновления в каталоге
	 */
	public function catalogItemsForUpdate(){
		if(!$this->is_init) return false;

		$this->db->prepare('SELECT `item_id` FROM `source_items` WHERE `need_catalog_update`=1 AND `catalog_item_id`<>0');

		return $this->db->selectFromField('item_id');
	}#end function



	/*
	 * Список идентификаторов записей для добавления в каталог
	 */
	public function catalogItemsForAdd(){
		if(!$this->is_init) return false;

		//$this->db->prepare('SELECT `item_id` FROM `source_items` WHERE `catalog_item_id`=0');

		return $this->db->selectFromField('item_id','SELECT `item_id` FROM `source_items` WHERE `catalog_item_id`=0');
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
		return $this->db->addRecord('source_items_groups', $fields, $this->defaultSourceItemGroup);
	}#end function




	/*
	 * Создание структуры каталога
	 */
	public function createItemGroups($catalog_id=0, $full_recreate=false, $source_id=0){
		if(!$this->is_init) return false;

		$source_id = (empty($source_id) ? $this->source_id : $source_id);

		//Список групп и подгрупп
		if($source_id > 0){
			$this->db->prepare('SELECT DISTINCT `group_name`, `subgroup_name` FROM `source_items` WHERE `source_id`=?');
			$this->db->bind($source_id);
		}else{
			$this->db->prepare('SELECT DISTINCT `group_name`, `subgroup_name` FROM `source_items`');
		}
		if(($list = $this->db->select())===false) return false;

		//Пересоздание каталога
		if(!empty($full_recreate)){
			if($source_id > 0){
				$this->db->prepare('DELETE FROM `source_items_groups` WHERE `source_id`=?');
				$this->db->bind($source_id);
			}else{
				$this->db->prepare('TRUNCATE `source_items_groups`');
			}
			if($this->db->simple()===false) return false;
		}

		$catalog_groups = array();
		foreach($list as $item){
			$item['group_name'] = trim($item['group_name']);
			$group_name = (empty($item['group_name']) ? '__empty__' : $item['group_name']);
			$subgroup_name = trim($item['subgroup_name']);
			if(!isset($catalog_groups[$group_name])){
				$catalog_groups[$group_name] = array();
			}
			if(empty($subgroup_name)) $subgroup_name = '__empty__'; 
				if(!isset($catalog_groups[$group_name][$subgroup_name])) $catalog_groups[$group_name][$subgroup_name] = true;
		}

		//Просмотр групп/подгрупп
		foreach($catalog_groups as $group_name=>$subgroups){

			$group_id  = 0;

			//Группа
			if($group_name!='__empty__'){
				$this->db->prepare('SELECT * FROM `source_items_groups` WHERE `name` LIKE ? AND `parent_id`=? LIMIT 1');
				$this->db->bind($group_name);
				$this->db->bind($catalog_id);
				if(($group_info = $this->db->selectRecord())===false) return false;
				//Нет группы
				if(empty($group_info)||!is_array($group_info)){
					if(($group_id = $this->addItemGroup(array(
						'parent_id'		=> $catalog_id,
						'name'			=> $group_name,
						'description'	=> $group_name,
						'parent_is_phpshop_id'	=>	($catalog_id>0 ? 1 : 0)
					)))===false) return false;
					if(APP_DEBUG) echo "Add new group ID:".$group_id." ".$group_name." to source_items_groups\n";
				}//Нет группы
				else{
					$group_id = $group_info['group_id'];
				}
			}else{
				$group_name = '';
				$group_id = 0;
			}

			//Подгруппа
			foreach($subgroups as $subgroup_name=>$idle_value){
				if($subgroup_name != '__empty__'){
					if($group_id > 0){
						$this->db->prepare('SELECT * FROM `source_items_groups` WHERE `name` LIKE ? AND `parent_id`=? LIMIT 1');
						$this->db->bind($subgroup_name);
						$this->db->bind($group_id);
					}else{
						$this->db->prepare('SELECT * FROM `source_items_groups` WHERE `name` LIKE ? AND `parent_id`=? LIMIT 1');
						$this->db->bind($subgroup_name);
						$this->db->bind($catalog_id);
					}
					if(($subgroup_info = $this->db->selectRecord())===false) return false;
					//Нет подгруппы
					if(empty($subgroup_info)||!is_array($subgroup_info)){
						if(($subgroup_id = $this->addItemGroup(array(
							'parent_id'		=> ($group_id>0?$group_id:$catalog_id),
							'name'			=> $subgroup_name,
							'description'	=> $subgroup_name,
							'parent_is_phpshop_id' => ($group_id>0||$catalog_id==0?0:1)
						)))===false) return false;
						if(APP_DEBUG) echo "Add new subgroup ID:".$subgroup_id." ".$subgroup_name." to source_items_groups\n";
					}//Нет подгруппы
					else{
						$subgroup_id = $subgroup_info['group_id'];
					}
				}else{
					$subgroup_id = $group_id;
				}
				$this->db->prepare('UPDATE `source_items` SET `group_id`=? WHERE '.($source_id>0?'`source_id`='.$source_id.' AND ':'').' `group_id`=0 AND `group_name` LIKE ? AND `subgroup_name` LIKE ?');
				$this->db->bind($subgroup_id);
				$this->db->bind(($group_name == '__empty__' ? '' : $group_name));
				$this->db->bind(($subgroup_name == '__empty__' ? '' : $subgroup_name));
				if($this->db->update()===false) return false;
			}

		}//Просмотр групп/подгрупп

		return true;
	}#end function





	/*
	 * Получение структуры каталога
	 */
	public function getItemGroups($extended=false){
		if(!$this->is_init) return false;

		$this->db->prepare('SELECT * FROM `source_items_groups` WHERE `source_id`=?');
		$this->db->bind($this->source_id);
		if(($list = $this->db->select())===false) return false;

		if(!$extended) return $list;

		$menu = array();
		$filter = array();
		$item_ids = array();
		$childs = array();

		foreach($list as $i=>$item){
			$childs[$item['parent_id']] = true;
			$filter[]=$item;
			$item_ids[$item['group_id']] = $item['group_id'];
		}

		foreach($filter as $item){
			if(!empty($item['parent_id']) && !isset($item_ids[$item['parent_id']])) continue;
			$menu[]=$item;
		}

		return $menu;
	}#end function




}#end class

?>
