{**
* @name admin.tpl Modèle d'affichage d'administration de table
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

{if $index}
	{include file=$index->getTemplateName()}
{else}
	{if $content->isSubForm() neq true}
		<form name="{$content->getName()}" id="{$content->getId()}" method="{$content->getMethod()}" action="{$content->getAction()}" enctype="{$content->getEnctype()}" class="{$content->getCss()}" {$content->getAttributs()}>
	{/if}
			{foreach $content->getCollection() as $collection => $fieldset}
				<fieldset>
					{if $fieldset->canLegend()}
						<legend>{$fieldset->getLegend()}</legend>
					{/if}
					
					{foreach $fieldset->getCollection() as $field}
						{include file=$field->getTemplateName() field=$field}
					{/foreach}
				</fieldset>
			{/foreach}
	{if $content->isSubForm() neq true}		
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
		</form>
	{/if}
{/if}