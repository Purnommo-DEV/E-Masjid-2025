<?php
// app/Models/QurbanSetting.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class QurbanSetting extends Model
{
    protected $table = 'qurban_settings';
    
    protected $fillable = [
        'masjid_code',
        'key',
        'value',
        'type',
        'label',
        'urutan',
    ];
    
    protected $casts = [
        'value' => 'json', // ini akan otomatis handle JSON
    ];
    
    /**
     * Get setting value by key
     */
    public static function get($key, $default = null)
    {
        $cacheKey = 'qurban_setting_' . masjid() . '_' . $key;
        
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = static::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->where('key', $key)
                ->first();
                
            if (!$setting) {
                return $default;
            }
            
            // ✅ KHUSUS BOOLEAN - konversi manual
            if ($setting->type === 'boolean') {
                $cleanValue = trim($setting->value, '"');
                return ($cleanValue === 'true');
            }
            
            // ✅ KHUSUS JSON - decode manual
            if ($setting->type === 'json') {
                $cleanValue = trim($setting->value, '"');
                $decoded = json_decode($cleanValue, true);
                return $decoded ?? $default;
            }
            
            // Untuk tipe text/number, bersihkan kutip lalu return
            return trim($setting->value, '"');
        });
    }
    
    /**
     * Set setting value
     */
    public static function set($key, $value, $type = 'text', $label = null)
    {
        $setting = static::updateOrCreate(
            [
                'masjid_code' => masjid(),
                'key' => $key,
            ],
            [
                'value' => $value, // Eloquent akan otomatis encode array ke JSON
                'type' => $type,
                'label' => $label ?? $key,
            ]
        );
        
        // Clear cache
        Cache::forget('qurban_setting_' . masjid() . '_' . $key);
        Cache::forget('qurban_settings_all_' . masjid());
        
        return $setting;
    }

    public static function getAllSettings()
    {
        $cacheKey = 'qurban_settings_all_' . masjid();
        
        return Cache::remember($cacheKey, 3600, function () {
            $settings = static::withoutGlobalScope('masjid')
                ->where('masjid_code', masjid())
                ->get();
                
            $result = [];
            foreach ($settings as $setting) {
                if ($setting->type === 'json') {
                    $result[$setting->key] = json_decode($setting->value, true);
                } else {
                    $result[$setting->key] = $setting->value;
                }
            }
            
            return $result;
        });
    }
    
    /**
     * Clear all cache
     */
    public static function clearCache()
    {
        Cache::forget('qurban_settings_all_' . masjid());
        Cache::flush();
    }
}