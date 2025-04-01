<?php
class StockManagerAdmin {
    /**
     * Thêm button vào thanh điều hướng admin
     */
    static function button($buttons)
    {
        $branches = \Stock\Helper::getBranchAll();

        $currentBranch = \Stock\Helper::getBranchCurrent();

        $buttons['stock'] = \Plugin::partial(STOCK_NAME, 'admin/button', [
            'branches' => $branches,
            'currentBranch' => $currentBranch
        ]);

        return $buttons;
    }

    static function navigation(): void
    {
        AdminMenu::addSub('order', 'orderReturn', 'Trả hàng', 'order-return', [
            'position' => 'order',
        ]);

        AdminMenu::add('stock_inventory', 'Kho hàng', 'inventories', [
            'position' => 'products',
            'icon' => '<i class="fa-duotone fa-regular fa-boxes-stacked"></i>'
        ]);

        AdminMenu::addSub('stock_inventory', 'inventories', 'Hàng hóa', 'inventories');
        if(Auth::hasCap('product_cate_list')) {
            AdminMenu::addSub('stock_inventory', 'suppliers', 'Nhà cung cấp', 'suppliers');
        }
        AdminMenu::addSub('stock_inventory', 'purchaseOrders', 'Nhập hàng', 'purchase-order');
        AdminMenu::addSub('stock_inventory', 'purchaseReturns', 'Trả hàng', 'purchase-return');
        AdminMenu::addSub('stock_inventory', 'damageItems', 'Xuất hủy hàng', 'damage-items');
        AdminMenu::addSub('stock_inventory', 'stockTakes', 'Kiểm kho', 'stock-take');
        AdminMenu::addSub('stock_inventory', 'transfers', 'Chuyển hàng', 'transfers');

        AdminMenu::add('cashFlow', 'Sổ quỹ', 'cash-flow', [
            'position' => 'stock_inventory',
            'icon' => '<i class="fa-duotone fa-regular fa-usd-circle"></i>'
        ]);
        AdminMenu::addSub('cashFlow', 'cash-flow-group-payment', 'Loại phiếu chi', 'cash-flow-group/payment');
        AdminMenu::addSub('cashFlow', 'cash-flow-group-receipt', 'Loại phiếu thu', 'cash-flow-group/receipt');
        AdminMenu::addSub('cashFlow', 'cash-flow', 'Phiếu thu chi', 'cash-flow');
    }

    static function breadcrumb($breadcrumb, $pageIndex, \SkillDo\Http\Request $request): array
    {
        if(Str::startsWith($pageIndex, 'DamageItemsController_'))
        {
            $breadcrumb['inventory'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.index'),
                'label'  => 'Kho hàng'
            ];

            if($pageIndex == 'DamageItemsController_index')
            {
                $breadcrumb['damageItems'] = [
                    'active' => true,
                    'url'    => Url::route('admin.stock.damageItems'),
                    'label'  => 'Phiếu xuất hủy'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'PurchaseOrdersController_'))
        {
            $breadcrumb['inventory'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.index'),
                'label'  => 'Kho hàng'
            ];

            $breadcrumb['purchaseOrders'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.purchaseOrders'),
                'label'  => 'Phiếu nhập hàng'
            ];

            if($pageIndex == 'PurchaseOrdersController_index')
            {
                $breadcrumb['purchaseOrders']['active'] = true;
            }

            if($pageIndex == 'PurchaseOrdersController_add')
            {
                $breadcrumb['purchaseOrdersAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.stock.purchaseOrders.new'),
                    'label'  => 'Thêm phiếu nhập hàng'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'PurchaseReturnsController_'))
        {
            $breadcrumb['inventory'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.index'),
                'label'  => 'Kho hàng'
            ];

            $breadcrumb['purchaseReturns'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.purchaseReturns'),
                'label'  => 'Phiếu trả hàng'
            ];

            if($pageIndex == 'PurchaseReturnsController_index')
            {
                $breadcrumb['purchaseReturns']['active'] = true;
            }

            if($pageIndex == 'PurchaseReturnsController_add')
            {
                $breadcrumb['purchaseReturnsAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.stock.purchaseReturns.new'),
                    'label'  => 'Thêm phiếu trả hàng'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'StockTakesController_'))
        {
            $breadcrumb['inventory'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.index'),
                'label'  => 'Kho hàng'
            ];

            $breadcrumb['stockTakes'] = [
                'active' => false,
                'url'    => Url::route('admin.stock.stockTakes'),
                'label'  => 'Phiếu kiểm kho'
            ];

            if($pageIndex == 'StockTakesController_index')
            {
                $breadcrumb['stockTakes']['active'] = true;
            }

            if($pageIndex == 'StockTakesController_add')
            {
                $breadcrumb['stockTakesAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.stock.stockTakes.new'),
                    'label'  => 'Thêm phiếu kiểm kho'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'CashFlowGroupController_receipt'))
        {
            $breadcrumb['cashFlowGroupReceipt'] = [
                'active' => false,
                'url'    => Url::route('admin.cashFlow.group.receipt'),
                'label'  => 'Loại phiếu thu'
            ];

            if($pageIndex == 'CashFlowGroupController_receiptIndex')
            {
                $breadcrumb['cashFlowGroupReceipt']['active'] = true;
            }

            if($pageIndex == 'CashFlowGroupController_receiptAdd')
            {
                $breadcrumb['cashFlowGroupReceiptAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.cashFlow.group.receipt.new'),
                    'label'  => 'Thêm Loại phiếu thu'
                ];
            }

            if($pageIndex == 'CashFlowGroupController_receiptEdit')
            {
                $breadcrumb['cashFlowGroupReceiptAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.cashFlow.group.receipt.new'),
                    'label'  => 'Cập nhật Loại phiếu thu'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'CashFlowGroupController_payment'))
        {
            $breadcrumb['cashFlowGroupPayment'] = [
                'active' => false,
                'url'    => Url::route('admin.cashFlow.group.payment'),
                'label'  => 'Loại phiếu chi'
            ];

            if($pageIndex == 'CashFlowGroupController_receiptIndex')
            {
                $breadcrumb['cashFlowGroupPayment']['active'] = true;
            }

            if($pageIndex == 'CashFlowGroupController_receiptAdd')
            {
                $breadcrumb['cashFlowGroupPaymentAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.cashFlow.group.payment.new'),
                    'label'  => 'Thêm Loại phiếu chi'
                ];
            }

            if($pageIndex == 'CashFlowGroupController_receiptEdit')
            {
                $breadcrumb['cashFlowGroupPaymentAdd'] = [
                    'active' => true,
                    'url'    => Url::route('admin.cashFlow.group.payment.new'),
                    'label'  => 'Cập nhật Loại phiếu chi'
                ];
            }
        }

        if(Str::startsWith($pageIndex, 'CashFlowController_'))
        {
            $breadcrumb['cashFlow'] = [
                'active' => false,
                'url'    => Url::route('admin.cashFlow'),
                'label'  => 'Sổ quỹ'
            ];

            if($pageIndex == 'CashFlowController_index')
            {
                $breadcrumb['cashFlow']['active'] = true;
            }
        }

        if(Str::startsWith($pageIndex, 'SuppliersController_'))
        {
            $breadcrumb['suppliers'] = [
                'active' => false,
                'url'    => Url::route('admin.suppliers'),
                'label'  => 'Nhà cung cấp'
            ];

            if($pageIndex == 'SuppliersController_index')
            {
                $breadcrumb['suppliers']['active'] = true;
            }

            if($pageIndex == 'SuppliersController_edit')
            {
                $breadcrumb['suppliersEdit'] = [
                    'active' => true,
                    'label'  => 'Chi tiết Nhà cung cấp'
                ];
            }
        }

        return $breadcrumb;
    }

    static function detail(): void
    {
        Plugin::view(STOCK_NAME, 'admin/detail');
    }

    static function fileDemo(): void
    {
        if (Admin::is()) {

            $segment = Url::segment();

            $request = request();

            if (!empty($segment[1]) && $segment[1] == 'plugins') {

                if(!empty($segment[2]) && $segment[2] == 'stock-file-demo')
                {
                    $file = $request->input('file');

                    $file = trim(Str::clear($file));

                    if (!empty($file)) {

                        $filePath = Path::plugin(STOCK_NAME) . '/assets/files/' . $file . '.xlsx';

                        if (file_exists($filePath)) {
                            response()->download($filePath, $file . '.xlsx');
                            die;
                        }

                        response()->setStatusCode(404)->send();
                    }
                }
            }
        }
    }
}

add_filter('admin_theme_header_button', 'StockManagerAdmin::button');
add_action('admin_init', 'StockManagerAdmin::navigation', 10);
add_filter('admin_breadcrumb', 'StockManagerAdmin::breadcrumb', 50, 3);
add_action('template_redirect', 'StockManagerAdmin::fileDemo');
add_action('admin_footer', 'StockManagerAdmin::detail');