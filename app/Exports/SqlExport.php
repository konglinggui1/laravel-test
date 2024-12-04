<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class SqlExport implements FromCollection , WithHeadings
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection(): \Illuminate\Support\Collection
    {
        return collect($this->data);
    }

    /**
     * @return array
     */
    public function headings(): array
    {
        if (empty($this->data)) {
            return []; // 如果没有数据，避免标题为空
        }

        // 提取数据库查询结果中的列名作为Excel表头
        return array_keys((array) $this->data[0]);
    }
}
