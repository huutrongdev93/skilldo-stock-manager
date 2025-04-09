<?php
class AdminStockProductQuickEdit {
    static function modal(): void
    {
        if(Template::isPage('products_index'))
        {
            Plugin::view(SKDEPOT_NAME, 'admin/products/quick-edit');
        }
    }
}

add_action('admin_footer', 'AdminStockProductQuickEdit::modal');