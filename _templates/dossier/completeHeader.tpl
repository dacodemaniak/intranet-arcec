{**
* @name header.tpl Affichage de l'en-tête complet d'un dossier
* @project Intranet ARCEC
* @version 1.0
**}
<header class="row dossier">
	<div class="col-sm-6">
		<span class="label label-default">Dossier n° :</span>{$dossier->get("id")}
	</div>
	<div class="col-sm-6">
		<span class="label label-default">Nom :</span>{$dossier->get("nomporteur")}
		<br />
		<span class="label label-default">Prénom :</span>{$dossier->get("prenomporteur")}
	</div>
	<div class="col-sm-12">
		<span class="label label-default">CCO :</span>{$dossier->getParent("porteurcnscoord")}
		<br />
		<span class="label label-default">Phase :</span>{$dossier->getParent("etd")}
	</div>
	<div class="col-sm-12">
		<span class="label label-default">Raison Sociale :</span>{$dossier->get("entraisonsociale")}
	</div>
</header>