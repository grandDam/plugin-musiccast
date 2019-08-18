== Configuration du plugin

La configuration est très simple, après téléchargement du plugin, il vous suffit de l'activer et c'est tout. Le plugin va rechercher les MusicCast sur votre réseau et créer les équipements automatiquement. Il vous restera à les affecter à vos pièces.

[TIP]
Ce plugin nécessite que les enceintes soit alimentés (même en mode veille) et configurer dans l'app officielle pour ne pas provoquer d'erreur.

Si plus tard vous ajoutez un élément MusicCast, vous pouvez soit créer un équipement MusicCast en donnant l'IP à Jeedom ou cliquer sur "Rechercher les équipements MusicCast"


== Configuration des équipements

La configuration des équipements MusicCast est accessible à partir du menu Plugins puis multimedia

Vous retrouvez ici toute la configuration de votre équipement : 

* *Nom de l'équipement MusicCast* : nom de votre équipement MusicCast
* *Objet parent* : indique l'objet parent auquel appartient l'équipement
* *Activer* : permet de rendre votre équipement actif
* *Visible* : le rend visible sur le dashboard
* *Modèle* : le modèle ou catégorie de votre MusicCast
* *IP* : l'IP de votre MusicCast, peut être utile si votre MusicCast change d'IP ou si vous le remplacez

En-dessous vous retrouvez la liste des commandes : 

* *Nom* : nom de la commande
* *Configuration avancée (petites roues crantées)* : permet d'afficher la configuration avancée de la commande (méthode d'historisation, widget...)
* *Tester* : permet de tester la commande

Comme commande vous retrouverez : 

* *Jouer playlist* : commande de type message permettant de lancer une playlist, il suffit dans le titre de mettre le nom de la playlist. Vous pouvez mettre "random" dans message pour mélanger la playlist avant la lecture.
* *Jouer un favori* : commande de type message permettant de lancer un favoris, il suffit dans le titre de mettre le nom du favoris.
* *Ajout un haut-parleur* : permet d'ajouter un haut-parleur MusicCast au haut-parleur courant (pour associer 2 MusicCast par exemple). Il faut mettre le nom du MusicCast à ajouter dans le titre (le champs message n'est pas utilisé ici).
* *Supprimer un haut-parleur* : permet de supprimer un haut-parleur MusicCast au haut-parleur courant (pour dissocier 2 MusicCast par exemple). Il faut mettre le nom du MusicCast à supprimer dans le titre (le champs message n'est pas utilisé ici).
* *Supprimer les hauts-parleurs* : permet de supprimer tous les hauts-parleurs MusicCast au haut-parleur courant (pour dissocier 2 MusicCast par exemple). Il faut mettre le nom du MusicCast à supprimer dans le titre (le champs message n'est pas utilisé ici).
* *Aléatoire statut* : indique si on est en mode aléatoire ou non
* *Aléatoire* : inverse le statut du mode aléatoire
* *Répéter statut* : indique si on est en mode répété ou non
* *Répéter* : inverse le statut du mode "répéter"
* *Image* : lien vers l'image de l'album
* *Album* : nom de l'album en cours de lecture
* *Artiste* : nom de l'artiste en cours de lecture
* *Piste* : nom de la piste en cours de lecture
* *Muet* : passe en muet
* *Précédent* : piste précédente
* *Suivant* : piste suivante
* *Lecture* : passer en lecture
* *Pause* : mettre en pause
* *Stop* : arrêter la lecture
* *Volume* : modifier le volume (de 0 à 100)
* *Volume statut* : niveau du volume
* *Statut* : statut (pause, lecture, transition...)
* *On* : indique si l'enceinte est allumée
* *Power On* : allume l'enceinte
* *StandBy* : mise en veille de l'enceinte
* *Coordinateur* : indique si l'enceinte se pilote elle-même et potentiellement d'autres enceintes.
* *Input* : Entrée sélectionnée
* *Change input* : changer l'entrée

[NOTE]
Pour la lecture des playlists vous pouvez mettre des options (dans la case option). Pour lancer la playlist en lecture aléatoire il faut mettre dedans "random"


== Le panel

Le plugin MusicCast met aussi à disposition un panel qui rassemble tous vos MusicCast. Disponible à partir du menu Accueil -> MusicCast Controller :

[IMPORTANT]
Pour avoir le panel il faut l'activer dans la configuration du plugin
