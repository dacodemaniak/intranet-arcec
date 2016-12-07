{**
* @name footer.tpl : Gestion du pied de page de l'application
* @project Intranet ARCEC
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
**}
<footer id="main-footer" class="row">
	<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
		<a href="http://web.arcec.net" target="_new" title="Site web de l'ARCEC">
			Copyright (&copy;) ARCEC 2015 - 2016
		</a>
	</div>
	
	<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
		<ul class="list-unstyled list-inline">
			<li>PHP Version : {$application->appConfig->getPHPVersion()}</li>
			<li>{$dbInstance->getDBMSType()} Version : {$dbInstance->getDBMSVersion()}</li>
		</ul>
	</div>
	
	<div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
		{if $smarty.const._PRODUCTION_MODE}
			Mode Production
		{else}
			Mode debug
		{/if}
	</div>
</footer>