<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Queue Library for handling asynchronous job processing
 * Uses Upstash Redis REST API for job storage
 * No external dependencies - uses PHP curl functions
 */
class Queue
{
	private $rest_url;
	private $rest_token;
	private $queue_key = 'codeigniter:queue';
	private $connected = FALSE;

	public function __construct()
	{
		// Load queue configuration
		$ci = &get_instance();
		$ci->config->load('queue');

		// Get Upstash configuration
		$this->rest_url = getenv('UPSTASH_REDIS_REST_URL') ?: $ci->config->item('upstash_redis_rest_url');
		$this->rest_token = getenv('UPSTASH_REDIS_REST_TOKEN') ?: $ci->config->item('upstash_redis_rest_token');

		// Connect and test
		$this->_connect();
	}

	/**
	 * Connect to Upstash Redis via REST API
	 */
	private function _connect()
	{
		try {
			if (empty($this->rest_url) || empty($this->rest_token)) {
				throw new Exception('Upstash REST URL or token not configured');
			}

			echo "[v0] Queue: Connecting to Upstash Redis REST API\n";
			
			// Test connection with PING
			$response = $this->_rest_request('PING');
			
			if ($response === FALSE) {
				throw new Exception('Failed to connect to Upstash Redis');
			}

			$this->connected = TRUE;
			echo "[v0] Queue: Connected to Upstash Redis successfully\n";
			log_message('info', 'Queue: Connected to Upstash Redis');
		} catch (Exception $e) {
			echo "[v0] Queue ERROR: " . $e->getMessage() . "\n";
			log_message('error', 'Queue: Connection failed - ' . $e->getMessage());
			$this->connected = FALSE;
		}
	}

	/**
	 * Make HTTP request to Upstash REST API
	 */
	private function _rest_request($command, $arguments = array())
	{
		if (!$this->connected && $command !== 'PING') {
			return FALSE;
		}

		try {
			// Build command array
			$cmd = array($command);
			if (!empty($arguments)) {
				$cmd = array_merge($cmd, $arguments);
			}

			// Prepare request
			$url = $this->rest_url;
			$headers = array(
				'Authorization: Bearer ' . $this->rest_token,
				'Content-Type: application/json',
			);

			$payload = json_encode($cmd);

			// Make request with curl
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_TIMEOUT, 10);

			$response = curl_exec($ch);
			$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($http_code !== 200) {
				throw new Exception("Upstash API error: HTTP $http_code - $response");
			}

			$result = json_decode($response, TRUE);
			
			if (isset($result['error'])) {
				throw new Exception('Upstash API error: ' . $result['error']);
			}

			return $result['result'] ?? $result;
		} catch (Exception $e) {
			log_message('error', 'Queue REST request failed: ' . $e->getMessage());
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
			echo "[v0] Queue ERROR: Upstash connection not available\n";
			log_message('error', 'Queue: Connection not available');
			return FALSE;
		}

		$job = array(
			'type' => $job_type,
			'data' => json_encode($data),
			'created_at' => time(),
			'attempts' => 0,
		);

		try {
			echo "[v0] Queue: Pushing {$job_type} job to Upstash\n";
			$result = $this->_rest_request('LPUSH', array($this->queue_key, json_encode($job)));
			
			if ($result === FALSE) {
				throw new Exception('Failed to push job');
			}

			echo "[v0] Queue: Push successful\n";
			return TRUE;
		} catch (Exception $e) {
			echo "[v0] Queue ERROR: " . $e->getMessage() . "\n";
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
			$job_json = $this->_rest_request('RPOP', array($this->queue_key));
			
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
			$size = $this->_rest_request('LLEN', array($this->queue_key));
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
			$result = $this->_rest_request('DEL', array($this->queue_key));
			return ($result !== FALSE);
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to flush queue - ' . $e->getMessage());
			return FALSE;
		}
	}
}
?>
