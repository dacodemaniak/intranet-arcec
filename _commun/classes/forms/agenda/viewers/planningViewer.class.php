<?php
/**
 * @name planningViewer.class.php Services d'affichage du planning ARCEC avec fullCalendar
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
 * @version 1.1 Ne pas r�cup�rer les �v�nements marqu�s comme invisibles
**/

namespace arcec;

class planningViewer extends \arcec\Agenda\agendaViewer implements \wp\Tpl\template,\wp\formManager\RIA {

	/**
	 * Modèle à utiliser pour l'affichage du viewer
	 * @var string
	 */
	private $templateName;
	
	/**
	 * Identifiant de la div contenant le calendrier
	 * @var string
	**/
	private $calendarId;
	
	/**
	 * Vue par défaut à l'initialisation du calendrier
	 * @var string
	 */
	private $defaultView;
	
	/**
	 * Définit la date ou la plage par défaut à afficher
	 * @var string
	 */
	private $defaultPlage;
	
	/**
	 * Objet de formulaire pour le filtrage des informations
	 * @var object
	 */
	private $filters;
	
	public function __construct(){
		$this->setTemplateName("planning");
		$this->calendarId = "planning";
		
		
		$this->beginAt("09:00");
		$this->endAt("17:00");
		
		$this->defaultView();
		
		$this->setFilterForm();
		
		$this->setRIAScript();	
	}
	
	public function calendarId($id=null){
		if(!is_null($id)){
			$this->calendarId = $id;
			return $this;
		}
		return $this->calendarId;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Agenda\agendaViewer::plages()
	 */
	public function plages(){}
	
	/**
	 * (non-PHPdoc)
	 * @see \arcec\Agenda\agendaViewer::process()
	 */
	public function process(){}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\formManager\RIA::setRIAScript()
	 */
	public function setRIAScript(){
		$js = new \wp\htmlManager\js();
		
		$jQuery = "
			$(\"#" . $this->calendarId . "\").fullCalendar(
				{
					// Langue par défaut
					\"lang\":\"fr\",
					
					// Format des heures
					timeFormat: \"HH:mm\",
					axisFormat: \"HH:mm\",
					
					// Affichage des n° de semaine
					weekNumbers:true,
					
					// Ne pas afficher le bloc allDay
					allDaySlot:false,
					
					// Libellés des boutons
					buttonText:{
					    today:    \"Aujourd'hui\",
					    month:    \"Mois\",
					    week:     \"Semaine\",
					    day:      \"Jour\"
					},
					
					// Plages horaires
					businessHours:{
						start: \"" . $this->beginAt() . "\",
						end: \"" . $this->endAt() . "\",
						dow: [ 1, 2, 3, 4, 5 ]
					},
					
					// En-tête
					header: {
						left: \"prev,next today\",
						center: \"title\",
						right: \"month,agendaWeek,agendaDay\"
					},
					
					// Affichage par défaut
					defaultDate: \"" . $this->defaultPlage . "\",
					defaultView: \"" . $this->defaultView . "\",
							
					// Remonte les informations du planning JSON / Ajax
					events:
						{
				            url:\"" . \wp\framework::getFramework()->getAjaxDispatcher() . "\",
				            cache:false,
				            type: \"POST\",
				            
				            /*
				            data: {
				                object: \"eventFetcher\",
				                namespace: \"_arcec_Agenda_\",
				            	paramCNS:  $(\"#frmCNS\").val(),
				            	paramType: $(\"#frmTypeEvent\").val(),
				            	paramBureau:$(\"#frmBureau\").val()
				            },
				            */
				            data: function(){
				            	return {
					                object: \"eventFetcher\",
					                namespace: \"_arcec_Agenda_\",
					            	paramCNS:  $(\"#frmCNS\").val(),
					            	paramType: $(\"#frmTypeEvent\").val(),
					            	paramBureau:$(\"#frmBureau\").val()				            		
				            	}
				            },
				            		
				            error: function() {
				                alert(\"Impossible de remonter les événements de la plage spécifiée !\");
            				}
						},
				        
				      // Fonction callback d'affichage du détail
					  eventRender: function(event, element) {
							$(element).qtip({content:event.description});
    				  },
				      lazyFetching:false				            		
				        
				}
			);
		";
		
		$js->addPlugin("moment");
		$js->addPlugin("fullcalendar");
		$js->addPlugin("jquery.qtip");
		
		$js->addScript("function", $jQuery);
			
		\wp\Tpl\templateEngine::getEngine()->addContent("js",$js);
		
		
		// Ajoute les CSS
		$css = new \wp\htmlManager\css();
		$css->addSheet("fullcalendar");
		$css->addSheet("jquery.qtip");
		\wp\Tpl\templateEngine::getEngine()->addContent("css",$css);
		
		return $this;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Tpl\template::setTemplateName()
	 */
	public function setTemplateName($name){
		$this->templateName = "agenda/" . $name . ".tpl";
		return $this;		
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\Tpl\template::getTemplateName()
	 */
	public function getTemplateName(){
		return $this->templateName;
	}
	
	/**
	 * Retourne le formulaire de filtrage de l'agenda
	 */
	public function filters(){
		return $this->filters;
	}
	
	private function defaultView(){
		if(!is_null($date = \wp\Helpers\urlHelper::context("date"))){
			$this->defaultView = "agendaDay";
			$this->defaultPlage = $date;		
		}	
	}
	/**
	 * Définit le formulaire de filtrage de l'agenda
	 */
	private function setFilterForm(){
		$this->filters = new \arcec\Agenda\filter();
		$this->filters->supportId($this->calendarId);
	}
}
?>