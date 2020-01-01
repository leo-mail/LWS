<?php
namespace LWS;
require_once "lang.php";
class HTTPResponse
{
    public $status;                 // HTTP status code
    public $status_msg;             // HTTP status message
    public $headers;                // associative array of HTTP headers (name => list of values)
    
    public $content = '';           // response body, as string (optional)    
    public $stream = null;          // response body (or headers+body if prepend_headers is false) as stream
    
    public $prepend_headers = true; // true if the HTTP status/headers should be added to the response
                                    // false if the HTTP status/headers are sent in $stream
    
    public $buffer = '';            // buffer of HTTP response waiting to be written to client socket
    public $bytes_written = 0;      // count of bytes written to client socket

    function __construct($status = 200, $content = '', $headers = null, $status_msg = null)
    {
        $this->status = $status;
        $this->status_msg = $status_msg;

        if (is_resource($content))
        {
            $this->stream = $content;
        }
        else        
        {
            $this->content = $content;
        }
        $this->headers = $headers ?: [];
    }        
   
    function eof()
    {
        return !strlen($this->buffer) && $this->stream_eof();
    }
    
    function stream_eof()
    {
        return !$this->stream || feof($this->stream);
    }    
        
    static function render_status($status, $status_msg = null)
    {
        // Per RFC2616 6.1.1 we pass on a status message from the provider if
        // provided, otherwise we use the standard message for that code.
        if (empty($status_msg)) 
        {
            $status_msg = __wl('er'.$status);
        }
        return "HTTP/1.1 $status $status_msg\r\n";
    }
    
    static function render_headers($headers)
    {
        ob_start();        
        foreach ($headers as $name => $values)
        {
            if (is_array($values))
            {
                foreach ($values as $value)
                {                
                    echo "$name: $value\r\n";
                }
            }
            else
            {
                echo "$name: $values\r\n";
            }
        }
        echo "\r\n";        
        return ob_get_clean();
    }
            
    function render()
    {
        $headers =& $this->headers;

        if (!isset($headers['Content-Length']))
        {
            $headers['Content-Length'] = [$this->get_content_length()];
        }        
        
        return  static::render_status($this->status, $this->status_msg).
                static::render_headers($headers).
                $this->content;
    }
    
    function get_content_length()
    {
        // only valid if content is supplied as a string
        return strlen($this->content);
    }    
    
}
