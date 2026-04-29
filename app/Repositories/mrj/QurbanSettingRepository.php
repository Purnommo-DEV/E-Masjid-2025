<?php
// app/Repositories/mrj/QurbanSettingRepository.php

namespace App\Repositories\mrj;

use App\Interfaces\QurbanSettingRepositoryInterface;
use App\Models\QurbanSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class QurbanSettingRepository implements QurbanSettingRepositoryInterface
{
    protected $cachePrefix = 'qurban_settings_';
    protected $cacheTtl = 3600;
    
    public function all()
    {
        return QurbanSetting::withoutGlobalScope('masjid')
            ->where('masjid_code', masjid())
            ->orderBy('urutan')
            ->get();
    }
    
    public function get($key, $default = null)
    {
        $cacheKey = $this->cachePrefix . masjid() . '_' . $key;
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($key, $default) {
            $setting = QurbanSetting::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->where('key', $key)
                ->first();
                
            if (!$setting) {
                return $default;
            }
            
            // ✅ Handle boolean type (dengan trim)
            if ($setting->type === 'boolean') {
                // Bersihkan dari kutipan berlebih
                $cleanValue = trim($setting->value, '"');
                return filter_var($cleanValue, FILTER_VALIDATE_BOOLEAN);
            }
            
            // ✅ Handle JSON type (dengan trim)
            if ($setting->type === 'json') {
                if (is_array($setting->value)) {
                    return $setting->value;
                }
                if (is_string($setting->value)) {
                    // Bersihkan dari kutipan berlebih
                    $cleanValue = trim($setting->value, '"');
                    $decoded = json_decode($cleanValue, true);
                    return $decoded ?? $default;
                }
                return $default;
            }
            
            return $setting->value;
        });
    }
    
    public function set($key, $value, $type = 'text', $label = null)
    {
        // ✅ Bersihkan value terlebih dahulu
        $cleanValue = $value;
        
        // Jika string boolean, konversi ke boolean proper
        if (is_string($cleanValue) && ($cleanValue === 'true' || $cleanValue === 'false')) {
            $cleanValue = filter_var($cleanValue, FILTER_VALIDATE_BOOLEAN);
        }
        
        // Handle JSON type
        if ($type === 'json' && is_array($cleanValue)) {
            $valueToStore = json_encode($cleanValue);
        }
        // Handle boolean type
        elseif ($type === 'boolean') {
            $valueToStore = $cleanValue ? 'true' : 'false';
        }
        // Handle other types
        else {
            $valueToStore = $cleanValue;
        }
        
        // ✅ Pastikan tidak ada kutipan berlebih
        if (is_string($valueToStore)) {
            $valueToStore = trim($valueToStore, '"');
        }
        
        $setting = QurbanSetting::updateOrCreate(
            [
                'masjid_code' => masjid(),
                'key' => $key,
            ],
            [
                'value' => $valueToStore,
                'type' => $type,
                'label' => $label ?? $key,
            ]
        );
        
        Cache::forget($this->cachePrefix . masjid() . '_' . $key);
        Cache::forget('qurban_settings_all_' . masjid());
        
        return $setting;
    }
    
    public function updateMultiple(array $settings)
    {
        DB::beginTransaction();
        
        try {
            foreach ($settings as $key => $value) {
                $this->set($key, $value);
            }
            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
    
    public function getGroupedSettings()
    {
        $settings = $this->all();
        
        $grouped = [
            'hero' => [],
            'kontak' => [],
            'bank' => [],
            'statistik' => [],
            'harga' => [],
            'catatan' => [],
            'faq' => [],
            'home' => [],
            'tampilan' => [],
        ];
        
        foreach ($settings as $setting) {
            if (str_starts_with($setting->key, 'home_')) {
                $grouped['home'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'show_')) {
                $grouped['tampilan'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'hero_')) {
                $grouped['hero'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'contact_')) {
                $grouped['kontak'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'bank_')) {
                $grouped['bank'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'stats_')) {
                $grouped['statistik'][$setting->key] = $setting;
            } elseif (str_starts_with($setting->key, 'potong_')) {
                $grouped['harga'][$setting->key] = $setting;
            } elseif ($setting->key === 'important_notes') {
                $grouped['catatan'][$setting->key] = $setting;
            } elseif ($setting->key === 'faq_items') {
                $grouped['faq'][$setting->key] = $setting;
            }
        }
        
        return $grouped;
    }
    
    public function getAllSettings()
    {
        $cacheKey = 'qurban_settings_all_' . masjid();
        
        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            $settings = QurbanSetting::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->get();
                
            $result = [];
            foreach ($settings as $setting) {
                if ($setting->type === 'boolean') {
                    $cleanValue = trim($setting->value, '"');
                    $result[$setting->key] = filter_var($cleanValue, FILTER_VALIDATE_BOOLEAN);
                } elseif ($setting->type === 'json') {
                    $cleanValue = trim($setting->value, '"');
                    $decoded = json_decode($cleanValue, true);
                    $result[$setting->key] = $decoded ?? [];
                } else {
                    $result[$setting->key] = $setting->value;
                }
            }
            return $result;
        });
    }
    
    public function uploadImage($key, UploadedFile $file, $oldPath = null)
    {
        $imagePath = upload_image($file, 'qurban-home', $oldPath, true);
        $this->set($key, $imagePath, 'image');
        return $imagePath;
    }
    
    public function clearCache()
    {
        Cache::forget('qurban_settings_all_' . masjid());
        Cache::flush();
    }
}