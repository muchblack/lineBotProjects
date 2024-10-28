<?php

namespace App\Exports;

use App\Models\StoreItem;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class StoreItemsExport implements FromQuery, WithHeadings, WithMapping
{
    use Exportable;

    private string $userId;
    public function __construct(string $userId)
    {
        $this->userId = $userId;
    }
    public function query()
    {
        // TODO: Implement query() method.
        return StoreItem::query()->where('user_id', $this->userId);
    }


    public function headings(): array
    {
        // TODO: Implement headings() method.
        return [
            '#',
            '材料名稱',
            '材料數量',
            '材料單位',
            '材料單價'
        ];
    }

    public function map($row): array
    {
        // TODO: Implement map() method.
        return [
            $row->id,
            $row->item_name,
            $row->item_quantity,
            $row->item_unit,
            $row->dollarPerSet
        ];
    }
}
