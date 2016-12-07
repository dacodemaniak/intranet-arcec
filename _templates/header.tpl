<header class="page-header row">
	<div class="col-lg-3 col-md-3 col-sm-6 col-xs-12">
		<a href="./index.php" rel="home" title="Retour à l'accueil de l'Intranet">
			<img src="{\wp\Helpers\imageHelper::find($config->get("default_logo"))}" alt="{$config->get("appName")}" class="img-responsive" />
		</a>
	</div>
	
	<h1 class="col-lg-9 col-md-9 hidden-sm col-xs-12">
		{$config->get("title")} - {$indexTitle}
	</h1>
	
	{if $userForm neq null}
		{include file=$userForm->getTemplateName() form=$userForm}
	{/if}
	
</header>