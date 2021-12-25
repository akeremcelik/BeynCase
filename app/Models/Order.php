<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    public function service() {
        return $this->belongsTo(Service::class);
    }

    public function carModel() {
        return $this->belongsTo(CarModel::class);
    }
}
