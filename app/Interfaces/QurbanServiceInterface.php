<?php
// app/Interfaces/QurbanServiceInterface.php

namespace App\Interfaces;

interface QurbanServiceInterface
{
    public function getAllActive();
    public function getFeatured();
    public function getBySlug($slug);
    public function getByJenis($jenis);
    public function register(array $data);
    public function getRegistrationByCode($kode);
}