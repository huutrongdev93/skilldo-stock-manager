<?php
use SkillDo\Middleware\AdminAuthMiddleware;
use SkillDo\Middleware\AdminPermissionMiddleware;

Route::middleware([AdminAuthMiddleware::class, AdminPermissionMiddleware::class])->prefix('admin')->group(function() {
    $controller = 'views/plugins/skdepot/controllers/backend';
    Route::get('/inventories', 'InventoriesController@index', ['namespace' => $controller])->name('admin.inventory.index');

    //Nhập hàng
    Route::get('/purchase-order', 'PurchaseOrdersController@index', ['namespace' => $controller])->name('admin.purchase.orders');
    Route::get('/purchase-order/new', 'PurchaseOrdersController@add', ['namespace' => $controller])->name('admin.purchase.orders.new');
    Route::get('/purchase-order/{num:id}', 'PurchaseOrdersController@edit', ['namespace' => $controller])->name('admin.purchase.orders.edit');
    //Xuất hàng
    Route::get('/purchase-return', 'PurchaseReturnsController@index', ['namespace' => $controller])->name('admin.purchase.returns');
    Route::get('/purchase-return/new', 'PurchaseReturnsController@add', ['namespace' => $controller])->name('admin.purchase.returns.new');
    Route::get('/purchase-return/{num:id}', 'PurchaseReturnsController@edit', ['namespace' => $controller])->name('admin.purchase.returns.edit');
    //Hủy hàng
    Route::get('/damage-items', 'DamageItemsController@index', ['namespace' => $controller])->name('admin.damage.items');
    Route::get('/damage-items/new', 'DamageItemsController@add', ['namespace' => $controller])->name('admin.damage.items.new');
    Route::get('/damage-items/{num:id}', 'DamageItemsController@edit', ['namespace' => $controller])->name('admin.damage.items.edit');
    //Nhà cung cấp
    Route::get('/suppliers', 'SuppliersController@index', ['namespace' => $controller])->name('admin.suppliers');
    Route::get('/suppliers/add', 'SuppliersController@add', ['namespace' => $controller])->name('admin.suppliers.new');
    Route::get('/suppliers/edit/{num:id}', 'SuppliersController@edit', ['namespace' => $controller])->name('admin.suppliers.edit');
    //kiểm kho
    Route::get('/stock-take', 'StockTakesController@index', ['namespace' => $controller])->name('admin.stock.takes');
    Route::get('/stock-take/new', 'StockTakesController@add', ['namespace' => $controller])->name('admin.stock.takes.new');
    Route::get('/stock-take/{num:id}', 'StockTakesController@edit', ['namespace' => $controller])->name('admin.stock.takes.edit');
    //Chuyển hàng
    Route::get('/transfers', 'TransfersController@index', ['namespace' => $controller])->name('admin.transfers');
    Route::get('/transfers/new', 'TransfersController@add', ['namespace' => $controller])->name('admin.transfers.new');
    Route::get('/transfers/{num:id}', 'TransfersController@edit', ['namespace' => $controller])->name('admin.transfers.edit');
    //Trả hàng
    Route::get('/order-return', 'OrderReturnController@index', ['namespace' => $controller])->name('admin.order.returns');
    Route::get('/order-return/new', 'OrderReturnController@add', ['namespace' => $controller])->name('admin.order.returns.new');
    Route::get('/order-return/{num:id}', 'OrderReturnController@edit', ['namespace' => $controller])->name('admin.order.returns.edit');

    //Sổ quỹ
    //Nhóm thu
    Route::get('/cash-flow-group/receipt', 'CashFlowGroupController@receiptIndex', ['namespace' => $controller])->name('admin.cashFlow.group.receipt');
    Route::get('/cash-flow-group/receipt/add', 'CashFlowGroupController@receiptAdd', ['namespace' => $controller])->name('admin.cashFlow.group.receipt.new');
    Route::get('/cash-flow-group/receipt/edit/{num:id}', 'CashFlowGroupController@receiptEdit', ['namespace' => $controller])->name('admin.cashFlow.group.receipt.edit');
    //Nhóm chi
    Route::get('/cash-flow-group/payment', 'CashFlowGroupController@paymentIndex', ['namespace' => $controller])->name('admin.cashFlow.group.payment');
    Route::get('/cash-flow-group/payment/add', 'CashFlowGroupController@paymentAdd', ['namespace' => $controller])->name('admin.cashFlow.group.payment.new');
    Route::get('/cash-flow-group/payment/edit/{num:id}', 'CashFlowGroupController@paymentEdit', ['namespace' => $controller])->name('admin.cashFlow.group.payment.edit');
    //thu - chi
    Route::get('/cash-flow', 'CashFlowController@index', ['namespace' => $controller])->name('admin.cashFlow');

    //Nhân viên
    Route::get('/member', 'MemberController@index', ['namespace' => $controller])->name('admin.member');
    Route::get('/member/new', 'MemberController@add', ['namespace' => $controller])->name('admin.member.new');
    Route::get('/member/edit/{num:id}', 'MemberController@edit', ['namespace' => $controller])->name('admin.member.edit');

    //Báo cáo
    Route::get('/report', 'ReportController@index', ['namespace' => $controller])->name('admin.report');
    Route::get('/report/sales/{any:id}', 'ReportController@sales', ['namespace' => $controller])->name('admin.report.sales');
    Route::get('/report/inventory/{any:id}', 'ReportController@inventory', ['namespace' => $controller])->name('admin.report.inventory');
    Route::get('/report/financial', 'ReportController@financial', ['namespace' => $controller])->name('admin.report.financial');
});