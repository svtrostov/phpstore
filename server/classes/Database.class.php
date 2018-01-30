<?php
/*==================================================================================================
Title	: Mysql Database class
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');

if(!defined('DB_ERROR')) define('DB_ERROR',-1);
if(!defined('DB_NONE')) define('DB_NONE',0);
if(!defined('DB_BOTH')) define('DB_BOTH',MYSQL_BOTH);
if(!defined('DB_ASSOC')) define('DB_ASSOC',MYSQL_ASSOC);
if(!defined('DB_NUM')) define('DB_NUM',MYSQL_NUM);
if(!defined('DBNOW')) define('DBNOW',date("Y-m-d H:i:s"));


if(!defined('BIND_NULL')) define('BIND_NULL',0);
if(!defined('BIND_TEXT')) define('BIND_TEXT',1);
if(!defined('BIND_NUM')) define('BIND_NUM',2);
if(!defined('BIND_FIELD')) define('BIND_FIELD',3);
if(!defined('BIND_SQL')) define('BIND_SQL',4);



class Database{

	use Trait_SingletonArray;

	/*==============================================================================================
	Переменные класса
	==============================================================================================*/


	#Настройки по-умолчанию для экземпляра класса
	protected $options = array(
		'host'			=> 'localhost',		#Хост или IP
		'port'			=> null,			#Номер порта сервера СУБД (если NULL, то определяется номер порта по умолчанию для указанного типа сервера)
		'username'		=> '',				#Логин
		'password'		=> '',				#Пароль
		'database'		=> '',				#Имя базы
		'charset'		=> 'utf8',			#Кодировка
		'connect'		=> true,			#Подключаться ли при инициализации
		'tables'		=> array(),			#Ассоциативный массив наименования таблиц array('php_alias' => 'real_db_table_name')
		'defaults'		=> array()			#Ассоциативный массив полей таблиц по-умолчанию array('php_alias' => array('field1'=>'value1','field2'=>'value2','field3'=>'value3'))
	);

	#Внутренние свойства
	protected $connected		= false;		#Признак подключения
	public $correct_init		= false;		#Признак корректной инициализации

	private $db					= null; 		#Идентификатор соединения с базой

	#Переменные текущего соединения с MySQL базой
	public $db_username		= '';
	public $db_password		= '';
	public $db_database		= '';
	public $db_charset		= '';
	public $db_host			= '';


	public		$template		= '';			#Темплейт SQL запроса
	protected	$binds			= array();		#Параметры SQL запроса
	protected	$bind_type		= DB_NONE;		#Тип передаваемых параметров: DB_NONE - не определено, DB_ASSOC - ассоциированный массив, DB_NUM - линейный индексный массив

	public  $sql				=	'';			#SQL запрос с обработанными параметрами
	public  $res				=	null;		#Результат
	public  $records			=	null;		#Массив записей в ответе
	private $in_transaction		= false;		#Признак выполнения операции в транзакции
	private $magic_quotes_gpc	= false;




	/*==============================================================================================
	Инициализация
	==============================================================================================*/


	/*
	 * Конструктор класса
	 */
	protected function init($connection='main', $options=null){

		#Установка опций
		if(is_array($options)) $this->options = array_merge($this->options, $options);

		$this->magic_quotes_gpc = get_magic_quotes_gpc();

		$this->tables = $this->options['tables'];
		$this->defaults = $this->options['defaults'];

		if(
			empty($this->options['host'])||
			empty($this->options['username'])||
			empty($this->options['database'])
		){
			return debugError(array(
				'id'		=> 'EDB00003',
				'desc'		=> 'Incorrect settings for '.$connection.' connection',
				'data'		=> $this->options,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}

		$this->correct_init = true;

		#Соединение с базой данных
		if($this->options['connect']) $this->connect();

	}#end function



	/*
	 * Деструктор класса
	 */
	public function __destruct(){
		$this->close();
	}#end function



	/*
	 * В контексте объекта при вызове недоступных методов
	 */
	public function __call($name, $arguments){
		return debugError(array(
			'id'		=> 'EDB00001',
			'desc'		=> 'Call unacceptable method: '.$name,
			'data'		=> $name,
			'return'	=> false,
			'file'		=> __FILE__,
			'line'		=> __LINE__,
			'class'		=> __CLASS__,
			'function'	=> __METHOD__
		));
	}#end function



	/*
	 * Чтение данных из недоступных свойств
	 */
	public function __get($name){
		return debugError(array(
			'id'		=> 'EDB00002',
			'desc'		=> 'Reading invalid properties: '.$name,
			'data'		=> $name,
			'return'	=> false,
			'file'		=> __FILE__,
			'line'		=> __LINE__,
			'class'		=> __CLASS__,
			'function'	=> __METHOD__
		));
	}#end function






	/*==============================================================================================
	Работа с базой данных
	==============================================================================================*/



	/*
	 * Соединение с базой данных
	 */
	private function connect(){

		if(!$this->correct_init) return false;
		if($this->connected) return true;

		#Переменные текущего соединения с MySQL базой
		$this->db_username	= $this->options['username'];
		$this->db_password 	= $this->options['password'];
		$this->db_database	= $this->options['database'];
		$this->db_charset	= $this->options['charset'];
		$this->db_host		= $this->options['host'];

		$this->db = @mysql_connect($this->db_host,$this->db_username,$this->db_password, true);
		if(!$this->db){
			return debugError(array(
				'id'		=> 'EDB00011',
				'desc'		=> 'Error connecting to MySQL server '.$this->db_host,
				'data'		=> mysql_errno().': '.mysql_error(),
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		if(!@mysql_select_db($this->db_database,$this->db)){
			return debugError(array(
				'id'		=> 'EDB00012',
				'desc'		=> 'Error opening database '.$this->db_database,
				'data'		=> mysql_errno().': '.mysql_error(),
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		if(!$this->setCharset($this->db_charset)){
			return debugError(array(
				'id'		=> 'EDB00013',
				'desc'		=> 'Error setting the specified encoding '.$this->db_charset,
				'data'		=> mysql_errno().': '.mysql_error(),
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		
		$this->connected = true;
		
		return true;
	}#end function



	/*
	 * Закрытие соединения
	 */
	public function close(){

		if(!$this->correct_init) return false;

		#если нет открытого соединения, выходим
		if (!$this->connected) return false;

		#если начата транзакция, заканчиваем с отменой изменений
		if($this->in_transaction) $this->rollback();

		$this->freeResult();

		#Закрываем соединение, обнуляем свойства
		$this->db = null;
		$this->connected = false;

		return true;
	}#end function




	/*
	 * Проверка соединения с базой данных
	 */
	public function ping($autoconnect=true){
		#Если соединение не установлено - инициализируем
		if(!is_resource($this->db)){
			#Соединение с базой данных
			return $this->connect();
		}
		if(!mysql_ping($this->db)){
		 if($autoconnect) return $this->connect(); #Если соединение было прервано - устаналвиваем заново
			return false;
		}
		return true;
	}#end function




	/*
	 * Смена кодировки
	 */
	private function setCharset($charset='utf8'){
		if(!mysql_query('SET NAMES "'.addslashes($charset).'"')) return false;
		return true;
	}




	/*
	 * Функция экранирования переменных
	 */
	public function getQuotedValue($value='',$quote=true){
		
		# если magic_quotes_gpc включена - используем stripslashes
		if($this->magic_quotes_gpc){
			$value = stripslashes($value);
		}
		if($this->db) return ($quote?"'":'') . mysql_real_escape_string($value, $this->db) . ($quote?"'":'');
		
		$search=array("\\","\0","\n","\r","\x1a","'",'"');
		$replace=array("\\\\","\\0","\\n","\\r","\Z","\'",'\"');

		return ($quote?"'":'') .str_replace($search,$replace,$value) . ($quote?"'":'');
	}#end function




	/*
	 * Удаление экранирования со значений
	 */
	public function dequoteValue($row=null){
	 	if(is_array($row)){
			foreach($row as $key => $value)
				$row[$key] = stripslashes($value);
			return $row;
		}
		return stripslashes($row);
	}#end function





	/*
	 * Функция определяет, является ли SQL запрос "на запись" 
	 */
	public function isWriteSql($sql){
		return (preg_match('/^\s*"?(SET|INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|TRUNCATE|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql));
	}#end function
	


	/*
	 * Функция возвращает, установлена ли в настоящий момент транзакция
	 */
	public function inTransaction(){
		return $this->in_transaction;
	}#end function




	/*==============================================================================================
	SQL таблицы
	==============================================================================================*/


	/*
	 * Проверка существования таблицы в базе данных
	 */
	public function tableExists($table_name=null){
		$found = 0;
		$sql = 'SHOW TABLES FROM `'.$this->db_database.'`';
		if($this->query($sql) === false) return false; 
		while ($row = @mysql_fetch_assoc($this->res)){
			if ($table_name == $row['Tables_in_'.$this->db_database]){
				$found = 1;
				break;
			}
		}
		$this->freeResult();
		
		return $found; 
	}#end function



	/*
	 * Возарвщает имена полей в таблице базы данных
	 */
    function getTableFields($table_name=''){
		if(empty($table_name))return false;
		return $this->selectFromField(0, 'SHOW COLUMNS FROM `'.addslashes($table_name).'`', MYSQL_NUM);
	}#end function



	/*
	 * Функция возвращает реальное имя таблицы на основе его алиаса
	 */
	public function getTableName($alias=''){
		if(empty($this->tables))return $alias;
		return !empty($this->tables[$alias]) ? $this->tables[$alias] : $alias;
	}#end function



	/*
	 * Функция возвращает массив полей и значений по-умолчанию для указанной таблицы
	 */
	public function getTableDefaults($alias=''){
		if(empty($this->defaults) || empty($this->defaults[$alias])) return array();
		return $this->defaults[$alias];
	}#end function





	/*==============================================================================================
	Функции формирования SQL: Параметрические запросы
	==============================================================================================*/




	/*
	 * Задает шаблон для SQL-запроса (данные следует заменить на ? при привязке по порядку или на :имя\s при привязке по имени)
	 * Cовмещать два типа привязки нельзя.
	 * 
	 * $template - SQL шаблон
	 * $binds - массив параметров для подстановки в шаблон
	 */
	public function prepare($template='', $binds=null){

		$this->template	= $template;
		$this->binds = array();
		$this->bind_type = DB_NONE;
		if(is_array($binds)) return $this->bind($binds);

		return $this;
	}#end function




	/*
	 * Заменяет следующий знак ? в шаблоне запроса на экранированную строку данных
	 * Может принимать массив (в т.ч. ассоциативный) в кач. значения
	 * Cовмещать два типа привязки нельзя.
	 * 
	 * $value - добавляемое значени или массив значений
	 * $name - ключ добавляемого значения
	 * $type - тип значения, может принимать следующие аргументы:
	 * 			BIND_NULL - null,
	 * 			BIND_TEXT - текст, автоматически обрамляется соответствующими кавычками и квотируется
	 * 			BIND_NUM - число (целое, с запятой), ничего не делается, остается в заданном виде
	 * 			BIND_FIELD - имя таблицы или поля, обрамляется соответствующими для СУБД кавычками
	 * 			BIND_SQL - sql инструкция, никак не обрабатывается, просто подставляется в указанное место, аля trait
	 */
	public function bind($value='', $name=null, $type=BIND_TEXT){

		if($this->bind_type == DB_ERROR) return false;

		if(is_array($value)){
			foreach ($value as $k=>$v){
				$this->bind($v, (!is_numeric($k) ? $k : null), $type);
			}
			return $this;
		}

		switch($type){
			case BIND_NULL: $value = 'NULL'; break;
			case BIND_NUM: $value = (is_numeric($value) ? $value : $this->getQuotedValue($value));
			case BIND_FIELD:
			case BIND_SQL: 
			break;
			case BIND_TEXT:
			default:
				$value = (is_null($value)) ? 'NULL' : $this->getQuotedValue($value);
		}


		if(empty($name)){
			$bind_type = DB_NUM;
			$this->binds[] = $value;
		}else{
			$bind_type = DB_ASSOC;
			$name = ltrim($name, ':');
			$this->binds[$name] = $value;
		}

		if($this->bind_type == DB_NONE){
			$this->bind_type = $bind_type;
		}else{
			if($this->bind_type != $bind_type){
				$this->bind_type = DB_ERROR;
				return debugError(array(
					'id'		=> 'EDB00032',
					'desc'		=> 'Mixed calls to bind()',
					'data'		=> func_get_args(),
					'return'	=> false,
					'file'		=> __FILE__,
					'line'		=> __LINE__,
					'class'		=> __CLASS__,
					'function'	=> __METHOD__
				));
			}
		}

		return $this;
	}#end function



	public function bindNull($value='', $name=null){return $this->bind($value,$name,BIND_NULL);}
	public function bindText($value='', $name=null){return $this->bind($value,$name,BIND_TEXT);}
	public function bindNum($value='', $name=null){return $this->bind($value,$name,BIND_NUM);}
	public function bindField($value='', $name=null){return $this->bind($value,$name,BIND_FIELD);}
	public function bindSql($value='', $name=null){return $this->bind($value,$name,BIND_SQL);}




	/*
	 * Возвращает результат SQL-запрос после обработки шаблона prepare() с учетом переданных в bind() параметров
	 */
	public function parseTemplate(){

		if($this->bind_type == DB_ERROR) return false;
		$template = $this->template;

		if(empty($template)) return debugError(array(
			'id'		=> 'EDB00411',
			'desc'		=> 'Not specified SQL template',
			'data'		=> null,
			'return'	=> false,
			'file'		=> __FILE__,
			'line'		=> __LINE__,
			'class'		=> __CLASS__,
			'function'	=> __METHOD__
		));

		$binds = $this->binds;
		$bind_type = $this->bind_type;
		$sql = '';

		if($bind_type == DB_NUM){
			$aq = explode('?', $template);
			$aq_cnt = count($aq);
			if($aq_cnt != (count($binds)+1)){
				return debugError(array(
					'id'		=> 'EDB00030',
					'desc'		=> 'Failed to generate a query. Mismatch template prepare() with the number of calls bind()',
					'data'		=> array('template'=>$template,'binds'=>$binds),
					'return'	=> false,
					'file'		=> __FILE__,
					'line'		=> __LINE__,
					'class'		=> __CLASS__,
					'function'	=> __METHOD__
				));
			}
			for($i=0; $i<$aq_cnt-1; $i++){
				$sql .= $aq[$i] . $binds[$i];
			}
			$sql .= $aq[$i];
		}else{
			$aq = explode(':', $template);
			$aq_cnt = count($aq);
			$sql = $aq[0];
			if($aq_cnt > 1){
				for($i=1; $i<$aq_cnt; $i++){
					$kv = explode(';',$aq[$i],2);
					if(count($kv) > 1){
						$key = $kv[0];
						$text = $kv[1];
					}else{
						$key = $kv[0];
						$text = '';
					}
					if(!isset($binds[$key])) return debugError(array(
						'id'		=> 'EDB00030',
						'desc'		=> 'Failed to generate a query. Mismatch template prepare() with the number of calls bind()',
						'data'		=> array('template'=>$template,'binds'=>$binds,'need_key'=>$key),
						'return'	=> false,
						'file'		=> __FILE__,
						'line'		=> __LINE__,
						'class'		=> __CLASS__,
						'function'	=> __METHOD__
					));
					$sql .= $binds[$key] . $text;
				}
			}
		}

		return $sql;
	}#end function








	/*==============================================================================================
	Работа с транзакциями
	==============================================================================================*/

	
	/*
	 * Установить уровень изоляции для транзакций
	 */
	public function transactionLevel($level='REPEATABLE READ'){
		if($this->in_transaction){
			return debugError(array(
				'id'		=> 'EDB00024',
				'desc'		=> 'Attempt to set the transaction isolation level in an open transaction',
				'data'		=> null,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		$set = 'REPEATABLE READ';
		switch($level){
			case 'READ UNCOMMITTED': $set='READ UNCOMMITTED'; break;
			case 'READ COMMTITED': $set='READ COMMTITED'; break;
			case 'REPEATABLE READ': $set='REPEATABLE READ'; break;
			case '': $set='SERIALIZABLE'; break;
		}
		if($this->query('SET TRANSACTION ISOLATION LEVEL '.$set) === false)return false;
		
		return true;
	}#end function


	/*
	 * Начало транзакции
	 */
	public function transaction(){
		if($this->in_transaction){
			return debugError(array(
				'id'		=> 'EDB00020',
				'desc'		=> 'Opening a new transaction with an already opened and Uncommitted transaction',
				'data'		=> null,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		if($this->query('START TRANSACTION') === false)return false;
		
		return true;
	}#end function




	/*
	 * Завершение транзакции - commit
	 */
	public function commit(){
		if(!$this->in_transaction){
			return debugError(array(
				'id'		=> 'EDB00021',
				'desc'		=> 'COMMIT call in the absence of transaction',
				'data'		=> null,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		if($this->query('COMMIT') === false)return false;

		return true;
	}#end function




	/*
	 * Завершение транзакции - rollback
	 */
	public function rollback(){
		if(!$this->in_transaction){
			return debugError(array(
				'id'		=> 'EDB00022',
				'desc'		=> 'ROLLBACK call in the absence of transaction',
				'data'		=> null,
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}
		if($this->query('ROLLBACK') === false)return false;

		return true;
	}#end function





	/*==============================================================================================
	SQL запросы
	==============================================================================================*/



	/*
	 * Освобождает ресурсы, выделенные для выполнения запроса
	 */
	public function freeResult(){
		if($this->res) @mysql_free_result($this->res);
		$this->res = null;
	}#end function





	/*
	 * Выполняет SQL-запрос
	 */
	public function query($sql=''){

		if(!$this->correct_init) return false;
		
		#Проверка соединения с базой данных
		if(!$this->ping()) return false;

		#Выбор текущей SQL инструкции
		$this->sql = (empty($sql)) ? $this->parseTemplate() : $sql;
		if(empty($this->sql)) return false;

		#Запрос
		$this->res = @mysql_query($this->sql, $this->db);
		if(mysql_error($this->db)){
			return debugError(array(
				'id'		=> 'EDB00022',
				'desc'		=> 'Error during executing SQL query: ',
				'data'		=> array('sql'=>$this->sql,'errno'=>mysql_errno(),'error'=>mysql_error()),
				'return'	=> false,
				'file'		=> __FILE__,
				'line'		=> __LINE__,
				'class'		=> __CLASS__,
				'function'	=> __METHOD__
			));
		}

		#Определение типа запроса
		switch(1){
			case preg_match("/^SELECT/i",$this->sql):
				$this->sql_type = 1; # SELECT
			break;
			case preg_match("/^INSERT/i",$this->sql):
				$this->sql_type = 2; # INSERT
			break;
			case preg_match("/^UPDATE/i",$this->sql):
				$this->sql_type = 3; # UPDATE
			break;
			case preg_match("/^DELETE/i",$this->sql):
				$this->sql_type = 4; # DELETE
			break;
			case preg_match("/^START TRANSACTION/i",$this->sql):
				$this->sql_type = 6; # Прочие запросы
				$this->in_transaction = true; # Транзакция начата
				$this->transact_result = 0; #результат транзакции не определен
			break;
			case preg_match("/^COMMIT/i",$this->sql):
				$this->sql_type = 6; # Прочие запросы
				$this->in_transaction = false; # Транзакция
				$this->transact_result = 1; #результат транзакции положительный
			break;
			case preg_match("/^ROLLBACK/i",$this->sql):
				$this->sql_type = 6; # Прочие запросы
				$this->in_transaction = false; # Транзакция закончена
				$this->transact_result = 2; #результат транзакции отрицательный
			break;
			default:
			$this->sql_type = 6; # Прочие запросы
		}

		#Запрос выполнен
		return $this->res;
	}#end function




	/*
	 * Последний вставленный ID с автоинкремента
	 */
	public function getLastInsertId(){
		if(!$this->res)return false;
		return (int)@mysql_insert_id($this->db);
	}#end function




	/*
	 * Количество затронутых строк при изменении
	 */
	public function getAffectedRows(){
		if(!$this->res)return false;
		return (int)@mysql_affected_rows($this->db);
	}#end function



	/*
	 * Количество строк полученных из запроса SELECT
	 */
	public function getResultRowsCount(){
		if(!$this->res)return false;
		return (int)@mysql_num_rows($this->res);
	}#end function




	/*
	 * Выборка значения
	 */
	function result($sql=''){
		if($this->query($sql) === false) return false;
		if (@mysql_num_rows($this->res) != 1) return null;
		$r = @mysql_result($this->res, 0);
		$this->freeResult();
		return stripslashes($r);
	}#end function




	/*
	 * INSERT
	 */
	public function insert($sql=''){
		if($this->query($sql) === false)return false;
		return $this->getLastInsertId();
	}#end function




	/*
	 * UPDATE
	 */
	public function update($sql=''){
		if($this->query($sql) === false)return false;
		return $this->getAffectedRows();
	}#end function




	/*
	 * DELETE
	 */
	public function delete($sql=''){
		if($this->query($sql) === false)return false;
		return $this->getAffectedRows();
	}#end function




	/*
	 * результаты запроса SELECT
	 */
	public function select($sql='', $type=MYSQL_ASSOC, $records=null){

		if($this->query($sql) === false)return false;
		$this->records = (empty($records)) ? array() : $records;
		while($row = @mysql_fetch_array($this->res, $type)){
			$this->records[] = $this->dequoteValue($row);
		}
		reset($this->records);
		$this->freeResult();

		return $this->records;
	}#end function




	/*
	 * результаты запроса SELECT LIMIT 1
	 */
	public function selectRecord($sql='', $type=MYSQL_ASSOC){
		
		if($this->query($sql) === false)return false;
		if (@mysql_num_rows($this->res) != 1) return null;
		$this->row = $this->dequoteValue(@mysql_fetch_array($this->res, $type));
		$this->freeResult();
		
		return $this->row;
	}#end function




	/*
	 * Выбор данных в ассоциированный массив с ключем,
	 * получаемым из значений поля field
	 * При этом ключ будет иметь только одно значение
	 */
	public function selectByKey($field=0, $sql='', $type=MYSQL_ASSOC, $records=null){

		if($this->query($sql) === false)return false;
		if(empty($field))$field = 0;
		$this->records = (empty($records)) ? array() : $records;
		while($row = @mysql_fetch_array($this->res, $type)){
			$this->records[$row[$field]] = $this->dequoteValue($row);
		}
		reset($this->records);
		$this->freeResult();

		return $this->records;
	}#end function


	/*
	 * Выбор данных в ассоциированный массив с ключем,
	 * получаемым из значений поля key, значением выступает значение из поля value
	 * При этом ключ будет иметь только одно значение
	 */
	public function selectAsKV($key=0, $value=0, $sql='', $records=null){
		if($this->query($sql) === false)return false;
		if(empty($key))$key = 0;
		if(empty($value))$value = 0;
		$this->records = (empty($records)) ? array() : $records;
		while($row = @mysql_fetch_array($this->res, $type)){
			$this->records[$row[$key]] = $row[$value];
		}
		reset($this->records);
		$this->freeResult();

		return $this->records;
	}#end function


	/*
	 * Выбор данных в ассоциированный массив с ключем,
	 * получаемым из значений поля field
	 * При этом ключ будет иметь массив значений состоящий из одного или более элементов
	 */
	public function selectByKeyArray($field=0, $sql='', $type=MYSQL_ASSOC, $records=null){

		if($this->query($sql) === false)return false;
		if(empty($field))$field = 0;
		$this->records = (empty($records)) ? array() : $records;
		while($row = @mysql_fetch_array($this->res, $type)){
			if(!isset($this->records[$row[$field]]))$this->records[$row[$field]]=array();
			$this->records[$row[$field]][] = $this->dequoteValue($row);
		}
		reset($this->records);
		$this->freeResult();

		return $this->records;
	}#end function




	/*
	* результаты запроса SELECT по одному полю $field
	*
	* Принимает аргументы:
	* $field - поле, которое будет использоваться для вывода данных
	* $sql - SQL инструкция, если не указана, будет выполнена инструкция из шаблона prepare()
	* Возвращает:
	* Одномерный (линейный) массив результатов со значениями поля FIELD или FALSE в случае ошибки
	*/
	public function selectFromField($field = '', $sql = null, $type = MYSQL_ASSOC){

		if($this->query($sql) === false)return false;
		if(empty($field))$field = 0;
		$this->records = array();
		while($row = @mysql_fetch_array($this->res, $type)){
			$this->records[] = $this->dequoteValue($row[$field]);
		}
		reset($this->records);
		$this->freeResult();

		return $this->records;
	}#end function




	/*
	 * Запрос без возврата результата
	 */
	public function simple($sql=''){
		if($this->query($sql) === false)return false;
		return true;
	}#end function




	/*
	 * Добавление записи в базу данных
	 */
	public function addRecord($table='',$fields=array(), $defaults=null, $priority=''){

		if(empty($table)||empty($fields)) return false;
		if(empty($defaults)) $defaults = $this->getTableDefaults($table);
		if(empty($defaults)) return false;

		$add_fields = array_intersect(array_keys($defaults), array_keys($fields));
		$adds = array();
		if(empty($add_fields)) return false;
		foreach($add_fields as $add_field){
			$adds[$add_field] = $fields[$add_field];
		}
		$fields = array_merge($defaults, $adds);
		$ins_names=array();
		$ins_q=array();
		$binds=array();
		foreach($fields as $field=>$value){
			$ins_names[]='`'.$field.'`';
			$ins_q[]='?';
			$binds[]=$value;
		}
		$this->prepare('INSERT '.$priority.' INTO `'.$this->getTableName($table).'` ('.implode(',',$ins_names).')VALUES('.implode(',',$ins_q).')');
		foreach($binds as $bind) $this->bind($bind);

		return $this->insert();
	}#end function




	/*
	 * Обновление записи
	 */
	public function updateRecord($table='', $keys=array(), $fields=array(), $defaults=null){

		if(empty($table)||empty($fields)) return false;
		if(empty($defaults)) $defaults = $this->getTableDefaults($table);
		if(empty($defaults)) return false;

		$update_fields = array_intersect(array_keys($defaults), array_keys($fields));
		if(empty($update_fields)) return false;
		$binds=array();
		$updates='';
		foreach($update_fields as $update_field){
			$updates.=(empty($updates)?'':',').'`'.$update_field.'`=?';
			$binds[]=$fields[$update_field];
		}

		$where='';
		$where_binds=array();
		foreach($keys as $key=>$value){
			$where.=($where==''?' WHERE ':' AND ').'`'.$key.'`=?';
			$where_binds[]=$value;
		}

		$this->prepare('UPDATE `'.$this->getTableName($table).'` SET '.$updates.$where);
		foreach($binds as $bind) $this->bind($bind);
		foreach($where_binds as $bind) $this->bind($bind);

		if($this->update()===false) return false;
		return true;
	}#end function




	/*
	 * Удаление записи
	 */
	public function deleteRecord($table='', $keys=array(), $defaults=null){

		if(empty($table)) return false;
		if(empty($defaults)) $defaults = $this->getTableDefaults($table);
		if(empty($defaults)) return false;

		$where='';
		$where_binds=array();
		$delete_fields = array_intersect(array_keys($defaults), array_keys($keys));
		if(empty($delete_fields)) return false;
		foreach($delete_fields as $delete_field){
			$where.=($where==''?' WHERE ':' AND ').'`'.$key.'`=?';
			$where_binds[]=$value;
		}

		$this->prepare('DELETE FROM `'.$this->getTableName($table).'` '.$where);
		foreach($where_binds as $bind) $this->bind($bind);

		if($this->delete()===false) return false;
		return true;
	}#end function



	/*
	 * Выполнение пакета запросов в транзакции
	 */
	public function multiQuery($queries=array()){

		if(empty($queries)) return true;

		//Проверяем наличие транзакции
		$in_transaction = $this->in_transaction;
		if($in_transaction) $this->transaction();

		foreach($queries as $query){

			if(empty($query)) continue;

			if(is_array($query)){
				
				
			}else{
				if($this->simple($query)===false){
					if(!$in_transaction) $this->rollback();
					return false;
				}
			}

		}//foreach

		//Выполнено успешно
		if(!$in_transaction) $this->commit();

		return true;
	}#end function








	/*==============================================================================================
	Функции формирования SQL: Обработка условий
	==============================================================================================*/


	/*
	 * Построение части SQL запроса на основании данных массива условий
	 * 
	 * $conditions - массив условий
	 * $separator - связка между условиями: 
	 * 		если часть SQL запроса будет как перечисление полей для UPDATE, используйте ","
	 * 		если часть SQL запроса будет после WHERE или ON, используйте для связки "AND" или "OR" в зависимости от запроса
	 * $prefix - Подстановка перед каждым полем названия таблицы: $prefix='table' => table.`field`
	 * 		Префикс не обрамляется обратными кавычками, если нужно обрамление таблицы, передавайте ее имя в массиве условий вместе с полем: 
	 * 
	 * Запись в $conditions:
	 * $conditions = array(
	 * 
	 * 		'testfield=25',			#Так задается SQL текст, который не будет вставлен в результирующую SQL строку без каких-либо изменений
	 * 
	 * 		'myfield'=>'test',		#Так задается конструкция [поле][=][значение], поле и значение квотируются, 
	 * 								#между ними применяется оператор равенства, результатом для MySQL будет: `myfield`='test'
	 * 
	 * 		'field2'=>array(1,2,3),	#Так задается конструкция [поле] IN ([значение1],[значение2],[значение3]), поле и значение квотируются,
	 * 								#между ними применяется оператор IN (входит в перечисление), результатом для MySQL будет: `field2` IN ('1','2','3')
	 * 
	 * 		array(							#Так задается произвольная конструкция вида [поле][=][значение], если значение value является массивом, 
	 * 			'field' => 'test',			#То обработка массива осуществялется в зависимости от значения в bridge (если не задано, по умолчанию ",")
	 * 			'value' => array(1,2,3),	#при bridge="," -> `test` NOT IN (1,2,3)
	 * 			'glue' => 'NOT IN',			#при bridge="OR"("AND") -> (`test` NOT IN (1) OR `test` NOT IN (2) OR `test` NOT IN (3))
	 * 			'bridge' => ',',			#за исключением, когда оператор задан как "BETWEEN", bridge в этом случае не используется
	 * 			'type' => BIND_NUM			#будет обработано только 2 элемента массива value -> `test` BETWEEN 1 AND 2
	 * 		),
	 * 		array('test',array(1,2,3),'NOT IN',',',BIND_NUM)	#Альтернативная запись вышеуказанного массива в неассоциированном виде, где элементы:
	 * 															#[0]-поле(*), [1]-значение(null), [2]-тип данных(BIND_TEXT), [3]-оператор(= или IN), [4]-связка(,)
	 * );
	 * 
	 * Примеры элементов в массиве $conditions и результат (для MySQL):
	 * "test != 'xxx'"													-> test != 'xxx'
	 * 'test' => 'xxx'													-> `test`='xxx'
	 * 'test' => null													-> `test`=NULL
	 * 'test' => array(1,2,3)											-> `test` IN ('1','2','3')
	 * array('test','xxx') 												-> `test`='xxx'
	 * array('test',123,null,'>=') 										-> `test`>='123'
	 * array('test',999,BIND_NUM,'!=','') 								-> `test`!=999
	 * array('test',array(1,2,3)) 										-> `test` IN ('1','2','3')
	 * array('field'=>'test','value'=>array(1,2,3),'type'=>BIND_NUM) 	-> `test` IN (1,2,3)
	 * array('test',array(1,2,3),BIND_NUM,'NOT IN','') 					-> `test` NOT IN (1,2,3)
	 * array('test',array(1,2,3),BIND_NUM,'!=','AND')					-> (`test` != 1 AND `test` != 2 AND `test` != 3)
	 * array('test',array(4,8,6),'','BETWEEN') 							-> `test` BETWEEN '4' AND '8' <<< '6' отсекается, используется только первые два элемента массива
	 * array('test',array(4,8),BIND_NUM,'BETWEEN','') 					-> `test` BETWEEN 4 AND 8
	 * array('test') 													-> `test` = NULL
	 * array('test',null) 												-> `test` = NULL
	 * array('test',null,'','!=') 										-> `test` !=NULL
	 * array('','SELECT field FROM table WHERE field>4',BIND_SQL)		-> (SELECT field FROM table WHERE field>4)
	 * 
	 * Примеры некорректных элементов:
	 * array('test',9,BIND_NUM,'BETWEEN','') 							-> `test` BETWEEN 9 <<< некорректный SQL!
	 * array('test',array()) 											-> `test`='' <<< внимание!
	 * array('test',array(),'','IN') 									-> `test` IN '' <<< внимание! некорректный SQL!
	 * array('test',9,'','IN') 											-> `test` IN '9' <<< некорректный SQL!
	 * array()															->  <<< некорректно, будет пропущено
	 * null,															->  <<< некорректно, будет пропущено
	 * 
	 * Пример вызова:
	 * $sql_conditions = $db->buildSqlConditions(array(
	 * array('test',array(1,2,3),'!=','AND',BIND_NUM)
	 * ),'AND');
	 */
	public function buildSqlConditions($conditions=null, $prefix='', $separator='AND'){

		if(empty($conditions)||!is_array($conditions)) return '';

		$result = array();

		#Просмотр conditions
		foreach($conditions as $k=>$v){

			$k_is_field = !is_numeric($k);
			$v_is_array = is_array($v);
			#Значение не задано массивом
			if(!$v_is_array){
				#'test' => 'xxx'
				if($k_is_field){
					$result[] = (empty($prefix)?'':$prefix.'.').$this->getQuotedField($k).'='.(!is_null($v) ? $this->getQuotedValue($v) : 'NULL');
				}
				#"test != 'xxx'"
				else{
					if($v!=null) $result[] = $v;
				}

				continue;
			}

			#'test' => array(1,2,3)
			if($v_is_array && $k_is_field){
				$result[] = (empty($prefix)?'':$prefix.'.').$this->getQuotedField($k).' IN ('.implode(',',array_map(array($this,'getQuotedValue'),$v)).')';
				continue;
			}

			#$v - ассоциированный массив
			if(isset($v['field'])){
				$field	= (empty($prefix)?'':$prefix.'.').$this->getQuotedField($v['field']);
				$value	= isset($v['value']) ? $v['value'] : null;
				$type	= isset($v['type']) ? $v['type'] : BIND_TEXT;
				$glue	= isset($v['glue']) ? strtoupper($v['glue']) : '=';
				$bridge	= !empty($v['bridge']) ? $v['bridge'] : ',';
			}
			#$v - линейный индексный массив
			else{
				#Если задан пустой массив - пропускаем
				if(empty($v)) continue;
				$field	= is_null($v[0]) ? null : ((empty($prefix)?'':$prefix.'.').$this->getQuotedField($v[0]));
				$value	= isset($v[1]) ? $v[1] : null;
				$type	= !empty($v[2]) ? $v[2] : BIND_TEXT;
				$glue	= !empty($v[3]) ? strtoupper($v[3]) : '=';
				$bridge	= !empty($v[4]) ? $v[4] : ',';
			}

			#Если нужно вернуть только имя поля
			if($type==BIND_FIELD){
				$result[] = $field;
				continue;
			}

			#Фикс для оператора, удаляем символы %
			#Могут присутствовать в операторах "%LIKE%", "LIKE%", "%LIKE", "NOT %LIKE%", "NOT LIKE%", "NOT %LIKE"
			$gluefix = str_replace('%','',$glue);

			#Если значение должно быть NULL
			if(is_null($value)||$type==BIND_NULL){
				$result[] = $field.' '.$gluefix.' NULL';
				continue;
			}

			#Если значение - SQL подзапрос
			if($type==BIND_SQL){
				$str = (is_array($value) ? $this->buildSqlConditions($value, ($bridge==',' ? 'AND' : $bridge)) : $value);
				$result[] = (is_null($field)?'':$field.' '.(empty($gluefix)?'':$gluefix)).' ('.$str.')';
				continue;
			}

			#Если значение не задано
			if(empty($value)){
				switch($type){
					case BIND_NUM: $value='0'; break;
					case BIND_TEXT:
					default: $value='';
				}
			}

			#Значение задано массивом
			if(is_array($value)){

				if($gluefix == '=' && $bridge == ',') $gluefix='IN';

				#`test` BETWEEN 23 AND 334
				if($gluefix =='BETWEEN'){
					$v1 = ($type==BIND_NUM ? $value[0] : $this->getQuotedValue($value[0]));
					$v2 = (isset($value[1]) ? ($type==BIND_NUM ? $value[1] : $this->getQuotedValue($value[1])) : ($type==BIND_NUM ? '0' : '\'\''));
					$result[] = $field.' BETWEEN '.$v1.' AND '.$v2;
					continue;
				}

				if($bridge == ','){
					if($type!=BIND_NUM) $value = array_map(array($this,'getQuotedValue'),$value);
					$result[] = $field.' '.$gluefix.' ('.implode(',',$value).')';
				}else{
					$str = '';
					foreach($value as $item){
						#Обработка значения в зависимости от оператора, передаем именно $glue, а не $gluefix
						if($type!=BIND_NUM) $item = $this->buildSqlConditionsCheckGlue($glue, $item);
						$str .= (empty($str) ? '(' : ' '.$bridge.' ') . $field.' '.$gluefix.' '.$item;
					}
					$result[] = $str.')';
				}

				continue;
			}#Значение задано массивом

			#Значение задано как число
			if($type==BIND_NUM){
				$result[] = $field.' '.$gluefix.' '.$value;
				continue;
			}

			#Обработка значения в зависимости от оператора, передаем именно $glue, а не $gluefix
			$value = $this->buildSqlConditionsCheckGlue($glue, $value);

			#Обычное значение
			$result[] = $field.' '.$gluefix.' '.$value;


		}#Просмотр conditions

		#Результат
		return (count($result) > 0 ? implode(' '.$separator.' ',$result) : '');
	}#end function



	/*
	 * Вспомогательная функция, проверяет тип оператора и соответствующим образом преобразует значение
	 */
	private function buildSqlConditionsCheckGlue($glue, $value){

		#Обработка операторов "%LIKE%", "LIKE%", "%LIKE", "NOT %LIKE%", "NOT LIKE%", "NOT %LIKE"
		switch($glue){
			case 'LIKE%':
			case 'NOT LIKE%':
				return $this->getQuotedValue(rtrim($value,'%').'%'); 

			case '%LIKE':
			case 'NOT %LIKE':
				return $this->getQuotedValue('%'.ltrim($value,'%'));

			case '%LIKE%':
			case 'NOT %LIKE%':
				return $this->getQuotedValue('%'.trim($value,'%').'%'); 

			default:
				return $this->getQuotedValue($value);
		}

	}#end function




	/*
	 * Возвращает поле, обрамленное в соответствующие выбранной СУБД кавычки 
	 * 
	 * $field - имя поля или выражение
	 * 
	 * Примеры:
	 * getQuotedField('field')				-> `field`
	 * getQuotedField('table.field')		-> `table`.`field`
	 * getQuotedField('max(field)')			-> max(field)
	 * getQuotedField('max(`field`)')		-> max(`field`)
	 * getQuotedField('field x')			-> `field` as `x`
	 * getQuotedField('field as x')			-> `field` as `x`
	 * getQuotedField('max(`field`) x')		-> max(`field`) as `x`
	 */
	public function getQuotedField($field=''){

		if(empty($field)) return $field;
		$field = trim($field,"\r\n\t ");

		#Преобразование нескольких пробелов в один
		$field = preg_replace('/\s\s+/', ' ', $field);

		#Проверяем наличие "(" и выходим в случае обнаружения
		#Считаем, что поля переданные с символом "(" являются функциями, 
		#типа min(field), max(field), count(*)
		if(strpos($field, '(') !== false) return $field;

		#Проверка наличия алиаса в названии поля
		#Алиас может быть задан одним из следующих способов:
		#field as alias
		#field alias
		$alias = '';
		$this->getQuotedFieldAlias($field, $alias);

		return $this->getQuotedFieldEscape($field).$alias;
	}#end function


	/*
	 * Возвращает поле, обрамленное в соответствующие выбранной СУБД кавычки, внутренняя функция
	 */
	private function getQuotedFieldEscape($field=''){

		#Имя переданного поля имеет формат table.field
		if(strpbrk($field,'.')!==false){
			return implode('.', array_map(array($this, 'getQuotedField'),explode('.',$field)));
		}

		if($field == '*') return $field;
		$field = trim($field,'[]"`\'');

		#Замена обрамлений полей для разных типов СУБД
		return '`'.$field.'`';
	}#end function



	/*
	 * Проверка наличия в имени поля алиаса и приведение его к корректному для СУБД виду
	 */
	private function getQuotedFieldAlias(&$field, &$alias){

		#Проверка наличия алиаса в названии поля
		#Алиас может быть задан одним из следующих способов:
		#field as alias
		#field alias
		$alias = '';
		if(strpos($field, ' ') !== false){
			$alias = strstr($field, ' ');
			$field = substr($field, 0, - strlen($alias));
			$alias = preg_replace('/^AS /i', '', ltrim($alias));
			$alias = ' as '.$this->getQuotedFieldEscape($alias);
			return true;
		}

		return false;
	}#end function



}#end class


?>
