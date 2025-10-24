<?php

if (!defined('ABSPATH')) exit;

$title = $args['title'] ?? '';
$content = $args['content'] ?? '';
$callback = $args['callback'] ?? null;
$class = $args['class'] ?? '';
$header_actions = $args['header_actions'] ?? [];
$footer_actions = $args['footer_actions'] ?? [];
$collapsible = $args['collapsible'] ?? false;

$card_id = 'card-' . uniqid();
?>

<div class="card <?php echo esc_attr($class); ?>">
    <?php if ($title || !empty($header_actions)): ?>
    <div class="card-header <?php echo $collapsible ? 'cursor-pointer' : ''; ?>" 
         <?php if ($collapsible): ?>data-bs-toggle="collapse" data-bs-target="#<?php echo $card_id; ?>-body"<?php endif; ?>>
        <div class="d-flex justify-content-between align-items-center">
            <?php if ($title): ?>
                <h5 class="mb-0">
                    <?php echo esc_html($title); ?>
                    <?php if ($collapsible): ?>
                        <i class="bi-chevron-down ms-2"></i>
                    <?php endif; ?>
                </h5>
            <?php endif; ?>
            
            <?php if (!empty($header_actions)): ?>
                <div class="btn-group btn-group-sm">
                    <?php foreach ($header_actions as $action): ?>
                        <button type="button" 
                                class="btn <?php echo esc_attr($action['class'] ?? 'btn-outline-secondary'); ?>"
                                <?php if (isset($action['onclick'])): ?>onclick="<?php echo esc_attr($action['onclick']); ?>"<?php endif; ?>
                                <?php if (isset($action['title'])): ?>title="<?php echo esc_attr($action['title']); ?>"<?php endif; ?>>
                            <?php if (isset($action['icon'])): ?>
                                <i class="<?php echo esc_attr($action['icon']); ?>"></i>
                            <?php endif; ?>
                            <?php if (isset($action['text'])): ?>
                                <?php echo esc_html($action['text']); ?>
                            <?php endif; ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="card-body <?php echo $collapsible ? 'collapse show' : ''; ?>" 
         <?php if ($collapsible): ?>id="<?php echo $card_id; ?>-body"<?php endif; ?>>
        <?php
        if (is_callable($callback)) {
            call_user_func($callback);
        } else {
            echo $content;
        }
        ?>
    </div>
    
    <?php if (!empty($footer_actions)): ?>
    <div class="card-footer">
        <div class="d-flex justify-content-end gap-2">
            <?php foreach ($footer_actions as $action): ?>
                <button type="button" 
                        class="btn <?php echo esc_attr($action['class'] ?? 'btn-primary'); ?>"
                        <?php if (isset($action['onclick'])): ?>onclick="<?php echo esc_attr($action['onclick']); ?>"<?php endif; ?>>
                    <?php if (isset($action['icon'])): ?>
                        <i class="<?php echo esc_attr($action['icon']); ?> me-1"></i>
                    <?php endif; ?>
                    <?php echo esc_html($action['text']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>