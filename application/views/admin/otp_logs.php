<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<h2>OTP logs</h2>
	<p class="text-muted">Recent two-factor authentication activity.</p>

	<div class="row mb-3">
		<div class="col-md-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5>Issued 24h</h5>
					<p class="display-4 mb-0"><?= (int) $otp_stats['otp_issued_24h'] ?></p>
				</div>
			</div>
		</div>
		<div class="col-md-3">
			<div class="card text-center shadow-sm">
				<div class="card-body">
					<h5>Consumed 24h</h5>
					<p class="display-4 mb-0"><?= (int) $otp_stats['otp_consumed_24h'] ?></p>
				</div>
			</div>
		</div>
	</div>

	<div class="table-responsive">
		<table class="table table-striped align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>User ID</th>
					<th>Created</th>
					<th>Expires</th>
					<th>Consumed</th>
					<th>Attempts</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($otp_logs)): ?>
					<tr>
						<td colspan="6" class="text-muted">No OTP records found.</td>
					</tr>
				<?php endif; ?>

				<?php foreach ($otp_logs as $otp): ?>
					<tr>
						<td><?= (int) $otp->id ?></td>
						<td><?= (int) $otp->user_id ?></td>
						<td><?= html_escape($otp->created_at) ?></td>
						<td><?= html_escape($otp->expires_at) ?></td>
						<td><?= html_escape($otp->consumed_at ?? '') ?></td>
						<td><?= (int) $otp->attempts ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
