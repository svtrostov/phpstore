<?php
/*==================================================================================================
Title	: Utilities functions
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
if(!defined('APP_INSIDE')) die('Direct access not allowed!');



function redirect_or_die($url='', $die_text=''){
	if(empty($url)) die($die_text);
	Response::_location($url);
	Response::_sendHeaders();
	exit;
}



/*==============================================================================================
Debug
==============================================================================================*/


/*
 * Генератор события об ошибке
 */
function debugError($data=array()){

	$error_return	= !empty($data['return']) ? $data['return'] : false;

	if(!APP_DEBUG) return $error_return;

	$error_uid		= !empty($data['id']) ? $data['id'] : 0;
	$error_desc		= !empty($data['desc']) ? $data['desc'] : 0;
	$error_data		= !empty($data['data']) ? $data['data'] : 0;
	$file = !empty($data['file']) ? $data['file'] :'';
	$line = !empty($data['line']) ? $data['line'] :'';
	$class = !empty($data['class']) ? $data['class'] :'';
	$function = !empty($data['function']) ? $data['function'] :'';

	if(empty($function)){
		$backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
		$backtrace = $backtrace[1];
		if(is_array($backtrace)){
			$file = $backtrace['file'];
			$line = $backtrace['line'];
			$class = isset($backtrace['class'])?$backtrace['class']:'';
			$function = isset($backtrace['function'])?$backtrace['function']:'';
		}
	}

	#Вывод ошибки на экран
	echo 
	"<pre>\nDEBUG ERROR:\n".str_repeat('=',40)."\n".
	"Error ID : ".$error_uid."\n".
	"Desc     : ".$error_desc."\n".
	"Class    : ".$class."\n".
	"Function : ".$function."\n".
	"File     : ".$file."\n".
	"Line     : ".$line."\n".
	(empty($error_data) ? '' :
	"Info     : ".print_r($error_data,true)."\n").
	"Backtrace: ".print_r(debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS),true)."\n".
	str_repeat('=',40),"\n</pre>";

	return $error_return;
}#end function



/*==============================================================================================
Шифрование
==============================================================================================*/

function encrypt($data,$key=null){
	if(empty($data))return '';
	if(!defined('SECURE_KEY')) define('SECURE_KEY', hash('SHA256', Config::getOption('general','crypt_key'), true));
	if(empty($key)) $key = SECURE_KEY;
	$data = serialize($data);
	$iv_size = mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_CBC);
	$iv = mcrypt_create_iv($iv_size, MCRYPT_RAND);
	try{
		$ciphertext = @mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $data, MCRYPT_MODE_CBC, $iv);
	}catch(Exception $e){
		return '';
	}
	return base64_encode(chr($iv_size) . $iv . $ciphertext);
}


function decrypt($data, $key=null){
	if(empty($data))return '';
	if(!defined('SECURE_KEY')) define('SECURE_KEY', hash('SHA256', Config::getOption('general','crypt_key'), true));
	if(empty($key)) $key = SECURE_KEY;
	$result = '';
	try{
		$ciphertext = base64_decode($data);
		$iv_size = ord($ciphertext[0]);
		$iv = substr($ciphertext, 1, $iv_size);
		$ciphertext = substr($ciphertext, $iv_size+1);
		$result = @mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $ciphertext, MCRYPT_MODE_CBC, $iv);
		$result = @unserialize($result);
	}catch(Exception $e){
		return '';
	}
	return $result;
}



/*==============================================================================================
Функции работы с датами
==============================================================================================*/



/*
 * Функция конвертации даты из одного формата в другой
 */
function dateformat($date, $to='Y-m-d', $from='d.m.Y'){
	if (strlen($date) == 8 || strlen($date) == 17){
		$date = substr($date, 0, 6) . '20' . substr($date, 6, strlen($date));
	}
	else if($from=='d.m.Y' && $to=='Y-m-d'){
		$d = explode('.',$date);
		return $d[2].'-'.$d[1].'-'.$d[0];
	}
	return date($to, strtotime($date));
}#end function



/*
 * Функция конвертации даты вида d.m.Y в SQL Y-m-d
 */
function date2sql($date){
	return dateformat($date,'Y-m-d','d.m.Y');
}#end function



/*
 * Функция конвертации SQL Y-m-d в дату вида d.m.Y
 */
function sql2date($date){
	return dateformat($date,'d.m.Y','Y-m-d');
}#end function





/*==============================================================================================
Преобразования строки в массив и обратно
==============================================================================================*/


/*
 * Преобразует строку в массив
 */
function arrayFromString($data='',$row='&',$col='|',$key=false){
	if(empty($data)) return array();
	$rows = explode($row,$data);
	if(empty($col)) return $rows;
	$result = array();
	if($key===false){
		foreach($rows as $v) if(!empty($v))$result[] = explode($col,$v);
	}else{
		foreach($rows as $v){
			if(empty($v)) continue;
			$v = explode($col,$v);
			$result[$v[$key]] = $v;
		}
	}
	return $result;
}


/*
 * Преобразует массив в строку
 */
function stringFromArray($data='',$row='&',$col='|'){
	if(empty($data)) return '';
	$result = '';
	foreach($data as $v){
		if(!empty($result)) $result.=$row;
		if(is_array($v)) $v=implode($col,$v);
		$result.=$v;
	}
	return $result;
}




/*==============================================================================================
Функции обработки строк
==============================================================================================*/

/*
 * Удаление из строки лишних пробелов
 */
function removeWhitespace($string){ 
	$string = preg_replace('/\s+/', ' ', $string); 
	$string = trim($string); 
	return $string;
}#end function


/*
 * Преобразует строку к формату кода
 */
function getAsCriteriaCode($string=''){
	return strtolower(str_replace(array(' ','/','(',')'),array('_','_','',''),$string));
}#end function





/*
 * Генерация уникальной строки
 */
function getUniqueID($length=16, $strength=4){

	switch($strength){
		case 1: $len=10; $ch = '0123456789'; break;
		case 2: $len=26; $ch = 'abcdefghijklmnopqrstuvwxyz'; break;
		case 3: $len=36; $ch = '0123456789abcdefghijklmnopqrstuvwxyz'; break;
		case 4: $len=52; $ch = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; break;
		default: $len=61; $ch = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
	}
	$r = '';
	for($i = 0; $i < $length; $i++) {
		$r .= $ch[mt_rand(0, $len-1)];
	}
	return $r;
}#end function



/*Преобразует массив в строку для GET/POST*/
function getRequestString($data=array()){
	$str = '';
	foreach($data as $key=>$value){
		$str .= ($str == '' ? '': '&') . $key . '=' . rawurlencode($value);
	}
	return $str;
}#end function


/*
 * Функция декодирует строку, упакованную GZIP
 */
if (!function_exists('gzdecode')) {
	function gzdecode ($data) {
		$flags = ord(substr($data, 3, 1));
		$headerlen = 10;
		$extralen = 0;
		$filenamelen = 0;
		if ($flags & 4) {
			$extralen = unpack('v' ,substr($data, 10, 2));
			$extralen = $extralen[1];
			$headerlen += 2 + $extralen;
		}
		if ($flags & 8)  $headerlen = strpos($data, chr(0), $headerlen) + 1; // Имя файла
		if ($flags & 16) $headerlen = strpos($data, chr(0), $headerlen) + 1; // Комментарий
		if ($flags & 2)  $headerlen += 2; // CRC по окончании данных
		$unpacked = gzinflate(substr($data, $headerlen));
		if ($unpacked === FALSE) $unpacked = $data;
		return $unpacked;
	}
}#end function




/*==============================================================================================
Функции работы с массивами
==============================================================================================*/

/*
 * Выбирает из массива $record часть полей, указанных в массиве $fields и возвращает их
 */
function arrayCustomFields($record, $fields){
	if(!is_array($record)) return false;
	$result = array();
	foreach($fields as $field){
		$result[$field] = (!isset($record[$field]) ? null : $record[$field]);
	}
	return $result;
}#end function


/*
 * Выбирает из массива $records значение поля $field и возвращает линейный массив
 */
function arrayFromField($field, $records, $uniques=false){
	if(!is_array($records)) return array();
	$result = array();
	if(!$uniques){
		foreach($records as $record){
			$result[] = (!isset($record[$field]) ? null : $record[$field]);
		}
	}else{
		foreach($records as $record){
			if(!isset($record[$field])) continue;
			if(in_array($record[$field], $result)) continue;
			$result[] = $record[$field];
		}
	}
	return $result;
}#end function


/*==============================================================================================
TeraWurfl Device detect
==============================================================================================*/


function detectOS($user_agent){
	$os_array = array(
		'windows nt 6.3'	=> 'ms_windows_8.1',
		'windows nt 6.2'	=> 'ms_windows_8',
		'windows nt 6.1'	=> 'ms_windows_7',
		'windows nt 6.0'	=> 'ms_windows_vista',
		'windows nt 5.2'	=> 'ms_windows_server_2003_xp_x64',
		'windows nt 5.1'	=> 'ms_windows_xp',
		'windows xp'		=> 'ms_windows_xp',
		'windows nt 5.0'	=> 'ms_windows_2000',
		'windows me'		=> 'ms_windows_me',
		'win98'				=> 'ms_windows_98',
		'win95'				=> 'ms_windows_95',
		'win16'				=> 'ms_windows_3.11',
		'macintosh'			=> 'mac_os_x',
		'mac os x'			=> 'mac_os_x',
		'mac_powerpc'		=> 'mac_os_9',
		'linux'				=> 'linux',
		'ubuntu'			=> 'ubuntu',
		'iphone'			=> 'iphone',
		'ipod'				=> 'ipod',
		'ipad'				=> 'ipad',
		'android'			=> 'android',
		'blackberry'		=> 'blackberry'
	);
	foreach ($os_array as $regex => $value){ 
		if (strpos($user_agent, $regex)!==false){
			return $value;
		}
	}
	return 'undefined';
}


function detectBrowser($user_agent){
	$result = 'undefined';
	$browser_array  =   array(
		'msie'		=> 'msie',
		'firefox'	=> 'firefox',
		'safari'	=> 'safari',
		'chrome'	=> 'chrome',
		'opera'		=> 'opera',
		'netscape'	=> 'netscape',
		'maxthon'	=> 'maxthon',
		'konqueror'	=> 'konqueror'
	);
	foreach ($browser_array as $regex => $value) { 
		if (strpos($user_agent, $regex)!==false){
			$result=$value;
		}
	}
	return $result;
}



/*
 * Ищет вхождение $needle в массиве $haystack
 */
function findArrayKey($needle, $haystack){ 
	if(!is_array($haystack))return false;
	foreach ($haystack as $key => &$value){
		if($needle == $key){
			return array(
				'parent'=> $haystack,
				'key'	=> $key,
				'value'	=> $value
			);
		}
		if(is_array($value)){
			if(($r=findArrayKey($needle, $value))!==false) return $r;
		}
	}
	return false; 
}#end function


/*
 * Преобразует объект в массив
 */
function stdToArray($obj){
	$rc = (array)$obj;
	foreach($rc as $key => &$field){
		if(is_object($field)||is_array($field)) $field = stdToArray($field);
	}
	return $rc;
}




/*
 * Подготавливает строку для использования в качкстве REGEXP паттерна
 */
function getPattern($str){
	$str = str_replace(array("\r","\n","\t"),array("","",""),$str);
	//All regex special chars (according to arkani at iol dot pt below):
	// \ ^ . $ | ( ) [ ]
	// * + ? { } ,       
	$patterns = array('/\"/','/\'/','/\//','/\\\\/', '/\^/', '/\./', '/\$/', '/\|/', '/\(/', '/\)/', '/\[/', '/\]/', '/\*/', '/\+/', '/\?/', '/\{/', '/\}/', '/\,/', '/\&/', '/\</', '/\>/', '/\:/', '/\;/', '/\=/', '/\-/', '/\_/');
	$replace = array('\"','\'','\/', '\\','\^', '\.', '\$', '\|', '\(', '\)', '\[', '\]', '\*', '\+', '\?', '\{', '\}', '\,', '\&', '\<', '\>', '\:', '\;', '\=', '\-', '\_');
	$arr = explode('(.*?)',$str);
	$out='';
	foreach($arr as $s){
		$out .= (strlen($out)>0 ? '(.*?)' : '') . preg_replace($patterns, $replace, $s);
	}

	return $out;
}#end function


/*
 * Возвращает прописью число
 */
function morph($n, $f1, $f2, $f5) {
	$n = abs(intval($n)) % 100;
	if ($n>10 && $n<20) return $f5;
	$n = $n % 10;
	if ($n>1 && $n<5) return $f2;
	if ($n==1) return $f1;
	return $f5;
}
function num2str($num, $is_money=true) {

	$nul='ноль';
	$ten=array(
		array('','один','два','три','четыре','пять','шесть','семь', 'восемь','девять'),
		array('','одна','две','три','четыре','пять','шесть','семь', 'восемь','девять'),
	);
	$a20=array('десять','одиннадцать','двенадцать','тринадцать','четырнадцать' ,'пятнадцать','шестнадцать','семнадцать','восемнадцать','девятнадцать');
	$tens=array(2=>'двадцать','тридцать','сорок','пятьдесят','шестьдесят','семьдесят' ,'восемьдесят','девяносто');
	$hundred=array('','сто','двести','триста','четыреста','пятьсот','шестьсот', 'семьсот','восемьсот','девятьсот');
	$unit=array( // Units
		($is_money ? array('копейка' ,'копейки' ,'копеек',	 1) : array('','','',1)),
		($is_money ? array('рубль'   ,'рубля'   ,'рублей'    ,0) : array('','','', 0)),
		array('тысяча'  ,'тысячи'  ,'тысяч'     ,1),
		array('миллион' ,'миллиона','миллионов' ,0),
		array('миллиард','милиарда','миллиардов',0)
	);
	//
	list($rub,$kop) = explode('.',sprintf("%015.2f", floatval($num)));
	$out = array();
	if (intval($rub)>0) {
		foreach(str_split($rub,3) as $uk=>$v) { // by 3 symbols
			if (!intval($v)) continue;
			$uk = sizeof($unit)-$uk-1; // unit key
			$gender = $unit[$uk][3];
			list($i1,$i2,$i3) = array_map('intval',str_split($v,1));
			// mega-logic
			$out[] = $hundred[$i1]; # 1xx-9xx
			if ($i2>1) $out[]= $tens[$i2].' '.$ten[$gender][$i3]; # 20-99
			else $out[]= $i2>0 ? $a20[$i3] : $ten[$gender][$i3]; # 10-19 | 1-9
			// units without rub & kop
			if ($uk>1) $out[]= morph($v,$unit[$uk][0],$unit[$uk][1],$unit[$uk][2]);
		} //foreach
	}
	else $out[] = $nul;
	$out[] = morph(intval($rub), $unit[1][0],$unit[1][1],$unit[1][2]); // rub
	if($is_money) $out[] = $kop.' '.morph($kop,$unit[0][0],$unit[0][1],$unit[0][2]); // kop
	return trim(preg_replace('/ {2,}/', ' ', join(' ',$out)));
}




function rus2translit($string, $space='-'){
	return str_replace(' ',$space,trim(preg_replace('/\s\s+/', ' ',preg_replace('~[^-a-z0-9_]+~u', ' ',strtolower(strtr($string, array(
		'а' => 'a',   'б' => 'b',   'в' => 'v',
		'г' => 'g',   'д' => 'd',   'е' => 'e',
		'ё' => 'e',   'ж' => 'zh',  'з' => 'z',
		'и' => 'i',   'й' => 'y',   'к' => 'k',
		'л' => 'l',   'м' => 'm',   'н' => 'n',
		'о' => 'o',   'п' => 'p',   'р' => 'r',
		'с' => 's',   'т' => 't',   'у' => 'u',
		'ф' => 'f',   'х' => 'h',   'ц' => 'c',
		'ч' => 'ch',  'ш' => 'sh',  'щ' => 'sch',
		'ь' => '\'',  'ы' => 'y',   'ъ' => '\'',
		'э' => 'e',   'ю' => 'yu',  'я' => 'ya',
		
		'А' => 'A',   'Б' => 'B',   'В' => 'V',
		'Г' => 'G',   'Д' => 'D',   'Е' => 'E',
		'Ё' => 'E',   'Ж' => 'Zh',  'З' => 'Z',
		'И' => 'I',   'Й' => 'Y',   'К' => 'K',
		'Л' => 'L',   'М' => 'M',   'Н' => 'N',
		'О' => 'O',   'П' => 'P',   'Р' => 'R',
		'С' => 'S',   'Т' => 'T',   'У' => 'U',
		'Ф' => 'F',   'Х' => 'H',   'Ц' => 'C',
		'Ч' => 'Ch',  'Ш' => 'Sh',  'Щ' => 'Sch',
		'Ь' => '\'',  'Ы' => 'Y',   'Ъ' => '\'',
		'Э' => 'E',   'Ю' => 'Yu',  'Я' => 'Ya',
	)))))));
}


?>