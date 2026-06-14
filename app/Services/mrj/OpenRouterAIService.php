<?php

namespace App\Services\mrj;

use Exception;

class OpenRouterAIService
{
    protected $apiKey;
    protected $model;
    
    public function __construct()
    {
        $this->apiKey = env('OPENROUTER_API_KEY');
        $this->model = env('OPENROUTER_MODEL', 'openrouter/free');
    }
    
    /**
     * Generate wish/ringkasan keinginan dari komentar
     */
    public function generateWishForComment($comment, $category)
    {
        if (empty($comment)) {
            return null;
        }
        
        $prompt = $this->buildPrompt($comment, $category);
        
        $data = [
            'model' => $this->model,
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Anda adalah asisten yang membantu merangkum keinginan pequrban. Buat 1 kalimat pendek (maksimal 15 kata). Gunakan bahasa Indonesia alami. Langsung ke poin keinginannya.'
                ],
                [
                    'role' => 'user',
                    'content' => $prompt
                ]
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000
        ];
        
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey,
            'HTTP-Referer: ' . url('/'),
            'X-Title: ' . config('app.name', 'E-Masjid')
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);
        
        // Cek error CURL
        if ($curlError) {
            throw new \Exception("CURL Error: {$curlError}");
        }
        
        // Cek HTTP error
        if ($httpCode !== 200) {
            $responseData = json_decode($response, true);
            $errorMsg = $responseData['error']['message'] ?? "HTTP {$httpCode}";
            throw new \Exception("API Error: {$errorMsg}");
        }
        
        $result = json_decode($response, true);
        $content = $result['choices'][0]['message']['content'] ?? null;
        
        if (!$content || strlen($content) < 5) {
            throw new \Exception("Response tidak valid atau terlalu pendek");
        }
        
        $content = trim($content);
        $content = str_replace('"', '', $content);
        $emoji = $this->getCategoryEmoji($category);
        
        return $emoji . ' ' . ucfirst($content);
    }
    
    /**
     * Build prompt berdasarkan kategori
     */
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

APA YANG MEREKA INGINKAN (1 kalimat, maksimal 15 kata):";
    }
    
    /**
     * Get emoji berdasarkan kategori
     */
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
    
    /**
     * Test API connection
     */
    public function testConnection()
    {
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'user', 'content' => 'Sebutkan 3 keutamaan ibadah qurban dalam bahasa Indonesia!']
            ],
            'max_tokens' => 1000
        ];
        
        $ch = curl_init('https://openrouter.ai/api/v1/chat/completions');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            return [
                'success' => true,
                'response' => json_decode($response, true)
            ];
        }
        
        return [
            'success' => false,
            'error' => "HTTP {$httpCode}",
            'response' => $response
        ];
    }
}