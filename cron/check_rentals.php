<?php
require_once '../config.php';

// Получение заказов аренды, которые скоро закончатся (за 3 дня до окончания)
$stmt = $pdo->prepare("
    SELECT o.*, u.email, u.username, b.title 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    JOIN books b ON o.book_id = b.id 
    WHERE o.type = 'rent' 
    AND o.status = 'completed'
    AND o.rental_end_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 3 DAY)
");
$stmt->execute();
$expiring_rentals = $stmt->fetchAll();

// Отправка уведомлений
foreach ($expiring_rentals as $rental) {
    $days_left = (strtotime($rental['rental_end_date']) - time()) / (60 * 60 * 24);
    $days_left = ceil($days_left);
    
    $subject = 'Напоминание о сроке аренды книги';
    $message = "
        Уважаемый(ая) {$rental['username']},

        Напоминаем вам, что срок аренды книги \"{$rental['title']}\" истекает через {$days_left} " . 
        ($days_left == 1 ? 'день' : ($days_left < 5 ? 'дня' : 'дней')) . ".

        Пожалуйста, не забудьте вернуть книгу вовремя или продлить срок аренды.

        С уважением,
        Команда книжного магазина
    ";
    
    // В реальном проекте здесь будет код отправки email
    // mail($rental['email'], $subject, $message);
    
    // Для демонстрации просто записываем в лог
    file_put_contents(
        __DIR__ . '/rental_notifications.log',
        date('Y-m-d H:i:s') . " - Отправлено уведомление пользователю {$rental['username']} о книге {$rental['title']}\n",
        FILE_APPEND
    );
}
