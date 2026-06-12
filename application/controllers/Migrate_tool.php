<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * One-off migration runner for local/dev use.
 *
 * Usage:
 *   http://localhost:8000/index.php/migrate_tool/run
 *
 * Refuses to run unless ENVIRONMENT !== 'production' or $config['allow_production']
 * is set to TRUE in application/config/migrate_tool.php.
 *
 * Remove or restrict access in production.
 */
class Migrate_tool extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		$this->load->library('migration');
	}

	public function run()
	{
		if (ENVIRONMENT === 'production' && ! $this->is_allowed_in_production()) {
			show_error('Migration tool is disabled in production.', 403);
		}

		if ($this->migration->latest() === FALSE) {
			show_error($this->migration->error_string(), 500);
		}

		echo "Migrations applied. Current version: ";
	}

	private function is_allowed_in_production()
	{
		$conf = include APPPATH . 'config/migrate_tool.php';
		return is_array($conf) && ! empty($conf['allow_production']);
	}
}
