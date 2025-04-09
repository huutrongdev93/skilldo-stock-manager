<?php
use SkillDo\DB;
use SkillDo\Validate\Rule;

class TransferAdminAjax
{
    static function loadProductsDetail(\SkillDo\Http\Request $request): void
    {
        $page   = $request->input('page');

        $page   = (empty($page) || !is_numeric($page)) ? 1 : (int)$page;

        $limit  = $request->input('limit');

        $limit  = (empty($limit)  || !is_numeric($page)) ? 10 : (int)$limit;

        $id  = $request->input('id');

        $query = Qr::where('skdepot_transfers_details.transfer_id', $id);

        $selected = [
            'product_id',
            'product_name',
            'product_code',
            'product_attribute',
            'price',
            'send_quantity',
            'send_price',
            'receive_quantity',
            'receive_price',
        ];

        $query->select($selected);

        # [Total decoders]
        $total = \Skdepot\Model\TransferDetail::count(clone $query);

        # [List data]
        $query
            ->limit($limit)
            ->offset(($page - 1)*$limit);

        $objects = \Skdepot\Model\TransferDetail::gets($query);

        foreach ($objects as $object)
        {
            $object->product_name .= ' '.$object->product_attribute;
        }

        # [created table]
        $table = new \Skdepot\Table\Transfer\ProductDetail([
            'items' => $objects,
        ]);

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
            'po.price',
            'po.send_quantity',
        ];

        $query = Qr::select($selected);

        $query->leftJoin('skdepot_transfers_details as po', function ($join) use ($id) {
            $join->on('po.product_id', '=', 'products.id');
        });

        $query->where('po.transfer_id', $id);

        $query
            ->limit(500)
            ->orderBy('products.order')
            ->orderBy('products.created', 'desc');

        $products = \Ecommerce\Model\Product::widthVariation($query)->get();

        $transfer = \Skdepot\Model\Transfer::find($id);

        if(have_posts($products) && have_posts($transfer))
        {
            $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $products
                ->pluck('id')
                ->toArray())
                ->where('branch_id', $transfer->to_branch_id)
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
            $transfer,
            $transferDetails,
            $productsDetail
        ] = static::dataDraft($request);

        try
        {
            DB::beginTransaction();

            $transferId = \Skdepot\Model\Transfer::create($transfer);

            if(empty($transferId) || is_skd_error($transferId))
            {
                response()->error('Tạo phiếu chuyển hàng thất bại');
            }

            foreach ($transferDetails as &$detail)
            {
                $detail['transfer_id'] = $transferId;
            }

            \Skdepot\Model\TransferDetail::inserts($transferDetails);

            DB::commit();

            response()->success('Lưu tạm phiếu chuyển hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu chuyển hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function saveDraft(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu kiểm chuyển hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Skdepot\Model\Transfer::find($id);

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        if($object->status === \Skdepot\Status\Transfer::success->value || $object->status === \Skdepot\Status\Transfer::cancel->value)
        {
            response()->error('Trạng thái phiếu chuyển hàng không cho phép chỉnh sữa');
        }

        [
            $transfer,
            $transferDetails,
            $productsDetail
        ] = static::dataDraft($request, $object);

        \Skdepot\Model\Transfer::whereKey($id)->update($transfer);

        //Lấy danh sách chi tiết phiếu chuyển hàng sẽ cập nhật
        $transferDetailsUp = [];

        foreach ($transferDetails as $key => $detail)
        {
            if(empty($detail['transfer_id']))
            {
                $detail['transfer_id'] = $object->id;
                $transferDetails[$key] = $detail;
            }

            if(!empty($detail['stock_take_detail_id']))
            {
                $transferDetailsUp[] = $detail;
                unset($transferDetails[$key]);
            }
        }

        try
        {
            DB::beginTransaction();

            //Thêm mới
            if(!empty($transferDetails))
            {
                \Skdepot\Model\TransferDetail::inserts($transferDetails);
            }

            //Cập nhật
            if(!empty($transferDetailsUp))
            {
                \Skdepot\Model\TransferDetail::updateBatch($transferDetailsUp, 'transfer_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Skdepot\Model\TransferDetail::whereKey($productsDetail->pluck('transfer_detail_id')->toArray())->delete();
            }

            DB::commit();

            response()->success('Lưu tạm phiếu chuyển hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu chuyển hàng (nháp): '. $e->getMessage());

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
                response()->error('Thời gian chuyển hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $transfer = [
            'status' => \Skdepot\Status\Transfer::draft->value,
            'send_date' => $time,
            'total_send_quantity' => 0,
            'total_send_price' => 0,
        ];

        //Từ Chi nhánh
        $branch = \Skdepot\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh chuyển hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        $transfer['from_branch_id']     = $branch->id;
        $transfer['from_branch_name']   = $branch->name;

        //Đến Chi nhánh
        $branchId = $request->input('to_branch_id');

        $branch = Branch::find($branchId);

        if(empty($branch))
        {
            response()->error('Chi nhánh nhận hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        $transfer['to_branch_id']     = $branch->id;
        $transfer['to_branch_name']   = $branch->name;

        //Người nhập hàng
        $user = Auth::user();
        $transfer['from_user_id']     = $user->id ?? 0;
        $transfer['from_user_name']   = $user->firstname.' '.$user->lastname;

        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Skdepot\Model\Transfer::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Skdepot\Model\Transfer::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu chuyển hàng này đã được sử dụng');
            }

            $transfer['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        $productTransfers = $request->input('products');

        //Danh sách sản phẩm phiếu kiểm kho hàng (nếu đang cập nhật)
        $productsDetail = new \Illuminate\Support\Collection();

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $transferDetails = [];

        if($isEdit)
        {
            $productsDetail = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productTransfers as $product)
        {
            $transfer['total_send_quantity'] += $product['send_quantity'];

            $transfer['total_send_price'] += $product['send_quantity']*$product['price'];

            $transferDetail = [
                'transfer_id'        => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'price'              => $product['price'],
                'send_quantity'      => $product['send_quantity'],
                'send_price'         => $product['send_quantity']*$product['price'],
            ];

            if ($productsDetail->has($product['id']))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Skdepot\Status\Transfer::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $transferDetail['transfer_detail_id'] = $productDetail->transfer_detail_id;

                $transferDetails[] = $transferDetail;

                unset($productsDetail[$product['id']]);
                continue;
            }

            // Thêm sản phẩm mới
            $transferDetails[] = $transferDetail;
        }

        return [
            $transfer,
            $transferDetails,
            $productsDetail
        ];
    }

    static function sendAdd(\SkillDo\Http\Request $request): void
    {
        static::validate($request);

        [
            $transfer,
            $inventories,
            $transferDetails,
            $productsDetail,
        ] = static::data($request);

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($transferDetails as $detail)
        {
            $inventory = $inventories[$detail['product_id']];

            $newStock = $inventory->stock - $detail['send_quantity'];

            $inventoriesUpdate[] = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Skdepot\Status\Inventory::out->value : \Skdepot\Status\Inventory::in->value
            ];

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                //Thông tin
                'cost'          => $detail['price'],
                'price'         => $detail['price']*$detail['send_quantity'],
                'quantity'      => $detail['send_quantity']*1,
                'start_stock'   => $inventory->stock,
                'end_stock'     => $newStock,
            ];
        }

        try {

            DB::beginTransaction();

            //Tạo phiếu chuyển hàng hàng
            $transferId = \Skdepot\Model\Transfer::create($transfer);

            if(empty($transferId) || is_skd_error($transferId))
            {
                response()->error('Tạo phiếu chuyển hàng thất bại');
            }

            if(empty($transfer['code']))
            {
                $transfer['code'] = \Skdepot\Helper::code(\Skdepot\Prefix::transfer->value, $transferId);
            }

            // Cập nhật mã phiếu vào lịch sử kho
            foreach ($inventoriesHistories as $key => $history)
            {
                $history['target_id'] = $transferId;
                $history['target_code'] = $transfer['code'];
                $history['target_name'] = 'Chuyển hàng';
                $history['target_type'] = \Skdepot\Prefix::transfer->value;
                $inventoriesHistories[$key] = $history;
            }

            // Cập nhật transfer_id
            foreach ($transferDetails as &$detail)
            {
                $detail['transfer_id'] = $transferId;
                unset($detail['transfer_detail_id']);
            }

            \Skdepot\Model\TransferDetail::inserts($transferDetails);

            //Cập nhật kho hàng
            \Skdepot\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            \Skdepot\Model\History::inserts($inventoriesHistories);

            DB::commit();

            response()->success('Tạo phiếu chuyển hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            \SkillDo\Log::error('Lỗi tạo phiếu chuyển hàng: '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function sendSave(\SkillDo\Http\Request $request): void
    {
        static::validate($request, [
            'id' => Rule::make('phiếu chuyển hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $object = \Skdepot\Model\Transfer::find($id);

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        if($object->status !== \Skdepot\Status\Transfer::draft->value)
        {
            response()->error('Trạng thái phiếu chuyển hàng này không thể cập nhật');
        }

        [
            $transfer,
            $inventories,
            $transferDetails,
            $productsDetail
        ] = static::data($request, $object);

        if(empty($transferDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }

        //Sản phẩm của phiếu kiểm kho cập nhật
        $transferDetailsUp = [];

        //Cập nhật tồn kho
        $inventoriesUpdate = [];

        //Cập nhật lịch sử
        $inventoriesHistories = [];

        foreach ($transferDetails as $key => $detail)
        {
            $inventory = $inventories[$detail['product_id']];

            $newStock = $inventory->stock - $detail['send_quantity'];

            $inventoriesUpdate[] = [
                'id'     => $inventory->id,
                'stock'  => $newStock,
                'status' => ($newStock == 0) ? \Skdepot\Status\Inventory::out->value : \Skdepot\Status\Inventory::in->value
            ];

            $inventoriesHistories[] = [
                'inventory_id'  => $inventory->id,
                'product_id'    => $inventory->product_id,
                'branch_id'     => $inventory->branch_id,
                //Đối tượng
                'target_id'   => $object->id ?? 0,
                'target_code' => $object->code ?? '',
                'target_type' => \Skdepot\Prefix::transfer->value,
                'target_name' => 'Chuyển hàng',
                //Thông tin
                'cost'          => $detail['price'],
                'price'         => $detail['price']*$detail['send_quantity'],
                'quantity'      => $detail['send_quantity']*-1,
                'start_stock'   => $inventory->stock,
                'end_stock'     => $newStock,
            ];

            if(empty($detail['transfer_id']))
            {
                $detail['transfer_id'] = $object->id;
                $transferDetails[$key] = $detail;
            }

            if(!empty($detail['transfer_detail_id']))
            {
                $transferDetailsUp[] = $detail;
                unset($transferDetails[$key]);
            }
            else
            {
                unset($detail['transfer_detail_id']);
                $transferDetails[$key] = $detail;
            }
        }

        try {

            DB::beginTransaction();

            //Cập nhật phiếu kiểm kho hàng
            \Skdepot\Model\Transfer::whereKey($id)->update($transfer);

            //Thêm mới
            if(!empty($transferDetails))
            {
                \Skdepot\Model\TransferDetail::inserts($transferDetails);
            }

            //Cập nhật
            if(!empty($transferDetailsUp))
            {
                \Skdepot\Model\TransferDetail::updateBatch($transferDetailsUp, 'transfer_detail_id');
            }

            //Xóa
            if(!empty($productsDetail))
            {
                \Skdepot\Model\TransferDetail::whereKey($productsDetail->pluck('transfer_detail_id')->toArray())->delete();
            }

            //Cập nhật kho hàng
            \Skdepot\Model\Inventory::updateBatch($inventoriesUpdate, 'id');

            //Cập nhật lịch sử
            \Skdepot\Model\History::inserts($inventoriesHistories);

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
            'to_branch_id'   => Rule::make('Chi nhánh nhận hàng')->notEmpty()->integer()->min(1),
            'products'              => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'         => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.send_quantity'   => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'      => Rule::make('Giá trị sản phẩm')->notEmpty()->integer()->min(0),
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
                response()->error('Thời gian chuyển hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $transfer = [
            'status'        => \Skdepot\Status\Transfer::process->value,
            'send_date' => $time,
            'total_send_quantity' => 0,
            'total_send_price' => 0,
        ];

        //Chi nhánh chuyển
        $branch = \Skdepot\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh chuyển hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        $transfer['from_branch_id']     = $branch->id;
        $transfer['from_branch_name']   = $branch->name;

        //chi nhánh nhận
        $branchId = $request->input('to_branch_id');

        $branchTo = Branch::find($branchId);

        if(empty($branchTo))
        {
            response()->error('Chi nhánh nhận hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        $transfer['to_branch_id']     = $branchTo->id;
        $transfer['to_branch_name']   = $branchTo->name;

        //Người nhập hàng
        $user = Auth::user();

        if(empty($user))
        {
            response()->error('Không tìm thấy Nhân viên chuyển hàng');
        }

        $transfer['from_user_id']     = $user->id;
        $transfer['from_user_name']   = $user->firstname.' '.$user->lastname;


        //Mã code
        $code = $request->input('code');

        if(!empty($code))
        {
            if($isEdit)
            {
                $count = 0;

                if($object->code != $code)
                {
                    $count = \Skdepot\Model\Transfer::where('code', $code)->count();
                }
                else
                {
                    $code = $object->code;
                }
            }
            else
            {
                $count = \Skdepot\Model\Transfer::where('code', $code)->count();
            }

            if($count > 0)
            {
                response()->error('Mã phiếu chuyển hàng này đã được sử dụng');
            }

            $transfer['code'] = $code;

            if(!empty($object))
            {
                $object->code = $code;
            }
        }

        //Sản phẩm
        $productTransfers = $request->input('products');

        $productsId = array_map(function ($item) { return $item['id']; }, $productTransfers);

        $productsId = array_unique($productsId);

        $inventories = \Skdepot\Model\Inventory::select(['id', 'product_id', 'parent_id', 'branch_id', 'stock', 'status', 'price_cost'])
            ->whereIn('product_id', $productsId)
            ->where('branch_id', $branch->id)
            ->get();

        if($inventories->count() !== count($productsId))
        {
            response()->error('Số lượng sản phẩm cập nhật và số lượng sản phẩm trong kho hàng không khớp');
        }

        $inventories = $inventories->keyBy('product_id');

        //Danh sách sản phẩm phiếu kiểm kho hàng (nếu đang cập nhật)
        $productsDetail = \Illuminate\Support\Collection::make([]);

        //Danh sách sản phẩm sẽ thêm mới hoặc cập nhật
        $transferDetails = [];

        if($isEdit)
        {
            $productsDetail = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
                ->get()
                ->keyBy('product_id');
        }

        foreach($productTransfers as $product)
        {
            $productId = $product['id'];

            if(!empty($productsId[$productId]))
            {
                response()->error('Sản phẩm '.$product['title'].$product['attribute_label'].' đã tồn tại trong danh sách sản phẩm');
            }

            $productsId[$productId] = $productId;

            if (!$inventories->has($productId))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm '.$product['title'].$product['attribute_label']);
            }

            if($product['send_quantity'] < 0)
            {
                response()->error('Số lượng thực tế của sản phẩm '.$product['title'].$product['attribute_label'].' không được nhỏ hơn 0');
            }

            $inventory = $inventories[$productId];

            if($product['send_quantity'] > $inventory->stock)
            {
                response()->error('Số lượng chuyển đi của sản phẩm '.$product['title'].$product['attribute_label'].' lớn hơn số lượng tồn kho ('.$inventory->stock.')');
            }

            $transfer['total_send_quantity'] += $product['send_quantity'];

            $transfer['total_send_price'] += $product['send_quantity']*$product['price'];

            $transferDetail = [
                'transfer_id'        => $object->id ?? 0,
                'product_id'         => $product['id'],
                'product_code'       => $product['code'] ?? '',
                'product_name'       => $product['title'],
                'product_attribute'  => $product['attribute_label'] ?? '',
                'price'              => $product['price'],
                'send_quantity'      => $product['send_quantity'],
                'send_price'         => $product['send_quantity']*$product['price'],
                'status'             => \Skdepot\Status\Transfer::process->value,
            ];

            if ($productsDetail->has($productId))
            {
                $productDetail = $productsDetail[$productId];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Skdepot\Status\Transfer::success->value)
                {
                    unset($productsDetail[$productId]);
                    continue;
                }

                $transferDetail['transfer_detail_id'] = $productDetail->transfer_detail_id;

                $transferDetails[] = $transferDetail;

                unset($productsDetail[$productId]);

                continue;
            }

            // Thêm sản phẩm mới
            $transferDetails[] = $transferDetail;
        }

        return [
            $transfer,
            $inventories,
            $transferDetails,
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

        $object = \Skdepot\Model\Transfer::find($id);

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        if($object->status === \Skdepot\Status\Transfer::cancel->value)
        {
            response()->error('phiếu chuyển hàng này đã được hủy');
        }

        if($object->status === \Skdepot\Status\Transfer::success->value)
        {
            response()->error('phiếu chuyển hàng này đã hoàn thành không thể hủy');
        }

        try {

            DB::beginTransaction();

            if($object->status === \Skdepot\Status\Transfer::process->value)
            {
                $products = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
                    ->where(function ($query) {
                        $query->where('status', \Skdepot\Status\Transfer::process->value);
                        $query->orWhere('status', \Skdepot\Status\Transfer::success->value);
                    })
                    ->get();

                $inventories = \Skdepot\Model\Inventory::whereIn('product_id', $products->pluck('product_id')->toArray())
                    ->where('branch_id', $object->from_branch_id)
                    ->get();

                $inventoriesUp = [];

                foreach ($products as $product)
                {
                    foreach ($inventories as $inventory)
                    {
                        if($product->product_id === $inventory->product_id)
                        {
                            $inventoriesUp[] = [
                                'id' => $inventory->id,
                                'stock' => $inventory->stock + $product->send_quantity,
                                'status' => \Skdepot\Status\Inventory::in->value,
                            ];
                            break;
                        }
                    }
                }

                if(have_posts($inventoriesUp))
                {
                    \Skdepot\Model\Inventory::updateBatch($inventoriesUp, 'id');
                }
            }

            \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
                ->where('status', '<>', \Skdepot\Status\Transfer::cancel->value)
                ->update([
                    'status' => \Skdepot\Status\Transfer::cancel->value,
                ]);

            \Skdepot\Model\Transfer::whereKey($object->id)->update([
                'status' => \Skdepot\Status\Transfer::cancel->value,
            ]);

            DB::commit();

            response()->success('Hủy phiếu chuyển hàng thành công', [
                'status' => Admin::badge(\Skdepot\Status\Transfer::cancel->badge(), 'Đã hủy')
            ]);
        }
        catch (\Exception $e)
        {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            response()->error($e->getMessage());
        }
    }

    static function print(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu chuyển hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Skdepot\Model\Transfer::find($id);

        if(empty($object))
        {
            response()->error('phiếu kiểm kho đã đóng cửa hoặc không còn trên hệ thống');
        }

        if(!empty($object->send_date))
        {
            $object->send_date = date('d/m/Y H:s', $object->send_date);
        }

        if(!empty($object->receive_date))
        {
            $object->receive_date = date('d/m/Y H:s', $object->receive_date);
        }

        $object->total_send_price = Prd::price($object->total_send_price);

        $object->total_receive_price = Prd::price($object->total_receive_price);

        $products = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)->get();

        response()->success('Dữ liệu print', [
            'purchase' => $object->toObject(),
            'items' => $products->map(function ($item, $key) {
                $item->stt = $key+1;
                $item->receive_price = Prd::price($item->receive_price);
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

        $branch = \Skdepot\Helper::getBranchCurrent();

        $type = $request->input('export');

        $query = Qr::orderBy('created');

        $query->where(function ($qr) use ($branch) {
            $qr->where('from_branch_id', $branch->id);
            $qr->orWhere(function ($q) use ($branch) {
                $q->where('to_branch_id', $branch->id);
                $q->where('status', '<>', \Skdepot\Status\Transfer::draft->value);
            });
        });

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

        $objects = \Skdepot\Model\Transfer::gets($query);

        if(empty($objects))
        {
            response()->error(trans('Không có phiếu kiểm kho hàng nào để xuất'));
        }

        foreach ($objects as $object)
        {
            if(!empty($object->send_date))
            {
                $object->send_date = date('d/m/Y H:s', $object->send_date);
            }

            if(!empty($object->receive_date))
            {
                $object->receive_date = date('d/m/Y H:s', $object->receive_date);
            }
        }

        $export = new \Skdepot\Export();

        $export->header('code', 'Mã chuyển hàng', function($item) {
            return $item->code ?? '';
        });

        $export->header('send_date', 'Ngày chuyển', function($item) {
            return $item->send_date;
        });

        $export->header('receive_date', 'Ngày nhận', function($item) {
            return $item->receive_date;
        });

        $export->header('from_branch_name', 'Từ chi nhánh', function($item) {
            return $item->from_branch_name;
        });

        $export->header('to_branch_name', 'Tới chi nhánh', function($item) {
            return $item->to_branch_name;
        });

        $export->header('total_send_quantity', 'SL chuyển', function($item) {
            return number_format($item->total_send_quantity);
        });

        $export->header('total_send_price', 'Gia trị chuyển', function($item) {
            return number_format($item->total_send_price);
        });

        $export->header('total_receive_quantity', 'SL nhận', function($item) {
            return number_format($item->total_receive_quantity);
        });

        $export->header('total_receive_price', 'Gia trị nhận', function($item) {
            return number_format($item->total_receive_price);
        });

        $export->setTitle('DSPhieuChuyenHang_'.time());

        $export->data($objects);

        $path = $export->export('assets/export/transfer/', 'DanhSachPhieuChuyenHang_'.time().'.xlsx');

        response()->success(trans('ajax.load.success'), $path);
    }

    static function exportDetail(\SkillDo\Http\Request $request): void
    {
        $validate = $request->validate([
            'id' => Rule::make('phiếu chuyển hàng')->notEmpty()->integer(),
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }

        $id = $request->input('id');

        $object = \Skdepot\Model\Transfer::find($id);

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        $products = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)->get();

        $export = new \Skdepot\Export();

        $export->header('code', 'Mã hàng', function($item) {
            return $item->product_code ?? '';
        });

        $export->header('name', 'Tên hàng', function($item) {
            return $item->product_name .' '.Str::clear($item->product_attribute);
        });

        $export->header('send_quantity', 'SL chuyển', function($item) {
            return number_format($item->send_quantity);
        });

        $export->header('send_price', 'Gia trị chuyển', function($item) {
            return number_format($item->send_price);
        });

        $export->header('receive_quantity', 'SL nhận', function($item) {
            return number_format($item->receive_quantity);
        });

        $export->header('receive_price', 'Gia trị nhận', function($item) {
            return number_format($item->receive_price);
        });

        $export->setTitle('DSChiTietChuyenHang_'.$object->code);

        $export->data($products);

        $path = $export->export('assets/export/transfer/', 'DanhSachChiTietChuyenHang_'.$object->code.'.xlsx');

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

            $myPath = SKDEPOT_NAME.'/assets/imports/stock-take';

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

                if(count($schedule) < 3) {
                    continue;
                }

                $rowData = [
                    'code'          => trim($schedule[1]),
                    'send_quantity' => trim($schedule[2]),
                    'price'         => trim($schedule[3])
                ];

                if(empty($rowData['code']))
                {
                    continue;
                }

                $rowDatas[] = $rowData;
            }

            $branch = \Skdepot\Helper::getBranchCurrent();

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
                                'id'            => $item->id,
                                'code'          => $item->code,
                                'title'         => $item->title,
                                'fullname'      => $item->fullname,
                                'attribute_label' => $item->attribute_label ?? '',
                                'image'         => $item->image,
                                'stock'         => $item->stock,
                                'price'         => $row['price'],
                                'send_quantity' => $row['send_quantity'],
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

    //Receive
    static function saveReceiveDraft(\SkillDo\Http\Request $request): void
    {
        static::validateReceive($request, [
            'id' => Rule::make('phiếu chuyển hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $branch = \Skdepot\Helper::getBranchCurrent();

        $object = \Skdepot\Model\Transfer::whereKey($id)->where(function ($qr) use ($branch) {
            $qr->where(function ($q) use ($branch) {
                $q->where('to_branch_id', $branch->id);
                $q->where('status', \Skdepot\Status\Transfer::process->value);
            });
        })->first();

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        if($object->status === \Skdepot\Status\Transfer::success->value || $object->status === \Skdepot\Status\Transfer::cancel->value)
        {
            response()->error('Trạng thái phiếu chuyển hàng không cho phép chỉnh sữa');
        }

        [
            $transfer,
            $transferDetails,
            $productsDetail
        ] = static::dataReceiveDraft($request, $object);

        \Skdepot\Model\Transfer::whereKey($id)->update($transfer);

        try
        {
            DB::beginTransaction();

            //Cập nhật
            if(!empty($transferDetails))
            {
                \Skdepot\Model\TransferDetail::updateBatch($transferDetails, 'transfer_detail_id');
            }

            DB::commit();

            response()->success('Lưu tạm phiếu chuyển hàng thành công');
        }
        catch (\Exception $e)
        {
            DB::rollBack();

            \SkillDo\Log::error('Lỗi cập nhật phiếu chuyển hàng (nháp): '. $e->getMessage());

            response()->error($e->getMessage());
        }
    }

    static function dataReceiveDraft(\SkillDo\Http\Request $request, $object): array
    {
        $time = $request->input('time');

        if(!empty($time))
        {
            $time = str_replace('/', '-', $time);

            $time = strtotime($time);

            if($time > time())
            {
                response()->error('Thời gian nhận hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $transfer = [
            'id'                     => $object->id,
            'receive_date'           => $time,
            'total_receive_quantity' => 0,
            'total_receive_price'    => 0,
        ];

        //Từ Chi nhánh
        $branch = \Skdepot\Helper::getBranchCurrent();

        if(empty($branch))
        {
            response()->error('Chi nhánh nhận hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($branch->id !== $object->to_branch_id)
        {
            response()->error('Phiếu chuyển hàng không thuộc chi nhánh này');
        }

        //Người nhận hàng
        $user = Auth::user();
        $transfer['to_user_id']     = $user->id ?? 0;
        $transfer['to_user_name']   = $user->firstname.' '.$user->lastname;

        $productTransfers = $request->input('products');

        //Danh sách sản phẩm chuyển hàng
        $productsDetail = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
            ->get()
            ->keyBy('product_id');

        //Danh sách sản phẩm sẽ cập nhật
        $transferDetails = [];

        foreach($productTransfers as $product)
        {
            $transfer['total_receive_quantity'] += $product['receive_quantity'];

            $transfer['total_receive_price'] += $product['receive_quantity']*$product['price'];

            $transferDetail = [
                'transfer_id'        => $object->id ?? 0,
                'receive_quantity'   => $product['receive_quantity'],
                'receive_price'      => $product['receive_quantity']*$product['price'],
            ];

            if ($productsDetail->has($product['id']))
            {
                $productDetail = $productsDetail[$product['id']];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Skdepot\Status\Transfer::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }
                // Nếu sản phẩm đã không thay đổi thì bỏ qua
                if($productDetail->receive_quantity == $product['receive_quantity'] && $productDetail->receive_price == $transferDetail['receive_price'])
                {
                    unset($productsDetail[$product['id']]);
                    continue;
                }

                $transferDetail['transfer_detail_id'] = $productDetail->transfer_detail_id;

                $transferDetails[] = $transferDetail;

                unset($productsDetail[$product['id']]);
            }
        }

        if(!empty($productTransfers))
        {
            response()->error('Phiếu chuyển hàng này không thể thêm hoặc xóa bớt sản phẩm');
        }

        return [
            $transfer,
            $transferDetails,
            $productsDetail
        ];
    }

    static function saveReceive(\SkillDo\Http\Request $request): void
    {
        static::validateReceive($request, [
            'id' => Rule::make('phiếu chuyển hàng')->integer()->min(1),
        ]);

        $id = $request->input('id');

        $branch = \Skdepot\Helper::getBranchCurrent();

        $object = \Skdepot\Model\Transfer::whereKey($id)->where(function ($qr) use ($branch) {
            $qr->where(function ($q) use ($branch) {
                $q->where('to_branch_id', $branch->id);
                $q->where('status', \Skdepot\Status\Transfer::process->value);
            });
        })->first();

        if(empty($object))
        {
            response()->error('phiếu chuyển hàng không còn trên hệ thống');
        }

        if($object->status === \Skdepot\Status\Transfer::success->value || $object->status === \Skdepot\Status\Transfer::cancel->value)
        {
            response()->error('Trạng thái phiếu chuyển hàng không cho phép chỉnh sữa');
        }

        [
            $transfer,
            $transferDetails,
            $inventories,
            $toInventoriesUpdate,
            $inventoriesHistories,
            $fromInventoriesUpdate,
            $productsDetail
        ] = static::dataReceive($request, $object);

        if(empty($transferDetails))
        {
            response()->error('Không tìm thấy sản phẩm nào để cập nhật');
        }

        try {

            DB::beginTransaction();

            //Cập nhật phiếu kiểm kho hàng
            \Skdepot\Model\Transfer::whereKey($id)->update($transfer);

            //Cập nhật
            if(!empty($transferDetails))
            {
                \Skdepot\Model\TransferDetail::updateBatch($transferDetails, 'transfer_detail_id');
            }

            \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)->update([
                'status' => \Skdepot\Status\Transfer::success->value
            ]);

            //Cập nhật kho hàng
            \Skdepot\Model\Inventory::updateBatch($toInventoriesUpdate, 'id');

            if(have_posts($fromInventoriesUpdate))
            {
                //Lịch sử
                $conditions = array_map(function ($item) {
                    return ['product_id' => $item['product_id'], 'branch_id' => $item['branch_id']];
                }, $fromInventoriesUpdate);

                $histories = \Skdepot\Model\History::where('target_code', $object->code)
                    ->where('target_id', $object->id)
                    ->where('target_type', \Skdepot\Prefix::transfer->value)
                    ->whereIn(DB::raw('(product_id, branch_id)'), $conditions)
                    ->get();

                $cases = [];
                $bindings = [];
                $conditions = [];

                foreach ($fromInventoriesUpdate as &$item) {
                    $cases[] = "WHEN product_id = ? AND branch_id = ? THEN stock + ?";
                    $bindings[] = $item['product_id'];
                    $bindings[] = $item['branch_id'];
                    $bindings[] = $item['stock'];
                    $conditions[] = "({$item['product_id']}, {$item['branch_id']})";

                    foreach ($histories as $history)
                    {
                        if($history->product_id == $item['product_id'] && $history->branch_id == $item['branch_id'])
                        {
                            $item['history'] = $history;
                            break;
                        }
                    }
                }

                $sql = "UPDATE cle_inventories SET stock = CASE " . implode(' ', $cases) . " END, status = ? WHERE (product_id, branch_id) IN (" . implode(',', $conditions) . ")";

                $bindings[] = \Skdepot\Status\Inventory::in->value;

                DB::statement($sql, $bindings);

                unset($item);

                foreach ($fromInventoriesUpdate as $item) {

                    $queryUpdate = DB::table('inventory_histories')
                        ->where('product_id', $item['history']->product_id)
                        ->where('branch_id', $item['history']->branch_id)
                        ->where('id', '>=', $item['history']->id);

                    if(!empty($item['history']))
                    {
                        $stop = \Skdepot\Model\History::where('product_id', $item['history']->product_id)
                            ->where('branch_id', $item['history']->branch_id)
                            ->where('id', '>', $item['history']->id)
                            ->where('target_type', \Skdepot\Prefix::stockTake->value)
                            ->first();

                        if(!empty($stop))
                        {
                            $stop->start_stock = $stop->start_stock + $item['stock'];

                            $stop->quantity = $stop->end_stock - $stop->start_stock;

                            $stop->save();

                            $queryUpdate->where('id', '<', $stop->id);
                        }
                    }

                    $queryUpdate->update([
                        'start_stock' => DB::raw("`start_stock` + {$item['stock']}"),
                        'end_stock' => DB::raw("`end_stock` + {$item['stock']}")
                    ]);
                }
            }

            DB::commit();

            response()->success('Lưu phiếu chuyển hàng thành công');
        }
        catch (\Exception $e) {
            // Nếu có lỗi, rollback transaction
            DB::rollBack();

            response()->error($e->getMessage());
        }
    }

    static function validateReceive(\SkillDo\Http\Request $request, $rules = []): void
    {
        $validate = $request->validate([
            'products'              => Rule::make('Danh sách sản phẩm')->notEmpty(),
            'products.*.id'         => Rule::make('Id sản phẩm')->notEmpty()->integer(),
            'products.*.receive_quantity'   => Rule::make('Số lượng sản phẩm')->notEmpty()->integer()->min(1),
            'products.*.price'      => Rule::make('Giá trị sản phẩm')->notEmpty()->integer()->min(0),
            ...$rules
        ]);

        if ($validate->fails())
        {
            response()->error($validate->errors());
        }
    }

    static function dataReceive(\SkillDo\Http\Request $request, $object = null): array
    {
        $time = $request->input('time');

        if(!empty($time))
        {
            $time = str_replace('/', '-', $time);

            $time = strtotime($time);

            if($time > time())
            {
                response()->error('Thời gian nhận hàng không thể lớn hơn thời gian hiện tại');
            }
        }
        else
        {
            $time = time();
        }

        $transfer = [
            'id'                     => $object->id,
            'receive_date'           => $time,
            'total_receive_quantity' => 0,
            'total_receive_price'    => 0,
            'status'                 => \Skdepot\Status\Transfer::success->value
        ];

        //Đến Chi nhánh
        $branchTo = \Skdepot\Helper::getBranchCurrent();

        if(empty($branchTo))
        {
            response()->error('Chi nhánh nhận hàng đã đóng cửa hoặc không còn trên hệ thống');
        }

        if($branchTo->id !== $object->to_branch_id)
        {
            response()->error('Phiếu chuyển hàng không thuộc chi nhánh này');
        }

        //Người nhận hàng
        $user = Auth::user();
        $transfer['to_user_id']     = $user->id ?? 0;
        $transfer['to_user_name']   = $user->firstname.' '.$user->lastname;

        $productTransfers = $request->input('products');

        $productsId = array_map(function ($item) { return $item['id']; }, $productTransfers);

        $productsId = array_unique($productsId);

        $inventories = \Skdepot\Model\Inventory::select(['id', 'product_id', 'parent_id', 'branch_id', 'stock', 'status', 'price_cost'])
            ->whereIn('product_id', $productsId)
            ->where('branch_id', $branchTo->id)
            ->get();

        if($inventories->count() !== count($productsId))
        {
            response()->error('Số lượng sản phẩm cập nhật và số lượng sản phẩm trong kho hàng không khớp');
        }

        $inventories = $inventories->keyBy('product_id');

        //Danh sách sản phẩm chuyển hàng
        $productsDetail = \Skdepot\Model\TransferDetail::where('transfer_id', $object->id)
            ->get()
            ->keyBy('product_id');

        //Danh sách sản phẩm sẽ cập nhật
        $transferDetails = [];

        $productsCheck = [];

        $toInventoriesUpdate = [];

        $fromInventoriesUpdate = [];

        $inventoriesHistories = [];

        foreach($productTransfers as $key => $product)
        {
            $productId = $product['id'];

            if(!empty($productsCheck[$productId]))
            {
                response()->error('Sản phẩm '.$product['title'].$product['attribute_label'].' đã tồn tại trong danh sách sản phẩm');
            }

            $productsCheck[$productId] = $productId;

            if (!$inventories->has($productId))
            {
                response()->error('Không tìm thấy tồn kho của sản phẩm '.$product['title'].$product['attribute_label']);
            }

            if ($productsDetail->has($product['id']))
            {
                $productDetail = $productsDetail[$product['id']];

                $inventory = $inventories[$product['id']];

                if($productDetail->send_quantity < $product['receive_quantity'])
                {
                    response()->error('Sản phẩm '.$product['title'].$product['attribute_label'].' có số lượng nhận lớn hơn số lượng chuyển');
                }

                $transfer['total_receive_quantity'] += $product['receive_quantity'];

                $transfer['total_receive_price'] += $product['receive_quantity']*$product['price'];

                $transferDetail = [
                    'transfer_id'        => $object->id ?? 0,
                    'receive_quantity'   => $product['receive_quantity'],
                    'receive_price'      => $product['receive_quantity']*$product['price'],
                    'status'             => \Skdepot\Status\Transfer::success->value
                ];

                // Nếu sản phẩm đã hoàn thành thì bỏ qua
                if ($productDetail->status === \Skdepot\Status\Transfer::success->value)
                {
                    unset($productsDetail[$product['id']]);
                    unset($productTransfers[$key]);
                    continue;
                }

                //Nhận hàng vào kho
                if($product['receive_quantity'] !== 0)
                {
                    $toInventoriesUpdate[] = [
                        'id'     => $inventory->id,
                        'stock'  => $inventory->stock + $product['receive_quantity'],
                        'status' => \Skdepot\Status\Inventory::in->value
                    ];

                    $inventoriesHistories[] = [
                        'inventory_id'  => $inventory->id,
                        'product_id'    => $inventory->product_id,
                        'branch_id'     => $inventory->branch_id,
                        //Đối tượng
                        'target_id'   => $object->id ?? 0,
                        'target_code' => $object->code ?? '',
                        'target_type' => \Skdepot\Prefix::transfer->value,
                        'target_name' => 'Nhận hàng',
                        //Thông tin
                        'cost'          => $product['price'],
                        'price'         => $product['price']*$product['receive_quantity'],
                        'quantity'      => $product['receive_quantity'],
                        'start_stock'   => $inventory->stock,
                        'end_stock'     => $inventory->stock + $product['receive_quantity'],
                    ];
                }

                //Trả hàng về kho chuyển
                if(($productDetail->send_quantity - $product['receive_quantity']) !== 0)
                {
                    $fromInventoriesUpdate[] = [
                        'product_id'   => $inventory->product_id,
                        'branch_id'    => $object->from_branch_id,
                        'stock'        => $productDetail->send_quantity - $product['receive_quantity'],
                    ];
                }

                // Nếu sản phẩm đã không thay đổi thì bỏ qua
                if($productDetail->receive_quantity == $product['receive_quantity'] && $productDetail->receive_price == $transferDetail['receive_price'])
                {
                    unset($productsDetail[$product['id']]);
                    unset($productTransfers[$key]);
                    continue;
                }

                $transferDetail['transfer_detail_id'] = $productDetail->transfer_detail_id;

                $transferDetails[] = $transferDetail;

                unset($productsDetail[$product['id']]);
                unset($productTransfers[$key]);
            }
        }

        if(!empty($productTransfers))
        {
            response()->error('Phiếu chuyển hàng này không thể thêm hoặc xóa bớt sản phẩm');
        }

        return [
            $transfer,
            $transferDetails,
            $inventories,
            $toInventoriesUpdate,
            $inventoriesHistories,
            $fromInventoriesUpdate,
            $productsDetail,
        ];
    }
}

Ajax::admin('TransferAdminAjax::loadProductsDetail');
Ajax::admin('TransferAdminAjax::loadProductsEdit');
Ajax::admin('TransferAdminAjax::addDraft');
Ajax::admin('TransferAdminAjax::saveDraft');
Ajax::admin('TransferAdminAjax::sendAdd');
Ajax::admin('TransferAdminAjax::sendSave');
Ajax::admin('TransferAdminAjax::cancel');
Ajax::admin('TransferAdminAjax::print');
Ajax::admin('TransferAdminAjax::export');
Ajax::admin('TransferAdminAjax::exportDetail');
Ajax::admin('TransferAdminAjax::import');
Ajax::admin('TransferAdminAjax::saveReceiveDraft');
Ajax::admin('TransferAdminAjax::saveReceive');