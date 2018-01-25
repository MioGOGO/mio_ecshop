<?php

namespace App\Models\dckc;
use App\Models\BaseModel;

class Sns extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'sns';
    protected $primaryKey = 'user_id';
    public    $timestamps = true;
}
