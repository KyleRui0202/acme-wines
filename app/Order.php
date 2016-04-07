<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ]; 

    /**
     * Scope a query to only inlcude valid orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeValid($query) {
        return $query->where('valid', true);
    }

    /**
     * Scope a query to limit the number of returned orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeLimit($query, $num) {
        return $query->take($num);
    }

    /**
     * Scope a query to skip a given number of orders.
     *
     * @param int $num
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeOffset($query, $num) {
        return $query->skip($num);
    }

    /**
     * Scope a query to only inlcude valide orders.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */ 
    public function scopeField($query, $fieldName, $fieldValue) {
        return $query->where($fieldName, $fieldValue);
    }

}
