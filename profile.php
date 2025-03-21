<?php
require_once 'includes/header.php';

if (!isLoggedIn()) {
    header('Location: /login.php');
    exit;
}

// Получение информации о пользователе
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user']['id']]);
$user = $stmt->fetch();

// Получение заказов пользователя
$stmt = $pdo->prepare("
    SELECT o.*, b.title, b.cover_image 
    FROM orders o 
    JOIN books b ON o.book_id = b.id 
    WHERE o.user_id = ? 
    ORDER BY o.created_at DESC
");
$stmt->execute([$_SESSION['user']['id']]);
$orders = $stmt->fetchAll();
?>

<div class="row">
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Профиль</h5>
                <div class="mb-3">
                    <strong>Имя пользователя:</strong>
                    <p><?php echo htmlspecialchars($user['username']); ?></p>
                </div>
                <div class="mb-3">
                    <strong>Полное имя:</strong>
                    <p><?php echo htmlspecialchars($user['full_name']); ?></p>
                </div>
                <div class="mb-3">
                    <strong>Email:</strong>
                    <p><?php echo htmlspecialchars($user['email']); ?></p>
                </div>
                <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editProfileModal">
                    <i class="bi bi-pencil"></i> Редактировать профиль
                </a>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Мои заказы</h5>
                <?php if (empty($orders)): ?>
                <p class="text-muted">У вас пока нет заказов</p>
                <?php else: ?>
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Книга</th>
                                <th>Тип</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Срок аренды</th>
                                <th>Сумма</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($order['cover_image']); ?>" 
                                             alt="<?php echo htmlspecialchars($order['title']); ?>"
                                             class="img-thumbnail me-2" style="width: 50px;">
                                        <?php echo htmlspecialchars($order['title']); ?>
                                    </div>
                                </td>
                                <td><?php echo $order['type'] === 'purchase' ? 'Покупка' : 'Аренда'; ?></td>
                                <td>
                                    <?php
                                    $status_class = [
                                        'pending' => 'warning',
                                        'completed' => 'success',
                                        'cancelled' => 'danger'
                                    ][$order['status']];
                                    $status_text = [
                                        'pending' => 'В обработке',
                                        'completed' => 'Завершен',
                                        'cancelled' => 'Отменен'
                                    ][$order['status']];
                                    ?>
                                    <span class="badge bg-<?php echo $status_class; ?>">
                                        <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d.m.Y', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <?php if ($order['type'] === 'rent'): ?>
                                    <?php echo date('d.m.Y', strtotime($order['rental_start_date'])); ?> -
                                    <?php echo date('d.m.Y', strtotime($order['rental_end_date'])); ?>
                                    <?php else: ?>
                                    -
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPrice($order['total_price']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно редактирования профиля -->
<div class="modal fade" id="editProfileModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Редактирование профиля</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProfileForm" method="POST" action="/api/update_profile.php">
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Полное имя</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" 
                               value="<?php echo htmlspecialchars($user['full_name']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Новый пароль (оставьте пустым, если не хотите менять)</label>
                        <input type="password" class="form-control" id="new_password" name="new_password">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="editProfileForm" class="btn btn-primary">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
