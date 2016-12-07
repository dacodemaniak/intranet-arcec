{**
* @name header.tpl Affichage de l'en-tête d'un dossier
* @project Intranet ARCEC
* @version 1.0
**}
<header class="row dossier">
	<div class="col-sm-3">
		<span class="label label-default">Dossier n° :</span>{$dossier->get("id")}
	</div>
	<div class="col-sm-4">
		<span class="label label-default">Nom :</span>{$dossier->get("nomporteur")}
		<br />
		<span class="label label-default">Prénom :</span>{$dossier->get("prenomporteur")}
	</div>
	<div class="col-sm-5">
		<span class="label label-default">CCO :</span>{$dossier->getParent("porteurcnscoord")}
		<br />
		<span class="label label-default">Phase :</span>{$dossier->getParent("etd")}
	</div>
</header>