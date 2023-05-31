<?php

namespace App\Http\Resources;

use App\Battleship\TypeBateau;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Log;

/**
 * Ressource pour les parties.
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class PartieResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            "id" => $this->id,
            "adversaire" => $this->adversaire,
            "bateaux" => $this->bateaux,
            "created_at" => $this->created_at
        ];
    }
}
