<script>
    $(function() {
        $(document).on('click', '.js_btn_customer_to_member', function () {

            const button = $(this)

            SkilldoConfirm.show({
                element : button,
                success : function (response) {
                    SkilldoMessage.response(response);
                    if(response.status === 'success') {
                        button.remove()
                    }
                }
            })
        })
    })
</script>