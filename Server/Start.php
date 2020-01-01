<?php
include(__DIR__."/linux_vars.php");
require_once 'FireLion/DataStructures/array.php';
require_once 'FireLion/DataStructures/xml.php';

$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
ini_set('date.timezone', $config["Timezone"]);
require_once 'httpserver.php';
require_once 'ServerLoop.php';
require_once 'ServerHost.php';
require_once 'lang.php';
define("OS", (substr_count(php_uname(), "Windows")> 0)?"Win":"Lin");
$MainServers = ( new LWServers($config) );

$MainServers->run();
