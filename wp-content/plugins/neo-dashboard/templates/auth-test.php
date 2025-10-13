<?php
/**
 * Тестовая страница для проверки системы аутентификации
 */

// Предотвращаем прямой доступ
if (!defined('ABSPATH')) {
    exit;
}

use NeoDashboard\Core\AccessControl;

// Получаем информацию о текущем пользователе
$user_info = AccessControl::getCurrentUserInfo();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Neo Dashboard - Auth Test</title>
    <?php wp_head(); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body <?php body_class(); ?>>

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0">
                        <i class="fas fa-shield-alt me-2"></i>
                        Neo Dashboard - Тест Аутентификации
                    </h3>
                </div>
                <div class="card-body">
                    
                    <!-- Статус пользователя -->
                    <div class="mb-4">
                        <h5>Статус пользователя:</h5>
                        <?php if ($user_info['logged_in']): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Авторизован</strong>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Не авторизован</strong>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Информация о пользователе -->
                    <?php if ($user_info['logged_in']): ?>
                        <div class="mb-4">
                            <h5>Информация о пользователе:</h5>
                            <table class="table table-bordered">
                                <tr>
                                    <td><strong>ID:</strong></td>
                                    <td><?php echo esc_html($user_info['user_id']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Логин:</strong></td>
                                    <td><?php echo esc_html($user_info['user_login'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Отображаемое имя:</strong></td>
                                    <td><?php echo esc_html($user_info['display_name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo esc_html($user_info['user_email'] ?? 'N/A'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Роли:</strong></td>
                                    <td>
                                        <?php if (!empty($user_info['roles'])): ?>
                                            <?php foreach ($user_info['roles'] as $role): ?>
                                                <span class="badge bg-info me-1"><?php echo esc_html($role); ?></span>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <span class="text-muted">Нет ролей</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <!-- Проверка доступа к Neo Dashboard -->
                        <div class="mb-4">
                            <h5>Проверка доступа:</h5>
                            <?php if (AccessControl::canAccessNeoDashboard()): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-unlock me-2"></i>
                                    <strong>Доступ к Neo Dashboard разрешен</strong>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-lock me-2"></i>
                                    <strong>Доступ к Neo Dashboard запрещен</strong>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Neo роли -->
                        <div class="mb-4">
                            <h5>Neo роли:</h5>
                            <?php
                            $hasNeoEditor = in_array('neo_editor', $user_info['roles']);
                            $hasNeoMitarbeiter = in_array('neo_mitarbeiter', $user_info['roles']);
                            $isAdmin = in_array('administrator', $user_info['roles']);
                            ?>
                            
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="card <?php echo $hasNeoEditor ? 'bg-success text-white' : 'bg-light'; ?>">
                                        <div class="card-body text-center">
                                            <i class="fas fa-edit mb-2"></i>
                                            <h6>Neo Editor</h6>
                                            <?php echo $hasNeoEditor ? 'Есть' : 'Нет'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card <?php echo $hasNeoMitarbeiter ? 'bg-success text-white' : 'bg-light'; ?>">
                                        <div class="card-body text-center">
                                            <i class="fas fa-users mb-2"></i>
                                            <h6>Neo Mitarbeiter</h6>
                                            <?php echo $hasNeoMitarbeiter ? 'Есть' : 'Нет'; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card <?php echo $isAdmin ? 'bg-warning text-dark' : 'bg-light'; ?>">
                                        <div class="card-body text-center">
                                            <i class="fas fa-crown mb-2"></i>
                                            <h6>Administrator</h6>
                                            <?php echo $isAdmin ? 'Есть' : 'Нет'; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Действия -->
                    <div class="mb-4">
                        <h5>Действия:</h5>
                        <div class="d-flex gap-2 flex-wrap">
                            <?php if ($user_info['logged_in']): ?>
                                <a href="<?php echo home_url('/neo-dashboard'); ?>" class="btn btn-primary">
                                    <i class="fas fa-tachometer-alt me-1"></i>
                                    Neo Dashboard
                                </a>
                                <a href="<?php echo wp_logout_url(home_url()); ?>" class="btn btn-secondary">
                                    <i class="fas fa-sign-out-alt me-1"></i>
                                    Выйти
                                </a>
                            <?php else: ?>
                                <a href="<?php echo wp_login_url(home_url('/neo-dashboard')); ?>" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-1"></i>
                                    Войти
                                </a>
                            <?php endif; ?>
                            
                            <a href="<?php echo admin_url(); ?>" class="btn btn-outline-primary">
                                <i class="fas fa-cog me-1"></i>
                                WordPress Admin
                            </a>
                        </div>
                    </div>

                    <!-- Debug информация -->
                    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                        <div class="mb-4">
                            <h5>Debug информация:</h5>
                            <div class="card">
                                <div class="card-body">
                                    <h6>$_SERVER:</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>REQUEST_URI:</strong> <?php echo esc_html($_SERVER['REQUEST_URI'] ?? 'N/A'); ?></li>
                                        <li><strong>HTTP_HOST:</strong> <?php echo esc_html($_SERVER['HTTP_HOST'] ?? 'N/A'); ?></li>
                                        <li><strong>USER_AGENT:</strong> <?php echo esc_html(substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 50)); ?>...</li>
                                    </ul>
                                    
                                    <h6 class="mt-3">WordPress:</h6>
                                    <ul class="list-unstyled">
                                        <li><strong>Home URL:</strong> <?php echo esc_html(home_url()); ?></li>
                                        <li><strong>Admin URL:</strong> <?php echo esc_html(admin_url()); ?></li>
                                        <li><strong>Current URL:</strong> <?php echo esc_html(home_url($_SERVER['REQUEST_URI'] ?? '')); ?></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
<?php wp_footer(); ?>
</body>
</html>