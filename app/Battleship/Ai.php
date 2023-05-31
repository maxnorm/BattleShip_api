<?php

namespace App\Battleship;

use App\Models\Missile;
use Illuminate\Support\Facades\Log;
use PhpParser\Node\Stmt\Switch_;

/**
 * Classe AI pour jouer à BattleShip
 *
 * @author Marc-Olivier Pellerin-Lacroix et Maxime Normandin
 */
class Ai
{
    /**
     * @var int Dimension de la grille de jeu
     */
    private static int $dimensionBoard = 10;

    /**
     * @var int ID des cases vides dans le board
     */
    private static int $idCaseVide = -1;

    /**
     * @var int ID des cases ayant un bateau pour placement
     */
    private static int $idCaseBateau = -2;

    /**
     * @var int ID des cases ayant un bateau pour placement
     */
    private static int $idCaseHit = 1;

    /**
     * Place les bateaux aléatoirement
     *
     * @return array
     */
    public static function placerAleatoire()
    {
        $board = self::creerBoardVide();
        $config = [];

        $longueurs_bateaux = [
            [TypeBateau::PorteAvions->value, TypeBateau::PorteAvions->longueur()],
            [TypeBateau::Cuirasse->value, TypeBateau::Cuirasse->longueur()],
            [TypeBateau::Destroyer->value, TypeBateau::Destroyer->longueur()],
            [TypeBateau::SousMarin->value, TypeBateau::SousMarin->longueur()],
            [TypeBateau::Patrouiller->value, TypeBateau::Patrouiller->longueur()]
        ];

        shuffle($longueurs_bateaux);

        foreach ($longueurs_bateaux as [$type, $longueur]) {
            $coords = self::coordsBateauAleatoireValide($board, $longueur)[0];
            $config[$type] = [];

            foreach ($coords as [$row, $col]) {
                $board[$row][$col] = self::$idCaseBateau;
                $config[$type][] = chr($row + 64) . "-" . $col;
            }
        }

        return $config;
    }

    /**
     * Missile initial aléatoirement sélectionné
     * sur les coordonnes entre [C-H] - [3-8] en suivant le concept de parité
     *
     * @return string Coordonnee du missile
     */
    public static function missileInitial()
    {
        while (true) {
            $row = rand(3, 8);
            $col = rand(3, 8);

            if ($row % 2 == $col % 2) {
                break;
            }
        }

        return chr($row + 64) . "-" . $col;
    }

    /**
     * Prochain missile à tirer
     *
     * @return string Coordonnee du missile
     */
    public static function prochainMissile($missilesTirer, $nb_samples)
    {
        $board = self::creerBoardCourant($missilesTirer);
        $longueurs = self::longueursBateauxRestant($board);

        $probabilite = self::monteCarlo($board, $longueurs, $nb_samples);

        for ($y = 1; $y <= self::$dimensionBoard; ++$y) {
            $rowCount = array_count_values($board[$y]);

            for ($x = 1; $x <= self::$dimensionBoard; ++$x) {
                $colCount = array_count_values(array_column($board, $x));

                $nbHitX = array_key_exists(1, $rowCount) ? $rowCount[1] : 0;
                $nbHitY = array_key_exists(1, $colCount) ? $colCount[1] : 0;

                if ($board[$y][$x] == self::$idCaseHit) {
                    if ($nbHitX >= $nbHitY) {
                        $probabilite[$y][min($x + 1, 10)] =
                            round($probabilite[$y][min($x + 1, 10)] * (1 + 2 * ($nbHitX)), 2);

                        $probabilite[$y][max($x - 1, 1)] =
                            round($probabilite[$y][max($x - 1, 1)] * (1 + 2 * ($nbHitX)), 2);
                    }

                    if ($nbHitY > $nbHitX) {
                        $probabilite[min($y + 1, 10)][$x] =
                            round($probabilite[min($y + 1, 10)][$x] * (1 + 2 * ($nbHitY)), 2);

                        $probabilite[max($y - 1, 1)][$x] =
                            round($probabilite[max($y - 1, 1)][$x] * (1 + 2 * ($nbHitY)), 2);
                    }
                }

                if ($board[$y][$x] != self::$idCaseVide) {
                    $probabilite[$y][$x] = 0;
                }
            }
        }

        $emplacement = [];
        $meilleurProb = 0;

        for ($y = 1; $y <= self::$dimensionBoard; ++$y) {
            for ($x = 1; $x <= self::$dimensionBoard; ++$x) {
                if ($probabilite[$y][$x] > $meilleurProb) {
                    $meilleurProb = $probabilite[$y][$x];
                    $emplacement = [$y, $x];
                }
            }
        }

        return chr($emplacement[0] + 64) . "-" . $emplacement[1];
    }

    /**
     * Calcule les probabilités d'avoir un bateau pour chaque case
     * en utilisant une génération aléatoire des bateaux restant
     * avec la grille de jeu courante
     *
     * @param array $board Grille de jeu courante
     * @return array Grille de probabilité
     */
    private static function monteCarlo($board, $longueurs, $nb_samples)
    {
        $probabilite = self::creerBoardVide();

        for ($i = 0; $i < $nb_samples; ++$i) {
            $longueur = rand(2, 5);

            if (!in_array($longueur, $longueurs)) {
                --$i;
                continue;
            }

            [$coords, $coordsHit] = self::coordsBateauAleatoireValide($board, $longueur);
            foreach ($coords as $coord) {
                $probabilite[$coord[0]][$coord[1]] += 1 + count($coordsHit);
            }
        }

        return $probabilite;
    }


    /**
     * Avoir les longueurs
     *
     * @param $board
     * @return array
     */
    private static function longueursBateauxRestant($board)
    {
        $bateaux = [
            TypeBateau::PorteAvions->value,
            TypeBateau::Cuirasse->value,
            TypeBateau::Destroyer->value,
            TypeBateau::SousMarin->value,
            TypeBateau::Patrouiller->value
        ];

        for ($y = 1; $y <= self::$dimensionBoard; ++$y) {
            for ($x = 1; $x <= self::$dimensionBoard; ++$x) {
                if ($board[$y][$x] >= 2) {
                    $type = $board[$y][$x] - 1;
                    if (in_array($type, $bateaux)) {
                        unset($bateaux[$type - 1]);
                    }
                }
            }
        }

        $longueur = [];
        foreach ($bateaux as $bateau) {
            $longueur[] = TypeBateau::fromId($bateau)->longueur();
        }

        return $longueur;
    }

    /**
     * Calculer des coordonnées valide d'un bateau aléatoirement selon la grille de jeu
     * et les coordonnnées déjà hit du bateau si applicable
     *
     * @param array $board Grille de jeu
     * @param int $longueur Longueur du bateau
     * @return array Coordonnées du bateau et coordonnées du bateau déjà hit
     */
    private static function coordsBateauAleatoireValide(array $board, int $longueur)
    {
        $coords = [];
        $coordsHit = [];

        while (true) {
            $vertical = rand(0, 1);

            if ($vertical == 0) {
                $row = rand(1, self::$dimensionBoard);
                $colDepart = rand(1, self::$dimensionBoard - $longueur + 1);

                for ($i = 0; $i < $longueur; $i++) {
                    $case = $board[$row][$colDepart + $i];
                    if ($case == self::$idCaseVide or $case == self::$idCaseHit) {
                        $coords[] = [$row, $colDepart + $i];

                        if ($board[$row][$colDepart + $i] == 1) {
                            $coordsHit[] = [$row, $colDepart + $i];
                        }
                    } else {
                        $coords = [];
                        break;
                    }
                }
            } else {
                $col = rand(1, self::$dimensionBoard);
                $rowDepart = rand(1, self::$dimensionBoard - $longueur + 1);

                for ($i = 0; $i < $longueur; $i++) {
                    $case = $board[$rowDepart + $i][$col];
                    if ($case == self::$idCaseVide or $case == self::$idCaseHit) {
                        $coords[] = [$rowDepart + $i, $col];

                        if ($board[$rowDepart + $i][$col] == 1) {
                            $coordsHit[] = [$rowDepart + $i, $col];
                        }
                    } else {
                        $coords = [];
                        break;
                    }
                }
            }

            if (count($coords) == $longueur) {
                break;
            }
        }

        return [$coords, $coordsHit];
    }

    /**
     * Création d'une grille de jeu pour la partie courante
     *
     * @return array
     */
    private static function creerBoardCourant($missilesTirer)
    {
        $board = self::creerBoardVide();

        foreach ($missilesTirer as $missile) {
            [$y, $x] = self::split_coords($missile["coordonnee"]);
            $board[$y][$x] = $missile['resultat'];
        }

        return $board;
    }

    /**
     * Initialise un grille de jeu vide
     *
     * @return array
     */
    private static function creerBoardVide()
    {
        $board = [];

        for ($i = 1; $i <= self::$dimensionBoard; $i++) {
            $board[$i] = array_fill(1, self::$dimensionBoard, self::$idCaseVide);
        }

        return $board;
    }

    /**
     * Split une coordonnee afin d'avoir tout sous le format 1-10 en x et y
     * @param String $coords Coordonnee sous format A-1
     * @return array Coordonnee sous format [1, 1]
     */
    public static function split_coords($coords)
    {
        [$y, $x] = explode("-", $coords);
        $y = ord($y) - 64;
        return [$y, $x];
    }

    /**
     * Determine si la partie est terminer
     * @param Missile $missilesTirer tirer
     * @return bool
     */
    public static function partieTerminer($missilesTirer) {
        $board = self::creerBoardCourant($missilesTirer);
        $longueurs = self::longueursBateauxRestant($board);
        return count($longueurs) == 0;
    }
}
