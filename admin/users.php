<?php
require_once '../dbConnection.php';

// Fetch all users
$query = "SELECT user_id, userName, email, is_admin, status FROM users";
$result = $conn->query($query);
?>

<table border="1" cellpadding="10">
    <tr>
        <th>User ID</th>
        <th>Username</th>
        <th>Email</th>
        <th>Role</th>
        <th>Status</th>
        <th>Actions</th>
    </tr>

<?php while ($row = $result->fetch_assoc()): ?>
    <tr>
        <td><?= $row['user_id'] ?></td>
        <td><?= htmlspecialchars($row['userName']) ?></td>
        <td><?= htmlspecialchars($row['email']) ?></td>
        <td><?= $row['is_admin'] ? 'Admin' : 'User' ?></td>
        <td><?= ucfirst($row['status']) ?></td>
        <td>
            <?php if ($row['status'] == 'active'): ?>
                <a href="update_status.php?id=<?= $row['user_id'] ?>&status=disabled" class="btn btn-warning">Disable</a>
            <?php else: ?>
                <a href="update_status.php?id=<?= $row['user_id'] ?>&status=active" class="btn btn-success">Enable</a>
            <?php endif; ?>

            <a href="delete_user.php?id=<?= $row['user_id'] ?>" class="btn btn-danger"
               onclick="return confirm('Are you sure you want to delete this user?');">Delete</a>
        </td>
    </tr>
<?php endwhile; ?>

</table>
