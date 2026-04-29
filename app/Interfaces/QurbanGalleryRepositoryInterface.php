<?php
// app/Interfaces/QurbanGalleryRepositoryInterface.php

namespace App\Interfaces;

interface QurbanGalleryRepositoryInterface
{
    public function all();
    public function findByPaket($paketId);
    public function find($id);
    public function create(array $data);
    public function update($id, array $data);
    public function delete($id);
    public function reorder($paketId, array $order);
    public function setCover($paketId, $galleryId);
}