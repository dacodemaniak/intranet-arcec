{**
* @name tableIndex.tpl Affichage de donn�ees d'une table pour CRUD
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}
<table class="{$index->getClass()}" id="{$index->getId()}">
	<!-- en-t�te du tableau //-->
	<thead>
		<tr>
			{foreach $index->getHeaders("val") as $libelle}
				<th>
					{if is_array($libelle) eq false}
						{$libelle}
					{else}
						{$libelle["header"]}
					{/if}
				</th>
			{/foreach}
		</tr>			
	</thead>
	
	{if $index->getMapper()->getNbRows() > 0}
		<!-- Corps de l'index //-->
		<tbody>
			{foreach $index->getMapper()->getCollection() as $object}
				<tr data-rel="{$object->id}">
					{foreach $index->getHeaders("keys") as $column}
						<td>
							{if $index->isArray($column) eq false}
								{$object->$column}
							{else}
								{$index->getMappedValue($column,$object->$column)}
							{/if}
						</td>
					{/foreach}
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
			<td colspan="{sizeof($index->getHeaders("val"))}">
				
			</td>
		</tr>
	</tfoot>
</table>

{if $index->getPagerStatus()}
<!-- pager --> 
	<div class="pager"> 
	        <img src="http://mottie.github.com/tablesorter/addons/pager/icons/first.png" class="first"/> 
	        <img src="http://mottie.github.com/tablesorter/addons/pager/icons/prev.png" class="prev"/> 
	        <span class="pagedisplay"></span> <!-- this can be any element, including an input --> 
	        <img src="http://mottie.github.com/tablesorter/addons/pager/icons/next.png" class="next"/> 
	        <img src="http://mottie.github.com/tablesorter/addons/pager/icons/last.png" class="last"/> 
	        <select class="pagesize" title="Nombre de lignes par page"> 
	            <option value="10"{if $index->getPagerThresold() == 10} selected="selected"{/if}>10</option> 
	            <option value="20"{if $index->getPagerThresold() == 20} selected="selected"{/if}>20</option> 
	            <option value="30"{if $index->getPagerThresold() == 30} selected="selected"{/if}>30</option> 
	            <option value="40"{if $index->getPagerThresold() == 40} selected="selected"{/if}>40</option> 
	        </select>  
	        <select class="gotoPage" title="Sélectionnez la plage"></select>
	</div>
{/if}