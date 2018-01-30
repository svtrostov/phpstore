<?php
/*==================================================================================================
Title	: Shop class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');




class Shop{

	use Trait_SingletonUnique;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#db
	public $db = null;
	public $currencies = null;
	public $config = null;
	public $discounts = null;
	public $config_shop_percent = 0;
	public $client_discount_percent = 0;
	public $shop_percent = 0;

	public $table_clients = '';
	public $table_users = '';
	public $table_extra = '';
	public $table_ankets = '';


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init(){
		$this->db = Database::getInstance('main');
		$this->getConfig();
		$this->getCurrencies();
		$this->getDiscounts();
		$this->config_shop_percent = floatval($this->config['shopPercent'])/100;	//% магазина к цене товара
		$this->client_discount_percent = $this->getClientDiscountPercent()/100;			//% скидки клиента
		$this->shop_percent = max(0, ($this->config_shop_percent - $this->client_discount_percent));
	}#end function



	/*
	 * Вызов недоступных методов
	 */
	public function __call($name, $args){
		return false;
	}#end function


	public function __destruct(){
		//
	}#end function


	/*
	 * Загружает настройки из базы данных
	 */
	public function getConfig(){
		if(empty($this->config)){
			//Значеничя по-умолчанию для конфигурации магазина
			$defs = array(
				'shopPercent'	=> 0
			);
			$this->config = $this->db->selectAsKV('param','value','SELECT * FROM `config`');
			foreach($defs as $k=>$v){
				if(!isset($this->config[$k]))$this->config[$k]=$v;
			}
		}
		return $this->config;
	}


	/*
	 * Возвращает значение запрошенной опции
	 */
	public function getConfigValue($key,$default=''){
		if(empty($this->config) || !isset($this->config[$key])) return $default;
		return $this->config[$key];
	}


	/*==============================================================================================
	Работа с валютными курсами
	==============================================================================================*/


	/*
	 * Загружает список обменных курсов валют из базы данных
	 */
	public function getCurrencies(){
		if(empty($this->currencies)){
			//Значеничя по-умолчанию для курсов валют по отношению к рублю
			$defs = array(
				'rub'	=> 1,
				'rur'	=> 1,
				'usd'	=> 1,
				'eur'	=> 1
			);
			$this->currencies = $this->db->selectAsKV('code','exchange','SELECT * FROM `currencies`');
			foreach($defs as $k=>$v){
				if(!isset($this->currencies[$k]))$this->currencies[$k]=$v;
			}
		}
		return $this->currencies;
	}


	/*
	 * Конвертирует валюту по курсу в рубли
	 */
	public function currencyExchange($code='usd',$value=0){
		if(empty($code)||empty($value)) return 0;
		if(!array_key_exists($code, $this->currencies)) return 0;
		return round(round($this->currencies[$code] * floatval($value),3),2);
	}#end function


	/*==============================================================================================
	Работа с Ценами
	==============================================================================================*/

	/*
	 * Возвращает правило вычисления цены для товара, объединенного мостом
	 */
	public function getBridgePriceRule(){
		switch($this->getConfigValue('bridgeRulePrice','max')){
			case 'min': return 'MIN';
			case 'avg':
			case 'middle': return 'AVG';
			case 'max':
			default:
				return 'MAX';
		}
	}


	/*
	 * Загружает список скидок из базы данных
	 */
	public function getDiscounts(){
		$this->discounts = $this->db->selectAsKV('discount_id','percent','SELECT * FROM `discounts`');
		return $this->discounts;
	}


	/*
	 * Возвращает % скидки текущего клиента
	 */
	public function getClientDiscountPercent(){
		$client = Client::getInstance();
		$discount = 0;
		$discount_id = $client->get('discount_id',0);
		if($discount_id>0 && array_key_exists($discount_id, $this->discounts)){
			$discount = floatval($this->discounts[$discount_id]); //скидка клиента
		}
		return min($discount, $this->config_shop_percent);
	}


	/*
	 * Возвращает цену товара в рублях для клиента, с учетом "накруток" и скидок
	 */
	public function getPrice($code='usd',$value=0, $with_discount=true, $offer_discount=0){
		$sum = $this->currencyExchange($code, $value); //Конвертация в рубли
		if($offer_discount>0){
			$percent = max(0, ($this->config_shop_percent - $offer_discount/100));
		}else{
			$percent = ($with_discount ? $this->shop_percent : $this->config_shop_percent);
		}
		return ceil($sum + $sum * $percent);
	}


	/*
	 * Вычисляет базовую цену товара при добавлении в каталог
	 */
	public function getBasePrice($code='usd',$value=0){
		$price = floatval($value);
		$sum = $this->currencyExchange($code, $price);	//Конвертация в рубли
		if($sum < 0) return 0;
		if($sum > 0 && $sum <= 50) return $price * 1.29;
		if($sum >= 50 && $sum <= 1000) return $price * 1.04;
		return $price;
	}


	/*
	 * Вычисляет оптимальную цену и количество товара на складах
	 * Возвращает запись о товаре с ценой и остатками на складах
	 */
	public function getBridgeInfo($bridge_id=0, $with_discount=true){
		if(empty($bridge_id)) return false;
		$products = $this->db->select('SELECT `product_id`,`article`,`name`,`base_price`,`currency`, (`offer`*`offer_discount`) as `offer_discount` FROM `products` as P WHERE `bridge_id`='.intval($bridge_id));
		if(empty($products)) return null;
		$ids = array();
		$price_min = -1;
		$price_max = -1;
		$base_price_min = -1;
		$base_price_max = -1;
		for($i=0;$i<count($products); $i++){
			$ids[$i] = $products[$i]['product_id'];
			$products[$i]['base_price_rub'] = $this->currencyExchange($products[$i]['currency'],$products[$i]['base_price']);
			$base_price = $products[$i]['base_price_rub'];
			$price = $this->getPrice($products[$i]['currency'],$products[$i]['base_price'], $with_discount, $products[$i]['offer_discount']);
			$products[$i]['price'] = $price;
			if($price_min == -1 || $price_min > $price){
				$price_min = $price;
				$base_price_min = $base_price;
			}
			if($price_max == -1 || $price_max < $price){
				$price_max = $price;
				$base_price_max = $base_price;
			}
		}
		$price = 0;
		$base_price = 0;
		$count = 0;
		switch($this->getConfigValue('bridgeRulePrice','max')){
			case 'min': 
				$price = $price_min;
				$base_price = $base_price_min;
			break;
			case 'avg':
			case 'middle':
				$price = $price_min + ceil(($price_max-$price_min)/2);
				$base_price = $base_price_min + ceil(($base_price_max-$base_price_min)/2);
			break;
			case 'max':
			default:
				$price = $price_max;
				$base_price = $base_price_max;
		}

		$warehouses = $this->db->select('SELECT PW.`warehouse_id` as `warehouse_id`, IFNULL(SUM(PW.`count`),0) as `count` FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id` IN('.implode(',',$ids).') GROUP BY W.`warehouse_id`');
		foreach($warehouses as $w){
			$count+=$w['count'];
		}

		return array(
			'bridge_id'		=> $bridge_id,
			'products'		=> $products,
			'warehouses'	=> $warehouses,
			'ids'			=> $ids,
			'price'			=> $price,
			'base_price'	=> $base_price,
			'count'			=> $count
		);
	}




	/*==============================================================================================
	Работа с Остатками товара
	==============================================================================================*/

	/*
	 * Обновление остатков на складе по товару
	 */
	public function whUpdate($product_id=0, $warehouse_id=0, $count=0){
		$product_id = intval($product_id);
		$warehouse_id = intval($warehouse_id);
		$count = floatval($count);
		if(empty($product_id)||empty($warehouse_id)) return false;
		$wh_record = $this->db->selectRecord('SELECT * FROM `product_warehouse` WHERE `product_id`='.$product_id.' AND `warehouse_id`='.$warehouse_id.' LIMIT 1');
		if(empty($wh_record)){
			$this->db->insert('INSERT INTO `product_warehouse` (`id`,`product_id`,`warehouse_id`,`count`) VALUES (null,'.$product_id.','.$warehouse_id.','.$count.')');
		}else{
			$this->db->update('UPDATE `product_warehouse` SET `count`='.$count.' WHERE `product_id`='.$product_id.' AND `warehouse_id`='.$warehouse_id);
		}
		return true;
	}




	/*==============================================================================================
	Работа с Каталогом
	==============================================================================================*/


	/*
	 * Проверяет, существует ли каталог и можно ли его отображать пользователю,
	 * возвращает запись каталога, или null если каталога нет или его нельзя отображать
	 * Если $path установлен в true, то дополнительно в записи каталога возвращается путь с родительскими элементами
	 */
	public function categoryExists($category_id=0, $check_enabled=true, $path=true){
		$category = $this->db->selectRecord('SELECT * FROM `categories` WHERE '.(is_numeric($category_id) ? '`category_id`='.$category_id : '`seo` LIKE "'.addslashes($category_id).'"').($check_enabled ? ' AND `enabled` > 0' : '').' LIMIT 1');
		$category['parents'] = array();
		if(empty($category)) return null;
		if(!$check_enabled && !$path) return $category;
		if(empty($category['enabled']) && $check_enabled) return null;
		if($category['parent_id']==0 || empty($path)) return $category;
		$ok=true;
		$parents=array();
		$parent_id = $category['parent_id'];
		do{
			$info = $this->db->selectRecord('SELECT `parent_id`,`name`,`seo`,`enabled` FROM `categories` WHERE `category_id`='.$parent_id.' LIMIT 1');
			$parents[]=array($parent_id, $info['name'],$info['seo']);
			if(empty($info)||(empty($info['enabled'])) && $check_enabled == true) $ok = false;
			else  $parent_id = $info['parent_id'];
		}while($parent_id>0&&$ok==true);
		if($ok==false) return null;
		$category['parents'] = array_reverse($parents);
		return $category;
	}


	/*
	 * Возвращает список дочерних категорий
	 */
	public function categoryChilds($category_id=0, $check_enabled=true){
		return $this->db->select('SELECT * FROM `categories` WHERE `parent_id`='.intval($category_id).($check_enabled ? ' AND `enabled`>0' : '').' ORDER BY `name`');
	}


	/*
	 * Возвращает массив всех категорий в виде дерева, начиная от указанной родительской категории
	 */
	private function p_categoryTree($parent_id=0, &$tree, $all_categories, $parent_enabled){
		$childs = $this->db->select('SELECT `category_id`,`name`,`enabled`,`records` FROM `categories` WHERE `parent_id`='.$parent_id.($all_categories?'':' AND `enabled`>0').' ORDER BY `name`');
		if(empty($childs)||!is_array($childs)) return array();
		$index=0;
		$all_childs = array();
		foreach($childs as $c){
			$all_childs[]=$c['category_id'];
			$tree[$index] = array(
				'category_id'	=> $c['category_id'],
				'name'			=> $c['name'],
				'enabled'		=> ($c['enabled']?true:false),
				'parent_id'		=> $parent_id,
				'parent_enabled'=> $parent_enabled,
				'records'		=> $c['records'],
				'childs'		=> array()
			);
			$cat_childs = $this->p_categoryTree($c['category_id'], $tree[$index]['childs'], $all_categories, ($c['enabled']?true:false));
			$tree[$index]['childs_ids'] = $cat_childs;
			$all_childs = array_merge($all_childs, $cat_childs);
			$index++;
		}
		return $all_childs;
	}
	public function categoryTree($parent_id=0, $all_categories=false){
		$tree = array();
		$this->p_categoryTree(intval($parent_id), $tree, $all_categories, true);
		return $tree;
	}


	/*
	 * Возвращает массив всех категорий в виде списка, начиная от указанной родительской категории
	 */
	private function p_categoryList($parent_id=0, &$list, $delimiter, $prefix, $only_enabled, $parent_enabled){
		$childs = $this->db->select('SELECT `category_id`,`name`,`enabled` FROM `categories` WHERE `parent_id`='.$parent_id.($only_enabled ? ' AND `enabled`>0':'').' ORDER BY `name`');
		if(empty($childs)||!is_array($childs)) return array();
		$all_childs = array();
		foreach($childs as $c){
			$all_childs[]=$c['category_id'];
			$name = ($prefix != '' ? $prefix . $delimiter . $c['name'] : $c['name']);
			$index=count($list);
			$enabled = ($parent_enabled && $c['enabled'] > 0);
			$list[]=array($c['category_id'], $name, null, $enabled);
			$cat_childs = $this->p_categoryList($c['category_id'], $list, $delimiter, $name, $only_enabled, $enabled);
			$list[$index][2] = $cat_childs;
			$all_childs = array_merge($all_childs, $cat_childs);
		}
		return $all_childs;
	}
	public function categoryList($parent_id=0, $delimiter, $only_enabled=true){
		$list = array();
		$prefix = '';
		$this->p_categoryList(intval($parent_id), $list, $delimiter, $prefix, $only_enabled, true);
		return $list;
	}


	/*
	 * Возвращает массив всех категорий в виде массива, начиная от указанной родительской категории
	 */
	private function p_categoryArray($parent_id=0, &$list, $only_enabled){
		$childs = $this->db->select('SELECT `category_id`,`name`,`parent_id` FROM `categories` WHERE `parent_id`='.$parent_id.($only_enabled ? ' AND `enabled`>0':'').' ORDER BY `name`');
		if(empty($childs)||!is_array($childs)) return;
		foreach($childs as $c){
			$list[]=array(
				'category_id'	=> $c['category_id'], 
				'name'			=> $c['name'], 
				'parent_id'		=> $c['parent_id']
			);
			$this->p_categoryArray($c['category_id'], $list, $only_enabled);
		}
		return;
	}
	public function categoryArray($parent_id=0, $only_enabled=true){
		$list = array();
		$prefix = '';
		$this->p_categoryArray(intval($parent_id), $list, $only_enabled);
		return $list;
	}



	/*
	 * Возвращает список продукции в категории для клиента с применением фильтров для магазина
	 * $category_id - ID категории
	 * $page - страница отображения
	 * $limit - количество товаров на странице
	 */
	public function shopCategoryProducts($category_id=0, $page=0, $limit=30, $filters=array(), $orderby='nameasc'){
		$products = array();
		$records = 0;
		$where_res = array();
		$where_pp = array();
		$inner_pp = '';
		$inner_pp_need = false;
		foreach($filters as $idx=>$f){
			switch($f['property_id']){
				case 'exists':
					if($f['selected'] == '1') $where_res[] = 'RES.`count`>0 AND RES.`count`> RES.`ccount`';
				break;

				case 'price':
					if(!empty($f['values'][0])&&!empty($f['values'][1])){
						$where_res[] = 'RES.`price_rub` BETWEEN '.$f['values'][0].' AND '.$f['values'][1];
					}else
					if(empty($f['values'][0])&&!empty($f['values'][1])){
						$where_res[] = 'RES.`price_rub` <= '.$f['values'][1];
					}else
					if(!empty($f['values'][0])&&empty($f['values'][1])){
						$where_res[] = 'RES.`price_rub` >= '.$f['values'][0];
					}
				break;

				case 'term':
					if(!empty($f['values'])) $where_pp[]='(P.`name` LIKE "%'.addslashes($f['values']).'%" OR PI.`description` LIKE "%'.addslashes($f['values']).'%")';
				break;

				default:
					switch($f['type']){
						case 'list':
						case 'multilist':
							if($f['selected'] > 0 ){
								//$where_pp[] = 'PP.`property_id`='.intval($f['property_id']).' AND PP.`value_id`='.intval($f['selected']);
								$inner_pp_need = true;
								$inner_pp.= 'INNER JOIN `product_properties` as PP'.$idx.' ON PP'.$idx.'.`product_id`=P.`product_id`';
								$where_pp[] = 'PP'.$idx.'.`property_id`='.intval($f['property_id']).' AND PP'.$idx.'.`value_id`='.intval($f['selected']);
							}
						break;
						case 'bool':
							if($f['selected'] != 'all'){
								//$where_pp[] = 'PP.`property_id`='.intval($f['property_id']).' AND PP.`value_bool`='.intval($f['selected']);
								$inner_pp_need = true;
								$inner_pp.= 'INNER JOIN `product_properties` as PP'.$idx.' ON PP'.$idx.'.`product_id`=P.`product_id`';
								$where_pp[] = 'PP'.$idx.'.`property_id`='.intval($f['property_id']).' AND PP'.$idx.'.`value_bool`='.intval($f['selected']);
							}
						break;
						case 'num':
							if(!empty($f['values'][0])&&!empty($f['values'][1])){
								//$where_pp[] = 'PP.`property_id`='.intval($f['property_id']).' AND PP.`value_num` BETWEEN '.$f['values'][0].' AND '.$f['values'][1];
								$inner_pp_need = true;
								$inner_pp.= 'INNER JOIN `product_properties` as PP'.$idx.' ON PP'.$idx.'.`product_id`=P.`product_id`';
								$where_pp[] = 'PP'.$idx.'.`property_id`='.intval($f['property_id']).' AND PP'.$idx.'.`value_num` BETWEEN '.$f['values'][0].' AND '.$f['values'][1];
							}else
							if(empty($f['values'][0])&&!empty($f['values'][1])){
								//$where_pp[] = 'PP.`property_id`='.intval($f['property_id']).' AND PP.`value_num` <= '.$f['values'][1];
								$inner_pp_need = true;
								$inner_pp.= 'INNER JOIN `product_properties` as PP'.$idx.' ON PP'.$idx.'.`product_id`=P.`product_id`';
								$where_pp[] = 'PP'.$idx.'.`property_id`='.intval($f['property_id']).' AND PP'.$idx.'.`value_num` <= '.$f['values'][1];
							}else
							if(!empty($f['values'][0])&&empty($f['values'][1])){
								//$where_pp[] = 'PP.`property_id`='.intval($f['property_id']).' AND PP.`value_num` >= '.$f['values'][0];
								$inner_pp_need = true;
								$inner_pp.= 'INNER JOIN `product_properties` as PP'.$idx.' ON PP'.$idx.'.`product_id`=P.`product_id`';
								$where_pp[] = 'PP'.$idx.'.`property_id`='.intval($f['property_id']).' AND PP'.$idx.'.`value_num` >= '.$f['values'][0];
							}
						break;
					}
				break;
			}
		}

		//if($inner_pp_need) $inner_pp = 'INNER JOIN `product_properties` as PP ON PP.`product_id`=P.`product_id`';

		$sql_orderby = '';
		switch($orderby){
			case 'nameasc': $sql_orderby = 'RES.`name` ASC'; break;
			case 'namedesc': $sql_orderby = 'RES.`name` DESC'; break;
			case 'priceasc': $sql_orderby = 'RES.`price_rub` ASC'; break;
			case 'pricedesc': $sql_orderby = 'RES.`price_rub` DESC'; break;
			default:  $sql_orderby = 'RES.`name` ASC'; break;
		}

		$sql = 
		'RES.* FROM (
			SELECT 
			P.`product_id` as `product_id`,
			P.`category_id` as `category_id`,
			P.`bridge_id` as `bridge_id`,
			P.`name` as `name`,
			P.`seo` as `seo`,
			P.`currency` as `currency`,
			P.`base_price` as `base_price`,
			CEIL(ROUND(ROUND(P.`base_price` * CUR.`exchange`,3),2) * '.(1 + $this->shop_percent).') as `price_rub`,
			(P.`offer`*P.`offer_discount`) as `offer_discount`,
			P.`pic_big` as `pic_big`,
			PI.`description` as `description`,
			(SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=P.`product_id`) as `count`,
			(SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=P.`product_id` AND PW.`warehouse_id` IN (5,3,7,9)) as `ccount`
			FROM `products` as P
			INNER JOIN `product_info` as PI ON PI.`product_id`=P.`product_id`
			INNER JOIN `currencies` as CUR ON `CUR`.`code` = P.`currency` '.$inner_pp.'
			WHERE P.`category_id`='.intval($category_id).' AND P.`enabled`>0'.(empty($where_pp) ? '' : ' AND '.implode(' AND ',$where_pp)).'
		)as RES '.(empty($where_res) ? '' : 'WHERE '.implode(' AND ',$where_res)).
		' ORDER BY '.$sql_orderby;

		//(SELECT sum(`count`) FROM `product_warehouse` WHERE `product_id`=P.`product_id` AND `warehouse_id` IN (5,3,7,9)) as `ccount`

		$this->db->query('SELECT SQL_CALC_FOUND_ROWS '.$sql);
		$records = intval($this->db->result('SELECT FOUND_ROWS()'));

		//echo "<!--\n".$sql."\n-->";

		$page = ($page > 0 ? $page - 1 : 0);
		$page_max = ($limit > 0 ? ceil($records / $limit) : 0);
		if($page >= $page_max) $page = ($page_max > 0 ? $page_max-1 : 0);
		$offset = $page * $limit;

		if(!empty($records)) $products = $this->db->select('SELECT '.$sql.($limit>0 ? ' LIMIT '.($offset).','.$limit : ''));

		return array(
			'filters'		=> $filters,
			'category_id'	=> $category_id,
			'products'		=> $products,
			'records'		=> $records,
			'page'			=> $page+1,
			'page_max'		=> $page_max,
			'per_page'		=> $limit,
			'sql'			=> $sql
		);
	}


	/*
	 * Возвращает список продукции в категории
	 * $category_id - ID категории
	 * $check_enabled - Проверять, можно ли отображать товар пользователю
	 * $page - страница отображения
	 * $limit - количество товаров на странице
	 */
	public function categoryProducts($category_id=0, $check_enabled=true, $page=0, $limit=30){
		if($page>0) $page=$page-1;
		$products = $this->db->select(
		'SELECT 
			PP.`product_id` as `product_id`,
			PP.`category_id` as `category_id`,
			PP.`bridge_id` as `bridge_id`,
			PP.`name` as `name`,
			PP.`currency` as `currency`,
			PP.`base_price` as `base_price`,
			(PP.`offer`*PP.`offer_discount`) as `offer_discount`,
			PP.`pic_big` as `pic_big`,
			PI.`description` as `description`,
			(SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=PP.`product_id`) as `count`
			FROM `products` as PP 
			LEFT JOIN `product_info` as PI ON PI.`product_id`=PP.`product_id`
			WHERE PP.`category_id`='.intval($category_id).
			($check_enabled ? ' AND `enabled`>0' : '').
			' ORDER BY `name`'.
			($limit>0 ? (' LIMIT '.($page*$limit).','.$limit) : '')
		);
		return $products;
	}


	/*
	 * Возвращает количество продукции на складах в виде ссылки на картинку и подписи
	 */
	public function productCountInfo($count=0){
		$pb_class = 3;
		if($count < 20) $pb_class = 2;
		if($count < 6) $pb_class = 1;
		if($count < 1){
			$pb_class = 0;
			$pb_text = 'Под заказ';
		}
		switch($pb_class){
			case 1:  $pb_text = 'Мало'; break;
			case 2:  $pb_text = 'Достаточно'; break;
			case 3:  $pb_text = 'Много'; break;
			default: $pb_text = 'Под заказ'; break;
		}
		return array(
			'count' => $count,
			'text'	=> $pb_text,
			'class'	=> $pb_class
		);
	}




	/*==============================================================================================
	Работа с Товаром
	==============================================================================================*/

	/*
	 * Проверяет, существует ли товар и можно ли его отображать пользователю,
	 * возвращает запись товара, или null если товара нет или его нельзя отображать
	 */
	public function productExists($product_id, $check_enabled=true){
		//$product_id = intval($product_id);
		$product = $this->db->selectRecord(
		'SELECT 
			PP.`product_id` as `product_id`,
			PP.`category_id` as `category_id`,
			PP.`bridge_id` as `bridge_id`,
			PP.`name` as `name`,
			PP.`seo` as `seo`,
			PP.`article` as `article`,
			PP.`vendor` as `vendor`,
			PP.`enabled` as `enabled`,
			PP.`currency` as `currency`,
			PP.`base_price` as `base_price`,
			(PP.`offer`*PP.`offer_discount`) as `offer_discount`,
			PP.`pic_big` as `pic_big`,
			PI.`description` as `description`,
			PI.`content` as `content`,
			PI.`part_nums` as `partnums`,
			PI.`compatible` as `compatible`,
			(SELECT sum(PW.`count`) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=PP.`product_id`) as `count`
			FROM `products` as PP 
			LEFT JOIN `product_info` as PI ON PI.`product_id`=PP.`product_id`
			WHERE '.(is_numeric($product_id) ? 'PP.`product_id`='.$product_id : 'PP.`seo` LIKE "'.addslashes($product_id).'"').
			($check_enabled ? ' AND PP.`enabled`>0' : '').
			' LIMIT 1'
		);
		$product['parents'] = array();
		if(empty($product)) return null;
		if($check_enabled && empty($product['enabled'])) return null;
		if($product['category_id']==0) return null;	//у товара нет каталога
		$ok=true;
		$parents=array();
		$parent_id = $product['category_id'];
		do{
			$info = $this->db->selectRecord('SELECT `parent_id`,`name`,`seo`,`enabled` FROM `categories` WHERE `category_id`='.$parent_id.' LIMIT 1');
			$parents[]=array($parent_id, $info['name'],$info['seo']);
			if(empty($info)||(empty($info['enabled'])) && $check_enabled == true) $ok = false;
			else  $parent_id = $info['parent_id'];
		}while($parent_id>0&&$ok==true);
		if($ok==false) return null;
		$product['parents'] = array_reverse($parents);
		//$product['compatible'] = $this->productСompatible($product_id);
		//$product['partnums'] = $this->productPartnums($product_id);
		$product['comments'] = $this->db->select('SELECT * FROM `product_comments` WHERE `product_id`='.$product['product_id']);
		return $product;
	}


	/*
	 * Список объединений
	 */
	public function getBridgeList($bridge_id=0){
		$bridge_id = intval($bridge_id);
		$bridges = $this->db->select('SELECT `product_id`,`name`,`bridge_id`,`pic_big`,`article` FROM `products` WHERE `bridge_id`'.($bridge_id>0?'='.$bridge_id:'>0').' ORDER BY `bridge_id`');
		$result = array();
		$bridge_id = 0;
		foreach($bridges as $b){
			if($b['bridge_id']!=$bridge_id){
				$bridge_id = $b['bridge_id'];
				$result[] = $bridge_id;
			}
			$result[] = array(
				'product_id'	=> $b['product_id'],
				'name'			=> $b['name'],
				'article'		=> $b['article'],
				'pic_big'		=> $b['pic_big']
			);
		}
		return $result;
	}



	/*
	 * Список товаров со специальным предложением
	 */
	public function getOffersList(){
		return $this->db->select('SELECT `product_id`,`name`,`pic_big`,`article`,`offer`,`offer_discount` FROM `products` WHERE `offer`>0 ORDER BY `name`');
	}


	/*==============================================================================================
	Работа с Корзиной
	==============================================================================================*/

	/*
	 * Возвращает количество товара и сумму заказа из корзины
	 */
	public function cartInfo(){
		$cart = Session::_get('cart');
		if(empty($cart)||!is_array($cart)) return array(
			'count' => 0,
			'sum'	=> 0,
			'pcount'=> 0
		);
		$pcount = 0;
		$sum = 0;
		$count = 0;
		foreach($cart as $p){
			$count += $p['count'];
			$sum += ceil($p['price']*$p['count']);
			$pcount++;
		}
		return array(
			'count' => $count,
			'sum'	=> $sum,
			'pcount'=> $pcount
		);
	}



	/*==============================================================================================
	Работа с Доставкой
	==============================================================================================*/

	/*
	 * Возвращает доступные методы доставки для пользователя
	 * $order_sum - общая сумма заказа
	 */
	public function deliveryAvailableList($order_sum=0){
		$order_sum=intval($order_sum);
		return $this->db->select('SELECT `delivery_id`,`name`,`price`,`desc` FROM `deliveries` WHERE `enabled`>0 AND `order_min`<='.$order_sum.' AND `order_max`>='.$order_sum);
	}

	/*
	 * Проверяет существование записи о типе доставки,
	 * если запись существует - возвращает ее
	 */
	public function deliveryExists($delivery_id=0){
		$delivery_id=intval($delivery_id);
		return $this->db->selectRecord('SELECT * FROM `deliveries` WHERE `delivery_id`='.$delivery_id.' LIMIT 1');
	}


	/*==============================================================================================
	Работа с заказами
	==============================================================================================*/


	/*
	 * Возвращает массив методов оплаты
	 */
	public function orderPaymethods($as_kv=false){
		if($as_kv) return array(
			'cash'	=> 'Наличная оплата',
			'wire'	=> 'Банковский платеж'
		);
		return array(
			array('type' => 'cash', 'name' => 'Наличная оплата'),
			array('type' => 'wire', 'name' => 'Банковский платеж')
		);
	}


	/*
	 * Возвразает массив статусов заказов
	 */
	public function orderStatuses($as_kv=false){
		if($as_kv) return array(
			'0'		=> 'Заказ отменен',
			'10'	=> 'Новый заказ',
			'20'	=> 'Обрабатывается менеджером',
			'25'	=> 'Ожидается оплата',
			'30'	=> 'Доставляется курьером',
			'40'	=> 'На точке выдачи: ул. Лермонтовская 102. оф. 6',
			'100'	=> 'Выполнен'
		);
		return array(
			array('status' => 0, 'name' => 'Заказ отменен', 'color'=>'red'),
			array('status' => 10, 'name' => 'Новый заказ', 'color'=>'#333333'),
			array('status' => 20, 'name' => 'Обрабатывается менеджером', 'color'=>'#666633'),
			array('status' => 25, 'name' => 'Ожидается оплата', 'color'=>'#006600'),
			array('status' => 30, 'name' => 'Доставляется курьером', 'color'=>'#6666cc'),
			array('status' => 40, 'name' => 'На точке выдачи', 'color'=>'#339900'),
			array('status' => 100, 'name' => 'Выполнен', 'color'=>'#77aa77'),
		);
	}


	/*
	 * Возвращает текстовое описание статуса заказа, 
	 * если $html=true, то описание возвращается как HTML код
	 */
	public function orderStatus($status=0, $html=true){
		$status_text = ''; $status_color='';
		switch($status){
			case 0: $status_text = 'Заказ отменен'; $status_color='red'; break;
			case 10: $status_text = 'Новый заказ'; $status_color='#333333'; break;
			case 20: $status_text = 'Обрабатывается менеджером'; $status_color='#666633'; break;
			case 25: $status_text = 'Ожидается оплата'; $status_color='#006600'; break;
			case 30: $status_text = 'Доставляется курьером'; $status_color='#6666cc'; break;
			case 40: $status_text = 'На точке выдачи'; $status_color='#339900'; break;
			case 100: $status_text = 'Выполнен'; $status_color='#77aa77'; break;
			default: $status_text = 'Неизвестный статус'; $status_color='#aaaaaa';
		}
		if(!$html) return $status_text;
		return '<font color="'.$status_color.'">'.$status_text.'</font>';
	}


	/*
	 * Поиск заказов по указанным параметрам
	 */
	public function ordersSearch($data=array()){
		$result = array();
		$where = array();
		$client_id = (!empty($data['client_id'])&&$data['client_id']!='all' ? intval($data['client_id']) : 0);
		$status = (!empty($data['status'])&&$data['status']!='all' ? intval($data['status']) : 0);
		$period = (!empty($data['period'])&&$data['period']!='all' ? intval($data['period']) : 0);
		$delivery = (!empty($data['delivery'])&&$data['delivery']!='all' ? intval($data['delivery']) : 0);
		$limit = (!empty($data['limit'])&&$data['limit']!='all' ? intval($data['limit']) : 0);
		$term = (!empty($data['term']) ? addslashes(str_replace('  ',' ',trim($data['term']))) : '');
		if(isset($data['manager_id'])){
			switch($data['manager_id']){
				case 'all': $manager_id = -1; break;
				case 'self': $manager_id = User::_getUserID(); break;
				default: $manager_id = abs(intval($data['manager_id']));
			}
		}else{
			$manager_id = -1;
		}


		if($client_id > 0) $where[] = 'o.`client_id`='.$client_id;
		if($status > 0) $where[] = 'o.`status`='.$status;
		if($delivery > 0) $where[] = 'o.`delivery_id`='.$delivery;
		if($period > 0) $where[] = 'o.`timestamp` >="'.date('Y-m-d 00:00:00', time()-86400*$period).'"';
		if(!empty($term)){
			$term_sql ='(';
			$term_sql .= 'o.`order_num` LIKE "%'.$term.'%"';
			$term_sql .= ' OR o.`name` LIKE "%'.$term.'%"';
			$term_sql .= ' OR o.`company` LIKE "%'.$term.'%"';
			$term_sql .= ' OR o.`email` LIKE "%'.$term.'%"';
			$term_sql .= ' OR o.`phone` LIKE "%'.$term.'%"';
			$term_sql .= ' OR o.`address` LIKE "%'.$term.'%"';
			if(is_numeric($term)) $term_sql .= ' OR o.`order_id`='.$term;
			$term_sql.=')';
			$where[] = $term_sql;
		}

		$where_sql = '';
		if(count($where)>0) $where_sql = ' WHERE '.implode(' AND ', $where);

		$sql = 'SELECT 
			o.`order_id` as `order_id`,
			o.`order_num` as `order_num`,
			o.`status` as `status`,
			o.`timestamp` as `timestamp`,
			o.`delivery_id` as `delivery_id`,
			o.`delivery_cost` as `delivery_cost`,
			o.`client_id` as `client_id`,
			o.`address` as `address`,
			o.`name` as `name`,
			o.`company` as `company`,
			o.`email` as `email`,
			o.`phone` as `phone`,
			o.`paymethod` as `paymethod`,
			(SELECT IFNULL(C.`manager_id`,0) FROM `clients` as C WHERE C.`client_id`=o.`client_id`) as `manager_id`,
			(SELECT IFNULL(count(*),0) FROM `order_products` as OP WHERE OP.`order_id`=o.`order_id`) as `products`,
			(SELECT IFNULL(sum(OP.`count`),0) FROM `order_products` as OP WHERE OP.`order_id`=o.`order_id`) as `count`,
			(SELECT IFNULL(sum(OP.`count` * OP.`price`),0) FROM `order_products` as OP WHERE OP.`order_id`=o.`order_id`) as `sum`
			FROM `orders` as o
		'.$where_sql.' ORDER BY `order_id` DESC '.
		($limit>0 ? ' LIMIT '.$limit : '');

		$result=array();
		if(($data = $this->db->select($sql))===false) return false;
		if($manager_id == -1) return $data;

		foreach($data as $row){
			if($row['manager_id'] == $manager_id) $result[]=$row;
		}
		return $result;
	}


	/*
	 * Возвращает информацию о товарах в заказе 
	 */
	public function orderProducts($order_id=0){
		$order_id = intval($order_id);
		if(empty($order_id)) return null;
		$sql = 'SELECT
			o.`order_id` as `order_id`,
			o.`product_id` as `product_id`,
			p.`name` as `name`,
			p.`bridge_id` as `bridge_id`,
			p.`article` as `article`,
			p.`currency` as `p_currency`,
			p.`base_price` as `p_base_price`,
			p.`currency` as `currency`,
			p.`base_price` as `base_price`,
			(p.`offer`*p.`offer_discount`) as `offer_discount`,
			o.`exchange` as `exchange`,
			o.`price` as `price`,
			o.`count` as `count`,
			(SELECT IFNULL(sum(PW.`count`),0) FROM `product_warehouse` as PW INNER JOIN `warehouses` as W ON W.`warehouse_id`=PW.`warehouse_id` AND W.`enabled`>0 WHERE PW.`product_id`=p.`product_id`) as `p_count`
			FROM `order_products` as o
			INNER JOIN `products` as p ON p.`product_id` = o.`product_id`
			WHERE o.`order_id`='.$order_id;
		$result = $this->db->select($sql);
		if(!empty($result)){
			for($i=0;$i<count($result);$i++){
				$result[$i]['base_price_rub'] = round(round($result[$i]['base_price'] * $result[$i]['exchange'],3),2);
				$result[$i]['p_base_price_rub'] = $this->currencyExchange($result[$i]['currency'],$result[$i]['base_price']);
				$result[$i]['p_price'] = $this->getPrice($result[$i]['currency'], $result[$i]['base_price'], false, $result[$i]['offer_discount']);
				$bridge_info = ($result[$i]['bridge_id']>0 ? $this->getBridgeInfo($result[$i]['bridge_id'], false) : null);
				if(!empty($bridge_info)){
					$result[$i]['bridge_price'] = $bridge_info['price'];
					$result[$i]['bridge_count'] = $bridge_info['count'];
					$result[$i]['bridge_base_price'] = $bridge_info['base_price'];
				}else{
					$result[$i]['bridge_price'] = $result[$i]['p_price'];
					$result[$i]['bridge_count'] = $result[$i]['p_count'];
					$result[$i]['bridge_base_price'] = $result[$i]['p_base_price_rub'];
				}

			}
		}
		return $result;
	}






	/*==============================================================================================
	Работа со свойствами товаров
	==============================================================================================*/


	/*
	 * Возвращает дерево свойств товаров
	 */
	public function getPropertiesTree(){
		$tree = array();
		$groups = $this->db->select('SELECT * FROM `property_groups` ORDER BY `name`', MYSQL_ASSOC, array(array('pgroup_id'=>0,'name'=>'-[Нет группы]-')));
		if(!empty($groups)){
			foreach($groups as $g){
				$childs = array();
				$childs_ids = array();
				$properties = $this->db->select('SELECT * FROM `properties` WHERE `pgroup_id`='.$g['pgroup_id'].' ORDER BY `name`');
				if(!empty($properties)){
					foreach($properties as $p){
						$p['is_group']	= false;
						$p['path']		= $g['name'].' / '.$p['name'];
						$childs[] = $p;
						$childs_ids[] = $p['property_id'];
					}//foreach $properties
				}//!empty($properties)
				$tree[] = array(
					'is_group'	=> true,
					'pgroup_id'	=> $g['pgroup_id'],
					'name'		=> $g['name'],
					'path'		=> $g['name'],
					'childs'	=> $childs,
					'childs_ids'=> $childs_ids
				);
			}//foreach $groups
		}//!empty($groups)
		return $tree;
	}


	/*
	 * Возвращает список каталогов, для которых характеристика установлена в качестве фильтра
	 */
	public function getPropertyCategories($property_id=0){
		if(empty($property_id)) return array();
		$result = array();
		$categories = $this->db->select('SELECT `category_id` FROM `category_properties` WHERE `property_id`='.$property_id);
		foreach($categories as $c){
			$category = $this->categoryExists($c['category_id'], false, true);
			if(!empty($category)){
				$path = $category['name'];
				$ppath = '';
				if(!empty($category['parents'])){
					foreach($category['parents'] as $p){
						$ppath .= $p[1].' / ';
					}
					
				}
				$category['path'] = $ppath . $path;
				$result[] = $category;
			}
		}
		return $result;
	}



	/*
	 * Возвращает список характеристик, установленных для каталога в качестве фильтров
	 */
	public function getCategoryProperties($category_id=0){
		if(empty($category_id)) return array();
		$result = array();
		$properties = $this->db->select('SELECT P.*,(SELECT G.`name` FROM `property_groups` as G WHERE G.`pgroup_id`=P.`pgroup_id` LIMIT 1) as `pgroup_name` FROM `category_properties` as CP INNER JOIN `properties` as P ON P.`property_id`=CP.`property_id` WHERE CP.`category_id`='.$category_id);
		foreach($properties as $p){
			$path = (empty($p['pgroup_name']) ? $p['name'] : $p['pgroup_name'].' / '.$p['name']);
			$result[] = array(
				'property_id'	=> $p['property_id'],
				'name'			=> $p['name'],
				'path'			=> $path
			);
		}
		return $result;
	}


	/*
	 * Возвращает список характеристик товара с заданными значениями
	 */
	public function getProductProperties($product_id=0){
		if(empty($product_id)) return array();
		$result = array();
		$properties = $this->db->select('
			SELECT 
			DISTINCT PP.`property_id` as `property_id`,
			P.`name` as `name`,
			P.`type` as `type`,
			PP.`value_id` as `value_id`,
			PP.`value_bool` as `value_bool`,
			PP.`value_num` as `value_num`,
			(SELECT G.`name` FROM `property_groups` as G WHERE G.`pgroup_id`=P.`pgroup_id` LIMIT 1) as `pgroup_name`
			FROM `product_properties` as PP 
			INNER JOIN `properties` as P ON P.`property_id`=PP.`property_id` 
			WHERE PP.`product_id`='.$product_id.' GROUP BY PP.`property_id`'
		);
		foreach($properties as $p){
			$path = (empty($p['pgroup_name']) ? $p['name'] : $p['pgroup_name'].' / '.$p['name']);
			$values = null;
			$applied = null;
			switch($p['type']){
				case 'list':
					$values = $this->db->select('SELECT * FROM `property_values` WHERE `property_id`='.$p['property_id']);
					$applied = intval($p['value_id']);
				break;
				case 'multilist':
					$values = $this->db->select('SELECT * FROM `property_values` WHERE `property_id`='.$p['property_id']);
					$applied = $this->db->selectFromField('value_id','SELECT `value_id` FROM `product_properties` WHERE `product_id`='.$product_id.' AND `property_id`='.$p['property_id']);
				break;
				case 'num':
					$applied = floatval($p['value_num']);
				break;
				case 'bool':
					$applied = intval($p['value_bool']);
				break;
			}
			$result[] = array(
				'property_id'	=> $p['property_id'],
				'name'			=> $p['name'],
				'path'			=> $path,
				'type'			=> $p['type'],
				'values'		=> $values,
				'applied'		=> $applied
			);
		}
		return $result;
	}


}#end class

?>