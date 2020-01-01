<?php
namespace LWS;
require_once "lang.php";
require_once "httprequest.php";
require_once "httpresponse.php";
require_once "cgistream.php";

class HTTP
{
	public static $inited = false;
    /* 
     * The following public properties can be passed as options to the constructor: 
     */    
    public $ip = 'UNKNOWN';               	// IP address to listen on
    public $port = 80;                      // TCP port number to listen on   
	//p/php-aws	
	public $isProxy = false;
    public $cfg = [];						// Configuration
	public $cgi_env = [];              		// associative array of additional environment variables to pass to php-cgi
    public $server_id = 'Lion Web Server';  // identifier string to use in 'Server' header of HTTP response
    public $php_cgi = 'php-cgi';            // Path to php-cgi, if not in the PATH       
	public $locallink = false;				// Local links postfix
	public $parent;							// Parent object (LWServerLoop)
    public $start_time;						// Start time
    /* 
     * Internal map of active client socket resource IDs to HTTPRequest objects
     */    
    private $requests = [/* socket_id => HTTPRequest */];    
    
    /* 
     * Internal map of stream resource IDs to HTTPResponse objects 
     * (only includes HTTPResponse objects with an associated stream)
     */        
    private $responses = [/* stream_id => HTTPResponse */];    
    private $sock = 0;
    function __construct($cfg)
    {
		if(!isset($cfg["port"]) or !isset($cfg["ip"]) or !isset($cfg["location"]) or !isset($cfg["location"]) or !isset($cfg["language"]))
		{
			die("HTTP error: bad config file! Aborting...\n");
		}
		if(!isset($cfg["dir_listening"]))
			$cfg["dir_listening"] = false;
		
		if(!isset($cfg["language"]))
			$cfg["language"] = "en";
		if(!isset($cfg["check_php"]))
			$cfg["check_php"] = "on";
		
		$www = isset($cfg["location"])? realpath($cfg["location"]): realpath('site');
		if(!is_dir($www) or !is_readable($www)){
			die("HTTP error: site location[homepath] is unaccessable! Aborting...\n");
		}
		if( !is_dir("logs") ) mkdir("logs");
		foreach($cfg as $a=>$value)
		{
			$this->$a = is_array($value)?$value:trim($value);
		}
		if( substr_count($this->ip,".") == 0 )
			$this->ip = "[{$this->ip}]";
		
		$this->cfg = $cfg;
    }
    
    /*  
     * Subclasses should override to route the current request to either a static file or PHP script
     * and return a HTTPResponse object. This function should call get_static_response() or
     * get_php_response(), as applicable.
     */
    function route_request($request)
    {
		if( substr($request->query_string, 0, 3) == "lwc" &&  $request->remote_addr == $this->ip )
			{
				return $this->text_response( 200, $this->parent->command($this, substr($request->query_string,4)) );
			}
			if( $request->query_string == "lws" )
				return $this->get_php_response($request, "Panel/panel.php");
			if( substr($request->query_string, 0, 3) == "lws" )
				return $this->get_php_response($request, "Panel/" . substr($request->query_string, 4));
		$uri = $request->uri;
        $doc_root = isset($this->cfg["location"])? realpath($this->cfg["location"]): realpath('site');
        
        if (preg_match('#/$#', $uri))
        {  
			if(file_exists("$doc_root$uri{$this->cfg['index']}")){
				$uri .= "$uri{$this->cfg['index']}";
			}else if(file_exists($doc_root.$uri."/index.php")){
				$uri .= "index.php";
			}
        }

        if (preg_match('#\.php$#', $uri))
        {
            return $this->get_php_response($request, "$doc_root$uri");
        }
        else
        {	
			$json = json_decode(file_get_contents("$doc_root/../alias.json"),true);
			if( isSet($json[substr($uri,1)]) )
				{
					return $this->get_php_response($request, "$doc_root/".$json[substr($uri,1)]);
				}
			if($this->locallink !== false)
			{
				if( file_exists("$doc_root$uri".$this->locallink) and $request->remote_addr == $this->ip )
					return $this->get_php_response($request, "$doc_root$uri".$this->locallink);
			}
			if($this->cfg['check_php'] and !file_exists("$doc_root$uri"))
			{
				foreach(['php', 'php7', 'php56', 'php5.6', 'php5', 'phtml', 'php3', 'phpt'] as $n){
				if($this->locallink !== false)
				if( file_exists("$doc_root$uri.$n".$this->locallink) and $request->remote_addr == $this->ip )
					return $this->get_php_response($request, "$doc_root$uri.$n".$this->locallink);
				if( file_exists("$doc_root$uri.$n") )
					return $this->get_php_response($request, "$doc_root$uri.$n");
				}
			}
            return $this->get_static_response($request, "$doc_root$uri", $uri);
        }
    }    

    /*  
     * Subclasses can override to get started event
     */
    function listening()
    {
        echo "\r\n".sprintf(__wl('listening_msg'), (($this->ip=='0.0.0.0')? '127.0.0.1': $this->ip), $this->port, $this->port)."\r\n";
    }    
    
    /*
     * Subclasses could override to disallow other characters in path names
     */
    function is_allowed_uri($request)
    {
		
		return
		!(!$this->isProxy && strtolower(substr($request->request_line,0,7))=="connect")
			&&
			$request->uri[0] == '/'                   // all URIs should start with a /
            && strpos($request->uri, '..') === false     // prevent paths from escaping document root
            && !preg_match('#/\.#', $request->uri)      // disallow dotfiles
			&& ((substr($request->query_string,0,5)=="/?lwc")?$request->remote_addr == $this->ip:true)
			&& ((substr($request->query_string,0,5)=="/?lws")?($request->remote_addr == $this->ip||$this->ForeignAccess):true);
    }
    
    /*
     * Subclasses could override to output a log entry in a particular format
     */    
    function get_log_line($request)
    {
        $response = $request->response;
        $time = strftime("%H:%M:%S");
        
        // http://www.w3.org/Daemon/User/Config/Logging.html#common-logfile-format
        return "{$request->remote_addr} - - [$time] \"{$request->request_line}\" {$response->status} {$response->bytes_written}\n";
    }      

    /*
     * Subclasses could override for logging or other other post-request events
     */    
    function request_done($request)
    {
		$log = $this->get_log_line($request);
		if($this->cfg['allow_long_logs'])
					file_put_contents("logs/Server {$this->ip};{$this->port} [{$this->start_time}].log", "\r\n$log", FILE_APPEND);

	   echo $log;
    }      
    
    function bind_error($errno, $errstr)
    {
		if($this->cfg['allow_long_logs']) file_put_contents("logs/Server {$this->ip};{$this->port} [{$this->start_time}].log", sprintf(__wl('start_er1').' - - [', $this->port, $errstr).date("d.m.y h;i;s").']', FILE_APPEND);
        error_log(sprintf(__wl('start_er1'), $this->port, $errstr));    
    }
    
	function Init()
	{
		if(!self::$inited)
		{
			self::$inited = true;
			stream_wrapper_register("cgi", "LWS\CGIStream");
			set_time_limit(0);
		}
        $this->sock = stream_socket_server("tcp://{$this->ip}:{$this->port}", $errno, $errstr);
        if($this->port < 0 )
        {
            $this->bind_error(0, __wl('start_er2'));
            exit();
        }
        if (!$this->sock)
        {            
            $this->bind_error($errno, $errstr);
            exit();
        }
        
        stream_set_blocking($this->sock, 0);
		$this->start_time = date("d.m.y h;i;s");
		
		if( strlen(trim(str_replace(range(0, 9), '', $this->port))) > 0 )
		{
            $this->bind_error(0, __wl('start_er3'));
            exit();
		} else {
		// send startup event
        $this->listening();	
		file_put_contents("logs/Server {$this->ip};{$this->port} [{$this->start_time}].log", __wl("server_started")." - - [".date("d.m.y h:i:s")."]");
		}
	}
	
	function Live()	
    {        
		// provide some required/useful environment variables even if 'E' is not in variables_order
		
        $env_keys = ['HOME','OS','Path','PATHEXT','SystemRoot','TEMP','TMP'];
        foreach ($env_keys as $key)
        {
            $_ENV[$key] = getenv($key);
        }
		
            $read	= [];
            $write	= [];
            foreach ($this->requests as $id => $request)
            {            
                if (!$request->is_read_complete())
                {
                    $read[] = $request->socket;
                }
                else 
                {
                    $response = $request->response;
                    
                    $buffer_len = strlen($response->buffer);
                    if ($buffer_len)
                    {
                        $write[] = $request->socket;
                    }
                    
                    if ($buffer_len < 20000 && !$response->stream_eof())
                    {
                        $read[] = $response->stream;
                    }
                }                
            }            
            $read[] = $this->sock;       
            $except = null;
            
            if (stream_select($read, $write, $except, null) < 1)
				return;
			
            if (in_array($this->sock, $read)) // new client connection
            {
                $client = stream_socket_accept($this->sock);
                $this->requests[(int)$client] = new HTTPRequest($client);
                
                $key = array_search($this->sock, $read);
                unset($read[$key]);
            }
            
            foreach ($read as $stream)
            {
                if (isset($this->responses[(int)$stream]))
                {
                    $this->read_response($stream);
                }
                else
                {
                    $this->read_socket($stream);
                }
            }
            
            foreach ($write as $client)
            {
                $this->write_socket($client);
            }        
    }
    
    function write_socket($client)
    {    
        $request = $this->requests[(int)$client];
        $response = $request->response;
        $response_buf =& $response->buffer;     
        
        $len = @fwrite($client, $response_buf);   
                
        if ($len === false)
        {
            $this->end_request($request);
        }
        else
        {
            $response->bytes_written += $len;
            $response_buf = substr($response_buf, $len);
            
            if ($response->eof())
            {                
                $this->request_done($request);
            
                if ($request->get_header('Connection') === 'close' || $request->http_version !== 'HTTP/1.1')
                {
                    $this->end_request($request);
                }
                else // HTTP Keep-Alive: expect another request on same client socket
                {           
                    $request->cleanup();                
                    $this->end_response($response);
                    $this->requests[(int)$client] = new HTTPRequest($client);
                }
            }
        }
    }
    
    function read_response($stream)
    {    
        $response = $this->responses[(int)$stream];
        
        $data = @fread($stream, 30000);

        if ($data !== false)
        {    
            if (isset($response->buffer[0]))
            {
                $response->buffer .= $data;
            }
            else
            {                
                $response->buffer = $data;
            }
        }
    }
    
    function read_socket($client)
    {
        $request = $this->requests[(int)$client];
        $data = @fread($client, 30000);
                
        if ($data === false || $data == '')
        {
            $this->end_request($request);
        }
        else
        {        
            $request->add_data($data);
            
            if ($request->is_read_complete())
            {
                $this->read_request_complete($request);
            }    
        }
    }
    
    function read_request_complete($request)
    {
        $uri = $request->uri;
        
        if (!$this->is_allowed_uri($request))
        {
			$response = $this->text_response(403, __wl("er403")); 	
        }
        else
        {        
            $response = $this->route_request($request);        
        }
        
        if ($response->prepend_headers)
        {
            $response->buffer = $response->render();
        }            
                
        if ($response->stream)
        {
            $this->responses[(int)$response->stream] = $response;
        }
        
        $request->set_response($response);
    }
    
    function end_request($request)
    {
        $request->cleanup();
        @fclose($request->socket);
        unset($this->requests[(int)$request->socket]);           
        $request->socket = null;
        
        if ($request->response)
        {
            $this->end_response($request->response);
            $request->response = null;
        }
    }        
    
    function end_response($response)
    {
        if ($response->stream)
        {        
            @fclose($response->stream);
            unset($this->responses[(int)$response->stream]);    
            $response->stream = null;
        }
    }
    
    /*
     * Returns a generic HTTPResponse object for this server.
     */
    function response($status = 200, $content = '', $headers = null, $status_msg = null)
    {
        $response = new HTTPResponse($status, $content, $headers, $status_msg);
		$response->headers['Keep-Alive'] = "timeout=15, max=2";
        $response->headers['Server'] = $this->server_id;                
        return $response;        
    }
      
    function text_response($status, $content)
    {
        $response = $this->response($status, $content);
        $response->headers['Content-Type'] = 'text/html';
        return $response;
    }
      
    /*
     * Returns a HTTPResponse object for the static file at $local_path.
     */      
    function get_static_response($request, $local_path, $uri='')
    {   
        if (is_file($local_path))
        {        
            $headers =
				[
                    'Content-Type' => static::get_mime_type($local_path),
                    'Cache-Control' => "max-age=8640000",
                    'Accept-Ranges' => 'bytes',
				];
        
            $file_size = filesize($local_path);
        
            if ($request->method === 'HEAD')
            {
                $headers['Content-Length'] = $file_size;
                return $this->response(200, '', $headers);
            }
            else if ($request->method == 'GET')
            {
                $range = $request->get_header('range');        
                                
                $file = fopen($local_path, 'rb');

                if ($range && preg_match('#^bytes=(\d+)\-(\d*)$#', $range, $match))
                {        
                    $start = (int)$match[1];
                    $end = (int)$match[2] ?: ($file_size - 1);
                                   
                    if ($end >= $file_size || $end < $start || $start < 0 || $start >= $file_size)
                    {
						$response = $this->text_response(416,  __wl("er416"));	
                    }
                    
                    $len = $end - $start + 1;
                    
                    $headers['Content-Length'] = $len;
                    $headers['Content-Range'] = "bytes $start-$end/$file_size";
                    
                    fseek($file, $start);
                    
                    if ($end == $file_size - 1)
                    {
                        return $this->response(206, $file, $headers);
                    }
                    else
                    {
                        $chunk = fread($file, $len);
                        return $this->response(206, $chunk, $headers);
                    }
                }
                else
                {
                    $headers['Content-Length'] = $file_size;
                    // hopefully file size doesn't change before we're done writing the file            
                    $response = $this->response(200, $file, $headers);
                }    
            }
            else
            {
				return $this->text_response(405,  __wl("er405"));
            }
        
            return $response;
        }
        else if (is_dir($local_path))
        {
			if($this->cfg["dir_listening"]){
					 $buffa = <<<EOD
					 <style>

  h1 {
    border-bottom: 1px solid #c0c0c0;
    margin-bottom: 10px;
    padding-bottom: 10px;
    white-space: nowrap;
  }

  table {
    border-collapse: collapse;
  }

  tr.header {
    font-weight: bold;
  }

  td.detailsColumn {
    -webkit-padding-start: 2em;
    text-align: end;
    white-space: nowrap;
  }

  a.icon {
    -webkit-padding-start: 1.5em;
    text-decoration: none;
  }

  a.icon:hover {
    text-decoration: underline;
  }

  a.file {
    background : url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAIAAACQkWg2AAAABnRSTlMAAAAAAABupgeRAAABHUlEQVR42o2RMW7DIBiF3498iHRJD5JKHurL+CRVBp+i2T16tTynF2gO0KSb5ZrBBl4HHDBuK/WXACH4eO9/CAAAbdvijzLGNE1TVZXfZuHg6XCAQESAZXbOKaXO57eiKG6ft9PrKQIkCQqFoIiQFBGlFIB5nvM8t9aOX2Nd18oDzjnPgCDpn/BH4zh2XZdlWVmWiUK4IgCBoFMUz9eP6zRN75cLgEQhcmTQIbl72O0f9865qLAAsURAAgKBJKEtgLXWvyjLuFsThCSstb8rBCaAQhDYWgIZ7myM+TUBjDHrHlZcbMYYk34cN0YSLcgS+wL0fe9TXDMbY33fR2AYBvyQ8L0Gk8MwREBrTfKe4TpTzwhArXWi8HI84h/1DfwI5mhxJamFAAAAAElFTkSuQmCC ") left top no-repeat;
  }

  a.dir {
    background : url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAd5JREFUeNqMU79rFUEQ/vbuodFEEkzAImBpkUabFP4ldpaJhZXYm/RiZWsv/hkWFglBUyTIgyAIIfgIRjHv3r39MePM7N3LcbxAFvZ2b2bn22/mm3XMjF+HL3YW7q28YSIw8mBKoBihhhgCsoORot9d3/ywg3YowMXwNde/PzGnk2vn6PitrT+/PGeNaecg4+qNY3D43vy16A5wDDd4Aqg/ngmrjl/GoN0U5V1QquHQG3q+TPDVhVwyBffcmQGJmSVfyZk7R3SngI4JKfwDJ2+05zIg8gbiereTZRHhJ5KCMOwDFLjhoBTn2g0ghagfKeIYJDPFyibJVBtTREwq60SpYvh5++PpwatHsxSm9QRLSQpEVSd7/TYJUb49TX7gztpjjEffnoVw66+Ytovs14Yp7HaKmUXeX9rKUoMoLNW3srqI5fWn8JejrVkK0QcrkFLOgS39yoKUQe292WJ1guUHG8K2o8K00oO1BTvXoW4yasclUTgZYJY9aFNfAThX5CZRmczAV52oAPoupHhWRIUUAOoyUIlYVaAa/VbLbyiZUiyFbjQFNwiZQSGl4IDy9sO5Wrty0QLKhdZPxmgGcDo8ejn+c/6eiK9poz15Kw7Dr/vN/z6W7q++091/AQYA5mZ8GYJ9K0AAAAAASUVORK5CYII= ") left top no-repeat;
  }

  a.up {
    background : url("data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABAAAAAQCAYAAAAf8/9hAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAmlJREFUeNpsU0toU0EUPfPysx/tTxuDH9SCWhUDooIbd7oRUUTMouqi2iIoCO6lceHWhegy4EJFinWjrlQUpVm0IIoFpVDEIthm0dpikpf3ZuZ6Z94nrXhhMjM3c8895977BBHB2PznK8WPtDgyWH5q77cPH8PpdXuhpQT4ifR9u5sfJb1bmw6VivahATDrxcRZ2njfoaMv+2j7mLDn93MPiNRMvGbL18L9IpF8h9/TN+EYkMffSiOXJ5+hkD+PdqcLpICWHOHc2CC+LEyA/K+cKQMnlQHJX8wqYG3MAJy88Wa4OLDvEqAEOpJd0LxHIMdHBziowSwVlF8D6QaicK01krw/JynwcKoEwZczewroTvZirlKJs5CqQ5CG8pb57FnJUA0LYCXMX5fibd+p8LWDDemcPZbzQyjvH+Ki1TlIciElA7ghwLKV4kRZstt2sANWRjYTAGzuP2hXZFpJ/GsxgGJ0ox1aoFWsDXyyxqCs26+ydmagFN/rRjymJ1898bzGzmQE0HCZpmk5A0RFIv8Pn0WYPsiu6t/Rsj6PauVTwffTSzGAGZhUG2F06hEc9ibS7OPMNp6ErYFlKavo7MkhmTqCxZ/jwzGA9Hx82H2BZSw1NTN9Gx8ycHkajU/7M+jInsDC7DiaEmo1bNl1AMr9ASFgqVu9MCTIzoGUimXVAnnaN0PdBBDCCYbEtMk6wkpQwIG0sn0PQIUF4GsTwLSIFKNqF6DVrQq+IWVrQDxAYQC/1SsYOI4pOxKZrfifiUSbDUisif7XlpGIPufXd/uvdvZm760M0no1FZcnrzUdjw7au3vu/BVgAFLXeuTxhTXVAAAAAElFTkSuQmCC ") left top no-repeat;
  }

  html[dir=rtl] a {
    background-position-x: right;
  }

  #listingParsingErrorBox {
    border: 1px solid black;
    background: #fae691;
    padding: 10px;
    display: none;
  }
</style>
EOD;
					 $buffa .= "<center><h1>Dir listening</h1><hr>";
					 $dir = $local_path;
					 $dh  = opendir($dir);
					 while (false !== ($filename = readdir($dh))) {
						if($filename!=='.'&&$filename!=='..')
							$files[] = $filename;
					 }

					sort($files);
					foreach($files as $k=>$v){ $buffa .= "<br><a href='".$uri.'/'.$v."'>".$v."</a>"; }
					$buffa .= "<hr><sub>Lion Web Server</sub></center>";
				    return $this->text_response(200, $buffa);
			}else{
				return $this->text_response(403,  __wl("er403"));
			}
        }
        else
        {
			return $this->text_response(404,  __wl("er404"));
        }    
    }        
    
    /*
     * Executes the PHP script in $script_filename using php-cgi, and returns 
     * a HTTPResponse object. $cgi_env_override can be set to an associative array 
     * to set or override any environment variables in the CGI process (e.g. PATH_INFO).
     */
    function get_php_response($request, $script_filename, $cgi_env_override = null, $redir = false)
    {            
        if (!is_file($script_filename))
        {
			return $this->text_response(404,  __wl("er404"));
        }    
        
        $content_length = $request->get_header('Content-Length');

        // see http://www.faqs.org/rfcs/rfc3875.html
        $cgi_env =
			[
				'QUERY_STRING' => $request->query_string,
				'REQUEST_METHOD' => $request->method,
				'REQUEST_URI' => $request->request_uri,
				'REDIRECT_STATUS' => $redir? 301: 200,
				'SCRIPT_FILENAME' => $script_filename,            
				'SCRIPT_NAME' => $request->uri,
				'SERVER_NAME' => $request->get_header('Host'),
				'SERVER_PORT' => $this->port,
				'SERVER_PROTOCOL' => 'HTTP/1.1',
				'SERVER_SOFTWARE' => $this->server_id,
				'CONTENT_TYPE' => $request->get_header('Content-Type'),
				'CONTENT_LENGTH' => $content_length,            
				'REMOTE_ADDR' => $request->remote_addr,
			];                
        
        foreach ($request->headers as $name => $values)
        {        
            $name = str_replace('-','_', $name);
            $name = strtoupper($name);
            $cgi_env["HTTP_$name"] = $values[0];
        }
        
        if ($cgi_env_override)
        {
            foreach ($cgi_env_override as $name => $value)
            {
                $cgi_env[$name] = $value;
            }
        }
                
        $response = $this->response();                    
                
        $context = stream_context_create(
		[
            'cgi' =>
			[
                'env' => array_merge($_ENV, $this->cgi_env, $cgi_env),
                'stdin' => $request->content_stream,
                'server' => $this,
                'response' => $response,
            ]
        ]);
        
        $cgi_stream = fopen("cgi://{$this->php_cgi}", 'rb', false, $context);
        
        if ($cgi_stream)
        {              
            $response->stream = $cgi_stream;
            $response->prepend_headers = false;
            
            return $response;
        }
        else
        {
		return $this->text_response(500,  __wl("er500"));
        }
    }         

    static function parse_headers($headers_str)
    {
        $headers_arr = explode("\r\n", $headers_str);
                
        $headers = [];
        foreach ($headers_arr as $header_str)
        {
            $header_arr = explode(": ", $header_str, 2);
            if (sizeof($header_arr) == 2)
            {
                $header_name = $header_arr[0];
                $value = $header_arr[1];
                
                if (!isset($headers[$header_name]))
                {
                    $headers[$header_name] = [$value];
                }
                else
                {
                    $headers[$header_name][] = $value;
                }
            }
        }                
        return $headers;
    }                          
        
    static function get_mime_type($filename)
    {
        $pathinfo = pathinfo($filename);
        $extension = strtolower($pathinfo['extension']);
    
        return @static::$mime_types[$extension];
    }        
    
    /*
     * List of mime types for common file extensions
     * (c) Tyler Hall http://code.google.com/p/php-aws/
     * released under MIT License
     */
    static $mime_types =
	[
		"323" => "text/h323", "acx" => "application/internet-property-stream", "ai" => "application/postscript", "aif" => "audio/x-aiff", "aifc" => "audio/x-aiff", "aiff" => "audio/x-aiff", 'apk' => "application/vnd.android.package-archive",
        "asf" => "video/x-ms-asf", "asr" => "video/x-ms-asf", "asx" => "video/x-ms-asf", "au" => "audio/basic", "avi" => "video/quicktime", "axs" => "application/olescript", "bas" => "text/plain", "bcpio" => "application/x-bcpio", "bin" => "application/octet-stream", "bmp" => "image/bmp",
        "c" => "text/plain", "cat" => "application/vnd.ms-pkiseccat", "cdf" => "application/x-cdf", "cer" => "application/x-x509-ca-cert", "class" => "application/octet-stream", "clp" => "application/x-msclip", "cmx" => "image/x-cmx", "cod" => "image/cis-cod", "cpio" => "application/x-cpio", "crd" => "application/x-mscardfile",
        "crl" => "application/pkix-crl", "crt" => "application/x-x509-ca-cert", "csh" => "application/x-csh", "css" => "text/css", "dcr" => "application/x-director", "der" => "application/x-x509-ca-cert", "dir" => "application/x-director", "dll" => "application/x-msdownload", "dms" => "application/octet-stream", "doc" => "application/msword",
        "dot" => "application/msword", "dvi" => "application/x-dvi", "dxr" => "application/x-director", "eps" => "application/postscript", "etx" => "text/x-setext", "evy" => "application/envoy", "exe" => "application/octet-stream", "fif" => "application/fractals", "flr" => "x-world/x-vrml", "gif" => "image/gif",
        "gtar" => "application/x-gtar", "gz" => "application/x-gzip", "h" => "text/plain", "hdf" => "application/x-hdf", "hlp" => "application/winhlp", "hqx" => "application/mac-binhex40", "hta" => "application/hta", "htc" => "text/x-component", "htm" => "text/html", "html" => "text/html",
        "htt" => "text/webviewhtml", "ico" => "image/x-icon", "ief" => "image/ief", "iii" => "application/x-iphone", "ins" => "application/x-internet-signup", "isp" => "application/x-internet-signup", "jfif" => "image/pipeg", "jpe" => "image/jpeg", "jpeg" => "image/jpeg", "jpg" => "image/jpeg",
        "js" => "application/x-javascript", "latex" => "application/x-latex", "lha" => "application/octet-stream", "lsf" => "video/x-la-asf", "lsx" => "video/x-la-asf", "lzh" => "application/octet-stream", "m13" => "application/x-msmediaview", "m14" => "application/x-msmediaview", "m3u" => "audio/x-mpegurl", "man" => "application/x-troff-man",
        "mdb" => "application/x-msaccess", "me" => "application/x-troff-me", "mht" => "message/rfc822", "mhtml" => "message/rfc822", "mid" => "audio/mid", "mny" => "application/x-msmoney", "mov" => "video/quicktime", "movie" => "video/x-sgi-movie", "mp2" => "video/mpeg", "mp3" => "audio/mpeg",
        'mp4' => 'video/mp4',
        "mpa" => "video/mpeg", "mpe" => "video/mpeg", "mpeg" => "video/mpeg", "mpg" => "video/mpeg", "mpp" => "application/vnd.ms-project", "mpv2" => "video/mpeg", "ms" => "application/x-troff-ms", "mvb" => "application/x-msmediaview", "nws" => "message/rfc822", "oda" => "application/oda",
        'ogg' => 'video/ogg',
        'ogv' => 'video/ogg',
        "p10" => "application/pkcs10", "p12" => "application/x-pkcs12", "p7b" => "application/x-pkcs7-certificates", "p7c" => "application/x-pkcs7-mime", "p7m" => "application/x-pkcs7-mime", "p7r" => "application/x-pkcs7-certreqresp", "p7s" => "application/x-pkcs7-signature", "pbm" => "image/x-portable-bitmap", "pdf" => "application/pdf", "pfx" => "application/x-pkcs12",
        "pgm" => "image/x-portable-graymap", "pko" => "application/ynd.ms-pkipko", "pma" => "application/x-perfmon", "pmc" => "application/x-perfmon", "pml" => "application/x-perfmon", "pmr" => "application/x-perfmon", "pmw" => "application/x-perfmon", "png" => "image/png", "pnm" => "image/x-portable-anymap", "pot" => "application/vnd.ms-powerpoint", "ppm" => "image/x-portable-pixmap",
        "pps" => "application/vnd.ms-powerpoint", "ppt" => "application/vnd.ms-powerpoint", "prf" => "application/pics-rules", "ps" => "application/postscript", "pub" => "application/x-mspublisher", "qt" => "video/quicktime", "ra" => "audio/x-pn-realaudio", "ram" => "audio/x-pn-realaudio", "ras" => "image/x-cmu-raster", "rgb" => "image/x-rgb",
        "rmi" => "audio/mid", "roff" => "application/x-troff", "rtf" => "application/rtf", "rtx" => "text/richtext", "scd" => "application/x-msschedule", "sct" => "text/scriptlet", "setpay" => "application/set-payment-initiation", "setreg" => "application/set-registration-initiation", "sh" => "application/x-sh", "shar" => "application/x-shar",
        "sit" => "application/x-stuffit", "snd" => "audio/basic", "spc" => "application/x-pkcs7-certificates", "spl" => "application/futuresplash", "src" => "application/x-wais-source", "sst" => "application/vnd.ms-pkicertstore", "stl" => "application/vnd.ms-pkistl", "stm" => "text/html", "svg" => "image/svg+xml", "sv4cpio" => "application/x-sv4cpio",
        "sv4crc" => "application/x-sv4crc", "t" => "application/x-troff", "tar" => "application/x-tar", "tcl" => "application/x-tcl", "tex" => "application/x-tex", "texi" => "application/x-texinfo", "texinfo" => "application/x-texinfo", "tgz" => "application/x-compressed", "tif" => "image/tiff", "tiff" => "image/tiff",
        "tr" => "application/x-troff", "trm" => "application/x-msterminal", "tsv" => "text/tab-separated-values", "txt" => "text/plain", "uls" => "text/iuls", "ustar" => "application/x-ustar", "vcf" => "text/x-vcard", "vrml" => "x-world/x-vrml", "wav" => "audio/x-wav", "wcm" => "application/vnd.ms-works",
        "wdb" => "application/vnd.ms-works",
        'webm' => 'video/webm',
        "wks" => "application/vnd.ms-works", "wmf" => "application/x-msmetafile", "wps" => "application/vnd.ms-works", "wri" => "application/x-mswrite", "wrl" => "x-world/x-vrml", "wrz" => "x-world/x-vrml", "xaf" => "x-world/x-vrml", "xbm" => "image/x-xbitmap", "xla" => "application/vnd.ms-excel",
        "xlc" => "application/vnd.ms-excel", "xlm" => "application/vnd.ms-excel", "xls" => "application/vnd.ms-excel", "xlt" => "application/vnd.ms-excel", "xlw" => "application/vnd.ms-excel", "xof" => "x-world/x-vrml", "xpm" => "image/x-xpixmap", "xwd" => "image/x-xwindowdump", "z" => "application/x-compress", "zip" => "application/zip"
	];    
}
