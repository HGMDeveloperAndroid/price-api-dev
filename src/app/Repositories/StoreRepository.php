<?php

namespace App\Repositories;

use App\Http\Resources\Location;
use App\Store;
use App\Chains;
use Grimzy\LaravelMysqlSpatial\Types\Point;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class StoreRepository
{

    public function Filters(Request $request)
    {
        $stores = Store::query();
        $stores->select('id', 'name', 'address', 'location', 'phone', 'created_at', 'updated_at');
        $stores->when($request->textSearch, function ($q, $textSearch) {
            $q->whereLike('name', $textSearch);
        });

        $stores->orderBy('name', 'ASC');
        return $stores;
    }

    public function FiltersChains(Request $request)
    {
        $Chains = Chains::query();
        $Chains->select('id', 'name', 'alias', 'description', 'blocked_up', 'created_at', 'updated_at');
        $Chains->when($request->textSearch, function ($q, $textSearch) {
            $q->whereLike('name', $textSearch);
        });

        $Chains->orderBy('name', 'ASC');
        return $Chains;
    }

    public function getOrSaveStore($data)
    {
        $location = $this->buildLocationData($data);
        $dataMessage = json_encode($data);
        error_log('*** Data receive:' . $dataMessage);
        $idStore = $data['id'] ?? 0;
        $store = $this->findLocation($location['latitude'], $location['longitude'], $idStore)->first();

        if (is_null($store)) {
            $store = $this->save($location);
        } 

        return $store;
    }

    public function findLocation($latitude, $longitude, $id)
    {
        $location = null;
        /** @var Store $location */
        if ($id) {
            $location = Store::where('id', $id);
        } else {
            $location = Store::Location($latitude, $longitude);
        }

        return $location;
    }

    public function save($data)
    {
        $store = null;
        try {
            $point = new Point($data['latitude'], $data['longitude']);
//            dd($point);
            $store = Store::create(
                [
                    'name' => $data['name'],
                    'address' => $data['address'] ?? null,
                    'location' => $point,
//                    'phone' => $data['phone'] ?? null,
//                    'id_chain' => 1
                ]
            );
        } catch (\Exception $e) {
            error_log($e->getMessage());
        }

        return new Location($store);
    }

    /**
     * @param Store $store
     * @param $data
     * @return Store
     */
    public function updateStore(Store $store, $data): Store
    {

        if (isset($data['name'])) {
            $store->name = strip_tags($data['name']);
        }

        if (isset($data['address'])) {
            $store->address = strip_tags($data['address']);
        }

        if (isset($data['latitude']) && isset($data['longitude'])) {
            $point = new Point($data['latitude'], $data['longitude']);
            $store->location = $point;
        }

        $store->save();
        return $store;
    }

    private function buildLocationData($data)
    {
        $location = [];

        if (isset($data['lat']) && isset($data['lng'])) {
            $location = [
                'latitude' => $data['lat'],
                'longitude' => $data['lng'],
                'name' => $data['name'] ?? 'N/A',
                'address' => $data['address'] ?? null
            ];
        } elseif (isset($data['place']) && !empty($data['place'])) {
            $place = json_decode($data['place'], true);
            $location = [
                'latitude' => $place['lat'],
                'longitude' => $place['lng'],
                'name' => $place['name'],
                'address' => $place['address'] ?? null
            ];
        }

        return $location;
    }
}
