<?php
require_once '../config.php';

if (!isLoggedIn()) {
    header('HTTP/1.1 401 Unauthorized');
    exit('Unauthorized');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $new_password = trim($_POST['new_password']);
    
    try {
        // Начало транзакции
        $pdo->beginTransaction();
        
        // Проверка email на уникальность
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $_SESSION['user']['id']]);
        if ($stmt->rowCount() > 0) {
            throw new Exception('Этот email уже используется другим пользователем');
        }
        
        // Обновление данных пользователя
        if (!empty($new_password)) {
            // Если указан новый пароль, обновляем его тоже
            // В реальном проекте используйте password_hash()
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, password = ? 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $new_password, $_SESSION['user']['id']]);
        } else {
            // Обновляем только имя и email
            $stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ? 
                WHERE id = ?
            ");
            $stmt->execute([$full_name, $email, $_SESSION['user']['id']]);
        }
        
        // Подтверждение транзакции
        $pdo->commit();
        
        // Обновление данных в сессии
        $_SESSION['user']['full_name'] = $full_name;
        
        header('Location: /profile.php?success=1');
        exit;
        
    } catch (Exception $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        header('Location: /profile.php?error=' . urlencode($e->getMessage()));
        exit;
    }
}

header('Location: /profile.php');
exit;
