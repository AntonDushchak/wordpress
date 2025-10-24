<?php
if (!defined('ABSPATH')) exit;


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

$css_classes = ['btn'];
$style_prefix = $outline ? 'btn-outline-' : 'btn-';
$css_classes[] = $style_prefix . $style;

if ($size) {
    $css_classes[] = 'btn-' . $size;
}

if ($class) {
    $css_classes[] = $class;
}

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

foreach ($data as $key => $value) {
    $attributes[] = 'data-' . esc_attr($key) . '="' . esc_attr($value) . '"';
}

$class_attr = 'class="' . esc_attr(implode(' ', $css_classes)) . '"';
$attrs_string = implode(' ', $attributes);

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