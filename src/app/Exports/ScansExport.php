<?php

namespace App\Exports;

use App\Products;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Http\Request;
use App\Scans;
use Illuminate\Database\Eloquent\Collection;

class ScansExport implements FromCollection, WithHeadings
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
        $scansView = $this->ScansView($this->request);
        $itemsScan = $this->scansData($scansView->get());
        return collect($itemsScan);
    }

    public function headings(): array
    {
        return [
            'Captura',  
            'Código de Barras', 
            'Cadena Comercial', 
            'Dirección',    
            'Capturista',   
            'Validador',    
            'Estado',   
            'Comentarios',  
            'Producto', 
            'Cantidad', 
            'Unidad',   
            'Precio Unitario',  
            'Marca',    
            'Tipo', 
            'Grupo',    
            'Línea',    
            'Fecha de Alta Producto',
            'Precio de Alta',
            'Precio Min',   
            'Precio Max',   
            'Fecha de Captura', 
            'Precio de Captura',    
            'Promoción'
        ];
    }

    public function ScansView(Request $request)
    {
        $query = Scans::query();

        if ($request->filled('items')) {
            $query->whereIn('scans.id', $request->items);

            return $query;
        }

        $query->when($request->textSearch, function ($q, $textSearch) {

            $q->whereHas('product', function ($qq) use ($textSearch) {
                $qq->where('scans.id', $textSearch);
                $qq->orWhere('scans.barcode', 'like', "%{$textSearch}%")
                    ->orWhere('products.name', 'like', "%{$textSearch}%");
            });
        });

        if ($request->filled(['from', 'to'])) {
            $query->whereDate('capture_date', '>=', $request->from)
                ->whereDate('capture_date', '<=', $request->to);
        }

        $query->when($request->withPhoto, function ($q, $withPhoto) {
            if ($withPhoto === 'yes') {
                $q->has('pictures');
            } elseif ($withPhoto === 'no') {
                $q->doesnthave('pictures');
            }
        });

        $query->when($request->is_promotion, function ($q, $isPromotion) {
            if ($isPromotion === 'yes') {
                $q->where('special_price', 1);
            }
            if ($isPromotion === 'no') {
                $q->where('special_price', 0);
            }
        });

        $type = $request->type;
        $query->whereHas('product', function ($q) use ($type) {
            if ($type === 'MC') {
                $q->where('type', 'MC');
            }
            if ($type === 'MP') {
                $q->where('type', 'MP');
            }
        });

        $query->when($request->status, function ($q, $status) {
            if ($status === 'valid') {
                $q->where('is_rejected', 0)->where('is_valid', 1);
            } elseif ($status === 'rejected') {
                $q->where('is_rejected', 1)->where('is_valid', 0);
            } elseif ($status === 'pending') {
                $q->where('is_rejected', 0)->where('is_valid', 0);
            }
        });

        if ($request->input('scanned_by') && !empty($request->input('scanned_by'))) {
            $values = $request->input('scanned_by');
            $query->where('id_scanned_by', $values);
        }

        if ($request->input('reviewed') && !empty($request->input('reviewed'))) {
            $value = $request->input('reviewed');
            $query->where('id_reviewed_by', $value);
        }

        if ($request->input('chain') && !empty($request->input('chain'))) {
            $values = $request->input('chain');
            $query->whereIn('id_store', $values);
        }

        if ($request->input('branch') && !empty($request->input('branch'))) {
            $values = $request->input('branch');
            $query->whereHas('store', function ($qq) use ($values) {
                $qq->whereLike('store.address', $values);
            });
        }
      
        if ($request->filled('brand')) {
            $brand = $request->brand;
            $query->whereHas('product', function ($q) use ($brand) {
                $q->whereIn('id_brand', $brand);
            });
        }

        if ($request->filled('group')) {
            $group = $request->group;
            $query->whereHas('product', function ($q) use ($group) {
                $q->whereIn('id_group', $group);
            });
        }

        if ($request->filled('line')) {
            $line = $request->line;
            $query->whereHas('product', function ($q) use ($line) {
                $q->whereIn('id_line', $line);
            });
        }

        if ($request->filled('unit')) {
            $unit = $request->unit;
            $query->whereHas('product', function ($q) use ($unit) {
                $q->whereIn('id_unit', $unit);
            });
        }

        $query->orderBy('id', 'DESC');
        return $query;
    }

    /**
     * @param Collection $scans
     */
    public function scansData(Collection $scans)
    {
        $result = [];
        foreach ($scans as $scan) {
            $product = $scan->product()->first();
            $brand = null !== $product ? $product->brand()->first() : null;
            $store = $scan->store()->first();
            $scannedBy = $scan->author()->first();
            $reviewedBy = $scan->reviewed()->first();
            $pictures = $scan->pictures()->first();

            $unitPrice = 0.0;
            $unit = null;
            $group = null;
            $line = null;
            if (isset($product)) {
                $unitPrice = $product->unit_quantity != 0 ? $scan->price / $product->unit_quantity : 0;
                $unit = $product->unit()->first();
                $group = $product->group()->first();
                $line = $product->line()->first();
            }

            $author = '';
            if (isset($scannedBy)) {
                $author = $scannedBy->first_name . ' ' . $scannedBy->last_name . ' ' . $scannedBy->mother_last_name;
            }

            $reviewed = '';
            if (isset($reviewedBy)) {
                $reviewed = $reviewedBy->first_name . ' ' . $reviewedBy->last_name . ' ' . $reviewedBy->mother_last_name;
            }

            $product_creation_date = Products::select('created_at')->where('barcode', $scan->barcode)->first();

            $result[] = [
                'scan' => $scan->id,
                'barcode' => $scan->barcode,
                'chain' => $store->name ?? '',
                'branch' => $store->address ?? '',
                'scanned_by' => $author,
                'reviewed' => $reviewed,
                'status' => $scan->getStatus(),
                'comments' => $scan->comments,
                'product' => $product->name ?? '',
                'grammage_quantity' => $product->unit_quantity ?? 0,
                'unit' => $unit->name ?? '',
                'unit_price' => $unitPrice,
                'brand' => $brand->name ?? '',
                'type' => $product->type ?? '',
                'group' => $group->name ?? '',
                'line' => $line->name ?? '',
                //'created_date' => $scan->created_at->format('d/M/Y'),
                'product_created_date' => $product_creation_date->created_at->format('d/M/Y H:i:s') ?? '',
                'created_price' => $scan->price,
                'lower_price' => $scan->where('barcode', $scan->barcode)->where('is_valid', 1)->where('is_rejected', 0)->min('price'),
                'highest_price' => $scan->where('barcode', $scan->barcode)->where('is_valid', 1)->where('is_rejected', 0)->max('price'),
                'capture_date' => date('d/M/Y H:i:s', strtotime($scan->capture_date)),
                'capture_price' => $scan->price,
                'promotion' => $scan->special_price ? 'Si' : 'No'
            ];
        }

        return $result;
    }
}
