<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

class StockPurchaseReturnAdminAjax
{
    static function detail(\SkillDo\Http\Request $request): void
    {
        $id = $request->input('id');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng nhập không có trên hệ thống');
        }
        $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
        $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        $object->status         = Admin::badge(\Stock\Status\PurchaseReturn::tryFrom($object->status)->badge(), \Stock\Status\PurchaseReturn::tryFrom($object->status)->label());
        $object->total        = \Prd::price($object->sub_total - $object->total_payment - $object->return_discount);
        $object->sub_total      = \Prd::price($object->sub_total);
        $object->return_discount = \Prd::price($object->return_discount);
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

        $query = Qr::where('inventories_purchase_returns_details.purchase_return_id', $id);

        $selected = [
            'product_id',
            'product_name',
            'product_code',
            'product_attribute',
            'quantity',
            'price',
            'cost',
            'sub_total',
        ];

        $query->select($selected);

        # [Total decoders]
        $total = \Stock\Model\PurchaseReturnDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Stock\Model\PurchaseReturnDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' - <span class="fw-bold sugg-attr">'.$object->product_attribute.'</span>';
        }

        # [created table]
        $table = new \Stock\Table\PurchaseReturn\ProductDetail([
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

    static function loadProductsEdit(\SkillDo\Http\Request $request): void
    {
        $type  = $request->input('type');

        $id  = $request->input('id');

        if($type == 'source-product')
        {
            $branch  = \Stock\Helper::getBranchCurrent();

            $selected = [
                'products.id',
                'products.title',
                'products.code',
                'products.attribute_label',
                'products.image',
                'products.attribute_label',
                DB::raw("MAX(cle_inventories.price_cost) AS price_cost"),
                DB::raw("MAX(cle_inventories.price_cost) AS price"),
                DB::raw("SUM(cle_inventories.stock) AS quantity")
            ];

            $products = Product::widthVariation()->where('products.id', $id)
                ->leftJoin('inventories', function ($join) use ($branch) {
                    $join->on('products.id', '=', 'inventories.product_id');
                    $join->where('inventories.branch_id', $branch->id);
                })
                ->whereNotNull('public')
                ->select($selected)
                ->groupBy('products.id')
                ->get();
        }
        else if($type == 'purchase-orders')
        {
            $selected = [
                'products.id',
                'products.title',
                'products.code',
                'products.image',
                'products.attribute_label',
                'po.price as price_cost',
                'po.price',
                'po.quantity',
            ];

            $products = \Ecommerce\Model\Product::widthVariation()
                ->select($selected)
                ->leftJoin('inventories_purchase_orders_details as po', function ($join) use ($id) {
                    $join->on('po.product_id', '=', 'products.id');
                })
                ->where('po.purchase_order_id', $id)
                ->limit(500)
                ->orderBy('products.order')
                ->orderBy('products.created', 'desc')
                ->get();
        }
        else
        {
            $selected = [
                'products.id',
                'products.title',
                'products.code',
                'products.image',
                'products.attribute_label',
                'po.cost as price_cost',
                'po.price',
                'po.quantity',
                'po.sub_total',
            ];

            $products = \Ecommerce\Model\Product::widthVariation()
                ->select($selected)
                ->leftJoin('inventories_purchase_returns_details as po', function ($join) use ($id) {
                    $join->on('po.product_id', '=', 'products.id');
                })
                ->where('po.purchase_return_id', $id)
                ->limit(500)
                ->orderBy('products.order')
                ->orderBy('products.created', 'desc')
                ->get();
        }

        foreach ($products as $key => $item)
        {
            $item = $item->toObject();

            $item->fullname = $item->title;

            if(!empty($item->attribute_label))
            {
                $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
            }

            $item->image = Image::medium($item->image)->html();

            if(!isset($item->sub_total))
            {
                $item->sub_total = $item->price*$item->quantity;
            }

            $item->sub_total = Prd::price($item->sub_total);

            $products[$key] = $item;
        }

        response()->success('Load dữ liệu thành công', $products);
    }

    static function addDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $purchaseReturn, // Phiếu trả hàng
            $branch, // chính nhánh
            $supplier, // nhà cung cấp
            $productsPurchase, // Danh sách sản phẩm đã chọn
            $purchaseReturnDetails, // Danh sách chi tiết phiếu trả hàng
            $productsDetail // Danh sách chi tiết sản phẩm sẽ xóa
        ] = static::purchaseDataDraft($request);

        try
        {
            DB::beginTransaction();

            $purchaseReturnId = \Stock\Model\PurchaseReturn::create($purchaseReturn);

            if(empty($purchaseReturnId) || is_skd_error($purchaseReturnId))
            {
                response()->error('Tạo phiếu trả hàng thất bại');
            }

            foreach ($purchaseReturnDetails as &$detail)
            {
                $detail['purchase_return_id'] = $purchaseReturnId;
            }

            DB::table('inventories_purchase_returns_details')->insert($purchaseReturnDetails);

            DB::commit();

            response()->success('Lưu tạm phiếu trả hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu trả hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function saveDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu trả hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng đã hủy hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\PurchaseReturn::success->value || $object->status === \Stock\Status\PurchaseReturn::cancel->value)
        {
            response()->error('Phiếu trả hàng này không thể cập nhật');
        }

        [
            $purchaseReturn, // Phiếu trả hàng
            $branch, // chính nhánh
            $supplier, // nhà cung cấp
            $productsPurchase, // Danh sách sản phẩm đã chọn
            $purchaseReturnDetails, // Danh sách chi tiết phiếu trả hàng
            $productsDetail // Danh sách chi tiết sản phẩm sẽ xóa
        ] = static::purchaseDataDraft($request, $object);

        try
        {
            DB::beginTransaction();

            \Stock\Model\PurchaseReturn::whereKey($id)->update($purchaseReturn);

            //Lấy danh sách chi tiết phiếu sẽ cập nhật
            $purchaseReturnDetailsUp = [];

            foreach ($purchaseReturnDetails as $key => $detail)
            {
                if(empty($detail['purchase_return_id']))
                {
                    $detail['purchase_return_id'] = $object->id;
                    $purchaseReturnDetails[$key] = $detail;
                }

                if(!empty($detail['purchase_return_detail_id']))
                {
                    $purchaseReturnDetailsUp[] = $detail;
                    unset($purchaseReturnDetails[$key]);
                }
            }

            //Thêm mới
            if(!empty($purchaseReturnDetails))
            {
                DB::table('inventories_purchase_returns_details')->insert($purchaseReturnDetails);
            }

            //Cập nhật
            if(!empty($purchaseReturnDetailsUp))
            {
                \Stock\Model\PurchaseReturnDetail::updateBatch($purchaseReturnDetailsUp, 'purchase_return_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\PurchaseReturnDetail::whereKey($productsDetail->pluck('purchase_return_detail_id')->toArray())->delete();
            }

            DB::commit();

            response()->success('Lưu tạm phiếu trả hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu trả hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function purchaseDataDraft(\SkillDo\Http\Request $request, $object = null): array
    {
        $isEdit = !empty($object);

        $time = $request->input('time');

        if(!empty($time))
        {
            $time = strtotime(str_replace('/', '-', $time));

            if($time > time())
            {
                response()->error('Thời gian nhập hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $purchaseReturn = [
            'status'            => \Stock\Status\PurchaseReturn::draft->value,
            'return_discount'   => (int)$request->input('return_discount'),
            'total_payment'     => (int)$request->input('total_payment'),
            'purchase_date' => $time
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $purchaseReturn['branch_id']     = $branch->id;
            $purchaseReturn['branch_name']   = $branch->name;
        }

        //Người nhập hàng
        $purchaseId = (int)$request->input('purchase');

        $purchase = (!empty($purchaseId)) ? \SkillDo\Model\User::find($purchaseId) : Auth::user();

        if(!empty($purchase->firstname) || !empty($purchase->lastname))
        {
            $purchase_name = $purchase->firstname.' '.$purchase->lastname;
        }

        $purchaseReturn['purchase_id']     = $purchase->id ?? 0;
        $purchaseReturn['purchase_name']   = $purchase_name ?? '';

        //Nhà cung cấp
        $supplierId = (int)$request->input('supplier');

        if(!empty($supplierId))
        {
            $supplier = \Stock\Model\Suppliers::find($supplierId);
        }

        $purchaseReturn['supplier_id']     = $supplier->id ?? 0;
        $purchaseReturn['supplier_name']   = $supplier->name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\PurchaseReturn::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\PurchaseReturn::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu trả hàng này đã được sử dụng');
            }

            $purchaseReturn['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productsPurchase = $request->input('products');

        $purchaseReturn['sub_total'] = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);

        $purchaseReturn['total_quantity'] = array_reduce($productsPurchase, function ($sum, $item) {
            return $sum + ($item['quantity']);
        }, 0);

        //Danh sách sản phẩm phiếu trả hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $purchaseReturnDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\PurchaseReturnDetail::where('purchase_return_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productsPurchase as $product)
        {
            $purchaseReturnDetail = [
                'purchase_return_id'        => $object->id ?? 0,
                'product_id'                => $product['id'],
                'product_code'              => $product['code'] ?? '',
                'product_name'              => $product['title'],
                'product_attribute'         => $product['attribute_label'] ?? '',
                'quantity'                  => $product['quantity'],
                'price'                     => $product['price'],
                'sub_total'                 => $product['quantity']*$product['price'],
                'cost'                      => Str::price($product['cost']),
            ];

            if ($productsDetail->has($product['id']))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\PurchaseReturn::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $purchaseReturnDetail['purchase_return_detail_id'] = $productDetail->purchase_return_detail_id;

                $purchaseReturnDetails[] = $purchaseReturnDetail;

                unset($productsDetail[$product['id']]);

                continue;
            }

            // Thêm sản phẩm mới
            $purchaseReturnDetails[] = $purchaseReturnDetail;
        }

        return [
            $purchaseReturn,
            $branch,
            $supplier ?? null,
            $productsPurchase,
            $purchaseReturnDetails,
            $productsDetail
        ];
    }

    static function add(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $purchaseReturn, // Phiếu trả hàng
            $branch, // Chi nhánh
            $supplier, // Nhà cung cấp
            $productsPurchase,
            $inventories, // Kho hàng
            $products, // Sản phẩm
            $purchaseReturnDetails, // Chi tiết phiếu trả hàng
            $inventoryCosts, //danh sách sản phẩm cập nhật giá vốn
            $productsDetail // Chi tiết sản phẩm xóa đi
        ] = static::purchaseData($request);

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($purchaseReturnDetails as $detail)
        {
            if (!$inventories->has($detail['product_id']))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm ' . $detail['product_name']);
            }

            $inventory = $inventories[$detail['product_id']];

            if ($inventory->stock < $detail['quantity'])
            {
                response()->error(
                    (empty($detail['product_code']) ? 'Sản phẩm ' . $detail['product_title'] . ' ' . $detail['product_attribute']
                        : 'Mã hàng ' . $detail['product_code']) . ': không đủ số lượng tồn kho để trả hàng'
                );
            }

            $newStock = $inventory->stock - $detail['quantity'];

            $inventoryUpdate = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value,
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
                //Đối tác
                'partner_id' => $supplier->id ?? 0,
                'partner_code' => $supplier->code ?? '',
                'partner_name' => $supplier->name ?? '',
                'partner_type' => !empty($supplier->id) ? 'S' : '',
                //Thông tin
                'cost'          => $detail['cost_new'],
                'price'         => $detail['price']*$detail['quantity'],
                'quantity'      => $detail['quantity']*-1,
                'start_stock'   => $inventory->stock,
                'end_stock'     => $newStock,
            ];
        }

        try {

            DB::beginTransaction();

            //Tạo phiếu nhập hàng
            $purchaseReturnId = \Stock\Model\PurchaseReturn::create($purchaseReturn);

            if(empty($purchaseReturnId) || is_skd_error($purchaseReturnId))
            {
                response()->error('Tạo phiếu trả hàng thất bại');
            }

            if(empty($purchaseReturn['code']))
            {
                $purchaseReturn['code'] = \Stock\Helper::code(\Stock\Prefix::purchaseReturn->value, $purchaseReturnId);
            }

            // Cập nhật mã phiếu vào lịch sử kho
            foreach ($inventoriesHistories as $key => $history)
            {
                $history['target_id'] = $purchaseReturnId;
                $history['target_code'] = $purchaseReturn['code'];
                $history['target_name'] = 'Trả hàng';
                $history['target_type'] = \Stock\Prefix::purchaseReturn->value;
                $inventoriesHistories[$key] = $history;
            }

            // Cập nhật purchase_return_id
            foreach ($purchaseReturnDetails as &$detail)
            {
                $detail['purchase_return_id'] = $purchaseReturnId;
                unset($detail['purchase_return_detail_id']);
            }

            DB::table('inventories_purchase_returns_details')->insert($purchaseReturnDetails);

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
                static::debt($supplier, $purchaseReturnId, $purchaseReturn);
            }

            DB::commit();

            response()->success('Tạo phiếu trả hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu trả hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu trả hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\PurchaseReturn::success->value || $object->status === \Stock\Status\PurchaseReturn::cancel->value)
        {
            response()->error('Phiếu trả hàng này không thể cập nhật');
        }

        [
            $purchaseReturn, // Phiếu trả hàng
            $branch, // Chi nhánh
            $supplier, // Nhà cung cấp
            $productsPurchase,
            $inventories, // Kho hàng
            $products, // Sản phẩm
            $purchaseReturnDetails, // Chi tiết phiếu trả hàng
            $inventoryCosts, //danh sách sản phẩm cập nhật giá vốn
            $productsDetail // Chi tiết sản phẩm xóa đi
        ] = static::purchaseData($request, $object);

        if(empty($purchaseReturnDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }
        //Sản phẩm của phiếu trả cập nhật
        $purchaseReturnDetailsUp = [];

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($purchaseReturnDetails as $key => $detail)
        {
            if (!$inventories->has($detail['product_id']))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm ' . $detail['product_name']);
            }

            $inventory = $inventories[$detail['product_id']];

            if ($inventory->stock < $detail['quantity'])
            {
                response()->error(
                    (empty($detail['product_code']) ? 'Sản phẩm ' . $detail['product_title'] . ' ' . $detail['product_attribute']
                        : 'Mã hàng ' . $detail['product_code']) . ': không đủ số lượng tồn kho để trả hàng'
                );
            }

            $newStock = $inventory->stock - $detail['quantity'];

            $inventoryUpdate = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value,
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
                //Đối tác
                'partner_id' => $supplier->id ?? 0,
                'partner_code' => $supplier->code ?? '',
                'partner_name' => $supplier->name ?? '',
                'partner_type' => !empty($supplier->id) ? 'S' : '',
                //Đối tượng
                'target_id'   => $object->id ?? 0,
                'target_code' => $object->code ?? '',
                'target_type' => \Stock\Prefix::purchaseReturn->value,
                'target_name' => 'Trả hàng',

                //Thông tin
                'cost'          => $detail['cost_new'],
                'price'         => $detail['price']*$detail['quantity'],
                'quantity'      => $detail['quantity']*-1,
                'start_stock'   => $inventory->stock,
                'end_stock'     => $newStock,
            ];

            if(empty($detail['purchase_return_id']))
            {
                $detail['purchase_return_id'] = $object->id;
                $purchaseReturnDetails[$key] = $detail;
            }

            if(!empty($detail['purchase_return_detail_id']))
            {
                $purchaseReturnDetailsUp[] = $detail;
                unset($purchaseReturnDetails[$key]);
            }
            else {
                unset($detail['purchase_return_detail_id']);
                $purchaseReturnDetails[$key] = $detail;
            }
        }

        try {

            DB::beginTransaction();

            //Cập nhật phiếu trả hàng
            \Stock\Model\PurchaseReturn::whereKey($id)->update($purchaseReturn);

            //Thêm mới
            if(!empty($purchaseReturnDetails))
            {
                DB::table('inventories_purchase_returns_details')->insert($purchaseReturnDetails);
            }

            //Cập nhật
            if(!empty($purchaseReturnDetailsUp))
            {
                \Stock\Model\PurchaseReturnDetail::updateBatch($purchaseReturnDetailsUp, 'purchase_return_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\PurchaseReturnDetail::whereKey($productsDetail->pluck('purchase_return_detail_id')->toArray())->delete();
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
                static::debt($supplier, $id, $purchaseReturn);
            }

            DB::commit();

            response()->success('Lưu phiếu trả hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu trả hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function validate(\SkillDo\Http\Request $request, $rules = []): void
    {
        $validate = $request->validate([
            'return_discount'   => Rule::make('Giảm giá')->notEmpty()->integer()->min(0),
            'total_payment'     => Rule::make('NCC đã trả')->notEmpty()->integer()->min(0),
            'products'          => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'     => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.quantity'  => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'   => Rule::make('Giá trị sản phẩm')->notEmpty()->integer()->min(0),
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
            $time = strtotime(str_replace('/', '-', $time));

            if($time > time())
            {
                response()->error('Thời gian nhập hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $purchaseReturn = [
            'status'            => \Stock\Status\PurchaseReturn::success->value,
            'purchase_date'     => $time,
            'return_discount'   => (int)$request->input('return_discount'),
            'total_payment'     => (int)$request->input('total_payment')
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh đã đóng cửa hoặc không còn trên hệ thống');
        }

        $purchaseReturn['branch_id']     = $branch->id;
        $purchaseReturn['branch_name']   = $branch->name;

        //Người nhập hàng
        $purchaseId = $request->input('purchase');

        $purchase = (!empty($purchaseId)) ? \SkillDo\Model\User::find($purchaseId) : Auth::user();

        if(empty($purchase))
        {
            response()->error('Không tìm thấy Nhân viên trả hàng');
        }

        $purchaseReturn['purchase_id']     = $purchase->id;
        $purchaseReturn['purchase_name']   = $purchase->firstname.' '.$purchase->lastname;

        //Nhà cung cấp
        $supplierId = (int)$request->input('supplier');

        if(!empty($supplierId))
        {
            $supplier = \Stock\Model\Suppliers::find($supplierId);
        }

        $purchaseReturn['supplier_id']     = $supplier->id ?? 0;
        $purchaseReturn['supplier_name']   = $supplier->name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\PurchaseReturn::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\PurchaseReturn::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu trả này đã được sử dụng');
            }

            $purchaseReturn['code'] = $code;

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

        if($total < $purchaseReturn['return_discount'])
        {
            response()->error('Số tiền khuyến mãi không được lớn hơn tổng tiền hàng');
        }

        if(($total - $purchaseReturn['return_discount']) < $purchaseReturn['total_payment'])
        {
            response()->error('Số tiền đã thanh toán không được lớn hơn số tiền nhà cung cấp phải trả');
        }

        $purchaseReturn['sub_total'] = $total;

        $purchaseReturn['total_quantity'] = $quantity;

        $productsId = [];

        //Danh sách sản phẩm phiếu trả hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $purchaseReturnDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\PurchaseReturnDetail::where('purchase_return_id', $object->id)
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

            if($purchaseReturn['return_discount'] != 0)
            {
                $total = $product['quantity']*$product['price'];

                $percent = ceil($total / $purchaseReturn['sub_total'] * 100);

                $discount = $percent * $purchaseReturn['return_discount'] / 100;
            }

            $purchaseReturnDetail = [
                'purchase_return_id'  => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'quantity'           => $product['quantity'],
                'price'              => $product['price'],
                'sub_total'          => $product['price']*$product['quantity'],
                'status'             => \Stock\Status\PurchaseReturn::success->value,
                'discount'           => $discount,
            ];

            if ($productsDetail->has($product['id']))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\PurchaseReturn::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $purchaseReturnDetail['purchase_return_detail_id'] = $productDetail->purchase_return_detail_id;

                $purchaseReturnDetails[] = $purchaseReturnDetail;

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $purchaseReturnDetails[] = $purchaseReturnDetail;
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

        $purchaseMap = (\Illuminate\Support\Collection::make($purchaseReturnDetails))
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

            $priceCost = ($inventory->stock == $purchase['quantity'])
                ? $inventory->price_cost
                : (($inventory->price_cost * $inventory->stock - $inventory->price_cost * $purchase['quantity']) / ($inventory->stock - $purchase['quantity']));

            $priceCost = ceil($priceCost);

            $purchaseMap->transform(function ($item, $key) use ($inventory, $priceCost, $product) {
                if ($key === $product->id) {
                    $item['cost'] = $inventory->price_cost;
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

        $purchaseReturnDetails = $purchaseMap->values()->toArray();

        return [
            $purchaseReturn,
            $branch,
            $supplier ?? null,
            $productsPurchase,
            $inventories,
            $products,
            $purchaseReturnDetails,
            $inventoryCosts,
            $productsDetail
        ];
    }

    static function debt($supplier, $purchaseReturnId, $purchaseReturn): void
    {
        //Số tiền ncc cần thanh toán
        $amount = $purchaseReturn['sub_total'] -  $purchaseReturn['return_discount'];

        //Tạo công nợ cho đơn xuất hàng
        \Stock\Model\Debt::create([
            'before'        => ($supplier->debt)*-1,
            'amount'        => $amount,
            'balance'       => ($supplier->debt - $amount)*-1,
            'partner_id'    => $supplier->id,
            'target_id'     => $purchaseReturnId,
            'target_code'   => $purchaseReturn['code'],
            'target_type'   => \Stock\Prefix::purchaseReturn->value,
            'time'          => $purchaseReturn['purchase_date']
        ]);

        if($purchaseReturn['total_payment'] > 0)
        {
            //Tạo phiếu thu
            $code = \Stock\Helper::code(\Stock\Prefix::cashFlowPurchaseReturn->value, $purchaseReturnId);

            $idCashFlow = \Stock\Model\CashFlow::create([
                'code'      => $code,
                'branch_id' => $purchaseReturn['branch_id'],
                'branch_name' => $purchaseReturn['branch_name'],
                //Người thu
                'user_id' => $purchaseReturn['purchase_id'],
                'user_name' => $purchaseReturn['purchase_name'],
                //người chi
                'partner_id' => $supplier->id,
                'partner_code' => $supplier->code,
                'partner_name'  => $supplier->name,
                'address' => $supplier->address,
                'phone' => $supplier->phone,
                'partner_type' => 'S',

                //Loại
                'group_id' => \Stock\CashFlowGroup\Transaction::supplierReceipt->id(),
                'group_name' => \Stock\CashFlowGroup\Transaction::supplierReceipt->label(),
                'origin' => 'purchase',
                'method' => 'cash',
                'amount' => $purchaseReturn['total_payment'],

                'target_id'     => $purchaseReturnId,
                'target_code'   => $purchaseReturn['code'],
                'target_type'   => \Stock\Prefix::purchaseReturn->value,
                'time'          => $purchaseReturn['purchase_date'],
                'status'        => \Stock\Status\CashFlow::success->value,
                'user_created'  => Auth::id()
            ]);

            //Tạo công nợ cho phiêu thu
            \Stock\Model\Debt::create([
                'before'        => ($supplier->debt)*-1,
                'amount'        => $purchaseReturn['total_payment']*-1,
                'balance'       => ($supplier->debt - $amount + $purchaseReturn['total_payment'])*-1,
                'partner_id'    => $supplier->id,
                'target_id'     => $idCashFlow,
                'target_code'   => $code,
                'target_type'   => 'PT'.\Stock\Prefix::purchaseReturn->value,
                'time'          => $purchaseReturn['purchase_date']
            ]);
        }

        \Stock\Model\Suppliers::whereKey($supplier->id)
            ->update([
                'total_invoiced' => DB::raw('total_invoiced - '. ($purchaseReturn['sub_total'] -  $purchaseReturn['return_discount'])),
                'debt' => DB::raw('debt - '. ($purchaseReturn['sub_total'] -  $purchaseReturn['return_discount'] + $purchaseReturn['total_payment'])),
            ]);
    }
    
    static function cancel(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'data' => Rule::make('phiếu trả hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('data');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\PurchaseReturn::cancel->value)
        {
            response()->error('phiếu trả hàng này đã hủy');
        }
        if($object->status === \Stock\Status\PurchaseReturn::success->value)
        {
            response()->error('phiếu trả hàng này đã hoàn thành không thể hủy');
        }

        \Stock\Model\PurchaseReturnDetail::where('purchase_return_id', $object->id)
            ->where('status', \Stock\Status\PurchaseReturn::draft->value)
            ->update([
                'status' => \Stock\Status\PurchaseReturn::cancel->value,
            ]);

        \Stock\Model\PurchaseReturn::whereKey($object->id)->update([
            'status' => \Stock\Status\PurchaseReturn::cancel->value,
        ]);

        response()->success('Hủy phiếu trả hàng thành công', [
            'status' => Admin::badge(\Stock\Status\PurchaseReturn::cancel->badge(), 'Đã hủy')
        ]);
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu trả hàng nhập')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
        $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);

        $userCreated = User::find($object->user_created);
        $object->user_created_name = (have_posts($userCreated)) ? $userCreated->firstname.' '.$userCreated->lastname : '';

        $object->sub_total = Prd::price($object->sub_total);

        $object->total_payment = Prd::price($object->total_payment);

        $products = \Stock\Model\PurchaseReturnDetail::where('purchase_return_id', $object->id)->get();

        response()->success('Dữ liệu print', [
            'purchase' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
                $item->cost = Prd::price($item->cost);
                $item->sub_total = Prd::price($item->sub_total);
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
                response()->error(trans('Không có phiếu trả hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'checked')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu trả hàng nào để xuất'));
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

        $objects = \Stock\Model\PurchaseReturn::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu trả hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            $object->purchase_date = !empty($object->purchase_date) ? $object->purchase_date : strtotime($object->created);
            $object->purchase_date = date('d/m/Y H:s', $object->purchase_date);
        }

        $export = new \Stock\Export();

        $export->header('code', 'Mã trả hàng', function($item) {
            return $item->code ?? '';
        });

        $export->header('purchase_date', 'Ngày trả', function($item) {
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
            return number_format($item->return_discount);
        });

        $export->header('total', 'NCC Cần Trả', function($item) {
            return number_format($item->sub_total - $item->return_discount - $item->total_payment);
        });

        $export->header('total_payment', 'NCC Đã Trả', function($item) {
            return number_format($item->total_payment);
        });

        $export->setTitle('DSPhieuTraHangNhap_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/purchase-return/', 'DanhSachPhieuTraHangNhap_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu trả hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\PurchaseReturn::find($id);

        if(empty($object))
        {
            response()->error('phiếu trả hàng không còn trên hệ thống');
        }

        $products = \Stock\Model\PurchaseReturnDetail::where('purchase_return_id', $object->id)->get();

        $export = new \Stock\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->quantity ?? 0);
        });

        $export->header('cost', 'Giá vốn', function($item) {
            return number_format($item->cost ?? 0);
        });

        $export->header('price', 'Giá trả hàng', function($item) {
            return number_format($item->price ?? 0);
        });

        $export->header('total', 'Thành tiền', function($item) {
            return number_format($item->sub_total);
        });

        $export->setTitle('DSChiTietTraHang_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/purchase-return/', 'DanhSachChiTietTraHang_'.$object->code.'.xlsx');

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

            $myPath = STOCK_NAME.'/assets/imports/purchase-return';

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

                if(count($schedule) < 6) {
                    continue;
                }

                $rowData = [
                    'code'          => trim($schedule[1]),
                    'quantity'      => trim($schedule[4]),
                    'price_cost'    => trim($schedule[5]),
                    'price'         => trim($schedule[6])
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
                                'id'            => $item->id,
                                'code'         => $item->code,
                                'title'         => $item->title,
                                'fullname'      => $item->fullname,
                                'attribute_label' => $item->attribute_label ?? '',
                                'image'         => $item->image,
                                'price_cost'    => (empty($row['price_cost'])) ? $item->price_cost : $row['price_cost'],
                                'price'         => (empty($row['price'])) ? $item->price_cost : $row['price'],
                                'quantity'      => $row['quantity'],
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

Ajax::admin('StockPurchaseReturnAdminAjax::detail');
Ajax::admin('StockPurchaseReturnAdminAjax::loadProductsDetail');
Ajax::admin('StockPurchaseReturnAdminAjax::loadProductsEdit');
Ajax::admin('StockPurchaseReturnAdminAjax::addDraft');
Ajax::admin('StockPurchaseReturnAdminAjax::saveDraft');
Ajax::admin('StockPurchaseReturnAdminAjax::add');
Ajax::admin('StockPurchaseReturnAdminAjax::save');
Ajax::admin('StockPurchaseReturnAdminAjax::cancel');
Ajax::admin('StockPurchaseReturnAdminAjax::print');
Ajax::admin('StockPurchaseReturnAdminAjax::export');
Ajax::admin('StockPurchaseReturnAdminAjax::exportDetail');
Ajax::admin('StockPurchaseReturnAdminAjax::import');