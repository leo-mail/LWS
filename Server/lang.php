<?php
function __wl($n)
{
	$config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
	$locale = isset($locale)? $config["locale"]: "uk_UA";
	if( !file_exists("Localization/{$locale}.ini") )
	{
	if( $locale == "ua" )
		$locale == "uk_UA";
	if( $locale == "en" )
		$locale == "en_GB";
	if( $locale == "ru" )
		$locale == "ru_UA";
	}
	if( file_exists("Localization/{$locale}.ini") )
	{
		$l = parse_ini_file("Localization/{$locale}.ini",true);
		$l = isset($l[$n])? $l[$n]: $n;
		
		if($n[0]=='e' and $n[1]=='r' and (int)$n[2] > -1)
		{
			return "<center><h1>".substr($n, 2)."</h1><p>$l</p><hr><sub>Lion Web Server</sub></center>";
		}
		return $l;
	}
};
