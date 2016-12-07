<?php
/**
 * @name setEvent.class.php Mise à jour d'un événement
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0.1
**/

namespace arcec;
 
class setEvent extends \wp\formManager\ajaxAdmin {
	
	/**
	 * Objet contenant l'événement courant
	 * @var object
	 */
	private $event;
	
	/**
	 * Collection de participants : Conseillers
	 * @var array
	 */
	private $conseillers;
	
	/**
	 * Collection de participants : Porteurs de projet
	 * @var array
	 */
	private $porteurs;
	
	/**
	 * Collection des matériels réservés
	 * @var array
	 */
	private $materiels;
	
	/**
	 * Détermine le statut de l'événement (événement dérivé d'un événement principal)
	 * @var boolean
	 */
	private $isChildren;
	
	/**
	 * Objet contenant les données de l'événement principal
	 * @var object
	 */
	private $masterEvent;
	
	/**
	 * Autres objets enfants dépendants
	 * @var array
	 */
	private $childrenEvents;
	
	/**
	 * Objet pour le contrôle des événements
	 * @var object
	 */
	private $checkEvent;
	
	/**
	 * Durée estimée en fonction du type d'événement
	 * @var DateTime
	 */
	private $timeInterval;
	
	/**
	 * Définit si l'événement en cours d'édition est passé
	 * @var boolean
	 */
	private $isPast;
	
	public function __construct(){
		$this->module = \wp\Helpers\stringHelper::lastOf(get_class($this) , "\\");
	
		$this->setId($this->module)
		->setName($this->module);
	
		$this->setCss("form-horizontal");
		$this->setCss("container-fluid");
	
		$this->setTemplateName("./ajaxAdmin.tpl");
	
		$this->mapper = new \arcec\Mapper\eventMapper();
		$this->mapper->setId(\wp\Helpers\httpQueryHelper::get("id"));
		$this->mapper->set($this->mapper->getNameSpace());
		$this->event = $this->mapper->getObject();
		
		// Définit les informations annexes autour de l'événement
		$this->isChildren = false;
		$this->masterEvent = null;
		$this->childrenEvents = null;
		
		$this->checkEvent();
		
		$this->setParticipants();

		$this->setMateriels();
		
		$this->set();
	
		\wp\Tpl\templateEngine::getEngine()->setVar("indexTitle","Agenda - " . $this->event->titre);

	}

	/**
	 * Définit le bouton d'annulation du formulaire
	 * @return \wp\formManager\Fields\linkButton
	 */
	public function getCancelBtn(){
		
		$button = new \wp\formManager\Fields\linkButton();
		$button->setId("btnCancel")
		->setTitle("Retour à la liste")
		->addAttribut("role","button")
		->setValue("./index.php?com=planningViewer&date=" . \wp\Helpers\dateHelper::toSQL($this->event->date, "d/m/y"))
		->setCss("btn")
		->setCss("btn-default")
		->setLabel("Retour")
		;
		return $button;
	}
	
	/**
	 * Contrôle la disponibilité d'un service d'une injection de dépendance
	 * @param object $service Dépendance injectée
	 * @return boolean
	 */
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
		->isDisabled(true)
		->setRIAScript()
		->setValue($this->event->date);
		
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();

		// Heure de début sous forme de slider
		$field = new \wp\formManager\Fields\timeRange();
		$definedHour = new \DateTime(\wp\Helpers\dateHelper::toSQL($this->event->date,"dd/mm/yyyy") . " " . $this->event->heuredebut);
		$min = new \DateTime(\wp\Helpers\dateHelper::toSQL($this->event->date,"dd/mm/yyyy") . " 07:00:00");
		$max = new \DateTime(\wp\Helpers\dateHelper::toSQL($this->event->date,"dd/mm/yyyy") . " 20:00:00");
		
		$field
			->setId("frmHeureDebut")
			->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heuredebut")
			->setLabel("Début")
			->setCss("col-sm-12",true)
			->setValue($this->event->heuredebut)
			->min($min)
			->max($max)
			->from($definedHour)
			->isDisabled($this->checkEvent->isPast())
			->addFunction("onFinish","timeChange")
			->setRIAScript();
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();

		/*
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmHeureDebut")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heuredebut")
		->setLabel("Début")
		->setCss("col-sm-12",true)
		->setValue($this->event->heuredebut)
		->isDisabled(true);
		$this->addToFieldset($field);
		
		$field = new \wp\formManager\Fields\text();
		$field->setId("frmHeureFin")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heurefin")
		->setLabel("Fin")
		->setCss("col-sm-12",true)
		->setValue($this->event->heurefin)
		->isDisabled(true);
		$this->addToFieldset($field);
		*/
		

		// Heure de fin sous forme de slider
		$field = new \wp\formManager\Fields\timeRange();
		$definedHour = new \DateTime(\wp\Helpers\dateHelper::toSQL($this->event->date,"dd/mm/yyyy") . " " . $this->event->heurefin);
		$field
		->setId("frmHeureFin")
		->setName($this->mapper->getTableName() . "." . $this->mapper->getColumnPrefix() . "heurefin")
		->setLabel("Fin")
		->setCss("col-sm-12",true)
		->setValue($this->event->heurefin)
		->min($min)
		->max($max)
		->from($definedHour)
		->isDisabled($this->checkEvent->isPast())
		->setRIAScript();
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Ajoute les interactions entre l'heure de début et l'heure de fin
		$this->clientRIA .= "
			// Contrôle les heures de début et les heures de fin en fonction de la durée estimée
			(function($){
			 
				timeChange = function() {
					// Recalcule l'heure de fin en fonction du type de l'événement
					var frDate = $(\"#frmDate\").val();
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts = frDate.split(regExp);
					var date = dateParts[2] + \"-\" + dateParts[1] + \"-\" + 	dateParts[0];
					var debut = $(\"#frmHeureDebut\").val();
					var fin = $(\"#frmHeureFin\").val();
					
					var mBegin = moment(date + \" \" + debut);
					var mFin = moment(date + \" \" + mFin);
					var offset = mBegin;
					offset.add(interval);
				
					console.log(\"Interval défini : \" + interval.humanize() + \" L'heure de fin doit être supérieure ou égale à \" + offset.format(\"DD/MM/YYYY HH:mm\"));

					if(mFin < offset){
						mFin = offset;
						var endSlider = $(\"#frmHeureFin\").data(\"ionRangeSlider\");
						endSlider.update(
							{
								from: +mFin.format(\"X\")
							}
						);
					}
				};
			 
			})(jQuery)
			
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
		->isDisabled($this->checkEvent->isPast())
		->setValue($this->event->titre)
		;
		$this->addToFieldset($field);
		
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
		->isDisabled($this->checkEvent->isPast())
		->setValue($this->event->objet)
		;
		$this->addToFieldset($field);
		
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
			->isDisabled($this->checkEvent->isPast())
			->setMapping($mapper,array("value" => "id", "content"=>array("titre")))
			->setValue($this->event->typeevent_id)
			;
				
			$this->addToFieldset($field);

			// Ajoute le script pour le calcul de l'heure de fin en cas de changement
			$this->clientRIA .= "
				// Vérifie le type d'événement, la date de début et la date de fin
				$(\"#" . $field->getId() . "\").on(\"change\",function(){
						if($(this).val() != 0){
							// Récupère l'intervalle de durée par rapport au type
							$.ajax({
									url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
									type:\"POST\",
									data:\"object=getDuree&content=\" + $(this).val(),
									dataType:\"json\"
								}
								).success(function(data,status){
									interval = moment.duration({\"hours\" : parseInt(data.heure),\"minutes\":parseInt(data.minute)});
									// Appelle la fonction de recalcul
									timeChange();
								}
							);
						}
					}
				);
			";
		}

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
			->isDisabled($this->checkEvent->isPast())
			->setMapping($mapper,array("value" => "id", "content"=>array("codification","libelle")))
			->parentMapper("acu",$mapper->getSchemeDetail("acu","mapper"),array("libellecourt","libellelong"))
			->setValue($this->event->bureau_id)
			;
		
			$this->addToFieldset($field);
			
			// Script de contrôle de disponibilité sur cette date, ce créneau horaire, sur ce lieu
			$this->clientRIA .= "
				$(\"#" . $field->getId() . "\").on(\"change\",function(){
						var originalLocation = " . $this->event->bureau_id . ";
						var lieu = $(\"#" . $field->getId() . "\").val();
						//var date = moment($(\"#frmDate\").val().replace();
						var frDate = $(\"#frmDate\").val();
						var RegEx = new RegExp(\"[/]+\", \"g\");
						var dateParts = frDate.split(RegEx);
						var date = dateParts[2] + \"-\" + dateParts[1] + \"-\" + dateParts[0];
						console.log(\"Date : \" + date + \" à partir de \" + frDate);
						var hDeb = moment.unix($(\"#frmHeureDebut\").val());
						var hFin = moment.unix($(\"#frmHeureFin\").val());
						var usDate = moment(date).format(\"YYYY-MM-DD\");
						var jsonDeb = hDeb.format(\"HH:mm\");
						var jsonFin = hFin.format(\"HH:mm\");
						console.log(\"Contrôle de validité pour le \" + usDate + \" de \" + jsonDeb + \" à \" + jsonFin +  \" sur le bureau : \" + lieu);
						// Appel Ajax pour le contrôle de disponibilité
						$.ajax({
							url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
							type:\"POST\",
							//data:\"object=eventChecker&selectedDate=\" + usDate + \"&hDeb=\" + hDeb + \"&hFin=\" + hFin + \"&bureau=\" + bureau,
							data:{
								object:\"eventChecker\",
								selectedDate: usDate,
								hDeb: jsonDeb,
								hFin: jsonFin,
								bureau: lieu,
								event:" . \wp\Helpers\httpQueryHelper::get("id") . "
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
					}
				);
			";
		}
		
		$field = new \wp\formManager\Fields\badgeGroup();
		$field
			->setId("grp-conseiller")
			->setLabel("Conseillers")
			->badgeClass("item-delete")
			->actionClass("icon-cross")
			->ajaxObject("itemDelete")
			->dataMapper(new \arcec\Mapper\paramCNSMapper())
			->mainMapper(new \arcec\Mapper\eventpersonMapper())
			->addCol("libellelong")
			->datas($this->conseillers)
			->toCollection()
			->setRIAScript()
		;
		$this->addToFieldset($field);
		
		$this->clientRIA .= $field->getRIAScript();
		
		// Bouton pour l'ajout d'un ou plusieurs conseiller
		$field = new \wp\formManager\Fields\button();
		$field
			->setId("btnAddCns")
			->setName("addCns")
			->setLabel("Ajouter des conseillers")
			->setCss("btn")
			->setCss("btn-success")
			->isDisabled($this->checkEvent->isPast())
			->setType("button");
		$this->addToFieldSet($field);
		
		// Script client pour remonter les conseillers disponibles sur ce créneau
		$this->clientRIA .= "
			// Ouvre la boîte de dialogue de sélection des conseillers disponibles
			$(\"#" . $field->getId() . "\").on(\"click\",function(){
					// Appelle le script de récupération des données
					var selectedDate = $(\"#frmDate\").val(); // Sélection au format fr
					var regExp = new RegExp(\"[/]+\", \"g\");
					var dateParts =selectedDate.split(regExp);
					var usDate = dateParts[2] + \"-\" + dateParts[1] + \"-\" + 	dateParts[0];					
					var hDeb = $(\"#frmHeureDebut\").val();
					var hFin = $(\"#frmHeureFin\").val();
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:{
							object:\"cnsGetter\",
							selectedDate:usDate,
							hDeb: hDeb,
							hFin: hFin,
							event:" . $this->event->id . "
						},
						dataType:\"json\"
					}).success(function(result){
							var data = result.data;
							var badge = null;
							var target = $(\"#select-persons div.content\");
							dialog = $(\"#select-persons\").dialog(
								{
									autoOpen:false,
									height:500,
									width:600,
									title:\"Ajouter des conseillers\",
									modal:true,
									buttons:{
										\"Fermer\":function(){
											dialog.dialog(\"close\");
										}
									},
									close:function(){
										$(\"#select-persons div.content\").html(\"\");
									}
								}
							);
									
							$(target).html(\"\");
							$.each(data,function(key,value){
									badge = createBadge(value.id,value.content,\"paramCNS\");
									$(badge).appendTo(target);
								}
							);
							// Ouvre la boîte modale
							dialog.dialog(\"open\");
						}
					);
				}
			);
			(function($){
				createBadge = function(id,content,rel) {
					// Créer les badges à partir des données retournées
					var container = $(\"<div>\");
					$(container).addClass(\"badge\").addClass(\"item-add\").attr(\"data-id\",id).attr(\"data-rel\",rel);
					var legend = $(\"<span>\");
					$(legend).addClass(\"legende\").text(content);
					var action = $(\"<span>\");
					$(action).addClass(\"action\").addClass(\"icon-plus\");
					$(legend).appendTo(container);
					$(action).appendTo(container);
					return container;
				};
			 
			})(jQuery)
		";
		
		$field = new \wp\formManager\Fields\badgeGroup();
		$field
		->setId("porteur")
		->setLabel("Porteurs")
		->badgeClass("item-delete")
		->actionClass("icon-cross")
		->ajaxObject("itemDelete")
		->dataMapper(new \arcec\Mapper\dossierMapper())
		->mainMapper(new \arcec\Mapper\eventpersonMapper())
		->addCol("prenomporteur")
		->addCol("nomporteur")
		->datas($this->porteurs)
		->toCollection()
		;
		$this->addToFieldset($field);

		// Bouton pour l'ajout d'un ou plusieurs porteurs
		$field = new \wp\formManager\Fields\button();
		$field
		->setId("btnAddPorteur")
		->setName("addPorteur")
		->setLabel("Ajouter des Porteurs")
		->setCss("btn")
		->setCss("btn-success")
		->isDisabled($this->checkEvent->isPast())
		->setType("button");
		$this->addToFieldSet($field);
		
		$field = new \wp\formManager\Fields\badgeGroup();
		$field
		->setId("materiel")
		->setLabel("Matériels")
		->badgeClass("item-delete")
		->actionClass("icon-cross")
		->ajaxObject("itemDelete")
		->dataMapper(new \arcec\Mapper\materielMapper())
		->mainMapper(new \arcec\Mapper\eventmaterielMapper())
		->addCol("libelle")
		->datas($this->materiels)
		->toCollection()
		;
		$this->addToFieldset($field);
		
		
		/**
		 * @todo Ajouter le fieldset pour la gestion des matériels
		
		$materiel = new \arcec\Mapper\materielMapper();
		if($materiel->count() > 0){
			$this->addFieldset("materiel");
			$this->defMateriel("materiel",$materiel);
			$this->pager->addPage("materiel");	
		}
		**/
		
		// Script de gestion des ajouts des personnes
		$this->clientRIA .="
			$(\"#select-persons div.content\").on(\"click\",\".icon-plus\",function(){
					badge = $(this).parent();
					var person = $(badge).data(\"id\");
					var type = $(badge).data(\"rel\");
					console.log(\"Ajouter \" + person + \" de \" + type);
					$.ajax({
						url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
						type:\"POST\",
						data:{
							object:\"addPerson\",
							id:person,
							type: type,
							event:" . $this->event->id . "
						},
						dataType:\"json\"
					}).success(function(result){
							data = result.data;
							var container = $(\"#grp-conseiller div.panel-body\");
							$(badge).remove();
							// Recrée un nouveau badge pour suppression
							var deleteBadge = $(\"<div>\");
							$(deleteBadge)
								.addClass(\"badge\")
								.addClass(\"item-delete\")
								.attr(\"data-id\",data.id);
							var legend = $(\"<span>\");
							$(legend).addClass(\"legend\").html(data.content);
							
							var action = $(\"<span>\");
							$(action).addClass(\"action\").addClass(\"icon-cross\");
							
							$(legend).appendTo(deleteBadge);
							$(action).appendTo(deleteBadge);
									
							// Ajoute le badge à la collection
							$(deleteBadge).appendTo(container);
						}
					);
				}
			);
		";
		
		// Traite la suppression de la donnée courante
		$this->clientRIA .= "
			$(\"#btnDelete\").on(\"click\",function(){
					// Récupère l'identifiant de l'événement à supprimer
					var eventId = $(this).data(\"id\");
					console.log(\"Suppression de l'événement : \" + eventId);
					// Définir le message du dialogue de suppression
					var dlgMessage = \"Etes-vous sûr de vouloir supprimer cet événement ?\";";
		if($this->checkEvent->isMaster()){
			$this->clientRIA .= "
				dlgMessage += \"Attention, tous les événements descendants seront aussi supprimés\";
			";
		}
		
		$this->clientRIA .= "
					// Définit le dialogue de suppression
					$(\"#dialog-confirm span#dialog-content\").html(dlgMessage);
					$(\"#dialog-confirm\").dialog(
						{
							resizable: false,
							height: \"auto\",
							width: 400,
							modal: true,
							buttons: {
								\"Oui\": function(){
									// Début de méthode
									// Déclenche la suppression définitive
									$.ajax({
										url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
										type:\"POST\",
										data:{
											object:\"deleteEvent\",
											eventId: eventId
										},
										dataType:\"json\"
									}).done(function(result){
											$(\"#dialog-confirm\").dialog(\"close\");
											// Redirige vers la page de création d'un événement
											location.replace(\"index.php?com=addEvent\");
										}
									)
									// Fin de méthode
								},
								\"Non\": function(){
										$(this).dialog(\"close\");
									}
							}
						}
					).dialog(\"open\");
				}
			);
		";
		
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");
		$js->addPlugin("jquery-ui");
		$js->addPlugin("jquery.maskedinput",true);
		$js->addPlugin("moment");
		$js->addPlugin("ion.rangeSlider");
		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		// Ajoute le script d'ouverture du datepicker et de la gestion du pager
		//$this->clientRIA .= $this->pager->getRIAScript($this->getTabId());
		
		/*
		// Ajoute le plug-in JS pour la gestion des dates
		$js = new \wp\htmlManager\js();
		$js->addPlugin("jquery.plugin",true);
		$js->addPlugin("jquery.datepick",true);
		$js->addPlugin("jquery.datepick-fr");

		
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		*/
		
		// Ajoute le script d'ouverture du datepicker
		$this->toControls();
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("jquery.datepick");
		$css->addSheet("jquery-ui");
		$css->addSheet("jquery-ui.structure");
		$css->addSheet("jquery-ui.theme");
		$css->addSheet("ion.rangeSlider");
		$css->addSheet("ion.rangeSlider.skinHTML5");
		
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
			$this->after();
			
			// Retourne à la création d'un événement
			$locationParams = array(
					"com" => "addEvent",
					"context" => "INSERT"
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
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->beforeInsert();
				break;
					
			case "upd":
				return $this->beforeUpdate();
				break;
					
			case "del":
				return $this->beforeDelete();
				break;
		}
	
		return;
	}
	
	protected function after(){
		switch(\wp\Helpers\urlHelper::context()){
			case "add":
				return $this->afterInsert();
				break;
					
			case "upd":
				return $this->afterUpdate();
				break;
					
			case "del":
				return $this->afterDelete();
				break;
		}
	
		return;
	}
	
	/**
	 * Retourne les participants sélectionnés
	 * @see \wp\formManager\admin::beforeInsert()
	 */
	protected function beforeInsert(){
		$participant = $this->getField("frmPersonnes");
		return $participant->getPostedData();
	}
	
	protected function beforeUpdate(){}
	protected function beforeDelete(){}
	
	protected function afterInsert(){
		if(sizeof($this->participants)){
			$participant = new \arcec\Mapper\eventpersonMapper();
			
			foreach($this->participants as $person){
				foreach($person as $mapper => $id){
					$participant->person = $id;
					$participant->mapper = $mapper;
					$participant->event_id = $this->tableId;
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
				if(sizeof($dates)){
					// Crée autant de ligne qu'il y a de dates à traiter avec les mêmes informations
					$currentMapper = new \arcec\Mapper\eventMapper();
					$currentMapper->setId($this->tableId);
					$currentMapper->set($currentMapper->getNameSpace());
					$event = $currentMapper->getObject();
					foreach ($dates as $date){
						$newEvent = clone $event;
						$newEvent->id = 0;
						$newEvent->date = $date->format("Y-m-d");
						$newEvent->parent = $this->tableId;
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
								$newPerson->save();
							}
						}
					}
				}
			}
		}
		// Traite le matériel associé le cas échéant
		$materiel = $this->getField("frmMateriel");
		$materiels = $materiel->getPostedData();
		if(sizeof($materiels)){
			$mapper = new \arcec\Mapper\eventmaterielMapper();
			foreach ($materiels as $id){
				$mapper->event_id = $this->tableId;
				$mapper->materiel_id = $id;
				$mapper->save();
			}
		}
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
		// Instancie un objet pour l'affichage des données
		$this->checkEvent = new \arcec\Event\updCheckEvent($this->event);
		
		// Récupère l'intervalle par rapport au type d'événement courant
		$type = $this->mapper->getSchemeDetail("typeevent_id","mapper");
		$type->setId($this->event->typeevent_id);
		$type->set($type->getNameSpace());
		if($type->getObject()->dureeestimee != ""){
			$this->timeInterval = new \DateTime("1970-01-01 " . $type->getObject()->dureeestimee);
		} else {
			$this->timeInterval = new \DateTime("1970-01-01 00:15");
		}
		// Ajoute la variable d'intervalle au script de gestion
		$this->clientRIA .= "
			var interval = moment.duration(\"" . $this->timeInterval->format("H:i:s") . "\");\n
		";
		
		/**
		 * @todo Afficher un message pour indiquer que l'événement passé
		 * 	ne peut pas être modifié, ni supprimé
		 */
		if($this->checkEvent->isPast()){
			$this->clientRIA .= "
				$(function(){
						$(\"#" . $this->module . " #btnSubmit\").prop(\"disabled\",true);
					}
				);
			";
		}
		

	}
	
	/**
	 * Définition des champs du fieldset Evénement
	 * @param string $fieldset ID du fieldset concerné
	 */
	private function defEvent($fieldset){
		

		
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
			$(\"#frmHeureDebut\").on(\"blur\",function(){
					// Vérifie si une sélection de type a été effectuée
					if($(\"#frmTypeEvent option:selected\").val() && $(\"#frmTypeEvent option:selected\").val() != 0){
						// Vérifie si une heure de fin a été saisie
						if($(\"#frmHeureFin\").val() == \"\"){
							// Calcule l'heure de fin en fonction de la durée estimée
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
	
	/**
	 * Ventile les participants à l'événements dans les collections spécifiées
	 */
	private function setParticipants(){
		$mapper = new \arcec\Mapper\eventpersonMapper();
		$mapper->searchBy("event_id", $this->event->id);
		$mapper->set($mapper->getNameSpace());
		
		if($mapper->getNbRows() > 0){
			foreach($mapper->getCollection() as $object){
				$factory = new \wp\Patterns\factory("\\arcec\\Mapper\\" . $object->mapper . "Mapper");
				$person = $factory->addInstance();
				$person->setId($object->person);
				$person->set($person->getNameSpace());
				if($object->mapper == "paramCNS"){
					$this->conseillers[] = array("row" => $object->id, "ref" => $person->getObject()->id);
				} else {
					$this->porteurs[] = array("row" => $object->id, "ref" => $person->getObject()->id);
				}
			}
		}
	}
	
	/**
	 * Ventile la collection des matériels réservés pour l'événement
	 */
	private function setMateriels(){
		$mapper = new \arcec\Mapper\eventmaterielMapper();
		$mapper->searchBy("event_id", $this->event->id);
		$mapper->set($mapper->getNameSpace());

		if($mapper->getNbRows() > 0){
			foreach($mapper->getCollection() as $materiel){
				$this->materiels[] = $materiel->materiel_id;
			}
		}
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