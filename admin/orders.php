<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

// Обработка обновления статуса заказа
if (isset($_POST['update_status'])) {
    $order_id = (int)$_POST['order_id'];
    $status = $_POST['status'];
    
    $stmt = $pdo->prepare("UPDATE orders SET status = ? WHERE id = ?");
    if ($stmt->execute([$status, $order_id])) {
        $success = 'Статус заказа успешно обновлен';
    } else {
        $error = 'Ошибка при обновлении статуса заказа';
    }
}

// Получение списка заказов с информацией о пользователях и книгах
$orders = $pdo->query("
    SELECT o.*, 
           u.username, u.email,
           b.title as book_title
    FROM orders o
    JOIN users u ON o.user_id = u.id
    JOIN books b ON o.book_id = b.id
    ORDER BY o.created_at DESC
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
            <a href="/admin/orders.php" class="list-group-item list-group-item-action active">
                <i class="bi bi-cart"></i> Заказы
            </a>
            <a href="/admin/users.php" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Пользователи
            </a>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="col-md-9">
        <h2 class="mb-4">Управление заказами</h2>

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
                                <th>Книга</th>
                                <th>Тип</th>
                                <th>Сумма</th>
                                <th>Статус</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td><?php echo $order['id']; ?></td>
                                <td>
                                    <?php echo htmlspecialchars($order['username']); ?><br>
                                    <small class="text-muted"><?php echo htmlspecialchars($order['email']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($order['book_title']); ?></td>
                                <td>
                                    <?php if ($order['type'] === 'purchase'): ?>
                                        <span class="badge bg-primary">Покупка</span>
                                    <?php else: ?>
                                        <span class="badge bg-info">Аренда</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo formatPrice($order['total_price']); ?></td>
                                <td>
                                    <form method="POST" class="status-form">
                                        <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                        <select name="status" class="form-select form-select-sm status-select" 
                                                onchange="this.form.submit()">
                                            <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>
                                                В обработке
                                            </option>
                                            <option value="completed" <?php echo $order['status'] === 'completed' ? 'selected' : ''; ?>>
                                                Завершен
                                            </option>
                                            <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>
                                                Отменен
                                            </option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo date('d.m.Y H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn btn-sm btn-info view-order" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#orderModal"
                                            data-order='<?php echo json_encode($order); ?>'>
                                        <i class="bi bi-eye"></i>
                                    </button>
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

<!-- Модальное окно для просмотра деталей заказа -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Детали заказа</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="order-details">
                    <!-- Детали заказа будут добавлены через JavaScript -->
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
    // Обработка просмотра деталей заказа
    document.querySelectorAll('.view-order').forEach(button => {
        button.addEventListener('click', function() {
            const order = JSON.parse(this.dataset.order);
            const details = document.querySelector('.order-details');
            
            let rentalInfo = '';
            if (order.type === 'rent') {
                rentalInfo = `
                    <p><strong>Период аренды:</strong><br>
                    с ${new Date(order.rental_start_date).toLocaleDateString()} 
                    по ${new Date(order.rental_end_date).toLocaleDateString()}</p>
                `;
            }
            
            details.innerHTML = `
                <p><strong>ID заказа:</strong> ${order.id}</p>
                <p><strong>Пользователь:</strong> ${order.username}</p>
                <p><strong>Email:</strong> ${order.email}</p>
                <p><strong>Книга:</strong> ${order.book_title}</p>
                <p><strong>Тип:</strong> ${order.type === 'purchase' ? 'Покупка' : 'Аренда'}</p>
                <p><strong>Сумма:</strong> ${formatPrice(order.total_price)}</p>
                ${rentalInfo}
                <p><strong>Дата создания:</strong> ${new Date(order.created_at).toLocaleString()}</p>
            `;
        });
    });
});

function formatPrice(price) {
    return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
    }).format(price);
}
</script>

<?php require_once '../includes/footer.php'; ?>
