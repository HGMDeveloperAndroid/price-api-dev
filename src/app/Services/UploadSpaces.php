<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;

class UploadSpaces
{
    private $uploadsPath = 'uploads/users';

    /**
     * @param $file
     * @param $folderName
     * @return mixed
     */
    public function uploadPicture($file, $folderName)
    {
        $path = $this->uploadsPath.'/'.$folderName;
        return Storage::disk('spaces')->putFile($path, $file, 'public');
    }

    public function deletePicture($file)
    {
        return Storage::disk('spaces')->delete($file);
    }

    /**
     * @return string
     */
    public function getUploadsPath(): string
    {
        return $this->uploadsPath;
    }

    /**
     * @param string $uploadsPath
     */
    public function setUploadsPath(string $uploadsPath): void
    {
        $this->uploadsPath = $uploadsPath;
    }
}
