<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card shadow-sm">
				<div class="card-body">
					<h3 class="card-title mb-3">Forgot Password</h3>
					<p class="text-muted">Enter your email address and we'll send you a link to reset your password.</p>

					<?php if ($this->session->flashdata('reset_info')): ?>
						<div class="alert alert-info py-2">
							<?= $this->session->flashdata('reset_info') ?>
						</div>
					<?php endif; ?>

					<?php if ($this->session->flashdata('reset_error')): ?>
						<div class="alert alert-danger py-2">
							<?= $this->session->flashdata('reset_error') ?>
						</div>
					<?php endif; ?>

					<?= validation_errors('<div class="alert alert-warning py-2">', '</div>') ?>

					<?= form_open('password/send-link') ?>
						<div class="form-group mb-3">
							<label for="email">Email address</label>
							<input
								type="email"
								name="email"
								id="email"
								class="form-control"
								placeholder="you@example.com"
								required
							>
						</div>
						<button type="submit" class="btn btn-primary w-100">Send Reset Link</button>
					<?= form_close() ?>

					<div class="mt-3 text-center">
						<a href="<?= base_url('home') ?>">Back to Login</a>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
