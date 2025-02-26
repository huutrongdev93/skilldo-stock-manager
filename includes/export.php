<?php
namespace Stock;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export
{
    protected array $header = [];

    protected mixed $data = [];

    protected array $characters = [
        'A','B','C','D','E','F','G','H','I','J','K','L','M','N','O','P','Q','R','S','T','U','V','W','X','Y','Z',
        'AA','AB','AC','AD','AE','AF','AG','AH','AI','AJ','AK','AL','AM','AN','AO','AP','AQ','AR','AS','AT','AU','AV','AW','AX','AY','AZ',
        'BA','BB','BC','BD','BE','BF','BG','BH','BI','BJ','BK','BL','BM','BN','BO','BP','BQ','BR','BS','BT','BU','BV','BW','BX','BY','BZ'
    ];

    protected Spreadsheet $spreadsheet;

    protected array $styleHeader = [];

    protected array $styleColumn = [];

    protected string $sheetTitle = '';

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();

        $this->styleHeader = [
            'font' => [ 'bold' => true, 'size' => 12],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
                'vertical'   => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000'],
                ],
            ],
        ];

        $this->styleColumn = [
            'alignment' => [
                'vertical' => \PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_CENTER,
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'argb' => 'E6F7FF',
                ],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['argb' => '000'],
                ],
            ],
        ];
    }

    public function setTitle(string $title): static
    {
        $this->sheetTitle = $title;

        return $this;
    }

    public function data($data): static
    {
        $this->data = $data;
        return $this;
    }

    public function header($key, $label, $value): static
    {
        $this->header[$key] = [
            'label' => $label,
            'value' => $value
        ];

        return $this;
    }

    protected function createRows(): array
    {
        $rows = [];

        foreach ($this->data as $key => $item) {

            $i = 0;

            foreach ($this->header as $header)
            {
                $rows[] = [
                    'cell'  => $this->characters[$i] .($key+2),
                    'value' => $header['value']($item),
                    'style' => $this->styleColumn
                ];

                $i++;
            }
        }

        return $rows;
    }

    public function export($path, $filename): string
    {
        $sheet = $this->spreadsheet->setActiveSheetIndex(0);

        $sheet->setTitle($this->sheetTitle);

        $sheet->getDefaultRowDimension()->setRowHeight(20);

        $sheet->getDefaultRowDimension()->setRowHeight(20);

        $key = 0;

        foreach ($this->header as $keyHeader => $item)
        {
            $item['cell'] =  $this->characters[$key].'1';

            if(!empty($item['width']))
            {
                $sheet->getColumnDimension($this->characters[$key])->setWidth($item['width']);
            }
            else
            {
                $sheet->getColumnDimension($this->characters[$key])->setAutoSize(true);
            }

            $this->header[$keyHeader] = $item;

            $key++;
        }

        foreach ($this->header as $keyHeader => $item)
        {
            $sheet->setCellValue($item['cell'], $item['label']);

            $style = (isset($item['style'])) ? $item['style'] : $this->styleHeader;

            if(!empty($style))
            {
                $sheet->getStyle($item['cell'])->applyFromArray($style);
            }
        }

        $rows = $this->createRows();

        foreach ($rows as $row)
        {
            $sheet->setCellValue($row['cell'], $row['value']);
            $sheet->getPageMargins()->setTop(2);
            $sheet->getPageMargins()->setRight(2);
            $sheet->getPageMargins()->setLeft(2);
            $sheet->getPageMargins()->setBottom(2);
            $sheet->getStyle($row['cell'])->applyFromArray($row['style']);
        }

        $this->spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($this->spreadsheet);

        $filePathData = 'views/plugins/'.STOCK_NAME.'/'.$path;

        $writer->save($filePathData.$filename);

        return \Url::base().$filePathData.$filename;
    }

}