<?php

namespace App\Events;

use App\Models\Acara;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AcaraPublished
{
    use Dispatchable, SerializesModels;

    public Acara $acara;

    public function __construct(Acara $acara)
    {
        $this->acara = $acara;
    }
}
