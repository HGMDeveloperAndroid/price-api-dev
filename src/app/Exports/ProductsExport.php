<?php

namespace App\Exports;

use App\Products;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use App\Scans;
use Illuminate\Database\Eloquent\Collection;

class ProductsExport implements FromCollection, WithHeadings
{
    protected $request;

    public function __construct($request)
    {
        $this->request = $request;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $productFilters = $this->productFilters($this->request);
        $itemsProduct = $this->productsData($productFilters->get());
        return collect($itemsProduct);
    }

    public function headings(): array
    {
        return [
            'id',
            'Foto de producto',
            'Nombre',
            'Código',
            'Fecha de alta',
            'Fecha de modificación',
            'Gramaje',
            'Unidad',
            'Marca',
            'Tipo',
            'Grupo',
            'Línea',
            'Precio más alto',
            'Precio más bajo',
            'Precio más bajo con promoción',
            'Último precio de capturado',
            'Fecha del último precio capturado'
        ];
    }

    public function productFilters(Request $request)
    {
        $query = Products::query();

        if ($request->filled('items')) {
            $query->whereIn('id', $request->items);

            return $query;
        }

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->where('barcode', 'like', "%{$textSearch}%")->whereLike('name', $textSearch);
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('created_at', '>=', $request->from)
                ->whereDate('created_at', '<=', $request->to);
        }

        if ($request->filled(['modifyFrom', 'modifyTo'])) {
            $query->whereDate('updated_at', '>=', $request->modifyFrom)
                ->whereDate('updated_at', '<=', $request->modifyTo);
        }

        $query->when($request->type, function ($q, $type) {
            $q->where('type', $type);
        });

        $query->when($request->brand, function ($qq, $brand) {
            $qq->whereIn('id_brand', $brand);
        });

        $query->when($request->group, function ($qq, $group) {
            $qq->whereIn('id_group', $group);
        });

        $query->when($request->line, function ($qq, $line) {
            $qq->whereIn('id_line', $line);
        });

        $query->when($request->unit, function ($qq, $unit) {
            $qq->whereIn('id_unit', $unit);
        });

        $query->orderBy('name', 'ASC');
        return $query;
    }

    public function productsData(Collection $products)
    {
        $result = [];
        foreach ($products as $product) {
            $brand = $product->brand()->first();
            $unit = $product->unit()->first();
            $group = $product->group()->first();
            $line = $product->line()->first();

            $result[] = [
                'id' => $product->id,
                'photo' => $product->picture_path ?? '-',
                'product' => $product->name ?? '',
                'barcode' => $product->barcode,
                'created_at' => $product->created_at->format('d/M/Y H:i:s'),
                'updated_at' => !$product->updated_at ?? $product->updated_at->format('d/M/Y H:i:s'),
                'grammage_quantity' => $product->unit_quantity ?? 0,
                'unit' => $unit->name ?? '',
                'brand' => $brand->name ?? '',
                'type' => $product->type ?? '',
                'group' => $group->name ?? '',
                'line' => $line->name ?? '',
                'highest_price' => $product->getMaxPrice($product->barcode),
                'lower_price' => $product->getMinPrice($product->barcode),
                'promotion_lower_price' => $product->getMinPriceWithPromotion($product->barcode),
                'last_price' => $product->getLastPrice($product->barcode),
                'date_last_price' => date('d/M/Y H:i:s', strtotime($product->getDateLastPrice($product->barcode)))
            ];
        }

        return $result;
    }
}
