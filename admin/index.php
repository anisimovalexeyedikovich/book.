<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

// Получение статистики
$stats = [
    'books' => $pdo->query("SELECT COUNT(*) FROM books")->fetchColumn(),
    'users' => $pdo->query("SELECT COUNT(*) FROM users WHERE is_admin = 0")->fetchColumn(),
    'orders' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'categories' => $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn()
];

// Получение последних заказов
$recent_orders = $pdo->query("
    SELECT o.*, u.username, b.title 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN books b ON o.book_id = b.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
")->fetchAll();

// Получение книг с малым количеством
$low_stock = $pdo->query("
    SELECT * FROM books 
    WHERE stock_quantity < 5 AND is_available = 1 
    ORDER BY stock_quantity ASC 
    LIMIT 5
")->fetchAll();
?>

<div class="row">
    <!-- Боковое меню -->
    <div class="col-md-3 mb-4">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action active">
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
            <a href="/admin/users.php" class="list-group-item list-group-item-action">
                <i class="bi bi-people"></i> Пользователи
            </a>
        </div>
    </div>

    <!-- Основной контент -->
    <div class="col-md-9">
        <h2 class="mb-4">Панель управления</h2>

        <!-- Статистика -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <h5 class="card-title">Книги</h5>
                        <p class="display-6"><?php echo $stats['books']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title">Пользователи</h5>
                        <p class="display-6"><?php echo $stats['users']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <h5 class="card-title">Заказы</h5>
                        <p class="display-6"><?php echo $stats['orders']; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <h5 class="card-title">Категории</h5>
                        <p class="display-6"><?php echo $stats['categories']; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Последние заказы -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Последние заказы</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_orders)): ?>
                        <p class="text-muted">Нет последних заказов</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Пользователь</th>
                                        <th>Книга</th>
                                        <th>Тип</th>
                                        <th>Статус</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($order['username']); ?></td>
                                        <td><?php echo htmlspecialchars($order['title']); ?></td>
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
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Книги с малым количеством -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Заканчивающиеся книги</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($low_stock)): ?>
                        <p class="text-muted">Нет книг с малым количеством</p>
                        <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Название</th>
                                        <th>В наличии</th>
                                        <th>Действие</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($low_stock as $book): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($book['title']); ?></td>
                                        <td>
                                            <span class="badge bg-danger"><?php echo $book['stock_quantity']; ?></span>
                                        </td>
                                        <td>
                                            <a href="/admin/edit_book.php?id=<?php echo $book['id']; ?>" class="btn btn-sm btn-primary">
                                                Изменить
                                            </a>
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
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
