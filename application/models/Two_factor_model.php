<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Two_factor_model extends CI_Model
{
	const CODE_TTL_MINUTES = 10;
	const MAX_ATTEMPTS     = 5;

	public function __construct()
	{
		parent::__construct();
	}

	public function issue_code($user_id)
	{
		$code    = $this->generate_code();
		$hash    = password_hash($code, PASSWORD_BCRYPT, ['cost' => 10]);
		$now     = new DateTimeImmutable('now');
		$expires = $now->modify('+' . self::CODE_TTL_MINUTES . ' minutes');

		$this->db->insert('user_otp', [
			'user_id'    => $user_id,
			'code_hash'  => $hash,
			'expires_at' => $expires->format('Y-m-d H:i:s'),
			'created_at' => $now->format('Y-m-d H:i:s'),
		]);

		return $code;
	}

	public function verify($user_id, $submitted_code)
	{
		$row = $this->db
			->where('user_id', $user_id)
			->where('consumed_at', NULL)
			->order_by('id', 'DESC')
			->limit(1)
			->get('user_otp')
			->row();

		if (empty($row)) {
			return 'no_code';
		}

		if ((int) $row->attempts >= self::MAX_ATTEMPTS) {
			return 'locked';
		}

		$now = new DateTimeImmutable('now');
		if ($now > new DateTimeImmutable($row->expires_at)) {
			return 'expired';
		}

		if (! password_verify($submitted_code, $row->code_hash)) {
			$this->db->where('id', $row->id)->update('user_otp', [
				'attempts' => $row->attempts + 1,
			]);
			return 'mismatch';
		}

		$this->db->where('id', $row->id)->update('user_otp', [
			'consumed_at' => $now->format('Y-m-d H:i:s'),
		]);

		return 'ok';
	}

	public function last_request_age_seconds($user_id)
	{
		$row = $this->db
			->select('created_at')
			->where('user_id', $user_id)
			->order_by('id', 'DESC')
			->limit(1)
			->get('user_otp')
			->row();

		if (empty($row)) {
			return PHP_INT_MAX;
		}

		$created = new DateTimeImmutable($row->created_at);
		return (new DateTimeImmutable('now'))->getTimestamp() - $created->getTimestamp();
	}

	private function generate_code()
	{
		return str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
	}
}
