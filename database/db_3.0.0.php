<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use SkillDo\DB;

return new class () extends Migration {

    public function up(): void
    {
        if(schema()->hasColumn('products', 'stock_status')) {
            schema()->table('products', function (Blueprint $table) {
                $table->dropColumn('stock_status');
            });
        }

        \Stock\Model\Inventory::where('branch_id', '')->delete();

        if(schema()->hasTable('inventories')) {
            schema()->table('inventories', function (Blueprint $table) {
                $table->integer('product_id')->default(0)->change();
                $table->integer('branch_id')->default(0)->change();
                $table->integer('price_cost')->default(0);
                $table->unique(['product_id', 'branch_id']);
            });
        }

        if(!schema()->hasColumn('users', 'branch_id')) {
            schema()->table('users', function (Blueprint $table) {
                $table->integer('branch_id')->default(0);
            });
        }

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

        if(!schema()->hasColumn('inventories_history', 'product_id')) {
            schema()->table('inventories_history', function (Blueprint $table) {
                $table->integer('product_id')->default(0);
                $table->integer('branch_id')->default(0);
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
                $table->integer('total_quantity')->default(0); //Tổng số lượng sản phẩm
                $table->integer('sub_total')->default(0); //Tổng giá trị hàng hóa
                $table->integer('discount')->default(0); //Giảm giá nhập hàng
                $table->integer('total_payment')->default(0); //Đã trả cho nhà cung cấp
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
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
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

        if(schema()->hasColumns('suppliers', ['seo_title', 'seo_description', 'seo_keywords'])) {
            schema()->table('suppliers', function (Blueprint $table) {
                $table->dropColumn(['slug', 'seo_title', 'seo_description', 'seo_keywords']);
                $table->string('code', 200)->collation('utf8mb4_unicode_ci'); //mã nhà cung cấp
                $table->integer('total_invoiced')->default(0); //Tổng mua
                $table->integer('debt')->default(0); //Công nợ
                $table->string('status', 20)->default('use');
                $table->string('company', 255)->nullable();
                $table->string('tax', 50)->nullable();
                $table->index('code');
            });
        }

        if(!schema()->hasTable('suppliers_adjustment')) {
            schema()->create('suppliers_adjustment', function (Blueprint $table) {
                $table->increments('id');

                $table->string('code', 200)
                    ->collation('utf8mb4_unicode_ci')
                    ->comment('Mã code phiếu điều chỉnh');

                $table->integer('partner_id')
                    ->default(0)
                    ->comment('Id nhà cung cấp');

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
                //user thu / chi
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
                //PN : phiếu nhập hàng
                //THN : Phiếu trả hàng nhập
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
                $table->integer('time')->default(0);
                $table->string('status', 20)->default('draft');
                $table->text('note')->nullable();
                $table->integer('user_created')->default(0);
                $table->dateTime('created')->default(DB::raw('CURRENT_TIMESTAMP'));
                $table->dateTime('updated')->nullable();
            });
        }

        if(!schema()->hasTable('cash_flow_group')) {
            //-1: Thu tiền khách trả
            //-2: Chi tiền trả NCC
            //-3: NCC hoàn tiền
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

        //Công nợ
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
    }

    public function down(): void
    {
    }
};