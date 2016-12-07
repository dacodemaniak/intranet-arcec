<?php
/**
 * @name dayView.class.php Service d'affichage de l'agenda sous forme de jours
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package \arcec\Agenda
 * @vesion 1.0
**/
namespace arcec\Agenda;

class dayView extends \arcec\Agenda\agendaViewer implements \wp\Tpl\template,\wp\formManager\RIA {
	
	/**
	 * Modèle à utiliser pour l'affichage du viewer
	 * @var string
	 */
	private $templateName;
	
	/**
	 * Construit un nouvel objet de type vue Jour
	 * @param string $date (Optionnel) Date de référence
	 * @param string $format (Optionnel) Format de la date
	 */
	public function __construct($date=null,$format=null){
		$this->beginAt("07:00");
		$this->endAt("20:00");
		$this->step(15,"m");

		$this->mappers();
		
		$this->initDate($date,$format);
		
		$this->plages();
		
		$this->setRIAScript();
	}
	
	/**
	 * Traite la récupération des données du jour
	 */
	public function process(){
		$events = clone $this->mappers["event"];
		
		$events->searchBy("date",$this->getInitDate("sql"));
		
		if($events->count() > 0){
			$events->order("heuredebut","desc");
		
			$events->set($events->getNameSpace());
			
			foreach($events->getCollection() as $event){
				$this->events[] = array(
					"id" => $event->id,
					"titre" => $event->titre,
					"description" => $event->objet,
					"debut" => $event->heuredebut,
					"fin" => $event->heurefin,
					"bystep" => $this->byStep($event->heuredebut,$event->heurefin),
					"lieu" => $this->getLieu($event->bureau_id)
				);
			}
			$this->nbEvent = sizeof($this->events);
		} else {
			$this->nbEvent = 0;
		}
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Tpl\template::setTemplateName()
	 */
	public function setTemplateName($name){
		$this->templateName = "./" . $name . ".tpl";
		return $this;
	}
	
	public function getTemplateName(){
		return $this->templateName;
	}
	
	private function byStep($hDeb,$hFin){
		$hour = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $hDeb);
		$end = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $hFin);
		
		$dateInterval = new \DateInterval("PT" . $this->step . strtoupper($this->stepType));
		
		do{
			$quarter[$hour->format("H")][] = $hour->format("i");
			$hour->add($dateInterval);
			
		} while($hour < $end);
		
		return $quarter;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Agenda\agendaViewer::plages()
	 */
	protected function plages(){
		$hour = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $this->beginAt());
		$end = new \DateTime($this->getInitDate("object")->format("Y-m-d") . " " . $this->endAt());
		
		$oldHour = $hour->format("H");
		
		$dateInterval = new \DateInterval("PT" . $this->step . strtoupper($this->stepType));
		
		do{
			if($hour->format("H") != $oldHour){
				$this->plages[] = $quarter;
				$oldHour = $hour->format("H");
				$quarter=array();
			}
			if($hour->format("i") != "00"){
				$quarter[$hour->format("H")][] = $hour->format("i");
			} else {
				if($hour->format("H:i") == $this->endAt()){
					$quarter = null;
				}
			}
			$hour->add($dateInterval);
				
		} while($hour <= $end);
		
		// Ajoute la dernière heure
		$quarter = array();
		$quarter[$end->format("H")][] = null;
		$this->plages[] = $quarter;
	}
	
	/**
	 * Définit le script pour la gestion du changement de dates
	**/
	public function setRIAScript(){
		$js = new \wp\htmlManager\js();
		
		$jQuery = "
			// Gestion du bouton \"Précédent\"
			moment.locale(\"fr\");
			var affDate = new String;
			var usDate = new String;
			
			$(\"span.day-before\").on(\"click\",function(){
					if(!$(this).hasClass(\"is-disabled\")){
						// Récupère la valeur de la date courante et calcule la date précédente
						var currentDate = moment($(this).data(\"rel\"));
						usDate = $(this).data(\"rel\")
						var prevDate = currentDate.subtract(1,\"days\");
				
						// Contrôle le statut du bouton courant
						if(prevDate.format(\"YYYY MM DD\") == moment().format(\"YYYY MM DD\") ){
							$(this).addClass(\"is-disabled\");
						}
				
						// Affecte la nouvelle valeur à la date affichée
						$(\"span.init-date-content\").data(\"dateref\",prevDate.format(\"YYYY-MM-DD\"));
				
						affDate = prevDate.format(\"dddd D MMMM YYYY\");
						var planning = $(\"<a>\");
						planning.attr(\"href\",\"index.php?com=planningViewer&date=\" + prevDate.format(\"YYYY-MM-DD\"));
						planning.attr(\"title\",\"Voir le planning\");
						planning.text(affDate);
						//$(planning).appendTo($(\"span.init-date-content\")); //.html(affDate);
						$(this).data(\"rel\",prevDate.format(\"YYYY-MM-DD\"));
						$(\"span.day-after\").data(\"rel\",prevDate.format(\"YYYY-MM-DD\"));
						
						$(\"div.init-date span.badge\").html(\"En cours...\");
				
						// Exécute le script de récupération des événements de la journée
						$.ajax({
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							data:\"object=dayEventGetter&selectedDate=\" + prevDate.format(\"YYYY-MM-DD\"),
							dataType:\"json\"
						}).success(function(data,statut){
								console.log(\"Appelle la fonction d'affichage\");
								dayView(data,planning);
							}
						);						
					}
				}
			);
				
			$(\"span.day-after\").on(\"click\",function(){
					// Récupère la valeur de la date courante et calcule la date précédente
					var currentDate = moment($(this).data(\"rel\"));
					usDate = $(this).data(\"rel\")
					var nextDate = currentDate.add(1,\"days\");
					
					// Affecte la nouvelle valeur à la date affichée
					$(\"span.init-date-content\").data(\"dateref\",nextDate.format(\"YYYY-MM-DD\"));
									
					affDate = nextDate.format(\"dddd D MMMM YYYY\");
					var planning = $(\"<a>\");
					planning.attr(\"href\",\"index.php?com=planningViewer&date=\" + nextDate.format(\"YYYY-MM-DD\"));
					planning.attr(\"title\",\"Voir le planning\");
					planning.text(affDate);
					//$(planning).appendTo($(\"span.init-date-content\")); //.html(affDate);
					
					$(this).data(\"rel\",nextDate.format(\"YYYY-MM-DD\"));
					$(\"span.day-before\").data(\"rel\",nextDate.format(\"YYYY-MM-DD\"));
					
					// Vérifie le statut d'activation de la date précédente
					if(nextDate.isAfter(moment())){
						console.log(\"La date est après \" + moment().format(\"DD MM YYYY\"));
						$(\"span.day-before\").removeClass(\"is-disabled\");
					} else {
						$(\"span.day-before\").addClass(\"is-disabled\");
					}
					// Indique le chargement de l'agenda
					$(\"div.init-date span.badge\").html(\"En cours...\");
					// Exécute le script de récupération des événements de la journée
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:\"object=dayEventGetter&selectedDate=\" + nextDate.format(\"YYYY-MM-DD\"),
						dataType:\"json\"
					}).success(function(data,statut){
							console.log(\"Appelle la fonction d'affichage\");
							dayView(data,planning);
						}
					);	
				}
			);
									
			// Définit la fonction de traitement des résultats
			(function($){
			 
				dayView = function(result,planning) {
					var multiple = 0;
					var dataRel = new String;
			";
				// Intègre le contrôle d'activation ou non des liens
				if(!\wp\Helpers\sessionHelper::getUserSession()->isLoggedIn()){
					$jQuery .= "var doLink = false;\n";
				} else {
					$jQuery .= "var doLink = true;\n";
				}
				$jQuery .= "// Affiche le nombre de données traitées
					// Stocke la date définie
					var dateRef = $(\"span.init-date-content\").data(\"dateref\");
						
					var nbEvent = result.nbEvent;
					$(\".init-date span.badge\").remove();
						
					var badge = $(\"<span>\");
					badge.addClass(\"badge\").text(nbEvent);
						
					$(\"span.init-date-content\").html(\"\");
						
					$(planning).appendTo($(\"span.init-date-content\"));
					$(badge).appendTo($(\"div.init-date\"));
										
					// Efface les contenus des événements
					$(\"ul.plage li.plage-group\").each(function(){
							$(this).children(\"span.event-content\").html(\"\");
							var dateTimeRef = moment(dateRef + \" \" + $(this).data(\"timeref\"));
							var badge = $(this).children(\"span.badge\");
							challenge(dateTimeRef,badge,doLink,false);
						}
					);
					$(\"ul.quarter li.plage-quarter\").each(function(){
							$(this).children(\"span.event-content\").html(\"\");
							var dateTimeRef = moment(dateRef + \" \" + $(this).data(\"timeref\"));
							var badge = $(this).children(\"span.badge\");
							challenge(dateTimeRef,badge,doLink,true);
						}
					);						
					
					if(nbEvent > 0){
						var strDate = result.date;
						var events = result.content; // Tous les événements de la journée
	
								
						$.each(events,function(key,event){
								// Récupère la ligne correspondante
								var target = $(\"span.event-content[data-time='\" + event.datarel + \"']\");
	
								// Calcule le premier lien sur l'événement
								var mainLink = $(\"<a>\");
								$(mainLink).attr(\"href\",event.link).attr(\"title\",event.description).text(event.titre);
								$(mainLink).appendTo(target);
							
								// Vérifie s'il existe d'autres événements sur cette plage horaire
								if(event.moreEvents != null){
									moreEvent = event.moreEvents;
									spanMore = $(\"<span>\");
									$(spanMore).addClass(\"more-event\");
									moreLink = $(\"<a>\");
									$(moreLink).attr(\"href\",moreEvent.link).attr(\"title\",\"Voir tous les événements\").text(\"et \" + moreEvent.nb + \" \" + moreEvent.titre);
									$(moreLink).appendTo(spanMore);
									$(spanMore).appendTo(target);
								}
							}
						);
					}
				}; // Fin de la fonction dayView
				
				// Challenge la date et l'heure du créneau par rapport à la date et l'heure courante
				challenge = function(dateTimeRef,badge,doLink,isQuarter){
					$(badge).removeAttr(\"role\");	
					if((dateTimeRef.format(\"YYYY-MM-DD HH:mm\") > moment().format(\"YYYY-MM-DD HH:mm\"))){
						if(doLink == true){
							$(badge).attr(\"role\",\"button\");
							console.log(\"Ajouter le lien : \" + doLink ? \"Oui\" : \"Non\");
							var link = $(\"<a>\");
							link
								.attr(\"href\",\"index.php?com=addEvent&context=INSERT&date=\" + dateTimeRef.format(\"DD/MM/YYYY\") + \"&heure=\" + dateTimeRef.format(\"HH:mm\"))
								.attr(\"title\",\"Ajouter un événement\");
							if(!isQuarter)
								link.text(dateTimeRef.format(\"HH:mm\"));
							else
								link.text(dateTimeRef.format(\"mm\"));
							$(badge).html(\"\");
							$(link).appendTo($(badge));
						} else {
							console.log(\"La valeur de doLink est fausse \" + doLink + \" le lien n'est pas fait\");
							if(!isQuarter)
								$(badge).html(\"\").html(dateTimeRef.format(\"HH:mm\"));
							else
								$(badge).html(\"\").html(dateTimeRef.format(\"mm\"));
						}
					} else {
						if(!isQuarter)
							$(badge).html(\"\").html(dateTimeRef.format(\"HH:mm\"));
						else
							$(badge).html(\"\").html(dateTimeRef.format(\"mm\"));
					}
				};
				
				// Définition de la fonction de contrôle d'évéments multiples sur une plage donnée
				isMultiple = function(events,strTime,mainId){
					var result = 0;
					$.each(events,function(key,event){
							if(event.debut == strTime){
								if(event.id != mainId){
									// Il y a un autre événement sur cette plage
									result++;
								}
							}
						}
					);
					return result;
				}; // Fin de la fonction isMultiple
			 
			})(jQuery);
			
			
			
		";
		
		$js->addPlugin("moment");
		$js->addScript("function", $jQuery);
			
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		return $this;
	}
	
}
?>