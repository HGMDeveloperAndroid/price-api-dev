<?php

namespace App\Repositories;

use MicrosoftAzure\Storage\Blob\Models\CreateBlobOptions;
use MicrosoftAzure\Storage\Common\ServicesBuilder;

use App\ScanPictures;
use App\User;
use App\Services\UploadSpaces;
use App\Settings;
use App\Chains;
use App\Theme;
use Illuminate\Support\Facades\Auth;


class PictureRepository
{
    /** @var UploadSpaces */
    private $spaces;

    /**
     * PictureRepository constructor.
     * @param UploadSpaces $spaces
     */
    public function __construct(UploadSpaces $spaces)
    {
        $this->spaces = $spaces;
        $this->spaces->setUploadsPath('uploads/scans');
    }

    /**
     * @param ScanPictures $scanPicture
     * @param array $pictures
     * @return void
     */
    public function savePictures(ScanPictures $scanPicture, $pictures = [])
    {
        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName='.env('ACCOUNT_NAME').';AccountKey='.env('ACCOUNT_KEY'));
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2/scans/';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (isset($pictures['id_scan']) && !empty($pictures['id_scan'])) {
            $scanPicture->id_scan = $pictures['id_scan'];
        }

        if (isset($pictures['product_picture'])) {
            $file = $pictures['product_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . 'products', $BlobName, $content, $options);
            $scanPicture->product_picture = 'scans/products/' . $BlobName;
            $scanPicture->save();
        }

        if (isset($pictures['shelf_picture'])) {
            $file = $pictures['shelf_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . 'shelves', $BlobName, $content, $options);
            $scanPicture->shelf_picture = 'scans/shelves/' . $BlobName;
            $scanPicture->save();
        }

        if (isset($pictures['promo_picture'])) {
            $file = $pictures['promo_picture'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . 'additional', $BlobName, $content, $options);
            $scanPicture->promo_picture = 'scans/additional/' . $BlobName;
            $scanPicture->save();
        }
    }

    /**
     * @param User $user
     * @param array $pictures
     * @return void
     */
    public function saveUserImage(User $user, $pictures = [])
    {
        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName='.env('ACCOUNT_NAME').';AccountKey='.env('ACCOUNT_KEY'));
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2/users/';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (!is_null($user->picture_path)) {
            $oldPicture = $user->picture_path;
            $Account->deleteBlob('pricecheckv2', $oldPicture);
        }

        if (isset($pictures['image_user'])) {
            $file = $pictures['image_user'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName . 'perfil', $BlobName, $content, $options);
            $user->picture_path = 'users/perfil/' . $BlobName;
            $user->save();
        }
    }

        /**
     * @return void
     */
    public function saveLogoImage($pictures = [])
    {

        $setting = Settings::where('id', 1)->first();

        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName='.env('ACCOUNT_NAME').';AccountKey='.env('ACCOUNT_KEY'));
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2/settingslogo/';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (!is_null($setting->logo_path)) {
            $oldPicture = $setting->logo_path;
            $Account->deleteBlob('pricecheckv2', $oldPicture);
        }

        if (isset($pictures['image_logo'])) {
            $file = $pictures['image_logo'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName. 'logo' , $BlobName, $content, $options);
            $setting->logo_path = 'settingslogo/logo/' . $BlobName;
            $setting->save();
        }
    }

    /**
     * @param array $pictures
     * @param int $id
     * @return void
     */
    public function saveChainImage($pictures = [], $id)
    {

        $chain = Chains::find($id);

        if (is_null($chain)) {
            return response()->json(['status' => false, 'errors' => 'El id no se encuentra en la Base de Datos'], JsonResponse::HTTP_NOT_FOUND);
        }

        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName='.env('ACCOUNT_NAME').';AccountKey='.env('ACCOUNT_KEY'));
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2/chainimage/';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (!is_null($chain->logo_path)) {
            $oldPicture = $chain->logo_path;
            $Account->deleteBlob('pricecheckv2', $oldPicture);
        }

        if (isset($pictures['image'])) {
            $file = $pictures['image'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName. 'logo' , $BlobName, $content, $options);
            $chain->logo_path = 'chainimage/logo/' . $BlobName;
            $chain->save();
        }
    }

    
    /**
     * @param array $pictures
     * @return void
     */
    public function saveThemeLogo($pictures = [])
    {
        $theme = Theme::first();

        $Account = ServicesBuilder::getInstance()->createBlobService('DefaultEndpointsProtocol=https;AccountName='.env('ACCOUNT_NAME').';AccountKey='.env('ACCOUNT_KEY'));
        $fileDate = date('d_m_y');
        $containerName = 'pricecheckv2/settingslogo/';
        $options = new CreateBlobOptions();
        $options->setBlobContentType("image/jpeg");

        if (!is_null($theme->logo_path)) {
            $oldPicture = $theme->logo_path;
            $Account->deleteBlob('pricecheckv2', $oldPicture);
        }

        if (isset($pictures['logo_path'])) {
            $file = $pictures['logo_path'];
            $BlobName = $fileDate . "/" . $file->hashName();
            $content = file_get_contents($file);
            $Account->createBlockBlob($containerName. 'logo' , $BlobName, $content, $options);
            $theme->logo_path = 'settingslogo/logo/' . $BlobName;
            $theme->save();
        }
    }
}
