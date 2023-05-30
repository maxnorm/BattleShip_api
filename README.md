# BattleShip_api

Ce projet est une api REST progammer en PHP avec Laravel.
Celui-ci consiste à une api jouant au jeu de BattleShip.
Pour créer de meilleur prédiction sur les cases possibles à tirer, 
l'algorithme utilise une simulation de monte carlo afin de générer en début 
de partie 50 000 positions de bateaux possibles. 

Afin de réduire le temps d'exécution, après chaque tranche de 5 tours, 
le nombre de position généré est réduit.
