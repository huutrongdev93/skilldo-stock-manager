<ul class="js_order_detail_history_tabs nav nav-tabs nav-tabs-horizontal" role="tablist">
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link active" data-tab="history" aria-selected="true" data-id="{!! $order->id !!}">Thông tin</button>
    </li>
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" data-tab="payment" aria-selected="true" data-id="{!! $order->id !!}">Lịch sử thanh toán</button>
    </li>
    <li class="nav-item" role="presentation">
        <button type="button" class="nav-link" data-tab="return" aria-selected="true" data-id="{!! $order->id !!}">Lịch sử trả hàng</button>
    </li>
</ul>

<script>
    $(function() {
        class OrderDetailHistoryHandle
        {
            constructor() {
                this.id = 0;

                this.history = {
                    info: {
                        tab: $('#order_history .order_cart__section')
                    },
                    payment: {
                        tab: $('#js_order_detail_history_tab_payment'),
                        tbody: $(`#js_order_detail_history_tab_payment tbody`),
                        loading: $(`#js_order_detail_history_tab_payment .loading`),
                        templateName: `#js_order_detail_history_payment_template`,
                    },
                    return: {
                        tab: $('#js_order_detail_history_tab_return'),
                        tbody: $(`#js_order_detail_history_tab_return tbody`),
                        loading: $(`#js_order_detail_history_tab_return .loading`),
                        templateName: `#js_order_detail_history_return_template`,
                    }
                }

                this.events()
            }

            loadPayment()
            {
                this.history.payment.tbody.html('');

                let self = this

                this.history.payment.loading.show();

                let data = {
                    action    : 'StockOrderAdminAjax::detailHistoryPayment',
                    id : this.id,
                }

                request.post(ajax, data)
                    .then(function(response) {

                        if (response.status === 'error') SkilldoMessage.response(response);

                        if (response.status === 'success') {

                            let historyContent = ''

                            for (const [key, item] of Object.entries(response.data.items)) {
                                historyContent += $(self.history.payment.templateName)
                                    .html()
                                    .split(/\$\{(.+?)\}/g)
                                    .map(render(item))
                                    .join('');
                            }

                            this.history.payment.tbody.html(historyContent);
                        }

                        this.history.payment.loading.hide();

                    }.bind(this))
            }

            loadReturn()
            {
                this.history.return.tbody.html('');

                let self = this

                this.history.return.loading.show();

                let data = {
                    action    : 'StockOrderAdminAjax::detailHistoryReturn',
                    id : this.id,
                }

                request.post(ajax, data)
                    .then(function(response) {

                        if (response.status === 'error') SkilldoMessage.response(response);

                        if (response.status === 'success') {

                            let historyContent = ''

                            for (const [key, item] of Object.entries(response.data.items)) {
                                historyContent += $(self.history.return.templateName)
                                    .html()
                                    .split(/\$\{(.+?)\}/g)
                                    .map(render(item))
                                    .join('');
                            }

                            this.history.return.tbody.html(historyContent);
                        }

                        this.history.return.loading.hide();

                    }.bind(this))
            }

            events() {

                let handle = this;

                $(document)
                    .on('click', '.js_order_detail_history_tabs .nav-link', function () {

                        handle.id = $(this).data('id');

                        let tabId = $(this).data('tab');

                        // Xóa class active khỏi tất cả các tab
                        $('.js_order_detail_history_tabs .nav-link').removeClass('active');

                        // Thêm class active cho tab vừa click
                        $(this).addClass('active');

                        // Ẩn tất cả các tab content
                        handle.history.info.tab.hide();
                        handle.history.payment.tab.hide();
                        handle.history.return.tab.hide();

                        // Hiển thị tab content tương ứng với data-tab khớp với id
                        if(tabId === 'history')
                        {
                            handle.history.info.tab.show();
                        }

                        if(tabId === 'payment')
                        {
                            handle.history.payment.tab.show();
                            handle.loadPayment()
                        }

                        if(tabId === 'return')
                        {
                            handle.history.return.tab.show();
                            handle.loadReturn()
                        }

                        // Cập nhật aria-selected
                        $('.nav-link').attr('aria-selected', 'false');

                        $(this).attr('aria-selected', 'true');

                        return false
                    })
            }
        }

        new OrderDetailHistoryHandle()
    })
</script>