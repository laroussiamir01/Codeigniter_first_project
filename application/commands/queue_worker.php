#!/usr/bin/env php
<?php
/**
 * Queue Worker - CLI Command to process async jobs
 * 
 * Usage: php application/commands/queue_worker.php
 *    or: ./application/commands/queue_worker.php (if made executable)
 * 
 * This worker continuously polls the Redis queue and processes jobs
 */

// Set up paths
$root = dirname(__FILE__) . '/../..';
define('BASEPATH', $root . '/system/');
define('APPPATH', $root . '/application/');
define('FCPATH', $root . '/');
define('ENVIRONMENT', isset($_SERVER['CI_ENV']) ? $_SERVER['CI_ENV'] : 'development');

// Load CodeIgniter bootstrap
require_once BASEPATH . 'core/CodeIgniter.php';

/**
 * Queue Worker Class
 */
class Queue_Worker
{
	private $ci;
	private $running = TRUE;
	private $job_count = 0;
	private $error_count = 0;

	public function __construct()
	{
		$this->ci = &get_instance();
		$this->ci->load->library('queue');
		$this->ci->load->model('Two_factor_model');
	}

	/**
	 * Main worker loop
	 */
	public function run()
	{
		$this->log('Queue Worker started');
		$this->log('Polling Redis queue for jobs...');

		// Handle signals for graceful shutdown
		if (function_exists('pcntl_signal')) {
			pcntl_signal(SIGTERM, array($this, 'shutdown'));
			pcntl_signal(SIGINT, array($this, 'shutdown'));
		}

		while ($this->running) {
			$this->process_jobs();
			// Sleep for 1 second before next poll to prevent high CPU usage
			sleep(1);
		}

		$this->log('Queue Worker stopped');
		$this->log("Processed {$this->job_count} jobs, {$this->error_count} errors");
	}

	/**
	 * Process jobs from queue
	 */
	private function process_jobs()
	{
		// Check if queue has jobs
		if ($this->ci->queue->is_empty()) {
			return;
		}

		// Pop a job from queue
		$job = $this->ci->queue->pop();

		if ($job === NULL) {
			return;
		}

		$this->log("Processing job: {$job['type']}");

		try {
			$this->handle_job($job);
			$this->job_count++;
		} catch (Exception $e) {
			$this->error_count++;
			$this->log("Error processing job: " . $e->getMessage(), 'error');
		}
	}

	/**
	 * Handle different job types
	 */
	private function handle_job($job)
	{
		switch ($job['type']) {
			case 'send_otp':
				$this->handle_send_otp($job);
				break;
			default:
				throw new Exception("Unknown job type: {$job['type']}");
		}
	}

	/**
	 * Handle OTP email sending job
	 */
	private function handle_send_otp($job)
	{
		$data = $job['data'];

		// Validate required fields
		if (empty($data['user_id']) || empty($data['code']) || empty($data['email'])) {
			throw new Exception('Invalid OTP job data');
		}

		// Send OTP email
		$this->ci->load->library('email');

		$this->ci->email->from('no-reply@example.com', 'CodeIgniter App');
		$this->ci->email->to($data['email']);
		$this->ci->email->subject('OTP: ' . $data['code']);
		$this->ci->email->message($data['code']);

		if (!$this->ci->email->send()) {
			throw new Exception('Failed to send OTP email');
		}

		$this->log("OTP email sent to {$data['email']} for user {$data['user_id']}");
	}

	/**
	 * Log message
	 */
	private function log($message, $level = 'info')
	{
		$timestamp = date('Y-m-d H:i:s');
		echo "[{$timestamp}] [{$level}] {$message}\n";
		log_message($level, "Queue Worker: {$message}");
	}

	/**
	 * Graceful shutdown
	 */
	public function shutdown()
	{
		$this->log('Shutdown signal received');
		$this->running = FALSE;
	}
}

// Instantiate and run worker
$worker = new Queue_Worker();
$worker->run();
?>
