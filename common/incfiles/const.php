<?php
ob_start();
set_time_limit(1800);
error_reporting(E_ALL ^ E_NOTICE);
date_default_timezone_set('PRC');
define('APPNAME', 'jtbc_');
define('ASSETSPATH', 'common/assets');
define('BASEDIR', '');
define('CACHEDIR', 'cache');
define('CHARSET', 'utf-8');
define('CONSOLEDIR', 'console');
define('COOKIESPATH', '/');
define('DB', 'MySQL');
define('DB_HOST', 'zhengqibang.cn');
// define('DB_HOST', 'localhost');
define('DB_USERNAME', 'jtbc_com');
// define('DB_USERNAME', 'jtbc_com');
// define('DB_PASSWORD', 'Kxl11520');
define('DB_PASSWORD', 'AJKA5Fh5ws');
define('DB_DATABASE', 'jtbc_com');
// define('DB_TABLE_PREFIX', '');
define('DB_TABLE_PREFIX', 'jtbc_');
define('DB_STRUCTURE_CACHE', false);
define('LANGUAGE', 'zh-cn');
define('PATH_INFO_MODE', false);
define('SEPARATOR', ' - ');
define('SITESTATUS', 100);
define('STARTTIME', microtime(true));
define('THEME', 'default');
define('TEMPLATE', 'default');
define('VERSION', '3.0.2.0');
define('WEBKEY', 'o2z1dg1kh0bc0zk7');
define('XMLSFX', '.jtbc');
?>