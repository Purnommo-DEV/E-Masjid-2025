<?php

namespace App\Services\mrj;

use Exception;

class DeepSeekAIService
{
    protected $apiKey;
    
    public function __construct()
    {
        $this->apiKey = env('DEEPSEEK_API_KEY');
    }
    
    public function generateWishForComment($comment, $category)
    {
        if (empty($comment)) {
            return null;
        }
        
        $prompt = $this->buildPrompt($comment, $category);
        
        $data = [
            'model' => 'deepseek-chat',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten yang membantu merangkum keinginan pequrban. Buat ringkasan 1 kalimat pendek (maksimal 15 kata). Mulai dengan kata kerja (Tambah, Percepat, Pertahankan, dll). Jangan pakai kata pengantar. Langsung ke poin keinginannya.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 150
        ];
        
        $ch = curl_init('https://api.deepseek.com/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        if ($curlError) {
            \Log::error('DeepSeek CURL Error: ' . $curlError);
            return null;
        }
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $content = $result['choices'][0]['message']['content'] ?? null;
            
            // Bersihkan output
            if ($content) {
                $content = trim($content);
                $content = str_replace('"', '', $content);
                
                // Tambahkan emoji berdasarkan kategori
                $emoji = $this->getCategoryEmoji($category);
                return $emoji . ' ' . $content;
            }
        }
        
        \Log::error('DeepSeek API Error: HTTP ' . $httpCode . ' - ' . $response);
        return null;
    }
    
    private function buildPrompt($comment, $category)
    {
        $categoryLabels = [
            'informasi' => 'Informasi & Promosi Qurban',
            'pendaftaran' => 'Pendaftaran & Pembayaran',
            'pelaksanaan' => 'Penyembelihan, Pencacahan & Pengemasan',
            'distribusi' => 'Distribusi Daging Qurban',
            'kualitas' => 'Kualitas Hewan Qurban',
            'umum' => 'Perbaikan Umum'
        ];
        
        $label = $categoryLabels[$category] ?? $category;
        
        return "Komentar pequrban tentang {$label}:
\"{$comment}\"

Buat 1 kalimat keinginan/ringkasan:";
    }
    
    private function getCategoryEmoji($category)
    {
        $emojis = [
            'informasi' => '📢',
            'pendaftaran' => '💳',
            'pelaksanaan' => '🔪',
            'distribusi' => '🚚',
            'kualitas' => '🥩',
            'umum' => '📌'
        ];
        return $emojis[$category] ?? '✅';
    }
}