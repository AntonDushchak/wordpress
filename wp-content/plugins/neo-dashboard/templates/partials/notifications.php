<?php

if (empty($notifications)) {
    return;
}

?>
<div id="neo-notification-container">
    <?php foreach ($notifications as $note) : ?>
        <div
            class="alert alert-<?php echo esc_attr($note['type']); ?> <?php echo $note['dismissible'] ? 'alert-dismissible' : ''; ?> fade show mb-2"
            role="alert"
            data-id="<?php echo esc_attr($note['id']); ?>"
        >
            <span class="neo-note-message">
                <?php echo esc_html($note['message']); ?>
            </span>

            <?php if ($note['dismissible']) : ?>
                <button
                    type="button"
                    class="btn-close"
                    data-bs-dismiss="alert"
                    aria-label="<?php esc_attr_e('Close notification', 'neo-dashboard'); ?>"
                ></button>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
</div>
