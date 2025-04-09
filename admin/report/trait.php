<?php
namespace Skdepot;

trait ReportTrait
{
    static function searchTimeRange($time): array
    {
        $timeStart = 0;

        $timeEnd = 0;

        if(!empty($time))
        {
            $time = explode(' - ', $time);

            if(have_posts($time) && count($time) == 2)
            {
                $timeStart = strtotime(str_replace('/', '-', $time[0]));
                $timeEnd   = strtotime(str_replace('/', '-', $time[1]));
            }
        }

        if(empty($timeStart) ||  empty($timeEnd))
        {
            $timeStart = strtotime('monday this week', time());
            $timeEnd   = strtotime('sunday this week', time());
        }

        return [
            'dateStart' => date('Y-m-d', $timeStart).' 00:00:00',
            'dateEnd' => date('Y-m-d', $timeEnd).' 23:59:59',
            'timeStart' => $timeStart,
            'timeEnd'   => $timeEnd,
        ];
    }

    static function exportWithColumns($columns, $args): string
    {
        $export = new \Skdepot\Export();

        $sheet = $export->getSheet('default');

        foreach ($columns as $key => $label)
        {
            $sheet->setHeader($key, $label['label'], function($item) use ($key) {
                return $item->$key ?? '';
            });
        }

        $sheet->setTitle($args['sheetName']);

        $sheet->setData($args['columnsData']);

        return $export->export('assets/export/report/', $args['filename'].'.xlsx');
    }
}