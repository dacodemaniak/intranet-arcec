{**
* 	@name index.tpl
*	@author web-Projet.com (jean-luc.aubert@web-projet.com)
*	@application webProjet.com
*	@version 1.0
**}
<!doctype html>
<html>
	{include file="./head.tpl"}
	
	<body>
		<div class="container">
			{include file="./header.tpl"}
			
			{include file=$menu->getTemplateName()}
			
			<main class="row">
				<section class="col-lg-8 col-md-8 col-sm-12 col-xs-12" id="main">
					{foreach $modules as $region => $contents}
						{if $region eq "_main"}
							{foreach $contents as $module}
								{include file=$module->getTemplateName() content=$module}
							{/foreach}
						{/if}
					{/foreach}
				</section>
				
				<aside class="col-lg-4 col-md-4 col-sm-12 col-xs-12">
					{foreach $modules as $region => $contents}
						{if $region eq "_raside"}
							{foreach $contents as $module}
								{include file=$module->getTemplateName() content=$module}
							{/foreach}
						{/if}
					{/foreach}
				</aside>
			</main>
			
			{include file="./footer.tpl"}
		</div>
		<script charset="{$charset}">
			{foreach $js as $scripts}
				{foreach $scripts->getScripts("function") as $script}
					$(function(){
						{foreach $script as $code}
							{$code}
						{/foreach}
					});
				{/foreach}
			{/foreach}
		</script>
	</body>
	
</html>