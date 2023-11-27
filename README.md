# BattleShip_api

API REST en PHP avec Laravel.

Algo pour jouer a BattleShip.

Pour créer de meilleur prédiction sur les cases possibles à tirer, 
l'algorithme utilise une simulation de monte carlo afin de générer en début 
de partie 50 000 positions de bateaux possibles. 

Afin de réduire le temps d'exécution, après chaque tranche de 5 tours, 
le nombre de position généré est réduit.
