{**
* @name tableIndex.tpl Affichage de données d'une table pour CRUD
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

<table class="{$docs->getClass()}" id="{$docs->getId()}">
	<!-- en-tête du tableau //-->
	<thead>
		<tr>
			<th>
				Sél.
			</th>
			{foreach $docs->getHeaders("val") as $libelle}
				<th>
					{if is_array($libelle) eq false}
						{$libelle}
					{else}
						{$libelle["header"]}
					{/if}
				</th>
			{/foreach}
			<th>Voir</th>
		</tr>			
	</thead>
	
	{if $docs->getMapper()->getNbRows() > 0}
		<!-- Corps de l'index //-->
		<tbody>
			{foreach $docs->getMapper()->getCollection() as $object}
				<tr data-rel="{$object->id}">
					<!-- Colonne pour la gestion d'actions groupées //-->
					<td>
						{include file=$object->getCheckBox()->getTemplateName() field=$object->getCheckBox()}
					</td>
					{foreach $docs->getHeaders("keys") as $column}
						<td>
							{if $docs->isArray($column) eq false}
								{$object->$column}
							{else}
								{$docs->getMappedValue($column,$object->$column)}
							{/if}
						</td>
					{/foreach}
					<!-- Colonne action : Mise à jour //-->
					<td>
						<a href="{$listeDocument->getUpdateModule()}&id={$object->id}"><span class="icon-eye"></span></a>
					</td>
				</tr>
			{/foreach}
		</tbody>
	{/if}		
	<!-- Pied de tableau : options de mise à jour //-->
	<tfoot>
		<tr>
			<td colspan="2">
				&nbsp;
			</td>
			<td colspan="{sizeof($docs->getHeaders("val"))}">
				{foreach $listeDocument->getButton() as $button}
					{include file=$button->getTemplateName() field=$button}
				{/foreach}
			</td>
		</tr>
	</tfoot>
</table>