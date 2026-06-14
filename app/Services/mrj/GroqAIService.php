<?php

namespace App\Services\mrj;

use LucianoTonet\GroqLaravel\Facades\Groq;
use Exception;

class GroqAIService
{
    protected $model;
    
    public function __construct()
    {
        $this->model = env('GROQ_MODEL', 'llama3-8b-8192');
    }
    
    public function generateWishForComment($comment, $category)
    {
        if (empty($comment)) {
            return null;
        }
        
        $prompt = $this->buildPrompt($comment, $category);
        
        try {
            $response = Groq::chat()->completions()->create([
                'model' => $this->model,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'Anda adalah asisten yang membantu merangkum keinginan pequrban. Buat 1 kalimat pendek (maksimal 15 kata). Mulai dengan kata kerja (Tambah, Percepat, Pertahankan, dll). Jangan pakai kata pengantar. Langsung ke poin keinginannya.'
                    ],
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ],
                'temperature' => 0.3,
                'max_tokens' => 150
            ]);
            
            $content = $response['choices'][0]['message']['content'] ?? null;
            
            if ($content) {
                $content = trim($content);
                $content = str_replace('"', '', $content);
                $emoji = $this->getCategoryEmoji($category);
                return $emoji . ' ' . $content;
            }
            
            return null;
            
        } catch (Exception $e) {
            \Log::error('Groq API Error: ' . $e->getMessage());
            return null;
        }
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
        
        return "Baca komentar pequrban berikut tentang \"{$label}\". Lalu tuliskan **APA YANG MEREKA HARAPKAN** dalam 1 kalimat.

    📝 KOMENTAR:
    \"{$comment}\"

    ✍️ HARAPAN (tulis dalam bahasa Indonesia yang alami, tidak perlu kata 'Percepat' jika tidak sesuai, langsung ke inti keinginan):

    Contoh:
    - Komentar: 'Spanduk kurang terang, susah dibaca dari jauh' → Harapan: 'Spanduk dibuat lebih terang dan mudah dibaca'
    - Komentar: 'Tempat penyembelihan sempit dan tim jagal lambat' → Harapan: 'Tempat diperluas dan tim jagal ditambah'
    - Komentar: 'Distribusi daging harus tepat sasaran' → Harapan: 'Distribusi daging tepat sasaran'

    HARAPAN:";
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
    
    /**
     * Test API connection
     */
    public function testConnection()
    {
        try {
            $response = Groq::chat()->completions()->create([
                'model' => $this->model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Sebutkan 3 keutamaan ibadah qurban dalam bahasa Indonesia!']
                ]
            ]);
            
            return [
                'success' => true,
                'response' => $response['choices'][0]['message']['content'] ?? null
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}