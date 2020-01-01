<?php
function __wl($n)
{
	$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
	$lang = isset($config["lang"])? $config["lang"]: "ua";
	if( file_exists('lang.ini')){
		$l = parse_ini_file('lang.ini',1);
		$l = isset($l[$lang][$n])? $l[$lang][$n]: $n;
		if($n[0]=='e' and $n[1]=='r' and is_int((int)$n[2])){
			return "<center><h1>".substr($n, 2)."</h1><p>$l</p><hr><sub>Lion Web Server</sub></center>";
		}
		return $l;
	}
};