<?php
/**
 * @name dossierMapper.class.php : Mapping de la table prefix_dossier
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Mapper
 * @version 1.0
 **/
namespace arcec\Mapper;

class dossierMapper extends \wp\dbManager\dbMapper {
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
		
		$this->columnPrefix = "dossier_";
		
		$this->alias = "dos";
		
		
		$this->defineScheme();
		
		$this->dependencies[] = array(
				_DB_PREFIX_ . "docpoteur",
				_DB_PREFIX_ . "suivi",
				_DB_PREFIX_ . "rapport"
		);
		
		$this->namespace = __NAMESPACE__;
	}
	
	private function defineScheme(){
		$this->scheme = array(
			"id" => array("type" => "int", "index" => "primary", "autoincrement" => true, "null" => false),
			"nomporteur" => array("type" => "varchar","size"=>32,"null"=>false,"index"=>1),
			"prenomporteur" => array("type" => "varchar","size" => 32, "null" => true),
			"cns" =>  array("type" => "int","null"=>false),
			"acu" =>  array("type" => "int","null"=>false),
			"etd" =>  array("type" => "int","null"=>false),
			"edo" =>  array("type" => "int","null"=>false),
			"ca" =>  array("type" => "int","null"=>true),
			"or" =>  array("type" => "int","null"=>true),
			"datecreation" => array("type" => "date","index"=>1,"null"=>false),
			"adhesion" => array("type"=>"tinyint","default"=>1),
			"dateadhesion" => array("type" => "date","index"=>1,"null"=>true),
			"datecotisation" => array("type" => "date","index"=>1,"null"=>true),
			"cotisationversee" => array("type"=>"tinyint","default"=>0),
			"adhesionclub" => array("type"=>"tinyint","default"=>0),
			
			// Données du porteur
			"porteurdatenaissance" => array("type" => "date","null"=>false),
			"porteuradrnum" => array("type" => "varchar","size"=>5,"null"=>true),
			"porteurvoi" =>  array("type" => "int","null"=>false),
			"porteuradr1" => array("type" => "varchar","size" => 75, "null" => true),
			"porteuradr2" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurcodepostal" => array("type" => "varchar","size" => 10, "null" => false),
			"porteurville" => array("type" => "varchar","size" => 75, "null" => false),
			"porteurtelfixe" => array("type" => "varchar","size" => 20, "null" => false),
			"porteurtelportable" => array("type" => "varchar","size" => 20, "null" => false),
			"porteuremail" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurdpl" =>  array("type" => "int","null"=>false),
			"porteurspecdiplome" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurprs" =>  array("type" => "int","null"=>false),
			"porteuremailpresc" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurnompresc" => array("type"=>"varchar","size"=>75, "null"=>true),
			"porteursis" =>  array("type" => "int","null"=>false),
			"porteursitsocialeparticuliere" => array("type" => "text","null"=>true),
			"porteurprecisionsis" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurdateinscpoleemploi" => array("type" => "date","null"=>true),
			"porteurcnt" =>  array("type" => "int","null"=>false),
			"porteuranciennetecnt" => array("type" => "varchar","size" => 75, "null" => true),
			"porteurresumeprojet" => array("type" => "text","null"=>true),
			"porteurdateaccueil" => array("type" => "date","null"=>false),
			"porteurgeneseprojet" => array("type" => "text","null"=>true),
			"porteurexppro" => array("type" => "text","null"=>true),
			"porteurdemande" => array("type" => "text","null"=>true),
			"porteurdatedepotdossier" => array("type" => "date","null"=>false),
			"porteurdateacceptationdossier" => array("type" => "date","null"=>false),
			"porteurnat" =>  array("type" => "int","null"=>false),
			"porteursif" =>  array("type" => "int","null"=>false),
			"porteurrim" =>  array("type" => "int","null"=>false),
			"porteurrin" =>  array("type" => "int","null"=>true),
			"porteurtyp" =>  array("type" => "int","null"=>true),
			"porteurscp" =>  array("type" => "int","null"=>true),
			"porteurmet" => array("type" => "int", "null" => true,"default" => 0),
			"porteurprg" => array("type" => "int", "null" => true,"default" => 0),
			"porteuretu" => array("type" => "int", "null" => true,"default" => 0),
			"porteurautremetier" => array("type" => "varchar","size" => 150, "null" => true),
			"porteurcnscoord" =>  array("type" => "int","null"=>true),
			"porteurhandicap" => array("type"=>"tinyint","default"=>0),
			"porteursexe" => array("type" => "char","size"=>1,"null"=>false,"default"=>"M"),
			"porteureligibleaccre" => array("type"=>"tinyint","default"=>0),
			"porteureligiblenacre" => array("type"=>"tinyint","default"=>0),
			"porteurdatetransmissionnacre" => array("type" => "date","null" => true),
			"porteurdesttransmissionnacre" => array("type" => "varchar","size" => 150,"null" => true),
			"porteureligiblefongecif" => array("type"=>"tinyint","default"=>0),
			"porteurdatetransfongecif" => array("type" => "date","null" => false),
			"porteurdestdossierfongecif" => array("type" => "varchar","size" => 150,"null" => true),
				
			// Données de l'entreprise
			"entraisonsociale" => array("type"=>"varchar","size"=>75, "null"=>true),
			"entsiret" => array("type"=>"varchar","size"=>15, "null"=>true),
			"enttelephone" => array("type"=>"varchar","size"=>20, "null"=>true),
			"enttelportable" => array("type"=>"varchar","size"=>20, "null"=>true),
			"entnumrue" => array("type" => "varchar","size"=>10,"null"=>true),
			"entvoi" =>  array("type" => "int","null"=>false),
			"entadresse1" => array("type" => "varchar","size" => 60, "null" => true),
			"entadresse2" => array("type" => "varchar","size" => 60, "null" => true),
			"entcodepostal" => array("type" => "varchar","size" => 10, "null" => false),
			"entville" => array("type" => "varchar","size" => 75, "null" => false),
			"entfju" =>  array("type" => "int","null"=>false,"default"=>0),
			"entsts" =>  array("type" => "int","null"=>false,"default"=>0),
			"entopf" =>  array("type" => "int","null"=>false,"default"=>0),
			"entrem" =>  array("type" => "int","null"=>false,"default"=>0),
			"entadherentctgestion" => array("type"=>"tinyint","default"=>0),
			"entsuivipcpostac" => array("type"=>"tinyint","default"=>0),
			"entdaterdvpc" => array("type" => "date","null"=>true),
			"entconvention" => array("type" => "varchar","size" => 255, "null" => true),
			"entconvention" => array("type" => "varchar","size" => 75, "null" => true),
			"entdateconvention" => array("type" => "date","null"=>true),
			"entnbemploiinitial" =>  array("type" => "int","null"=>false,"default"=>1),
			"entnbemploicrees" =>  array("type" => "int","null"=>false,"default"=>0),
			"entdemandeentrepreneur" => array("type" => "text","null"=>true),
			"entresumemission" => array("type" => "text","null"=>true),
			"entresultatobtenu" => array("type" => "text","null"=>true),
			"entcreee" => array("type" => "int","default"=>0),
			"entdatecreation" => array("type" => "date","null"=>false)
		);
	}

	public function count(){
		$params = array();
		
		$requete = "SELECT COUNT(*) AS nbrows
			FROM " . $this->getTableName();
		
		// Ajoute les contraintes à partir des données de recherche
		if(!is_null($this->clause)){
			$requete .= " WHERE ";
			for($i=0; $i<sizeof($this->clause);$i++){
				$requete .= $this->clause[$i]["column"] . " " . $this->clause[$i]["operateur"] . " :" . $this->clause[$i]["column"] . " AND ";
				$params[$this->clause[$i]["column"]] = $this->clause[$i]["value"];
			}
			// Supprime le dernier " AND "
			$requete = substr($requete,0,strlen($requete) - strlen(" AND "));
		}		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance();
		$query = $dbInstance->getConnexion()->prepare($requete);
		$query->execute($params);
		$query->setFetchMode(\PDO::FETCH_OBJ);
		$row = $query->fetch();
	
		return (integer) $row->nbrows;
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