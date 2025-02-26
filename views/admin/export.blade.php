<div class="modal fade" id="js_export_detail_modal" data-action="{!! $action !!}">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ $title }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <a href="" class="btn btn-blue btn-blue-bg btn-download" download><i class="fa-duotone fa-file-excel"></i> {{trans('export.button.download')}}</a>
                <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">{{trans('button.close')}}</button>
            </div>
        </div>
    </div>
</div>