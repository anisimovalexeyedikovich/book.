<?php
require_once 'includes/header.php';

// Получение популярных книг
$stmt = $pdo->query("SELECT b.*, a.name as author_name, c.name as category_name 
                     FROM books b 
                     JOIN authors a ON b.author_id = a.id 
                     JOIN categories c ON b.category_id = c.id 
                     WHERE b.is_available = 1 
                     ORDER BY RAND() 
                     LIMIT 6");
$popular_books = $stmt->fetchAll();

// Получение категорий
$stmt = $pdo->query("SELECT * FROM categories");
$categories = $stmt->fetchAll();
?>

<div class="hero-section bg-primary text-white py-5 mb-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6 fade-in">
                <h1 class="display-4">Добро пожаловать в книжный магазин</h1>
                <p class="lead">Откройте для себя мир увлекательных историй и знаний</p>
                <a href="/catalog.php" class="btn btn-light btn-lg">Перейти в каталог</a>
            </div>
            <div class="col-md-6 text-center fade-in">
                <img src="/assets/images/hero-books.svg" alt="Books" class="img-fluid" style="max-height: 300px;">
            </div>
        </div>
    </div>
</div>

<div class="container">
    <section class="popular-books mb-5 fade-in">
        <h2 class="text-center mb-4">Популярные книги</h2>
        <div class="row row-cols-2 row-cols-md-3 row-cols-lg-5 g-4 justify-content-center">
            <?php foreach ($popular_books as $book): ?>
            <div class="col">
                <div class="book-card h-100">
                    <div class="book-card-inner">
                        <div class="book-cover">
                            <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" 
                                 class="card-img-top" 
                                 alt="<?php echo htmlspecialchars($book['title']); ?>">
                            <div class="book-overlay">
                                <a href="/book.php?id=<?php echo $book['id']; ?>" class="btn btn-light btn-sm">Подробнее</a>
                            </div>
                        </div>
                        <div class="book-info">
                            <h5 class="book-title"><?php echo htmlspecialchars($book['title']); ?></h5>
                            <p class="book-author"><?php echo htmlspecialchars($book['author_name']); ?></p>
                            <div class="book-category">
                                <span class="badge bg-primary"><?php echo htmlspecialchars($book['category_name']); ?></span>
                            </div>
                            <div class="book-price">
                                <strong><?php echo formatPrice($book['price']); ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="mb-5 fade-in">
        <h2 class="text-center mb-4">Категории книг</h2>
        <div class="row g-4">
            <?php foreach ($categories as $category): ?>
            <div class="col-md-4">
                <a href="/catalog.php?category=<?php echo $category['id']; ?>" class="text-decoration-none">
                    <div class="card category-card h-100">
                        <div class="card-body text-center">
                            <i class="bi bi-book display-4 text-primary"></i>
                            <h3 class="card-title h5 mt-3"><?php echo htmlspecialchars($category['name']); ?></h3>
                            <p class="card-text text-muted"><?php echo htmlspecialchars($category['description']); ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="features-section py-5 fade-in">
        <h2 class="text-center mb-4">Наши преимущества</h2>
        <div class="row g-4">
            <div class="col-md-4 text-center">
                <i class="bi bi-book display-4 text-primary mb-3"></i>
                <h3 class="h5">Большой выбор книг</h3>
                <p>Тысячи книг различных жанров и авторов</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-clock display-4 text-primary mb-3"></i>
                <h3 class="h5">Удобная аренда</h3>
                <p>Возможность арендовать книги на разные сроки</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-truck display-4 text-primary mb-3"></i>
                <h3 class="h5">Быстрая доставка</h3>
                <p>Доставка в любую точку России</p>
            </div>
        </div>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
