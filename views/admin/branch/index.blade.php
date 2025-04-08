<script>
    $(function() {
        $(document)
            .on('change', '.js_branch_btn_website', function () {

                const element = $(this)

                $('input.js_branch_btn_website').prop('checked', false);

                element.prop('checked', true);

                let id = element.attr('data-id');

                let data = {
                    action: 'StockBranchAjax::website',
                    id: id
                }

                request
                    .post(ajax, data)
                    .then(function (response) {
                        SkilldoMessage.response(response);
                    }.bind(this));
            })
    })
</script>