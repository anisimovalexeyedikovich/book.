<?php
require_once '../config.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Необходимо войти в систему']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Метод не поддерживается']);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($data['book_id']) || !isset($data['type'])) {
        throw new Exception('Неверные параметры запроса');
    }
    
    $book_id = (int)$data['book_id'];
    $type = $data['type']; // 'purchase' или 'rent'
    $rental_duration = isset($data['rental_duration']) ? $data['rental_duration'] : null;
    
    // Получаем информацию о книге
    $stmt = $pdo->prepare("SELECT * FROM books WHERE id = ?");
    $stmt->execute([$book_id]);
    $book = $stmt->fetch();
    
    if (!$book) {
        throw new Exception('Книга не найдена');
    }
    
    if ($book['stock_quantity'] <= 0) {
        throw new Exception('Книга отсутствует в наличии');
    }
    
    // Начинаем транзакцию
    $pdo->beginTransaction();
    
    // Определяем цену и даты аренды
    $total_price = 0;
    $rental_start_date = null;
    $rental_end_date = null;
    
    if ($type === 'purchase') {
        $total_price = $book['price'];
    } else if ($type === 'rent') {
        switch ($rental_duration) {
            case '2weeks':
                $total_price = $book['rental_price_2weeks'];
                $rental_start_date = date('Y-m-d');
                $rental_end_date = date('Y-m-d', strtotime('+2 weeks'));
                break;
            case '1month':
                $total_price = $book['rental_price_1month'];
                $rental_start_date = date('Y-m-d');
                $rental_end_date = date('Y-m-d', strtotime('+1 month'));
                break;
            case '3months':
                $total_price = $book['rental_price_3months'];
                $rental_start_date = date('Y-m-d');
                $rental_end_date = date('Y-m-d', strtotime('+3 months'));
                break;
            default:
                throw new Exception('Неверный период аренды');
        }
    } else {
        throw new Exception('Неверный тип заказа');
    }
    
    // Создаем заказ
    $stmt = $pdo->prepare("
        INSERT INTO orders (
            user_id, book_id, type, total_price, 
            rental_start_date, rental_end_date, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $stmt->execute([
        $_SESSION['user']['id'],
        $book_id,
        $type,
        $total_price,
        $rental_start_date,
        $rental_end_date
    ]);
    
    // Уменьшаем количество книг в наличии
    $stmt = $pdo->prepare("
        UPDATE books 
        SET stock_quantity = stock_quantity - 1 
        WHERE id = ? AND stock_quantity > 0
    ");
    $stmt->execute([$book_id]);
    
    // Подтверждаем транзакцию
    $pdo->commit();
    
    echo json_encode([
        'success' => true,
        'message' => $type === 'purchase' ? 'Книга успешно куплена' : 'Книга успешно арендована',
        'order_id' => $pdo->lastInsertId()
    ]);
    
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
