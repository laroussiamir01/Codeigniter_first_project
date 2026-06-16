<?php $this->load->view('admin/_nav'); ?>

<div class="container">
	<div class="mb-3">
		<h2>Create user</h2>
		<p class="text-muted">Add a new application user.</p>
	</div>

	<?php if ($this->session->flashdata('dashboard_error')): ?>
		<div class="alert alert-danger"><?= html_escape($this->session->flashdata('dashboard_error')) ?></div>
	<?php endif; ?>

	<?= validation_errors('<div class="alert alert-danger">', '</div>') ?>

	<form method="post" action="<?= base_url('admin/users/create') ?>">
		<input type="hidden" name="<?= $this->security->get_csrf_token_name() ?>" value="<?= $this->security->get_csrf_hash() ?>" />

		<div class="row">
			<div class="col-md-6 mb-3">
				<label for="username">Username</label>
				<input type="text" class="form-control" id="username" name="username" value="<?= html_escape($this->input->post('username')) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="email">Email</label>
				<input type="email" class="form-control" id="email" name="email" value="<?= html_escape($this->input->post('email')) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="password">Password</label>
				<input type="password" class="form-control" id="password" name="password" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="role">Role</label>
				<select class="form-control" id="role" name="role" required>
					<option value="user" <?= $this->input->post('role') === 'user' ? 'selected' : '' ?>>User</option>
					<option value="admin" <?= $this->input->post('role') === 'admin' ? 'selected' : '' ?>>Admin</option>
				</select>
			</div>
			<div class="col-md-6 mb-3">
				<label for="first_name">First name</label>
				<input type="text" class="form-control" id="first_name" name="first_name" value="<?= html_escape($this->input->post('first_name')) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="last_name">Last name</label>
				<input type="text" class="form-control" id="last_name" name="last_name" value="<?= html_escape($this->input->post('last_name')) ?>" required>
			</div>
			<div class="col-md-6 mb-3">
				<label for="birthday">Birthday</label>
				<input type="date" class="form-control" id="birthday" name="birthday" value="<?= html_escape($this->input->post('birthday')) ?>" required>
			</div>
		</div>

		<button type="submit" class="btn btn-primary">Create user</button>
		<a class="btn btn-link" href="<?= base_url('admin/users') ?>">Cancel</a>
	</form>
</div>
