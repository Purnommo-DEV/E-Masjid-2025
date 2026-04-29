<?php
// app/Interfaces/QurbanSettingRepositoryInterface.php

namespace App\Interfaces;

use Illuminate\Http\UploadedFile;

interface QurbanSettingRepositoryInterface
{
    public function all();
    public function get($key, $default = null);
    public function set($key, $value, $type = 'text', $label = null);
    public function updateMultiple(array $settings);
    public function getGroupedSettings();
    public function getAllSettings();
    public function uploadImage($key, UploadedFile $file, $oldPath = null);
    public function clearCache();
}