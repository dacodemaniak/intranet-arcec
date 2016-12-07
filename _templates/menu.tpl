{*
* @name menu.tpl Affichage des options de menu de l'application
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
* @see Objet menu et toutes les méthodes publiques associées
*}

<nav class="navbar navbar-default">
	<div class="container-fluid">
	
		<!-- En-tête du menu //-->
		<div class="navbar-header">
			<button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#{$menu->getId()}" aria-expanded="false">
	        	<span class="sr-only">Afficher / Masquer</span>
	        	<span class="icon-bar"></span>
	        	<span class="icon-bar"></span>
	        	<span class="icon-bar"></span>
	      	</button>
	      	<a class="navbar-brand" href="{$menu->dispatcher()}">{$menu->getNavBrand()}</a>
		</div>
		
		<div class="collapse navbar-collapse" id="{$menu->getId()}">
			{function name=toUL}
				<ul class="{if $isChildren}dropdown-menu{else}nav navbar-nav{/if}">
				    {foreach $items as $item}
				        {if $item["children"]}
				        	<li class="dropdown">
				        		<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button" aria-haspopup="true" aria-expanded="false">
				        			{$item["libelle"]}
				        			<span class="caret"></span>
				        		</a>
				            	{call name=toUL items=$item["children"] isChildren=true}
				            </li>
				        {else}
				        	<li>
								<a href="{\wp\Helpers\urlHelper::toURL($item["component"])}" title="{$item["description"]}">
				        			{$item["libelle"]}
				        		</a>
				        	</li>
				        {/if}
				    {/foreach}
				</ul>
			{/function}
			{call name=toUL items=$menu->getAdminArbo() isChildren=false}
		</div>
	</div>
</nav>