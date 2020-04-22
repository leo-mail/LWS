<?php
require_once 'FireLion/Data/Structures/array.php';
require_once 'FireLion/Data/Structures/xml.php';

$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
if( !is_dir("Localization") )
	mkdir("Localization");
	$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
	$locale = ["ua"=>"uk_UA", "en"=>"en_GB", "ru"=>"ru_UA", "ukr"=>"uk_UA"];
	$locale = isSet($config["locale"])? 
            ( isSet($locale[$config["locale"]])? $locale[$config["locale"]]: $config["locale"] ): "uk_UA";
if( !file_exists("Localization/{$locale}.ini") )
	{
	file_put_contents("Localization/{$locale}.ini", file_get_contents("https://raw.githubusercontent.com/leo-mail/LWS-uil/master/webint/{$locale}.ini"));
	}
ini_set('date.timezone', $config["Timezone"]);
require_once 'httpserver.php';
require_once 'ServerLoop.php';
require_once 'ServerHost.php';
require_once 'lang.php';

define("OS", (substr_count(php_uname(), "Windows")> 0)?"Win":"Lin");

$MainServers = ( new LWServers($config) );
$MainServers->run();
if(OS=="Lin")
    exec('cd $PWD'); //To change the title in non xTerm-like terminals
EXIT();
