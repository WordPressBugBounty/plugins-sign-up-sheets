<?php
/**
 * Export Dropdown Template
 *
 * @var array $menu_items The menu items array
 */
?>
<span class="fdsus-export-dropdown">
    <button type="button" class="page-title-action fdsus-dropdown-toggle"
            aria-haspopup="menu" aria-expanded="false" aria-controls="fdsus-export-menu">
        <span class="dashicons dashicons-download"></span>
        <span class="fdsus-dropdown-label">
            <?php esc_html_e('Export Sheet', 'sign-up-sheets'); ?>
        </span>
        <span class="dashicons dashicons-arrow-down-alt2"></span>
    </button>
    <div class="fdsus-dropdown-menu" id="fdsus-export-menu" role="menu" style="display: none;">
        <?php foreach ($menu_items as $key => $item): ?>
            <?php if ($item['disabled']): ?>
                <span role="menuitem"
                      aria-disabled="true"
                      class="<?php echo esc_attr($item['class']); ?>">
                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                    <?php echo esc_html($item['label']); ?>
                    <?php echo $item['badge']; ?>
                </span>
            <?php else: ?>
                <a href="<?php echo esc_url($item['url']); ?>"
                   role="menuitem"
                   class="<?php echo esc_attr($item['class']); ?>"
                   <?php foreach ($item['data'] as $data_key => $data_value): ?>
                       data-<?php echo esc_attr($data_key); ?>="<?php echo esc_attr($data_value); ?>"
                   <?php endforeach; ?>>
                    <span class="dashicons <?php echo esc_attr($item['icon']); ?>"></span>
                    <?php echo esc_html($item['label']); ?>
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</span>