<?php

namespace App\Repositories;

use Illuminate\Http\Request;

use Grimzy\LaravelMysqlSpatial\Types\Point;

use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

use App\Http\Resources\Picture;
use App\Http\Resources\Scan as ScanResource;
use App\Missions;
use App\Scans;
use App\ScanPictures;
use App\Services\UploadSpaces;
use App\Store;
use App\Products;
use App\Chains;


class ScanRepository
{
    /** @var UploadSpaces $spaces */
    private $spaces;

    /** @var StoreRepository $storeRepository */
    private $storeRepository;

    /** @var ProductRepository $productRepository */
    private $productRepository;

    /** @var PictureRepository $pictureRepository */
    private $pictureRepository;

    /**
     * ScanRepository constructor.
     * @param UploadSpaces $spaces
     * @param StoreRepository $storeRepository
     * @param ProductRepository $productRepository
     * @param PictureRepository $pictureRepository
     */
    public function __construct(
        UploadSpaces $spaces,
        StoreRepository $storeRepository,
        ProductRepository $productRepository,
        PictureRepository $pictureRepository
    )
    {
        $this->spaces = $spaces;
        $this->storeRepository = $storeRepository;
        $this->productRepository = $productRepository;
        $this->pictureRepository = $pictureRepository;

        $this->spaces->setUploadsPath('uploads/scans');
    }

    /**
     * @param array $data
     */
    public function save($data = [])
    {
        $store = $this->findStore($data);

        if(is_null($store)) {
            $store = $this->createStore($data);
        }

        $mission_id = $data['mission_id'];

        if($mission_id == -1){
            $mission_id = Missions::whereLike('title', 'Misión 0')->first();
            $mission_id = $mission_id->id;
        }

        $scan = new Scans();
        $scan->id_mission = $mission_id;
        $scan->id_scanned_by = $data['author'] ?? null;
        $scan->barcode = $data['barCode'];
        $scan->price = $data['price'];
        $scan->special_price = $data['special_price'] ?? 0;
        $scan->comments = $data['comments'] ?? null;
        $scan->capture_date = $data['capture_date'] ?? new \DateTime();
        $scan->reception_date = new \DateTime();
        $scan->id_store = $store->id ?? null;
        $scan->being_validated = false;

        $product = Products::where('barcode', $data['barCode'])->first();
        $scan->id_product = $product->id ?? null;

        try {
            if ($scan->save()) {
                // Las imagenes se suben en otro endPoint
                // Solo relacionamos el pictureId con el scan
                if (isset($data['picture_id']) && !empty($data['picture_id'])) {
                    $id = $data['picture_id'];
                    $scanPictures = ScanPictures::find($id);

                    if ($scanPictures) {
                        $scanPictures->id_scan = $scan->id;
                        $scanPictures->save();
                    }
                }
            }
        } catch (\Exception $e) {
            error_log('error:' . $e->getMessage());
        }

        return $scan;
    }


    /**
     * @param Request $request
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function listScans(Request $request)
    {
        $query = Scans::query();
        $query->select('scans.id', 'barcode', 'id_mission', 'id_product')
            ->where('is_locked', 0)
            ->where('is_valid', 0)
            ->where('is_rejected', 0);

        $query->when($request->textSearch, function ($q, $textSearch) {
            $q->where('id_mission', $textSearch);
        });

        $query->when($request->withProduct, function ($q, $withProduct) {
            if ($withProduct === 'yes') {
                $q->has('product');
            } elseif ($withProduct === 'no') {
                $q->doesnthave('product');
            }
        });

        $query->join('users', 'users.id', 'scans.id_scanned_by')
            ->whereNull('users.deleted_at');

        $query->orderBy('scans.created_at', 'DESC');

        return $query;
    }

    /**
     * @param Request $request
     * @param Scans $scan
     * @return Scans
     */
    public function findScan(Request $request, Scans $scan)
    {
        if ($request->has('previous_id') && $request->input('previous_id') != 0) {
            $previousScan = Scans::find($request->input('previous_id'));
            $previousScan->is_locked = 0;
            $previousScan->save();
        }
//
//        $scan->is_locked = 1;
//        $scan->save();

        return $scan;
    }

    /**
     * @param Scans $scan
     * @param $data
     * @param PictureRepository $pictureRepository
     * @return bool
     */
    public function validate(Scans $scan, $data, $validator)
    {
        $scanData = $data['scan'];

        // El scan ha sido rechazado
        if ($scan->is_rejected) {
            return null;
        }

        // Validate capture and update data
        $scan->is_valid = 1;
        $scan->barcode = $scanData['barcode'];
        $scan->price = $scanData['price'];
        $scan->previous_price = $scan->price;

        if (isset($scanData['special_price'])) {
            $scan->special_price = $scanData['special_price'];
        }

        if (isset($scanData['comments'])) {
            $scan->comments = $scanData['comments'];
        }

        //Update Pictures
        $scanPictures = ScanPictures::where('id_scan', $scan->id)->first();
//        dd([$scanData, $scanPictures]);
        if (is_null($scanPictures)) {
            $scanData['id_scan'] = $scan->id;
            $scanPictures = new ScanPictures();
            $this->pictureRepository->savePictures($scanPictures, $scanData);
        } else {
            $scanData['barCode'] = $scan->barcode;
            $this->updatePictures($scanPictures, $scanData);
        }

        // Update store info
        if (!empty($data['store'])) {
            $datStore = $data['store'];
            $store = Store::where('id', $datStore['id'])->first();
            $store->name = $datStore['name'];
            $store->save();
            $scan->id_store = $store->id;
        }

        $productData = $data['product'] ?? null;
        if (!empty($productData) && empty($scan->id_product)) {
            $product = $scan->product()->first();

            if (is_null($product)) {
                $product = $this->productRepository->create($scanData['barcode'], $productData);
            }

            if (empty($product->picture_path)) {
                $product->picture_path = $scanPictures->product_picture;
                $product->save();
            }

            $scan->id_product = $product->id;
        }

        if (is_null($scan->id_product)) {
            $product = Products::where('barcode', trim($scanData['barcode']))->first();
            $scan->id_product = $product->id;

            if (is_null($scan->id_product)) {
                return null;
            }
        }

        $scan->validation_date = new \DateTime();
        $scan->id_reviewed_by = $validator;

        return $scan->save();
    }

    /**
     * @param ScanPictures $pictures
     * @param $dataPictures
     */
    public function updatePictures(ScanPictures $pictures, $dataPictures)
    {
        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName=lampt3bdiag;AccountKey=8wHByxaXQ7KVu46+sDd3RCKQVNCEAb6zTi3jMCwDF+thGKUzUe4M5P9vQgFa3XOfzj4Ffr+DVDZpYtytnmkOFw==');
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (isset($dataPictures['product_picture'])) {
            $oldPicture = $pictures->product_picture;
            $Account->deleteBlob($containerName, $oldPicture);
            $file = $dataPictures['product_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . '/scans/products', $BlobName, $content, $options);
            $pictures->product_picture = 'scans/products/' . $BlobName;
        }

        if (isset($dataPictures['shelf_picture'])) {
            $oldPicture = $pictures->shelf_picture;
            $Account->deleteBlob($containerName, $oldPicture);
            $file = $dataPictures['shelf_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . '/scans/shelves', $BlobName, $content, $options);
            $pictures->shelf_picture = 'scans/shelves/' . $BlobName;
        }
        if (isset($dataPictures['promo_picture'])) {
            $oldPicture = $pictures->promo_picture;
            $Account->deleteBlob($containerName, $oldPicture);
            $file = $dataPictures['promo_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . '/scans/additional', $BlobName, $content, $options);
            $pictures->promo_picture = 'scans/additional/' . $BlobName;
        }

        $pictures->save();
    }

    private function createStore($data): ?Store
    {
        $place = null;
        if (isset($data['place']) && !empty($data['place'])) {
            $dataPlace = $data['place'];
            $point = new Point($dataPlace['lat'], $dataPlace['long']);

            $originalName = $dataPlace['name'];

            $storeName = Chains::select('name')->whereRaw('"' . $originalName . '" like CONCAT(alias,"%")')->first();
            $Nostore = Chains::select('name')->where('alias', 'like', "%No store%")->first();

            if ($storeName) {
                $storeName = $storeName->name;
            } else {
                $storeName = $Nostore->name;
            }

            $place = [
                'name' => $storeName,
                'address' => $dataPlace['address'],
                'location' => $point
            ];
        } elseif (isset($data['fill_in_place'])) {
            $point = new Point($data['lat'], $data['lng']);
            $place = [
                'name' => $data['fill_in_place'],
                'address' => $data['place']['address'] ?? 'N/D',
                'location' => $point
            ];
        }

        if (!is_null($place)) {
            return Store::create($place);
        }

        return null;
    }

    public function findStore($data)
    {
        $latitude = null;
        $longitude = null;
        if (isset($data['place']) && !empty($data['place'])) {
            $dataPlace = $data['place'];
            $latitude = $dataPlace['lat'];
            $longitude = $dataPlace['long'];
        } elseif (isset($data['fill_in_place'])) {
            $latitude = $data['lat'];
            $longitude = $data['lng'];
        }
//        POINT(-99.635451 20.5344258)
        if (!is_null($latitude) && !is_null($longitude)) {
            return Store::Location($latitude, $longitude)->first();
        }

        return null;
    }

    public function updateScan(Scans $scan, array $data, $id_product)
    {
        if (isset($data['scan'])) {
            $scanData = $data['scan'];

            if (isset($scanData['barcode'])) {
                $scan->barcode = $scanData['barcode'];
                $scan->id_product = $id_product;
            }

            if (isset($scanData['price'])) {
                $scan->price = $scanData['price'];
            }

            if (isset($scanData['special_price'])) {
                $scan->special_price = $scanData['special_price'];
            }

            if (isset($scanData['id_chain'])) {
                $scan->id_store = $scanData['id_chain'];
            }

            $scan->save();
        }

        if (isset($data['store'])) {
            $storeData = $data['store'];
            $storeData['address'] = $storeData['branch'] ?? null;

            $store = $scan->store()->first();
            $this->storeRepository->updateStore($store, $storeData);
        }

        if (isset($data['brand'])) {
            $brand = $scan->product()->first()->brand()->first();

            $brandData = $data['brand'];
            if (isset($brandData['name']) && isset($brand)) {
                $brand->name = strip_tags($brandData['name']);
                $brand->save();
            }
        }

        return $scan;
    }
}
