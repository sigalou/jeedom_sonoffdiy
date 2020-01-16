<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class sonoffdiy extends eqLogic {
	
	
	public function refresh() {
		$largeurPhoto=$this->getConfiguration('largeurPhoto');
		$hauteurPhoto=$this->getConfiguration('hauteurPhoto');
		$arrondiPhoto=$this->getConfiguration('arrondiPhoto');
		if ($largeurPhoto =="") $largeurPhoto="250";
		if ($hauteurPhoto =="") $hauteurPhoto="250";		
		if ($arrondiPhoto =="") $arrondiPhoto="30%";		
		$tirageSort="999";//999 pour boucler dans tirageSort
		$touteslesValeurs= array($tirageSort);
		$nbPhotosaGenerer=$this->getConfiguration('nbPhotosaGenerer');
		$centrerLargeur=$this->getConfiguration('centrerLargeur');
		//log::add('sonoffdiy', 'debug', '~~~~~~~~~~~~~~~~~~~~~~$centrerLargeur:'.$centrerLargeur.'~~~~~~~~~~~~~~~~~~~~~~~~~');
		$formatDateHeure = config::byKey('formatDateHeure', 'sonoffdiy', '0');
		if ($formatDateHeure =="") $formatDateHeure="d-m-Y H:i:s";
		if ($nbPhotosaGenerer<1 || $nbPhotosaGenerer>9) $nbPhotosaGenerer=2;
		if ($this->getConfiguration('stockageSamba')==1) {
			$sambaShare	= config::byKey('samba::backup::share')	;
			$dos=$sambaShare.$this->getConfiguration('dossierSambasonoffdiy');
			//log::add('sonoffdiy', 'debug', '**********************1***********************************');
			$diapo=self::jpg_list($this->getConfiguration('dossierSambasonoffdiy'));
			//log::add('sonoffdiy', 'debug', '**********************diapo:'.json_encode($diapo).'***********************************');
			$nbPhotos=count($diapo);
			log::add('sonoffdiy', 'debug', '----------------------------------------------------------------------------');
			log::add('sonoffdiy', 'debug', 'Dans le dossier '.$dos.', il y a '.$nbPhotos.' photos');
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
			while ($compteurparSecurite < 20 && in_array($tirageSort, $touteslesValeurs))
				{
				$tirageSort=mt_rand(0,$nbPhotos-1);
				$compteurparSecurite++;
				}
			array_push($touteslesValeurs, $tirageSort);
			$file = $diapo[$tirageSort];
			$newfile = '/var/www/html/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
			log::add('sonoffdiy', 'debug', 'Fichier sélectionné au hasard:'.$file.' copié dans '.$this->getConfiguration('dossierSambasonoffdiy').' en '.$newfile);
			try {
				self::downloadCore($this->getConfiguration('dossierSambasonoffdiy'), $file, $newfile);
				self::infosExif($tirageSort,$i,$this);
				$image=self::redimensionne_Photo($tirageSort,$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
				$this->checkAndUpdateCmd('photo'.$i, $image);	
			}
			catch(Exception $exc) {
				log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . ' : ' . $exc->getMessage());
			}			
			}
			self::chmod777();
		}
		elseif ($this->getConfiguration('stockageFacebook')==1) {
			log::add('sonoffdiy', 'debug', '**********************Refresh Facebook***********************************');
			// on va tirer au sort l'album photo
			$Albums=$this->getConfiguration('arrayAlbumsFacebook');
			//log::add('sonoffdiy', 'debug', 'Albums : '.json_encode($Albums).'***********************************');
			$CompteAlbums=0;
			foreach ($Albums as $key2 => $value2) {
				if ($Albums[$key2][1] == '1') $CompteAlbums++;
			}
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
				$tirageSort=mt_rand(0,$CompteAlbums-1);
				$compteurPourTrouverAlbum=0;
				foreach ($Albums as $key2 => $value2) {
					if (($Albums[$key2][1] == '1') && ($compteurPourTrouverAlbum==$tirageSort)) {$idAlbumChoisi=$value2[0]; break;}
					if ($Albums[$key2][1] == '1') $compteurPourTrouverAlbum++;
				}	
				$albumsFacebook = config::byKey('albumsFacebook', 'sonoffdiy', '0');
				foreach ($albumsFacebook as $value) {
					if ($value['id'] == $idAlbumChoisi) { $nbdePhotosdansAlbum=$value['count']; break;}
				}		
				$tirageSort=mt_rand(0,$nbdePhotosdansAlbum-1);
				log::add('sonoffdiy', 'debug', "On a tiré au sort la ".$tirageSort."ème photo de l'album ".$idAlbumChoisi." qui en compte ".$nbdePhotosdansAlbum);
				$TokenFacebook = config::byKey('TokenFacebook', 'sonoffdiy', '0');
				$requete="https://graph.facebook.com/v5.0/".$idAlbumChoisi."/photos?fields=height%2Cwidth&limit=100&access_token=".$TokenFacebook;
				log::add('sonoffdiy', 'debug', 'On cherche la photo avec la requète : '.$requete);
				$onaTrouvePhoto = false;
				$countdata=0;
				while (!$onaTrouvePhoto) {
					if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$data=$json['data'];
					$indexPhoto=$tirageSort-1-$countdata;
					$countdata=$countdata+count($data);
					$paging=$json['paging'];
						if ($tirageSort<=$countdata) {
								// c'est ok, la photo est dans $json['data']
							$idphotoChoisie=$data[$indexPhoto]['id'];
							log::add('sonoffdiy', 'debug', 'ID de la photo choisie : '.$idphotoChoisie);
							$onaTrouvePhoto = true;
							} else {
							log::add('sonoffdiy', 'debug', 'On recherche la photo avec la requète : '.json_encode($paging['next']));
							$requete=$paging['next'];
							}
					} else {
					log::add('sonoffdiy', 'debug', "*********************** Souci de récupération de l'ID de la photo");
					$onaTrouvePhoto = true; // on sort	
					}
				}
				$requete="https://graph.facebook.com/v5.0/".$idphotoChoisie."?fields=event%2Calbum%2Calt_text_custom%2Cbackdated_time%2Cbackdated_time_granularity%2Ccreated_time%2Cheight%2Cname%2Cname_tags%2Cpage_story_id%2Cplace%2Cwidth%2Cimages&access_token=".$TokenFacebook;
				log::add('sonoffdiy', 'debug', 'On cherche les infos sur la photo avec la requète : '.$requete);
				if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$this->checkAndUpdateCmd('date'.$i, date($formatDateHeure,  strtotime($json['created_time'])));				
					$this->checkAndUpdateCmd('site'.$i, $json['place']['name']);		
					$this->checkAndUpdateCmd('pays'.$i, $json['place']['location']['country']);		
					$this->checkAndUpdateCmd('ville'.$i, $json['place']['location']['city']);		
					$this->checkAndUpdateCmd('album'.$i, $json['album']['name']);		
					$image=self::redimensionne_PhotoFacebook($json['images']['0']['source'],$json['images']['0']['width'],$json['images']['0']['height'],$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
					$this->checkAndUpdateCmd('photo'.$i, $image);		
				} else {
					log::add('sonoffdiy', 'debug', "*********************** Souci de récupération des infos de la photo");
				}		
			}
		}
		else {
			$dossierLocal=$this->getConfiguration('cheminsonoffdiy');
			if ($dossierLocal =="") $dossierLocal="/../images/"; // par défaut
			$dos=dirname(__FILE__).$dossierLocal; 
			$diapo=glob($dos.'*.jpg');
			$nbPhotos=count($diapo);
			for ($i = 1; $i <= $nbPhotosaGenerer; $i++) {
			while ($compteurparSecurite < 20 && in_array($tirageSort, $touteslesValeurs))
				{
				$tirageSort=mt_rand(0,$nbPhotos-1);
				$compteurparSecurite++;
				}
			array_push($touteslesValeurs, $tirageSort);
			$file = $diapo[$tirageSort];
			$newfile = '/var/www/html/tmp/sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg';
			if (!copy($file, $newfile)) log::add('sonoffdiy', 'debug', 'Copie image '.$file.' en sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg NOK'); else log::add('sonoffdiy', 'debug', 'Copie image '.$file.' en sonoffdiy_'.$this->getId()."_".$tirageSort.'.jpg OK');
			$image=self::redimensionne_Photo($tirageSort,$largeurPhoto,$hauteurPhoto, $arrondiPhoto, $centrerLargeur);
			$this->checkAndUpdateCmd('photo'.$i, $image);			
			}
		}
	}
	
	
	public function postSave() {
					log::add('sonoffdiy', 'debug', 'postSAVE ');

				$cmd = $this->getCmd(null, 'Off');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Off');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Off');
					$cmd->setConfiguration('request', 'switch?command=off');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();

				
				$cmd = $this->getCmd(null, 'On');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('On');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('On');
					$cmd->setConfiguration('request', 'switch?command=on');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();
			
		
		
/*
	//Commande Refresh
	$createRefreshCmd = true;
	$refresh = $this->getCmd(null, 'refresh');
	if (!is_object($refresh)) {
		$refresh = cmd::byEqLogicIdCmdName($this->getId(), __('Rafraichir', __FILE__));
		if (is_object($refresh)) {
			$createRefreshCmd = false;
		}
	}
	if ($createRefreshCmd) {
		if (!is_object($refresh)) {
			$refresh = new sonoffdiyCmd();
			$refresh->setLogicalId('refresh');
			$refresh->setIsVisible(1);
			$refresh->setDisplay('icon', '<i class="fa fa-sync"></i>');
			$refresh->setName(__('Refresh', __FILE__));
		}
		$refresh->setType('action');
		$refresh->setSubType('other');
		$refresh->setEqLogic_id($this->getId());
		$refresh->save();
	}*/
	}
	
	public function preUpdate() {
	}
	
	public function preRemove () {
	}
	
	public function preSave() {
		// Controle si 	nbPhotosaGenerer n'est pas vide
		$nbPhotosaGenerer=$this->getConfiguration('nbPhotosaGenerer');
		if ($nbPhotosaGenerer<1 || $nbPhotosaGenerer>9) 
			{$nbPhotosaGenerer=2;
			$this->setConfiguration('nbPhotosaGenerer',"2");
		}
		$this->setConfiguration('cheminsonoffdiyValide', "question"); 
		$diapo = array();
		if (($this->getConfiguration('stockageSamba')!=1) && ($this->getConfiguration('stockageFacebook')!=1)) {
			// On est sur le mode Stockage LOCAL
			$this->setConfiguration('stockageLocal',1); // par défaut
			$this->setConfiguration('sambaEtat', "nok"); 		
			$this->setConfiguration('facebookEtat', "nok"); 
			$this->setConfiguration('cheminsonoffdiyMessage', "");
			$dossierLocal=$this->getConfiguration('cheminsonoffdiy');
			if ($dossierLocal =="") $dossierLocal="/../images/"; // par défaut
			$dos=dirname(__FILE__).$dossierLocal; 
			$diapo=glob($dos.'*.jpg');
			$this->setConfiguration('cheminsonoffdiyComplet', realpath($dos)); 
			$this->setConfiguration('localEtat', "ok"); 
			$nbPhotos=count($diapo);
			$this->setConfiguration('nombrePhotos', $nbPhotos);
			$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));
			if ($nbPhotos==0) {
				$this->setConfiguration('cheminsonoffdiyValide', "nok");
				$this->setConfiguration('localEtat', "nok"); 
			}
			else $this->setConfiguration('cheminsonoffdiyValide', "ok");
		} elseif ($this->getConfiguration('stockageSamba')==1)	{
			// On est sur le mode Stockage SAMBA
				if ($this->getConfiguration('sambaEtat') != "ok") {
					if ($this->getConfiguration('cheminsonoffdiyMessage') == "") {
					$this->setConfiguration('cheminsonoffdiyValide', "question");
					$this->setConfiguration('localEtat', "nok"); 
					$this->setConfiguration('sambaEtat', "nok"); 	
					$this->setConfiguration('facebookEtat', "nok"); 	
					$this->setConfiguration('nombrePhotos', "");
					$this->setConfiguration('derniereMAJ', " ");
					$this->setConfiguration('cheminsonoffdiyComplet', "");
					} else {				
					//$this->setConfiguration('cheminsonoffdiyValide', "question");
					//$this->setConfiguration('localEtat', "nok"); 
					$this->setConfiguration('sambaEtat', "nok"); 	
					$this->setConfiguration('cheminsonoffdiyValide', "nok");
					//$this->setConfiguration('facebookEtat', "nok"); 	
					//$this->setConfiguration('nombrePhotos', "");
					//$this->setConfiguration('derniereMAJ', " ");
					//$this->setConfiguration('cheminsonoffdiyComplet', "");
					}
				}
				else {
					$this->setConfiguration('cheminsonoffdiyValide', "ok");
				}
				
		} else {
			// On est sur le mode Recupération FACEBOOK
			$this->setConfiguration('sambaEtat', "nok"); 		
			$this->setConfiguration('localEtat', "nok"); 
			$this->setConfiguration('cheminsonoffdiyMessage', "");
			$TokenFacebook = config::byKey('TokenFacebook', 'sonoffdiy', '0');
			$requete="https://graph.facebook.com/v5.0/me?access_token=".$TokenFacebook;
			log::add('sonoffdiy', 'debug', 'On teste le compte Facebook avec la requète : '.$requete.'***********************************');
			$recupereJson=file_get_contents($requete);
			if(empty($recupereJson)) {log::add('sonoffdiy', 'debug', 'vide');}
			if ($recupereJson=file_get_contents($requete, true)) {
				$json = json_decode($recupereJson,true);
				$this->setConfiguration('cheminsonoffdiyValide', "ok");
				$this->setConfiguration('facebookEtat', "ok"); 		
				$this->setConfiguration('cheminsonoffdiyComplet', "Facebook : Page ".self::Utf8_ansi($json['name'])); 
				$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));
				$requete="https://graph.facebook.com/v5.0/me/albums?fields=count%2Cname%2Ccreated_time&access_token=".$TokenFacebook;	
				log::add('sonoffdiy', 'debug', 'On teste les albums photos Facebook avec la requète : '.$requete.'***********************************');
				if ($recupereJson=file_get_contents($requete, true)) {
					$json = json_decode($recupereJson,true);
					$compteur=0;
					foreach($json['data'] as $item)
					{
						$compteur=$compteur+$item['count'];
						// Enregistrement dans la Config du Plugin
						config::save('albumsFacebook', $json['data'], 'sonoffdiy');
						log::add('sonoffdiy', 'debug', 'On enregistre : '.json_encode($json['data']).' dans plugin/config/albumsFacebook');
						// on va compter le nb de photos des albums cochés 
						// Lecture de arrayAlbumsFacebook dans configuration du device en cours ($device)
						$Albums=$this->getConfiguration('arrayAlbumsFacebook');
						$totalPhotosCochees=0;
						foreach ($json['data'] as $value) {
							foreach ($Albums as $key2 => $value2) {
								if (($value['id'] == $Albums[$key2][0]) && $Albums[$key2][1] == '1') $totalPhotosCochees=$totalPhotosCochees+$value['count'];
							}	
						}
						$this->setConfiguration('nombrePhotos', $compteur." (".$totalPhotosCochees." sélectionnées)");
					}
				}
				else {
					log::add('sonoffdiy', 'debug', '******* Souci dans la requète JSON '.$recupereJson);
					log::add('sonoffdiy', 'debug', '******* Souci dans la requète JSON ');
					$this->setConfiguration('cheminsonoffdiyValide', "nok");
					$this->setConfiguration('facebookEtat', "nok");
					$this->setConfiguration('derniereMAJ', date("d-m-Y H:i:s"));	
					$this->setConfiguration('nombrePhotos', "");			
					$this->setConfiguration('cheminsonoffdiyComplet', "Facebook : Error"); 
				}
			}
		}
	}
	
	
}
class sonoffdiyCmd extends cmd {
	
	public function dontRemoveCmd() {
		if ($this->getLogicalId() == 'refresh') {
			return true;
		}
		return false;
	}
	
	public function postSave() {
	}
	
	public function preSave() {
		if ($this->getLogicalId() == 'refresh') {
			return;
		}
	}
	
	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		log::add('sonoffdiy', 'debug', 'Execute');
		log::add('sonoffdiy', 'info', 'options : ' . json_encode($_options));//Request : http://192.168.0.21:3456/volume?value=50&device=G090LF118173117U
		
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->refresh();
			return;
		}
				$adresse_ip = $this->getEqLogic()->getConfiguration('adresse_ip');
		log::add('sonoffdiy', 'debug', '----adresse_ip:'.$adresse_ip);
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
	list($variable, $valeur) = explode('=', $arguments, 2);
	log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur);

			$url = "http://".$adresse_ip.":8081/zeroconf/switch"; // Envoyer la commande Refresh via jeeAlexaapi
			$ch = curl_init($url);
			$data = array(
				'deviceid'        => '45855',
				'data'    => array(
					'switch'      => $valeur
				),
			);			
			$payload = json_encode($data);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			$result = curl_exec($ch);
			curl_close($ch);
		
		
		
		
		
		
		
		
		
		
		/*
		$request = $this->buildRequest($_options);
		log::add('sonoffdiy', 'info', 'Request : ' . $request);//Request : http://192.168.0.21:3456/volume?value=50&device=G090LF118173117U
		$request_http = new com_http($request);
		$request_http->setAllowEmptyReponse(true);//Autorise les réponses vides
		if ($this->getConfiguration('noSslCheck') == 1) $request_http->setNoSslCheck(true);
		if ($this->getConfiguration('doNotReportHttpError') == 1) $request_http->setNoReportError(true);
		if (isset($_options['speedAndNoErrorReport']) && $_options['speedAndNoErrorReport'] == true) {// option non activée 
			$request_http->setNoReportError(true);
			$request_http->exec(0.1, 1);
			return;
		}
		$result = $request_http->exec($this->getConfiguration('timeout', 3), $this->getConfiguration('maxHttpRetry', 3));//Time out à 3s 3 essais
		if (!$result) throw new Exception(__('Serveur injoignable', __FILE__));
		// On traite la valeur de resultat (dans le cas de whennextalarm par exemple)
		$resultjson = json_decode($result, true);
		//log::add('sonoffdiy', 'info', 'resultjson:'.json_encode($resultjson));
					// Ici, on va traiter une commande qui n'a pas été executée correctement (erreur type "Connexion Close")
					if (($value =="Connexion Close") || ($detail =="Unauthorized")){
						$value = $resultjson['value'];
						$detail = $resultjson['detail'];
						log::add('sonoffdiy', 'debug', '**On traite '.$value.$detail.' Connexion Close** dans la Class');
						sleep(6);
							if (ob_get_length()) {
							ob_end_flush();
							flush();
							}	
						log::add('sonoffdiy', 'debug', '**On relance '.$request);
						$result = $request_http->exec($this->getConfiguration('timeout', 2), $this->getConfiguration('maxHttpRetry', 3));
						if (!result) throw new Exception(__('Serveur injoignable', __FILE__));
						$jsonResult = json_decode($json, true);
						if (!empty($jsonResult)) throw new Exception(__('Echec de l\'execution: ', __FILE__) . '(' . $jsonResult['title'] . ') ' . $jsonResult['detail']);
						$resultjson = json_decode($result, true);
						$value = $resultjson['value'];
					}
		
				
		if (($this->getType() == 'action') && (is_array($this->getConfiguration('infoNameArray')))) {
			foreach ($this->getConfiguration('infoNameArray') as $LogicalIdCmd) {
				$cmd=$this->getEqLogic()->getCmd(null, $LogicalIdCmd);
				if (is_object($cmd)) { 
					$this->getEqLogic()->checkAndUpdateCmd($LogicalIdCmd, $resultjson[0][$LogicalIdCmd]);					
					//log::add('sonoffdiy', 'info', $LogicalIdCmd.' prévu dans infoNameArray de '.$this->getName().' trouvé ! '.$resultjson[0]['whennextmusicalalarminfo'].' OK !');
				} else {
					log::add('sonoffdiy', 'warning', $LogicalIdCmd.' prévu dans infoNameArray de '.$this->getName().' mais non trouvé ! donc ignoré');
				} 
			}
		} 
		elseif (($this->getType() == 'action') && ($this->getConfiguration('infoName') != '')) {
			// Boucle non testée !!
				$LogicalIdCmd=$this->getConfiguration('infoName');
				$cmd=$this->getEqLogic()->getCmd(null, $LogicalIdCmd);
				if (is_object($cmd)) { 
					$this->getEqLogic()->checkAndUpdateCmd($LogicalIdCmd, $resultjson[$LogicalIdCmd]);
				} else {
					log::add('sonoffdiy', 'warning', $LogicalIdCmd.' prévu dans infoName de '.$this->getName().' mais non trouvé ! donc ignoré');
				} 
		}*/
		
		return true;
	}
	/*
	private function buildRequest($_options = array()) {
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	log::add('sonoffdiy', 'info', '----Command:*'.$command.'* Request:'.json_encode($_options));
	
		switch ($command) {
			case 'switch':
	log::add('sonoffdiy', 'info', 'switch*');
				$request = $this->build_ControledeSliderSelectMessage($_options);
	log::add('sonoffdiy', 'info', 'switch*2');
			break;
			default:
				$request = '';
			break;
		}
		
		$adresse_ip = $this->getEqLogic()->getConfiguration('adresse_ip');
		log::add('sonoffdiy', 'debug', '----adresse_ip:'.$adresse_ip);
		$request = scenarioExpression::setTags($request);
		if (trim($request) == '') throw new Exception(__('Commande inconnue ou requête vide : ', __FILE__) . print_r($this, true));
		$device=str_replace("_player", "", $this->getEqLogic()->getConfiguration('serial'));
		return 'http://' . $adresse_ip . ':8081/' . $request . '&device=' . $device;
	}
	
	private function build_ControledeSliderSelectMessage($_options = array(), $default = "Ceci est un message de test") {
		log::add('sonoffdiy', 'info', '---->build_ControledeSliderSelectMessage');
		$request = $this->getConfiguration('request');
		log::add('sonoffdiy', 'info', '---->Request2:'.$request);
		//log::add('sonoffdiy', 'debug', '---->getName:'.$this->getEqLogic()->getCmd(null, 'volumeinfo')->execCmd());
		if ((isset($_options['slider'])) && ($_options['slider'] == "")) $_options['slider'] = $default;
		if ((isset($_options['select'])) && ($_options['select'] == "")) $_options['select'] = $default;
		if ((isset($_options['message'])) && ($_options['message'] == "")) $_options['message'] = $default;
		// Si on est sur une commande qui utilise volume, on va remettre après execution le volume courant
		//if (strstr($request, '&volume=')) $request = $request.'&lastvolume='.$lastvolume;
		log::add('sonoffdiy', 'info', '---->Request3:'.$request);
		$request = str_replace(array('#slider#', '#select#', '#message#', '#volume#'), 
		array($_options['slider'], $_options['select'], urlencode($_options['message']), $_options['volume']), $request);
		//log::add('sonoffdiy', 'info', '---->RequestFinale:'.$request);
		return $request;
	}	*/
}