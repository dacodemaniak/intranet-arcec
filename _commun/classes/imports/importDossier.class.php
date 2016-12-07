<?php
/**
 * @name importFoodFact.class.php Services d'importation des produits Open Food Fact
 * @author web-Projet.com (jean-luc.aubert@web-projet.com)
 * @package arcec\Utilities
 * @version 1.0
**/
namespace arcec;

class importDossier extends \wp\Files\importCSV {
	
	/**
	 * Instance du mapper de dossier
	 * @var object
	 */
	private $dossier;
	
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
		$this->extraColumns["etd"] = 3;
		
		$this->dossier = new \arcec\Mapper\dossierMapper();
		
		$this->filePath = "_repository/dossiers.csv";
		$this->packets = 10000;
		
		/* Définit la signature d'une ligne incohérente
		*	Colonne prescripteur à 0
		*	Colonne prenom à 0
		*	Colonne sexe à 0
		*	Colonne age à 0
		*/
		$this->badSignature = sha1("0000");
		
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
						if($column->name() == "prescripteur"){
							$signature .= $value;
						}
						if($column->name() == "prenom"){
							$signature .= $value;
						}
						if($column->name() == "sexe"){
							$signature .= $value;
						}
						if($column->name() == "prescripteur"){
							$signature .= $value;
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
		
		// Identifiant
		$column = new \wp\Files\column();
		$column
			->name("id")
			->type("int")
			->mappedColumn("id")
		;
		$this->addColumn($column);
		
		// Mois d'accueil
		$column = new \wp\Files\column();
		$column
		->name("jour_accueil")
		->isMapped(false)
		->type("datefr")
		;
		$this->addColumn($column);

		// Etat du dossier
		$column = new \wp\Files\column();
		$column
		->name("edo")
		->type("int")
		->defaultVal(9)
		->mappedColumn("edo")
		;
		$this->addColumn($column);

		// Prescripteur
		$column = new \wp\Files\column();
		$column
		->name("prescripteur")
		->type("int")
		->mappedColumn("porteurprs")
		->defaultVal(122)
		;
		$this->addColumn($column);
		
		// Nom
		$column = new \wp\Files\column();
		$column
		->name("nom")
		->type("string")
		->mappedColumn("nomporteur")
		->addTranslation("toUpper")
		;
		$this->addColumn($column);

		// Prénom
		$column = new \wp\Files\column();
		$column
		->name("prenom")
		->type("string")
		->mappedColumn("prenomporteur")
		;
		$this->addColumn($column);
		
		// Sexe
		$column = new \wp\Files\column();
		$column
		->name("sexe")
		->type("string")
		->mappedColumn("porteursexe")
		;
		$this->addColumn($column);	

		// Age, nécessite calcul entre date accueil et date du jour pour déterminer date de naissance
		/*
		$computeInfos["computeDateNaissance"] = array(
				"date" => array(
						"column" => "jour_accueil",
						"type" => "datefr"
				),
				"age" => array(
						"column" => "age",
						"type" => "int"
				)
		);
		$column = new \wp\Files\column();
		$column
			->name("age")
			->mappedColumn("porteurdatenaissance")
			->type("int")
			->addMethod($computeInfos)
		;
		*/
		$column = new \wp\Files\column();
		$column
		->name("age")
		->mappedColumn("porteurdatenaissance")
		->type("datefr")
		->defaultVal("1986/01/01")
		->addTranslation("toSQL");
		$this->addColumn($column);
		
		// Nationalité
		$column = new \wp\Files\column();
		$column
		->name("nationalite")
		->type("int")
		->defaultVal(19)
		->mappedColumn("porteurnat")
		;
		$this->addColumn($column);

		// Téléphone fixe
		$column = new \wp\Files\column();
		$column
		->name("telephone")
		->type("string")
		->mappedColumn("porteurtelfixe")
		;
		$this->addColumn($column);
		
		// Portable
		$column = new \wp\Files\column();
		$column
		->name("portable")
		->type("string")
		->mappedColumn("porteurtelportable")
		;
		$this->addColumn($column);
		
		// e-mail
		$column = new \wp\Files\column();
		$column
		->name("email")
		->type("string")
		->mappedColumn("porteuremail")
		;
		$this->addColumn($column);		
		
		// Code postal
		$column = new \wp\Files\column();
		$column
		->name("code_postal")
		->type("string")
		->mappedColumn("porteurcodepostal")
		;
		$this->addColumn($column);
		
		// Ville
		$column = new \wp\Files\column();
		$column
		->name("ville")
		->type("string")
		->addTranslation("toUpper")
		->mappedColumn("porteurville")
		;
		$this->addColumn($column);

		// Niveau d'étude
		$column = new \wp\Files\column();
		$column
		->name("niveau_etude")
		->type("int")
		->mappedColumn("porteuretu")
		->defaultVal(242)
		;
		$this->addColumn($column);
		
		// Situation sociale
		$column = new \wp\Files\column();
		$column
		->name("ssociale")
		->type("int")
		->defaultVal(32)
		->mappedColumn("porteursis")
		;
		$this->addColumn($column);		

		// Régime d'indemnisation
		$column = new \wp\Files\column();
		$column
		->name("regime_indemnisation")
		->type("int")
		->defaultVal(65)
		->mappedColumn("porteurrin")
		;
		$this->addColumn($column);
		
		// Droit ACCRE
		$column = new \wp\Files\column();
		$column
		->name("accre")
		->type("boolean")
		->mappedColumn("porteureligibleaccre")
		->defaultVal(0)
		;
		$this->addColumn($column);
		
		// Droit NACRE
		$column = new \wp\Files\column();
		$column
		->name("nacre")
		->type("boolean")
		->mappedColumn("porteureligiblenacre")
		->defaultVal(0)
		;
		$this->addColumn($column);
		
		// Eligible FONGECIF
		$column = new \wp\Files\column();
		$column
		->name("fongecif")
		->type("boolean")
		->mappedColumn("porteureligiblefongecif")
		->defaultVal(0)
		;
		$this->addColumn($column);
		
		// Date d'adhésion
		$column = new \wp\Files\column();
		$column
		->name("adhesion")
		->type("datefr")
		->mappedColumn("dateadhesion")
		->defaultVal(\wp\Helpers\dateHelper::today())
		->addTranslation("toSQL")
		;
		$this->addColumn($column);
		
		// Renouvellement
		$date = new \DateTime();
		$date->modify("1 year");
		$column = new \wp\Files\column();
		$column
		->name("renouvellement")
		->type("datefr")
		->mappedColumn("datecotisation")
		->defaultVal($date->format("Y-m-d"))
		->addTranslation("toSQL")
		;
		$this->addColumn($column);
		
		// Catégorie projet
		$column = new \wp\Files\column();
		$column
		->name("type")
		->type("int")
		->mappedColumn("porteurtyp")
		->defaultVal(54)
		;
		$this->addColumn($column);
		
		// Programme
		$column = new \wp\Files\column();
		$column
		->name("programme")
		->type("int")
		->defaultVal(222)
		->mappedColumn("porteurprg")
		;
		$this->addColumn($column);

		// Date création
		$column = new \wp\Files\column();
		$column
		->name("datecreation")
		->type("datefr")
		->mappedColumn("datecreation")
		->defaultVal(\wp\Helpers\dateHelper::today())
		->addTranslation("toSQL")
		;
		$this->addColumn($column);
		
		// Code postal Entreprise
		$column = new \wp\Files\column();
		$column
		->name("cpentreprise")
		->type("string")
		->mappedColumn("entcodepostal")
		;
		$this->addColumn($column);
		
		// Ville entreprise
		$column = new \wp\Files\column();
		$column
		->name("villeentreprise")
		->type("string")
		->addTranslation("toUpper")
		->defaultVal('')
		->mappedColumn("entville")
		;
		$this->addColumn($column);
		
		// Secteur professionnel
		$column = new \wp\Files\column();
		$column
		->name("scp")
		->type("int")
		->defaultVal(228)
		->mappedColumn("porteurscp")
		;
		$this->addColumn($column);
		
		// Métier
		$column = new \wp\Files\column();
		$column
			->name("metier")
			->type("string")
			->mappedColumn("porteurautremetier");
		$this->addColumn($column);
		
		// Forme juridique
		$column = new \wp\Files\column();
		$column
		->name("forme")
		->type("int")
		->defaultVal(0)
		->mappedColumn("entfju")
		;
		$this->addColumn($column);

		// Statut social
		$column = new \wp\Files\column();
		$column
		->name("statutsocial")
		->type("int")
		->defaultVal(0)
		->mappedColumn("entsts")
		;
		$this->addColumn($column);
		
		// Nb. emploi initial
		$column = new \wp\Files\column();
		$column
		->name("emploiinit")
		->type("int")
		->defaultVal(1)
		->mappedColumn("entnbemploiinitial")
		;
		$this->addColumn($column);
		
		// Nb. emplois créés
		$column = new \wp\Files\column();
		$column
		->name("nbemploiscrees")
		->type("int")
		->defaultVal(0)
		->mappedColumn("entnbemploicrees")
		;
		$this->addColumn($column);
		
		// Passage AC vers PC
		$column = new \wp\Files\column();
		$column
		->name("passageacpc")
		->type("boolean")
		->defaultVal(0)
		->mappedColumn("entsuivipcpostac")
		;
		$this->addColumn($column);
		

		// Conseiller coordinateur
		$column = new \wp\Files\column();
		$column
		->name("cco")
		->type("int")
		->defaultVal(14)
		->mappedColumn("porteurcnscoord")
		;
		$this->addColumn($column);
		
		// Conseiller accueil
	 	$column = new \wp\Files\column();
		$column
		 ->name("cns")
		 ->type("int")
		 ->mappedColumn("cns")
		 ->defaultVal(14)
		 ;
		$this->addColumn($column);
		
	}
	
	
	
}
?>