<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<div class="d-flex justify-content-between align-items-center mb-3">
		<div>
			<h2>Users</h2>
			<p class="text-muted mb-0">Paginated user management.</p>
		</div>
		<a class="btn btn-primary" href="<?= base_url('admin/users/create') ?>">Create user</a>
	</div>

	<?php if ($this->session->flashdata('dashboard_success')): ?>
		<div class="alert alert-success"><?= html_escape($this->session->flashdata('dashboard_success')) ?></div>
	<?php endif; ?>

	<?php if ($this->session->flashdata('dashboard_error')): ?>
		<div class="alert alert-danger"><?= html_escape($this->session->flashdata('dashboard_error')) ?></div>
	<?php endif; ?>

	<form method="get" action="<?= base_url('admin/users/search') ?>" class="row g-3 mb-4">
		<div class="col-md-9">
			<input type="text" class="form-control" name="query" value="<?= html_escape($search) ?>" placeholder="Search by username, email, first name, or last name">
		</div>
		<div class="col-md-3">
			<button type="submit" class="btn btn-outline-primary w-100">Search</button>
		</div>
	</form>

	<?php if ($search !== ''): ?>
		<a class="btn btn-link p-0 mb-3" href="<?= base_url('admin/users') ?>">Show all users</a>
	<?php endif; ?>

	<div class="table-responsive">
		<table class="table table-striped align-middle mb-0">
			<thead>
				<tr>
					<th>ID</th>
					<th>Username</th>
					<th>Email</th>
					<th>Full name</th>
					<th>Role</th>
					<th>Status</th>
					<th>Locked until</th>
					<th>Actions</th>
				</tr>
			</thead>
			<tbody>
				<?php if (empty($users)): ?>
					<tr>
						<td colspan="8" class="text-muted">No users found.</td>
					</tr>
				<?php endif; ?>

				<?php foreach ($users as $user): ?>
					<tr>
						<td><?= (int) $user->id ?></td>
						<td><?= html_escape($user->username) ?></td>
						<td><?= html_escape($user->email) ?></td>
						<td><?= html_escape($user->first_name . ' ' . $user->last_name) ?></td>
						<td><span class="<?= $user->role === 'admin' ? 'primary' : 'secondary' ?>"><?= html_escape($user->role ?? 'user') ?></span></td>
						<td><?= html_escape($user->status ?? 'active') ?></td>
						<td><?= html_escape($user->locked_until ?? '') ?></td>
						<td>
							<a class="btn btn-sm btn-outline-primary" href="<?= base_url('admin/users/edit/' . (int) $user->id) ?>">Edit</a>
							<form method="post" action="<?= base_url('admin/users/delete/' . (int) $user->id) ?>" class="d-inline">
								<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />
								<button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Delete this user?')">Delete</button>
							</form>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php if ($links !== ''): ?>
		<div class="mt-3"><?= $links ?></div>
	<?php endif; ?>
</div>
