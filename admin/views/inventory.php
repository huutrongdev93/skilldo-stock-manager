<div class="ui-layout">
    <div class="col-md-12">
        <div class="ui-title-bar__group">
            <h1 class="ui-title-bar__title">Kho hàng</h1>
            <div class="ui-title-bar__action">
                <?php do_action('admin_inventory_action_bar_heading');?>
            </div>
        </div>
        <div class="box">
            <!-- .box-content -->
            <div class="box-content" id="js_inventory_table">
                <div class="table-search row">
                    <div class="col-md-12">
                        <div class="pull-right" style="padding-right:10px;">
                            <form action="<?php echo Url::admin('plugins');?>" method="get" class="d-flex gap-3 p-2" role="form" autocomplete="off" id="js_inventory_form_search">
                                <input type="hidden" name="page" value="stock_inventory">
                                <div class="form-group-search"><input type="text" name="keyword" value="" id="keyword" class=" form-control" placeholder="Từ khóa..." field="keyword"></div>
                                <div class="form-group">
                                    <select name="branch" class=" form-control" id="branch" placeholder="Trạng thái đơn hàng">
                                        <?php foreach ($branches as $branch) { ?>
                                            <option value="<?php echo $branch->id;?>" <?php echo ($branch_id == $branch->id) ?'selected' : '';?>><?php echo $branch->name;?></option>
                                        <?php } ?>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <select name="status" class=" form-control" id="status" placeholder="Trạng thái đơn hàng">
                                        <option value="">Tất cả trạng thái</option>
                                        <option value="instock" <?php echo ($stock_status == 'instock') ?'selected' : '';?>>còn hàng</option>
                                        <option value="outstock" <?php echo ($stock_status == 'outstock') ?'selected' : '';?>>hết hàng</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-blue"><i class="fad fa-search"></i> Lộc dữ liệu</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php echo Admin::loading();?>
                <table class="display table table-striped media-table">
                    <thead>
                        <tr>
                            <th class='manage-column column-title'>Sản phẩm</th>
                            <th class='manage-column column-title'>SKU</th>
                            <th class='manage-column column-title'>Kho hàng</th>
                            <th class='manage-column column-stock'>Tồn kho</th>
                            <th class='manage-column column-status'>Trạng thái</th>
                            <?php if(Auth::hasCap('inventory_edit')) {?>
                            <th class='manage-column column-action'>Cập nhật</th>
                            <?php } ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($inventories as $inventory) { ?>
                        <tr class="tr_<?php echo $inventory->id;?>">
                            <td class='title column-title'>
                                <h3><?php echo $inventory->product_name;?></h3>
                            </td>
                            <td><?php echo $inventory->product_code;?></td>
                            <td><?php echo $inventory->branch_name;?></td>
                            <td class="stock column-stock">
                                <span class="js_inventory_stock_number"><?php echo $inventory->stock;?></span>
                                <span class="js_inventory_stock_review_icon" style="display:none;"><i class="fal fa-angle-right"></i></span>
                                <span class="js_inventory_stock_review inventory-quantity--modified" style="display:none;"></span>
                            </td>
                            <td><span style="background-color:<?php echo Inventory::status($inventory->status, 'color');?>; border-radius:20px; padding:3px 15px; font-size:12px; display:inline-block;color:#000;"><?php echo Inventory::status($inventory->status,'label');?></span></td>
                            <?php if(Auth::hasCap('inventory_edit')) {?>
                            <td class="stock_update column-stock_update">
                                <div class="inventory-form-update">
                                    <input type="hidden" class="js_inventory_inp_id" name="inventory[id]" value="<?php echo $inventory->id;?>">
                                    <input type="hidden" class="js_inventory_inp_type" name="inventory[type]" value="1">
                                    <input type="hidden" class="js_inventory_inp_stock_old" name="inventory[stock_old]" value="<?php echo $inventory->stock;?>">
                                    <div class="next-input-wrapper">
                                        <label class="next-label"></label>
                                        <div class="inventory-line-quantity-fields">
                                            <button class="btn btn-green btn-active inventory_update_type" data-type="1" type="button" name="button"> Thêm bớt </button>
                                            <button class="btn btn-green inventory_update_type" data-type="2" type="button" name="button"> Đặt lại </button>
                                            <input type="number" name="inventory[stock]" value="0" class="form-control js_inventory_inp_stock" min="-<?php echo $inventory->stock;?>">
                                            <button class="btn btn-blue js_inventory__btn_save" type="button" disabled>Lưu</button>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <?php } ?>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
                <!-- paging -->
                <div class="col-md-12 text-left pagination">
                    <?php echo (isset($pagination)) ? $pagination->backend() : '';?>
                </div>
                <!-- paging -->
            </div>
            <!-- /.box-content -->
        </div>
    </div>
</div>

<style>
    .inventory-form-update .inventory-line-quantity-fields {
        display: -webkit-inline-flex;
        display: -ms-inline-flexbox;
        display: inline-flex;
    }
    .inventory-form-update button, .inventory-form-update input {
        -webkit-box-flex: 0;
        -webkit-flex: 0 0 auto;
        -ms-flex: 0 0 auto;
        flex: 0 0 auto;
        position: relative;
        border-radius: 0;
        -webkit-box-flex: 1;
        -webkit-flex: 1 1 0%;
        -ms-flex: 1 1 0%;
        flex: 1 1 0%;
        left: -1px;
        margin: 0 -1px 0 0;
        max-width: 100%;
    }
    .inventory-form-update button.inventory_update_type { opacity:0.3; color:#000; }
    .inventory-form-update button.inventory_update_type.btn-active {
        opacity:1;
    }
</style>

<script id="inventory_item_template" type="text/x-custom-template">
    <tr class="tr_${id}">
        <td class='title column-title'>
            <h3>${product_name}</h3>
        </td>
        <td>${product_code}</td>
        <td>${branch_name}</td>
        <td class="stock column-stock">
            <span class="js_inventory_stock_number">${stock}</span>
            <span class="js_inventory_stock_review_icon" style="display:none;"><i class="fal fa-angle-right"></i></span>
            <span class="js_inventory_stock_review inventory-quantity--modified" style="display:none;"></span>
        </td>
        <td><span style="background-color:${status_color}; border-radius:20px; padding:3px 15px; font-size:12px; display:inline-block;color:#000;">${status_label}</span></td>
        <?php if(Auth::hasCap('inventory_edit')) {?>
        <td class="stock_update column-stock_update">
            <div class="inventory-form-update">
                <input type="hidden" class="js_inventory_inp_id" name="inventory[id]" value="${id}">
                <input type="hidden" class="js_inventory_inp_type" name="inventory[type]" value="1">
                <input type="hidden" class="js_inventory_inp_stock_old" name="inventory[stock_old]" value="${stock}">
                <div class="next-input-wrapper">
                    <label class="next-label"></label>
                    <div class="inventory-line-quantity-fields">
                        <button class="btn btn-green btn-active inventory_update_type" data-type="1" type="button" name="button"> Thêm bớt </button>
                        <button class="btn btn-green inventory_update_type" data-type="2" type="button" name="button"> Đặt lại </button>
                        <input type="number" name="inventory[stock]" value="0" class="form-control js_inventory_inp_stock" min="-${stock}">
                        <button class="btn btn-blue js_inventory__btn_save" type="button" disabled>Lưu</button>
                    </div>
                </div>
            </div>
        </td>
        <?php } ?>
    </tr>
</script>

<script defer>
    $(function () {
        let inventory_form, page = 1;

        let InventoryHandler = function () {
            $(document)
                .on('click', '#js_inventory_table .pagination .pagination-item', this.pagination)
                .on('click', '.inventory-form-update .inventory_update_type', this.updateType)
                .on('change', '.inventory-form-update .js_inventory_inp_stock', this.changeStock)
                .on('click', '.inventory-form-update .js_inventory__btn_save', this.save)
                .on('submit', '#js_inventory_form_search', this.search)
        };

        InventoryHandler.prototype.load = function (e) {

            let loading = $('#js_inventory_table').find('.loading');

            let data    = $('#js_inventory_form_search').serializeJSON();

            data.action = 'Stock_Manager_Ajax::inventoryLoad';

            data.currentItem = page;

            loading.show();

            $.post(ajax, data, function(data) {}, 'json').done(function( response ) {

                loading.hide();

                if(response.status === 'success') {

                    let str = '';

                    for (const [key, items_tmp] of Object.entries(response.list)) {
                        let items = [items_tmp];
                        items.map(function(item) {
                            str += $('#inventory_item_template').html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                        });
                    }

                    $('#js_inventory_table tbody').html(str);

                    $('#js_inventory_table .pagination').html(response.pagination);
                }
            });

            return false;
        };

        InventoryHandler.prototype.search = function (e) {
            page = 1;
            InventoryHandler.prototype.load();
            return false;
        };

        InventoryHandler.prototype.pagination = function (e) {
            page = $(this).attr('data-page-number');
            InventoryHandler.prototype.load();
            return false;
        };

        InventoryHandler.prototype.updateType = function (e) {

            inventory_form = $(this).closest('.inventory-form-update');

            inventory_form.find('.inventory_update_type').removeClass('btn-active');

            inventory_form.find('.js_inventory_inp_type').val($(this).attr('data-type'));

            inventory_form.find('.js_inventory_inp_stock').trigger('change');

            $(this).addClass('btn-active');
        };

        InventoryHandler.prototype.changeStock = function (e) {

            inventory_form = $(this).closest('.inventory-form-update');

            let stock = parseInt($(this).val());

            let stock_old = 0;

            let type = parseInt(inventory_form.find('.js_inventory_inp_type').val());

            if( stock !== 0 ) {

                inventory_form.closest('tr').find('.js_inventory_stock_review').show();

                inventory_form.closest('tr').find('.js_inventory_stock_review_icon').show();

                if (type === 1) {

                    stock_old = inventory_form.find('.js_inventory_inp_stock_old').val();

                    stock = parseInt(stock_old) + stock;
                }

                inventory_form.closest('tr').find('.js_inventory_stock_review').text(stock);

                inventory_form.find('.js_inventory__btn_save').removeAttr('disabled');
            }
            else {

                inventory_form.closest('tr').find('.js_inventory_stock_review').hide();

                inventory_form.closest('tr').find('.js_inventory_stock_review_icon').hide();

                inventory_form.find('.js_inventory__btn_save').attr('disabled','');
            }
        };

        InventoryHandler.prototype.save = function (e) {

            let tr_box = $(this).closest('tr');

            let data = $(':input', tr_box.find('.inventory-form-update')).serializeJSON();

            data.action = 'Stock_Manager_Ajax::inventoryUpdate';

            $.post(ajax, data, function () { }, 'json').done(function (response) {

                if(response.status === 'success') {

                    tr_box = $('.tr_'+response.inventory.id);

                    tr_box.find('.js_inventory_stock_review').hide();

                    tr_box.find('.js_inventory_stock_review_icon').hide();

                    tr_box.find('.js_inventory_inp_stock_old').val(response.inventory.stock);

                    tr_box.find('.js_inventory_inp_stock').val(0);

                    tr_box.find('.js_inventory_stock_number').text(response.inventory.stock);

                    tr_box.find('.js_inventory_reserved_number').text(response.inventory.reserved);

                    tr_box.find('.js_inventory__btn_save').attr('disabled', '');
                }
                else {
                    show_message(response.message, response.status);
                }
            });

            return false;
        };

        new InventoryHandler();
    })
</script>