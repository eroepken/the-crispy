<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    use Notifiable;

    public $timestamps = false;

    protected $dates = ['birthday'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [ 'slack_id', 'name', 'birthday', 'karma' ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [];

    public function addKarma() {
      $this->karma++;
      $this->save();
    }

    public function subtractKarma() {
      $this->karma--;
      $this->save();
    }

    public function getKarma() {
      return $this->karma;
    }
}
