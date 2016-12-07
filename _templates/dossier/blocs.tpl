{**
* @name blocs.tpl Affichage de blocs identifiés administrables en Ajax
* @author web-projet.com (Jean-Luc Aubert)
* @package templates/dossier
* @version 1.0
**}

<div class="row">
	{foreach $suivi->getCollection() as $etape}
		<div class="phase {$suivi->blocCSS()}" data-rel="{$etape->etapeprojet_id}">
			<p class="text-center"><strong>{$suivi->get("libelle","etapeprojet_id",$etape)}</strong></p>
			{include file=$suivi->actionList($etape)->getTemplateName() field=$suivi->actionList()}
			{include file=$suivi->conseillerList($etape)->getTemplateName() field=$suivi->conseillerList()}
		</div>
	{/foreach}
</div>