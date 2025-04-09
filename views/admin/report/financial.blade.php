
<div class="ui-title-bar__group">
    <h1 class="ui-title-bar__title text-3xl">{{ $report['title'] }}</h1>
</div>

<div id="js_report_data">
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
        <div class="box-content">
            {!! Admin::loading() !!}
            <div class="report-box" id="js_report_table_tbody"></div>
            <style>
                .report-box {
                    display: grid;
                    gap: 16px;
                }
                .report-gird {
                    display: grid;
                    gap: 8px;
                    grid-template-columns: 1fr 1fr 1fr;
                }
                .report-gird-item {
                    display: flex;
                    gap: 4px;
                    -webkit-box-align: start;
                    align-items: start;
                }
            </style>
            @include('empty')
        </div>
    </div>
</div>

<script id="js_order_report_item_template" type="text/x-custom-template">
    @foreach(\Skdepot\ReportColumns::financial() as $key => $column)
        <div class="report-gird">
            <div class="report-gird-item">{!! $column['label'] !!}</div>
            <div class="report-gird-item text-primary {!! $column['valueClass'] ?? '' !!}">{!! '${'.$key.'}' !!}</div>
            <div class="report-gird-item"></div>
        </div>
        @if(!empty($column['child']))
            @foreach($column['child'] as $keyChild => $columnChild)
                <div class="report-gird">
                    <div class="report-gird-item ps-4 text-secondary">{!! $columnChild['label'] !!}</div>
                    <div class="report-gird-item {!! $columnChild['valueClass'] ?? '' !!}">{!! '${'.$keyChild.'}' !!}</div>
                    <div class="report-gird-item"></div>
                </div>
    @endforeach
    @endif
    @endforeach
</script>

<style>
    .page-content .page-body
    {
        overflow: visible;
    }
</style>

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

                data.action = 'StockReportFinancialAdminAjax::financial';

                request.post(ajax, data).then(function (response) {

                    this.elements.loading.hide()

                    if (response.status === 'success') {

                        let str = '';

                        if(response.data?.total?.profit !== undefined)
                        {
                            str += this.elements.itemTemplate.html().split(/\$\{(.+?)\}/g).map(render(response.data.total)).join('');

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

            events()
            {
                const self = this;

                $(document)
                    .on('change', '#dateRangeInputValue_time', function () {
                        self.load()
                    })
                    .on('click', '#js_report_btn_reload', function () {
                        self.load()
                    })
            }
        }

        new ReportSaleHandle()
    });
</script>