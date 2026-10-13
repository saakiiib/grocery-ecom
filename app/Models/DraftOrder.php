<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class DraftOrder extends Model
{
    protected $fillable = ['draft_number','user_id','payload','payment_method','status'];
    protected $casts = ['payload'=>'array'];
}
