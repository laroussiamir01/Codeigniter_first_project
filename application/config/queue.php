<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Queue Configuration
 * Configuration for Upstash Redis REST API
 */

// Upstash Redis REST URL
$config['upstash_redis_rest_url'] = getenv('UPSTASH_REDIS_REST_URL') ?: '';

// Upstash Redis REST Token
$config['upstash_redis_rest_token'] = getenv('UPSTASH_REDIS_REST_TOKEN') ?: '';

// Queue name/key prefix
$config['queue_name'] = 'codeigniter:queue';

// Maximum retry attempts for failed jobs
$config['max_attempts'] = 3;

// Job timeout in seconds
$config['job_timeout'] = 30;

/* End of file queue.php */
/* Location: ./application/config/queue.php */
?>
