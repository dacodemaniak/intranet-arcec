{**
* @name tableIndex.tpl Affichage de données d'une table pour CRUD
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}

<table class="{$liste->getClass()}" id="{$liste->getId()}">
	<!-- en-tête du tableau //-->
	<thead>
		<tr>
			{foreach $liste->getHeaders("val") as $libelle}
				<th>
					{if is_array($libelle) eq false}
						{$libelle}
					{else}
						{$libelle["header"]}
					{/if}
				</th>
			{/foreach}
			<th>Mettre à jour</th>
		</tr>			
	</thead>
	
	{if $liste->getMapper()->getNbRows() > 0}
		<!-- Corps de l'index //-->
		<tbody>
			{foreach $liste->getMapper()->getCollection() as $object}
				<tr data-rel="{$object->id}">
					{foreach $liste->getHeaders("keys") as $column}
						<td>
							{if $liste->isArray($column) eq false}
								{$object->$column}
							{else}
								{$liste->getMappedValue($column,$object->$column)}
							{/if}
						</td>
					{/foreach}
					<!-- Colonne action : Mise à jour //-->
					<td>
						<a href="{$instance->getUpdateModule()}&id={$object->id}"><span class="icon-eye"></span></a>
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
			<td colspan="{sizeof($liste->getHeaders("val"))}">
				{include file=$instance->getAddBtn()->getTemplateName() field=$instance->getAddBtn()}
			</td>
		</tr>
	</tfoot>
</table>