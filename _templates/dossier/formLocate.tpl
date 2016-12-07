{**
* @name formLocate.tpl Affichage du formulaire de recherche des dossier
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @projet intranet-arcec
**}
<form name="{$form->getName()}" id="{$form->getId()}" method="{$form->getMethod()}" action="{$form->getAction()}" enctype="{$form->getEnctype()}" class="{$form->getCss()}" {$form->getAttributs()}>
	{foreach $form->getCollection() as $name => $fieldset}
		{foreach $fieldset->getCollection() as $field}
			{include file=$field->getTemplateName() field=$field}
		{/foreach}
	{/foreach}

	<div class="alert alert-info text-center" role="alert">
		 <strong class="total">{$form->getTotalRows()}</strong> dossier(s) au total
	</div>	
	
	{if not $form->getFormStatut()}
		<div class="alert alert-danger alert-dismissible" role="alert">
		  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		  <strong>Attention !</strong> {$form->getFailedMsg()}
		</div>
	{/if}
</form>
