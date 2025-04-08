<?php
use SkillDo\Form\InputBuilder;

class RangeTimePicker extends InputBuilder {

    function __construct($args = [], mixed $value = null) {

        parent::__construct($args, $value);

        $this->type = 'rangeTimePicker';
    }

    public function output(): static
    {
        $this->output .= Plugin::partial(STOCK_NAME, 'admin/report/field/RangeTimePicker', [
            'attributes' => $this->attributes(true)
        ]);

        return $this;
    }
}