<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

// Обработка удаления категории
if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];
    $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
    if ($stmt->execute([$category_id])) {
        $success = 'Категория успешно удалена';
    } else {
        $error = 'Ошибка при удалении категории';
    }
}

// Обработка добавления/редактирования категории
if (isset($_POST['save_category'])) {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;

    if (empty($name)) {
        $error = 'Название категории обязательно для заполнения';
    } else {
        if ($category_id > 0) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE categories SET name = ?, description = ? WHERE id = ?");
            if ($stmt->execute([$name, $description, $category_id])) {
                $success = 'Категория успешно обновлена';
            } else {
                $error = 'Ошибка при обновлении категории';
            }
        } else {
            // Добавление
            $stmt = $pdo->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
            if ($stmt->execute([$name, $description])) {
                $success = 'Категория успешно добавлена';
            } else {
                $error = 'Ошибка при добавлении категории';
            }
        }
    }
}

// Получение списка категорий
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
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
            <a href="/admin/categories.php" class="list-group-item list-group-item-action active">
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
            <h2>Управление категориями</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#categoryModal">
                <i class="bi bi-plus-lg"></i> Добавить категорию
            </button>
        </div>

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
                                <th>Название</th>
                                <th>Описание</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $category): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                <td><?php echo htmlspecialchars($category['description']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary edit-category" 
                                                data-id="<?php echo $category['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($category['name']); ?>"
                                                data-description="<?php echo htmlspecialchars($category['description']); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#categoryModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить эту категорию?');">
                                            <input type="hidden" name="category_id" value="<?php echo $category['id']; ?>">
                                            <button type="submit" name="delete_category" class="btn btn-sm btn-danger">
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

<!-- Модальное окно для добавления/редактирования категории -->
<div class="modal fade" id="categoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Категория</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="categoryForm" method="POST">
                    <input type="hidden" name="category_id" id="category_id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Название</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Описание</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="categoryForm" name="save_category" class="btn btn-primary">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка редактирования категории
    document.querySelectorAll('.edit-category').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('category_id').value = this.dataset.id;
            document.getElementById('name').value = this.dataset.name;
            document.getElementById('description').value = this.dataset.description;
        });
    });

    // Сброс формы при открытии модального окна для добавления
    document.querySelector('[data-bs-target="#categoryModal"]').addEventListener('click', function() {
        document.getElementById('categoryForm').reset();
        document.getElementById('category_id').value = '';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
