<?php
/**
 * @name typeTableParamMapper.class.php : Mapping de la table prefix_typetableparam
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class typetableparamMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du type de paramètre
	 * @property string $type Type de paramètre
	 * @property string $description Description du paramètre
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "type_table_param_";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = _DB_PREFIX_ . "paramdefinition";
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"type" => array("type" => "varchar","size"=>20,"null"=>false,"index"=>1),
			"description" => array("type" => "varchar","size" => 255, "null" => true)
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