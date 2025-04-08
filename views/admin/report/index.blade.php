<div class="box mb-2">
    <div class="box-header">
        <h4 class="box-title">Báo cáo</h4>
    </div>
    <div class="box-content p-3">
        <ul class="js_report_detail_tabs nav nav-tabs nav-tabs-horizontal mb-4" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="info" aria-selected="true">Tất cả</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history" aria-selected="true">Bán hàng</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history" aria-selected="true">Tài chính</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history" aria-selected="true">Kho hàng</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="history" aria-selected="true">Khách hàng</button>
            </li>
        </ul>

        <div class="report-list">
            @foreach($reports as $report)
                <div class="report-item d-flex justify-content-between p-3">
                    <div class="report-name">
                        <a href="{!! $report['href'] !!}">
                            <i class="fa-solid fa-star"></i> &nbsp;&nbsp;
                            {!! $report['label'] !!}
                        </a>
                    </div>
                    <div class="report-tag">
                        @foreach($report['badge'] as $badge)
                            {!! $badge !!}
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
    .report-item {
        border: 1px solid var(--sd-content-bg);
        border-bottom: 0;
        font-size: 1.1rem;
    }
    .report-list .report-item:last-child {
        border-bottom: 1px solid var(--sd-content-bg);
    }
    .report-item a {
        color: #515151;
    }
</style>