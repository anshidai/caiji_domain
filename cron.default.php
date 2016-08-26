<?php 

ini_set('display_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
//error_reporting(E_ALL);

header("Content-type:text/html; charset=utf-8");
set_time_limit(0);

require 'function.php';
require 'simple_html_dom.php';
require 'cj.class.php'; //引入采集扩展文件
require 'mysql.class.php';
require 'http_proxy.php';

define('ROOTPATH', substr(__FILE__, 0 , -8));
define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );

//数据库配置
$config['db'][1]['dbhost'] = 'localhost';        
$config['db'][1]['dbuser'] = '';        
$config['db'][1]['dbpw'] = '';        
$config['db'][1]['dbcharset'] = 'utf8';        
$config['db'][1]['pconnect'] = 0;            
$config['db'][1]['dbname'] = '';         
$config['db'][1]['tablepre'] = '';
$config['db']['slave'] = array();

$db = & DB::object('db_mysql');
$db->set_config($config['db']);
$db->connect();


$proxy_ip = array();

require 'aizhan.php';
$Aizhan =  new Aizhan();
$Aizhan->allowProxy = true;
$Aizhan->delay = 2000;
$Aizhan->total = 1000;

//采集代理ip
if($Aizhan->allowProxy) {
    $proxy_ip_1 = cj_xicidaili_ip(2);
    $proxy_ip_2 = cj_66ip_ip(10);

    $proxy_ip = array_merge($proxy_ip_1, $proxy_ip_2);
    $proxy_ip = filter_proxy_ips($proxy_ip, 2);
    $Aizhan->writeLog("采集代理ip完成 记录总数: ".count($proxy_ip));
}
$Aizhan->proxyIP = $proxy_ip;

$Aizhan->init();

/*
require 'chinaz.php';
$Ahinaz = new Ahinaz();
$Ahinaz->proxyIP = $proxy_ip;
$Ahinaz->allowProxy = false;
$Ahinaz->init();
*/

