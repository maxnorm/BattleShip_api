<?php

namespace App\Http\Controllers;

use App\Battleship\Ai;
use App\Battleship\TypeBateau;
use App\Http\Requests\MissileRequest;
use App\Http\Requests\PartieRequest;
use App\Http\Resources\MissileResource;
use App\Http\Resources\PartieResource;
use App\Models\EmplacementBateau;
use App\Models\Missile;
use App\Models\Partie;
use Illuminate\Support\Collection;


/**
 * Controller pour une partie
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class PartieController extends Controller
{
    /**
     * Creation d'une nouvelle partie
     * @param PartieRequest $request Requete
     * @return \Illuminate\Http\JsonResponse
     */
    public function nouvellePartie(PartieRequest $request)
    {
        $attributes = $request->validated();
        $config = Ai::placerAleatoire();

        $partie = Partie::create([
            "adversaire" => $attributes['adversaire'],
            "user_id" => $request->user()->id
        ]);

        foreach ($config as $type => $emplacements) {
            foreach ($emplacements as $emplacement) {
                EmplacementBateau::create([
                    "partie_id" => $partie->id,
                    "type_bateau_id" => $type,
                    "emplacement" => $emplacement
                ]);
            }
        }

        return (new PartieResource($partie))
            ->response()->setStatusCode(201);
    }

    /**
     * Tirer un missile
     *
     * @param Partie $partie Partie
     * @return \Illuminate\Http\JsonResponse Ressource missile avec code 201
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function tirerMissile(Partie $partie)
    {
        $this->authorize('update', $partie);
        if ($partie->missiles()->contains("resultat", "===", null)) {
            $missile = $partie->missiles()->where("resultat", "===", null)->first();
        } else {
            $missile = Missile::create([
                "coordonnee" => Ai::missileInitial(),
                "partie_id" => $partie->id
            ]);
        }

        return (new MissileResource($missile))
            ->response()->setStatusCode(201);
    }


    /**
     * Update le resultat du missile
     * @param MissileRequest $request Requete
     * @param Partie $partie Partie
     * @param string $coord Coordonnee
     * @return MissileResource Ressource du missile
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function updateMissile(MissileRequest $request, Partie $partie, string $coord)
    {
        $this->authorize('update', $partie);
        $attributes = $request->validated();
        $res = $attributes['resultat'];

        $missile = $partie->missiles()->where("coordonnee", str($coord))->first();

        if(is_null($missile)) {
            abort(404);
        }

        $missile->resultat = $res;
        $missile->save();

        if ($res >= 2) {
            $this->updateEmplacementsCouler($res, $request->coordonnee, $partie);
        }

        if (!Ai::partieTerminer($partie->missiles())) {
            $this->ajusterNbSamples($partie);

            Missile::create([
                "coordonnee" => Ai::prochainMissile($partie->missiles(), $partie->nb_samples),
                "partie_id" => $partie->id
            ]);
        }

        return new MissileResource($missile);
    }

    /**
     * Delete la partie
     *
     * @param Partie $partie Partie
     * @return PartieResource Ressource de la partie
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function finPartie(Partie $partie)
    {
        $this->authorize('delete', $partie);
        $resource = new PartieResource($partie);

        EmplacementBateau::destroy($partie->emplacementsBateaux());
        Missile::destroy($partie->missiles());
        $partie->delete();

        return $resource;
    }

    /**
     * Ajuster le nombre de samples d'une partie
     * @param Partie $partie Partie
     */
    private function ajusterNbSamples(Partie $partie)
    {
        if (count($partie->missiles()) % 5 == 0) {
            $partie->nb_samples = round($partie->nb_samples * 0.85);
            $partie->save();
        }
    }

    /**
     * Update des emplacements du bateau coulé
     * @param int $resultat Code du résultat
     * @param string $coord Coordonne du missible
     * @param Partie $partie Partie
     */
    private function updateEmplacementsCouler(int $resultat, string $coord, Partie $partie) {
        [$y, $x] = AI::split_coords($coord);
        $longueur = TypeBateau::fromIdCouler($resultat)->longueur();

        $missiles = $partie->missiles()->where("resultat", 1);

        if (count($missiles) <= $longueur - 1) {
            foreach ($missiles as $missile) {
                $missile->resultat = $resultat;
                $missile->save();
            }
        } else {
            [$axe, $sens] = $this->determinerDirection($y, $x, $missiles, $longueur);

            if ($axe == "y") {
                if ($sens == "+") {
                    for ($i = 0; $i < $longueur; $i++) {
                        $missile = $partie->missiles()
                            ->where("coordonnee", chr($y + $i + 64) . $coord[1] . $x)->first();
                        $missile->resultat = $resultat;
                        $missile->save();
                    }
                } else {
                    for ($i = 0; $i < $longueur; $i++) {
                        $missile = $partie->missiles()
                            ->where("coordonnee", chr($y - $i + 64) . $coord[1] . $x)->first();
                        $missile->resultat = $resultat;
                        $missile->save();
                    }
                }
            } else {
                if ($sens == "+") {
                    for ($i = 0; $i < $longueur; $i++) {
                        $missile = $partie->missiles()
                            ->where("coordonnee", $coord[0] . $coord[1] . $x + $i)->first();
                        $missile->resultat = $resultat;
                        $missile->save();
                    }
                } else {
                    for ($i = 0; $i < $longueur; $i++) {
                        $missile = $partie->missiles()
                            ->where("coordonnee", $coord[0] . $coord[1] . $x - $i)->first();
                        $missile->resultat = $resultat;
                        $missile->save();
                    }
                }
            }
        }
    }

    /**
     * Determiner la direction du bateau
     * @param int $y Coordonnee en y
     * @param int $x Coordonnee en x
     * @param Collection $missiles Missiles de la partie
     * @param int $longueur Longueur du bateau
     * @return array Direction [axe, sens]
     */
    private function determinerDirection(int $y, int $x, Collection $missiles, int $longueur)
    {
        $nbHits = [
            "y+" => 0,
            "y-" => 0,
            "x+" => 0,
            "x-" => 0
        ];

        foreach ($missiles as $missile) {
            [$ym, $xm] = Ai::split_coords($missile->coordonnee);

            if ($x == $xm) {
                if ($ym < $y) {
                    $nbHits["y-"] += 1;
                } elseif ($ym > $y) {
                    $nbHits["y+"] += 1;
                }
            } elseif ($y == $ym) {
                if ($xm < $x) {
                    $nbHits["x-"] += 1;
                } elseif ($xm > $x) {
                    $nbHits["x+"] += 1;
                }
            }
        }

        $possibleEmplacements = array_filter($nbHits, function ($v) use ($longueur) {
            return $v >= $longueur - 1;
        });

        rsort($possibleEmplacements);
        $direction = array_search($possibleEmplacements[0], $nbHits);

        return [$direction[0], $direction[1]];
    }
}

