<?php
/**
 * Скрипт для миграции поля is_active
 * Запустите этот файл один раз через браузер: /wp-content/plugins/job-board-integration/migrate_is_active.php
 */

// Подключаем WordPress
require_once('../../../wp-load.php');

if (!current_user_can('manage_options')) {
    die('Недостаточно прав для выполнения миграции');
}

echo "<h1>Миграция поля is_active</h1>";

try {
    // Выполняем миграцию
    \NeoJobBoard\Database::migrate_add_is_active_field();
    
    echo "<p style='color: green;'>✅ Миграция успешно выполнена!</p>";
    echo "<p>Поле is_active добавлено в таблицу заявок.</p>";
    
    // Проверяем результат
    global $wpdb;
    $table_applications = $wpdb->prefix . 'neo_job_board_applications';
    
    $column_info = $wpdb->get_results("SHOW COLUMNS FROM {$table_applications} LIKE 'is_active'");
    
    if (!empty($column_info)) {
        echo "<p style='color: green;'>✅ Поле is_active существует в таблице.</p>";
        
        // Показываем статистику
        $total_applications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_applications}");
        $active_applications = $wpdb->get_var("SELECT COUNT(*) FROM {$table_applications} WHERE is_active = 1");
        
        echo "<p>Всего заявок: {$total_applications}</p>";
        echo "<p>Активных заявок: {$active_applications}</p>";
    } else {
        echo "<p style='color: red;'>❌ Поле is_active не найдено в таблице.</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Ошибка миграции: " . $e->getMessage() . "</p>";
}

echo "<p><a href='/wp-admin/admin.php?page=neo-job-board'>← Вернуться к плагину</a></p>";
?>
