{**
* @name adminPager.tpl Modèle d'affichage d'une administration de table par pages
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

{if $content->getDossier() neq null}
	{include file=$content->getDossier()->getTemplateName() dossier=$content->getDossier()}
{/if}

<form name="{$content->getName()}" id="{$content->getId()}" method="{$content->getMethod()}" action="{$content->getAction()}" enctype="{$content->getEnctype()}" class="{$content->getCss()}" {$content->getAttributs()}>
	{foreach $content->getCollection() as $collection => $fieldset}
		<fieldset id="{$fieldset->getId()}" class="{$fieldset->getCss()}">
			{if $fieldset->canLegend()}
				<legend>{$fieldset->getLegend()}</legend>
			{/if}
				
			
			{foreach $fieldset->getCollection() as $field}
				{include file=$field->getTemplateName() field=$field}
			{/foreach}
		</fieldset>
	{/foreach}
	
	<!-- Ajoute la gestion du pager //-->
	{include file=$content->getPager()->getTemplateName() pager=$content->getPager()}

	
	<!-- Ajoute les boutons de sélection pour les mises à jour //-->
	<fieldset id="buttons">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			{include file=$content->getCancelBtn()->getTemplateName() field=$content->getCancelBtn()}
		</div>

		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			{include file=$content->getSubmitBtn()->getTemplateName() field=$content->getSubmitBtn()}
		</div>
						
	</fieldset>
		
	{if not $content->getFormStatut()}
		<div class="alert alert-danger alert-dismissible" role="alert">
			 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
			 <strong>Attention !</strong> {$content->getFailedMsg()}
		</div>
	{/if}
	
	<!-- Boîte modale supplémentaire //-->
	{include file="./modalSelect.tpl"}
</form>
