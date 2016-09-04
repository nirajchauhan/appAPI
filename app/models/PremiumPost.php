<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class PremiumPost extends Eloquent{
    protected $table = 'amasi_premium';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;

    public function user(){
        return $this->belongsTo('User');
    }

    /*
     * This needs to be changed
     */
    public function getComments(){
        return PremiumPostComment::orderBy('updated_at', 'desc')->where('post_id',$this->id)->get();
    }

}