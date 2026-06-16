<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
	<div class="container">
		<a class="navbar-brand" href="<?= base_url('admin/dashboard') ?>">Admin Dashboard</a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#adminNav" aria-controls="adminNav" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>

		<div class="collapse navbar-collapse" id="adminNav">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item">
					<a class="nav-link" href="<?= base_url('admin/dashboard') ?>">Dashboard</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= base_url('admin/users') ?>">Users</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= base_url('admin/locked-accounts') ?>">Locked accounts</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= base_url('admin/login-attempts') ?>">Login attempts</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" href="<?= base_url('admin/otp-logs') ?>">OTP logs</a>
				</li>
			</ul>
			<span class="navbar-text text-light">
				Signed in as <?= html_escape($this->session->userdata('username')) ?>
			</span>
		</div>
	</div>
</nav>
