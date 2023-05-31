<?php

namespace App\Models;

use App\Battleship\TypeBateau;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Model pour les parties.
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class Partie extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ["adversaire", 'nb_samples', 'user_id'];

    protected $appends = ['bateaux'];

    /**
     * Lien entre une partie et un utilisateur
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function user()
    {
        return $this->belongsTo(Missile::class)->get();
    }

    /**
     * Lien entre une partie et les missiles
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function missiles()
    {
        return $this->hasMany(Missile::class)->get();
    }

    /**
     * Lien entre une partie aux emplacements des bateaux
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function emplacementsBateaux()
    {
        return $this->hasMany(EmplacementBateau::class)->withTrashed()->get();
    }

    /**
     * Attribute pour les emplacements des bateaux dans un partie
     * @return Attribute
     */
    protected function bateaux(): Attribute
    {
        $emplacementsBateau = [];
        foreach (TypeBateau::cases() as $bateau)
        {
            $emplacements = $this->emplacementsBateaux()->where("type_bateau_id", $bateau->value);
            foreach ($emplacements as $emplacement){
                $emplacementsBateau[$bateau->value][] = $emplacement["emplacement"];
            }
        }

        return Attribute::make(
            get: fn () =>  [
                TypeBateau::PorteAvions->ToString() => $emplacementsBateau[TypeBateau::PorteAvions->value],
                TypeBateau::Cuirasse->ToString() => $emplacementsBateau[TypeBateau::Cuirasse->value],
                TypeBateau::Destroyer->ToString() => $emplacementsBateau[TypeBateau::Destroyer->value],
                TypeBateau::SousMarin->ToString() => $emplacementsBateau[TypeBateau::SousMarin->value],
                TypeBateau::Patrouiller->ToString() => $emplacementsBateau[TypeBateau::Patrouiller->value]
            ]
        );
    }
}
