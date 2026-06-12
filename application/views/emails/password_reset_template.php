<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<style>
		body { font-family: Arial, Helvetica, sans-serif; color: #333; line-height: 1.6; }
		.container { max-width: 600px; margin: 0 auto; padding: 20px; }
		.header { background: #007bff; color: #fff; padding: 20px; text-align: center; border-radius: 6px 6px 0 0; }
		.body { padding: 20px; background: #f9f9f9; border: 1px solid #ddd; }
		.btn { display: inline-block; padding: 12px 24px; background: #007bff; color: #fff; text-decoration: none; border-radius: 4px; margin: 20px 0; }
		.footer { font-size: 12px; color: #999; text-align: center; padding: 10px; }
	</style>
</head>
<body>
	<div class="container">
		<div class="header">
			<h2>Password Reset Request</h2>
		</div>
		<div class="body">
			<p>Hello <?= $username ?>,</p>
			<p>We received a request to reset your password. Click the button below to choose a new one.</p>
			<p style="text-align: center;">
				<a href="<?= $reset_url ?>" class="btn">Reset Password</a>
			</p>
			<p>This link expires at <strong><?= $expiry ?></strong>.</p>
			<p>If you did not request this, you can safely ignore this email.</p>
		</div>
		<div class="footer">
			<p>&copy; <?= date('Y') ?> CodeIgniter App. All rights reserved.</p>
		</div>
	</div>
</body>
</html>
