<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<h2>Failed login attempts</h2>
	<p class="text-muted">Recent accounts with failed login activity.</p>

	<div class="table-responsive">
		<table class="table table-striped align-middle mb-0">
			<thead>
				<tr>
					<th>User</th>
					<th>Email</th>
					<th>Attempts</th>
					<th>Last attempt</th>
					<th>Last IP</th>
					<th>Status</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($attempts)): ?>
					<tr>
						<td colspan="6" class="text-muted">No failed login attempts found.</td>
					</tr>
				<?php endif; ?>

				<?php foreach ($attempts as $attempt): ?>
					<tr>
						<td><?= html_escape($attempt->username) ?></td>
						<td><?= html_escape($attempt->email) ?></td>
						<td><?= (int) $attempt->login_attempts ?></td>
						<td><?= html_escape($attempt->last_login_attempt ?? '') ?></td>
						<td><?= html_escape($attempt->last_login_attempt_ip ?? '') ?></td>
						<td><?= html_escape($attempt->status ?? 'active') ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
</div>
