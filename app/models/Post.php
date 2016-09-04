<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Post extends Eloquent{
    protected $table = 'amasi_posts';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function user(){
        return $this->belongsTo('User');
    }

    public function event(){
        return $this->belongsTo('Event');
    }

    public function getComments(){
        return PostComment::orderBy('updated_at', 'desc')->where('post_id',$this->id)->get();
    }

}