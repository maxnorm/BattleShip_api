<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model pour les emplacements des bateaux d'une partie.
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class EmplacementBateau extends Model
{
    use HasFactory,  SoftDeletes;

    protected $fillable = ["partie_id", "type_bateau_id", "emplacement"];

    /**
     * Table de la bd associée au model.
     *
     * @var string
     */
    protected $table = 'emplacements_bateaux';


}
