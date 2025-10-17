<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Category extends Model
{

    //
    use HasFactory;
    protected $table ='category_of_course';

    protected $fillable = [
        'name',
    ];
}
