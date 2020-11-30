<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Player extends Model
{
    use HasFactory;

    protected $primaryKey = "player_id";

    protected $fillable = ["player_id"];

//    protected $guarded = "player_id";


}
