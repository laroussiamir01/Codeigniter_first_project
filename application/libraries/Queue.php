<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Queue Library for handling asynchronous job processing
 * Uses Redis as the backend for job storage
 */
class Queue
{
	private $redis;
	private $queue_key = 'codeigniter:queue';
	private $processing_key = 'codeigniter:queue:processing';

	public function __construct()
	{
		// Load queue configuration
		$ci = &get_instance();
		$ci->config->load('queue');

		// Initialize Redis connection
		$redis_config = array(
			'host' => $ci->config->item('redis_host'),
			'port' => $ci->config->item('redis_port'),
		);

		// Add password if configured
		if ($ci->config->item('redis_password')) {
			$redis_config['password'] = $ci->config->item('redis_password');
		}

		try {
			$this->redis = new Predis\Client($redis_config);
			// Test connection
			$this->redis->ping();
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to connect to Redis - ' . $e->getMessage());
			$this->redis = NULL;
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
		if ($this->redis === NULL) {
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
			$this->redis->lpush($this->queue_key, json_encode($job));
			return TRUE;
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
		if ($this->redis === NULL) {
			return NULL;
		}

		try {
			$job_json = $this->redis->rpop($this->queue_key);
			if ($job_json === NULL) {
				return NULL;
			}

			$job = json_decode($job_json, TRUE);
			$job['data'] = json_decode($job['data'], TRUE);

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
		if ($this->redis === NULL) {
			return 0;
		}

		try {
			return $this->redis->llen($this->queue_key);
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
		if ($this->redis === NULL) {
			return FALSE;
		}

		try {
			$this->redis->del($this->queue_key);
			return TRUE;
		} catch (Exception $e) {
			log_message('error', 'Queue: Failed to flush queue - ' . $e->getMessage());
			return FALSE;
		}
	}
}
?>
