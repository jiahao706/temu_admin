<?php

namespace App\Export;

use Maatwebsite\Excel\Concerns\FromArray;

class SkuCostExport implements FromArray
{
    private $data;

    public function __construct()
    {
        $this->data = [[
            "sku货号",
            "进货价"
        ],[
            "11153401XZ-1",
            "9.98"
        ]];
    }
    public function array(): array
    {
        // TODO: Implement array() method.
        return $this->data;
    }

}
