<?php
/**
 * @name materielMapper.class.php Mapper sur la table dbPrefix_materiel Matériels disponibles pour les conseillers
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
**/

namespace arcec\Mapper;

class materielMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du type
	 * @property string $libelle Libellé du matériel / dénomination
	 * @property string $description Description (optionnelle)
	**/
	
	/**
	 * Instancie un nouveau Mapper sur la table concernée
	 * @todo Ajouter les dépendances sur les événements
	 */
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "materiel_";
		
		$this->alias = "mat";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = _DB_PREFIX_ . "event";
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
				"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
				"libelle" => array("type" => "varchar","size"=>150,"null"=>false,"index"=>1),
				"description" => array("type" => "text", "null" => true)
		);
	}
	
	/**
	 *
	 * @param string $attrName : Nom de l'attribut / colonne
	 * @param mixed $attrValue : Valeur de l'attribut / colonne
	 */
	public function __set($attrName,$attrValue){
		if(!property_exists($this,$attrName) && $this->in($attrName)){
			$this->{$attrName} = $attrValue;
			return true;
		}
		return false;
	}
	
	public function __get($attrName){
		if(!property_exists($this,$attrName)){
			return $this->{$attrName};
		}
		return;
	}
	
	public function getCheckBox(){
		return $this->setCheckBox();
	
	}
	private function setCheckBox(){
		$checkbox = new \wp\formManager\Fields\checkbox();
		$checkbox->setId("id_" . $this->id)
		->setName("id_" . $this->id)
		->setValue($this->id)
		->isDisabled(!$this->checkIntegrity($this->id))
		;
		return $checkbox;
	}
	
	/**
	 * (non-PHPdoc)
	 * @see \wp\dbManager\dbMapper::usage()
	 **/
	protected function usage(){}
}
?>