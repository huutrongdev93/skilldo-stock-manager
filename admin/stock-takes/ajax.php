<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

class StockTakeAdminAjax
{
    static function loadProductsDetail(\SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $id  = $request->input('id');

        $query = Qr::where('stock_take_details.stock_take_id', $id);

        $selected = [
            'product_id',
            'product_name',
            'product_code',
            'product_attribute',
            'stock',
            'actual_quantity',
            'adjustment_quantity',
            'adjustment_price',
        ];

        $query->select($selected);

        # [Total decoders]
        $total = \Stock\Model\StockTakeDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Stock\Model\StockTakeDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' '.$object->product_attribute;
        }

        # [created table]
        $table = new \Stock\Table\StockTake\ProductDetail([
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
        $id  = $request->input('id');

        $selected = [
            'products.id',
            'products.title',
            'products.code',
            'products.attribute_label',
            'products.image',
            'products.attribute_label',
            'po.price as price_cost',
            'po.actual_quantity as quantity',
        ];

        $query = Qr::select($selected);

        $query->leftJoin('stock_take_details as po', function ($join) use ($id) {
            $join->on('po.product_id', '=', 'products.id');
        });

        $query->where('po.stock_take_id', $id);

        $query
            ->limit(500)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc');

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        if(have_posts($products))
        {
            $inventories = \Stock\Model\Inventory::whereIn('product_id', $products
                ->pluck('id')
                ->toArray())
                ->select('id', 'product_id', 'stock', 'reserved')
                ->get()
                ->keyBy('product_id');

            foreach ($products as $key => $item)
            {
                if(!$inventories->has($item->id))
                {
                    unset($products[$key]);
                    continue;
                }

                $inventory = $inventories->get($item->id);

                $item = $item->toObject();

                $item->stock = $inventory->stock;

                $item->fullname = $item->title;

                if(!empty($item->attribute_label))
                {
                    $item->fullname .= ' <span class="fw-bold sugg-attr">'.$item->attribute_label.'</span>';
                }

                $item->image = Image::medium($item->image)->html();

                $products[$key] = $item;
            }
        }



        response()->success('Load dữ liệu thành công', $products);
    }

    static function addDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $stockTake,
            $branch,
            $stockTakeDetails,
            $productsDetail
        ] = static::dataDraft($request);

        try
        {
            DB::beginTransaction();

            $stockTakeId = \Stock\Model\StockTake::create($stockTake);

            if(empty($stockTakeId) || is_skd_error($stockTakeId))
            {
                response()->error('Tạo phiếu kiểm kho hàng thất bại');
            }

            foreach ($stockTakeDetails as &$detail)
            {
                $detail['stock_take_id'] = $stockTakeId;
            }

            DB::table('stock_take_details')->insert($stockTakeDetails);

            DB::commit();

            response()->success('Lưu tạm phiếu kiểm kho hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu kiểm kho hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function saveDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu kiểm kho hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\StockTake::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\StockTake::success->value || $object->status === \Stock\Status\StockTake::cancel->value)
        {
            response()->error('Trạng thái phiếu kiểm kho không cho phép chỉnh sữa');
        }

        [
            $stockTake,
            $branch,
            $stockTakeDetails,
            $productsDetail
        ] = static::dataDraft($request, $object);

        \Stock\Model\StockTake::whereKey($id)->update($stockTake);

        //Lấy danh sách chi tiết phiếu kiểm kho sẽ cập nhật
        $stockTakeDetailsUp = [];

        foreach ($stockTakeDetails as $key => $detail)
        {
            if(empty($detail['stock_take_id']))
            {
                $detail['stock_take_id'] = $object->id;
                $stockTakeDetails[$key] = $detail;
            }

            if(!empty($detail['stock_take_detail_id']))
            {
                $stockTakeDetailsUp[] = $detail;
                unset($stockTakeDetails[$key]);
            }
        }

        try
        {
            DB::beginTransaction();

            //Thêm mới
            if(!empty($stockTakeDetails))
            {
                DB::table('stock_take_details')->insert($stockTakeDetails);
            }

            //Cập nhật
            if(!empty($stockTakeDetailsUp))
            {
                \Stock\Model\StockTakeDetail::updateBatch($stockTakeDetailsUp, 'stock_take_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\StockTakeDetail::whereKey($productsDetail->pluck('stock_take_detail_id')->toArray())->delete();
            }

            DB::commit();

            response()->success('Lưu tạm phiếu kiểm kho hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu kiểm kho hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function dataDraft(\SkillDo\Http\Request $request, $object = null): array
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

        $stockTake = [
            'status' => \Stock\Status\StockTake::draft->value,
            'total_actual_quantity' => 0,
            'total_actual_price' => 0,
            'total_increase_quantity' => 0,
            'total_increase_price' => 0,
            'total_reduced_quantity' => 0,
            'total_reduced_price' => 0,
            'total_adjustment_quantity' => 0,
            'total_adjustment_price' => 0,
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(!empty($branch))
        {
            $stockTake['branch_id']     = $branch->id;
            $stockTake['branch_name']   = $branch->name;
        }

        //Người nhập hàng
        $userId = (int)$request->input('user');

        $user = (!empty($userId)) ? \SkillDo\Model\User::find($userId) : Auth::user();

        if(!empty($user->firstname) || !empty($user->lastname))
        {
            $user_name = $user->firstname.' '.$user->lastname;
        }

        $stockTake['user_id']     = $user->id ?? 0;
        $stockTake['user_name']   = $user_name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\StockTake::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\StockTake::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu kiểm kho này đã được sử dụng');
            }

            $stockTake['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productStockTakes = $request->input('products');

        //Danh sách sản phẩm phiếu kiểm kho hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $stockTakeDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\StockTakeDetail::where('stock_take_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productStockTakes as $product)
        {
            $adjustment_quantity = $product['quantity'] - $product['stock'];

            $adjustment_price    = ($product['quantity'] - $product['stock'])*$product['price'];

            // Tổng số lượng hàng thực tế
            $stockTake['total_actual_quantity'] += $product['quantity'];

            // Tổng giá trị hàng thực tế
            $stockTake['total_actual_price'] += $product['quantity']*$product['price'];

            // Tổng hàng tăng
            if($adjustment_quantity > 0)
            {
                $stockTake['total_increase_quantity'] += $adjustment_quantity;
                $stockTake['total_increase_price'] += $adjustment_price;
            }

            // Tổng hàng giảm
            if($adjustment_quantity < 0)
            {
                $stockTake['total_reduced_quantity'] += $adjustment_quantity;
                $stockTake['total_reduced_price'] += $adjustment_price;
            }

            if (isset($productsDetail[$product['id']]))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\StockTake::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $stockTakeDetails[] = [
                    'stock_take_detail_id'  => $productDetail->stock_take_detail_id,
                    'stock_take_id'         => $object->id,
                    'product_id'         => $product['id'],
                    'product_code'       => $product['code'] ?? '',
                    'product_name'       => $product['title'],
                    'product_attribute'  => $product['attribute_label'] ?? '',
                    'stock'              => $product['stock'],
                    'price'              => $product['price'],
                    'actual_quantity'    => $product['quantity'],
                    'adjustment_quantity'=> $adjustment_quantity,
                    'adjustment_price'   => $adjustment_price,
                ];

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $stockTakeDetails[] = [
                'stock_take_id'      => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'stock'              => $product['stock'],
                'price'              => $product['price'],
                'actual_quantity'    => $product['quantity'],
                'adjustment_quantity'=> $adjustment_quantity,
                'adjustment_price'   => $adjustment_price,
            ];
        }

        $stockTake['total_adjustment_quantity'] += $stockTake['total_increase_quantity'] + $stockTake['total_reduced_quantity'];

        $stockTake['total_adjustment_price'] += $stockTake['total_increase_price'] + $stockTake['total_reduced_price'];
        
        return [
            $stockTake,
            $branch,
            $stockTakeDetails,
            $productsDetail
        ];
    }

    static function add(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $stockTake,
            $branch,
            $inventories,
            $stockTakeDetails,
            $productsDetail
        ] = static::data($request);

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($stockTakeDetails as $detail)
        {
            $inventory = $inventories[$detail['product_id']];

            if($detail['actual_quantity'] == $inventory->stock)
            {
                continue;
            }

            $newStock = $detail['actual_quantity'];

            $inventoriesUpdate[] = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value
            ];

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                'message'       => [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $newStock,
                    'stockTakeCode'  => '',
                ],
                'action'        => ($detail['actual_quantity'] > $inventory->stock) ? 'cong' : 'tru',
                'type'          => 'stock',
            ];
        }

        try {

            DB::beginTransaction();

            //Tạo phiếu kiểm kho hàng
            $stockTakeId = \Stock\Model\StockTake::create($stockTake);

            if(empty($stockTakeId) || is_skd_error($stockTakeId))
            {
                response()->error('Tạo phiếu kiểm kho hàng thất bại');
            }

            // Cập nhật mã phiếu vào lịch sử kho
            foreach ($inventoriesHistories as $key => $history)
            {
                $history['message']['stockTakeCode'] = (!(empty($stockTake['code']))) ? $stockTake['code'] : \Stock\Helper::code('KK', $stockTakeId);
                $history['message'] = InventoryHistory::message('stock_take_update', $history['message']);
                $inventoriesHistories[$key] = $history;
            }

            // Cập nhật stock_take_id
            foreach ($stockTakeDetails as &$detail)
            {
                $detail['stock_take_id'] = $stockTakeId;
                unset($detail['stock_take_detail_id']);
            }

            DB::table('stock_take_details')->insert($stockTakeDetails);

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            DB::commit();

            response()->success('Tạo phiếu kiểm kho hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu kiểm kho hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu kiểm kho hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Stock\Model\StockTake::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status !== \Stock\Status\StockTake::draft->value)
        {
            response()->error('Trạng thái phiếu kiểm kho này đã không thể cập nhật');
        }

        [
            $stockTake,
            $branch,
            $inventories,
            $stockTakeDetails,
            $productsDetail
        ] = static::data($request, $object);

        if(empty($stockTakeDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }

        //Sản phẩm của phiếu kiểm kho cập nhật
        $stockTakeDetailsUp = [];

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($stockTakeDetails as $key => $detail)
        {
            $inventory = $inventories[$detail['product_id']];

            if($detail['actual_quantity'] != $inventory->stock)
            {
                $newStock = $detail['actual_quantity'];

                $inventoriesUpdate[] = [
                    'id'     => $inventory->id,
                    'stock'  => $newStock,
                    'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value
                ];

                $inventoriesHistories[] = [
                    'inventory_id'  => $inventory->id,
                    'product_id'    => $inventory->product_id,
                    'branch_id'     => $inventory->branch_id,
                    'message'       => InventoryHistory::message('stock_take_update', [
                        'stockBefore'   => $inventory->stock,
                        'stockAfter'    => $newStock,
                        'stockTakeCode'  => $object->code,
                    ]),
                    'action'        => ($detail['actual_quantity'] > $inventory->stock) ? 'cong' : 'tru',
                    'type'          => 'stock',
                ];
            }

            if(empty($detail['stock_take_id']))
            {
                $detail['stock_take_id'] = $object->id;
                $stockTakeDetails[$key] = $detail;
            }

            if(!empty($detail['stock_take_detail_id']))
            {
                $stockTakeDetailsUp[] = $detail;
                unset($stockTakeDetails[$key]);
            }
            else {
                unset($detail['stock_take_detail_id']);
                $stockTakeDetails[$key] = $detail;
            }
        }

        try {

            DB::beginTransaction();

            //Cập nhật phiếu kiểm kho hàng
            \Stock\Model\StockTake::whereKey($id)->update($stockTake);

            //Thêm mới
            if(!empty($stockTakeDetails))
            {
                DB::table('stock_take_details')->insert($stockTakeDetails);
            }

            //Cập nhật
            if(!empty($stockTakeDetailsUp))
            {
                \Stock\Model\StockTakeDetail::updateBatch($stockTakeDetailsUp, 'stock_take_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\StockTakeDetail::whereKey($productsDetail->pluck('stock_take_detail_id')->toArray())->delete();
            }

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            DB::commit();

            response()->success('Lưu tạm phiếu kiểm kho hàng thành công');
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
            'products'              => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'         => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.quantity'   => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'      => Rule::make('Giá vốn sản phẩm')->notEmpty()->integer()->min(0),
            'products.*.stock'      => Rule::make('Tồn kho')->notEmpty()->integer()->min(0),
            ...$rules
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }
    }

    static function data(\SkillDo\Http\Request $request, $object = null): array
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

        $stockTake = [
            'status'        => \Stock\Status\StockTake::success->value,
            'balance_date' => $time,
            'total_actual_quantity' => 0,
            'total_actual_price' => 0,
            'total_increase_quantity' => 0,
            'total_increase_price' => 0,
            'total_reduced_quantity' => 0,
            'total_reduced_price' => 0,
            'total_adjustment_quantity' => 0,
            'total_adjustment_price' => 0,
        ];

        //Chi nhánh
        $branch = \Stock\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh đã đóng cửa hoặc không còn trên hệ thống');
        }

        $stockTake['branch_id']     = $branch->id;
        $stockTake['branch_name']   = $branch->name;

        //Người nhập hàng
        $userId = $request->input('user');

        $user = (!empty($userId)) ? \SkillDo\Model\User::find($userId) : Auth::user();

        if(empty($user))
        {
            response()->error('Không tìm thấy Nhân viên nhập hàng');
        }

        $stockTake['user_id']     = $user->id;
        $stockTake['user_name']   = $user->firstname.' '.$user->lastname;


        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\StockTake::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\StockTake::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu kiểm kho này đã được sử dụng');
            }

            $stockTake['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        //Sản phẩm
        $productStockTakes = $request->input('products');

        $productsId = array_map(function ($item) { return $item['id']; }, $productStockTakes);

        $productsId = array_unique($productsId);

        $inventories = \Stock\Model\Inventory::select(['id', 'product_id', 'parent_id', 'branch_id', 'stock', 'status', 'price_cost'])->whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->get();

        if($inventories->count() !== count($productsId))
        {
            response()->error('Số lượng sản phẩm cập nhật và số lượng sản phẩm trong kho hàng không khớp');
        }

        $inventories = $inventories->keyBy('product_id');

        //Danh sách sản phẩm phiếu kiểm kho hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $stockTakeDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\StockTakeDetail::where('stock_take_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productStockTakes as $productStockTake)
        {
            $productId = $productStockTake['id'];

            if(!empty($productsId[$productId]))
            {
                response()->error('Sản phẩm '.$productStockTake['title'].$productStockTake['attribute_label'].' đã tồn tại trong danh sách sản phẩm');
            }

            $productsId[$productId] = $productId;

            if (!$inventories->has($productId))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm '.$productStockTake['title'].$productStockTake['attribute_label']);
            }

            if($productStockTake['quantity'] < 0)
            {
                response()->error('Số lượng thực tế của sản phẩm '.$productStockTake['title'].$productStockTake['attribute_label'].' không được nhỏ hơn 0');
            }

            $inventory = $inventories[$productId];

            $productStockTake['price'] = $inventory->price_cost;

            $productStockTake['stock'] = $inventory->stock;

            $adjustment_quantity = $productStockTake['quantity'] - $productStockTake['stock'];

            $adjustment_price    = ($productStockTake['quantity'] - $productStockTake['stock'])*$productStockTake['price'];

            // Tổng số lượng hàng thực tế
            $stockTake['total_actual_quantity'] += $productStockTake['quantity'];

            // Tổng giá trị hàng thực tế
            $stockTake['total_actual_price'] += $productStockTake['quantity']*$productStockTake['price'];

            // Tổng hàng tăng
            if($adjustment_quantity > 0)
            {
                $stockTake['total_increase_quantity'] += $adjustment_quantity;
                $stockTake['total_increase_price'] += $adjustment_price;
            }

            // Tổng hàng giảm
            if($adjustment_quantity < 0)
            {
                $stockTake['total_reduced_quantity'] += $adjustment_quantity;
                $stockTake['total_reduced_price'] += $adjustment_price;
            }

            if (isset($productsDetail[$productId]))
            {
                $productDetail = $productsDetail[$productId];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\StockTake::success->value)
                {
                    unset($productsDetail[$productId]);
                    continue;
                }

                $stockTakeDetails[] = [
                    'stock_take_detail_id'  => $productDetail->stock_take_detail_id,
                    'stock_take_id'         => $object->id,
                    'product_id'         => $productStockTake['id'],
                    'product_code'       => $productStockTake['code'] ?? '',
                    'product_name'       => $productStockTake['title'],
                    'product_attribute'  => $productStockTake['attribute_label'] ?? '',
                    'stock'              => $productStockTake['stock'],
                    'price'              => $productStockTake['price'],
                    'actual_quantity'    => $productStockTake['quantity'],
                    'adjustment_quantity'=> $adjustment_quantity,
                    'adjustment_price'   => $adjustment_price,
                ];

                unset($productsDetail[$productId]);
                continue;
            }

            // Thêm sản phẩm mới
            $stockTakeDetails[] = [
                'stock_take_id'      => $object->id ?? 0,
                'product_id'         => $productStockTake['id'],
                'product_code'       => $productStockTake['code'] ?? '',
                'product_name'       => $productStockTake['title'],
                'product_attribute'  => $productStockTake['attribute_label'] ?? '',
                'stock'              => $productStockTake['stock'],
                'price'              => $productStockTake['price'],
                'actual_quantity'    => $productStockTake['quantity'],
                'adjustment_quantity'=> $adjustment_quantity,
                'adjustment_price'   => $adjustment_price,
            ];
        }

        $stockTake['total_adjustment_quantity'] += $stockTake['total_increase_quantity'] + $stockTake['total_reduced_quantity'];

        $stockTake['total_adjustment_price'] += $stockTake['total_increase_price'] + $stockTake['total_reduced_price'];

        return [
            $stockTake,
            $branch,
            $inventories,
            $stockTakeDetails,
            $productsDetail
        ];
    }

    static function cancel(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'data' => Rule::make('phiếu kiểm kho')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('data');

        $object = \Stock\Model\StockTake::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\StockTake::cancel->value)
        {
            response()->error('phiếu kiểm kho này đã được hủy');
        }
        if($object->status === \Stock\Status\StockTake::success->value)
        {
            response()->error('phiếu kiểm kho này đã hoàn thành không thể hủy');
        }

        \Stock\Model\StockTakeDetail::where('stock_take_id', $object->id)
            ->where('status', \Stock\Status\StockTake::draft->value)
            ->update([
                'status' => \Stock\Status\StockTake::cancel->value,
            ]);

        \Stock\Model\StockTake::whereKey($object->id)->update([
            'status' => \Stock\Status\StockTake::cancel->value,
        ]);

        response()->success('Hủy phiếu kiểm kho hàng thành công', [
            'status' => Admin::badge(\Stock\Status\StockTake::cancel->badge(), 'Đã hủy')
        ]);
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu kiểm kho')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\StockTake::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho đã đóng cửa hoặc không còn trên hệ thống');
        }

        $object->balance_date = !empty($object->balance_date) ? $object->balance_date : strtotime($object->created);
        $object->balance_date = date('d/m/Y H:s', $object->balance_date);

        $object->total_actual_price = Prd::price($object->total_actual_price);
        $object->total_increase_price = Prd::price($object->total_increase_price);
        $object->total_reduced_price = Prd::price($object->total_reduced_price);
        $object->total_adjustment_price = Prd::price($object->total_adjustment_price);

        $products = \Stock\Model\StockTakeDetail::where('stock_take_id', $object->id)->get();

        response()->success('Dữ liệu print', [
            'purchase' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
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

        $query = Qr::orderBy('created');

        if($type === 'page')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu kiểm kho hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'checked')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu kiểm kho hàng nào để xuất'));
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

        $objects = \Stock\Model\StockTake::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu kiểm kho hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            $object->balance_date = !empty($object->balance_date) ? $object->balance_date : strtotime($object->created);
            $object->balance_date = date('d/m/Y H:s', $object->balance_date);
        }

        $export = new \Stock\Export();

        $export->header('code', 'Mã kiểm kho', function($item) {
            return $item->code ?? '';
        });

        $export->header('created', 'Ngày tạo', function($item) {
            return $item->created;
        });

        $export->header('branch_name', 'Chi nhánh', function($item) {
            return $item->branch_name;
        });

        $export->header('total_actual_quantity', 'SL Thực tế', function($item) {
            return number_format($item->total_actual_quantity);
        });

        $export->header('total_actual_price', 'Tổng Thực tế', function($item) {
            return number_format($item->total_actual_price);
        });

        $export->header('total_adjustment_quantity', 'Tổng chênh lệch', function($item) {
            return number_format($item->total_adjustment_quantity);
        });

        $export->setTitle('DSPhieuKiemKho_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/stock-take/', 'DanhSachPhieukiemKho_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu kiểm kho')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\StockTake::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho đã đóng cửa hoặc không còn trên hệ thống');
        }

        $products = \Stock\Model\StockTakeDetail::where('stock_take_id', $object->id)->get();

        $export = new \Stock\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('stock', 'Tồn kho', function($item) {
            return number_format($item->stock ?? 0);
        });

        $export->header('actual_quantity', 'Thực tế', function($item) {
            return number_format($item->actual_quantity ?? 0);
        });

        $export->header('adjustment_quantity', 'SL Lệch', function($item) {
            return number_format($item->adjustment_quantity ?? 0);
        });

        $export->header('adjustment_price', 'Giá trị lệch', function($item) {
            return number_format($item->adjustment_price);
        });

        $export->setTitle('DSChiTietKiemKho_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/stock-take/', 'DanhSachChiTietKiemKho_'.$object->code.'.xlsx');

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

            $myPath = STOCK_NAME.'/assets/imports/stock-take';

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

                if(count($schedule) < 2) {
                    continue;
                }

                $rowData = [
                    'code'      => trim($schedule[1]),
                    'quantity'  => trim($schedule[2])
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
                DB::raw("MAX(cle_inventories.price_cost) AS price_cost"),
                DB::raw("SUM(cle_inventories.stock) AS stock")
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
                                'id'        => $item->id,
                                'code'     => $item->code,
                                'title'     => $item->title,
                                'fullname'  => $item->fullname,
                                'attribute_label' => $item->attribute_label ?? '',
                                'image'     => $item->image,
                                'price_cost'    => $item->price_cost,
                                'stock'    => $item->stock,
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

Ajax::admin('StockTakeAdminAjax::loadProductsDetail');
Ajax::admin('StockTakeAdminAjax::loadProductsEdit');
Ajax::admin('StockTakeAdminAjax::addDraft');
Ajax::admin('StockTakeAdminAjax::saveDraft');
Ajax::admin('StockTakeAdminAjax::add');
Ajax::admin('StockTakeAdminAjax::save');
Ajax::admin('StockTakeAdminAjax::cancel');
Ajax::admin('StockTakeAdminAjax::print');
Ajax::admin('StockTakeAdminAjax::export');
Ajax::admin('StockTakeAdminAjax::exportDetail');
Ajax::admin('StockTakeAdminAjax::import');