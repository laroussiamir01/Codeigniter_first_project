<div class="container mt-5">
	<div class="card shadow-sm">
		<div class="card-header">
			<h3 class="mb-0">Locked accounts</h3>
		</div>
		<div class="card-body">
			<?php if ($this->session->flashdata('unlock_success')): ?>
				<div class="alert alert-success">
					<?= html_escape($this->session->flashdata('unlock_success')) ?>
				</div>
			<?php endif; ?>

			<?php if ($this->session->flashdata('unlock_error')): ?>
				<div class="alert alert-danger">
					<?= html_escape($this->session->flashdata('unlock_error')) ?>
				</div>
			<?php endif; ?>

			<?php if (empty($locked_users)): ?>
				<p class="text-muted mb-0">No accounts are currently locked.</p>
			<?php else: ?>
				<div class="table-responsive">
					<table class="table table-striped align-middle mb-0">
						<thead>
							<tr>
								<th>User</th>
								<th>Email</th>
								<th>Attempts</th>
								<th>Locked until</th>
								<th>Last IP</th>
								<th>Action</th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ($locked_users as $locked_user): ?>
								<tr>
									<td><?= html_escape($locked_user->username) ?></td>
									<td><?= html_escape($locked_user->email) ?></td>
									<td><?= (int) $locked_user->login_attempts ?></td>
									<td><?= html_escape($locked_user->locked_until) ?></td>
									<td><?= html_escape($locked_user->last_login_attempt_ip ?? '') ?></td>
									<td>
										<form method="post" action="<?= base_url('admin/unlock/' . (int) $locked_user->id) ?>">
											<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />
											<button type="submit" class="btn btn-sm btn-primary">
												Unlock
											</button>
										</form>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>
			<?php endif; ?>
		</div>
	</div>
</div>
