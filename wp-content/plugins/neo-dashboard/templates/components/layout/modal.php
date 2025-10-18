<?php
/**
 * Modal Component
 * Базовый компонент модального окна
 */

if (!defined('ABSPATH')) exit;

/**
 * @param array $args {
 *     @type string $id          ID модального окна (обязательно)
 *     @type string $title       Заголовок модального окна
 *     @type string $content     HTML содержимое (опционально, если используется callback)
 *     @type callable $callback  Функция для генерации содержимого
 *     @type string $size        Размер: 'sm', 'lg', 'xl' или пустая строка для обычного
 *     @type bool $backdrop      Закрывать ли при клике вне модала (по умолчанию true)
 *     @type bool $keyboard      Закрывать ли по ESC (по умолчанию true)
 *     @type array $footer_buttons Массив кнопок для футера
 * }
 */

$id = $args['id'] ?? 'modal-' . uniqid();
$title = $args['title'] ?? '';
$content = $args['content'] ?? '';
$callback = $args['callback'] ?? null;
$size = $args['size'] ?? '';
$backdrop = $args['backdrop'] ?? true;
$keyboard = $args['keyboard'] ?? true;
$footer_buttons = $args['footer_buttons'] ?? [];

$size_class = $size ? 'modal-' . $size : '';
$modal_attrs = [];
if (!$backdrop) $modal_attrs[] = 'data-bs-backdrop="static"';
if (!$keyboard) $modal_attrs[] = 'data-bs-keyboard="false"';
?>

<div class="modal fade" id="<?php echo esc_attr($id); ?>" tabindex="-1" <?php echo implode(' ', $modal_attrs); ?>>
    <div class="modal-dialog <?php echo esc_attr($size_class); ?>">
        <div class="modal-content">
            <?php if ($title): ?>
            <div class="modal-header">
                <h1 class="modal-title fs-5"><?php echo esc_html($title); ?></h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <?php endif; ?>
            
            <div class="modal-body">
                <?php
                if (is_callable($callback)) {
                    call_user_func($callback);
                } else {
                    echo $content;
                }
                ?>
            </div>
            
            <?php if (!empty($footer_buttons)): ?>
            <div class="modal-footer">
                <?php foreach ($footer_buttons as $button): ?>
                    <button type="button" 
                            class="btn <?php echo esc_attr($button['class'] ?? 'btn-secondary'); ?>"
                            <?php if (isset($button['dismiss']) && $button['dismiss']): ?>data-bs-dismiss="modal"<?php endif; ?>
                            <?php if (isset($button['onclick'])): ?>onclick="<?php echo esc_attr($button['onclick']); ?>"<?php endif; ?>>
                        <?php echo esc_html($button['text']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>