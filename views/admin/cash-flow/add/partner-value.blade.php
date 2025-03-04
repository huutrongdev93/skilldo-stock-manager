<div class="stock-form-group form-group">
    <label class="control-label">
        <p>{{ $label }}</p>
        <a href="#" class="js_partner_add">Thêm người {{ ($type == 'receipt') ? 'nộp' : 'nhận' }}</a>
    </label>
    <div class="form-input">
        {!! form()->popoverAdvance('partner_value_customer', [
            'search' => 'user',
            'multiple' => false,
            'noImage' => true,
            'start' => '<div class="js_partner_value_input js_partner_value_C" style="display:none">',
            'end' => '</div>'
            ])->html() !!}

        {!! form()->select2('partner_value_supplier', $suppliers, [
            'start' => '<div class="js_partner_value_input js_partner_value_S" style="display:none">',
            'end' => '</div>'
        ])->html() !!}

        {!! form()->popoverAdvance('partner_value_other', [
            'search' => 'CashFlowPartner',
            'multiple' => false,
            'noImage' => true,
            'start' => '<div class="js_partner_value_input js_partner_value_O">',
            'end' => '</div>'
            ])->html() !!}
    </div>
</div>