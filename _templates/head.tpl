{**
*	@name head.tpl
	@author web-Projet.com (jean-luc.aubert@web-projet.com)
	@app web-Projet.com
	@date Nov. 2015
	@version 1.0
**}
<head>
	<!-- viewport pour affichage responsive //-->
	<meta name="viewport" content="width=device-width,initial-scale=1.0,maximum-scale=1.0,user-scalable=no" />
	
	<!-- charset pour le jeu de caractères //--> 
	<meta charset="{$charset}" />
	
	<!-- title //-->
	<title></title>
	
	<!-- Description //-->
	
	<!-- Mots-clés //-->
	
	{if $expose eq 1}
		<!-- On se fait plaisir... //-->
		<meta name="author" content="{$author}" />
	{/if}
	
	{if $css neq null}
		<!-- Feuilles de styles //-->
		{foreach $css as $stylesheets}
			{foreach $stylesheets->getContents() as $sheet}
				<link href="{$sheet.file}" rel="stylesheet" />
			{/foreach}
		{/foreach}
	{/if}
	
	{if $js neq null}
		<!-- Fichiers javascripts //-->
		{foreach $js as $scripts}
			{foreach $scripts->getContents() as $script}
				<script src="{$script.file}" charset="{$charset}"></script>
			{/foreach}
		{/foreach}
		
		<!-- Code javascript à intégrer //-->
		<script charset="{$charset}">
			{foreach $js as $scripts}
				{foreach $scripts->getScripts("ready") as $script}
				{/foreach}
			{/foreach}
		</script>
	{/if}
	
	
	
</head>