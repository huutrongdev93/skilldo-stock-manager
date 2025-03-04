<div class="modal fade" id="js_cash_flow_{{ $modalId }}_modal_add" tabindex="-1" role="dialog" aria-hidden="true">
    <form class="modal-dialog modal-lg js_cash_flow_modal_add" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">{{ $modalTitle }}</h4>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                {!! $form->html() !!}
            </div>
            <div class="modal-footer">
                {!! Admin::button('green', ['text' => trans('button.save'), 'icon' => Admin::icon('save'), 'type' => 'submit']) !!}
                <button type="button" class="btn btn-white" data-bs-dismiss="modal">{{ trans('button.close') }}</button>
            </div>
        </div>
    </form>
</div>