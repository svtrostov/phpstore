<?php
/*==================================================================================================
Title	: Декларации
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
define('BEGIN_WORK_TIME',microtime(true));

/*
 * Проверка текущей версии PHP
 * Данное программное обеспечение требует версии PHP не ниже 5.4.0
 * Это связано с использованием технологии Trait, введенной в PHP с версии 5.4.0
 -------------------------------------------------------------------------------

 if(version_compare(PHP_VERSION, '5.4.0', '<')){
 	die('FATAL ERROR: you using PHP less than 5.4.0. This software is incompatible with this version. Please upgrade your PHP.');
 }
/*-------------------------------------------------------------------------------*/

#------------------------------------------------------------
#Локализация
setLocale(LC_ALL, 'ru_RU.UTF-8');
setLocale(LC_NUMERIC, 'C');
mb_internal_encoding('UTF-8');
mt_srand();

#------------------------------------------------------------
#Временная зона
if(function_exists('date_default_timezone_set')) date_default_timezone_set('Europe/Moscow');

/*
 * Константы приложения
 */
if(!defined('APP_INSIDE')) define('APP_INSIDE',	true);		#Признак, указывающий на корректный запуск приложения
if(!defined('APP_DEBUG')) define('APP_DEBUG',	true);		#Признак включения режима отладки

define('DBTIMESTAMP',floor(BEGIN_WORK_TIME));
define('DBDATE',date("Y-m-d",DBTIMESTAMP));
define('DBTIME',date("H:i:s",DBTIMESTAMP));
define('DBNOW',DBDATE.' '.DBTIME);
$GLOBALS['TIMEINFO'] = getdate(DBTIMESTAMP);
if($GLOBALS['TIMEINFO']['wday']==0) $GLOBALS['TIMEINFO']['wday']=7;
define('DBYEAR',$GLOBALS['TIMEINFO']['year']);
define('DBMONTH',$GLOBALS['TIMEINFO']['mon']);
define('DBDAY',$GLOBALS['TIMEINFO']['mday']);
define('DBHOUR',$GLOBALS['TIMEINFO']['hours']);
define('DAYOFWEEK',$GLOBALS['TIMEINFO']['wday']);
define('DAYOFYEAR',$GLOBALS['TIMEINFO']['yday']);
define('LN',"\n");


/*
 * Пути к папкам
 */
define('DIR_SERVER', 	DIR_ROOT.'/server');				#Путь к корневой папке серверной части, в файле .htaccess в папке должно быть прописано: deny from all
define('DIR_TEMP',		DIR_ROOT.'/server/tmp');			#Путь к папке для хранения временных файлов, на папке должен стоять chmod 777
define('DIR_CLASSES',	DIR_ROOT.'/server/classes');		#Путь к папке с файлами классов
define('DIR_CRON',		DIR_ROOT.'/server/cron');			#Путь к папке с файлами скриптов, выполняемых по CRON'у
define('DIR_FUNCTIONS', DIR_ROOT.'/server/functions');		#Путь к папке с файлами функций
define('DIR_SCRIPTS', 	DIR_ROOT.'/server/scripts');		#Путь к папке с файлами произвольных скриптов
define('DIR_LOGS',		DIR_ROOT.'/server/logs');			#Путь к папке с LOG файлами, на папке должен стоять chmod 777
define('DIR_MODULES',	DIR_ROOT.'/server/modules');		#Путь к папке с модулями
define('DIR_CONFIG',	DIR_ROOT.'/server/config');			#Путь к папке с файлами настроек
define('DIR_LANGUAGES',	DIR_ROOT.'/server/languages');		#Путь к папке с файлами языковых локализаций
define('DIR_GEOIP',		DIR_ROOT.'/server/geoip');			#Путь к папке с файлами DAT файлов maxmind geoip
define('DIR_DATA',		DIR_ROOT.'/server/data');			#Путь к папке с файлами данных

define('DIR_CLIENT',	DIR_ROOT.'/client');				#Путь к папке с клиентскими файлами

define('GEOIP_IP',		DIR_GEOIP.'/GeoIP.dat');			#DAT файл maxmind geoip - IP адреса
define('GEOIP_ISP',		DIR_GEOIP.'/GeoIPISP.dat');			#DAT файл maxmind geoip - ISP

define('DIR_TERAWURFL',	DIR_ROOT.'/server/TeraWurfl');		#Путь к папке с файлами Tera Wurfl (Определение устройств по UserAgent)

?>
