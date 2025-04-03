<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Capsule\Manager as DB;

return new class () extends Migration {

    public function up(): void
    {
        if(!schema()->hasTable('inventories')) {
            schema()->create('inventories', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('product_id')->default(0);
                $table->string('product_name', 255)->collate('utf8mb4_unicode_ci')->nullable();
                $table->string('product_code', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->integer('price_cost')->default(0);
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->collate('utf8mb4_unicode_ci')->nullable();
                $table->integer('parent_id')->default(0);
                $table->integer('stock')->default(0);
                $table->integer('reserved')->default(0);
                $table->string('status', 100)->collate('utf8mb4_unicode_ci')->default('outstock');
                $table->integer('default')->default(0);
                $table->integer('order')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasColumn('users', 'branch_id'))
        {
            schema()->table('users', function (Blueprint $table) {
                $table->integer('branch_id')->default(0);
                $table->integer('debt')->default(0)->comment('Công nợ khách hàng');
            });

            $branch = Branch::where('default', 1)->first();

            if(!have_posts($branch))
            {
                $branch = Branch::get();

                if(!have_posts($branch))
                {
                    response()->error('Không tìm thấy chi nhánh');
                }

                $branch->default = 1;

                $branch->save();
            }

            \SkillDo\Model\User::where('branch_id', 0)->update(['branch_id' => $branch->id]);
        }

        if(!schema()->hasTable('users_debt')) {
            schema()->create('users_debt', function (Blueprint $table) {
                $table->increments('id');

                $table->integer('before')->default(0); //giá trị công nợ trước điều chỉnh
                $table->integer('amount')->default(0); //Số tiền thanh toán
                $table->integer('balance')->default(0); // Công nợ sau khi điều chỉnh
                $table->integer('partner_id')->default(0); //id user

                //Target
                $table->integer('target_id')->default(0);
                $table->string('target_code', 100)->nullable();
                $table->string('target_type', 10)->nullable();
                $table->string('target_type_name', 50)->nullable();

                $table->integer('time')->default(0); //thời gian
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_history')) {
            schema()->create('inventories_history', function (Blueprint $table) {
                $table->increments('id');
                $table->integer('inventory_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->integer('branch_id')->default(0);

                //Loại
                //order - bán hàng
                //order-cancel - Đơn hàng bị hủy
                //purchase-order: nhập hàng
                //purchase-return: trả hàng
                //damage-item: hủy hàng
                //transfer: chuyển hàng
                //transfer-cancel: chuyển hàng bị hủy
                //Target
                $table->integer('target_id')->default(0);
                $table->string('target_code', 100)->nullable();
                $table->string('target_type', 50)->nullable();
                $table->string('target_name', 100)->nullable();

                //Đối tác
                $table->integer('partner_id')->default(0);
                $table->string('partner_code', 50)->nullable();
                $table->string('partner_name', 255)->nullable();
                //Loại
                //C - khách hàng
                //S - Nhà cung cấp
                $table->string('partner_type', 50)->nullable();

                //Thông tin
                $table->integer('cost')->default(0)->comment('Giá vốn');
                $table->integer('price')->default(0)->comment('Giá vốn');
                $table->integer('quantity')->default(0)->comment('Số lượng tồn kho thay đổi');
                $table->integer('start_stock')->default(0)->comment('Số lượng tồn kho đầu');
                $table->integer('end_stock')->default(0)->comment('Số lượng tồn kho cuối');

                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_purchase_orders')) {
            schema()->create('inventories_purchase_orders', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->nullable();
                $table->integer('purchase_id')->default(0); //người nhập hàng
                $table->string('purchase_name', 100)->nullable(); //người nhập hàng
                $table->integer('purchase_date')->default(0); //ngày nhập hàng
                $table->integer('supplier_id')->default(0); //id nhà cung cấp
                $table->string('supplier_name', 100)->nullable();
                $table->string('status', 20)->default('draft');
                $table->integer('discount')->default(0); //Giảm giá nhập hàng
                $table->integer('sub_total')->default(0); //Tổng giá trị hàng hóa
                $table->integer('total_payment')->default(0); //Đã trả cho nhà cung cấp
                $table->integer('total_quantity')->default(0); //Tổng số lượng sản phẩm
                $table->integer('is_payment')->default(0); //Đã trả hết
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_purchase_orders_details')) {
            schema()->create('inventories_purchase_orders_details', function (Blueprint $table) {
                $table->increments('purchase_order_detail_id');
                $table->integer('purchase_order_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('quantity')->default(0); //số lượng nhập
                $table->integer('price')->default(0); //giá nhập hàng
                $table->integer('cost_old')->default(0); //giá nhập hàng củ
                $table->integer('cost_new')->default(0); //giá nhập hàng mới
                $table->string('status', 20)->default('draft');
            });
        }

        if(!schema()->hasTable('inventories_damage_items')) {
            schema()->create('inventories_damage_items', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->nullable();
                $table->integer('damage_id')->default(0); //người nhập hàng
                $table->string('damage_name', 100)->nullable(); //người nhập hàng
                $table->integer('damage_date')->default(0); //ngày nhập hàng
                $table->integer('sub_total')->default(0); //tổng giá trị hàng hóa
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(\SkillDo\DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_damage_item_details')) {
            schema()->create('inventories_damage_item_details', function (Blueprint $table) {
                $table->increments('damage_item_detail_id');
                $table->integer('damage_item_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('quantity')->default(0); //số lượng nhập
                $table->integer('price')->default(0); //giá nhập hàng hóa
                $table->string('status', 20)->default('draft');
            });
        }

        if(!schema()->hasTable('inventories_purchase_returns')) {
            schema()->create('inventories_purchase_returns', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('purchase_order_id')->default(0);
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->nullable();
                $table->integer('purchase_id')->default(0); //người trả hàng
                $table->string('purchase_name', 100)->nullable(); //tên người trả hàng
                $table->integer('purchase_date')->default(0); //ngày trả hàng
                $table->integer('return_discount')->default(0); // Giảm giá trả hàng
                $table->integer('sub_total')->default(0); // Tổng tiền trả hàng
                $table->integer('total_payment')->default(0); //Tổng tiền khách trả
                $table->integer('total_quantity')->default(0); //Tổng số lượng hàng trả
                $table->integer('supplier_id')->default(0); //id nhà cung cấp
                $table->string('supplier_name', 100)->nullable();
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('inventories_purchase_returns_details')) {
            schema()->create('inventories_purchase_returns_details', function (Blueprint $table) {
                $table->increments('purchase_return_detail_id');
                $table->integer('purchase_return_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('quantity')->default(0); //số lượng nhập
                $table->integer('price')->default(0); //Giá trị
                $table->integer('sub_total')->default(0); //Tổng tiền hàng
                $table->integer('cost')->default(0); //giá nhập hàng
                $table->integer('cost_new')->default(0); //giá nhập hàng mới
                $table->string('status', 20)->default('draft');
            });
        }

        if(!schema()->hasTable('suppliers')) {
            schema()->create('suppliers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 200)->collation('utf8mb4_unicode_ci'); //mã nhà cung cấp
                $table->string('name', 200)->collation('utf8mb4_unicode_ci'); //Tên nhà cung cấp
                $table->string('firstname', 200)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('lastname', 200)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('email', 200)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('phone', 200)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('address', 200)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('company', 255)->nullable();
                $table->string('tax', 50)->nullable();
                $table->string('image', 100)->nullable();
                $table->string('status', 20)->default('use');
                $table->integer('total_invoiced')->default(0); //Tổng mua
                $table->integer('debt')->default(0); //Tổng cần trả cho nhà cung cấp
                $table->integer('order')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->index('code');
            });
        }

        if(!schema()->hasTable('debt_adjustment')) {
            schema()->create('debt_adjustment', function (Blueprint $table) {
                $table->increments('id');

                $table->string('code', 200)
                    ->collation('utf8mb4_unicode_ci')
                    ->comment('Mã code phiếu điều chỉnh');

                $table->integer('partner_id')
                    ->default(0)
                    ->comment('Id đối tượng điều chỉnh');

                $table->string('partner_type', 50)
                    ->comment('Loại đối tượng điều chỉnh');

                $table->integer('debt_before')
                    ->default(0)
                    ->comment('Công nợ trước điều chỉnh');

                $table->integer('balance')
                    ->default(0)
                    ->comment('Công nợ sau khi được điều chỉnh');

                $table->integer('time')
                    ->default(0)
                    ->comment('Thời gian điều chỉnh');

                $table->integer('user_id')->default(0)->comment('id người điều chỉnh');
                $table->string('user_code', 100)->collation('utf8mb4_unicode_ci')->nullable();
                $table->string('user_name', 100)->collation('utf8mb4_unicode_ci')->nullable();

                $table->text('note')->collation('utf8mb4_unicode_ci')->nullable();
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
                $table->index('code');
            });
        }

        if(!schema()->hasTable('stock_takes')) {
            schema()->create('stock_takes', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->nullable();
                $table->integer('user_id')->default(0); //người cân bằng
                $table->string('user_name', 100)->nullable(); //tên người cân bằng
                $table->integer('balance_date')->default(0); //ngày cân bằng
                $table->integer('total_actual_quantity')->default(0); // Tổng số lượng hàng thực tế
                $table->integer('total_actual_price')->default(0); // Tổng giá trị hàng thực tế
                $table->integer('total_increase_quantity')->default(0); // Tổng số lượng hàng tăng
                $table->integer('total_increase_price')->default(0); // Tổng giá trị hàng tăng
                $table->integer('total_reduced_quantity')->default(0); // Tổng số lượng hàng giảm
                $table->integer('total_reduced_price')->default(0); // Tổng giá trị hàng giảm
                $table->integer('total_adjustment_quantity')->default(0); //Tổng lệch
                $table->integer('total_adjustment_price')->default(0); //Tổng giá trị lệch
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('stock_take_details')) {
            schema()->create('stock_take_details', function (Blueprint $table) {
                $table->increments('stock_take_detail_id');
                $table->integer('stock_take_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('stock')->default(0); //tồn kho trước điều chỉnh
                $table->integer('price')->default(0); //tiền hàng
                $table->integer('actual_quantity')->default(0); // Tồn kho thực tế
                $table->integer('adjustment_quantity')->default(0); // Số lượng lệch
                $table->integer('adjustment_price')->default(0); // Giá trị lệch
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('cash_flow')) {
            schema()->create('cash_flow', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                //chi nhánh
                $table->integer('branch_id')->default(0);
                $table->string('branch_name', 100)->nullable();
                //user tạo
                $table->integer('user_id')->default(0);
                $table->string('user_name', 100)->nullable();

                //người nhận
                $table->integer('partner_id')->default(0);
                $table->string('partner_code', 50)->nullable();
                $table->string('partner_name', 255)->nullable();
                $table->text('address')->nullable();
                $table->text('phone')->nullable();
                //S : nhà cung cấp
                //C : khách hàng
                //O : Khác
                $table->string('partner_type', 10)->nullable();

                //Loại
                $table->integer('group_id')->default(0);
                $table->string('group_name', 255)->nullable();

                //Nguồn tiền:
                //Pay: khách trả
                //Purchase: thanh toán
                $table->string('origin', 50)->default('cash');
                //Loại thanh toán
                // cash : tiền mặt
                // bank : chuyển khoản
                $table->string('method', 50)->default('cash');
                $table->integer('amount')->default(0);

                //Target
                $table->integer('target_id')->default(0);
                $table->string('target_code', 100)->nullable();
                //PNH : phiếu nhập hàng
                //XHN : Phiếu xuất hàng nhập
                //Order : Đơn hàng
                $table->string('target_type', 10)->nullable();

                //Thông tin phiếu thu
                //Giá trị phiếu
                $table->integer('order_value')->default(0);
                //Giá trị cần trả phiếu
                $table->integer('need_pay_value')->default(0);
                //Đã chi trước
                $table->integer('paid_value')->default(0);

                $table->integer('parent_id')->default(0);
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('cash_flow_group')) {
            schema()->create('cash_flow_group', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->nullable();
                //receiptVoucher phiếu thu
                //paymentVoucher phiếu chi
                $table->string('type', 20)->default('receipt');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('cash_flow_partner')) {
            schema()->create('cash_flow_partner', function (Blueprint $table) {
                $table->increments('id');
                $table->string('name', 100)->nullable();
                $table->string('phone', 100)->nullable();
                $table->string('address', 100)->nullable();
                $table->string('address_full', 255)->nullable();
                $table->integer('city')->default(0);
                $table->integer('district')->default(0);
                $table->integer('ward')->default(0);
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('debt')) {
            schema()->create('debt', function (Blueprint $table) {
                $table->increments('id');

                $table->integer('before')->default(0); //giá trị công nợ trước điều chỉnh
                $table->integer('amount')->default(0); //Số tiền thanh toán
                $table->integer('balance')->default(0); // Công nợ sau khi điều chỉnh
                $table->integer('partner_id')->default(0); //id nhà cung cấp

                //Target
                $table->integer('target_id')->default(0);
                $table->string('target_code', 100)->nullable();
                //PN : phiếu nhập hàng
                //TTPN : thanh toán phiếu nhập hàng
                //PCPT : Phiếu chi - phiếu thu
                $table->string('target_type', 10)->nullable();

                $table->integer('time')->default(0); //thời gian
                $table->integer('user_created')->default(0);
                $table->integer('user_updated')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('transfers')) {
            schema()->create('transfers', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('from_branch_id')->default(0)->comment('Id chi nhánh chuyển hàng');
                $table->string('from_branch_name', 100)->nullable()->comment('Tên chi nhánh chuyển hàng');
                $table->integer('to_branch_id')->default(0)->comment('Id chi nhánh nhận hàng');
                $table->string('to_branch_name', 100)->nullable()->comment('Tên chi nhánh nhận hàng');

                $table->integer('from_user_id')->default(0)->comment('Id người chuyển hàng');
                $table->string('from_user_name', 100)->nullable()->comment('tên người chuyển hàng');

                $table->integer('to_user_id')->default(0)->comment('Id người nhận hàng');
                $table->string('to_user_name', 100)->nullable()->comment('tên người nhận hàng');

                $table->integer('send_date')->default(0)->comment('ngày chuyển hàng');

                $table->integer('receive_date')->default(0)->comment('ngày nhận hàng');

                $table->integer('total_send_quantity')->default(0)->comment('Tổng số lượng chuyển hàng');
                $table->integer('total_send_price')->default(0)->comment('Tổng giá trị hàng chuyển');

                $table->integer('total_receive_quantity')->default(0)->comment('Tổng số lượng nhận hàng');
                $table->integer('total_receive_price')->default(0)->comment('Tổng giá trị hàng nhận');

                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('transfers_details')) {
            schema()->create('transfers_details', function (Blueprint $table) {
                $table->increments('transfer_detail_id');
                $table->integer('transfer_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('price')->default(0)->comment('Giá trị sản phẩm');
                $table->integer('send_quantity')->default(0)->comment('Số lượng chuyển');
                $table->integer('send_price')->default(0)->comment('Tổng tiền hàng chuyển');

                $table->integer('receive_quantity')->default(0)->comment('Số lượng nhận');
                $table->integer('receive_price')->default(0)->comment('Tổng tiền hàng nhận');

                $table->string('status', 20)->default('draft');
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('orders_returns')) {
            schema()->create('orders_returns', function (Blueprint $table) {
                $table->increments('id');
                $table->string('code', 50)->nullable();
                $table->integer('branch_id')->default(0)->comment('Id chi nhánh');
                $table->string('branch_name', 100)->nullable()->comment('Tên chi nhánh');

                $table->integer('user_id')->default(0)->comment('Id nhân viên xử lý');
                $table->string('user_name', 100)->nullable()->comment('tên nhân viên xử lý');

                $table->integer('customer_id')->default(0)->comment('Id khách hàng');
                $table->string('customer_name', 100)->nullable()->comment('tên khách hàng');

                $table->integer('order_id')->default(0)->comment('Id đơn hàng');
                $table->string('order_code', 100)->nullable()->comment('mã đơn hàng');

                $table->integer('discount')->default(0)->comment('Giảm giá');
                $table->integer('surcharge')->default(0)->comment('Phí trả hàng');

                $table->integer('total_quantity')->default(0)->comment('Tổng số lượng hàng trả');
                $table->integer('total_return')->default(0)->comment('Tổng số giá trị hàng');
                $table->integer('total_payment')->default(0)->comment('Tổng cần trả khách');
                $table->integer('total_paid')->default(0)->comment('Tổng đã trả khách');

                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('orders_returns_details')) {
            schema()->create('orders_returns_details', function (Blueprint $table) {
                $table->increments('order_return_detail_id');
                $table->integer('order_return_id')->default(0);
                $table->integer('order_id')->default(0);
                $table->integer('detail_id')->default(0);
                $table->integer('product_id')->default(0);
                $table->string('product_name', 200)->nullable();
                $table->string('product_attribute', 200)->nullable();
                $table->string('product_code', 100)->nullable();
                $table->integer('cost')->default(0)->comment('Giá vốn lúc bán');
                $table->integer('price_sell')->default(0)->comment('Giá bán hàng');
                $table->integer('price')->default(0)->comment('Giá trả hàng');
                $table->integer('quantity')->default(0)->comment('Số lượng trả');
                $table->integer('sub_total')->default(0)->comment('Tổng tiền hàng');
                $table->string('status', 20)->default('draft');
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }
    }

    public function down(): void
    {
        schema()->drop('inventories');
        schema()->drop('inventories_history');
    }
};