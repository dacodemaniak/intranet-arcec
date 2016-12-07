{**
* @name adminTabs.tpl Publication d'un formulaire divisé par onglets
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

{if $content->getDossier() neq null}
	{include file=$content->getDossier()->getTemplateName() dossier=$content->getDossier()}
{/if}

<form name="{$content->getName()}" id="{$content->getId()}" method="{$content->getMethod()}" action="{$content->getAction()}" enctype="{$content->getEnctype()}" class="{$content->getCss()}" {$content->getAttributs()}>
	{foreach $content->getCollection() as $collection => $fieldset}
		<fieldset>
				{foreach $fieldset->getCollection() as $field}
					{include file=$field->getTemplateName() field=$field}
				{/foreach}
			</fieldset>
	{/foreach}
				
	<ul class="{$tabs->getCss()}">
		{foreach $tabs->getTabs() as $tab}
			<li role="presentation" {if $tab->isActive()} class="active"{/if}>
				<a href="#{$tab->getId()}"  data-toggle="{$tabs->getDataToggle()}">{$tab->getTitle()}</a>
			</li>
		{/foreach}
	</ul>
	
	{** Contenu des onglets **}
	<div class="{$tabs->getCss("contentCssClasses")}">
		{foreach $tabs->getTabs() as $tab}
			{if $tab->getContent() neq null}
				<div id="{$tab->getId()}" class="{$tab->getCss()}">
					{include file=$tab->getContent()->getTemplateName() content=$tab->getContent()}
				</div>
			{/if}
		{/foreach}
	</div>
	
	<!-- Ajoute les boutons de sélection pour les mises à jour //-->
	<fieldset id="buttons">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			{include file=$content->getCancelBtn()->getTemplateName() field=$content->getCancelBtn()}
		</div>

		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-6">
			{include file=$content->getSubmitBtn()->getTemplateName() field=$content->getSubmitBtn()}
		</div>
						
	</fieldset>
</form>
	
<!-- Boîttes modales supplémentaires //-->
{include file="./modalSelect.tpl"}
{include file="./errors.tpl"}
	