<div class="col-md-12">
    <?php foreach ($branches as $branch) {

        $inventory = Inventory::get(Qr::set('product_id', $product_id)->where('branch_id', $branch->id));
        $stock = (!empty($inventory->stock)) ? $inventory->stock : 0;
        ?>
        <hr />
        <div class="row stock-update">
            <div class="col-md-6"><h5>Tồn kho <?php echo $branch->name;?></h5></div>
            <div class="col-md-6">
                <div class="spinner">
                    <span class="quantity-btn minus quantity-down"></span>
                    <input type="number" name="product_stock[<?php echo $branch->id;?>]" value="<?php echo $stock;?>" min="0" class="quantity-selector">
                    <span class="quantity-btn plus quantity-up"></span>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<style>
    .spinner {
        -moz-border-radius: 25px;
        -webkit-border-radius: 25px;
        border-radius: 25px;
        border: 1px solid #edeff2;
        height: 40px;
        padding: 10px 20px 0;
        position: relative;
        float: right;
    }
    .spinner .quantity-btn {
        display: block;
        cursor: pointer;
        float: left;
        height: 10px;
        margin-top: 4px;
        position: relative;
        width: 10px;
    }
    .spinner .quantity-btn:before {
        -moz-transition: all 0.3s ease-in-out;
        -o-transition: all 0.3s ease-in-out;
        -webkit-transition: all 0.3s ease-in-out;
        transition: all 0.3s ease-in-out;
        background-color: #086fcf;
        content: "";
        display: block;
        height: 2px;
        left: 0;
        margin-top: -1px;
        position: absolute;
        top: 50%;
        width: 100%;
    }
    .spinner .quantity-btn.plus:after {
        -moz-transition: all 0.3s ease-in-out;
        -o-transition: all 0.3s ease-in-out;
        -webkit-transition: all 0.3s ease-in-out;
        transition: all 0.3s ease-in-out;
        background-color: #086fcf;
        bottom: 0;
        content: "";
        display: block;
        height: 100%;
        left: 50%;
        margin-left: -1px;
        position: absolute;
        top: 0;
        width: 2px;
    }
    .spinner input {
        border: 0 none;
        color: #16161a;
        display: block;
        float: left;
        font-size: 14px;
        height: 17px !important;
        line-height: 17px !important;
        margin-left: 1px;
        padding-bottom: 0;
        padding-top: 0;
        width: 100px;
        outline: none;
        text-align: center;
    }
    .stock-update { margin-bottom: 10px; overflow: hidden;}
</style>
<script defer>
    $('.stock-update').each(function() {
        let spinner = $(this),
            input = spinner.find('input[type="number"]'),
            btnUp = spinner.find('.quantity-up'),
            btnDown = spinner.find('.quantity-down'),
            min = input.attr('min'),
            max = input.attr('max');

        btnUp.click(function() {
            let oldValue = parseFloat(input.val());
            let newVal = oldValue;
            if (oldValue >= max) {
                newVal = oldValue;
            } else {
                newVal = oldValue + 1;
            }
            spinner.find("input").val(newVal);
            spinner.find("input").trigger("change");
        });

        btnDown.click(function() {
            let oldValue = parseFloat(input.val());
            let newVal = oldValue;
            if (oldValue <= min) {
                newVal = oldValue;
            } else {
                newVal = oldValue - 1;
            }
            spinner.find("input").val(newVal);
            spinner.find("input").trigger("change");
        });
    });
</script>