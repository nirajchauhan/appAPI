<?php
use Illuminate\Database\Eloquent\Model as Eloquent;

class Event extends Eloquent{
    protected $table = 'amasi_events';

    /**
     * Indicates if the model should be timestamped.
     *
     * @var bool
     */
    public $timestamps = true;
}