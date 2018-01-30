<?php
/*==================================================================================================
Title	: Pattern Singleton 
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

trait Trait_SingletonArray{


	/*==============================================================================================
	Переменные класса
	==============================================================================================*/

	#Массив объектов классов
	protected static $_instances = array();

	#Название текущего соединения
	public $connection = null;


	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	final private function __construct(){}



	/*
	 * Клонирование объекта
	 */
	final private function __clone(){}



	/*
	 * Конструктор класса для дочерних классов
	 *
	 * В дочернем классе функция должна быть описана следующим образом:
	 * protected function _init(...){...}
	 */
	protected function init(){}








	/*==============================================================================================
	Функции
	==============================================================================================*/



	/*
	 * Создание экземпляра класса
	 */
	final static public function getInstance(){

		$args	= func_get_args();
		$class	= get_called_class();
		$exists	= isset(self::$_instances[$args[0]]);

		#Класс инициализируется первый раз
		if(!$exists){

			self::$_instances[$args[0]] = new $class();
			self::$_instances[$args[0]]->connection = $args[0];
		}
		#Класс уже был инициализирован
		else{

			#возвращаем экземпляр класса
			return self::$_instances[$args[0]];

		}

		#Вызов функции init для вновь созданного класса
		#Функция __construct была намерянно отключена и не может быть использована в дочерних классах
		call_user_func_array(
			array(
				self::$_instances[$args[0]],
				'init'
			),
			$args
		);

		return self::$_instances[$args[0]];
	}#end function



	/*
	 * Возвращает массив $_instances
	 */
	final static public function getAllInstances(){
		return self::$_instances;
	}#end function


}#end class

?>
