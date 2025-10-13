# Neo Dashboard Authentication System

## 🔐 Система аутентификации и авторизации

Комплексная система безопасности для Neo Dashboard с ролевым контролем доступа и многоуровневой защитой.

## 🚀 Быстрый старт

### 1. Активация системы

После установки плагина система аутентификации активируется автоматически:

```php
// В Bootstrap.php уже настроено:
AccessControl::init();
SecurityEnforcer::init();
```

### 2. Создание пользователей с Neo ролями

```php
// Создание Neo Editor
$user_id = wp_create_user('editor', 'secure_password', 'editor@company.com');
$user = new WP_User($user_id);
$user->set_role('neo_editor');

// Создание Neo Mitarbeiter  
$user_id = wp_create_user('mitarbeiter', 'secure_password', 'mitarbeiter@company.com');
$user = new WP_User($user_id);
$user->set_role('neo_mitarbeiter');
```

### 3. Тестирование

Добавьте шорткод на любую страницу для тестирования:
```
[neo_auth_test]
```

## 📋 Функционал

### ✅ Контроль доступа
- **Неавторизованные пользователи** → редирект на `wp-login.php`
- **После входа** → редирект на `/neo-dashboard` 
- **Neo роли** → доступ только к страницам с префиксом `/neo-dashboard`
- **Администраторы** → полный доступ

### ✅ Роли пользователей
- `neo_editor` - редактирование контента Neo Dashboard
- `neo_mitarbeiter` - работа с Neo Dashboard в рамках роли
- `administrator` - полный доступ без ограничений

### ✅ Безопасность
- Защита от прямого доступа к файлам
- .htaccess правила безопасности
- Блокировка запрещенных URL
- Логирование попыток несанкционированного доступа

## 🔧 API

### AccessControl

```php
// Проверка аутентификации
AccessControl::checkAuthentication();

// Проверка доступа к Neo страницам
AccessControl::checkNeoPageAccess();

// Может ли пользователь получить доступ к Neo Dashboard
AccessControl::canAccessNeoDashboard();

// Получение информации о текущем пользователе
$user_info = AccessControl::getCurrentUserInfo();
```

### Вспомогательные функции

```php
// Проверить доступ к Neo Dashboard
if (can_access_neo_dashboard()) {
    // Пользователь может получить доступ
}

// Проверить наличие Neo роли
if (has_neo_role()) {
    // У пользователя есть одна из Neo ролей
}

// Получить текущую Neo роль
$role = get_current_neo_role();
```

## 📁 Структура файлов

```
neo-dashboard/
├── src/
│   ├── AccessControl.php      # Основной контроль доступа
│   └── SecurityEnforcer.php   # Дополнительная безопасность
├── templates/
│   ├── access-denied.php      # Страница отказа в доступе
│   └── auth-test.php          # Тестовая страница
└── docs/
    └── AUTHENTICATION_TESTING.md  # Руководство по тестированию
```

## 🛡️ Безопасность

### .htaccess защита

Автоматически создаются правила для:
- Блокировки прямого доступа к `wp-config.php`
- Защиты `readme.html` и `license.txt`
- Ограничения доступа к PHP файлам плагинов

### Дополнительные меры

- Валидация всех запросов к Neo Dashboard
- Проверка ролей на каждом обращении
- Логирование подозрительной активности
- Защита от CSRF атак (в разработке)

## 🔍 Тестирование

### Автоматические тесты

```bash
# Запуск тестов (требует PHPUnit)
composer test

# Тесты безопасности
composer security-test
```

### Ручное тестирование

1. **Неавторизованный доступ**: перейдите на `/neo-dashboard` без входа
2. **Neo Editor**: войдите и попробуйте `/wp-admin`
3. **Администратор**: убедитесь в полном доступе

Подробно в [AUTHENTICATION_TESTING.md](docs/AUTHENTICATION_TESTING.md)

## ⚙️ Настройка

### Изменение URL после входа

```php
// В AccessControl.php
public static function loginRedirect(): string
{
    return home_url('/custom-dashboard'); // Вместо /neo-dashboard
}
```

### Добавление новых ролей

```php
// В functions.php плагина
add_action('init', function() {
    add_role('custom_neo_role', 'Custom Neo Role', [
        'read' => true,
        'access_neo_dashboard' => true
    ]);
});
```

### Кастомная страница отказа

Создайте файл `templates/custom-access-denied.php` и обновите путь в `AccessControl::showAccessDeniedPage()`

## 🚨 Устранение неполадок

### Бесконечные редиректы
Проверьте настройки роутера Neo Dashboard и убедитесь, что URL `/neo-dashboard` правильно обрабатывается.

### Пользователи не могут войти
1. Убедитесь что роли созданы: `Roles::addRoles()`
2. Проверьте назначение ролей пользователям
3. Включите WP_DEBUG для отладки

### .htaccess не работает
1. Проверьте поддержку .htaccess сервером
2. Убедитесь в правах на запись
3. Проверьте включение mod_rewrite

## 🔄 Обновления

### v1.0.0 (текущая)
- ✅ Базовая система аутентификации
- ✅ Ролевой контроль доступа  
- ✅ .htaccess защита
- ✅ Шаблоны страниц

### Планируется v1.1.0
- 🔄 CSRF защита
- 🔄 Two-factor authentication
- 🔄 Расширенное логирование
- 🔄 API токены

## 📞 Поддержка

Для получения помощи:
1. Проверьте документацию в `/docs`
2. Включите режим отладки WP_DEBUG
3. Проверьте логи в `/wp-content/debug.log`

## 📄 Лицензия

Данная система распространяется под лицензией GPL v2 или выше, совместимой с WordPress.