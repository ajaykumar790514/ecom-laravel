<?php
use Illuminate\Support\Facades\Config;
function getImageUrl($fileName)
{
    $uploadPath = Config::get('app.UPLOAD_PATH');
    $defaultImagePath =config('app.DEFAULT_IMAGE');
    if (!empty($fileName) && file_exists($uploadPath . $fileName)) {
        return config('app.IMGS_URL') . $fileName;
    }
    return  $defaultImagePath;
}

