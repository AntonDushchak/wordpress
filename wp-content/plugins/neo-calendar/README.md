# Neo Calendar

Мой первый плагин на базе Neo Dashboard Framework.

## 🎯 Описание

Этот плагин демонстрирует основные возможности Neo Dashboard Framework:
- Создание sidebar групп и элементов
- Регистрация секций
- Создание виджетов
- Система уведомлений
- AJAX обработчики
- Кастомные CSS и JavaScript

## 🚀 Установка

1. Скопируйте папку `my-first-plugin` в `wp-content/plugins/`
2. Активируйте плагин в WordPress админке
3. Перейдите в Neo Dashboard
4. Найдите группу "Мой Плагин" в левом меню

## 📁 Структура

```
my-first-plugin/
├── my-first-plugin.php      # Главный файл плагина
├── assets/
│   ├── css/
│   │   └── style.css        # Стили плагина
│   └── js/
│       └── script.js        # JavaScript функционал
└── README.md                # Этот файл
```

## 🔧 Функциональность

### **Секции:**
- **Главная** - обзор плагина
- **Приветствие** - описание возможностей
- **Настройки** - форма настроек
- **Информация** - детали плагина

### **Виджет:**
- Статистика плагина
- Кнопка обновления
- Анимированные счетчики

### **Особенности:**
- ✅ Адаптивный дизайн
- ✅ Анимации и переходы
- ✅ AJAX формы
- ✅ Система уведомлений
- ✅ Bootstrap 5 стили

## 🎨 Кастомизация

### **Изменение названия:**
Отредактируйте заголовок в `my-first-plugin.php`:
```php
/**
 * Plugin Name: Мой Кастомный Плагин
 * Description: Описание вашего плагина
 * Version: 1.0.0
 * Author: Ваше имя
 */
```

### **Добавление новых секций:**
В функции `neo_dashboard_init` добавьте:
```php
$sections['new-section'] = [
    'label' => 'Новая секция',
    'icon'  => 'bi-plus-circle',
    'pos'   => 29,
];
```

### **Изменение стилей:**
Отредактируйте `assets/css/style.css`

### **Добавление JavaScript:**
Отредактируйте `assets/js/script.js`

## 🔌 Хуки Neo Dashboard

### **Регистрация sidebar элемента:**
```php
do_action('neo_dashboard_register_sidebar_item', [
    'slug'     => 'my-slug',
    'label'    => 'Мой элемент',
    'icon'     => 'bi-star',
    'url'      => '/neo-dashboard/my-slug',
    'position' => 30,
]);
```

### **Регистрация секции:**
```php
do_action('neo_dashboard_register_section', [
    'slug'     => 'my-section',
    'label'    => 'Моя секция',
    'callback' => 'my_section_function',
]);
```

### **Регистрация виджета:**
```php
do_action('neo_dashboard_register_widget', [
    'id'       => 'my-widget',
    'label'    => 'Мой виджет',
    'callback' => 'my_widget_function',
]);
```

### **Регистрация уведомления:**
```php
do_action('neo_dashboard_register_notification', [
    'id'          => 'my-notification',
    'type'        => 'success',
    'message'     => 'Мое уведомление',
    'dismissible' => true,
]);
```

## 🛠️ Разработка

### **Добавление новых функций:**
1. Создайте функцию в главном файле
2. Зарегистрируйте через хуки Neo Dashboard
3. Добавьте стили в CSS
4. Добавьте JavaScript функционал

### **AJAX обработчики:**
```php
add_action('wp_ajax_my_action', function() {
    // Проверка nonce
    if (!wp_verify_nonce($_POST['nonce'], 'my_nonce')) {
        wp_send_json_error('Security check failed');
    }
    
    // Ваша логика
    wp_send_json_success(['message' => 'Success!']);
});
```

### **JavaScript интеграция:**
```javascript
$.post(neoCalendarAjax.ajaxurl, {
    action: 'neo_calendar_action',
    nonce: neoCalendarAjax.nonce,
    data: formData
}, function(response) {
    if (response.success) {
        console.log('Success:', response.data);
    }
});
```

## 📱 Адаптивность

Плагин автоматически адаптируется под:
- Desktop (1200px+)
- Tablet (768px - 1199px)
- Mobile (< 768px)

## 🎨 Стилизация

### **CSS переменные:**
```css
:root {
    --my-plugin-primary: #007cba;
    --my-plugin-secondary: #6c757d;
    --my-plugin-success: #28a745;
}
```

### **Bootstrap 5:**
Все компоненты используют Bootstrap 5 классы

### **Иконки:**
Используются Bootstrap Icons (bi-*)

## 🚀 Следующие шаги

1. **Изучите код** - поймите как работает каждый компонент
2. **Измените названия** - адаптируйте под свои нужды
3. **Добавьте секции** - создайте нужный функционал
4. **Кастомизируйте стили** - сделайте уникальный дизайн
5. **Добавьте логику** - реализуйте бизнес-логику

## 📚 Полезные ссылки

- [Neo Dashboard Documentation](docs/)
- [WordPress Plugin Handbook](https://developer.wordpress.org/plugins/)
- [Bootstrap 5 Documentation](https://getbootstrap.com/docs/5.3/)
- [Bootstrap Icons](https://icons.getbootstrap.com/)

## 🤝 Поддержка

Если у вас есть вопросы:
1. Изучите код плагина
2. Посмотрите примеры в `neo-dashboard-examples`
3. Изучите архитектуру в `neo-worker-is`
4. Создайте Issue в GitHub

---

**Удачи в создании вашего плагина! 🎉**
