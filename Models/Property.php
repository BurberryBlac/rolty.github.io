<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $table='table_properties';

    public function myPropertiesOffers()
    {
        return $this->hasMany(Offer::class);

    }

    public function getUserProfile(){
        return $this->hasOne('App\Models\User','id','user_id');
    }

    
    public function getPropertyRating()
    {
        return $this->hasMany(PropertyReview::class);

    }
    public function getPropertyFavourite()
    {
        return $this->hasOne(PropertyFavourite::class);

    }

    public function getPropertyThumnail()
    {
        return $this->hasOne(PropertyGallery::class);

    }
    
    
}
