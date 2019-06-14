<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $table = 'categories';
    /**
     * Relacion uno a muchos
     *
     * @var array
     */
    public function posts(){
        return $this->hasMany('App\Post');
    }
}
