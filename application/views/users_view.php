
<div class="users">
    <table class="users-table">
        <thead>
            <tr>
                <th>#</th>
                <th>Username</th>
                <th>Password</th>
            </tr>
        </thead>

        <tbody>
        <?php foreach (($users ?? []) as $user): ?>
            <tr>
                <td><?= html_escape($user->id); ?></td>
                <td><?= html_escape($user->username); ?></td>
                <td><?= html_escape($user->password); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>

    </table>
</div>