<div class="modal fade" id="js_export_list_modal" data-action="{!! $action !!}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ $title }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="js_export_list_form">
                {!! Admin::loading() !!}
                <div class="modal-body" style="overflow-x:auto; max-height:500px;">
                    <div class="form-group">
                        <label class="form-check radio d-block mb-2">
                            <input type="radio" name="type" value="page" class="form-check-input" checked> Trang hiện tại
                        </label>
                        <label class="form-check radio d-block mb-2">
                            <input type="radio" name="type" value="checked" class="form-check-input"> Xuất theo dữ liệu được chọn
                        </label>
                        <label class="form-check radio d-block mb-2">
                            <input type="radio" name="type" value="search" class="form-check-input"> Xuất theo kết quả tìm kiếm hiện tại
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">{{trans('button.cancel')}}</button>
                    <button class="btn btn-blue" type="button" id="js_export_list_btn_submit"><i class="fa-light fa-download"></i> {{trans('export.data')}}</button>
                </div>
            </div>
            <div id="js_export_list_result" style="display:none;">
                <div class="modal-body">
                    <a href="" class="btn btn-blue btn-blue-bg" download><i class="fa-duotone fa-file-excel"></i> {{trans('export.button.download')}}</a>
                    <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">{{trans('close')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>