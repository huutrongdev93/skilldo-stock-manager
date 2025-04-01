<?php
use SkillDo\Middleware\AdminAuthMiddleware;
use SkillDo\Middleware\AdminPermissionMiddleware;

Route::middleware([AdminAuthMiddleware::class, AdminPermissionMiddleware::class])->prefix('admin')->group(function() {
    $controller = 'views/plugins/stock-manager/controllers/backend';
    Route::get('/inventories', 'InventoriesController@index', ['namespace' => $controller])->name('admin.stock.index');

    //Nhập hàng
    Route::get('/purchase-order', 'PurchaseOrdersController@index', ['namespace' => $controller])->name('admin.stock.purchaseOrders');
    Route::get('/purchase-order/new', 'PurchaseOrdersController@add', ['namespace' => $controller])->name('admin.stock.purchaseOrders.new');
    Route::get('/purchase-order/{num:id}', 'PurchaseOrdersController@edit', ['namespace' => $controller])->name('admin.stock.purchaseOrders.edit');
    //Xuất hàng
    Route::get('/purchase-return', 'PurchaseReturnsController@index', ['namespace' => $controller])->name('admin.stock.purchaseReturns');
    Route::get('/purchase-return/new', 'PurchaseReturnsController@add', ['namespace' => $controller])->name('admin.stock.purchaseReturns.new');
    Route::get('/purchase-return/{num:id}', 'PurchaseReturnsController@edit', ['namespace' => $controller])->name('admin.stock.purchaseReturns.edit');
    //Hủy hàng
    Route::get('/damage-items', 'DamageItemsController@index', ['namespace' => $controller])->name('admin.stock.damageItems');
    Route::get('/damage-items/new', 'DamageItemsController@add', ['namespace' => $controller])->name('admin.stock.damageItems.new');
    Route::get('/damage-items/{num:id}', 'DamageItemsController@edit', ['namespace' => $controller])->name('admin.stock.damageItems.edit');
    //Nhà cung cấp
    Route::get('/suppliers', 'SuppliersController@index', ['namespace' => $controller])->name('admin.suppliers');
    Route::get('/suppliers/add', 'SuppliersController@add', ['namespace' => $controller])->name('admin.suppliers.new');
    Route::get('/suppliers/edit/{num:id}', 'SuppliersController@edit', ['namespace' => $controller])->name('admin.suppliers.edit');
    //kiểm kho
    Route::get('/stock-take', 'StockTakesController@index', ['namespace' => $controller])->name('admin.stock.stockTakes');
    Route::get('/stock-take/new', 'StockTakesController@add', ['namespace' => $controller])->name('admin.stock.stockTakes.new');
    Route::get('/stock-take/{num:id}', 'StockTakesController@edit', ['namespace' => $controller])->name('admin.stock.stockTakes.edit');
    //Chuyển hàng
    Route::get('/transfers', 'TransfersController@index', ['namespace' => $controller])->name('admin.stock.transfers');
    Route::get('/transfers/new', 'TransfersController@add', ['namespace' => $controller])->name('admin.stock.transfers.new');
    Route::get('/transfers/{num:id}', 'TransfersController@edit', ['namespace' => $controller])->name('admin.stock.transfers.edit');
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
});