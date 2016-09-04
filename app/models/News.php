<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class News extends Eloquent {

    protected $table = 'amasi_news';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function comments()
    {
        return $this->hasMany('Comment');
    }

    public function getComments(){
       return Comment::orderBy('updated_at', 'desc')->where('news_id',$this->id)->get();
    }
}