<?php
class LWServers
{
	private $Servers = [];
	private $Streams = [];
	private $schedule = [];
	
	private $console_codes = 
	[
	"<red>",
	"</red>",
	];
	private $console_colors = [];
	
	private $ConsoleIn;
	private $ConsoleOut;
	private $cfg;
	private $_updconf = true;
	public function __construct( $Config )
	{
		$this->cfg = $Config;
	}
	private function StartServer($Type, $Path, $id=false)
	{
		if($id==false)
			$id = count($this->Servers);
		$pipes = [];
		$this->ServerInfo[$id] = new ServerInfo($Type, $id, $Path);
		
		if(OS == "Win")
		{
		$this->ServerInfo[$id]->stream_buffer =  dirname(dirname(PHP_BINARY)) . DIRECTORY_SEPARATOR . 'temp' . DIRECTORY_SEPARATOR . 'stream-' . $this->ServerInfo[$id]->ip . '-' . $this->ServerInfo[$id]->port . '.b';
		file_put_contents($this->ServerInfo[$id]->stream_buffer, "");
		$fh = fopen($this->ServerInfo[$id]->stream_buffer, "w");
		$this->Servers[$id] = proc_open(
							'"'. PHP_BINARY .'" "' . realpath(dirname(dirname(PHP_BINARY)) . '/Server/sub.php').'" -c "' . realpath(dirname(PHP_BINARY) . '/php.ini').'" "'.json_encode($Path).'"',
							[
							0 => STDIN,
							1 => $fh,
							2 => STDERR
							],
							$pipes,
							NULL,
							NULL,
							[
							'binary_pipes' => false,
							'bypass_shell' => true
							]
							 
							);
		$this->Streams[$id] = [0,$this->ServerInfo[$id]->stream_buffer,array_merge($pipes,[$fh])];
		} else {
			$this->Servers[$id] = proc_open(
							//'"'. PHP_BINARY .'" "' . realpath(dirname(dirname(PHP_BINARY)) . '/Server/sub.php').'" -c "' . realpath(dirname(PHP_BINARY) . '/php.ini').'" "'.base64_encode(json_encode($Path)).'"',
							'sudo php ' . "'Server/sub.php' '".json_encode($Path)."'",
							[
							0 => STDIN,
							1 => ["pipe", "w"],
							2 => STDERR
							],
							$pipes,
							NULL,
							NULL,
							[
							'binary_pipes' => false,
							'bypass_shell' => true
							]
							 
							);
			$this->Streams[$id] = $pipes;
			stream_set_blocking($pipes[1], 0);
		}
	}
	private function StopServer($id,$notify=true)
	{
		foreach(((OS === "Win")?$this->Streams[$id][2]:$this->Streams[$id]) as $p)
			fclose($p);
		proc_terminate( $this->Servers[$id] );
		if($notify)
		{
			$ProcStatus = PHP_EOL . "\t\t{$this->ServerInfo[$id]->type}@{$this->ServerInfo[$id]->ip}:{$this->ServerInfo[$id]->port}".PHP_EOL."Server stopped.".PHP_EOL;
			$this->ConsoleOut($ProcStatus);
			$this->Out($ProcStatus);
		}
		$this->ServerInfo[$id]->Stop();
		unset($this->Servers[$id], $this->Streams[$id]);
	}
	private function StopAllServers()
	{
		foreach( $this->Servers as $id=>$v )
			$this->StopServer($id);
		exit();
	}
	private function StartServers()
	{
		foreach( $this->cfg["Servers"] as $Server=>$sc )
		{
			if( isset($sc[0]) )
			{
				foreach($sc as $id=>$s)
				{
					$this->StartServer($Server, [$Server, $id]);
				}
			} else {
				$this->StartServer($Server, [$Server]);
			}
			return;
		}
	}
	public function RestartServer($id)
	{
		$ProcStatus = PHP_EOL."\t\t{$this->ServerInfo[$id]->type}@{$this->ServerInfo[$id]->ip}:{$this->ServerInfo[$id]->port}".PHP_EOL."Restarting server...".PHP_EOL;
		echo $ProcStatus;
		$this->ConsoleOut($ProcStatus);
		$this->StopServer($id,false);
		$this->StartServer($this->ServerInfo[$id]->type, $this->ServerInfo[$id]->path, $id);
	}
	public function RestartEveryServer()
	{
		foreach( $this->Servers as $id=>$v )
			$this->RestartServer($id);
	}
	public function UpdConfiguration()
	{
		$this->Config = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"));
		$this->_updconf = true;
	}
	public function GetCli()
	{
		switch($this->cfg["CLI_Mode"])
		{
			default:
				return "CircleDef";
			case 1:
				return "CircleCLI";
			case 2:
				return "CircleProc";
		}
	}
	public function Run()
	{
		$this->console_colors = [];
		${0} = false;
		if(!file_exists("temp/server;;in"))
			{
				${0} = true;
				file_put_contents("temp/server;;in", "");
				file_put_contents("temp/server;;out", "");
				$this->ConsoleIn	= fopen("temp/server;;in", "r");
				$this->ConsoleOut	= fopen("temp/server;;out", "w");
				$this->StartServers();
			} else {
				$this->_updconf = false;
				$this->ConsoleIn	= fopen("temp/server;;in", "w");
				$this->ConsoleOut	= fopen("temp/server;;out", "r");
				$c = "ConsoleHost";
				if( substr_count(php_uname(),"Windows")<=0 )
				{
					//l
					return;
				}
			}
		set_time_limit(0);
		while( true )
		{
			if( $this->_updconf === true )
			{
				$this->_updconf = false;
				$c = $this->GetCli();
			}
			$this->$c();
		}
		fclose($this->ConsoleIn);
		fclose($this->ConsoleOut);
		if(${0})
			{
				${0} = 0;
				foreach([realpath("temp/server;;in"),realpath("temp/server;;out")] as $file)
				while(file_exists($file))
				{
					${0}++;
					unlink($file);
					if(${0} == 8) return;
				}
			}
	}
	
	private function readbuf(&$f,&$res)
	{
		$res = "";
		if( OS == "Win" )
		{
			$res = substr(file_get_contents($f[1]),$f[0]);
			$f[0] += strlen($res);
		} else {
			while( ($r = fread($f[1], 280)) !== "" )
						{
							fseek($f[1], strlen($r), SEEK_CUR);
							$res .= $r;
						}
		}
	}
	public function CheckALive($id)
	{
		$status = proc_get_status($this->Servers[$id]);
		if(!($status!==false&&$status["running"]==1))
		{
			echo PHP_EOL."\t\t{$this->ServerInfo[$id]->type}@{$this->ServerInfo[$id]->ip}:{$this->ServerInfo[$id]->port}".PHP_EOL.
				"ERROR: server crashed, restarting...".PHP_EOL;
				$this->RestartServer($id);
				sleep(8);
		}
	}
	public function CircleDef()
	{
		foreach( $this->Streams as $id=>&$f )
		{
			$this->CheckALive($id);
            if(($res = $this->ConsoleIn()) !== "")
			{
				$this->Command(json_decode($res));
			}
			$res = "";
			$this->readbuf($f,$res);
			if(trim($res)!=="")
			{
				if( substr($res, 0, 1) == "~" )
				{
					$this->Command(array_merge(self::ParseCommand(substr($res,1)), [$id]));
				} else {
					if(substr($res,0,strlen(PHP_EOL))!==PHP_EOL)
						$res = PHP_EOL.$res;
					$this->out("\t\t{$this->ServerInfo[$id]->type}@{$this->ServerInfo[$id]->ip}:{$this->ServerInfo[$id]->port}".$res);
				}
				if(isset($this->schedule[$id]))
				{
					$this->schedule[$id][0]--;
					if( $this->schedule[$id][0] <= 0)
					{
						$this->{$this->schedule[$id][1]}(...$this->schedule[$id][2]);
						unset($this->schedule[$id]);
					}
				}
			}
		}
	}
	public function CircleCli()
	{
		$this->Command(self::ParseCommand(readline("#LWS:")),false);
	}
	public function CircleProc()
	{
		foreach( $this->Streams as $id=>&$f )
		{
			$this->CheckALive($id);
			if(($res = $this->ConsoleIn()) !== "")
			{
				$this->Command(json_decode($res));
			}
			$res = "";
			$this->readbuf($f,$res);
			if(trim($res)!=="")
			{
				if( substr($res, 0, 1) == "~" )
					$this->command(array_merge(self::ParseCommand(substr($res,1)), [$id]));
				
				if(isset($this->schedule[$id]))
				{
					$this->schedule[$id][0]--;
					if( $this->schedule[$id][0] <= 0)
					{
						$this->{$this->schedule[$id][1]}(...$this->schedule[$id][2]);
						unset($this->schedule[$id]);
					}
				}
			}
		}
	}
	public function ConsoleOut($response)
	{
		fwrite($this->ConsoleOut,  is_array($response)?json_encode($response):json_encode([$response]));
	}
	public function ConsoleIn()
	{
		$result = "";
		$res = ".";
		while( $res !== false && $res !== "" )
		{
			$res = fread($this->ConsoleIn, 4096);
			fseek($this->ConsoleIn, strlen($res), SEEK_CUR);
			$result .= $res;
		}
		return $result;
	}
	public function Out($text)
	{
		$EOLen = strlen(PHP_EOL);
		$TextLen = strlen($text);
		$TextCentered = "";
		$text = ((substr($text,0,$EOLen)==PHP_EOL)?"":PHP_EOL).$text.((substr($text,$TextLen-$EOLen)==PHP_EOL)?"":PHP_EOL);
		echo $this->FormatColor($text);
	}
	public function FormatColor($text)
	{
		return str_replace($this->console_codes, $this->console_colors, $text);
	}
	public function ConsoleHost()
	{
		$input = readline("#LWS:");
		$this->CommandTo(self::ParseCommand($input),true);
	}
	public static function ParseCommand($c)
	{
		return strpos($c, " ")!==false?explode(" ",$c):[$c];
	}
	public function Command($c, $wait=true)
	{
		switch(strtolower($c[0]))
		{
			case "restart":
			case "stop":
			{
				$this->ServerCommand($c[1], $c[0], true);
			} break;
			
			case "exit":
			case "halt":
			{
				$this->StopAllServers();
			} break;
			case "restartevery":
			{
				$this->RestartEveryServer();
			} break;
			
			case "updconf":
			{
				$this->UpdConfiguration();
			} break;
		}
	}
	public function CommandTo($c,$WaitResponse=true)
	{
		$CHName = strtolower($c[0]);
		if($CHName == "stop")
			$CHName = "halt";
		
		switch($CHName)
		{
            case "exit":
            {
                exit();
            } break;
			case "c":
			{
				$this->Out("\color=red[hello, world!");
			} break;
			case "hello-word":
			{
				$this->Out("Hello, world!");
			} break;
			default:
			{
				fwrite($this->ConsoleIn, json_encode($c));
				if($WaitResponse)
				{
					$result = "";
					if( ($res = fread($this->ConsoleOut, 280)) == false)
					return;
						fseek($this->ConsoleOut, strlen($res), SEEK_CUR);
						$result .= $res;
						while( ($res = fread($this->ConsoleOut, 280)) !== "" )
						{
							fseek($this->ConsoleOut, strlen($res), SEEK_CUR);
							$result .= $res;
						}
					$this->CommandReceivedResult($c[0], json_decode($result,true) );
				}
			}
		}
	}
	public function CommandReceivedResult($c, $response)
	{
		switch($c)
		{
			case "list":
			{
				$this->out("Server List:");
				foreach($response as $server)
				{
					$this->out($server);
				}
			}
			case "restartevery":
			{
				$this->out("Log:");
				$this->out(print_r($response));
			}
			case "halt":
			{
				$this->out($response[0]);
			}
		}
	}
	private function ServerCommand($id, $n, $wait)
	{
		if($wait)
			$this->Schedule($id, "{$n}Server", 1, [$id]);
		else
			$this->{"{$n}Server"}($id);
	}
	public function Schedule($ServerId, $method, $loops, $args=[])
	{
		$this->schedule[$ServerId] = [$loops, $method, $args];
	}
}

class ServerInfo{
	public $class;
	public $type;
	public $path;
	public $StartTime;
	public $StopTime;
	private $id;
	public $stream_buffer;
	public $_c;
	private $_LaunchTime;
	
	public function __construct($ServerType, $id, $ServerPath)
	{
		$this->id = $id;
		$this->path = $ServerPath;
		$this->class = $ServerType;
		$this->type = $ServerType;
		$this->LaunchTime = date("d.m.y h;i;s");
		$this->_LaunchTime = date_create();
		$this->StartTime = "0.0.0 0:0:0";
		$this->StopTime	 = "0.0.0 0:0:0";
		$this->_c = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"))["Servers"];
		$this->_c = FireLion\Data\Structures\Arr\SubLevelGet($this->_c, $ServerPath);
		if(!isSet($this->_c["AutoRestart"]))
			$this->_c["AutoRestart"] = true;
		if(!isSet($this->_c["RestartOnCfg"]))
			$this->_c["RestartOnCfg"] = false;
	}
	public function __get($name)
	{
		if($name == "ListeningTime")
		{
			return date_format( date_create() - $this->_LaunchTime, "d.m.y h;i;s");
		} else {
			if($this->_c["RestartOnCfg"])
				$this->ConfigChanged(false);
			
			return isset($this->_c[$name])?$this->_c[$name]: NULL;
		}
	}
	public function __set($name,$v)
	{
		return true;
	}
	public function Start()
	{
		$this->StartTime = date("d.m.y h;i;s");
	}
	public function Stop()
	{
		$this->StopTime = date("d.m.y h;i;s");
	}
	public function ConfigChanged($a=true) //?
	{
		$cfg = FireLion\Data\Structures\XML\ToArray(file_get_contents("config.xml"))["Servers"];
		$cfg = FireLion\Data\Structures\Arr\SubLevelGet($cfg, $this->path);
		$res = [];
		foreach($cfg as $i=>$el)
		{
			if($this->_c[$i] === $el)
				$res[$i] = $el;
		}
		if(isset($res["RestartOnCfg"])||($this->_c["RestartOnCfg"]&&count($res)>0))
		{
			$this->_c = $cfg;
			return true;
		}
		return false;
	}
}
