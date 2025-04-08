<?php
namespace Stock;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class Export
{
    protected array $sheets = [];

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

        $this->sheets['default'] = new ExportSheet('default', 'Default');
    }

    public function addSheet($key, string $title): ExportSheet
    {
        if(isset($this->sheets['default']))
        {
            unset($this->sheets['default']);
        }

        $this->sheets[$key] = new ExportSheet($key, $title);

        return $this->sheets[$key];
    }

    public function getSheet($key): ExportSheet
    {
        return $this->sheets[$key];
    }

    public function setTitle(string $title, $key = 'default'): static
    {
        $this->getSheet($key)->setTitle($title);

        return $this;
    }

    public function data($data, $key = 'default'): static
    {
        $this->getSheet($key)->setData($data);

        return $this;
    }

    public function header($key, $label, $value, $sheetKey = 'default'): static
    {
        $this->getSheet($sheetKey)->setHeader($key, $label, $value);

        return $this;
    }

    protected function createRows(ExportSheet $sheet): array
    {
        $rows = [];

        foreach ($sheet->getData() as $key => $item) {

            $i = 0;

            foreach ($sheet->getHeader() as $header)
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
        // Xóa sheet mặc định
        $this->spreadsheet->removeSheetByIndex(0);

        // Tạo các sheet mới
        foreach ($this->sheets as $index => $sheet) {

            // Tạo sheet mới
            $worksheet = $this->spreadsheet->createSheet();

            $worksheet->setTitle($sheet->getTitle());

            // Thiết lập chiều cao mặc định cho row
            $worksheet->getDefaultRowDimension()->setRowHeight(20);

            // Thiết lập header
            $key = 0;

            foreach ($sheet->getHeader() as $item)
            {
                $item['cell'] =  $this->characters[$key].'1';

                if(!empty($item['width']))
                {
                    $worksheet->getColumnDimension($this->characters[$key])->setWidth($item['width']);
                }
                else
                {
                    $worksheet->getColumnDimension($this->characters[$key])->setAutoSize(true);
                }

                $worksheet->setCellValue($item['cell'], $item['label']);

                $style = $this->styleHeader;

                $worksheet->getStyle($item['cell'])->applyFromArray($style);

                $key++;
            }

            // Tạo và điền dữ liệu cho các row
            $rows = $this->createRows($sheet);

            foreach ($rows as $row) {
                $worksheet->setCellValue($row['cell'], $row['value']);
                $worksheet->getPageMargins()->setTop(2);
                $worksheet->getPageMargins()->setRight(2);
                $worksheet->getPageMargins()->setLeft(2);
                $worksheet->getPageMargins()->setBottom(2);
                $worksheet->getStyle($row['cell'])->applyFromArray($row['style']);
            }
        }

        $this->spreadsheet->setActiveSheetIndex(0);

        $writer = new Xlsx($this->spreadsheet);

        $filePathData = 'views/plugins/'.STOCK_NAME.'/'.$path;

        $writer->save($filePathData.$filename);

        return \Url::base().$filePathData.$filename;
    }
}

class ExportSheet
{
    protected string $id;

    protected string $title;

    protected array $data;

    protected array $header;

    public function __construct(string $id, string $title)
    {
        $this->id = $id;

        $this->title = $title;

        $this->data = [];

        $this->header = [];
    }

    public function setId(string $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function setHeader($key, $label, $value): self
    {
        $this->header[$key] = [
            'label' => $label,
            'value' => $value,
        ];

        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getHeader(): array
    {
        return $this->header;
    }
}