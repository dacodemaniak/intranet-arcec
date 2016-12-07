{**
* @name planning.tpl Affichage du planning général
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @theme agenda
* @version 1.0
**}
<div class="row">
	{** Formulaire pour le filtrage des informations **}
	{include file=$content->filters()->getTemplateName() form=$content->filters()}
	
	<div id="{$content->calendarId()}" class="col-sm-12">
	</div>
</div>