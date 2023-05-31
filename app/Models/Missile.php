<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model pour les missiles.
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class Missile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["coordonnee", "resultat", "partie_id"];
}
