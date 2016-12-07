{*
* @name ressourceIndex.tpl Affichage des ressources ARCEC
*	couplé à la table principale à traiter
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
* @see Objet taxonomyIndex.class.php et toutes les méthodes publiques associées
*}

<div class="row">
	<div id="{$index->treeId()}" class="col-sm-5">
		<a href="{$index->taxonomyModule()}" class="btn btn-success" role="button">
			Nouveau...
		</a>
		{function name=toUL}
			<ul class="{if $isChildren}children-list{else}tree-list{/if}">
				{foreach $items as $item}
					  	{if $item["children"]}
					      	<li class="dropdown" data-filter="{$item["id"]}">
					        	{$item["content"]}
					            {call name=toUL items=$item["children"] isChildren=true parent=$item["id"]}
					        </li>
					     {else}
					     	<li data-filter="{$item["id"]}">
					        	{$item["content"]}
					        </li>
					     {/if}
				{/foreach}
			</ul>
		{/function}
		{call name=toUL items=$index->taxonomy() isChildren=false parent=0}
	</div>
	
	<div class="col-sm-7">
		{if $index->addAnchors() eq true}
			<div class="row">
				<ul class="list-inline list-unstyled anchors">
					{foreach $index->anchors() as $anchor}
						<li class="anchor {if $index->check($anchor["value"])}active{else}no-active{/if}">{$anchor["value"]}</li>
					{/foreach}
				</ul>
			</div>
		{/if}
		
		{foreach $index->getMapper(false)->getCollection() as $line}
			<div class="row admin-element" data-rel="{$line->id}">
				
					{foreach $index->getHeaders(false) as $class => $column}
						{if $class eq "titre"}
							<a href="{$index->toUpdate($line->id)}" title="Mettre à jour">
								<span class="content {$class}">{$line->$column}</span>
							</a>
						{else}
							<span class="content {$class}">{$index->toLink($line->id)}</span>
						{/if}
					{/foreach}
				
			</div>
		{/foreach}
		
		<div class="row admin-button">
			<a href="{$index->toInsert()}" role="button" class="btn btn-success" title="Ajouter">
				Ajouter
			</a>
		</div>
	</div>
</div>