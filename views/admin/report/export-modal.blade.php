<div class="modal fade" id="js_report_export_modal">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5">{{ trans('export.gfr.title') }}</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div id="js_report_export_form">
                {!! Admin::loading() !!}
            </div>
            <div id="js_report_export_result" style="display:none;">
                <div class="modal-body">
                    <a href="" class="btn btn-blue btn-blue-bg" download><i class="fa-duotone fa-file-excel"></i> {{trans('export.button.download')}}</a>
                    <button class="btn btn-white" type="button" data-bs-dismiss="modal" aria-label="Close">{{trans('button.close')}}</button>
                </div>
            </div>
        </div>
    </div>
</div>
