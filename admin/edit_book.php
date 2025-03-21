<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';
$book = [
    'id' => '',
    'title' => '',
    'author_id' => '',
    'category_id' => '',
    'description' => '',
    'price' => '',
    'rental_price_2weeks' => '',
    'rental_price_1month' => '',
    'rental_price_3months' => '',
    'publication_year' => '',
    'cover_image' => '',
    'stock_quantity' => '',
    'is_available' => 1
];

// Получение списка авторов и категорий
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Редактирование существующей книги
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $book = $stmt->fetch();
    
    if (!$book) {
        header('Location: /admin/books.php');
        exit;
    }
}

// Обработка формы
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $book = array_merge($book, $_POST);
    
    // Валидация
    if (empty($book['title']) || empty($book['author_id']) || empty($book['category_id']) || 
        empty($book['price']) || empty($book['stock_quantity'])) {
        $error = 'Пожалуйста, заполните все обязательные поля';
    } else {
        try {
            if (empty($book['id'])) {
                // Создание новой книги
                $stmt = $pdo->prepare("
                    INSERT INTO books (
                        title, author_id, category_id, description, price,
                        rental_price_2weeks, rental_price_1month, rental_price_3months,
                        publication_year, cover_image, stock_quantity, is_available
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $book['title'], $book['author_id'], $book['category_id'],
                    $book['description'], $book['price'], $book['rental_price_2weeks'],
                    $book['rental_price_1month'], $book['rental_price_3months'],
                    $book['publication_year'], $book['cover_image'],
                    $book['stock_quantity'], isset($book['is_available']) ? 1 : 0
                ]);
                $success = 'Книга успешно добавлена';
            } else {
                // Обновление существующей книги
                $stmt = $pdo->prepare("
                    UPDATE books SET
                        title = ?, author_id = ?, category_id = ?, description = ?,
                        price = ?, rental_price_2weeks = ?, rental_price_1month = ?,
                        rental_price_3months = ?, publication_year = ?, cover_image = ?,
                        stock_quantity = ?, is_available = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $book['title'], $book['author_id'], $book['category_id'],
                    $book['description'], $book['price'], $book['rental_price_2weeks'],
                    $book['rental_price_1month'], $book['rental_price_3months'],
                    $book['publication_year'], $book['cover_image'],
                    $book['stock_quantity'], isset($book['is_available']) ? 1 : 0,
                    $book['id']
                ]);
                $success = 'Книга успешно обновлена';
            }
        } catch (PDOException $e) {
            $error = 'Ошибка при сохранении книги: ' . $e->getMessage();
        }
    }
}
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
            <h2><?php echo empty($book['id']) ? 'Добавление книги' : 'Редактирование книги'; ?></h2>
            <a href="/admin/books.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> Назад к списку
            </a>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="id" value="<?php echo $book['id']; ?>">

                    <div class="row">
                        <div class="col-md-8">
                            <div class="mb-3">
                                <label for="title" class="form-label">Название книги *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($book['title']); ?>" required>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="author_id" class="form-label">Автор *</label>
                                        <select class="form-select" id="author_id" name="author_id" required>
                                            <option value="">Выберите автора</option>
                                            <?php foreach ($authors as $author): ?>
                                            <option value="<?php echo $author['id']; ?>" 
                                                <?php echo $author['id'] == $book['author_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($author['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="category_id" class="form-label">Категория *</label>
                                        <select class="form-select" id="category_id" name="category_id" required>
                                            <option value="">Выберите категорию</option>
                                            <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo $category['id']; ?>" 
                                                <?php echo $category['id'] == $book['category_id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($category['name']); ?>
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="description" class="form-label">Описание</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($book['description']); ?></textarea>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="price" class="form-label">Цена покупки *</label>
                                        <input type="number" class="form-control" id="price" name="price" 
                                               value="<?php echo $book['price']; ?>" step="0.01" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="publication_year" class="form-label">Год издания</label>
                                        <input type="number" class="form-control" id="publication_year" 
                                               name="publication_year" value="<?php echo $book['publication_year']; ?>">
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="rental_price_2weeks" class="form-label">Цена аренды (2 недели)</label>
                                        <input type="number" class="form-control" id="rental_price_2weeks" 
                                               name="rental_price_2weeks" value="<?php echo $book['rental_price_2weeks']; ?>" 
                                               step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="rental_price_1month" class="form-label">Цена аренды (1 месяц)</label>
                                        <input type="number" class="form-control" id="rental_price_1month" 
                                               name="rental_price_1month" value="<?php echo $book['rental_price_1month']; ?>" 
                                               step="0.01">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="rental_price_3months" class="form-label">Цена аренды (3 месяца)</label>
                                        <input type="number" class="form-control" id="rental_price_3months" 
                                               name="rental_price_3months" value="<?php echo $book['rental_price_3months']; ?>" 
                                               step="0.01">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="cover_image" class="form-label">URL обложки</label>
                                <input type="text" class="form-control" id="cover_image" name="cover_image" 
                                       value="<?php echo htmlspecialchars($book['cover_image']); ?>">
                                <?php if ($book['cover_image']): ?>
                                <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                     class="img-thumbnail mt-2" alt="Обложка книги">
                                <?php endif; ?>
                            </div>

                            <div class="mb-3">
                                <label for="stock_quantity" class="form-label">Количество в наличии *</label>
                                <input type="number" class="form-control" id="stock_quantity" 
                                       name="stock_quantity" value="<?php echo $book['stock_quantity']; ?>" required>
                            </div>

                            <div class="mb-3">
                                <div class="form-check">
                                    <input type="checkbox" class="form-check-input" id="is_available" 
                                           name="is_available" value="1" 
                                           <?php echo $book['is_available'] ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="is_available">Доступна для заказа</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-save"></i> Сохранить
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
