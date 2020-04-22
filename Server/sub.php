<?php
require_once 'FireLion/Data/Structures/array.php';
require_once 'FireLion/Data/Structures/xml.php';
$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
ini_set('date.timezone', $config["Timezone"]);
ini_set('php.short_open_tag', 'On');
$Path = json_decode($argv[count($argv)-1]);

require_once 'httpserver.php';
require_once 'ServerLoop.php';
require_once 'lang.php';
$Class = "LWS\\" . $Path[0];
$Server = ( new LWLoop( $Class, FireLion\Data\Structures\Arr\SubLevelGet($config["Servers"], $Path) )	);
$Server->run();
