<?php
/*==================================================================================================
Title	: Стартовый скрипт
Автор	: Stanislav V. Tretyakov (svtrostov@yandex.ru)
==================================================================================================*/
define('DIR_ROOT', realpath(dirname(__FILE__)));
require_once(DIR_ROOT.'/defines.php');

/*
 * Загрузка основных функций
 */
require_once(DIR_FUNCTIONS.'/utils.functions.php');
require_once(DIR_FUNCTIONS.'/loader.functions.php');
loader_init();

Page::_build();

?>
