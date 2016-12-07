<form name="{$form->getName()}" id="{$form->getId()}" method="{$form->getMethod()}" action="{$form->getAction()}" enctype="{$form->getEnctype()}" class="{$form->getCss()}" {$form->getAttributs()}>
	{foreach $form->getCollection() as $name => $fieldset}
		{foreach $fieldset->getCollection() as $field}
			{include file=$field->getTemplateName() field=$field}
		{/foreach}
	{/foreach}
	
	{if not $form->getFormStatut()}
		<div class="alert alert-danger alert-dismissible" role="alert">
		  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
		  <strong>Attention !</strong> {$form->getFailedMsg()}
		</div>
	{/if}
</form>
