<div class="container mt-5">
	<div class="row justify-content-center">
		<div class="col-md-6">
			<div class="card shadow-sm">
				<div class="card-body">
					<h3 class="card-title mb-3">Two-factor verification</h3>
					<p class="text-muted">
						Hello <strong><?= html_escape($username) ?></strong>, enter the 6-digit code
						we sent to <strong><?= html_escape($masked_to) ?></strong>.
					</p>

					<?php if ($this->session->flashdata('otp_info')): ?>
						<div class="alert alert-info py-2">
							<?= $this->session->flashdata('otp_info') ?>
						</div>
					<?php endif; ?>

					<?php if ($this->session->flashdata('otp_error')): ?>
						<div class="alert alert-danger py-2">
							<?= $this->session->flashdata('otp_error') ?>
						</div>
					<?php endif; ?>

					<?= validation_errors('<div class="alert alert-warning py-2">', '</div>') ?>

					<?= form_open('two_factor/verify', ['autocomplete' => 'off']) ?>
						<div class="form-group mb-3">
							<label for="otp">Verification code</label>
							<input
								type="text"
								inputmode="numeric"
								pattern="[0-9]{6}"
								maxlength="6"
								name="otp"
								id="otp"
								class="form-control"
								placeholder="000000"
								required
							>
						</div>
						<button type="submit" class="btn btn-primary w-100">Verify</button>
					<?= form_close() ?>

					<?= form_open('two_factor/resend', ['class' => 'mt-2']) ?>
						<button type="submit" class="btn btn-link p-0">
							Resend a new code
						</button>
					<?= form_close() ?>
				</div>
			</div>
		</div>
	</div>
</div>