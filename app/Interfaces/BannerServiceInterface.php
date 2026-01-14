<?php

namespace App\Interfaces;

interface BannerServiceInterface
{
    /**
     * Return array berisi "pages" untuk slider home.
     *
     * Contoh:
     * [
     *   [ [..banner1..], [..banner2..], [..banner3..] ], // page 1
     *   [ [..banner4..], [..banner5..], [..banner6..] ], // page 2
     * ]
     */
    public function sliderPages(int $perPage = 3): array;
}
