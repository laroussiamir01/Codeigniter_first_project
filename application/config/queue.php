<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Queue Configuration
 * Configuration for Redis queue system
 */

// Redis Host
$config['redis_host'] = getenv('REDIS_HOST') ?: 'localhost';

// Redis Port
$config['redis_port'] = getenv('REDIS_PORT') ?: 6379;

// Redis Password (optional)
$config['redis_password'] = getenv('REDIS_PASSWORD') ?: NULL;

// Queue name/key prefix
$config['queue_name'] = 'codeigniter:queue';

// Maximum retry attempts for failed jobs
$config['max_attempts'] = 3;

// Job timeout in seconds
$config['job_timeout'] = 30;

/* End of file queue.php */
/* Location: ./application/config/queue.php */
?>
