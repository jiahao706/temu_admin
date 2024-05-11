<?php

namespace App\Export;


use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class SkuInfoExport implements FromCollection,WithHeadings,WithMapping,WithColumnWidths,WithStyles,ShouldAutoSize,WithEvents
{
    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function collection()
    {
        // TODO: Implement collection() method.
        return collect($this->data);
    }

    public function headings():array
    {
        return [
            "商品图片",
            "商品标题",
            "类目",
            "skc",
            "spu",
            "skc货号",
            "sku名称",
            "sku货号",
            "销售价格",
            "成本价格",
            "仓内可用库存",
            "仓内暂时不可用库存",
            "已发货库存",
            "已下单待发货库存",
            "待审核备货库存",
            "近30日销量",
            "不合理库存",
            "不合理库存总成本",
        ];
    }

    public function map($row): array
    {
        // TODO: Implement map() method.
        return [
            "",
            $row["title"],
            $row["category"],
            $row["skc"],
            $row["spu"],
            $row["sku_article_number"],
            $row["sku_name"],
            $row["sku_ext_code"],
            $row["supplier_price"],
            $row["cost_price"],
            $row["ware_house_inventory_num"],
            $row["unavailable_warehouse_inventory_num"],
            $row["wait_receive_num"],
            $row["wait_delivery_inventory_num"],
            $row["wait_approve_inventory_num"],
            $row["last_thirty_days_sale_volume"],
            $row["unreasonable_inventory"],
            $row["unreasonable_inventory_total_cost_price"],
        ];
    }

    public function columnWidths(): array
    {
        // TODO: Implement columnWidths() method.

        return [
            "A"=>30,
            "B"=>80,
            "C"=>20,
            "D"=>20,
            "E"=>20,
            "F"=>20,
            "G"=>20,
            "H"=>20,
            "I"=>20,
            "J"=>20,
            "K"=>20,
            "L"=>40,
            "M"=>20,
            "N"=>20,
            "O"=>20,
            "P"=>20,
            "Q"=>20,
            "R"=>20,
        ];
    }

    public function styles(Worksheet $sheet)
    {
        // TODO: Implement styles() method.
        return [
          1=>[
            "font"=>[
                "bold"=>true,
                "size"=>18,
            ],
          ]
        ];
    }

    public function registerEvents(): array
    {
        // TODO: Implement registerEvents() method.

        return [
            AfterSheet::class=>function(AfterSheet $event){
                $cellRange = 'A1:R1'; // 根据需要调整列范围,设置背景色
                $event->sheet->getDelegate()->getStyle($cellRange)->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFF0000'); // 设置为红色
                $data = $this->data;
                $rowIndex = 2;
                foreach ($data as $row){
                    if(file_exists($row["img"])){
                        $drawing = new Drawing();
                        $drawing->setPath($row["img"]);
                        $drawing->setHeight(100);
                        $drawing->setCoordinates("A".$rowIndex);
                        $drawing->setWorksheet($event->sheet->getDelegate());
                    }
                    $event->sheet->getDelegate()->getRowDimension($rowIndex)->setRowHeight(100);
                    $rowIndex++;
                }
            }
        ];
    }
}
