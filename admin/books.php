<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

// Обработка удаления книги
if (isset($_POST['delete_book'])) {
    $book_id = (int)$_POST['book_id'];
    $stmt = $pdo->prepare("DELETE FROM books WHERE id = ?");
    if ($stmt->execute([$book_id])) {
        $success = 'Книга успешно удалена';
    } else {
        $error = 'Ошибка при удалении книги';
    }
}

// Получение списка книг
$stmt = $pdo->query("
    SELECT b.*, a.name as author_name, c.name as category_name 
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    JOIN categories c ON b.category_id = c.id 
    ORDER BY b.title
");
$books = $stmt->fetchAll();
?>

<div class="row">
    <!-- Боковое меню -->
    <div class="col-md-3 mb-4">
        <div class="list-group">
            <a href="/admin/" class="list-group-item list-group-item-action">
                <i class="bi bi-speedometer2"></i> Панель управления
            </a>
            <a href="/admin/books.php" class="list-group-item list-group-item-action active">
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
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Управление книгами</h2>
            <a href="/admin/edit_book.php" class="btn btn-primary">
                <i class="bi bi-plus-lg"></i> Добавить книгу
            </a>
        </div>

        <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Обложка</th>
                                <th>Название</th>
                                <th>Автор</th>
                                <th>Категория</th>
                                <th>Цена</th>
                                <th>В наличии</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($books as $book): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                         alt="<?php echo htmlspecialchars($book['title']); ?>" 
                                         class="img-thumbnail" 
                                         style="width: 50px;">
                                </td>
                                <td><?php echo htmlspecialchars($book['title']); ?></td>
                                <td><?php echo htmlspecialchars($book['author_name']); ?></td>
                                <td><?php echo htmlspecialchars($book['category_name']); ?></td>
                                <td><?php echo formatPrice($book['price']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $book['stock_quantity'] < 5 ? 'danger' : 'success'; ?>">
                                        <?php echo $book['stock_quantity']; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $book['is_available'] ? 'success' : 'danger'; ?>">
                                        <?php echo $book['is_available'] ? 'Доступна' : 'Недоступна'; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="/admin/edit_book.php?id=<?php echo $book['id']; ?>" 
                                           class="btn btn-sm btn-primary"
                                           title="Редактировать">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить эту книгу?');">
                                            <input type="hidden" name="book_id" value="<?php echo $book['id']; ?>">
                                            <button type="submit" name="delete_book" 
                                                    class="btn btn-sm btn-danger"
                                                    title="Удалить">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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

<?php require_once '../includes/footer.php'; ?>
