<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card shadow-sm">
				<div class="card-body">
					<h3 class="card-title mb-3">Reset Your Password</h3>
					<p class="text-muted">
						Choose a new password for <strong><?= html_escape($user_email) ?></strong>.
					</p>

					<?php if ($this->session->flashdata('reset_error')): ?>
						<div class="alert alert-danger py-2">
							<?= $this->session->flashdata('reset_error') ?>
						</div>
					<?php endif; ?>

					<?= validation_errors('<div class="alert alert-warning py-2">', '</div>') ?>

					<?= form_open('password/update') ?>
						<input type="hidden" name="token" value="<?= html_escape($token) ?>">

						<div class="form-group mb-3">
							<label for="password">New Password</label>
							<input
								type="password"
								name="password"
								id="password"
								class="form-control"
								placeholder="Min 7 characters"
								required
							>
						</div>

						<div class="form-group mb-3">
							<label for="passconf">Confirm New Password</label>
							<input
								type="password"
								name="passconf"
								id="passconf"
								class="form-control"
								placeholder="Repeat password"
								required
							>
						</div>

						<button type="submit" class="btn btn-primary w-100">Update Password</button>
					<?= form_close() ?>
				</div>
			</div>
		</div>
	</div>
</div>
