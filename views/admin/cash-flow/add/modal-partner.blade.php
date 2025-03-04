<div class="modal fade" id="js_cash_flow_partner_modal_add" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <form class="modal-content" id="js_cash_flow_partner_form">
            <div class="modal-header">
                <h4 class="modal-title">Thêm đối tượng chi/nộp</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row">
                    {!! $form->html() !!}
                </div>
            </div>
            <div class="modal-footer">
                {!! Admin::button('green', ['text' => trans('button.save'), 'icon' => Admin::icon('save'), 'type' => 'submit']) !!}
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{ trans('button.close') }}</button>
            </div>
        </form>
    </div>
</div>