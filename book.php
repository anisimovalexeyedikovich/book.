<?php
require_once 'includes/header.php';

$book_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$book_id) {
    header('Location: /catalog.php');
    exit;
}

// Получение информации о книге
$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name, a.bio as author_bio, c.name as category_name 
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    JOIN categories c ON b.category_id = c.id 
    WHERE b.id = ?
");
$stmt->execute([$book_id]);
$book = $stmt->fetch();

if (!$book) {
    header('Location: /catalog.php');
    exit;
}

// Получение похожих книг
$stmt = $pdo->prepare("
    SELECT b.*, a.name as author_name 
    FROM books b 
    JOIN authors a ON b.author_id = a.id 
    WHERE b.category_id = ? AND b.id != ? 
    LIMIT 4
");
$stmt->execute([$book['category_id'], $book_id]);
$similar_books = $stmt->fetchAll();
?>

<div class="row mb-5">
    <div class="col-md-4">
        <img src="<?php echo htmlspecialchars($book['cover_image']); ?>" class="img-fluid rounded shadow" alt="<?php echo htmlspecialchars($book['title']); ?>">
    </div>
    <div class="col-md-8">
        <h1 class="mb-3"><?php echo htmlspecialchars($book['title']); ?></h1>
        <p class="lead">Автор: <a href="/catalog.php?author=<?php echo $book['author_id']; ?>"><?php echo htmlspecialchars($book['author_name']); ?></a></p>
        
        <div class="mb-4">
            <span class="badge bg-primary category-badge">
                <a href="/catalog.php?category=<?php echo $book['category_id']; ?>" class="text-white text-decoration-none">
                    <?php echo htmlspecialchars($book['category_name']); ?>
                </a>
            </span>
            <span class="badge bg-secondary">Год: <?php echo $book['publication_year']; ?></span>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title">Описание</h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($book['description'])); ?></p>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-4">Варианты приобретения</h5>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Купить</h6>
                                <p class="display-6 text-primary mb-3"><?php echo formatPrice($book['price']); ?></p>
                                <?php if ($book['stock_quantity'] > 0): ?>
                                <button class="btn btn-primary buy-button w-100" data-book-id="<?php echo $book['id']; ?>" data-action="purchase" id="purchaseButton">
                                    Купить сейчас
                                </button>
                                <?php else: ?>
                                <button class="btn btn-secondary w-100" disabled>Нет в наличии</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-body">
                                <h6 class="card-title">Арендовать</h6>
                                <div class="mb-3">
                                    <select class="form-select mb-3" id="rentDuration">
                                        <option value="2weeks">2 недели - <?php echo formatPrice($book['rental_price_2weeks']); ?></option>
                                        <option value="1month">1 месяц - <?php echo formatPrice($book['rental_price_1month']); ?></option>
                                        <option value="3months">3 месяца - <?php echo formatPrice($book['rental_price_3months']); ?></option>
                                    </select>
                                </div>
                                <?php if ($book['stock_quantity'] > 0): ?>
                                <button class="btn btn-outline-primary rent-button w-100" id="rentButton">
                                    Арендовать
                                </button>
                                <?php else: ?>
                                <button class="btn btn-outline-secondary w-100" disabled>
                                    Нет в наличии
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h5 class="card-title">Об авторе</h5>
                <p class="card-text"><?php echo nl2br(htmlspecialchars($book['author_bio'])); ?></p>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($similar_books)): ?>
<section class="mb-5">
    <h3 class="mb-4">Похожие книги</h3>
    <div class="row g-4">
        <?php foreach ($similar_books as $similar): ?>
        <div class="col-md-3">
            <div class="card book-card h-100">
                <img src="<?php echo htmlspecialchars($similar['cover_image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($similar['title']); ?>">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($similar['title']); ?></h5>
                    <p class="card-text text-muted"><?php echo htmlspecialchars($similar['author_name']); ?></p>
                    <p class="card-text">
                        <strong>Цена: </strong><?php echo formatPrice($similar['price']); ?>
                    </p>
                    <a href="/book.php?id=<?php echo $similar['id']; ?>" class="btn btn-primary w-100">Подробнее</a>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- Контейнер для уведомлений -->
<div id="toastContainer" class="toast-container position-fixed bottom-0 end-0 p-3"></div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const isLoggedIn = <?php echo isLoggedIn() ? 'true' : 'false'; ?>;
    const bookId = <?php echo $book['id']; ?>;

    // Обработка покупки книги
    document.getElementById('purchaseButton').addEventListener('click', function() {
        processOrder('purchase');
    });

    // Обработка аренды книги
    document.getElementById('rentButton').addEventListener('click', function() {
        const duration = document.getElementById('rentDuration').value;
        processOrder('rent', duration);
    });

    // Функция обработки заказа
    function processOrder(type, rentalDuration = null) {
        if (!isLoggedIn) {
            window.location.href = '/login.php';
            return;
        }

        const data = {
            book_id: bookId,
            type: type
        };

        if (rentalDuration) {
            data.rental_duration = rentalDuration;
        }

        fetch('/api/process_order.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                setTimeout(() => {
                    window.location.href = '/profile.php';
                }, 2000);
            } else {
                showAlert('danger', data.error || 'Произошла ошибка при обработке заказа');
            }
        })
        .catch(error => {
            showAlert('danger', 'Произошла ошибка при обработке заказа');
            console.error('Error:', error);
        });
    }

    // Функция отображения уведомлений
    function showAlert(type, message) {
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed top-0 start-50 translate-middle-x mt-3`;
        alertDiv.style.zIndex = '1050';
        alertDiv.innerHTML = `
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        document.body.appendChild(alertDiv);

        setTimeout(() => {
            alertDiv.remove();
        }, 5000);
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
