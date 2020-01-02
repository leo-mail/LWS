<?php
include(__DIR__."/linux_vars.php");
require_once 'FireLion/DataStructures/array.php';
require_once 'FireLion/DataStructures/xml.php';

$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
if( !is_dir("Localization") )
	mkdir("Localization");
if( !file_exists("Localization/{$config["locale"]}.ini") )
	{
	if( $config["locale"] == "ua" )
		$config["locale"] == "uk_UA";
	if( $config["locale"] == "en" )
		$config["locale"] == "en_GB";
	if( $config["locale"] == "ru" )
		$config["locale"] == "ru_UA";
	file_put_contents("Localization/{$config["locale"]}.ini", file_get_contents("https://raw.githubusercontent.com/leo-mail/LWS-lang-{$config["locale"]}/master/{$config["locale"]}.ini"));
	}
ini_set('date.timezone', $config["Timezone"]);
require_once 'httpserver.php';
require_once 'ServerLoop.php';
require_once 'ServerHost.php';
require_once 'lang.php';
define("OS", (substr_count(php_uname(), "Windows")> 0)?"Win":"Lin");
$MainServers = ( new LWServers($config) );

$MainServers->run();
