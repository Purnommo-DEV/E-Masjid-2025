<?php

namespace App\Services\mrj;

use Exception;
use Illuminate\Support\Facades\Log;
use DeepseekPhp\Deepseek;

class GeminiAIService
{
    protected $apiKey;
    protected $model;
    
    public function __construct()
    {
        $this->apiKey = env('GEMINI_API_KEY');
        $this->model = env('GEMINI_DEFAULT_MODEL', 'models/gemini-2.0-flash');
    }
    
    // GEMINI
    // public function generateWishForComment($comment, $category)
    // {
    //     if (empty($comment)) {
    //         return null;
    //     }
        
    //     $categoryLabels = [
    //         'informasi' => 'Informasi & Promosi Qurban',
    //         'pendaftaran' => 'Pendaftaran & Pembayaran',
    //         'pelaksanaan' => 'Penyembelihan, Pencacahan & Pengemasan',
    //         'distribusi' => 'Distribusi Daging Qurban',
    //         'kualitas' => 'Kualitas Hewan Qurban',
    //         'umum' => 'Perbaikan Umum'
    //     ];
        
    //     $label = $categoryLabels[$category] ?? 'Qurban';
        
    //     $prompt = "Anda adalah asisten yang membantu menganalisis masukan pequrban.
        
    //     Berdasarkan komentar berikut tentang \"{$label}\", buatlah SATU KALIMAT RINGKASAN tentang APA YANG MEREKA INGINKAN.

    //     **PENTING:**
    //     - Hanya 1 kalimat pendek (maksimal 15 kata)
    //     - Mulai dengan kata kerja (Tambah, Percepat, Pertahankan, dll)
    //     - JANGAN pakai kata pengantar seperti \"Pequrban ingin...\"
    //     - Langsung ke poin keinginannya

    //     Contoh output yang benar:
    //     - \"Percepat proses pencacahan dengan menambah tim jagal\"
    //     - \"Spanduk dibuat lebih terang dan dipasang lebih awal\"
    //     - \"Tambahkan opsi pendaftaran via Google Form\"

    //     Komentar pequrban:
    //     \"{$comment}\"

    //     Keinginan (1 kalimat):";
        
    //     try {
    //         $response = $this->callGeminiAPI($prompt);
    //         $response = trim($response);
    //         $response = str_replace('"', '', $response);
            
    //         $emoji = $this->getCategoryEmoji($category);
    //         return $emoji . ' ' . $response;
            
    //     } catch (Exception $e) {
    //         Log::error('Gemini Wish Error: ' . $e->getMessage());
    //         return null;
    //     }
    // }
    
    // DEEPSEEK
    public function generateWishForComment($comment, $category)
    {
        $client = new Deepseek(env('DEEPSEEK_API_KEY'));
        
        $response = $client->chat()->create([
            'model' => 'deepseek-chat',
            'messages' => [
                ['role' => 'system', 'content' => 'Anda adalah asisten summarization...'],
                ['role' => 'user', 'content' => $this->buildPrompt($comment, $category)]
            ],
            'temperature' => 0.3,
            'max_tokens' => 200
        ]);
        
        return $response['choices'][0]['message']['content'];
    }

    public function generateOverallSummary($commentsByCategory, $tahun)
    {
        $prompt = $this->buildOverallPrompt($commentsByCategory, $tahun);
        
        try {
            $response = $this->callGeminiAPI($prompt);
            return $this->parseOverallResponse($response);
        } catch (Exception $e) {
            Log::error('Gemini Overall Error: ' . $e->getMessage());
            return $this->fallbackOverallSummary();
        }
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
    
    private function callGeminiAPI($prompt)
    {
        $url = "https://generativelanguage.googleapis.com/v1beta/{$this->model}:generateContent?key={$this->apiKey}";
        
        $data = [
            'contents' => [
                ['parts' => [['text' => $prompt]]]
            ],
            'generationConfig' => [
                'temperature' => 0.3,
                'topP' => 0.95,
                'topK' => 40,
                'maxOutputTokens' => 200
            ]
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("API returned HTTP {$httpCode}");
        }
        
        $result = json_decode($response, true);
        
        if (isset($result['candidates'][0]['content']['parts'][0]['text'])) {
            return $result['candidates'][0]['content']['parts'][0]['text'];
        }
        
        throw new Exception("Invalid response structure");
    }
    
    private function buildOverallPrompt($commentsByCategory, $tahun)
    {
        $categories = [
            'informasi' => 'Informasi & Promosi',
            'pendaftaran' => 'Pendaftaran & Pembayaran',
            'penyembelihan' => 'Penyembelihan & Pengemasan',
            'distribusi' => 'Distribusi Daging',
            'kualitas' => 'Kualitas Hewan'
        ];
        
        $prompt = "Buat ringkasan keinginan pequrban tahun {$tahun} per kategori dalam format JSON.\n\n";
        
        foreach ($categories as $key => $label) {
            if (!empty($commentsByCategory[$key])) {
                $prompt .= "=== {$label} ===\n";
                $comments = array_slice($commentsByCategory[$key], 0, 10);
                foreach ($comments as $comment) {
                    $prompt .= "- {$comment}\n";
                }
                $prompt .= "\n";
            }
        }
        
        $prompt .= "Output JSON: {\"informasi\":[\"✅ poin1\",\"✅ poin2\"],\"pendaftaran\":[...],\"penyembelihan\":[...],\"distribusi\":[...],\"kualitas\":[...]}";
        
        return $prompt;
    }
    
    private function parseOverallResponse($response)
    {
        $jsonStart = strpos($response, '{');
        $jsonEnd = strrpos($response, '}');
        
        if ($jsonStart !== false && $jsonEnd !== false) {
            $jsonString = substr($response, $jsonStart, $jsonEnd - $jsonStart + 1);
            $parsed = json_decode($jsonString, true);
            if ($parsed) {
                return $parsed;
            }
        }
        
        return $this->fallbackOverallSummary();
    }
    
    private function fallbackOverallSummary()
    {
        return [
            'informasi' => ['✅ Informasi harga disebarkan lebih awal', '✅ Spanduk dibuat lebih terang'],
            'pendaftaran' => ['✅ Tambahkan opsi pendaftaran via Google Form', '✅ Tambahkan metode pembayaran QRIS'],
            'penyembelihan' => ['✅ Percepat proses pencacahan', '✅ Tempat penyembelihan diperluas'],
            'distribusi' => ['✅ Distribusi lebih tepat sasaran', '✅ Kupon diberikan bersamaan dengan daging'],
            'kualitas' => ['✅ Pertahankan standar kualitas hewan']
        ];
    }
}