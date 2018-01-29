<?php

namespace App\Models\dckc;
use App\Models\BaseModel;
use App\Helper\Token;
use DB;

class AffiliateOrder extends BaseModel {

    protected $connection = 'shop';
    protected $table      = 'order_info';
    protected $primaryKey = 'order_id';
    public    $timestamps = false;
    protected $guarded = [];
    
}
