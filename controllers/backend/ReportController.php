<?php

use Illuminate\Support\Collection;
use SkillDo\Http\Request;

class ReportController extends MY_Controller {

    function __construct()
    {
        add_action('beforeLoad', function () {
            Cms::set('loadWidget', false);
        });

        parent::__construct();
    }

    public function index(Request $request): void
    {
        $reports = [
            'salesTime' => [
                'label' => 'Doanh thu theo thời gian',
                'badge' => [
                    Admin::badge('gray', 'Bán hàng'),
                ],
                'tab'   => 'sales',
                'href'  => Url::route('admin.report.sales', ['id' => 'time']),
            ],
            'salesProduct' => [
                'label' => 'Doanh thu theo sản phẩm',
                'badge' => [
                    Admin::badge('gray', 'Bán hàng')
                ],
                'tab'   => 'sales',
                'href'  => Url::route('admin.report.sales', ['id' => 'product']),
            ],
            'salesBranch' => [
                'label' => 'Doanh thu theo chi nhánh',
                'badge' => [
                    Admin::badge('gray', 'Bán hàng')
                ],
                'tab'   => 'sales',
                'href'  => Url::route('admin.report.sales', ['id' => 'branch']),
            ],
            'salesCustomer' => [
                'label' => 'Doanh thu theo khách hàng',
                'badge' => [
                    Admin::badge('gray', 'Bán hàng'),
                    Admin::badge('green', 'Khách hàng')
                ],
                'tab'   => 'sales',
                'href'  => Url::route('admin.report.sales', ['id' => 'customer']),
            ],
            'inventorySupplier' => [
                'label' => 'Báo cáo nhập trả hàng theo NCC',
                'badge' => [
                    Admin::badge('gray', 'Nhà cung cấp'),
                    Admin::badge('blue', 'Kho hàng')
                ],
                'tab'   => 'supplier',
                'href'  => Url::route('admin.report.inventory', ['id' => 'supplier']),
            ],
            'inventoryProduct' => [
                'label' => 'Báo cáo nhập trả hàng theo sản phẩm',
                'badge' => [
                    Admin::badge('gray', 'Sản phẩm'),
                    Admin::badge('blue', 'Kho hàng')
                ],
                'tab'   => 'supplier',
                'href'  => Url::route('admin.report.inventory', ['id' => 'product']),
            ],
            'financial' => [
                'label' => 'Báo cáo lợi nhuận tổng hợp',
                'badge' => [
                    Admin::badge('blue', 'Tài chính')
                ],
                'tab'   => 'financial',
                'href'  => Url::route('admin.report.financial'),
            ],
        ];

        Cms::setData('reports', $reports);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/report/index', 'plugin');

        $this->template->render();
    }

    public function sales(Request $request, $type): void
    {
        $report = [
            'title'     => '',
            'key'       => $type,
            'columns'   => ''
        ];

        $form = form();

        $form->startDefault('<div>');

        $form->endDefault('</div>');

        $form->rangeTimePicker('time', [
            'placeholder' => 'Thời gian',
            'start' => '<div class="field-time">',
            'end' => '</div>'
        ]);

        if($type === 'time')
        {
            $report['title'] = 'Doanh thu theo thời gian';
            $report['columns'] = \Skdepot\ReportColumns::salesTime();

            $form->select('group', [
                'day'  => 'Theo ngày',
                'month' => 'Theo tháng',
                'year' => 'Theo năm'
            ], [], 'day');
        }

        if($type === 'product')
        {
            $report['title'] = 'Doanh thu theo sản phẩm';
            $report['columns'] = \Skdepot\ReportColumns::salesProduct();
        }

        if($type === 'branch')
        {
            $report['title'] = 'Doanh thu theo chi nhánh';
            $report['columns'] = \Skdepot\ReportColumns::salesBranch();
        }

        if($type === 'customer')
        {
            $report['title'] = 'Doanh thu theo khách hàng';
            $report['columns'] = \Skdepot\ReportColumns::salesCustomer();
        }

        Cms::setData('form', $form);

        Cms::setData('report', $report);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/report/sales', 'plugin');

        $this->template->render();
    }

    public function financial(Request $request): void
    {
        $report = [
            'title'     => 'Báo cáo lợi nhuận tổng hợp',
        ];

        $form = form();

        $form->startDefault('<div>');

        $form->endDefault('</div>');

        $form->rangeTimePicker('time', [
            'placeholder' => 'Thời gian',
            'start' => '<div class="field-time">',
            'end' => '</div>'
        ]);

        Cms::setData('form', $form);

        Cms::setData('report', $report);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/report/financial', 'plugin');

        $this->template->render();
    }

    public function inventory(Request $request, $by): void
    {
        $report = [
            'title'     => '',
            'key'       => $by,
            'columns'   => ''
        ];

        $form = form();

        $form->startDefault('<div>');

        $form->endDefault('</div>');

        $form->rangeTimePicker('time', [
            'placeholder' => 'Thời gian',
            'start' => '<div class="field-time">',
            'end' => '</div>'
        ]);

        if($by === 'supplier')
        {
            $report['title'] = 'Danh sách hàng nhập theo NCC';
            $report['columns'] = \Skdepot\ReportColumns::inventorySupplier();
            $report['columnsChild'] = \Skdepot\ReportColumns::inventorySupplierChild();
        }

        if($by === 'product')
        {
            $report['title'] = 'Danh sách hàng nhập theo sản phẩm';
            $report['columns'] = \Skdepot\ReportColumns::inventoryProduct();
        }

        Cms::setData('form', $form);

        Cms::setData('report', $report);

        $this->template->setView(SKDEPOT_NAME.'/views/admin/report/inventory', 'plugin');

        $this->template->render();
    }
}