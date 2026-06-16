<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<div class="mb-3">
		<h2>Edit user</h2>
		<p class="text-muted">Update user details and role.</p>
	</div>

	<?php if ($this->session->flashdata('dashboard_error')): ?>
		<div class="alert alert-danger"><?= html_escape($this->session->flashdata('dashboard_error')) ?></div>
	<?php endif; ?>

	<?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

	<form method="post" action="<?= base_url('admin/users/edit/' . (int) $user->id) ?>">
		<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />

		<div class="row">
			<div class="col-md-6 mb-3">
				<label for="username">Username</label>
				<input type="text" class="form-control" id="username" name="username" value="<?= html_escape($user->username) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="email">Email</label>
				<input type="email" class="form-control" id="email" name="email" value="<?= html_escape($user->email) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="password">New password</label>
				<input type="password" class="form-control" id="password" name="password" placeholder="Leave blank to keep current password">
			</div>
			<div class="col-md-6 mb-3">
				<label for="role">Role</label>
				<select class="form-control" id="role" name="role" required>
					<option value="user" <?= ($user->role ?? 'user') === 'user' ? 'selected' : '' ?>>User</option>
					<option value="admin" <?= ($user->role ?? 'user') === 'admin' ? 'selected' : '' ?>>Admin</option>
				</select>
			</div>
			<div class="col-md-6 mb-3">
				<label for="first_name">First name</label>
				<input type="text" class="form-control" id="first_name" name="first_name" value="<?= html_escape($user->first_name) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="last_name">Last name</label>
				<input type="text" class="form-control" id="last_name" name="last_name" value="<?= html_escape($user->last_name) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="birthday">Birthday</label>
				<input type="date" class="form-control" id="birthday" name="birthday" value="<?= html_escape($user->birthday) ?>" required>
			</div>
		</div>

		<button type="submit" class="btn btn-primary">Save changes</button>
		<a class="btn btn-link" href="<?= base_url('admin/users') ?>">Cancel</a>
	</form>
</div>
