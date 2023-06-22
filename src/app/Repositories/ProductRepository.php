<?php

namespace App\Repositories;

use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

use App\Products;
use App\Scans;
use App\Services\UploadSpaces;
use Illuminate\Http\Request;


class ProductRepository
{
    /** @var UploadSpaces */
    //private $spaces;

    /**
     * ProductRepository constructor.
     * @param UploadSpaces $spaces
     */
    public function __construct(UploadSpaces $spaces)
    {
        $this->spaces = $spaces;
        $this->spaces->setUploadsPath('uploads/products');
    }


    public function Filters($barcodes, Request $request)
    {
        $barcodes_array = explode(',', $barcodes);
        $products = Products::whereIn('barcode', $barcodes_array);
        $products->orderBy('name', 'ASC');
        
        return $products;
    }
    /**
     * @param $barcode
     * @param array $data
     * @return mixed
     */
    public function create($barcode, $data)
    {
        $price = $data['price'] ?? 0;

        $product = Products::create([
            'name' => $data['name'],
            'barcode' => $barcode,
            'id_brand' => $data['brand'],
            'price' => $price,
            'min_price' => $price * 0.9,
            'max_price' => $price * 1.1,
            'unit_quantity' => $data['quantity'] ?? null,
            'id_unit' => $data['unit'] ?? null,
            'id_group' => $data['group'],
            'id_line' => $data['line'],
            'type' => $data['type'],
        ]);

        if (isset($product->id) && isset($data['picture_path'])) {
            $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName=lampt3bdiag;AccountKey=8wHByxaXQ7KVu46+sDd3RCKQVNCEAb6zTi3jMCwDF+thGKUzUe4M5P9vQgFa3XOfzj4Ffr+DVDZpYtytnmkOFw==');
            $fileDate = date('d_m_y');
            $containerName = 'pricecheckv2';
            $options = new CreateBlobOptions();
            $options->setBlobContentType("image/jpeg");

            $file = $data['picture_path'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . '/scans/products', $BlobName, $content, $options);
            $product->picture_path = 'scans/products/' . $BlobName;
            $product->save();
        }

        return $product;
    }


    public function findProductByBarcode($barcode)
    {
        $product = Products::where('barcode', $barcode);
        return $product->first();
    }

    public function update($product, $data, $barcode)
    {
        if (isset($data['name'])) {
            $product->name = strip_tags($data['name']);
            $product->save();
        }

        if (isset($data['barcode'])) {
            $scans = Scans::where('barcode', $barcode)->where('is_valid', 1)->get();
            
            if ($scans) {
                foreach ($scans as $scan) {
                    $scan->barcode = $data['barcode'];
                    $scan->save();
                }
            }

            $product->barcode = strip_tags($data['barcode']);
            $product->save();
        }

        if (isset($data['type'])) {
            $product->type = $data['type'];
            $product->save();
        }

        if (isset($data['quantity'])) {
            $product->unit_quantity = $data['quantity'];
            $product->save();
        }

        if (isset($data['id_brand'])) {
            $product->id_brand = $data['id_brand'];
            $product->save();
        }

        if (isset($data['id_unit'])) {
            $product->id_unit = $data['id_unit'];
            $product->save();
        }

        if (isset($data['id_group'])) {
            $product->id_group = $data['id_group'];
            $product->save();
        }

        if (isset($data['id_line'])) {
            $product->id_line = $data['id_line'];
            $product->save();
        }

        if (isset($data['created_at'])) {
            $product->created_at = $data['created_at'];
            $product->save();
        }
    }

    public function updatePhoto(Products $product, array $data): bool
    {
        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName=lampt3bdiag;AccountKey=8wHByxaXQ7KVu46+sDd3RCKQVNCEAb6zTi3jMCwDF+thGKUzUe4M5P9vQgFa3XOfzj4Ffr+DVDZpYtytnmkOFw==');
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");
        $status = false;

        if (isset($product->id) && isset($data['picture_path'])) {
            $oldPicture = $product->picture_path;

            if (!empty($oldPicture)) {
                $Account->deleteBlob($containerName, $oldPicture);
            }

            $file = $data['picture_path'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . '/scans/products', $BlobName, $content, $options);
            $product->picture_path = 'scans/products/' . $BlobName;
            $product->save();
            $status = true;
        }

        return $status;
    }

    public function updateProductById($product, $data, $id)
    {
        if (isset($data['name'])) {
            $product->name = strip_tags($data['name']);
            $product->save();
        }

        if (isset($data['barcode'])) {
            $product->barcode = strip_tags($data['barcode']);
            $product->save();
        }

        if (isset($data['type'])) {
            $product->type = $data['type'];
            $product->save();
        }

        if (isset($data['quantity'])) {
            $product->unit_quantity = $data['quantity'];
            $product->save();
        }

        if (isset($data['id_brand'])) {
            $product->id_brand = $data['id_brand'];
            $product->save();
        }

        if (isset($data['id_unit'])) {
            $product->id_unit = $data['id_unit'];
            $product->save();
        }

        if (isset($data['id_group'])) {
            $product->id_group = $data['id_group'];
            $product->save();
        }

        if (isset($data['id_line'])) {
            $product->id_line = $data['id_line'];
            $product->save();
        }

        if (isset($data['created_at'])) {
            $product->created_at = $data['created_at'];
            $product->save();
        }
    }
}
