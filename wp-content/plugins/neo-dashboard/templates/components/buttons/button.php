<?php
/**
 * Button Component
 * Базовый компонент кнопки
 */

if (!defined('ABSPATH')) exit;

/**
 * @param array $args {
 *     @type string $text        Текст кнопки (обязательно)
 *     @type string $type        Тип кнопки: 'button', 'submit', 'reset'
 *     @type string $style       Стиль: 'primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'
 *     @type string $size        Размер: 'sm', 'lg' или пустая строка
 *     @type string $icon        CSS класс иконки (Bootstrap Icons)
 *     @type string $onclick     JavaScript код для onclick
 *     @type string $href        Ссылка (если нужна кнопка-ссылка)
 *     @type string $target      Target для ссылки (_blank, _self и т.д.)
 *     @type string $class       Дополнительные CSS классы
 *     @type string $id          ID элемента
 *     @type bool $disabled      Отключена ли кнопка
 *     @type bool $outline       Использовать outline стиль
 *     @type array $data         Data атрибуты (например: ['toggle' => 'modal', 'target' => '#myModal'])
 * }
 */

$text = $args['text'] ?? 'Button';
$type = $args['type'] ?? 'button';
$style = $args['style'] ?? 'primary';
$size = $args['size'] ?? '';
$icon = $args['icon'] ?? '';
$onclick = $args['onclick'] ?? '';
$href = $args['href'] ?? '';
$target = $args['target'] ?? '';
$class = $args['class'] ?? '';
$id = $args['id'] ?? '';
$disabled = $args['disabled'] ?? false;
$outline = $args['outline'] ?? false;
$data = $args['data'] ?? [];

// Формируем CSS классы
$css_classes = ['btn'];
$style_prefix = $outline ? 'btn-outline-' : 'btn-';
$css_classes[] = $style_prefix . $style;

if ($size) {
    $css_classes[] = 'btn-' . $size;
}

if ($class) {
    $css_classes[] = $class;
}

// Формируем атрибуты
$attributes = [];

if ($id) {
    $attributes[] = 'id="' . esc_attr($id) . '"';
}

if ($onclick) {
    $attributes[] = 'onclick="' . esc_attr($onclick) . '"';
}

if ($disabled) {
    $attributes[] = 'disabled';
}

if ($target) {
    $attributes[] = 'target="' . esc_attr($target) . '"';
}

// Data атрибуты
foreach ($data as $key => $value) {
    $attributes[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
}

$class_attr = 'class="' . esc_attr(implode(' ', $css_classes)) . '"';
$attrs_string = implode(' ', $attributes);

// Формируем содержимое кнопки
$button_content = '';
if ($icon) {
    $button_content .= '<i class="' . esc_attr($icon) . '"></i>';
    if ($text) {
        $button_content .= ' ';
    }
}
$button_content .= esc_html($text);
?>

<?php if ($href): ?>
    <a href="<?php echo esc_url($href); ?>" <?php echo $class_attr; ?> <?php echo $attrs_string; ?>>
        <?php echo $button_content; ?>
    </a>
<?php else: ?>
    <button type="<?php echo esc_attr($type); ?>" <?php echo $class_attr; ?> <?php echo $attrs_string; ?>>
        <?php echo $button_content; ?>
    </button>
<?php endif; ?>