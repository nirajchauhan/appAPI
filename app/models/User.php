<?php

use Illuminate\Database\Eloquent\Model as Eloquent;

class User extends Eloquent {

	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'amasi_users';

	/**
	 * The attributes excluded from the model's JSON form.
	 *
	 * @var array
	 */
	protected $hidden = array('password');

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = false;

    public function Comments(){
        return $this->hasMany('Comment','user_id');
    }

    public function PostComments(){
        return $this->hasMany('PostComment','user_id');
    }

}
