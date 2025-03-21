<?php
require_once 'includes/header.php';

$error = '';
$success = '';

if (isset($_POST['login'])) {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = 'Пожалуйста, заполните все поля';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && $password === $user['password']) { // В реальном проекте используйте password_hash() и password_verify()
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'],
                'is_admin' => $user['is_admin']
            ];
            header('Location: /');
            exit;
        } else {
            $error = 'Неверное имя пользователя или пароль';
        }
    }
}
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="card-title text-center mb-4">Вход в систему</h2>
                
                <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>

                <?php if ($success): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="username" class="form-label">Имя пользователя</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Пароль</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>

                    <button type="submit" name="login" class="btn btn-primary w-100">Войти</button>
                </form>

                <div class="text-center mt-3">
                    <p class="mb-0">Нет аккаунта? <a href="/register.php">Зарегистрируйтесь</a></p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
