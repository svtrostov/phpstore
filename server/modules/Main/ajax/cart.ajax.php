<?php
/*==================================================================================================
Title	: Cart AJAX
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');


$request_action = Request::_get('action');
$db = Database::getInstance('main');

LABEL_user_START:

#Обработка AJAX запроса, в зависимости от запрошенного действия
switch($request_action){


	/*******************************************************************
	 * Добавление товара в корзину
	 ******************************************************************/
	case 'add.to.cart':
		$product_id = $request->getId('product_id', 0);
		if(empty($product_id)) return Ajax::_responseError('Ошибка выполнения', 'Идентификатор товара задан некорректно');
		$shop = Shop::getInstance();

		$p = $shop->productExists($product_id,true);
		if(empty($p)) return Ajax::_responseError('Ошибка выполнения', 'Товар не найден');

		$cart = Session::_get('cart');
		if(empty($cart)||!is_array($cart)) $cart = array();
		if(isset($cart['p'.$product_id])){
			$cart['p'.$product_id]['count'] = (isset($cart['p'.$product_id]['count']) ? $cart['p'.$product_id]['count'] + 1 : 1);
		}else{

			$bridge_info = ($p['bridge_id']>0 ? $shop->getBridgeInfo($p['bridge_id'], true) : null);
			if(!empty($bridge_info)){
				$price = $bridge_info['price'];
				$base_price = $bridge_info['base_price'];
				$bridge_id = $bridge_info['bridge_id'];
			}else{
				$base_price = $p['base_price'];
				$price = $shop->getPrice($p['currency'],$base_price, true, $p['offer_discount']);
				$bridge_id = 0;
			}

			$cart['p'.$product_id] = array(
				'product_id' 	=> $product_id,
				'bridge_id'		=> $bridge_id,
				'name'			=> $p['name'],
				'available'		=> $p['count'],
				'currency'		=> $p['currency'],
				'base_price'	=> $base_price,
				'offer_discount'=> $p['offer_discount'],
				'exchange'		=> $shop->currencyExchange($p['currency'], 1),
				'price'			=> $price,	//Цена товара
				'count'			=> 1,		//Количество товаров
				'timestamp'		=> time()	//Время добавления товара в корзину
			);
		}
		Session::_set('cart', $cart);

		$cart = $shop->cartInfo();

		#Выполнено успешно
		Ajax::_setData(array(
			'cart_count' => $cart['count'],
			'cart_sum'	=> $cart['sum'].'.00',
			'sess'		=> Session::_getAll()
		));

		return Ajax::_responseSuccess('Товар добавлен в корзину','Выбранный Вами товар <b>'.$p['name'].'</b> добавлен в корзину.','hint');
	break;



	default:
	Ajax::_responseError('/main/ajax/cart', 'Undefined action: '.Request::_get('action'));
}
?>
