<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

class StockPurchaseOrderAdminAjax
{
    static function detail(\SkillDo\Http\Request $request): void
    {
        $id = $request->input('id');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('phiếu nhập hàng không có trên hệ thống');
        }
        $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
        $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        $object->status         = Admin::badge(\Stock\Status\PurchaseOrder::tryFrom($object->status)->badge(), \Stock\Status\PurchaseOrder::tryFrom($object->status)->label());
        $object->payment        = \Prd::price($object->sub_total - $object->total_payment - $object->discount);
        $object->sub_total      = \Prd::price($object->sub_total);
        $object->discount       = \Prd::price($object->discount);
        $object->total_payment  = \Prd::price($object->total_payment);

        response()->success('load dữ liệu thành công', [
            'item' => $object->toObject()
        ]);
    }

    static function loadProductsDetail(\SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $id  = $request->input('id');

        $query = Qr::where('inventories_purchase_orders_details.purchase_order_id', $id);

        $selected = [
            'product_id',
            'product_name',
            'product_code',
            'product_attribute',
            'quantity',
            'price',
        ];

        $query->select($selected);

        # [Total decoders]
        $total = \Stock\Model\PurchaseOrderDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Stock\Model\PurchaseOrderDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' '.$object->product_attribute;
        }

        # [created table]
        $table = new \Stock\Table\PurchaseOrder\ProductDetail([
            'items' => $objects,
        ]);

        $table->setTrash(true);

        $table->getColumns();

        $html = $table->renderBody();

        $result['data'] = [
            'html'          => base64_encode($html),
            'bulkAction'    => base64_encode(''),
            'recordsPublic' => $publicCount ?? 0,
            'recordsTrash'  => $trashCount ?? 0,
        ];

        $result['pagination']   = [
            'limit' => $limit,
            'total' => $total,
            'page'  => $page,
        ];

        response()->success(trans('ajax.load.success'), $result);
    }

    static function loadCashFlowDetail(\SkillDo\Http\Request $request): void
    {
        $id   = (int)$request->input('id');

        $cashFlows = \Stock\Model\CashFlow::widthChildren()
            ->select('id', 'code', 'target_id', 'target_code', 'parent_id', 'created', 'amount', 'order_value', 'need_pay_value', 'paid_value')
            ->where('target_id', $id)
            ->where('target_type', \Stock\Prefix::purchaseOrder->value)
            ->get();

        response()->success(trans('ajax.load.success'), $cashFlows);
    }

    static function loadProductsEdit(\SkillDo\Http\Request $request): void
    {
        $id  = $request->input('id');

        $selected = [
            'products.id',
            'products.title',
            'products.code',
            'products.attribute_label',
            'products.image',
            'products.attribute_label',
            'po.quantity',
            'po.price',
        ];

        $query = Qr::select($selected);

        $query->leftJoin('inventories_purchase_orders_details as po', function ($join) use ($id) {
            $join->on('po.product_id', '=', 'products.id');
        });

        $query->where('po.purchase_order_id', $id);

        $query
            ->limit(500)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc');

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        foreach ($products as $key => $item)
        {
            $item = $item->toObject();

            $item->fullname = $item->title;

            if(!empty($item->attribute_label))
            {
                $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
            }

            $item->image = Image::medium($item->image)->html();

            $products[$key] = $item;
        }

        response()->success('Load dữ liệu thành công', $products);
    }

    static function addDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $purchaseOrder, // Phiếu nhập hàng
            $branch, // chính nhánh
            $supplier, // nhà cung cấp
            $productsPurchase, // Danh sách sản phẩm đã chọn
            $purchaseOrderDetails, // Danh sách chi tiết phiếu nhập hàng
            $productsDetail // Danh sách chi tiết sản phẩm sẽ xóa
        ] = static::purchaseDataDraft($request);

        try
        {
            DB::beginTransaction();

            $purchaseOrderId = \Stock\Model\PurchaseOrder::create($purchaseOrder);

            if(empty($purchaseOrderId) || is_skd_error($purchaseOrderId))
            {
                response()->error('Tạo phiếu nhập hàng thất bại');
            }

            foreach ($purchaseOrderDetails as &$detail)
            {
                $detail['purchase_order_id'] = $purchaseOrderId;
            }

            DB::table('inventories_purchase_orders_details')->insert($purchaseOrderDetails);

            DB::commit();

            response()->success('Lưu tạm phiếu nhập hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu nhập hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function saveDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu nhập hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('Phiếu nhập đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\PurchaseOrder::success->value || $object->status === \Stock\Status\PurchaseOrder::cancel->value)
        {
            response()->error('Trạng thái phiếu nhập không cho phép chỉnh sữa');
        }

        [
            $purchaseOrder, // Phiếu nhập hàng
            $branch, // chính nhánh
            $supplier, // nhà cung cấp
            $productsPurchase, // Danh sách sản phẩm đã chọn
            $purchaseOrderDetails, // Danh sách chi tiết phiếu nhập hàng
            $productsDetail // Danh sách chi tiết sản phẩm sẽ xóa
        ] = static::purchaseDataDraft($request, $object);

        \Stock\Model\PurchaseOrder::whereKey($id)->update($purchaseOrder);

        //Lấy danh sách chi tiết phiếu nhập sẽ cập nhật
        $purchaseOrderDetailsUp = [];

        foreach ($purchaseOrderDetails as $key => $detail)
        {
            if(empty($detail['purchase_order_id']))
            {
                $detail['purchase_order_id'] = $object->id;
                $purchaseOrderDetails[$key] = $detail;
            }

            if(!empty($detail['purchase_order_detail_id']))
            {
                $purchaseOrderDetailsUp[] = $detail;
                unset($purchaseOrderDetails[$key]);
            }
        }

        try
        {
            DB::beginTransaction();

            //Thêm mới
            if(!empty($purchaseOrderDetails))
            {
                DB::table('inventories_purchase_orders_details')->insert($purchaseOrderDetails);
            }

            //Cập nhật
            if(!empty($purchaseOrderDetailsUp))
            {
                \Stock\Model\PurchaseOrderDetail::updateBatch($purchaseOrderDetailsUp, 'purchase_order_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\PurchaseOrderDetail::whereKey($productsDetail->pluck('purchase_order_detail_id')->toArray())->delete();
            }

            DB::commit();

            response()->success('Lưu tạm phiếu nhập hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu nhập hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function purchaseDataDraft(\SkillDo\Http\Request $request, $object = null): array
    {
        $isEdit = !empty($object);

        $time = $request->input('time');

        if(!empty($time))
        {
            $time = str_replace('/', '-', $time);

            $time = strtotime($time);

            if($time > time())
            {
                response()->error('Thời gian nhập hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $purchaseOrder = [
            'status'        => \Stock\Status\PurchaseOrder::draft->value,
            'discount'      => (int)$request->input('discount'),
            'total_payment' => (int)$request->input('total_payment'),
            'purchase_date' => $time
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $purchaseOrder['branch_id']     = $branch->id;
            $purchaseOrder['branch_name']   = $branch->name;
        }

        //Người nhập hàng
        $purchaseId = (int)$request->input('purchase');

        $purchase = (!empty($purchaseId)) ? \SkillDo\Model\User::find($purchaseId) : Auth::user();

        if(!empty($purchase->firstname) || !empty($purchase->lastname))
        {
            $purchase_name = $purchase->firstname.' '.$purchase->lastname;
        }

        $purchaseOrder['purchase_id']     = $purchase->id ?? 0;
        $purchaseOrder['purchase_name']   = $purchase_name ?? '';

        //Nhà cung cấp
        $supplierId = (int)$request->input('supplier');

        if(!empty($supplierId))
        {
            $supplier = \Stock\Model\Suppliers::find($supplierId);
        }

        $purchaseOrder['supplier_id']     = $supplier->id ?? 0;
        $purchaseOrder['supplier_name']   = $supplier->name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\PurchaseOrder::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\PurchaseOrder::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu nhập này đã được sử dụng');
            }

            $purchaseOrder['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productsPurchase = $request->input('products');

        $purchaseOrder['sub_total'] = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);

        $purchaseOrder['total_quantity'] = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity']);
        }, 0);

        $productsId = [];

        //Danh sách sản phẩm phiếu nhập hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $purchaseOrderDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\PurchaseOrderDetail::where('purchase_order_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productsPurchase as $product)
        {
            $productsId[$product['id']] = $product['id'];

            if (isset($productsDetail[$product['id']]))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\PurchaseOrder::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $purchaseOrderDetails[] = [
                    'purchase_order_detail_id'  => $productDetail->purchase_order_detail_id,
                    'purchase_order_id'         => $object->id,
                    'product_id'                => $product['id'],
                    'product_code'              => $product['code'] ?? '',
                    'product_name'              => $product['title'],
                    'product_attribute'         => $product['attribute_label'] ?? '',
                    'quantity'                  => $product['quantity'],
                    'price'                     => $product['price'],
                ];

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $purchaseOrderDetails[] = [
                'purchase_order_id'  => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'quantity'           => $product['quantity'],
                'price'              => $product['price'],
            ];
        }

        return [
            $purchaseOrder,
            $branch,
            $supplier ?? null,
            $productsPurchase,
            $purchaseOrderDetails,
            $productsDetail
        ];
    }

    static function add(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $purchaseOrder, // Phiếu nhập
            $branch, // Chi nhánh
            $supplier, // Nhà cung cấp
            $productsPurchase,
            $inventories, // Kho hàng
            $products, // Sản phẩm
            $purchaseOrderDetails, // Chi tiết phiếu nhập
            $inventoryCosts, //danh sách sản phẩm cập nhật giá vốn
            $productsDetail // Chi tiết sản phẩm xóa đi
        ] = static::purchaseData($request);

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($purchaseOrderDetails as $detail)
        {
            if (!$inventories->has($detail['product_id'])) {
                response()->error('Không tìm thấy tồn kho của sản phẩm ' . $detail['product_name']);
            }

            $inventory = $inventories[$detail['product_id']];

            $newStock = $inventory->stock + $detail['quantity'];

            $inventoryUpdate = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => \Stock\Status\Inventory::in->value
            ];

            foreach ($inventoryCosts as $keyCost => $inventoryCost)
            {
                if($inventoryCost['id'] == $inventory->id)
                {
                    $inventoryUpdate['price_cost'] = $inventoryCost['price_cost'];

                    unset($inventoryCosts[$keyCost]);
                }
            }

            $inventoriesUpdate[] = $inventoryUpdate;

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                'message'       => [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $newStock,
                    'purchaseCode'  => '',
                ],
                'action'        => 'cong',
                'type'          => 'stock',
            ];
        }

        try {

            DB::beginTransaction();

            //Tạo phiếu nhập hàng
            $purchaseOrderId = \Stock\Model\PurchaseOrder::create($purchaseOrder);

            if(empty($purchaseOrder['code']))
            {
                $purchaseOrder['code'] = \Stock\Helper::code(\Stock\Prefix::purchaseOrder->value, $purchaseOrderId);
            }

            if(empty($purchaseOrderId) || is_skd_error($purchaseOrderId))
            {
                response()->error('Tạo phiếu nhập hàng thất bại');
            }

            // Cập nhật mã phiếu vào lịch sử kho
            foreach ($inventoriesHistories as $key => $history)
            {
                $history['message']['purchaseCode'] = $purchaseOrder['code'];
                $history['message'] = InventoryHistory::message('purchase_update', $history['message']);
                $inventoriesHistories[$key] = $history;
            }

            // Cập nhật purchase_order_id
            foreach ($purchaseOrderDetails as &$detail)
            {
                $detail['purchase_order_id'] = $purchaseOrderId;
                unset($detail['purchase_order_detail_id']);
            }

            DB::table('inventories_purchase_orders_details')->insert($purchaseOrderDetails);

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            //Cập nhật giá vốn trung bình
            if(have_posts($inventoryCosts))
            {
                \Stock\Model\Inventory::updateBatch($inventoryCosts, 'id');
            }

            if(!empty($supplier))
            {
                static::debt($supplier, $purchaseOrderId, $purchaseOrder);
            }

            DB::commit();

            response()->success('Tạo phiếu nhập hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu nhập hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu nhập hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('Phiếu nhập đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status !== \Stock\Status\PurchaseOrder::draft->value)
        {
            response()->error('Trạng thái phiếu nhập này đã không thể cập nhật');
        }

        [
            $purchaseOrder, // Phiếu nhập
            $branch, // Chi nhánh
            $supplier, // Nhà cung cấp
            $productsPurchase,
            $inventories, // Kho hàng
            $products, // Sản phẩm
            $purchaseOrderDetails, // Chi tiết phiếu nhập,
            $inventoryCosts, //danh sách sản phẩm cập nhật giá vốn
            $productsDetail // Chi tiết sản phẩm xóa đi
        ] = static::purchaseData($request, $object);

        if(empty($purchaseOrderDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }

        //Sản phẩm của phiếu nhập cập nhật
        $purchaseOrderDetailsUp = [];

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($purchaseOrderDetails as $key => $detail)
        {
            if (!$inventories->has($detail['product_id']))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm ' . $detail['product_name']);
            }

            $inventory = $inventories[$detail['product_id']];

            $newStock = $inventory->stock + $detail['quantity'];

            $inventoryUpdate = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => \Stock\Status\Inventory::in->value
            ];

            foreach ($inventoryCosts as $keyCost => $inventoryCost)
            {
                if($inventoryCost['id'] == $inventory->id)
                {
                    $inventoryUpdate['price_cost'] = $inventoryCost['price_cost'];

                    unset($inventoryCosts[$keyCost]);
                }
            }

            $inventoriesUpdate[] = $inventoryUpdate;

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                'message'       => InventoryHistory::message('purchase_update', [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $newStock,
                    'purchaseCode'  => $object->code,
                ]),
                'action'        => 'cong',
                'type'          => 'stock',
            ];

            if(empty($detail['purchase_order_id']))
            {
                $detail['purchase_order_id'] = $object->id;
                $purchaseOrderDetails[$key] = $detail;
            }

            if(!empty($detail['purchase_order_detail_id']))
            {
                $purchaseOrderDetailsUp[] = $detail;
                unset($purchaseOrderDetails[$key]);
            }
            else {
                unset($detail['purchase_order_detail_id']);
                $purchaseOrderDetails[$key] = $detail;
            }
        }

        try {

            DB::beginTransaction();

            //Cập nhật phiếu nhập hàng
            \Stock\Model\PurchaseOrder::whereKey($id)->update($purchaseOrder);

            //Thêm mới
            if(!empty($purchaseOrderDetails))
            {
                DB::table('inventories_purchase_orders_details')->insert($purchaseOrderDetails);
            }

            //Cập nhật
            if(!empty($purchaseOrderDetailsUp))
            {
                \Stock\Model\PurchaseOrderDetail::updateBatch($purchaseOrderDetailsUp, 'purchase_order_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\PurchaseOrderDetail::whereKey($productsDetail->pluck('purchase_order_detail_id')->toArray())->delete();
            }

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            //Cập nhật giá vốn trung bình
            if(have_posts($inventoryCosts))
            {
                \Stock\Model\Inventory::updateBatch($inventoryCosts, 'id');
            }

            if(!empty($supplier))
            {
                static::debt($supplier, $id, $purchaseOrder);
            }

            DB::commit();

            response()->success('Lưu tạm phiếu nhập hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            response()->error($e->getMessage());
        }
    }

    static function validate(\SkillDo\Http\Request $request, $rules = []): void
    {
        $validate = $request->validate([
            'discount'              => Rule::make('Giảm giá')->notEmpty()->integer()->min(0),
            'total_payment'         => Rule::make('Đã trả NCC')->notEmpty()->integer()->min(0),
            'products'              => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'         => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.quantity'   => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'      => Rule::make('Giá vốn sản phẩm')->notEmpty()->integer()->min(0),
            ...$rules
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }
    }

    static function purchaseData(\SkillDo\Http\Request $request, $object = null): array
    {
        $isEdit = !empty($object);

        $time = $request->input('time');

        if(!empty($time))
        {
            $time = str_replace('/', '-', $time);

            $time = strtotime($time);

            if($time > time())
            {
                response()->error('Thời gian nhập hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $purchaseOrder = [
            'status'        => \Stock\Status\PurchaseOrder::success->value,
            'discount'      => (int)$request->input('discount'),
            'total_payment' => (int)$request->input('total_payment'),
            'purchase_date' => $time,
            'is_payment'    => 0
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh đã đóng cửa hoặc không còn trên hệ thống');
        }

        $purchaseOrder['branch_id']     = $branch->id;
        $purchaseOrder['branch_name']   = $branch->name;

        //Người nhập hàng
        $purchaseId = $request->input('purchase');

        $purchase = (!empty($purchaseId)) ? \SkillDo\Model\User::find($purchaseId) : Auth::user();

        if(empty($purchase))
        {
            response()->error('Không tìm thấy Nhân viên nhập hàng');
        }

        $purchaseOrder['purchase_id']     = $purchase->id;
        $purchaseOrder['purchase_name']   = $purchase->firstname.' '.$purchase->lastname;

        //Nhà cung cấp
        $supplierId = (int)$request->input('supplier');

        if(!empty($supplierId))
        {
            $supplier = \Stock\Model\Suppliers::find($supplierId);
        }

        $purchaseOrder['supplier_id']     = $supplier->id ?? 0;
        $purchaseOrder['supplier_name']   = $supplier->name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\PurchaseOrder::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\PurchaseOrder::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu nhập này đã được sử dụng');
            }

            $purchaseOrder['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productsPurchase = $request->input('products');

        $total = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);

        $quantity = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity']);
        }, 0);

        if($total < $purchaseOrder['discount'])
        {
            response()->error('Số tiền khuyến mãi không được lớn hơn tổng tiền hàng');
        }

        if(($total - $purchaseOrder['discount']) < $purchaseOrder['total_payment'])
        {
            response()->error('Số tiền đã thanh toán không được lớn hơn số tiền trả cho nhà cung cấp');
        }

        if(($total - $purchaseOrder['discount']) == $purchaseOrder['total_payment'])
        {
            $purchaseOrder['is_payment'] = 1;
        }

        $purchaseOrder['sub_total'] = $total;

        $purchaseOrder['total_quantity'] = $quantity;

        $productsId = [];

        //Danh sách sản phẩm phiếu nhập hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $purchaseOrderDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\PurchaseOrderDetail::where('purchase_order_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productsPurchase as $product)
        {
            if(!empty($productsId[$product['id']]))
            {
                response()->error('Sản phẩm '.$product['title'].$product['attribute_label'].' đã tồn tại trong danh sách sản phẩm');
            }

            $productsId[$product['id']] = $product['id'];

            $discount = 0;

            if($purchaseOrder['discount'] != 0)
            {
                $total = $product['stock']*$product['cost'];

                $percent = ceil($total / $purchaseOrder['sub_total'] * 100);

                $discount = $percent * $purchaseOrder['discount'] / 100;
            }

            if (isset($productsDetail[$product['id']]))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\PurchaseOrder::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $purchaseOrderDetails[] = [
                    'purchase_order_detail_id'  => $productDetail->purchase_order_detail_id,
                    'purchase_order_id'         => $object->id,
                    'product_id'                => $product['id'],
                    'product_code'              => $product['code'] ?? '',
                    'product_name'              => $product['title'],
                    'product_attribute'         => $product['attribute_label'] ?? '',
                    'quantity'                  => $product['quantity'],
                    'price'                     => $product['price'],
                    'status'                    => \Stock\Status\PurchaseOrder::success->value,
                    'discount'                  => $discount,
                ];

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $purchaseOrderDetails[] = [
                'purchase_order_id'  => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'quantity'           => $product['quantity'],
                'price'              => $product['price'],
                'status'             => \Stock\Status\PurchaseOrder::success->value,
                'discount'           => $discount,
            ];
        }

        $inventories = \Stock\Model\Inventory::select(['id', 'product_id', 'parent_id', 'branch_id', 'stock', 'status', 'price_cost'])
            ->whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->get();

        if($inventories->count() !== count($productsId))
        {
            response()->error('Số lượng sản phẩm cập nhật và số lượng sản phẩm trong kho hàng không khớp');
        }

        $inventories = $inventories->keyBy('product_id');

        $products = \Ecommerce\Model\Product::widthVariation()
            ->whereIn('id', $productsId)
            ->select('id', 'title')
            ->get();

        $purchaseMap = (\Illuminate\Support\Collection::make($purchaseOrderDetails))
            ->keyBy('product_id');

        //Biến chứa danh sách sản phẩm cập nhật giá vốn
        $inventoryCosts = [];

        foreach ($products as $product)
        {
            if (!$inventories->has($product->id) || !$purchaseMap->has($product->id)) {
                continue;
            }

            $inventory = $inventories[$product->id];

            $purchase = $purchaseMap[$product->id];

            $priceCost = (
                (($purchase['quantity']*$purchase['price'] - $purchase['discount']) + $inventory->stock*$inventory->price_cost) /
                ($inventory->stock+$purchase['quantity']));

            $priceCost = ceil($priceCost);

            $purchaseMap->transform(function ($item, $key) use ($priceCost, $product, $inventory) {
                if ($key === $product->id) {
                    $item['cost_old'] = $inventory->price_cost;
                    $item['cost_new'] = $priceCost;
                    unset($item['discount']);
                }
                return $item;
            });

            // Nếu giá vốn thay đổi, thêm vào productCosts
            if ($priceCost != $inventory->price_cost) {
                $inventoryCosts[] = [
                    'id'          => $inventory->id,
                    'price_cost'  => $priceCost
                ];
            }
        }

        $purchaseOrderDetails = $purchaseMap->values()->toArray();

        return [
            $purchaseOrder,
            $branch,
            $supplier ?? null,
            $productsPurchase,
            $inventories,
            $products,
            $purchaseOrderDetails,
            $inventoryCosts,
            $productsDetail
        ];
    }

    //Xử lý công nợ
    static function debt($supplier, $purchaseOrderId, $purchaseOrder): void
    {
        //Tạo công nợ cho đơn nhập hàng
        \Stock\Model\Debt::create([
            'before'        => ($supplier->debt)*-1,
            'amount'        => ($purchaseOrder['sub_total'] -  $purchaseOrder['discount'])*-1,
            'balance'       => ($purchaseOrder['sub_total'] -  $purchaseOrder['discount'] + $supplier->debt)*-1,
            'partner_id'    => $supplier->id,
            'target_id'     => $purchaseOrderId,
            'target_code'   => $purchaseOrder['code'],
            'target_type'   => \Stock\Prefix::purchaseOrder->value,
            'time'          => $purchaseOrder['purchase_date']
        ]);

        if($purchaseOrder['total_payment'] > 0)
        {
            //Tạo phiếu chi
            $code = \Stock\Helper::code('TT'.\Stock\Prefix::purchaseOrder->value, $purchaseOrderId);

            $idCashFlow = \Stock\Model\CashFlow::create([
                'code'      => $code,
                'branch_id' => $purchaseOrder['branch_id'],
                'branch_name' => $purchaseOrder['branch_name'],
                //Người chi
                'user_id' => $purchaseOrder['purchase_id'],
                'user_name' => $purchaseOrder['purchase_name'],
                //người nhận
                'partner_id' => $supplier->id,
                'partner_code' => $supplier->code,
                'partner_name'  => $supplier->name,
                'address' => $supplier->address,
                'phone' => $supplier->phone,
                'partner_type' => 'S',

                //Loại
                'group_id' => -2,
                'group_name' => 'Chi tiền trả NCC',
                'origin' => 'purchase',
                'method' => 'cash',
                'amount' => $purchaseOrder['total_payment']*-1,

                'target_id'     => $purchaseOrderId,
                'target_code'   => $purchaseOrder['code'],
                'target_type'   => \Stock\Prefix::purchaseOrder->value,
                'time'          => $purchaseOrder['purchase_date'],
                'status'        => \Stock\Status\CashFlow::success->value,
                'user_created'  => Auth::id()
            ]);

            //Tạo công nợ cho phiêu chi
            \Stock\Model\Debt::create([
                'before'        => ($supplier->debt)*-1,
                'amount'        => $purchaseOrder['total_payment'],
                'balance'       => ($purchaseOrder['sub_total'] -  $purchaseOrder['discount'] - $purchaseOrder['total_payment'] + $supplier->debt)*-1,
                'partner_id'    => $supplier->id,
                'target_id'     => $idCashFlow,
                'target_code'   => $code,
                'target_type'   => 'TT'.\Stock\Prefix::purchaseOrder->value,
                'time'          => $purchaseOrder['purchase_date']
            ]);
        }

        \Stock\Model\Suppliers::whereKey($supplier->id)
            ->update([
                'total_invoiced' => DB::raw('total_invoiced + '. ($purchaseOrder['sub_total'] -  $purchaseOrder['discount'])),
                'debt' => DB::raw('debt + '. ($purchaseOrder['sub_total'] -  $purchaseOrder['discount'] - $purchaseOrder['total_payment'])),
            ]);
    }

    static function cancel(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'data' => Rule::make('Phiếu nhập')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('data');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('Phiếu nhập đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\PurchaseOrder::cancel->value)
        {
            response()->error('Phiếu nhập này đã được hủy');
        }
        if($object->status === \Stock\Status\PurchaseOrder::success->value)
        {
            response()->error('Phiếu nhập này đã hoàn thành không thể hủy');
        }

        \Stock\Model\PurchaseOrderDetail::where('purchase_order_id', $object->id)
            ->where('status', \Stock\Status\PurchaseOrder::draft->value)
            ->update([
                'status' => \Stock\Status\PurchaseOrder::cancel->value,
            ]);

        \Stock\Model\PurchaseOrder::whereKey($object->id)->update([
            'status' => \Stock\Status\PurchaseOrder::cancel->value,
        ]);

        response()->success('Hủy phiếu nhập hàng thành công', [
            'status' => Admin::badge(\Stock\Status\PurchaseOrder::cancel->badge(), 'Đã hủy')
        ]);
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Phiếu nhập')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('Phiếu nhập đã đóng cửa hoặc không còn trên hệ thống');
        }

        $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
        $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        $object->payment = $object->sub_total - $object->discount - $object->total_payment;

        $object->sub_total = Prd::price($object->sub_total);
        $object->discount = Prd::price($object->discount);
        $object->total_payment = Prd::price($object->total_payment);
        $object->payment = Prd::price($object->payment);

        $products = \Stock\Model\PurchaseOrderDetail::where('purchase_order_id', $object->id)->get();

        response()->success('Dữ liệu print', [
            'purchase' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
                $item->total = Prd::price($item->price*$item->quantity);
                $item->price = Prd::price($item->price);
                return $item->toObject();
            })
        ]);
    }

    static function export(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'export' => Rule::make('Loại xuất dữ liệu')->notEmpty()->in(['page', 'search', 'checked']),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $type = $request->input('export');

        $query = Qr::orderBy('purchase_date');

        if($type === 'page')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu nhập hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'checked')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu nhập hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'search') {

            $search = $request->input('search');

            if(!empty($search['keyword']))
            {
                $query->orWhere('code', 'like', '%'.$search['keyword'].'%');
            }

            if(!empty($search['branch_id']))
            {
                $branch_id = (int)$search['branch_id'];

                $query->where('branch_id', $branch_id);
            }

            if(!empty($search['status']))
            {
                $query->where('status', $search['status']);
            }
        }

        $objects = \Stock\Model\PurchaseOrder::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu nhập hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
            $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        }

        $export = new \Stock\Export();

        $export->header('code', 'Mã nhập hàng', function($item) {
            return $item->code ?? '';
        });

        $export->header('purchase_date', 'Ngày nhập', function($item) {
            return $item->purchase_date;
        });

        $export->header('branch_name', 'Chi nhánh', function($item) {
            return $item->branch_name;
        });

        $export->header('supplier_name', 'Nhà cung cấp', function($item) {
            return $item->supplier_name;
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->total_quantity);
        });

        $export->header('sub_total', 'Giá trị', function($item) {
            return number_format($item->sub_total);
        });

        $export->header('discount', 'Giảm giá', function($item) {
            return number_format($item->discount);
        });

        $export->header('total', 'Cần Trả NCC', function($item) {
            return number_format($item->sub_total - $item->discount - $item->total_payment);
        });

        $export->header('total_payment', 'Đã Trả NCC', function($item) {
            return number_format($item->total_payment);
        });

        $export->setTitle('DSPhieuNhapHang_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/purchase-order/', 'DanhSachPhieuNhapHang_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('Phiếu nhập')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseOrder::find($id);

        if(empty($object))
        {
            response()->error('Phiếu nhập đã đóng cửa hoặc không còn trên hệ thống');
        }

        $products = \Stock\Model\PurchaseOrderDetail::where('purchase_order_id', $object->id)->get();

        $export = new \Stock\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('price', 'Giá nhập', function($item) {
            return number_format($item->price ?? 0);
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->quantity ?? 0);
        });

        $export->header('total', 'Thành tiền', function($item) {
            return number_format($item->quantity * $item->price);
        });

        $export->setTitle('DSChiTietNhapHang_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/purchase-order/', 'DanhSachChiTietNhapHang_'.$object->code.'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function import(\SkillDo\Http\Request $request): void
    {
        if($request->hasFile('file')) {

            $validate = $request->validate([
                'file' => Rule::make('File sản phẩm')->notEmpty()->file(['xlsx', 'xls'], [
                    'min' => 1,
                    'max' => '5mb'
                ]),
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $myPath = STOCK_NAME.'/assets/imports/purchase-order';

            $path = $request->file('file')->store($myPath, ['disk' => 'plugin']);

            if (empty($path)) {
                response()->error(trans('File not found'));
            }

            $filePath = FCPATH.'views/plugins/'.$path;

            $reader = PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');

            $spreadsheet = $reader->load($filePath);

            $worksheet = $spreadsheet->getActiveSheet();

            $highestRow = $worksheet->getHighestRow();

            $highestColumn = $worksheet->getHighestColumn();

            $highestColumnIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestColumn);

            $schedules = [];

            for ($row = 1; $row <= $highestRow; $row++) {
                $empty = false;
                for ($col = 1; $col <= $highestColumnIndex; $col++) {
                    $cell = $worksheet->getCellByColumnAndRow($col, $row);
                    if ($cell->isFormula()) {
                        $cellValue = $cell->getOldCalculatedValue();
                    } else {
                        $cellValue = $cell->getValue();
                    }
                    $schedules[$row][$col] = (string)$cellValue;
                    if(!empty($schedules[$row][$col])) {
                        $empty = true;
                    }
                }
                if(!$empty) {
                    array_pop($schedules);
                    break;
                }
            }

            if(!have_posts($schedules)) {
                $result['message'] 	= 'Không lấy được dữ liệu từ file excel này';
                echo json_encode($result);
                return;
            }

            $rowDatas = [];

            foreach ($schedules as $numberRow => $schedule) {

                if($numberRow == 1) continue;

                if(count($schedule) < 5) {
                    continue;
                }

                $rowData = [
                    'code'      => trim($schedule[1]),
                    'price'     => trim($schedule[4]),
                    'quantity'  => trim($schedule[5]),
                ];

                if(empty($rowData['code']))
                {
                    continue;
                }

                $rowDatas[] = $rowData;
            }

            $branch = \Stock\Helper::getBranchCurrent();

            $selected = [
                'products.id',
                'products.code',
                'products.title',
                'products.attribute_label',
                'products.image',
                'products.price',
                'products.price_sale',
                'products.hasVariation',
                'products.parent_id',
                DB::raw("MAX(cle_inventories.price_cost) AS price_cost")
            ];

            $products = Product::widthVariation()->whereIn('code', array_map(function ($item) {
                    return $item['code'];
                }, $rowDatas))
                ->leftJoin('inventories', function ($join) use ($branch) {
                    $join->on('products.id', '=', 'inventories.product_id');
                    $join->where('inventories.branch_id', $branch->id);
                })
                ->whereNotNull('public')
                ->select($selected)
                ->groupBy('products.id')
                ->get();

            $response = [];

            if(have_posts($products))
            {
                foreach ($products as $key => $item)
                {
                    $item = $item->toObject();

                    $item->fullname = $item->title;

                    if(!empty($item->attribute_label))
                    {
                        $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
                    }

                    $item->image = Image::medium($item->image)->html();

                    $products[$key] = $item;

                    foreach ($rowDatas as $row)
                    {
                        if($row['code'] == $item->code)
                        {
                            $response[] = [
                                'id'                => $item->id,
                                'code'              => $item->code,
                                'title'             => $item->title,
                                'fullname'          => $item->fullname,
                                'attribute_label'   => $item->attribute_label ?? '',
                                'image'             => $item->image,
                                'parent_id'         => $item->parent_id,
                                'hasVariation'      => $item->hasVariation,
                                'price'             => (empty($row['price'])) ? $item->price_cost : $row['price'],
                                'quantity'          => $row['quantity'],
                            ];
                            break;
                        }
                    }
                }
            }

            response()->success(trans('Load file excel thành công'), $response);
        }

        response()->error(trans('load file excel không thành công'));
    }
}

Ajax::admin('StockPurchaseOrderAdminAjax::detail');
Ajax::admin('StockPurchaseOrderAdminAjax::loadProductsDetail');
Ajax::admin('StockPurchaseOrderAdminAjax::loadCashFlowDetail');
Ajax::admin('StockPurchaseOrderAdminAjax::loadProductsEdit');
Ajax::admin('StockPurchaseOrderAdminAjax::addDraft');
Ajax::admin('StockPurchaseOrderAdminAjax::saveDraft');
Ajax::admin('StockPurchaseOrderAdminAjax::add');
Ajax::admin('StockPurchaseOrderAdminAjax::save');
Ajax::admin('StockPurchaseOrderAdminAjax::cancel');
Ajax::admin('StockPurchaseOrderAdminAjax::print');
Ajax::admin('StockPurchaseOrderAdminAjax::export');
Ajax::admin('StockPurchaseOrderAdminAjax::exportDetail');
Ajax::admin('StockPurchaseOrderAdminAjax::import');