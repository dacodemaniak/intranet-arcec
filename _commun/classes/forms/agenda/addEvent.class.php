<?php
/**
 * @name addEvent.class.php Création d'un nouvel événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0.1
**/

namespace arcec;
 
class addEvent extends \wp\formManager\admin {
	
	/**
	 * Définit les participants pour l'événement courant
	 * @var array
	 */
	private $participants;
	
	/**
	 * Objet "pager" pour dérouler les fieldsets
	 * @var object
	 **/
	private $pager;
	
	/**
	 * Objet pour le contrôle des événements
	 * @var object
	 */
	private $checkEvent;
	
	/**
	 * Objet identifiant le compte (Conseiller) identifié
	 * @var \arcec\Mapper\paramCNSMapper
	 */
	private $account;
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
	
		$this->setId($this->module)
		->setName($this->module);
	
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
	
		$this->setTemplateName("./agenda/adminPager.tpl");
	
		$this->mapper = new \arcec\Mapper\eventMapper();

		$this->setPager();
		
		$this->set();
	
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Agenda - Nouvel événement");
		
		// Déterminer le nombre d'événements déjà programmés pour ce jour
		$this->checkEvent();
		
		$this->participants = array();
		
		if(!$this->isValidate()){
			$this->setAction(array("com" => $this->module,"context"=>\wp\Helpers\urlHelper::toContext()));
		} else {
			$this->process();
		}
	}
	
	public function serviceExists($service){
		if(method_exists($this, $service)){
			return true;
		}
		return false;
	}
	
	/**
	 * Retourne le vérificateur d'événement défini
	 * @return object
	 */
	public function eventChecker(){
		return $this->checkEvent;
	}
	
	/**
	 * Définit les fieldsets du formulaire de création d'un événement
	 * @see \wp\formManager\admin::set()
	 */
	protected function set(){
		
		// Récupère le compte éventuel associé à l'utilisateur connecté
		$user = \wp\Helpers\sessionHelper::getUserSession();
		$this->account = $user->getAccount();
		
		$this->addFieldset("event");
		$this->defEvent("event");
		$this->pager->addPage("event");
		
		$this->addFieldset("participant");
		$this->defPersonne("participant");
		$this->pager->addPage("participant");
		
		$this->addFieldset("repetition");
		$this->defRepetition("repetition");
		$this->pager->addPage("repetition");
		
		
		
		/**
		 * @todo Ajouter le fieldset pour la gestion des matériels
		**/
		$materiel = new \arcec\Mapper\materielMapper();
		if($materiel->count() > 0){
			$this->addFieldset("materiel");
			$this->defMateriel("materiel",$materiel);
			$this->pager->addPage("materiel");	
		}
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker et de la gestion du pager
		$this->clientRIA .= $this->pager->getRIAScript($this->getTabId());

		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		$js->addPlugin("jquery.maskedinput",true);
		$js->addPlugin("moment");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery.datepick");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
		
	}
	
	public function getPager(){
		return $this->pager;
	}
	
	/**
	 * Traite le formulaire
	 * @see \wp\formManager\admin::process()
	 * @todo Créer la méthode pour définir l'URL d'erreur (rester sur place et afficher le message)
	 */
	protected function process(){
		$this->participants = $this->before();

		if($this->tableId = parent::process()){
			$eventDate = new \DateTime($this->getField("frmDate")->getPostedData());
			
			// Retourne à la création d'un événement
			/**
			$locationParams = array(
					"com" => "addEvent",
					"context" => "INSERT"
			);
			**/
			$this->after();
			
			$locationParams = array(
				"com" => "planningViewer",
				"date" => $eventDate->format("Y-m-d")
			);
			$location = \wp\Helpers\urlHelper::setAction($locationParams);
			
			#die("Redirection vers : " . $location);
			
			header("Location:" . $location);
			
			return;
		}
	
		$this->sucess = false;
		$this->failedMsg = "Une erreur est survenue lors de l'enregistrement";
		header("Location:" . \wp\Helpers\urlHelper::toURL($this->module));
	}
	
	protected function before(){
		return $this->beforeInsert();
	}
	
	protected function after(){
		return $this->afterInsert();
	}
	
	/**
	 * Retourne les participants sélectionnés
	 * @see \wp\formManager\admin::beforeInsert()
	 */
	protected function beforeInsert(){
		$participant = $this->getField("frmPersonnes");
		#begin_debug
		#var_dump($participant->getPostedData());
		#echo "<br />\n";
		#end_debug
		return $participant->getPostedData();
	}
	
	protected function beforeUpdate(){}
	protected function beforeDelete(){}
	
	protected function afterInsert(){
		if(sizeof($this->participants)){
			$participant = new \arcec\Mapper\eventpersonMapper();
			
			foreach($this->participants as $person){
				#begin_debug
				#var_dump($person);
				#echo "<br />\n";
				#end_debug
				foreach($person as $mapper => $id){
					$participant->id = 0;
					$participant->person = $id;
					$participant->mapper = $mapper;
					$participant->event_id = $this->tableId;
					#begin_debug
					#echo "Enregistre le participant $id du mapper $mapper pour l'événement " . $this->tableId . "<br />\n";
					#end_debug
					$participant->save();
				}
			}
		}

		// Gérer les éventuelles répétitions
		if(($repetition = $this->getField("frmRepetition")->getPostedData()) != 0){
			// En fonction du type de répétition programmé
			$dateDebut = new \DateTime($this->getField("frmDate")->getPostedData());
			$dateFin = new \DateTime($this->getField("frmDateFin")->getPostedData());
			
			$dates = array();
			
			if(\wp\Helpers\dateHelper::compare($dateDebut,$dateFin) == 2){
				switch($repetition){
					case 1: // Quotidien
						// La date de fin saisie est bien supérieure à la date de début
						$dates = $this->getDates($dateDebut,$dateFin,new \DateInterval("P1D"));
					break;
					
					case 2: // Hebdo
						// Vérifier qu'il y a bien au moins 7 jours entre les deux dates
						$interval = $dateDebut->diff($dateFin);
						if($interval->format("%a") >= 7){
							// Détermine les jours de la semaine pour lesquels l'événement doit se produire
							$jourSemaines = $this->getJoursSemaine();
							
							if(sizeof($jourSemaines) == 0){
								// On répète juste au jour courant
								$jourSemaines[] = $dateDebut->format("N");
							}
							// La date de début de récupération doit être le début de la semaine suivante
							$beginNextWeek = \wp\Helpers\dateHelper::beginNextWeek($dateDebut);
							
							$dates = $this->getDates($beginNextWeek,$dateFin,new \DateInterval("P1D"),$jourSemaines);
						}
					break;
					
					case 3: // Mensuel
						$interval = $dateDebut->diff($dateFin);
						if($interval->format("%m") >= 1){
							// Détermine les jours de la semaine pour lesquels l'événement doit se produire
							$jourSemaines = $this->getJoursSemaine();
								
							if(sizeof($jourSemaines) == 0){
								// On répète juste au jour courant
								$jourSemaines[] = $dateDebut->format("N");
							}
							$dates = $this->getDates($dateDebut,$dateFin,new \DateInterval("P1M"),$jourSemaines);
						}
					break;
					
					case 4: // Trimestriel
						$interval = $dateDebut->diff($dateFin);
						if($interval->format("%m") >= 3){
							// Détermine les jours de la semaine pour lesquels l'événement doit se produire
							$jourSemaines = $this->getJoursSemaine();
						
							if(sizeof($jourSemaines) == 0){
								// On répète juste au jour courant
								$jourSemaines[] = $dateDebut->format("N");
							}
							$dates = $this->getDates($dateDebut,$dateFin,new \DateInterval("P3M"),$jourSemaines);
						}
					break;
					
					case 5: // Semestriel
						$interval = $dateDebut->diff($dateFin);
						if($interval->format("%m") >= 6){
							// Détermine les jours de la semaine pour lesquels l'événement doit se produire
							$jourSemaines = $this->getJoursSemaine();
						
							if(sizeof($jourSemaines) == 0){
								// On répète juste au jour courant
								$jourSemaines[] = $dateDebut->format("N");
							}
							$dates = $this->getDates($dateDebut,$dateFin,new \DateInterval("P6M"),$jourSemaines);
						}
					break;
					
					case 6: // Annuel
						$interval = $dateDebut->diff($dateFin);
						if($interval->format("%m") >= 12){
							// Détermine les jours de la semaine pour lesquels l'événement doit se produire
							$jourSemaines = $this->getJoursSemaine();
						
							if(sizeof($jourSemaines) == 0){
								// On répète juste au jour courant
								$jourSemaines[] = $dateDebut->format("N");
							}
							$dates = $this->getDates($dateDebut,$dateFin,new \DateInterval("P12M"),$jourSemaines);
						}
					break;
				}
				
				#begin_debug
				#foreach ($dates as $date){
				#	echo "Répéter le " . $date->format("Y-m-d") . "<br />\n";
				#}
				#die();
				#end_debug
				/**
				 * @todo Contrôler pour chaque date le chevauchement éventuel
				**/
				if(sizeof($dates)){
					$materiel = $this->getField("frmMateriel");
					$materiels = $materiel->getPostedData();
					
					// Crée autant de ligne qu'il y a de dates à traiter avec les mêmes informations
					$currentMapper = new \arcec\Mapper\eventMapper();
					$currentMapper->setId($this->tableId);
					$currentMapper->set($currentMapper->getNameSpace());
					$event = $currentMapper->getObject();
					
					$controller = new \arcec\Event\eventController();
					
					foreach ($dates as $date){
						$newEvent = clone $event;
						$newEvent->id = 0;
						$newEvent->date = $date->format("Y-m-d");
						$newEvent->parent = $this->tableId;
						
						// Contrôler la disponibilité de la date avec les paramètres concernés
						
						$controller->setEvent($newEvent);
						if(!$controller->officeOccupied()){
							$newId = $newEvent->save();
							
							// Reporter les participants aussi
							$personnes = new \arcec\Mapper\eventpersonMapper();
							$personnes->searchBy("event_id",$this->tableId);
							$personnes->set($personnes->getNameSpace());
							if($personnes->getNbRows() > 0){
								foreach($personnes->getCollection() as $personne){
									$newPerson = clone $personne;
									$newPerson->id = 0;
									$newPerson->event_id = $newId;
									$controller->setPerson($newPerson);
									if(!$controller->personOccupied()){
										$newPerson->save();
									}
								}
							}
							
							// Traite le matériel associé le cas échéant
							if(sizeof($materiels)){
								$mapper = new \arcec\Mapper\eventmaterielMapper();
								foreach ($materiels as $id){
									$mapper->event_id = $this->newId;
									$mapper->materiel_id = $id;
									$controller->setMateriel($mapper);
									if(!$controller->materielOccupied()){
										$mapper->save();
									}
								}
							}
						}
					}
					// Après traitement, on stocke les éventuelles erreurs
					$controller->toSession();
					//die();
				}
			}
		}
		return $eventDate;
	}
	
	protected function afterUpdate(){}
	protected function afterDelete(){}

	private function setPager(){
		$this->pager = new \wp\htmlManager\pager();
		$this->pager->setId("porteur-pager")
		->setTemplateName("pager")
		->setNavClass("nav")
		->setListClass("pagination");
	}
	
	private function checkEvent(){
		$this->checkEvent = new \arcec\Event\checkEvent();
		
		if(\wp\Helpers\httpQueryHelper::get("date") !== false){
			$theDate = \wp\Helpers\dateHelper::toSQL(\wp\Helpers\httpQueryHelper::get("date"), "dd/mm/yyyy");
		} else {
			$theDate = \wp\Helpers\dateHelper::toDay("Y-m-d");
		}
		
		
		if(\wp\Helpers\httpQueryHelper::get("heure") !== false){
			$theTime = \wp\Helpers\httpQueryHelper::get("heure");
		} else {
			$currentDateTime = \wp\Helpers\dateHelper::today(null,true);
			$theTime = \wp\Helpers\timeHelper::closestQuarter($currentDateTime->format("H:i"));
		}
		$this->checkEvent
			->setDate($theDate)
			->setDebut($theTime)
			->process()
		;
	}
	
	/**
	 * Définition des champs du fieldset Evénement
	 * @param string $fieldset ID du fieldset concerné
	 */
	private function defEvent($fieldset){
		
		// Ajoute le créateur de l'événement
		$mapper = new \arcec\Mapper\paramCNSMapper();
		$mapper->addInactive(false);
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			$field
				->setId("frmCreateur")
				->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "createur")
				->setLabel("Créateur")
				->setCss("control-label",true)
				->setCss("col-sm-3",true)
				->setCss("col-sm-9")
				->setCss("form-control")
				->setGroupCss("col-sm-12")
				->isRequired(true)
				->setMapping($mapper,array("value" => "id", "content"=>array("libellecourt","libellelong")))
				->setValue(is_null($this->account) ? 0 : $this->account->id);
			$this->addToFieldSet($field,$fieldset);
			
			$this->clientRIA .= "
				$(\"#" . $field->getId() . "\").on(\"change\",function(){
						var checkbox = $(\"#frmParticipe\");
						if($(this).val() != 0){
							checkbox.removeProp(\"disabled\");
							checkbox.prop(\"checked\",\"checked\");
							checkbox.prop(\"data-rel\",$(this).val());
							participant(true,$(this).val(),$(\"#" . $field->getId() . " option:selected\").text());
						} else {
							checkbox.removeProp(\"checked\");
							checkbox.prop(\"disabled\",\"disabled\");
							participant(false,$(checkbox).data(\"rel\"),$(\"#" . $field->getId() . " option:selected\").text());
							checkbox.removeProp(\"data-rel\");
						}
					}
				);
				
				// Gestion de la boîte à cocher
				$(\"#frmParticipe\").on(\"change\",function(){
						if($(this).is(\":checked\")){
							participant(true,$(\"#frmCreateur option:selected\").val(),$(\"#frmCreateur option:selected\").text());
						} else {
							participant(false,$(\"#frmCreateur option:selected\").val(),$(\"#frmCreateur option:selected\").text())
						}
					}
				);
				
				// Définition de la fonction d'ajout d'une ligne
				(function($){
						participant = function(add,id,content){
							// La ligne est-elle déjà présente dans le tableau de résultat
							var tbody = $(\"#resultat tbody\");	
							if(add){
								if(!objectExists(id)){
									var line = $(\"<tr>\");
									var colCheck = $(\"<td>\");
									var checkBox = $(\"<input>\");
									checkBox.prop(\"type\",\"checkbox\")
										.prop(\"name\",\"Conseiller_\" + id)
										.prop(\"checked\",\"checked\")
										.addClass(\"item-select\");
									$(checkBox).appendTo(colCheck);
									$(colCheck).appendTo(line);
										
									// Libellé complet
									var colContent = $(\"<td>\");
									$(colContent).text(content);
									$(colContent).appendTo(line);
										
									// Type de participant
									var type = $(\"<td>\");
									$(type).text(\"Conseiller\");
									$(type).appendTo(line);
										
									// Ajoute la ligne au corps du tableau
									$(line).appendTo(tbody);
								}
							} else {
								if(objectExists(id)){
									var selector = \"Conseiller_\" + id;
									var object = $(\"[name=selector]\");
									$(object).parent().parent().remove();
								}
							}
						};
						objectExists = function(id){
							var name = \"Conseiller_\" + id;
							console.log(\"Cherche le conseiller : \" + name);
							$(\"#resultat tbody tr\").each(function(){
									var firstCol =  $(this).find(\"td:eq(0)\");
									var checkbox = $(firstCol).find(\"input:first\");
									console.log(\"Nom : \" + checkbox.attr(\"name\") + \" <=> \" + name);
									if(checkbox.attr(\"name\") == name){
										return true;
									}
								}
							);
							return false;
						}";
						if(!is_null($this->account)){
							$this->clientRIA .= "
								var checkbox = $(\"#frmParticipe\");
								checkbox.removeProp(\"disabled\");
								checkbox.prop(\"checked\",\"checked\");
								checkbox.prop(\"data-rel\"," . $this->account->id . ");
								participant(true," . $this->account->id . ",\"" . $this->account->libellelong . "\");
							";
						}						
					$this->clientRIA .= "}
				)(jQuery)
			";
			// Script de gestion de la boîte participants
			
			// Ajoute la boîte à cocher déterminant si le créateur est aussi participant
			$field = new \wp\formManager\Fields\checkbox();
			$field
				->setId("frmParticipe")
				->setName("participe_evenement")
				->setLabel("Le créateur participe à l'événement")
				->setCss("control-label",true)
				->setCss("col-sm-12",true)
				->setCss("col-sm-3")
				->setCss("form-control")
				->setGroupCss("col-sm-12")
				->isDisabled(is_null($this->account) ? true : false)
				->isChecked(is_null($this->account) ? false : true);
			$this->addToFieldSet($field,$fieldset);
		}
		
		// Date de l'événement
		$field = new \wp\formManager\Fields\datePicker();
		$field->setId("frmDate")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "date")
		->setLabel("Date")
		->widthClass("col-sm-3")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("col-sm-6")
		->isRequired()
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->setTriggerId("date")
		->setMinDate()
		->addEvent("onSelect",null)
		->setRIAScript()
		->setValue(\wp\Helpers\httpQueryHelper::get("date") ? \wp\Helpers\httpQueryHelper::get("date") : $this->mapper->getSchemeDetail("date","default"));
		
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Définir le gestionnaire d'événement sur le changement de date
		/**
			* @todo Modifier les plages horaires de sélection s'il s'agit de la date du jour qui est sélectionnée
		 */
		$this->clientRIA .= "
			$(\"#" . $field->getId() . "\").on(\"change\",function(dateContent){
					var selectedDate = $(this).val(); // Sélection au format fr
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts =selectedDate.split(regExp);
					var usDate = dateParts[2] + \"-\" + dateParts[1] + \"-\" + 	dateParts[0];					
					var hDeb = $(\"#frmHeureDebut\").val();
					var hFin = $(\"#frmHeureFin\").val();
					var bureau = $(\"#frmBureau option:selected\").val();
					
					console.log(\"Changement de date pour \" + selectedDate);
					
					// Exécute l'appel Ajax
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						//data:\"object=eventChecker&selectedDate=\" + usDate + \"&hDeb=\" + hDeb + \"&hFin=\" + hFin + \"&bureau=\" + bureau,
						data:{
							object:\"eventChecker\",
							selectedDate:\" + usDate + \",
							hDeb:\" + hDeb + \",
							hFin:\" + hFin + \",
							bureau:\" + bureau + \"
						},
						dataType:\"json\"
					}).success(function(data,statut){
							$(\"div.alert p\").html(data.content);
								
							// Gère le type de l'alerte et l'état du bouton d'ajout
							if(data.statut == 0){
								$(\"div.alert\").removeClass(\"alert-info\").addClass(\"alert-danger\");
								$(\"#btnSubmit\").attr(\"disabled\",\"disabled\");
							} else {
								$(\"div.alert\").removeClass(\"alert-danger\").addClass(\"alert-info\");
								$(\"#btnSubmit\").removeAttr(\"disabled\");
							}
						}
					);
					
					// Met à jour l'éventuelle date de fin de répétition de l'événement
					//if($(\"#frmRepetition option:selected\").val() != 0){
						
						var dateDeb = moment().set({
								\"year\":dateParts[2],
								\"month\":dateParts[1] + 1,
								\"date\":dateParts[0]
							}
						);
						var endDate = moment().set({
								\"year\":dateParts[2],
								\"month\":dateParts[1] + 1,
								\"date\":dateParts[0]
							}
						);
	
						var duration = null;
										
						console.log(\"Date de début de l'événement :  \" + dateDeb.format(\"DD/MM/YYYY\"));
								
						switch(parseInt($(\"#frmRepetition option:selected\").val())){
							case 1: // Renouvellement quotidien
								duration = moment.duration(1, \"days\");
							break;
							
							case 2: // Renouvellement hebdomadaire
								duration = moment.duration(1, \"weeks\");
							break;
								
							case 3: // Renouvellement mensuel
								duration = moment.duration(1, \"months\");
							break;
							
							case 4: // Renouvellement trimestriel
								duration = moment.duration(3, \"months\");
							break;
								
							case 5: // Renouvellement semestriel
								duration = moment.duration(6, \"months\");
							break;
								
							case 6: // Renouvellement annuel
								duration = moment.duration(1, \"years\");
							break;
							
							default:
							break;
						}
						if(duration != null){
							console.log(\"Une durée a été calculée \");
							endDate = dateDeb.add(duration);
						}
								
						console.log(\"Date de fin de répétition : \" + endDate.format(\"DD/MM/YYYY\"));
						// Affecte la date de fin à la date définie pour la répétition
						$(\"#frmDateFin\").val(endDate.format(\"DD/MM/YYYY\"));
								
						// Change la date minimum...
						$(\"#frmDateFin\").datepick(\"option\", \"minDate\", endDate.format(\"DD/MM/YYYY\"));
								
						// Contrôle s'il s'agit de la date du jour
						var today = new Date();
						var year = today.getFullYear();
						var month = today.getMonth() + 1;
						if(month < 10){
								month = \"0\" + parseInt(month);
						}
						var day = today.getDate();
						if(day < 10){
								day = \"0\" + parseInt(day);
						}						
						var strDay = day + \"/\" + month + \"/\" + year;
						console.log(\"Date du jour : \" + strDay);

						if(strDay == selectedDate){
							// Calculer les heures passées... pour les désactiver
							var disableRange = [\"09:00\",\"17:00\"];
							$(\"#frmHeureDebut\").timepicker(\"option\",
								{
									\"disableTimeRanges\": disableRange
								}
							);
						}
					//}
				}
			);
		";
		
		
		// Titre de l'événement
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmTitre")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "titre")
			->setLabel("Titre")
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setCss("col-sm-9")
			->isRequired()
			->setCss("form-control")
			->setGroupCss("col-sm-12")
		;
		$this->addToFieldset($field,$fieldset);
		
		// Objet
		$field = new \wp\formManager\Fields\textarea();
		$field->setId("frmObjet")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "objet")
		->setLabel("Objet")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("col-sm-9")
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		;
		$this->addToFieldset($field,$fieldset);
		
		// Type de l'événement
		$mapper = new \arcec\Mapper\typeeventMapper();
		
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\popup();
			
			$field->setId("frmTypeEvent")
			->setName($this->mapper->getTableName() . "." . "typeevent_id")
			->setLabel("Type d'événement")
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setCss("col-sm-9")
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isRequired(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("titre")))
			;
			
			$this->addToFieldset($field,$fieldset);
			
			// Ajoute le script pour le calcul de l'heure de fin en cas de changement
			$this->clientRIA .= "
				// Vérifie le type d'événement, la date de début et la date de fin
				$(\"#" . $field->getId() . "\").on(\"change\",function(){
						if($(this).val() != 0){
							// Contrôle l'heure de début
							if($(\"#frmHeureDebut\").val() != \"\"){
								// Contrôle l'heure de fin
								if($(\"#frmHeureFin\").val() == \"\"){
									// Calcule l'heure de fin en fonction de la durée estimée
									$.ajax({
											url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
											type:\"POST\",
											data:\"object=getDuree&content=\" + $(this).val(),
											dataType:\"json\"
										}
									).success(function(data,status){
											var regExp = new RegExp(\"[:]+\", \"g\");
											var debutParts = $(\"#frmHeureDebut\").val().split(regExp);
											
											var momentDeb = moment(debutParts[0] + debutParts[1],\"hmm\");
											console.log(\"Heure de début : \" + momentDeb.format(\"HH:mm\"));
											var duration = moment.duration({\"hours\" : parseInt(data.heure),\"minutes\":parseInt(data.minute)});
											
											var momentFin = momentDeb.add(duration);
											
											$(\"#frmHeureFin\").val(momentFin.format(\"HH:mm\"));
											$(\"#frmHeureFin\").removeAttr(\"disabled\");		
													
										}
									);									
								} // Fin de contrôle de l'heure de fin
							}
						}
					}
				);
			";
		}
				
		// Heure de début
		/*
		$field = new \wp\formManager\Fields\hourMinute();
		$field->setId("frmHeureDebut")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heuredebut")
		->setLabel("Heure de début")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->widthClass("col-sm-3")
		->setSeparator(":")
		->isRequired()
		->setRIAScript()
		->setValue(\wp\Helpers\httpQueryHelper::get("heure") ? \wp\Helpers\httpQueryHelper::get("heure") : \arcec\Agenda\agendaViewer::currentStepTime(15,"M"))
		;
		*/
		$field = new \wp\formManager\Fields\timePicker();
		$field->setId("frmHeureDebut")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heuredebut")
		->setLabel("Heure de début")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->widthClass("col-sm-3")
		->isRequired()
		->setValue(\wp\Helpers\httpQueryHelper::get("heure") ? \wp\Helpers\httpQueryHelper::get("heure") : \arcec\Agenda\agendaViewer::currentStepTime(15,"M"))
		->setRIAScript()
		;
		
		$this->addToFieldset($field,$fieldset);
		$this->clientRIA .= $field->getRIAScript();
		
		// Ajoute le calcul automatique de l'heure de fin en fonction du type d'événement
		$this->clientRIA .= "
			// Calcule l'heure de fin si nécessaire
			$(\"#frmHeureDebut\").on(\"change\",function(){
					// Vérifie si une sélection de type a été effectuée
					if($(\"#frmTypeEvent\").val() != 0){
						$(\"#frmHeureFin\").val(\"\"); // Efface l'heure de fin... pour repartir à 0
						// Vérifie si une heure de fin a été saisie
						if($(\"#frmHeureFin\").val() == \"\"){
							// Calcule l'heure de fin en fonction de la durée estimée
							console.log(\"Récupère la durée estimée de l'événement\");
							$.ajax({
									url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
									type:\"POST\",
									data:\"object=getDuree&content=\" + $(\"#frmTypeEvent option:selected\").val(),
									dataType:\"json\"
								}
							).success(function(data,status){
									var regExp = new RegExp(\"[:]+\", \"g\");
									var debutParts = $(\"#frmHeureDebut\").val().split(regExp);
									
									var momentDeb = moment(debutParts[0] + debutParts[1],\"hmm\");
									console.log(\"Heure de début : \" + momentDeb.format(\"HH:mm\"));
									var duration = moment.duration({\"hours\" : parseInt(data.heure),\"minutes\":parseInt(data.minute)});
									
									var momentFin = momentDeb.add(duration);
									
									$(\"#frmHeureFin\").val(momentFin.format(\"HH:mm\"));
									$(\"#frmHeureFin\").removeAttr(\"disabled\");		
											
								}
							);
						} else {
							// Une heure de fin a été définie, contrôler la validité

							var regExp = new RegExp(\"[:]+\", \"g\");
							
							var debutParts = $(\"#frmHeureDebut\").val().split(regExp);

							var finParts =  $(\"#frmHeureDebut\").val().split(regExp);
											
							var momentDeb = moment(debutParts[0] + debutParts[1],\"hmm\");
							var momentFin = moment(finParts[0] + finParts[1],\"hmm\");

							if(momentDeb.isAfter(momentFin)){
								// Retouche l'heure de fin en ajoutant une heure à partir de l'heure de début
								var duration = moment.duration({\"hours\" : 1});
									
								var momentFin = momentDeb.add(duration);											
							}
									
							$(\"#frmHeureFin\").val(momentFin.format(\"HH:mm\"));
							$(\"#frmHeureFin\").removeAttr(\"disabled\");
												
						}
					} else {
						// Pas de type d'événement, on contrôle les données saisies, on ajoute par défaut 1h à l'heure de début si nécessaire
						if($(\"#frmHeureFin\").val() == \"\"){
							var regExp = new RegExp(\"[:]+\", \"g\");
							var debutParts = $(\"#frmHeureDebut\").val().split(regExp);
									
							var momentDeb = moment(debutParts[0] + debutParts[1],\"hmm\");
							
							var duration = moment.duration({\"hours\" : 1});
									
							var momentFin = momentDeb.add(duration);
									
							$(\"#frmHeureFin\").val(momentFin.format(\"HH:mm\"));
							$(\"#frmHeureFin\").removeAttr(\"disabled\");
						}
					}
				}
			);
		";

		// Définir le gestionnaire d'événement sur le changement de date
		$this->clientRIA .= "
			$(\"#" . $field->getId() . "\").on(\"change\",function(dateContent){
					var selectedDate = $(\"#frmDate\").val(); // Sélection au format fr
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts =selectedDate.split(regExp);
					var usDate = dateParts[2] + \"_\" + dateParts[1] + \"_\" + 	dateParts[0];
					var hDeb = $(\"#frmHeureDebut\").val();
					var hFin = $(\"#frmHeureFin\").val();
					var bureau = $(\"#frmBureau option:selected\").val();
			
					console.log(\"Changement de date pour \" + selectedDate);
			
					// Exécute l'appel Ajax
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:\"object=eventChecker&selectedDate=\" + usDate + \"&hDeb=\" + hDeb + \"&hFin=\" + hFin + \"&bureau=\" + bureau,
						dataType:\"json\"
					}).success(function(data,statut){
							$(\"div.alert p\").html(data.content);
								
							// Gère le type de l'alerte et l'état du bouton d'ajout
							if(data.statut == 0){
								$(\"div.alert\").removeClass(\"alert-info\").addClass(\"alert-danger\");
								$(\"#btnSubmit\").attr(\"disabled\",\"disabled\");
							} else {
								$(\"div.alert\").removeClass(\"alert-danger\").addClass(\"alert-info\");
								$(\"#btnSubmit\").removeAttr(\"disabled\");
							}
						}
					);
				}
			);
		";
		
		// Heure de fin
		$field = new \wp\formManager\Fields\hourMinute();
		$field->setId("frmHeureFin")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heurefin")
		->setLabel("Heure de fin")
		->setCss("control-label",true)
		->setCss("col-sm-3",true)
		->setCss("form-control")
		->setGroupCss("col-sm-12")
		->widthClass("col-sm-3")
		->setSeparator(":")
		->isRequired()
		->isDisabled(true)
		->setRIAScript()
		;
			
		$this->addToFieldset($field,$fieldset);
		$this->clientRIA .= $field->getRIAScript();
		
		// Lieu
		$mapper = new \arcec\Mapper\bureauMapper();
		if($mapper->count() > 0){
			$field = new \wp\formManager\Fields\optgroup();
			$field->setId("frmBureau")
			->setName($this->mapper->getTableName() . "." . "bureau_id")
			->setLabel("Lieu")
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setCss("col-sm-9")
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->isRequired(true)
			->setMapping($mapper,array("value" => "id", "content"=>array("codification","libelle")))
			->parentMapper("acu",$mapper->getSchemeDetail("acu","mapper"),array("libellecourt","libellelong"))
			;
				
			$this->addToFieldset($field,$fieldset);

			// Définir le gestionnaire d'événement sur le changement de date
			$this->clientRIA .= "
			$(\"#" . $field->getId() . "\").on(\"change\",function(dateContent){
					var selectedDate = $(\"#frmDate\").val(); // Sélection au format fr
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts =selectedDate.split(regExp);
					var usDate = dateParts[2] + \"_\" + dateParts[1] + \"_\" + 	dateParts[0];
					var hDeb = $(\"#frmHeureDebut\").val();
					var hFin = $(\"#frmHeureFin\").val();
					var bureau = $(\"#frmBureau option:selected\").val();
			
					console.log(\"Changement de date pour \" + selectedDate);
			
					// Exécute l'appel Ajax
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:\"object=eventChecker&selectedDate=\" + usDate + \"&hDeb=\" + hDeb + \"&hFin=\" + hFin + \"&bureau=\" + bureau,
						dataType:\"json\"
					}).success(function(data,statut){
							$(\"div.alert p\").html(data.content);
								
							// Gère le type de l'alerte et l'état du bouton d'ajout
							if(data.statut == 0){
								$(\"div.alert\").removeClass(\"alert-info\").addClass(\"alert-danger\");
								$(\"#btnSubmit\").attr(\"disabled\",\"disabled\");
							} else {
								$(\"div.alert\").removeClass(\"alert-danger\").addClass(\"alert-info\");
								$(\"#btnSubmit\").removeAttr(\"disabled\");
							}
						}
					);
				}
			);
		";
		}
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Nouvel événement");
		$object->setCss(array("fieldset","active"));
	}
	
	private function defPersonne($fieldset){
		$field = new \wp\formManager\Fields\toTable();
		$cnsMapper = new \arcec\Mapper\paramCNSMapper();
		$pdpMapper = new \arcec\Mapper\dossierMapper();
		
		$field->setId("frmPersonnes")
			->setName("participants")
			->setLabel("Rechercher")
			->multiple(true)
			->supportName("searchName")
			->supportId("searchId")
			->tableId("resultat")
			->toUpper(true)
			->headers(array("prenom-nom"=>"Prénom Nom","type" => "Type"))
			->addMapper($cnsMapper,"Conseiller","paramCNS")
			->addMapper($pdpMapper,"PdP")
			->addSearchCol("paramCNS","libellelong","contient")
			->addSearchCol($pdpMapper->getClassName(),"nomporteur","commence")
			->addCol("paramCNS","libellelong")
			->addCol($pdpMapper->getClassName(),array("prenomporteur","nomporteur"))
			->inject($this->account)
			->setRIAScript()
		;
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		// Ajoute le contrôle de disponibilité du conseiller à l'heure donnée, sur le bureau éventuel + contrôle de capacité totale
		$this->clientRIA .= "
			$(\"fieldset#participant\").on(\"change\",\".item-select\",function(){
					// S'agit-il d'un conseiller
					var regExp = new RegExp(\"[_]+\", \"g\");
					var person = $(this).attr(\"name\").split(regExp);
					if(person[0] == \"Conseiller\" && $(this).is(\":checked\")){
						var object = $(this);
						var selectedDate = $(\"#frmDate\").val(); // Sélection au format fr
						var regExp = new RegExp(\"[/]+\", \"g\");
						var dateParts =selectedDate.split(regExp);
						var usDate = dateParts[2] + \"-\" + dateParts[1] + \"-\" + 	dateParts[0];					
						var hDeb = $(\"#frmHeureDebut\").val();
						var hFin = $(\"#frmHeureFin\").val();
						var bureau = $(\"#frmBureau option:selected\").val();
						console.log(\"Contrôle la disponibilité du conseiller \" + person[1] + \" sur ce créneau.\");
						// Exécute l'appel Ajax
						$.ajax({
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							//data:\"object=eventChecker&selectedDate=\" + usDate + \"&hDeb=\" + hDeb + \"&hFin=\" + hFin + \"&bureau=\" + bureau,
							data:{
								object:\"eventChecker\",
								selectedDate:usDate,
								hDeb: hDeb,
								hFin: hFin,
								bureau: bureau,
								conseiller: person[1]
							},
							dataType:\"json\"
						}).success(function(data,statut){
								$(\"div.alert p\").html(data.content);
									
								// Gère le type de l'alerte et l'état du bouton d'ajout
								if(data.statut == 0){
									$(\"div.alert\").removeClass(\"alert-info\").addClass(\"alert-warning\");
									// Décoche la boîte courante
									$(object).removeProp(\"checked\");
									//$(\"#btnSubmit\").attr(\"disabled\",\"disabled\");
								} else {
									$(\"div.alert\").removeClass(\"alert-warning\").addClass(\"alert-info\");
									//$(\"#btnSubmit\").removeAttr(\"disabled\");
								}
							}
						);
					} else {
					
					}
				}
			);
		";
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Participants");
		$object->setCss(array("fieldset","inactive"));
	}
	
	private function defRepetition($fieldset){
		
		// Sélection du type de répétition
		$field = new \wp\formManager\Fields\popup();
		$types = array(
			array("value" => 1,"content" => "Jours"),
			array("value" => 2,"content" => "Semaines"),
			array("value" => 3,"content" => "Mois"),
			array("value" => 4,"content" => "Trimestres"),
			array("value" => 5,"content" => "Semestres"),
			array("value" => 6,"content" => "Ans"),
		);
		$field
			->setId("frmRepetition")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "typerepetition")
			->setLabel("Répéter")
			->setHeaderLine(0,"Tous les...")
			->setDatas($types)
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setCss("col-sm-9")
			->setCss("form-control")
			->setGroupCss("col-sm-12")
		;
		$this->addToFieldset($field,$fieldset);
		// Ajoute le script de contrôle de la liste
		$this->clientRIA .= "
			// Gestion des sélections des jours en fonction de la liste
			$(\"#" . $field->getId() . "\").on(\"change\",function(){
					var selected = $(this).val();
					$(\".two-state\").each(function(){
							if(selected > 1){
								// Activer tous les jours jusqu'au vendredi
								if($(this).data(\"toggle\") < 6){
									$(this).attr(\"aria-disabled\",\"false\");
								}
							} else {
								// Désactiver tous les jours et supprimer les valeurs associées
								if($(this).data(\"toggle\") < 6){
									$(this).attr(\"aria-disabled\",\"true\");
									var field = $(\"#\" + $(this).data(\"rel\"));
									field.val(\"\");
								}
							}
						}
					);
					
					// Détermine par défaut la date de fin de l'événement
					var selectedDate = $(\"#frmDate\").val(); // Sélection au format fr
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts = selectedDate.split(regExp);
					console.log(\"jour :\" + dateParts[0] + \" Mois : \" + dateParts[1]);
					var dateDeb = moment().set({
							\"year\":dateParts[2],
							\"month\":dateParts[1],
							\"date\":dateParts[0]
						}
					);
					
					var duration = null;
					var endDate = dateDeb;
					console.log(\"Répétition : \" + $(this).val() + \" à partir de : \" + dateDeb.format(\"DD-MM-YYYY\"));			
					switch(parseInt($(this).val())){
						case 1: // Renouvellement quotidien
							duration = moment.duration(1, \"days\");
						break;
							
						case 2: // Renouvellement hebdomadaire
							duration = moment.duration(1, \"weeks\");
						break;
								
						case 3: // Renouvellement mensuel
							duration = moment.duration(1, \"months\");
						break;
							
						case 4: // Renouvellement trimestriel
							duration = moment.duration(3, \"months\");
						break;
								
						case 5: // Renouvellement semestriel
							duration = moment.duration(6, \"months\");
						break;
								
						case 6: // Renouvellement annuel
							duration = moment.duration(1, \"years\");
						break;
							
						default:
						break;
					}
					if(duration != null)
						var endDate = dateDeb.add(duration);
					
					// Affecte la date de fin à la date définie pour la répétition
					$(\"#frmDateFin\").val(endDate.format(\"DD/MM/YYYY\"));
					
					// Change la date minimum...
					$(\"#frmDateFin\").datepick(\"option\", \"minDate\", endDate.format(\"DD/MM/YYYY\"));
				}
			);
		";
				
		// Jours de répétitions
		$field = new \wp\formManager\Fields\group();
		$field->setId("frmJours")
			->setName("jours")
			->setLabel("Jours")
			->setCss("control-label",true)
			->setCss("col-sm-12",true)
			->isRequired()
			->setCss("col-sm-6")
			->isReadOnly(true)
			->isDisabled(true)
			->setTemplateName("listgroup");
		;
		
		// Ajoute les objets au groupe...
		$ts = new \wp\formManager\Fields\twoState();
		$ts
			->setId("frmLundi")
			->setName("lundi")
			->setLabel("Lu")
			->fullTitle("Lundi")
			->isDisabled($field->getDisabledStatut() && true)
			->isSingle(false)
			->defaultValue(1)
			->addAttribut("aria-checked",false)
			->alwaysDisabled()
			->setRIAScript();
		$field->add($ts);
		
		// Définit le code de gestion de la sélection
		$this->clientRIA .= $ts->getRIAScript();
		
		$ts = new \wp\formManager\Fields\twoState();
		$ts
			->setId("frmMardi")
			->setName("mardi")
			->setLabel("Ma")
			->fullTitle("Mardi")
			->isDisabled($field->getDisabledStatut() && true)
			->isSingle(false)
			->addAttribut("aria-checked","false")
			->alwaysDisabled()
			->defaultValue(2);
		$field->add($ts);
		
		$ts = new \wp\formManager\Fields\twoState();
		$ts
		->setId("frmMercredi")
		->setName("mercredi")
		->setLabel("Me")
		->fullTitle("Mercredi")
		->isDisabled($field->getDisabledStatut() && true)
		->isSingle(false)
		->addAttribut("aria-checked","false")
		->alwaysDisabled()
		->defaultValue(3);
		$field->add($ts);

		$ts = new \wp\formManager\Fields\twoState();
		$ts
		->setId("frmJeudi")
		->setName("jeudi")
		->setLabel("Je")
		->fullTitle("Jeudi")
		->isDisabled($field->getDisabledStatut() && true)
		->isSingle(false)
		->addAttribut("aria-checked","false")
		->alwaysDisabled()
		->defaultValue(4);
		$field->add($ts);

		$ts = new \wp\formManager\Fields\twoState();
		$ts
		->setId("frmVendredi")
		->setName("vendredi")
		->setLabel("Ve")
		->fullTitle("Vendredi")
		->isDisabled($field->getDisabledStatut() && true)
		->isSingle(false)
		->addAttribut("aria-checked","false")
		->alwaysDisabled()
		->defaultValue(5);
		$field->add($ts);

		$ts = new \wp\formManager\Fields\twoState();
		$ts
		->setId("frmSamedi")
		->setName("samedi")
		->setLabel("Sa")
		->fullTitle("Samedi")
		->isDisabled(true)
		->isSingle(false)
		->addAttribut("aria-checked","false")
		->alwaysDisabled()
		->defaultValue(6);
		$field->add($ts);

		$ts = new \wp\formManager\Fields\twoState();
		$ts
		->setId("frmDimanche")
		->setName("dimanche")
		->setLabel("Di")
		->fullTitle("Dimanche")
		->isDisabled(true)
		->isSingle(false)
		->addAttribut("aria-checked","false")
		->alwaysDisabled()
		->defaultValue(7);
		$field->add($ts);
		
		$this->addToFieldset($field,$fieldset);
		
		// Date de fin de répétition
		$field = new \wp\formManager\Fields\datePicker();
		$field
			->setId("frmDateFin")
			->setName("dateFin")
			->setLabel("Jusqu'au")
			->widthClass("col-sm-3")
			->setCss("control-label",true)
			->setCss("col-sm-3",true)
			->setCss("col-sm-6")
			->isRequired()
			->setCss("form-control")
			->setGroupCss("col-sm-12")
			->setTriggerId("toEnd")
			->setMinDate()
			->addEvent("onSelect",null)
			->setValue(\wp\Helpers\dateHelper::today("d/m/Y"))
			->setRIAScript();
		$this->addToFieldset($field,$fieldset);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Répétition");
		$object->setCss(array("fieldset","inactive"));
	}
	
	private function defMateriel($fieldset,$materiel){
		// Ajoute la liste des matériels
		$field = new \wp\formManager\Fields\checkTable();
		$field->setId("frmMateriel")
		->setName("materiels")
		->setHeaders(
				array("libelle" => "Matériel")
		)
		->caption("Matériels à emprunter")
		->source($materiel);
		
		$this->addToFieldset($field,$fieldset);		
		// Définit les classes spécifiques pour ce fieldset
		$object = $this->getFieldset($fieldset);
		$object->setLegend("Matériel");
		$object->setCss(array("fieldset","inactive"));		
	}
	/**
	 * Retourne le tableau des dates comprises entre la date de début et la date de fin
	 * en ajoutant $dateInterval à chaque occurrence
	 * @param \DateTime $debut
	 * @param \DateTime $fin
	 * @return array Objets dates définis
	 */
	private function getDates($debut,$fin,$dateInterval,$daysOfWeek=null){
		
		$dates = new \DatePeriod($debut, $dateInterval, $fin);
		
		foreach($dates as $date){
			if($indice > 0){
				// Exclusion des week-ends
				if($date->format("N") < 6){
					if(is_null($daysOfWeek)){
						$addDates[] = $date;
					} else {
						if(in_array($date->format("N"),$daysOfWeek)){
							#begin_debug
							#echo "La date fait partie des dates à retenir<br />\n";
							#end_debug
							$addDates[] = $date;
						} else {
							if($this->getField("frmRepetition")->getPostedData() >= 3){
								// Récursivité sur les dates pour récupérer les autres dates éventuelles
								$end = new \DateTime($date->format("Y-m-d"));
								$end->modify("+7 day");
								$innerDates = new \DatePeriod($date, new \DateInterval("P1D"),$end);
								$innerIndice = 0;
								foreach ($innerDates as $inner){
									if($inner->format("N") < 6){
										if(is_null($daysOfWeek)){
											$addDates[] = $inner;
										} else {
											if(in_array($inner->format("N"),$daysOfWeek)){
												$addDates[] = $inner;
											}
										}
									}
									$innerIndice++;
								}
							}
						}
					}
				}
			}
			$indice++;
		}
		
		// Traite la dernière date
		if($fin->format("N") < 6){
			if(is_null($daysOfWeek)){
				$addDates[] = $fin;
			} else {
				if(in_array($fin->format("N"),$daysOfWeek)){
					$addDates[] = $fin;
				}				
			}
		}
		
		return $addDates;
	}
	
	private function getJoursSemaine(){
		if(($jour = $_POST["lundi"]) != ""){
			$joursSemaine[] = $jour;
		}
		
		if(($jour = $_POST["mardi"]) != ""){
			$joursSemaine[] = $jour;
		}

		if(($jour = $_POST["mercredi"]) != ""){
			$joursSemaine[] = $jour;
		}
		
		if(($jour = $_POST["jeudi"]) != ""){
			$joursSemaine[] = $jour;
		}
		
		if(($jour = $_POST["vendredi"]) != ""){
			$joursSemaine[] = $jour;
		}
		return $joursSemaine;
	}
}
?>