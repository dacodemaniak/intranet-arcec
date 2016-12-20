{**
* @name admin.tpl Modèle d'affichage d'administration de table
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

{** Ajout du message après création modification de données **}
{if \wp\Helpers\sessionHelper::flashMessage() neq false}
	<div class="alert alert-warning alert-dismissible" role="alert">
	  <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	  {\wp\Helpers\sessionHelper::getFlashMessage()}
	</div>
{/if}

<div class="alert alert-warning alert-dismissible" role="alert" id="setevent-msg">
	 <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
	 <p class="setevent-content"></p>
</div>

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
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					{include file=$content->getCancelBtn()->getTemplateName() field=$content->getCancelBtn()}
				</div>
	
				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					{include file=$content->getSubmitBtn()->getTemplateName() field=$content->getSubmitBtn()}
				</div>

				<div class="col-lg-4 col-md-4 col-sm-4 col-xs-4">
					{include file=$content->getDeleteBtn()->getTemplateName() field=$content->getDeleteBtn()}
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

{if $content->serviceExists("eventChecker") eq true}
	<div class="alert alert-info" role="alert">
		<p>
			{$content->eventChecker()->toString()}
		</p>
	</div>
{/if}

{** Boîtes de dialogue **}
<div id="confirm-delete-dialog" title="Attention" class="ui-dialog">
	<p>Etes-vous sûr de vouloir retirer ce participant ?<br />
	Cette action est irréversible...</p>
</div>

<div id="select-persons" title="" class="ui-dialog">
	<div class="content">
	</div>
</div>

<div id="dialog-confirm" title="Suppression" class="ui-dialog">
  <p><span class="ui-icon ui-icon-alert" style="float:left; margin:12px 12px 20px 0;"></span><span id="dialog-content"></span></p>
</div>
