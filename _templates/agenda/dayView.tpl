{**
* @name dayView.tpl Affichage des événements sur une journée donnée
* @author web-Projet.com (jean-luc.aubert@web-projet.com)
* @version 1.0
**}
<!-- Affiche le contrôle pour la gestion du changement de jour //-->
<div class="row">
	<span class="col-sm-2 day-before icon-arrow-up is-disabled" data-rel="{$content->getInitDate("sql")}"></span>
	<div class="text-center col-sm-8 init-date"><span class="init-date-content" data-dateref="{$content->getInitDate("sql")}"><a href="./index.php?com=planningViewer&date={$content->getInitDate("sql")}" title="Agenda">{$content->getInitDate()}</a></span> <span class="badge">{$content->nbEvent()}</span></div>
	<span class="col-sm-2 day-after icon-arrow-down" data-rel="{$content->getInitDate("sql")}"></span>
</div>
<!-- Affiche les plages de la journée //-->
<ul class="list-unstyled plage">
	{foreach $content->getPlages() as $plage}
		{foreach $plage as $heure => $quarter}
			{assign var="timePlage" value="{$heure}:00"}
			{assign var="date" value=$content->getInitDate("object")}
			
			<li class="plage-group{if $content->hasEvent($content->getInitDate("object"),$timePlage) eq true} occupied{/if}" data-timeref="{$timePlage}">
				{$content->setEventContent($content->getInitDate("object"),$timePlage)}
				
				{if \wp\Helpers\dateHelper::isPast($content->getInitDate("sql"),$timePlage) neq true && \wp\Helpers\sessionHelper::getUserSession()->isLoggedIn() eq true}
					<span class="badge" role="button">
						<a href="index.php?com=addEvent&context=INSERT&date={$content->getInitDate("url")}&heure={$heure}:00" title="Ajouter un événement">
							{$heure}:00
						</a>
					</span>
				{else}
					<span class="badge">
						{$heure}:00
					</span>
				{/if}
				
				<span class="event-content" data-time="{$timePlage}">
					{** Affiche le premier événement dans tous les cas **}
					{if $content->getFirstEvent("url") neq null}
						<a href="{$content->getFirstEvent("url")}" title="{$content->getFirstEvent("objet")}">{$content->getFirstEvent("titre")}</a>
						{if $content->getNbEventByPlage() > 0}
							<span class="more-event"><a href="index.php?com=planningViewer&date={$content->getInitDate("sql")}&heure={$heure}:00&context=VIEW" title="">et {$content->getNbEventByPlage()} de plus...</a></span>
						{/if}
					{/if}
				</span>
				
				{** Affiche les quart d'heures associés **}
				{if $quarter neq null && sizeof($quarter) > 0}
					<ul class="quarter">
						{foreach $quarter as $minute}
							{assign var="timePlage" value="{$heure}:{$minute}"}
							
							{$content->setEventContent($content->getInitDate("object"),$timePlage)}
							
							<li class="plage-quarter{if $content->hasEvent($content->getInitDate("object"),$timePlage) eq true} occupied{/if}" data-timeref="{$timePlage}">
								{if \wp\Helpers\dateHelper::isPast($content->getInitDate("sql"),$timePlage) neq true && \wp\Helpers\sessionHelper::getUserSession()->isLoggedIn() eq true}
									<span class="badge" role="button">
										<a href="index.php?com=addEvent&context=INSERT&date={$content->getInitDate("url")}&heure={$heure}:{$minute}" title="Ajouter un événement">
											{$minute}
										</a>
									</span>
								{else}
									<span class="badge">
										{$minute}
									</span>
								{/if}
								
								<span class="event-content" data-time="{$timePlage}">
									{** Affiche le premier événement dans tous les cas **}
									{if $content->getFirstEvent("url") neq null}
										<a href="{$content->getFirstEvent("url")}" title="{$content->getFirstEvent("objet")}">{$content->getFirstEvent("titre")}</a>
										{if $content->getNbEventByPlage() > 0}
											<span class="more-event"><a href="index.php?com=planningViewer&date={$content->getInitDate("sql")}&heure={$heure}:00&context=VIEW" title="">et {$content->getNbEventByPlage()} de plus...</a></span>
										{/if}
									{/if}
								</span>
							</li>
						{/foreach}
					</ul>
				{/if}
			</li>
		{/foreach}
	{/foreach}
</ul>