
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

    table.table > thead > tr > th {
        font-size: calc(1rem * 1) !important;
        line-height: calc(1.50rem * 1);
        text-transform: none;
        letter-spacing: normal;
        padding: 12px;
    }

    table.table tr td {
        font-size: calc(1rem * 1);
        line-height: calc(1.50rem * 1);
        text-transform: none;
        letter-spacing: normal;
        color: rgb(15, 24, 36);
        padding: 12px;
    }
</style>

<script id="js_report_item_total_template" type="text/x-custom-template">
    <tr class="js_column ${class}">
        @foreach($report['columns'] as $columnsKey => $column)
            <td class="{!! $column['class'] ?? '' !!}">{{'${'.$columnsKey.'}'}}</td>
        @endforeach
    </tr>
</script>

<script id="js_report_item_template" type="text/x-custom-template">
    <tr class="js_column {!! (isset($report['columnsChild']) ? 'js_column_parent js_report_column_parent' : '') !!} tr_${id} ${class}" data-id="${id}">
        @foreach($report['columns'] as $columnsKey => $column)
            <td class="{!! $column['class'] ?? '' !!}">{{'${'.$columnsKey.'}'}}</td>
        @endforeach
    </tr>
    @if(isset($report['columnsChild']))
    <tr class="js_column_child tr_child_${id}">
        <td colspan="{{ count($report['columns']) }}" style="position: relative">
            {!! Admin::loading() !!}
            <div class="table-responsive"></div>
        </td>
    </tr>
    @endif
</script>

@if(isset($report['columnsChild']))
<script id="js_report_item_child_template" type="text/x-custom-template">
    <table class="display table table-striped media-table ">
        <thead>
            <tr>
                @foreach($report['columnsChild'] as $columnsKey => $column)
                    <th>{!! $column['label'] ?? '' !!}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr class="js_column">
                @foreach($report['columnsChild'] as $columnsKey => $column)
                    <td class="{!! $column['class'] ?? '' !!}">{{'${'.$columnsKey.'}'}}</td>
                @endforeach
            </tr>
        </tbody>
    </table>
</script>
@endif

{!! Plugin::partial(STOCK_NAME, 'admin/report/export-modal', [
    'report' => 'sales_'.$report['key'],
]); !!}

<script defer>
    $(function () {

        class ReportSaleHandle
        {
            constructor()
            {
                this.ajax = {};

                this.element = {
                    filter: $('.js_report_filter_wrapper'),
                    totalTemplate: $('#js_report_item_total_template'),
                    itemTemplate: $('#js_report_item_template'),
                    itemChildTemplate: $('#js_report_item_child_template'),
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

                if(this.key === 'supplier')
                {
                    this.ajax = {
                        load: 'StockReportInventoryAdminAjax::supplier',
                        detail: 'StockReportInventoryAdminAjax::supplierDetail',
                    }
                }

                if(this.key === 'product')
                {
                    this.ajax = {
                        load: 'StockReportInventoryAdminAjax::product',
                    }
                }

                this.detail = SkilldoUtil.reducer()

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

                data.action = this.ajax.load;

                request.post(ajax, data).then(function (response) {

                    this.elements.loading.hide()

                    if (response.status === 'success') {

                        let str = '';

                        if(response.data.items?.length > 0)
                        {
                            if(response.data?.totals)
                            {
                                response.data.totals.class = 'column-total';

                                str += this.elements.totalTemplate.html().split(/\$\{(.+?)\}/g).map(render(response.data.totals)).join('');
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

            loadDetail(element)
            {
                let id = element.data('id');

                if(!this.detail.has(id))
                {
                    const column = $(`.tr_child_${id} td`);

                    column.find('.loading').show()

                    let search = $(':input', this.elements.filter).serializeJSON()

                    let data = {
                        id: id,
                        action: this.ajax.detail,
                        time: search.time
                    }

                    request.post(ajax, data).then(function (response) {

                        column.find('.loading').hide()

                        if (response.status === 'success') {

                            this.detail.add({
                                id: id
                            })

                            let str = '';

                            if(response.data.items?.length > 0)
                            {
                                for (const [key, item] of Object.entries(response.data.items)) {
                                    str += this.elements.itemChildTemplate.html().split(/\$\{(.+?)\}/g).map(render(item)).join('');
                                }
                            }

                            column.find('.table-responsive').html(str);
                        }
                        else
                        {
                            column.find('.table-responsive').html('');
                        }

                    }.bind(this))
                }
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
                    action: 'StockReportInventoryAdminAjax::export',
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
                    .on('click', '.js_report_column_parent', function () {
                        self.loadDetail($(this))
                        return false;
                    })
            }
        }

        new ReportSaleHandle()
    });
</script>