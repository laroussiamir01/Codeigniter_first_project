<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Queue Library for handling asynchronous job processing
 * Uses Redis as the backend for job storage
 * No external dependencies - uses PHP native socket functions
 */
class Queue
{
	private $socket;
	private $host;
	private $port;
	private $password;
	private $queue_key = 'codeigniter:queue';
	private $connected = FALSE;

	public function __construct()
	{
		// Load queue configuration
		$ci = &get_instance();
		$ci->config->load('queue');

		// Get Redis configuration
		$this->host = $ci->config->item('redis_host');
		$this->port = $ci->config->item('redis_port');
		$this->password = $ci->config->item('redis_password');

		// Connect to Redis
		$this->_connect();
	}

	/**
	 * Connect to Redis using PHP sockets
	 */
	private function _connect()
	{
		try {
			$this->socket = @fsockopen($this->host, $this->port, $errno, $errstr, 5);
			
			if (!$this->socket) {
				throw new Exception("Failed to connect to Redis at {$this->host}:{$this->port} - $errstr ($errno)");
			}

			stream_set_timeout($this->socket, 5);

			// Authenticate if password is set
			if (!empty($this->password)) {
				$this->_send_command('AUTH', array($this->password));
			}

			// Test connection with PING
			$response = $this->_send_command('PING');
			if (strpos($response, 'PONG') === FALSE) {
				throw new Exception('Redis PING failed');
			}

			$this->connected = TRUE;
			log_message('info', 'Queue: Connected to Redis at ' . $this->host . ':' . $this->port);
		} catch (Exception $e) {
			log_message('error', 'Queue: Redis connection failed - ' . $e->getMessage());
			$this->connected = FALSE;
		}
	}

	/**
	 * Send a command to Redis and get response
	 */
	private function _send_command($command, $arguments = array())
	{
		if (!$this->connected || !$this->socket) {
			return FALSE;
		}

		$parts = array_merge(array($command), $arguments);
		$request = '*' . count($parts) . "\r\n";
		
		foreach ($parts as $part) {
			$request .= '$' . strlen($part) . "\r\n" . $part . "\r\n";
		}

		// Send the command
		if (fwrite($this->socket, $request) === FALSE) {
			$this->connected = FALSE;
			return FALSE;
		}

		// Read the response
		return $this->_read_response();
	}

	/**
	 * Read response from Redis
	 */
	private function _read_response()
	{
		if (!$this->socket) {
			return FALSE;
		}

		$line = fgets($this->socket, 512);
		
		if ($line === FALSE) {
			return FALSE;
		}

		$type = $line[0];
		$data = substr(trim($line), 1);

		switch ($type) {
			case '+': // Simple string
				return $data;
			case '-': // Error
				log_message('error', 'Redis error: ' . $data);
				return FALSE;
			case ':': // Integer
				return intval($data);
			case '$': // Bulk string
				$len = intval($data);
				if ($len === -1) {
					return NULL;
				}
				$bulk = fread($this->socket, $len + 2);
				return substr($bulk, 0, -2);
			case '*': // Array
				$count = intval($data);
				if ($count === -1) {
					return NULL;
				}
				$array = array();
				for ($i = 0; $i < $count; $i++) {
					$array[] = $this->_read_response();
				}
				return $array;
			default:
				return FALSE;
		}
	}

	/**
	 * Push a job to the queue
	 *
	 * @param string $job_type Type of job (e.g., 'send_otp')
	 * @param array $data Job data/payload
	 * @return bool
	 */
	public function push($job_type, $data = array())
	{
		if (!$this->connected) {
			log_message('error', 'Queue: Redis connection not available');
			return FALSE;
		}

		$job = array(
			'type' => $job_type,
			'data' => json_encode($data),
			'created_at' => time(),
			'attempts' => 0,
		);

		try {
			$result = $this->_send_command('LPUSH', array($this->queue_key, json_encode($job)));
			return ($result !== FALSE);
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to push job - ' . $e->getMessage());
			return FALSE;
		}
	}

	/**
	 * Pop a job from the queue
	 *
	 * @return array|NULL
	 */
	public function pop()
	{
		if (!$this->connected) {
			return NULL;
		}

		try {
			$job_json = $this->_send_command('RPOP', array($this->queue_key));
			if ($job_json === NULL || $job_json === FALSE) {
				return NULL;
			}

			$job = json_decode($job_json, TRUE);
			if ($job && isset($job['data'])) {
				$job['data'] = json_decode($job['data'], TRUE);
			}

			return $job;
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to pop job - ' . $e->getMessage());
			return NULL;
		}
	}

	/**
	 * Get queue size
	 *
	 * @return int
	 */
	public function size()
	{
		if (!$this->connected) {
			return 0;
		}

		try {
			$size = $this->_send_command('LLEN', array($this->queue_key));
			return ($size !== FALSE) ? intval($size) : 0;
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to get queue size - ' . $e->getMessage());
			return 0;
		}
	}

	/**
	 * Check if queue is empty
	 *
	 * @return bool
	 */
	public function is_empty()
	{
		return $this->size() === 0;
	}

	/**
	 * Flush all jobs from queue
	 *
	 * @return bool
	 */
	public function flush()
	{
		if (!$this->connected) {
			return FALSE;
		}

		try {
			$result = $this->_send_command('DEL', array($this->queue_key));
			return ($result !== FALSE);
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to flush queue - ' . $e->getMessage());
			return FALSE;
		}
	}

	public function __destruct()
	{
		if ($this->socket) {
			fclose($this->socket);
		}
	}
}
?>
