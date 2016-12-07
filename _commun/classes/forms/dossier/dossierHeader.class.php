<?php
/**
 * @name dossierHeader.class.php En-tête d'affichage des formulaires suivi de dossiers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/
namespace arcec;

class dossierHeader implements \wp\Tpl\template{
	
	/**
	 * Identifiant du dossier
	 * @var int
	 */
	private $id;
	
	/**
	 * Mapper sur le dossier
	 * @var object
	 */
	private $mapper;
	
	/**
	 * Nom du modèle à charger
	 * @var string
	**/
	private $templateName;
	
	/**
	 * Ligne de dossier courante
	 * @var object
	 */
	private $activeRecord;
	
	/**
	 * Instancie un nouvel objet d'en-tête de dossier
	 * @param int $id Identifiant du dossier courant
	 */
	public function __construct($id){
		$this->setTemplateName("header");
		
		$this->id = $id;
		$this->mapper = new \arcec\Mapper\dossierMapper();
		$this->mapper->setId($id);
		$this->mapper->set($this->mapper->getNamespace());
		
		$this->activeRecord = $this->mapper->getObject();
	}
	
	/**
	 * Définit le nom du modèle à charger
	 * @see \wp\Tpl\template::setTemplateName()
	 */
	public function setTemplateName($templateName){
		$this->templateName = "dossier/" . $templateName . ".tpl";
		return $this;
	}
	
	public function getTemplateName(){
		return $this->templateName;
	}
	
	/**
	 * Retourne une information de la ligne courante
	 * @param string $dataName
	**/
	public function get($dataName){
		if($this->mapper->getSchemeDetail($dataName)){
			return $this->activeRecord->{$dataName};
		}
		return;
	}
	
	public function getParent($dataName){
		if(strpos($dataName,"cns") !== false){
			$mapperName = "cns";
		} else {
			$mapperName = $dataName;
		}
		$mapperClassName = "\\arcec\\Mapper\\param". strtoupper($mapperName) . "Mapper";
		$factory = new \wp\Patterns\factory($mapperClassName);
		$mapper = $factory->addInstance();
		$mapper->setId($this->activeRecord->{$dataName});
		$mapper->set("\\arcec\\Mapper\\");
		
		$datas = $mapper->getObject();
		
		return $datas->libellelong; 
	}
	
	/**
	 * Compare le libellé court d'un paramètre à une valeur définie
	 * @param string $paramType Type de paramètre à retourner
	 * @param int $id Identifiant du paramètre
	 * @param string $code
	 * @return boolean
	**/
	public static function getParentCode($paramType,$id,$code){
		$mapperClassName = "\\arcec\\Mapper\\param". strtoupper($paramType) . "Mapper";
		
		$factory = new \wp\Patterns\factory($mapperClassName);
		
		$mapper = $factory->addInstance();
		$mapper->setId($id);
		$mapper->set("\\arcec\\Mapper\\");
		
		$datas = $mapper->getObject();

		if($datas->libellecourt == $code){
			return true;
		}
		
		return false;
	}
}
 ?>