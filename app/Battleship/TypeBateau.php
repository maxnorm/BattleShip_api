<?php

namespace App\Battleship;

/**
 * Enum pour les types de bateaux
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
enum TypeBateau : int
{
    case PorteAvions = 1;
    case Cuirasse = 2;
    case Destroyer = 3;

    case SousMarin = 4;

    case Patrouiller = 5;

    /**
     * Avoir la string correspondante
     * @return string
     */
    public function ToString(): string
    {
        return match ($this) {
            TypeBateau::PorteAvions => "porte-avions",
            TypeBateau::Cuirasse => "cuirasse",
            TypeBateau::Destroyer => "destroyer",
            TypeBateau::SousMarin => "sous-marin",
            TypeBateau::Patrouiller => "patrouilleur",
        };
    }

    /**
     * Avoir la longueur du bateau
     * @return int
     */
    public function longueur(): int
    {
        return match ($this) {
            TypeBateau::PorteAvions => 5,
            TypeBateau::Cuirasse => 4,
            TypeBateau::Destroyer, TypeBateau::SousMarin => 3,
            TypeBateau::Patrouiller => 2,
        };
    }

    /**
     * Avoir le type du bateau selon le id
     * @param int $id
     * @return TypeBateau
     */
    public static function fromId(int $id): TypeBateau
    {
        return match ($id) {
            1 => TypeBateau::PorteAvions,
            2 => TypeBateau::Cuirasse,
            3 => TypeBateau::Destroyer,
            4 => TypeBateau::SousMarin,
            5 => TypeBateau::Patrouiller,
        };
    }

    /**
     * Avoir le type du bateau selon le id du résultat d'un missile
     * @param int $id
     * @return TypeBateau
     */
    public static function fromIdCouler(int $id): TypeBateau
    {
        return match ($id) {
            2 => TypeBateau::PorteAvions,
            3 => TypeBateau::Cuirasse,
            4 => TypeBateau::Destroyer,
            5 => TypeBateau::SousMarin,
            6 => TypeBateau::Patrouiller,
        };
    }
}
