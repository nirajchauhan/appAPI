<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class PostLikes extends Eloquent {

    protected $table = 'likes_post';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function user()
    {
        return $this->hasMany('User');
    }

}