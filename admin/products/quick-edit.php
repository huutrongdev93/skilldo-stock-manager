<?php
class AdminStockProductQuickEdit {
    static function modal(): void
    {
        Plugin::view('stock-manager', 'admin/products/quick-edit');
    }
}

add_action('admin_footer', 'AdminStockProductQuickEdit::modal');