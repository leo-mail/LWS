<?php
require_once "cgistream.php";

class LWLoop
{
	private $c;
	public $restart;
	const _BREAK_ = 0;
	const _CONTINUE_ = 1;
	const _OUTPUT_ = 2;
	
	public function __construct($Server,$cfg)
	{
		$this->server = new $Server($cfg);
		$this->server->parent = $this;
		$this->server->Init();
		for($i=self::_BREAK_;$i<=self::_OUTPUT_;$i++)
		{
			$this->c[$i] = false;
		}
	}
	
	public function Continue()
	{
		$this->c[self::_CONTINUE__] = true;
	}
	
	public function Break()
	{
		$this->c[self::_BREAK_] = true;
	}
	
	public function ReCast()
	{
		$this->Break();
		$this->Live();
	}
	
	function Run()
	{
		while( true )
		{
			if( $this->c[SELF::_CONTINUE_] == true)
			{
				$this->c[SELF::_CONTINUE_] = false;
				continue;
			}
				$this->server->live();
			if( $this->c[SELF::_OUTPUT_] !== false )
			{
				if($this->c[SELF::_OUTPUT_][0]>0)
				{
					$this->c[SELF::_OUTPUT_][0]--;
				} else {
					$c = false;
					echo $this->c[SELF::_OUTPUT_][1];
					$this->c[SELF::_OUTPUT_] = false;
				}
			}
				if( $this->c[SELF::_BREAK_] == true )
					break;
		}
	}
	function Command($a, $name)
	{
		if( strtolower($name) == "recast" )
		{
			return $this->Recast();
		} elseif(substr($name,0,1) == "~") {
			$this->c[self::_OUTPUT_] = [2,$name];
			if(substr($name,1)=="restart")
				return "Restarting...";
			return "SUCCESS";
		} elseif( is_file(__DIR__."/Panel/curl/$name.php") ) {
			$_REQUEST["@"] = $a;
			$result = include(__DIR__."/Panel/curl/$name.php");
			unset( $_REQUEST["@"] );
		} else $result = "404";
		return $result;
	}
}
