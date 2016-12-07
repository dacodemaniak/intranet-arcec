<?php
/**
 * @name importRapport.class.php Services d'importation des rapports d'entretien
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec
 * @version 1.0
**/
namespace arcec;

class importRapport extends \wp\Files\importCSV {

	/**
	 * Instance du mapper de rapport
	 * @var object
	 */
	private $rapport;

	/**
	 * Données à intégrer dans la base de données
	 * @var array
	 */
	private $datas;


	/**
	 * Données non intégrables
	 * @var array
	 **/
	private $badDatas;

	public function __construct(){
		$this->columns = array();

		$this->columns();

		// Définit les colonnes supplémentaires à traiter
		$this->extraColumns["duree"] = "01:00";

		$this->rapport = new \arcec\Mapper\rapportMapper();

		$this->filePath = "_repository/entretiens.csv";
		$this->packets = 10000;

		/* Définit la signature d'une ligne incohérente
		 *	Colonne date à vide
		*	Colonne rapport à vide
		*/
		$this->badSignature = sha1("vide");

		if($this->read()){
			$this->toMapper();
			$this->process();
		}
	}
	
	/**
	 * Traite l'intégration des données
	 */
	private function process(){
		$indice = 0; // Indice pour provoquer la rupture de fichiers
		$fileIndice = 1;
		
		$dbInstance = \wp\dbManager\dbConnect::dbInstance()->getConnexion();
	
		$insertStatement = "INSERT INTO " . $this->rapport->getTableName() . "(";
		// Alimente les colonnes à traiter à partir de la définition des colonnes
		foreach($this->columns as $object){
			if($object->isMapped()){
				if(!$object->externalKey())
					$insertStatement .= $this->rapport->getColumnPrefix() . $object->mappedColumn() . ",";
				else
					$insertStatement .= $object->mappedColumn() . ",";
			}
		}
		// Ajoute les colonnes supplémentaires
		$extras = array_keys($this->extraColumns);
		foreach($extras as $column){
			$insertStatement .= $this->rapport->getColumnPrefix() . $column . ",";
		}
	
		$insertStatement = substr($insertStatement,0,strlen($insertStatement) - 1) . ") VALUES ";
		
		$saveInsertCols = $insertStatement;
		
		$values = "";
		
		foreach ($this->datas as $line){
			if($indice <= 100){
				$values .= "(";
				foreach($line as $value){
					foreach($value as $column => $data){
						$values .= $dbInstance->quote($data) . ",";
					}
				}
				// Ajoute les données complémentaires
				foreach($this->extraColumns as $column => $value){
					$values .= 	$dbInstance->quote($value) . ",";
				}
					
				$values = substr($values,0,strlen($values)-1) . "),\n";
			} else {
				// Génère un fichier toutes les 100 lignes
				$values = substr($values,0,strlen($values)-2) . ";";
				if(file_exists("_repository/entretiens_$fileIndice.sql")){
					unlink("_repository/entretiens_$fileIndice.sql");
				}
				$handle = fopen("_repository/entretiens_$fileIndice.sql","w+");
				fwrite($handle,$saveInsertCols . $values);
				fclose($handle);
				$indice = 0;
				$fileIndice++;
				$values = "(";
				foreach($line as $value){
					foreach($value as $column => $data){
						$values .= $dbInstance->quote($data) . ",";
					}
				}
				// Ajoute les données complémentaires
				foreach($this->extraColumns as $column => $value){
					$values .= 	$dbInstance->quote($value) . ",";
				}
					
				$values = substr($values,0,strlen($values)-1) . "),\n";				
			}
			$indice++;
		}
		$values = substr($values,0,strlen($values)-2) . ";";
		$insertStatement = $saveInsertCols . $values;

		if(file_exists("_repository/entretiens_$fileIndice.sql")){
			unlink("_repository/entretiens_$fileIndice.sql");
		}
		$handle = fopen("_repository/entretiens.sql","w+");
		fwrite($handle,$insertStatement);
		fclose($handle);
	}
	
	/**
	 * Traite les informations définitives à traiter
	 */
	private function toMapper(){
		$indice		= 0;
		$colIndice	= 0; // Indice de la colonne courante
	
		foreach($this->result as $line){
			$datas = array();
			$signature = ""; // Pour le contrôle des signatures de lignes incohérentes
			if($indice == 0){
				if($this->processFirstLine){
					foreach($line as $value){
						$column = $this->columns[$colIndice];
	
						if($column->isMapped()){
							if(sizeof($column->method())){
								// Calcule la donnée en fonction des paramètres
								$computeInfo = $column->method();
								$methods = array_keys($computeInfo); // Stocke la méthode à appeler
								$methodName = $methods[0];
								$params = array();
								foreach($computeInfo[$methodName] as $columnDefinition){
									$params[] = $this->getValueFromDefinition($line,$columnDefinition);
								}
								$data = $this->{$methodName}($params);
							}
	
							if(sizeof($column->translations())){
								// Transforme la donnée en fonction du type de translation à opérer
								$data = $column->translate($this->getValue($line,$column));
							}
							// Ajoute l'information à la collection finale
							$datas[] = array($column->name() => $data);
						}
	
						$colIndice++;
					}
					$this->datas[] = $datas;
				}
			} else {
				foreach($line as $value){
					
					$column = $this->columns[$colIndice];
					if(!is_null($column)){
						// Détermine les informations de signature
						if($column->name() == "dateRapport"){
							if($value == "" || is_null($value) || $value === false)
								$signature = "vide";
							else
								$signature = $value;
						}
						
						
						if($column->isMapped()){
							$data = $value;
								
							if(sizeof($column->method())){
								// Calcule la donnée en fonction des paramètres
								$computeInfo = $column->method();
								$methods = array_keys($computeInfo); // Stocke la méthode à appeler
								$methodName = $methods[0];
								$params = array();
								foreach($computeInfo[$methodName] as $columnDefinition){
									$params[] = $this->getValueFromDefinition($line,$columnDefinition);
								}
								$data = $this->{$methodName}($params);
							}
								
							if(sizeof($column->translations())){
								// Transforme la donnée en fonction du type de translation à opérer
								//$data = $column->translate($this->getValue($line,$column));
								$data = $column->translate($data);
							}
								
							// Ajoute l'information à la collection finale
							if($data == ""){
								$default = $column->defaultVal();
								if($default !== false){
									$data = $default;
								}
							}
							$datas[] = array($column->name() => $data);
								
							#begin_debug
							#echo "Donnée retournée pour " . $column->mappedColumn() . " : $data<br />\n";
							#end_debug
								
						}
	
					}
					$colIndice++;
				}
				if(sha1($signature) != $this->badSignature){
					$this->datas[] = $datas;
				} else {
					$this->badDatas[] = $datas;
				}
			}
			$colIndice = 0;
			$indice++;
			#begin_debug
			#if($indice > 1) die();
			#end_debug
		}
	}
	
	/**
	 * Retourne les valeurs associées à une ligne et les détails des colonnes concernées
	 * @param array $line
	 * @param array $details
	 */
	private function getValues($line,$details){
		$indice = 0;
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
	 * Définit les colonnes à traiter du fichier d'origine
	 */
	private function columns(){
		$computeInfos	= array();
	
		// N° du dossier
		$column = new \wp\Files\column();
		$column
		->name("iddossier")
		->type("int")
		->mappedColumn("dossier_id")
		->externalKey(true)
		;
		$this->addColumn($column);
	
		// Date
		$column = new \wp\Files\column();
		$column
		->name("dateRapport")
		->isMapped(true)
		->mappedColumn("date")
		->type("datefr")
		->defaultVal(\wp\Helpers\dateHelper::today())
		->addTranslation("toSQL")
		;
		$this->addColumn($column);

		// Lieu du rendez-vous
		$column = new \wp\Files\column();
		$column
		->name("lieux")
		->type("int")
		->defaultVal(15)
		->mappedColumn("acu")
		;
		$this->addColumn($column);
		
		// Conseiller d'accueil
		$column = new \wp\Files\column();
		$column
		->name("conseiller")
		->type("int")
		->defaultVal(14)
		->mappedColumn("cns")
		;
		$this->addColumn($column);
		
		// Rapport
		$column = new \wp\Files\column();
		$column
		->name("rapport")
		->type("string")
		->mappedColumn("contenu")
		->defaultVal("Néant")
		;
		$this->addColumn($column);
	

	
	}
}
?>