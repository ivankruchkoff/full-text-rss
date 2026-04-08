<?php
/**
 * Humble HTTP Agent extension for SimplePie_File
 * 
 * This class is designed to extend and override SimplePie_File
 * in order to prevent duplicate HTTP requests being sent out.
 * The idea is to initialise an instance of Humble HTTP Agent
 * and attach it, to a static class variable, of this class.
 * SimplePie will then automatically initialise this class
 * 
 * @date 2011-02-28
 */

class SimplePie_HumbleHttpAgent extends SimplePie_File
{
	protected static $agent;
	public static $last_url = null;
	public static $last_effective_url = null;
	public static $last_status_code = null;
	public static $last_headers = null;
	public static $last_body_sample = null;
	public static $last_error = null;
	var $url;
	var $useragent;
	var $success = true;
	var $headers = array();
	var $body;
	var $status_code;
	var $redirects = 0;
	var $error;
	var $method = SIMPLEPIE_FILE_SOURCE_NONE;

	public static function set_agent(HumbleHttpAgent $agent) {
		self::$agent = $agent;
		self::$last_url = null;
		self::$last_effective_url = null;
		self::$last_status_code = null;
		self::$last_headers = null;
		self::$last_body_sample = null;
		self::$last_error = null;
	}
	
	public function __construct($url, $timeout = 10, $redirects = 5, $headers = null, $useragent = null, $force_fsockopen = false) {
		if (class_exists('idna_convert'))
		{
			$idn = new idna_convert();
			$parsed = SimplePie_Misc::parse_url($url);
			$url = SimplePie_Misc::compress_parse_url($parsed['scheme'], $idn->encode($parsed['authority']), $parsed['path'], $parsed['query'], $parsed['fragment']);
		}
		$this->url = $url;
		$this->useragent = $useragent;
		self::$last_url = $url;
		self::$last_effective_url = null;
		self::$last_status_code = null;
		self::$last_headers = null;
		self::$last_body_sample = null;
		self::$last_error = null;
		if (preg_match('/^http(s)?:\/\//i', $url))
		{
			if (!is_array($headers))
			{
				$headers = array();
			}
			$this->method = SIMPLEPIE_FILE_SOURCE_REMOTE | SIMPLEPIE_FILE_SOURCE_CURL;
			$headers2 = array();
			foreach ($headers as $key => $value) {
				$headers2[] = "$key: $value";
			}
			//TODO: allow for HTTP headers
			// curl_setopt($fp, CURLOPT_HTTPHEADER, $headers2);

			$response = self::$agent->get($url);
			
			if ($response === false || !isset($response['status_code'])) {
				$this->error = 'failed to fetch URL';
				$this->success = false;
				self::$last_error = $this->error;
			} else {
				self::$last_effective_url = isset($response['effective_url']) ? $response['effective_url'] : $url;
				self::$last_status_code = isset($response['status_code']) ? $response['status_code'] : null;
				self::$last_headers = isset($response['headers']) ? $response['headers'] : null;
				self::$last_body_sample = isset($response['body']) ? substr($response['body'], 0, 1000) : null;
				// The extra lines at the end are there to satisfy SimplePie's HTTP parser.
				// The class expects a full HTTP message, whereas we're giving it only
				// headers - the new lines indicate the start of the body.
				$parser = new SimplePie_HTTP_Parser($response['headers']."\r\n\r\n");
				if ($parser->parse()) {
					$this->headers = $parser->headers;
					//$this->body = $parser->body;
					$this->body = $response['body'];
					$this->status_code = $parser->status_code;
				}
			}
		}
		else
		{
			$this->error = 'invalid URL';
			$this->success = false;
			self::$last_error = $this->error;
		}
	}
}