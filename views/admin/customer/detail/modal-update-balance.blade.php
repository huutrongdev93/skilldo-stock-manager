<div class="modal fade" id="js_user_debt_update_balance_modal" tabindex="-1" role="dialog" aria-hidden="true">
    <form class="modal-dialog modal-xs js_user_debt_update_balance_form">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Điều chỉnh</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                <div class="row mb-3">
                    <div class="col-md-4"><b>Nợ cần trả hiện tại</b></div>
                    <div class="col-md-8"><span class="js_user_debt_update_balance_total"></span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><b>Giá trị nợ điều chỉnh:</b></div>
                    <div class="col-md-8">
                        <input type="text" min="0" value="0" class="form-control js_input_user_debt_update_balance_number" data-input-type="currency" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4"><b>Mô tả:</b></div>
                    <div class="col-md-8">
                        <textarea class="form-control js_input_user_debt_update_balance_note"></textarea>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                {!! Admin::button('green', ['text' => 'Cập nhật', 'icon' => Admin::icon('save'), 'type' => 'button', 'class' => 'js_user_debt_update_balance_btn_submit']) !!}
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{ trans('button.close') }}</button>
            </div>
        </div>
    </form>
</div>