<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

// Обработка блокировки/разблокировки пользователя
if (isset($_POST['toggle_status'])) {
    $user_id = (int)$_POST['user_id'];
    $is_active = (int)$_POST['is_active'];
    
    $stmt = $pdo->prepare("UPDATE users SET is_active = ? WHERE id = ?");
    if ($stmt->execute([$is_active, $user_id])) {
        $success = 'Статус пользователя успешно обновлен';
    } else {
        $error = 'Ошибка при обновлении статуса пользователя';
    }
}

// Получение списка пользователей
$users = $pdo->query("
    SELECT u.*,
           COUNT(DISTINCT o.id) as total_orders,
           SUM(CASE WHEN o.type = 'purchase' THEN 1 ELSE 0 END) as purchases,
           SUM(CASE WHEN o.type = 'rent' THEN 1 ELSE 0 END) as rentals
    FROM users u
    LEFT JOIN orders o ON u.id = o.user_id
    GROUP BY u.id
    ORDER BY u.created_at DESC
")->fetchAll();
?>

<div class="row">
    <!-- Боковое меню -->
    <div class="col-md-3 mb-4">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">
                <i class="bi bi-speedometer2"></i> Панель управления
            </a>
            <a href="/admin/books.php" class="list-group-item list-group-item-action">
                <i class="bi bi-book"></i> Управление книгами
            </a>
            <a href="/admin/categories.php" class="list-group-item list-group-item-action">
                <i class="bi bi-grid"></i> Категории
            </a>
            <a href="/admin/authors.php" class="list-group-item list-group-item-action">
                <i class="bi bi-person"></i> Авторы
            </a>
            <a href="/admin/orders.php" class="list-group-item list-group-item-action">
                <i class="bi bi-cart"></i> Заказы
            </a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action active">
                <i class="bi bi-people"></i> Пользователи
            </a>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="col-md-9">
        <h2 class="mb-4">Управление пользователями</h2>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Пользователь</th>
                                <th>Статистика</th>
                                <th>Статус</th>
                                <th>Дата регистрации</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td><?php echo $user['id']; ?></td>
                                <td>
                                    <div>
                                        <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-danger">Администратор</span>
                                        <?php endif; ?>
                                    </div>
                                    <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                </td>
                                <td>
                                    <small>
                                        Всего заказов: <?php echo $user['total_orders']; ?><br>
                                        Покупок: <?php echo $user['purchases']; ?><br>
                                        Аренд: <?php echo $user['rentals']; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($user['is_active']): ?>
                                        <span class="badge bg-success">Активен</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Заблокирован</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if (!$user['is_admin']): ?>
                                    <form method="POST" class="d-inline" 
                                          onsubmit="return confirm('Вы уверены?');">
                                        <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                        <input type="hidden" name="is_active" value="<?php echo $user['is_active'] ? 0 : 1; ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="btn btn-sm <?php echo $user['is_active'] ? 'btn-warning' : 'btn-success'; ?>">
                                            <?php echo $user['is_active'] ? '<i class="bi bi-lock"></i>' : '<i class="bi bi-unlock"></i>'; ?>
                                        </button>
                                    </form>
                                    <button class="btn btn-sm btn-info view-user" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#userModal"
                                            data-user='<?php echo json_encode($user); ?>'>
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для просмотра деталей пользователя -->
<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали пользователя</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="user-details">
                    <!-- Детали пользователя будут добавлены через JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка просмотра деталей пользователя
    document.querySelectorAll('.view-user').forEach(button => {
        button.addEventListener('click', function() {
            const user = JSON.parse(this.dataset.user);
            const details = document.querySelector('.user-details');
            
            details.innerHTML = `
                <p><strong>ID:</strong> ${user.id}</p>
                <p><strong>Имя пользователя:</strong> ${user.username}</p>
                <p><strong>Полное имя:</strong> ${user.full_name || '-'}</p>
                <p><strong>Email:</strong> ${user.email}</p>
                <p><strong>Статус:</strong> ${user.is_active ? 'Активен' : 'Заблокирован'}</p>
                <p><strong>Администратор:</strong> ${user.is_admin ? 'Да' : 'Нет'}</p>
                <p><strong>Дата регистрации:</strong> ${new Date(user.created_at).toLocaleString()}</p>
                <hr>
                <h6>Статистика заказов:</h6>
                <p>Всего заказов: ${user.total_orders}</p>
                <p>Покупок: ${user.purchases}</p>
                <p>Аренд: ${user.rentals}</p>
            `;
        });
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
