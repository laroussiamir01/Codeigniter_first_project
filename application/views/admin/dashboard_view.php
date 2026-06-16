<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<div class="row mb-3">
		<div class="col">
			<h2>Dashboard</h2>
			<p class="text-muted">Security and user administration overview.</p>
		</div>
	</div>

	<div class="row">
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">Users</h5>
					<p class="display-4"><?= (int) $stats['total_users'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">Sessions</h5>
					<p class="display-4"><?= (int) $stats['active_sessions'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">Failed 24h</h5>
					<p class="display-4"><?= (int) $stats['failed_login_attempts_24h'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">OTP 24h</h5>
					<p class="display-4"><?= (int) $stats['otp_issued_24h'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">OTP used</h5>
					<p class="display-4"><?= (int) $stats['otp_consumed_24h'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-4 col-lg-2 mb-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5 class="card-title">Locked</h5>
					<p class="display-4"><?= (int) $stats['locked_accounts'] ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="row mt-3">
		<div class="col-md-6 mb-3">
			<div class="card shadow-sm h-100">
				<div class="card-header">
					<h5 class="mb-0">User management</h5>
				</div>
				<div class="card-body">
					<p>Create, edit, search, and remove user accounts.</p>
					<a class="btn btn-primary" href="<?= base_url('admin/users') ?>">Manage users</a>
				</div>
			</div>
		</div>
		<div class="col-md-6 mb-3">
			<div class="card shadow-sm h-100">
				<div class="card-header">
					<h5 class="mb-0">Security monitoring</h5>
				</div>
				<div class="card-body">
					<p>Review failed login attempts, OTP activity, and locked accounts.</p>
					<a class="btn btn-outline-primary" href="<?= base_url('admin/login-attempts') ?>">View security logs</a>
				</div>
			</div>
		</div>
	</div>
</div>
