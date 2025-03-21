<?php
require_once 'includes/header.php';

// Получение параметров фильтрации
$category_id = isset($_GET['category']) ? (int)$_GET['category'] : null;
$author_id = isset($_GET['author']) ? (int)$_GET['author'] : null;
$year = isset($_GET['year']) ? (int)$_GET['year'] : null;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'title_asc';

// Формирование SQL запроса
$sql = "SELECT b.*, a.name as author_name, c.name as category_name 
        FROM books b 
        JOIN authors a ON b.author_id = a.id 
        JOIN categories c ON b.category_id = c.id 
        WHERE b.is_available = 1";
$params = [];

if ($category_id) {
    $sql .= " AND b.category_id = ?";
    $params[] = $category_id;
}

if ($author_id) {
    $sql .= " AND b.author_id = ?";
    $params[] = $author_id;
}

if ($year) {
    $sql .= " AND b.publication_year = ?";
    $params[] = $year;
}

if ($search) {
    $sql .= " AND (b.title LIKE ? OR a.name LIKE ? OR c.name LIKE ?)";
    $searchParam = "%$search%";
    $params[] = $searchParam;
    $params[] = $searchParam;
    $params[] = $searchParam;
}

// Сортировка
switch ($sort) {
    case 'price_asc':
        $sql .= " ORDER BY b.price ASC";
        break;
    case 'price_desc':
        $sql .= " ORDER BY b.price DESC";
        break;
    case 'year_desc':
        $sql .= " ORDER BY b.publication_year DESC";
        break;
    case 'year_asc':
        $sql .= " ORDER BY b.publication_year ASC";
        break;
    default:
        $sql .= " ORDER BY b.title ASC";
}

// Получение книг
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll();

// Получение списка категорий и авторов для фильтров
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
$authors = $pdo->query("SELECT * FROM authors ORDER BY name")->fetchAll();
$years = $pdo->query("SELECT DISTINCT publication_year FROM books ORDER BY publication_year DESC")->fetchAll();
?>

<div class="row">
    <!-- Фильтры -->
    <div class="col-md-3 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Фильтры</h5>
                <form id="filterForm" method="GET" action="/catalog.php">
                    <div class="mb-3">
                        <label for="search" class="form-label">Поиск</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="category" class="form-label">Категория</label>
                        <select class="form-select" id="category" name="category">
                            <option value="">Все категории</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $category_id == $cat['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="author" class="form-label">Автор</label>
                        <select class="form-select" id="author" name="author">
                            <option value="">Все авторы</option>
                            <?php foreach ($authors as $auth): ?>
                            <option value="<?php echo $auth['id']; ?>" <?php echo $author_id == $auth['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($auth['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="year" class="form-label">Год издания</label>
                        <select class="form-select" id="year" name="year">
                            <option value="">Все годы</option>
                            <?php foreach ($years as $y): ?>
                            <option value="<?php echo $y['publication_year']; ?>" <?php echo $year == $y['publication_year'] ? 'selected' : ''; ?>>
                                <?php echo $y['publication_year']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="sort" class="form-label">Сортировка</label>
                        <select class="form-select" id="sort" name="sort">
                            <option value="title_asc" <?php echo $sort == 'title_asc' ? 'selected' : ''; ?>>По названию (А-Я)</option>
                            <option value="price_asc" <?php echo $sort == 'price_asc' ? 'selected' : ''; ?>>По цене (возрастание)</option>
                            <option value="price_desc" <?php echo $sort == 'price_desc' ? 'selected' : ''; ?>>По цене (убывание)</option>
                            <option value="year_desc" <?php echo $sort == 'year_desc' ? 'selected' : ''; ?>>Сначала новые</option>
                            <option value="year_asc" <?php echo $sort == 'year_asc' ? 'selected' : ''; ?>>Сначала старые</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">Применить фильтры</button>
                    <a href="/catalog.php" class="btn btn-outline-secondary w-100 mt-2">Сбросить фильтры</a>
                </form>
            </div>
        </div>
    </div>

    <!-- Список книг -->
    <div class="col-md-9">
        <h2 class="mb-4">Каталог книг</h2>
        <?php if (empty($books)): ?>
        <div class="alert alert-info">
            По вашему запросу ничего не найдено. Попробуйте изменить параметры поиска.
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($books as $book): ?>
            <div class="col-md-4">
                <div class="card book-card h-100">
                    <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($book['title']); ?>">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                        <p class="card-text text-muted"><?php echo htmlspecialchars($book['author_name']); ?></p>
                        <span class="badge bg-primary category-badge"><?php echo htmlspecialchars($book['category_name']); ?></span>
                        <p class="card-text mt-2">
                            <strong>Цена: </strong><?php echo formatPrice($book['price']); ?><br>
                            <small class="text-muted">Год издания: <?php echo $book['publication_year']; ?></small>
                        </p>
                        <a href="/book.php?id=<?php echo $book['id']; ?>" class="btn btn-primary w-100">Подробнее</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
