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
});