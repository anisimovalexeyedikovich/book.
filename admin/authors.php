<?php
require_once '../includes/header.php';

if (!isAdmin()) {
    header('Location: /');
    exit;
}

$error = '';
$success = '';

// Обработка удаления автора
if (isset($_POST['delete_author'])) {
    $author_id = (int)$_POST['author_id'];
    $stmt = $pdo->prepare("DELETE FROM authors WHERE id = ?");
    if ($stmt->execute([$author_id])) {
        $success = 'Автор успешно удален';
    } else {
        $error = 'Ошибка при удалении автора';
    }
}

// Обработка добавления/редактирования автора
if (isset($_POST['save_author'])) {
    $name = trim($_POST['name']);
    $bio = trim($_POST['bio']);
    $photo_url = trim($_POST['photo_url']);
    $author_id = isset($_POST['author_id']) ? (int)$_POST['author_id'] : 0;

    if (empty($name)) {
        $error = 'Имя автора обязательно для заполнения';
    } else {
        if ($author_id > 0) {
            // Обновление
            $stmt = $pdo->prepare("UPDATE authors SET name = ?, bio = ?, photo_url = ? WHERE id = ?");
            if ($stmt->execute([$name, $bio, $photo_url, $author_id])) {
                $success = 'Автор успешно обновлен';
            } else {
                $error = 'Ошибка при обновлении автора';
            }
        } else {
            // Добавление
            $stmt = $pdo->prepare("INSERT INTO authors (name, bio, photo_url) VALUES (?, ?, ?)");
            if ($stmt->execute([$name, $bio, $photo_url])) {
                $success = 'Автор успешно добавлен';
            } else {
                $error = 'Ошибка при добавлении автора';
            }
        }
    }
}

// Получение списка авторов
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
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
            <a href="/admin/authors.php" class="list-group-item list-group-item-action active">
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
            <h2>Управление авторами</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#authorModal">
                <i class="bi bi-plus-lg"></i> Добавить автора
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
                                <th>Фото</th>
                                <th>Имя</th>
                                <th>Биография</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($authors as $author): ?>
                            <tr>
                                <td>
                                    <img src="<?php echo htmlspecialchars($author['photo_url']); ?>" 
                                         alt="<?php echo htmlspecialchars($author['name']); ?>"
                                         class="img-thumbnail" style="width: 50px;">
                                </td>
                                <td><?php echo htmlspecialchars($author['name']); ?></td>
                                <td><?php echo htmlspecialchars($author['bio']); ?></td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-primary edit-author" 
                                                data-id="<?php echo $author['id']; ?>"
                                                data-name="<?php echo htmlspecialchars($author['name']); ?>"
                                                data-bio="<?php echo htmlspecialchars($author['bio']); ?>"
                                                data-photo="<?php echo htmlspecialchars($author['photo_url']); ?>"
                                                data-bs-toggle="modal" 
                                                data-bs-target="#authorModal">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <form method="POST" class="d-inline" 
                                              onsubmit="return confirm('Вы уверены, что хотите удалить этого автора?');">
                                            <input type="hidden" name="author_id" value="<?php echo $author['id']; ?>">
                                            <button type="submit" name="delete_author" class="btn btn-sm btn-danger">
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

<!-- Модальное окно для добавления/редактирования автора -->
<div class="modal fade" id="authorModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Автор</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="authorForm" method="POST">
                    <input type="hidden" name="author_id" id="author_id">
                    <div class="mb-3">
                        <label for="name" class="form-label">Имя</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="bio" class="form-label">Биография</label>
                        <textarea class="form-control" id="bio" name="bio" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="photo_url" class="form-label">URL фотографии</label>
                        <input type="url" class="form-control" id="photo_url" name="photo_url">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <button type="submit" form="authorForm" name="save_author" class="btn btn-primary">Сохранить</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Обработка редактирования автора
    document.querySelectorAll('.edit-author').forEach(button => {
        button.addEventListener('click', function() {
            document.getElementById('author_id').value = this.dataset.id;
            document.getElementById('name').value = this.dataset.name;
            document.getElementById('bio').value = this.dataset.bio;
            document.getElementById('photo_url').value = this.dataset.photo;
        });
    });

    // Сброс формы при открытии модального окна для добавления
    document.querySelector('[data-bs-target="#authorModal"]').addEventListener('click', function() {
        document.getElementById('authorForm').reset();
        document.getElementById('author_id').value = '';
    });
});
</script>

<?php require_once '../includes/footer.php'; ?>
