
<div class="ui-title-bar__group">
    <h1 class="ui-title-bar__title text-3xl">{{ $report['title'] }}</h1>
</div>

<div id="js_report_data" data-report-key="{!! $report['key'] !!}">
    <div class="box mb-3">
        <div class="box-heading d-flex gap-2 justify-content-between">
            <div class="js_report_filter_wrapper d-flex gap-2">
                {!! $form->html(); !!}
            </div>
            <div class="js_report_action">
                {!! Admin::button('white', [
                    'id' => 'js_report_btn_reload',
                    'icon' => '<i class="fa-duotone fa-arrows-rotate"></i>',
                    'text' => 'Lọc',
                ]) !!}
                {!! Admin::button('blue', [
                    'id' => 'js_report_btn_export',
                    'icon' => '<i class="fa-light fa-download"></i>',
                    'text' => 'Xuất Excel',
                ]) !!}
            </div>
        </div>
        <div class="box-content p-0">
            {!! Admin::loading() !!}
            <table class="display table table-striped">
                <thead>
                    <tr>
                        @foreach($report['columns'] as $columnsKey => $column)
                            <th>{!! $column['label'] ?? '' !!}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody id="js_report_table_tbody"></tbody>
            </table>
            @include('empty')
        </div>
    </div>
</div>

<style>
    table.table tr:nth-child(odd) > td {
        background-color: #f9f9f9;
    }

    .page-content .page-body
    {
        overflow: visible;
    }

    table tr.column-total td {
        font-weight: bold;
    }
</style>

<script id="js_order_report_item_template" type="text/x-custom-template">
    <tr class="${class}">
        @foreach($report['columns'] as $columnsKey => $column)
            <td class="{!! $column['class'] ?? '' !!}">{{'${'.$columnsKey.'}'}}</td>
        @endforeach
    </tr>
</script>

{!! Plugin::partial(SKDEPOT_NAME, 'admin/report/export-modal', [
    'report' => 'sales_'.$report['key'],
]); !!}

<script defer>
    $(function () {

        class ReportSaleHandle
        {
            constructor()
            {
                this.element = {
                    filter: $('.js_report_filter_wrapper'),
                    itemTemplate: $('#js_order_report_item_template'),
                    tbody: $('#js_report_table_tbody'),
                    loading: $('#js_report_data .loading'),
                    empty: $('#empty-filter'),
                }

                this.dataExport = {
                    modal: {
                        element: $('#js_report_export_modal'),
                        handler: new bootstrap.Modal('#js_report_export_modal'),
                        loading: $('#js_report_export_modal .loading')
                    }
                }

                this.key = $('#js_report_data').data('report-key')

                this.load()

                this.events()
            }

            get elements() {
                return this.element;
            }

            load()
            {
                this.elements.loading.show()

                let data = $(':input', this.elements.filter).serializeJSON()

                data.action = 'StockReportSaleAdminAjax::sales';

                data.report = this.key;

                request.post(ajax, data).then(function (response) {

                    this.elements.loading.hide()

                    if (response.status === 'success') {

                        let str = '';

                        if(response.data.items?.length > 0)
                        {
                            if(response.data?.totals)
                            {
                                response.data.totals.class = 'column-total';

                                str += this.elements.itemTemplate.html().split(/\$\{(.+?)\}/g).map(render(response.data.totals)).join('');
                            }

                            for (const [key, item] of Object.entries(response.data.items)) {
                                item.class = '';
                                str += this.elements.itemTemplate.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                            }

                            this.elements.empty.hide()
                        }
                        else
                        {
                            this.elements.empty.show()
                        }

                        this.elements.tbody.html(str);


                    }
                    else
                    {
                        this.elements.tbody.html('');
                        this.elements.empty.show()
                    }
                }.bind(this))
            }

            openModalExport(element) {
                this.dataExport.modal.element.find('#js_report_export_form').show();
                this.dataExport.modal.element.find('#js_report_export_result').hide();
                this.dataExport.modal.handler.show()
                this.export()
                return false;
            }

            export(element)
            {
                this.dataExport.modal.loading.show();

                let data = {
                    action: 'StockReportSaleAdminAjax::export',
                    search: $(':input', this.elements.filter).serializeJSON(),
                    report: this.key
                };

                request.post(ajax, data).then(function (response) {

                    this.dataExport.modal.loading.hide();

                    if (response.status === 'success')
                    {
                        this.dataExport.modal.element.find('#js_report_export_form').hide();
                        this.dataExport.modal.element.find('#js_report_export_result a').attr('href', response.data);
                        this.dataExport.modal.element.find('#js_report_export_result').show();
                    }

                }.bind(this));

                return false;
            }

            events()
            {
                const self = this;

                $(document)
                    .on('change', '#group', function () {
                        self.load()
                    })
                    .on('change', '#dateRangeInputValue_time', function () {
                        self.load()
                    })
                    .on('click', '#js_report_btn_reload', function () {
                        self.load()
                    })
                    .on('click', '#js_report_btn_export', function () {
                        self.openModalExport($(this))
                        return false;
                    })
            }
        }

        new ReportSaleHandle()
    });
</script>