<?php
/**
 * @name importFoodFact.class.php Services d'importation des produits Open Food Fact
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Utilities
 * @version 1.0
**/
namespace arcec;

class importFoodFact extends \wp\Files\importCSV {
	
	/**
	 * Données à intégrer dans la base de données
	 * @var array
	 */
	private $datas;
	
	
	
	public function __construct(){
		$this->columns = array();
		
		$this->columns();
		
		
		$this->filePath = "_repository/foodfact.csv";
		$this->packets = 10000;
		
		
		if($this->read()){
			$this->toMapper();
			$this->process();
		}
	}
	
	/**
	 * Traite l'intégration des données
	 */
	private function process(){
		$debug		= "";
		$indice		= 0;
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance()->getConnexion();
		
		$insertStatement = "INSERT INTO " . $this->dossier->getTableName() . "(";
		
		// Alimente les colonnes à traiter à partir de la définition des colonnes
		foreach($this->columns as $object){
			if($object->isMapped()){
				$debug .= $this->dossier->getColumnPrefix() . $object->mappedColumn() . "<br />\n";
				$insertStatement .= $this->dossier->getColumnPrefix() . $object->mappedColumn() . ",";
			}
		}
		// Ajoute les colonnes supplémentaires
		$extras = array_keys($this->extraColumns);
		foreach($extras as $column){
			$insertStatement .= $this->dossier->getColumnPrefix() . $column . ",";	
		}
		
		$insertStatement = substr($insertStatement,0,strlen($insertStatement) - 1) . ") VALUES ";
		$values = "";
		foreach ($this->datas as $line){
			$values .= "(";
			foreach($line as $value){
				foreach($value as $column => $data){
					if($indice == 0){
						$debug .= $column . " => " . $data . "<br />\n";
					}
					$values .= $dbInstance->quote($data) . ",";
				}
			}
			// Ajoute les données complémentaires
			foreach($this->extraColumns as $column => $value){
				$values .= 	$dbInstance->quote($value) . ",";
			}
			
			$values = substr($values,0,strlen($values)-1) . "),\n";
			$indice++;
		}
		$values = substr($values,0,strlen($values)-2) . ";";
		$insertStatement = $insertStatement . $values;
		if(file_exists("_repository/dossiers.sql")){
			unlink("_repository/dossiers.sql");
		}
		$handle = fopen("_repository/dossiers.sql","w+");
		fwrite($handle,$insertStatement);
		fclose($handle);
		
		echo $debug;
	}
	
	/**
	 * Traite les informations définitives à traiter
	 */
	private function toMapper(){
		$indice		= 0;
		$colIndice	= 0; // Indice de la colonne courante
		
		foreach($this->result as $line){
			if($indice == 0){
				continue;
			}
			foreach($line as $value){
				var_dump($value);
				die();
			}
		}
	}
	
	/**
	 * Retourne les valeurs associées à une ligne et les détails des colonnes concernées
	 * @param array $line
	 * @param array $details
	 */
	private function getValues($line,$details){
		$indice = 0;
		var_dump($details);
		die();
		foreach ($details as $key => $detail){
			$signature = implode("",$detail);
			foreach ($this->columns as $column){
				if($column->signature() == $signature){
					$values[] = $line[$indice];
				}
				$indice++;
			}
			$indice = 0;
		}
		return $values;
	}
	
	private function getValue($line,$column){
		$indice = 0;
		foreach ($this->columns as $column){
			if($column->signature() == $signature){
				$value = $line[$indice];
			}
			$indice++;
		}
		return $value;			
	}
	
	/**
	 * Retourne la valeur courante de la ligne à partir d'une définition de colonne
	 * @param array $line
	 * @param array $definition
	 * @return multitype
	 */
	private function getValueFromDefinition($line,$definition){
		$signature = sha1($definition["column"] . $definition["type"]);
		$indice = 0;
		foreach($this->columns as $column){
			if($signature == $column->signature()){
				return $line[$indice];
			}
			$indice++;
		}
	}
	/**
	 * Calcule la date de naissance à partir de la date d'inscription, la colonne Age et la valeur de la colonne âge
	 * @param array $params Tableau contenant la date d'inscription et l'âge au moment de l'inscription
	 */
	private function computeDateNaissance($params){
		$dateCreation = new \DateTime(\wp\Helpers\dateHelper::toSQL($params[0],"dd/mm/yyyy"));
		
		$age = $params[1];
		
		$birthYear = $dateCreation->format("Y") - $age;
		
		return $birthYear . "-01-01";
	}
	
	/**
	 * Définit les colonnes à traiter du fichier d'origine
	 */
	private function columns(){
		$computeInfos	= array();
		
		// Code EAN
		$column = new \wp\Files\column();
		$column
			->name("code")
			->type("string")
			->mappedColumn("codeean")
		;
		$this->addColumn($column);
		
		// URL
		$column = new \wp\Files\column();
		$column
		->name("url")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);

		// Date de création
		$column = new \wp\Files\column();
		$column
		->name("datecreation")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);

		// Date de modification
		$column = new \wp\Files\column();
		$column
		->name("datemodification")
		->type("string")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);
		
		// Nom
		$column = new \wp\Files\column();
		$column
		->name("nom")
		->type("string")
		->isMapped(false)
		->addTranslation("toUpper")
		;
		$this->addColumn($column);

		// Nom générique
		$column = new \wp\Files\column();
		$column
		->name("nomgenerique")
		->type("string")
		->isMapped(false)
		->addTranslation("toUpper")
		;
		$this->addColumn($column);
		
		// Marque
		$column = new \wp\Files\column();
		$column
		->name("marque")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);	


		$column = new \wp\Files\column();
		$column
		->name("age")
		->mappedColumn("porteurdatenaissance")
		->type("datefr")
		->defaultVal("1986/01/01")
		->addTranslation("toSQL");
		$this->addColumn($column);
		
		// Categorie
		$column = new \wp\Files\column();
		$column
		->name("categorie")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);

		// image
		$column = new \wp\Files\column();
		$column
		->name("image")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);
		
		// Small image
		$column = new \wp\Files\column();
		$column
		->name("smallimage")
		->type("string")
		->isMapped(false)
		;
		$this->addColumn($column);
		
	}
	
	
	
}
?>