<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

class StockDamageItemsAdminAjax
{
    static function loadProductsDetail(\SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $id  = $request->input('id');

        $query = Qr::where('inventories_damage_item_details.damage_item_id', $id);

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
        $total = \Stock\Model\DamageItemDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Stock\Model\DamageItemDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' '.$object->product_attribute;
        }

        # [created table]
        $table = new \Stock\Table\DamageItems\ProductDetail([
            'items' => $objects,
        ]);

        $table->setTrash(true);

        $table->getColumns();

        $html = $table->renderBody();

        $result['data'] = [
            'html'          => base64_encode($html),
            'bulkAction'    => base64_encode(''),
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
            'po.quantity',
            'po.price',
        ];

        $query = Qr::select($selected);

        $query->leftJoin('inventories_damage_item_details as po', function ($join) use ($id) {
            $join->on('po.product_id', '=', 'products.id');
        });

        $query->where('po.damage_item_id', $id);

        $query
            ->limit(30)
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
            $damageItems,
            $branch,
            $productDamageItems,
            $damageItemsDetails,
            $productsDetail
        ] = static::dataDraft($request);

        try
        {
            DB::beginTransaction();

            $damageItemsId = \Stock\Model\DamageItem::create($damageItems);

            if(empty($damageItemsId) || is_skd_error($damageItemsId))
            {
                response()->error('Tạo phiếu xuất hủy hàng thất bại');
            }

            foreach ($damageItemsDetails as $key => $detail)
            {
                $damageItemsDetails[$key]['damage_item_id'] = $damageItemsId;
            }

            DB::table('inventories_damage_item_details')->insert($damageItemsDetails);

            DB::commit();

            response()->success('Lưu tạm phiếu xuất hủy hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu xuất hủy hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function saveDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'damage_item_id' => Rule::make('phiếu xuất hủy')->notEmpty()->integer(),
        ]);

        $id = $request->input('damage_item_id');

        $object = \Stock\Model\DamageItem::find($id);

        if(empty($object))
        {
            response()->error('phiếu xuất hủy đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\DamageItem::success->value)
        {
            response()->error('phiếu xuất hủy này đã hoàn thành');
        }

        if($object->status === \Stock\Status\DamageItem::cancel->value)
        {
            response()->error('phiếu xuất hủy này đã bị hủy');
        }

        [
            $damageItems,
            $branch,
            $productDamageItems,
            $damageItemsDetails,
            $productsDetail
        ] = static::dataDraft($request, $object);

        \Stock\Model\DamageItem::whereKey($id)->update($damageItems);

        //Lấy danh sách chi tiết phiếu nhập sẽ cập nhật
        $damageItemsDetailsUp = [];

        foreach ($damageItemsDetails as $key => $detail)
        {
            if(empty($detail['damage_item_id']))
            {
                $detail['damage_item_id'] = $object->id;
                $damageItemsDetails[$key] = $detail;
            }

            if(!empty($detail['damage_item_detail_id']))
            {
                $damageItemsDetailsUp[] = $detail;
                unset($damageItemsDetails[$key]);
            }
        }

        try
        {
            DB::beginTransaction();

            //Thêm mới
            if(!empty($damageItemsDetails))
            {
                DB::table('inventories_damage_item_details')->insert($damageItemsDetails);
            }

            //Cập nhật
            if(!empty($damageItemsDetailsUp))
            {
                \Stock\Model\DamageItemDetail::updateBatch($damageItemsDetailsUp, 'damage_item_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\DamageItemDetail::whereKey($productsDetail->pluck('damage_item_detail_id')->toArray())->delete();
            }

            DB::commit();

            response()->success('Lưu tạm phiếu xuất hủy hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu xuất hủy hàng (nháp): '. $e->getMessage());

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

        $damageItems = [
            'status' => \Stock\Status\DamageItem::draft->value,
            'damage_date' => $time,
        ];

        //Chi nhánh
        $branch = Branch::find($request->input('branch_id'));

        if(!empty($branch))
        {
            $damageItems['branch_id']     = $branch->id;
            $damageItems['branch_name']   = $branch->name;
        }

        //Người hủy hàng
        $damageId = (int)$request->input('damage');

        $damage = (!empty($damageId)) ? \SkillDo\Model\User::find($damageId) : Auth::user();

        if(!empty($damage->firstname) || !empty($damage->lastname))
        {
            $damage_name = $damage->firstname.' '.$damage->lastname;
        }

        $damageItems['damage_id']     = $damage->id ?? 0;
        $damageItems['damage_name']   = $damage_name ?? '';

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\DamageItem::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\DamageItem::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu xuất hủy hàng này đã được sử dụng');
            }

            $damageItems['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productDamageItems = $request->input('products');

        $damageItems['sub_total'] = array_reduce($productDamageItems, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);

        $damageItems['total_quantity'] = array_reduce($productDamageItems, function ($sum, $item) {
            return $sum + ($item['quantity']);
        }, 0);

        $productsId = [];

        //Danh sách sản phẩm phiếu trả hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $damageItemsDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\DamageItemDetail::where('damage_item_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productDamageItems as $product)
        {
            $productsId[$product['id']] = $product['id'];

            if (isset($productsDetail[$product['id']]))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\DamageItem::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $damageItemsDetails[] = [
                    'damage_item_detail_id'     => $productDetail->damage_item_detail_id,
                    'damage_item_id'            => $object->id,
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
            $damageItemsDetails[] = [
                'damage_item_id'     => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'quantity'           => $product['quantity'],
                'price'              => $product['price'],
            ];
        }

        return [
            $damageItems,
            $branch,
            $productDamageItems,
            $damageItemsDetails,
            $productsDetail
        ];
    }

    static function add(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $damageItems,
            $branch,
            $productDamageItems,
            $inventories,
            $damageItemsDetails,
            $productsDetail
        ] = static::data($request);

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        //Cập nhật trạng thái sản phẩm chính
        $productsUp = [];

        foreach ($damageItemsDetails as $detail)
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
                        : 'Mã hàng ' . $detail['product_code']) . ': không đủ số lượng tồn kho để xuất hủy hàng'
                );
            }

            $newStock = $inventory->stock - $detail['quantity'];

            $inventoriesUpdate[] = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value,
            ];

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                'message'       => [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $newStock,
                    'damageCode'  => '',
                ],
                'action'        => 'tru',
                'type'          => 'stock',
            ];

            if ($newStock == 0)
            {
                if(!empty($inventory->parent_id))
                {
                    $productsUp[] = $inventory->parent_id;
                }

                $productsUp[] = $inventory->product_id;
            }
        }

        try {

            DB::beginTransaction();

            $damageItemsId = \Stock\Model\DamageItem::create($damageItems);

            if(empty($damageItemsId) || is_skd_error($damageItemsId))
            {
                response()->error('Tạo phiếu xuất hủy hàng thất bại');
            }

            foreach ($inventoriesHistories as $key => $history)
            {
                $history['message']['damageCode'] = (!empty($damageItems['code'])) ? $damageItems['code'] : \Stock\Helper::code('XH', $damageItemsId);;
                $history['message'] = InventoryHistory::message('damage_items_update', $history['message']);
                $inventoriesHistories[$key] = $history;
            }

            foreach ($damageItemsDetails as &$detail)
            {
                $detail['damage_item_id'] = $damageItemsId;
                unset($detail['damage_item_detail_id']);
            }

            DB::table('inventories_damage_item_details')->insert($damageItemsDetails);

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            //Cập nhật trạng thái
            if(have_posts($productsUp))
            {
                \Ecommerce\Model\Product::widthVariation()
                    ->whereIn('id', $productsUp)
                    ->update(['stock_status' => \Stock\Status\Inventory::out->value]);
            }

            DB::commit();

            response()->success('Lưu phiếu xuất hủy hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu xuất hủy hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function save(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'damage_item_id' => Rule::make('phiếu xuất hủy')->notEmpty()->integer(),
        ]);

        $id = $request->input('damage_item_id');

        $object = \Stock\Model\DamageItem::find($id);

        if(empty($object))
        {
            response()->error('phiếu xuất hủy đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\DamageItem::success->value)
        {
            response()->error('phiếu xuất hủy này đã hoàn thành');
        }

        if($object->status === \Stock\Status\DamageItem::cancel->value)
        {
            response()->error('phiếu xuất hủy này đã bị hủy');
        }

        [
            $damageItems,
            $branch,
            $productDamageItems,
            $inventories,
            $damageItemsDetails,
            $productsDetail
        ] = static::data($request, $object);

        if(empty($damageItemsDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }

        $damageItemsDetailsUp = [];

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        //Cập nhật trạng thái sản phẩm chính
        $productsUp = [];

        foreach ($damageItemsDetails as $key => $detail)
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

            $inventoriesUpdate[] = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Stock\Status\Inventory::out->value : \Stock\Status\Inventory::in->value,
            ];

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                'message'       => InventoryHistory::message('damage_items_update', [
                    'stockBefore'   => $inventory->stock,
                    'stockAfter'    => $newStock,
                    'damageCode'  => $object->code,
                ]),
                'action'        => 'tru',
                'type'          => 'stock',
            ];

            if ($newStock == 0)
            {
                if(!empty($inventory->parent_id))
                {
                    $productsUp[] = $inventory->parent_id;
                }

                $productsUp[] = $inventory->product_id;
            }

            if(empty($detail['damage_item_id']))
            {
                $detail['damage_item_id'] = $object->id;
                $damageItemsDetails[$key] = $detail;
            }

            if(!empty($detail['damage_item_detail_id']))
            {
                $damageItemsDetailsUp[] = $detail;
                unset($damageItemsDetails[$key]);
            }
            else {
                unset($detail['damage_item_detail_id']);
                $damageItemsDetails[$key] = $detail;
            }
        }

        try
        {
            DB::beginTransaction();

            \Stock\Model\DamageItem::whereKey($id)->update($damageItems);

            //Thêm mới
            if(!empty($damageItemsDetails))
            {
                DB::table('inventories_damage_item_details')->insert($damageItemsDetails);
            }

            //Cập nhật
            if(!empty($damageItemsDetailsUp))
            {
                \Stock\Model\DamageItemDetail::updateBatch($damageItemsDetailsUp, 'damage_item_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Stock\Model\DamageItemDetail::whereKey($productsDetail->pluck('damage_item_detail_id')->toArray())->delete();
            }

            //Cập nhật kho hàng
            \Stock\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            DB::table('inventories_history')->insert($inventoriesHistories);

            //Cập nhật trạng thái
            if(have_posts($productsUp))
            {
                \Ecommerce\Model\Product::widthVariation()
                    ->whereIn('id', $productsUp)
                    ->update(['stock_status' => \Stock\Status\Inventory::out->value]);
            }

            DB::commit();

            response()->success('Lưu phiếu xuất hủy hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu xuất hủy hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
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

        $damageItems = [
            'status' => \Stock\Status\DamageItem::success->value,
            'damage_date' => $time,
        ];

        //Chi nhánh
        $branch = Branch::find($request->input('branch_id'));

        if(empty($branch))
        {
            response()->error('Chi nhánh đã đóng cửa hoặc không còn trên hệ thống');
        }

        $damageItems['branch_id']     = $branch->id;
        $damageItems['branch_name']   = $branch->name;

        //Người hủy hàng
        $damageId = (int)$request->input('damage');

        $damage = (!empty($damageId)) ? \SkillDo\Model\User::find($damageId) : Auth::user();

        if(empty($damage))
        {
            response()->error('Không tìm thấy Nhân viên xuất hủy hàng');
        }

        $damageItems['damage_id']     = $damage->id ?? 0;
        $damageItems['damage_name']   = $damage->firstname.' '.$damage->lastname;

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Stock\Model\DamageItem::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Stock\Model\DamageItem::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu xuất hủy hàng này đã được sử dụng');
            }

            $damageItems['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productDamageItems = $request->input('products');

        $productsId = array_map(function ($item) {
            return $item['id'];
        }, $productDamageItems);

        $productsId = array_unique($productsId);

        $products = \Ecommerce\Model\Product::widthVariation()
            ->whereIn('id', $productsId)
            ->select('id', 'title', 'price_cost')
            ->get();

        $purchaseMap = (\Illuminate\Support\Collection::make($productDamageItems))
            ->keyBy('product_id');

        foreach ($products as $product)
        {
            if (!$purchaseMap->has($product->id))
            {
                continue;
            }

            $purchaseMap->transform(function ($item, $key) use ($product) {
                if ($key === $product->id) {
                    $item['price'] = $product->price_cost;
                }
                return $item;
            });
        }

        $productDamageItems = $purchaseMap->values()->toArray();

        $damageItems['sub_total'] = array_reduce($productDamageItems, function ($sum, $item) {
            return $sum + ($item['quantity'] * $item['price']);
        }, 0);

        $damageItems['total_quantity'] = array_reduce($productDamageItems, function ($sum, $item) {
            return $sum + ($item['quantity']);
        }, 0);

        //Danh sách sản phẩm phiếu trả hàng (nếu đang cập nhật)
        $productsDetail = [];

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $damageItemsDetails = [];

        if($isEdit)
        {
            $productsDetail = \Stock\Model\DamageItemDetail::where('damage_item_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productDamageItems as $product)
        {
            if (isset($productsDetail[$product['id']]))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Stock\Status\DamageItem::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $damageItemsDetails[] = [
                    'damage_item_detail_id'     => $productDetail->damage_item_detail_id,
                    'damage_item_id'            => $object->id,
                    'product_id'                => $product['id'],
                    'product_code'              => $product['code'] ?? '',
                    'product_name'              => $product['title'],
                    'product_attribute'         => $product['attribute_label'] ?? '',
                    'quantity'                  => $product['quantity'],
                    'price'                     => $product['price'],
                    'status'                    => \Stock\Status\DamageItem::success->value,
                ];

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $damageItemsDetails[] = [
                'damage_item_id'     => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'quantity'           => $product['quantity'],
                'price'              => $product['price'],
                'status'             => \Stock\Status\PurchaseOrder::success->value,
            ];
        }

        $inventories = \Stock\Model\Inventory::select(['id', 'product_id', 'parent_id', 'branch_id', 'stock', 'status'])
            ->whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->get();

        if($inventories->count() !== count($productDamageItems))
        {
            response()->error('Số lượng sản phẩm cập nhật và số lượng sản phẩm trong kho hàng không khớp');
        }

        $inventories = $inventories->keyBy('product_id');

        return [
            $damageItems,
            $branch,
            $productDamageItems,
            $inventories,
            $damageItemsDetails,
            $productsDetail
        ];
    }

    static function validate(\SkillDo\Http\Request $request, $rules = []): void
    {
        $validate = $request->validate([
            'branch_id'         => Rule::make('Chi nhánh')->notEmpty()->integer(),
            'products'          => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'     => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.quantity'  => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'   => Rule::make('Giá vốn sản phẩm')->notEmpty()->integer()->min(0),
            'damage'          => Rule::make('Người nhập hàng')->integer()->min(1),
            ...$rules
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }
    }

    static function cancel(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'data' => Rule::make('phiếu xuất hủy')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('data');

        $object = \Stock\Model\DamageItem::find($id);

        if(empty($object))
        {
            response()->error('phiếu xuất hủy đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($object->status === \Stock\Status\DamageItem::cancel->value)
        {
            response()->error('phiếu xuất hủy này đã được hủy');
        }
        if($object->status === \Stock\Status\DamageItem::success->value)
        {
            response()->error('phiếu xuất hủy này đã hoàn thành không thể hủy');
        }

        \Stock\Model\DamageItemDetail::where('damage_item_id', $object->id)
            ->where('status', \Stock\Status\DamageItem::draft->value)
            ->update([
                'status' => \Stock\Status\DamageItem::cancel->value,
            ]);

        \Stock\Model\DamageItem::whereKey($object->id)->update([
            'status' => \Stock\Status\DamageItem::cancel->value,
        ]);

        response()->success('Hủy phiếu xuất hủy hàng thành công', [
            'status' => Admin::badge(\Stock\Status\DamageItem::cancel->badge(), 'Đã hủy')
        ]);
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu xuất hủy')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\DamageItem::find($id);

        if(empty($object))
        {
            response()->error('phiếu xuất hủy đã đóng cửa hoặc không còn trên hệ thống');
        }

        $object->damage_date = !empty($object->damage_date) ? $object->damage_date : strtotime($object->created);
        $object->damage_date = date('d/m/Y H:s', $object->damage_date);

        $userCreated = User::find($object->user_created);
        $object->user_created_name = (have_posts($userCreated)) ? $userCreated->firstname.' '.$userCreated->lastname : '';

        $products = \Stock\Model\DamageItemDetail::where('damage_item_id', $object->id)->get();

        $object->count = $products->count();

        response()->success('Dữ liệu print', [
            'damageItems' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
                $item->total = $item->quantity * $item->cost;
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

        $query = Qr::orderBy('damage_date');

        if($type === 'page')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu xuất hủy hàng nào để xuất'));
            }

            $query->whereIn('id', $ids);
        }

        if($type === 'checked')
        {
            $ids = $request->input('items');

            if(!have_posts($ids))
            {
                response()->error(trans('Không có phiếu xuất hủy hàng nào để xuất'));
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

        $objects = \Stock\Model\DamageItem::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu trả hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            $object->damage_date = !empty($object->damage_date) ? $object->damage_date : strtotime($object->created);
            $object->damage_date = date('d/m/Y H:s', $object->damage_date);
        }

        $export = new \Stock\Export();

        $export->header('code', 'Mã xuất hủy hàng', function($item) {
            return $item->code ?? '';
        });

        $export->header('damage_date', 'Ngày hủy', function($item) {
            return $item->damage_date;
        });

        $export->header('branch_name', 'Chi nhánh', function($item) {
            return $item->branch_name;
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->total_quantity);
        });

        $export->header('sub_total', 'Giá trị', function($item) {
            return number_format($item->sub_total);
        });

        $export->setTitle('DSPhieuXuatHuyHang_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/damage-items/', 'DanhSachPhieuXuatHuyHang_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu xuất hủy')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Stock\Model\DamageItem::find($id);

        if(empty($object))
        {
            response()->error('phiếu xuất hủy đã đóng cửa hoặc không còn trên hệ thống');
        }

        $products = \Stock\Model\DamageItemDetail::where('damage_item_id', $object->id)->get();

        $export = new \Stock\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('price', 'Giá vốn', function($item) {
            return number_format($item->price ?? 0);
        });

        $export->header('quantity', 'Số lượng', function($item) {
            return number_format($item->quantity ?? 0);
        });

        $export->header('total', 'Thành tiền', function($item) {
            return number_format($item->quantity * $item->price);
        });

        $export->setTitle('DSChiTietXuatHuyHang_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/damage-items/', 'DSChiTietXuatHuyHang_'.$object->code.'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function import(\SkillDo\Http\Request $request): void
    {
        if($request->hasFile('file')) {

            $validate = $request->validate([
                'file' => Rule::make('File sản phẩm')->notEmpty()->file(['xlsx', 'xls'], [
                    'min' => 1,
                    'max' => '2mb'
                ]),
            ]);

            if ($validate->fails()) {
                response()->error($validate->errors());
            }

            $myPath = STOCK_NAME.'/assets/imports/damage-items';

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

            $rowDatasId = [];

            $rowDatasCode = [];

            foreach ($schedules as $numberRow => $schedule) {

                if($numberRow == 1) continue;

                if(count($schedule) < 6) {
                    continue;
                }

                $rowData = [
                    'id'        => (int)trim($schedule[1]),
                    'code'      => trim($schedule[2]),
                    'stock'     => trim($schedule[5]),
                    'cost'      => trim($schedule[6])
                ];

                if(empty($rowData['id']) && empty($rowData['code']))
                {
                    continue;
                }

                if(!empty($rowData['id']))
                {
                    $rowDatasId[] = $rowData;
                    continue;
                }

                $rowDatasCode[] = $rowData;
            }

            $selected = [
                'products.id',
                'products.code',
                'products.title',
                'products.image',
                'products.price',
                'products.price_sale',
                'products.price_cost',
                'products.stock_status',
                'products.hasVariation',
                'products.parent_id',
            ];

            $productsId = Product::whereIn('id', array_map(function ($item) {
                return $item['id'];
            }, $rowDatasId))
                ->where('type', '<>', 'null')
                ->where('public', '<>', null)
                ->select($selected)
                ->get();

            $productsCode = Product::whereIn('code', array_map(function ($item) {
                return $item['code'];
            }, $rowDatasCode))
                ->where('type', '<>', 'null')
                ->where('public', '<>', null)
                ->select($selected)
                ->get();

            $products = $productsId->merge($productsCode);

            $rowDatas = array_merge($rowDatasId, $rowDatasCode);

            $response = [];

            if(have_posts($products))
            {
                foreach ($products as $key => $item)
                {
                    $item = $item->toObject();

                    $item->fullname = $item->title;

                    if(!empty($item->attribute_label))
                    {
                        $item->fullname .= ' '.$item->attribute_label;
                    }

                    $item->image = Image::medium($item->image)->html();

                    $products[$key] = $item;

                    foreach ($rowDatas as $row)
                    {
                        if($row['id'] == $item->id || $row['code'] == $item->code)
                        {
                            $response[] = [
                                'id'    => $item->id,
                                'title' => $item->title,
                                'fullname' => $item->fullname,
                                'attribute_label' => $item->attribute_label ?? '',
                                'image' => $item->image,
                                'parent_id' => $item->parent_id,
                                'hasVariation' => $item->hasVariation,
                                'cost' => (empty($row['cost'])) ? $item->price_cost : $row['cost'],
                                'stock' => $row['stock'],
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

Ajax::admin('StockDamageItemsAdminAjax::loadProductsDetail');
Ajax::admin('StockDamageItemsAdminAjax::loadProductsEdit');
Ajax::admin('StockDamageItemsAdminAjax::addDraft');
Ajax::admin('StockDamageItemsAdminAjax::saveDraft');
Ajax::admin('StockDamageItemsAdminAjax::add');
Ajax::admin('StockDamageItemsAdminAjax::save');
Ajax::admin('StockDamageItemsAdminAjax::cancel');
Ajax::admin('StockDamageItemsAdminAjax::print');
Ajax::admin('StockDamageItemsAdminAjax::export');
Ajax::admin('StockDamageItemsAdminAjax::exportDetail');
Ajax::admin('StockDamageItemsAdminAjax::import');