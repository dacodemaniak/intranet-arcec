<?php
/**
 * @name paramdefinitionMapper.class.php : Mapping de la table prefix_paramdefinition
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class paramdefinitionMapper extends \wp\dbManager\dbMapper {
	/**
	 * @property int $id Identifiant du paramètre
	 * @property string $code Code du paramètre
	 * @property string $nom Nom du paramètre
	 * @property int $type_table_param_id Identifiant du type de paramètre (voir typetableparamMapper)
	**/
	

	
	public function __construct(){
		$classParts = explode("\\",__CLASS__);
		$className = array_pop($classParts);
		
		$this->namespace = implode("\\",$classParts);
		
		$this->className = substr($className,0,strpos($className,"Mapper"));
		
		$this->columnPrefix = "param_definition_";
		
		$this->alias = "def";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = _DB_PREFIX_ . "param_base";
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"code" => array("type" => "varchar","size"=>20,"null"=>false,"index"=>1),
			"nom" => array("type" => "varchar","size" => 255, "null" => true),
			"type_table_param_id" => array("type" => "int","foreign_key" => true, "parent_table" => "typetableparam", "null" => false)
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