<?php
include(__DIR__."/linux_vars.php");
require_once 'FireLion/DataStructures/array.php';
require_once 'FireLion/DataStructures/xml.php';
$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
ini_set('date.timezone', $config["Timezone"]);
ini_set('php.short_open_tag', 'On');
$Path = json_decode($argv[count($argv)-1]);
print_r($argv);
require_once 'httpserver.php';
require_once 'ServerLoop.php';
require_once 'lang.php';
$Class = "LWS\\" . $Path[0];
$Server = ( new LWLoop( $Class, FireLion\Data\Structures\Arr\SubLevelGet($config["Servers"], $Path) )	);
$Server->run();
