<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';

if (!class_exists('mDNS')) {
	require_once dirname(__FILE__) . '/../../3rdparty/lib/mdns.php';
	//// Fichier récupéré sur https://github.com/ChrisRidings/PHPmDNS

}

// Ressources : https://notenoughtech.com/featured/sonoff-r3-diy-mode/

class sonoffdiy extends eqLogic {


	public static function deamon_info() {
		$return = array();
		$return['log'] = '';
		$return['state'] = 'nok';
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (is_object($cron) && $cron->running()) {
			$return['state'] = 'ok';
		}
		$return['launchable'] = 'ok';
		return $return;
	}
	public static function cron() {
		//log::add('sonoffdiy', 'debug', '!!************************** Start cron update sonoffdiy *******************************!!');
		return;
	}
	public static function deamon_start($_debug = false) {
		log::add('sonoffdiy', 'debug', 'deamon_start');
		self::deamon_stop();
		$deamon_info = self::deamon_info();
		if ($deamon_info['launchable'] != 'ok') {
			throw new Exception(__('Veuillez vérifier la configuration', __FILE__));
		}
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Cron et Daemon introuvables - réinstaller le plugin', __FILE__));
		}
		$cron->run();
	}

	public static function deamon_stop() {
		log::add('sonoffdiy', 'debug', 'deamon_stop');
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		if (!is_object($cron)) {
			throw new Exception(__('Cron et Daemon introuvables - réinstaller le plugin', __FILE__));
		}
		$cron->halt();
  
	}
	public static function daemon() { 
		//gc_enable();
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 1');
		log::add('sonoffdiy_mDNS', 'debug', '-----------------------------------------------------------------');

		$port = 6900; // port
        $address = '0.0.0.0';
		$mdns = new mDNS();
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 2');
		
		if (isset($sock) && $sock != NULL) socket_close($sock);
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 3');

        if (($sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP)) === false) {
			log::add('sonoffdiy', 'debug', "socket_create() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        }
        if (!socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1)) {
            log::add('sonoffdiy', 'debug', "socket_set_option() failed: reason: " . socket_strerror(socket_last_error()));
            exit();
        } 
        if (socket_bind($sock, $address, $port) === false) {
           log::add('sonoffdiy', 'debug', "socket_bind() failed: reason: " . socket_strerror(socket_last_error($sock))." port :". $port);
           log::add('sonoffdiy', 'debug', "Un autre PLUGIN utilise déja ce port, désactivez les plugins un par un pour voir lequel est en conflit");
            exit();
        }
        if (socket_listen($sock, 5) === false) {
           log::add('sonoffdiy', 'debug', "socket_listen() failed: reason: " . socket_strerror(socket_last_error($sock)));
        }
        socket_set_nonblock($sock);
		//log::add('sonoffdiy', 'debug', 'Lancement du Daemon mDNS Debug 4');
        // search for connected devices
		$time=0;
		log::add('sonoffdiy_mDNS', 'debug', 'Lancement du Daémon');
		//log::add('sonoffdiy_mDNS','debug','Mémoire utilisée :'.round(memory_get_usage()/1000). " ko ".memory_get_usage()%1000 . " o ");
		//$memDep = round(memory_get_usage()/1000);
		while(true) {

			// search for ewelink devices using mDNS
			$inpacket = $mdns->readIncoming();		
			if ($inpacket->packetheader !=NULL){
				$ans = $inpacket->packetheader->getAnswerRRs();
				if ($ans> 0) {
					if ($inpacket->answerrrs[0]->name == "_ewelink._tcp.local") { 
						log::add('sonoffdiy_mDNS', 'debug', "**************** Une Trame qui nous intéresse de ".$inpacket->answerrrs[0]->name);
						//log::add('sonoffdiy_mDNS', 'debug', "Trame mDNS entrante ".json_encode($inpacket));
						//log::add('sonoffdiy_mDNS', 'debug', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name);
						for ($x=0; $x < sizeof($inpacket->answerrrs); $x++) {
							//log::add('sonoffdiy_mDNS', 'debug', "   x:$x  qtype:".$inpacket->answerrrs[$x]->qtype);
							if ($inpacket->answerrrs[$x]->qtype == 12) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
									//log::add('sonoffdiy_mDNS','debug',"  | Nom de l'émetteur :".$name);
								}
							}
							if ($inpacket->answerrrs[$x]->qtype == 16) {
								$str="";
								for($i=0;$i<sizeof($inpacket->answerrrs[$x]->data);$i++) {
									$caractere=$inpacket->answerrrs[$x]->data[$i];
									if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($caractere);
								}
								$data = $caractere=$inpacket->answerrrs[$x]->data;
								$sequence="{";
								$offset = 0;
								$size = $data[$offset];
								$str="";
								for ($ls=1; $ls <= $size; $ls++) { 
								  $caractere=$data[$offset+$ls];
								  if ($caractere>31 && $caractere<127 && $caractere!= 34  && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
								}
								$pos = strpos ( $str , '=');
								$key = substr($str,0,$pos);
								$val = substr($str,$pos+1);
								$sequence.='"'.$key.'":"'.$val.'"';
								$offset = $offset + $size+1;
								while ($data[$offset]<> 0  && sizeof($data)) {
									$size = $data[$offset];
									$str="";
									for ($ls=1; $ls <= $size; $ls++) { 
										$caractere=$data[$offset+$ls];
										if ($caractere>31 && $caractere<127 && $caractere!= 39 && $caractere!= 92 && $caractere!=96) $str.=chr($data[$offset+$ls]); 
									}
									$pos = strpos ( $str , '=');
									$key = substr($str,0,$pos);
									$val = substr($str,$pos+1);
									if ($key!='data1') 	$sequence.=',"'.$key.'":"'.$val.'"';
									else 				$data_data=$val;
									$offset = $offset + $size+1;
								} 
								$sequence.="}";
								/*
								if ($inpacket->answerrrs[$x]->name == "_ewelink._tcp.local") {
									$name = "";
									for ($y = 0; $y < sizeof($inpacket->answerrrs[$x]->data); $y++) {
										$name .= chr($inpacket->answerrrs[$x]->data[$y]);
									}
								}*/
							}/*
							if ($inpacket->answerrrs[$x]->qtype == 33) {
								$d = $inpacket->answerrrs[$x]->data;
								$portm = ($d[4] * 256) + $d[5];
								$offset = 6;
								$size = $d[$offset];
								$offset++;
								$target = "";
								for ($z=0; $z < $size; $z++) {
									$target .= chr($d[$offset + $z]);
								}
								$target .= ".local";
							}*/
							if ($inpacket->answerrrs[$x]->qtype == 1) {
								$d = $inpacket->answerrrs[$x]->data;
								$ip = $d[0] . "." . $d[1] . "." . $d[2] . "." . $d[3];
							}
						}
						
						//$sequence = {"txtvers":"1","id":"1000ab1e93","type":"diy_plug","apivers":"1","seq":"66"}
						//$data_data= {"switch":"on","startup":"on","pulse":"off","sledOnline":"on","pulseWidth":5000,"rssi":-77}

						$sequence_decoded=json_decode($sequence, true);
						$IPetSEQ=$ip.".".$sequence_decoded['seq'];
						if ($IPetSEQ!=$last_IPetSEQ)
						{
						$last_IPetSEQ=$ip.".".$sequence_decoded['seq'];
						$data_data_decoded=json_decode($data_data, true);
						$data_data_decoded['IDdetectee']=$sequence_decoded['id'];
						log::add('sonoffdiy_mDNS','debug',"  | séquence : ".$sequence);
						//log::add('sonoffdiy_mDNS','debug',"  | données : ".$data_data);
						log::add('sonoffdiy_mDNS','debug',"  | données : ".json_encode($data_data_decoded));
						log::add('sonoffdiy_mDNS','debug',"  | ip : ".$ip);
						log::add('sonoffdiy_mDNS','debug',"  | seq : ".$sequence_decoded['seq']);
						log::add('sonoffdiy_mDNS','debug',"  | id : ".$sequence_decoded['id']);
							
							
						self::sauvegardeCmdsInfo($data_data_decoded, true, $sequence_decoded['id']);

							
							
							
							
							
						}
						else
						log::add('sonoffdiy_mDNS','debug',"  | Trame non traitée identique à la précédente -> ignorée");

					}
					else log::add('sonoffdiy_mDNS', 'debug', "Trame mDNS entrante depuis ".$inpacket->answerrrs[0]->name." -> ignorée");

				}
			}
			

			//log::add('sonoffdiy_mDNS', 'debug', date('h:i:s'));
			usleep(200000);
			//log::add('sonoffdiy_mDNS', 'debug', date('h:i:s'));
		}
	}		
		
	
	public function sauvegardeCmdsInfo($_data_decoded, $save, $_ID) {
						//log::add('sonoffdiy_mDNS','debug'," **** Lancement sauvegardeCmdsInfo");
						$cestBonOnaTrouveleDevice=false;
						foreach (eqLogic::byType('sonoffdiy') as $eqLogic){
							//log::add('sonoffdiy_mDNS','debug'," TTTTTTTTTTTTTTTTTTTTTTTTTTTTTEST : ".$eqLogic->getConfiguration('device_id')." / ".$_ID);
							if (!($eqLogic->getConfiguration('device_id') == $_ID)) continue;
							foreach ($eqLogic->getCmd('info') as $cmd) {
							//log::add('sonoffdiy_mDNS','debug'," **** rssi : ".$data_data_decoded['rssi']);
							//log::add('sonoffdiy_mDNS','debug'," **** switch : ".$data_data_decoded['switch']);
							//log::add('sonoffdiy_mDNS','debug'," **** getLogicalId : ".$cmd->getLogicalId());
							//log::add('sonoffdiy_mDNS','debug'," **>>** _data_decoded : ".$data_data_decoded);
							$cmd->enregistreCmdInfo($cmd->getLogicalId(), $_data_decoded, $eqLogic);
							//log::add('sonoffdiy_mDNS','debug'," FINI**** getLogicalId : ".$cmd->getLogicalId());
							$cestBonOnaTrouveleDevice=true;
							}
							if ($save) $eqLogic->save(); // à voir si on garde c'est que pour actualiser les infos du desktop
						}
						if (!$cestBonOnaTrouveleDevice) {
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"* Il y a un souci dans l'ID d'un des devices, il devrait y avoir un device avec l'ID : ".$_ID);
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						log::add('sonoffdiy_mDNS','warning',"**********************************************************************************************");
						}
							

	}	
	
	
	
	public function stopDaemon() {
		log::add('sonoffdiy', 'debug', 'stopDaemon');
		$cron = cron::byClassAndFunction('sonoffdiy', 'daemon');
		$cron->stop();
		$cron->start();
	}
	

	public function refresh() {
		
			//log::add('sonoffdiy', 'info', '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>--Refresh');
			
			try {
				foreach ($this->getCmd('action') as $cmd) {
							//log::add('sonoffdiy', 'info', 'Test refresh de la commande '.$cmd->getName().'--'.$cmd->getConfiguration('RunWhenRefresh', 0));

					if ($cmd->getConfiguration('RunWhenRefresh', 0) != '1') {
						continue; // si le lancement n'est pas prévu, ça va au bout de la boucle foreach
					}
					//log::add('sonoffdiy', 'info', 'OUI pour '.$cmd->getName());
					$value = $cmd->execute();
				}
			}
			catch(Exception $exc) {log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . $this->getHumanName() . ' : ' . $exc->getMessage());}
	}
	
	
	public function postSave() {
					log::add('sonoffdiy', 'debug', 'Sauvegarde '.$this->getName());
					
				
		$premierSAVE = false;
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
				$refresh->setName(__('Rafraichir', __FILE__));
			}
			$refresh->setType('action');
			$refresh->setSubType('other');
			$refresh->setEqLogic_id($this->getId());
			$refresh->setOrder(10);
			$refresh->setConfiguration('expliq', 'Rafraîchit manuellement toutes les données du device');
			$refresh->save();
		}

				$cmd = $this->getCmd(null, 'switch');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('switch');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat du relais');
					//$cmd->setDisplay('title_disable', 1);
					$cmd->setIsVisible(1);
					$cmd->setOrder(1);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'Off');
				if (!is_object($cmd)) {
					$premierSAVE = true;
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Off');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Off');
					$cmd->setConfiguration('request', 'switch?command=off');
					$cmd->setConfiguration('expliq', 'Eteindre');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(3);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
					$cmd->setDisplay('icon', '<i class="icon_red icon fas fa-times"></i>');
					
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'Info');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Info');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Info');
					$cmd->setConfiguration('request', 'info');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setConfiguration('RunWhenRefresh', 1);				
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'On');
				if (!is_object($cmd)) {
					$premierSAVE = true;
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('On');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('On');
					$cmd->setConfiguration('request', 'switch?command=on');
					$cmd->setConfiguration('expliq', 'Allumer');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(2);
					$cmd->setDisplay('icon', '<i class="icon_green icon fas fa-check"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'PulseOff');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('PulseOff');
					$cmd->setSubType('other');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Pulse Off');
					//$cmd->setConfiguration('parameter', '5000');					
					$cmd->setConfiguration('request', 'pulse?command=off');
					$cmd->setConfiguration('expliq', 'Désactive le mode Pulse');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(5);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);
				}
				$cmd->save();

				
				$cmd = $this->getCmd(null, 'PulseOn');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('PulseOn');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Pulse On');
					$cmd->setConfiguration('parameter', '5000');
					$cmd->setConfiguration('request', 'pulse?command=on');
					$cmd->setConfiguration('expliq', 'Active le mode Pulse (et fixe le nb de ms en paramètre)');
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(4);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);

				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'startup_action'); // 
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('startup_action');
					$cmd->setSubType('select');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat initial');					
					$cmd->setConfiguration('request', 'startup?state=#select#');
					$cmd->setConfiguration('listValue', 'on|on; off|off; stay|stay');
					$cmd->setConfiguration('expliq', "Définir l'état à la mise sous tension");
					$cmd->setDisplay('title_disable', 1);
					$cmd->setOrder(6);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(0);
				}
				$cmd->save();
				
			
				$cmd = $this->getCmd(null, 'startup');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('startup');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat à la mise sous tension');
					$cmd->setIsVisible(0);
					$cmd->setOrder(2);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();

				
				$cmd = $this->getCmd(null, 'pulse');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('pulse');
					$cmd->setSubType('binary');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Etat de la fonction Pulse');
					$cmd->setIsVisible(0);
					$cmd->setOrder(3);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				
				$cmd = $this->getCmd(null, 'pulseWidth');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('pulseWidth');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Tempo de la fonction Pulse');
					$cmd->setIsVisible(0);
					$cmd->setOrder(4);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				
				$cmd = $this->getCmd(null, 'ssid');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('ssid');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('SSID');
					$cmd->setIsVisible(1);
					$cmd->setOrder(5);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
								
				$cmd = $this->getCmd(null, 'rssi');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('rssi');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('RSSI');
					$cmd->setIsVisible(1);
					$cmd->setOrder(6);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'IDdetectee');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('IDdetectee');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('ID');
					$cmd->setIsVisible(0);
					$cmd->setOrder(7);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();	
				
				$cmd = $this->getCmd(null, 'signal_strength');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('signal_strength');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Signal Action');
					$cmd->setConfiguration('request', 'signal_strength');
					$cmd->setConfiguration('expliq', 'Détecte la force du signal Wifi');
					$cmd->setConfiguration('RunWhenRefresh', 1);				
					//$cmd->setConfiguration('infoName', $signalinfo->getId());
					$cmd->setIsVisible(0);
				}
				$cmd->save();		
				
				/* ca va pas, on a pas l'ip
				if ($premierSAVE) {
				log::add('sonoffdiy', 'debug', '*********PREMIERSAVE***********');
				$cmd = $this->getCmd(null, 'refresh');
				if (!is_object($cmd)) $cmd->execCmd();
				}*/
				
			
/*
			//$signalinfo = $this->getCmd(null, 'signal');
			$signalaction = $this->getCmd(null, 'signal_strength');
					if((is_object($signalinfo)) && (is_object($signalaction))) {
					$signalaction->setValue($signalinfo->getId());// Lien 
					$signalaction->save();
				log::add('sonoffdiy', 'debug', '***lien:signalaction/signalinfo');
				}
*/




	}
	
	public function preUpdate() {
	}
	
	public function preRemove () {
	}
	
	public function preSave() {
		
			
			//log::add('sonoffdiy', 'debug', 'Presave '.$this->getName());
			try {
				foreach ($this->getCmd('info') as $cmd) {
					//log::add('sonoffdiy', 'info', '--------->Test CONF de la commande '.$cmd->getName().'--'.$cmd->execCmd());
					$this->setConfiguration($cmd->getLogicalId(), self::chiffreenOnOff($cmd->execCmd()));
				}
				$this->setConfiguration('LastMAJ', date('Y-m-d H:i:s'));
			}
			catch(Exception $exc) {log::add('sonoffdiy', 'error', __('Erreur pour ', __FILE__) . $this->getHumanName() . ' : ' . $exc->getMessage());}
		

	}
	
	public function chiffreenOnOff($unouzero) {
		// transforme 1 en On et 0 en Off
		if ($unouzero == '1') $unouzero="On";
		if ($unouzero == '0') $unouzero="Off";
		return $unouzero;
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
		
		// Enregistre dans configuration/value les valeurs pour pouvoir les afficher sur les ecrans du desktop et des commandes
		if ($this->getType() == 'info')	$this->setConfiguration('value', $this->execCmd());
	
		if ($this->getLogicalId() == 'refresh') {
			return;
			
		}
	}
	
	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		//log::add('sonoffdiy', 'debug', 'Execute '.$this->getLogicalId());
		//log::add('sonoffdiy', 'info', 'options : ' . json_encode($_options));//Request : http://192.168.0.21:3456/volume?value=50&device=G090LF118173117U
		
		if ($this->getLogicalId() == 'refresh') {
		//log::add('sonoffdiy', 'debug', 'Faut Execute1 '.$this->getLogicalId());
			$this->getEqLogic()->refresh();
		//log::add('sonoffdiy', 'debug', 'Faut Execute2 '.$this->getLogicalId());
			return;
		}
		
		$adresse_ip = $this->getEqLogic()->getConfiguration('adresse_ip');
		//log::add('sonoffdiy', 'debug', '----adresse_ip:'.$adresse_ip);
		$device_id = $this->getEqLogic()->getConfiguration('device_id');
		//log::add('sonoffdiy', 'debug', '----device_id:'.$device_id);
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	list($variable, $valeur) = explode('=', $arguments, 2);
	$parameter=(int)$this->getConfiguration('parameter');
	//log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur);
	//log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
	if ($_options['select'] != '') $valeur=$_options['select']; // Pour Etat Initial
	if ($_options['message'] != '') $parameter=$_options['message']; // pour Pulse ON
	

			$url = "http://".$adresse_ip.":8081/zeroconf/".$command; // Envoyer la commande Refresh via jeeAlexaapi
			$ch = curl_init($url);
			
			if ($command=="switch")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'switch'      => $valeur
				),
			);	
			
			if ($command=="startup")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'startup'      => $valeur
				),
			);				
		
			if ($command=="pulse")	
				if ($parameter>500) 
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur,
							'pulseWidth'      => $parameter,
						),
					);	
				 else 					
					$data = array(
						'deviceid'        => $device_id,
						'data'    => array(
							'pulse'      => $valeur
						),
					);
			$vide = (object)[];
			if (($command=="signal_strength") || ($command=="info")	)		
			$data = array(
				'deviceid'        => $device_id,
				'data'    => $vide,
			);	
			
			$payload = json_encode($data);
	log::add('sonoffdiy', 'info', '>> Envoyé à '.$eqLogic->getName().' : '.$url." ".$payload);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type:application/json'));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			//curl_setopt($ch, CURLOPT_HEADER, true); //TRUE pour inclure l'en-tête dans la valeur de retour
			$result = json_decode(curl_exec($ch),true);
			curl_close($ch);
			
		if (is_null($result)) {
			log::add('sonoffdiy', 'error', 'Souci sur la commande '.$this->getName().' de '.$eqLogic->getName());
			return false;
		}
		log::add('sonoffdiy', 'info', '<< Réponse de '.$eqLogic->getName().' : '.json_encode($result));
		//log::add('sonoffdiy', 'debug', '<< Data recues de '.$eqLogic->getName().' : '.$result['data']);
		//log::add('sonoffdiy', 'debug', '<< error recue de '.$eqLogic->getName().' : '.$result['error']);
		if ($result['error']!="0") {
			log::add('sonoffdiy', 'error', 'Souci sur la commande '.$this->getName().' de '.$eqLogic->getName().' error : '.$result['error']);
			// Pour avoir les codes erreur : https://github.com/itead/Sonoff_Devices_DIY_Tools/blob/master/SONOFF%20DIY%20MODE%20Protocol%20Doc%20v1.4.md
		}
		
			//log::add('sonoffdiy_mDNS', 'debug', '-----------------------------------------------------------');
		
		
		/*
		$data=json_decode($result['data'], true);
		self::enregistreCmdInfo('rssi', $data, $eqLogic);
		self::enregistreCmdInfo('switch', $data, $eqLogic);
		self::enregistreCmdInfo('startup', $data, $eqLogic);
		self::enregistreCmdInfo('pulse', $data, $eqLogic);
		self::enregistreCmdInfo('pulseWidth', $data, $eqLogic);
		self::enregistreCmdInfo('ssid', $data, $eqLogic);
		log::add('sonoffdiy', 'debug', 'Mise à jour '.$data);*/
		$_id=$eqLogic->getConfiguration('device_id');
		//$_id='1000ab1e9';
		//log::add('sonoffdiy_mDNS', 'debug', '$_id::: '.$_id);
		//log::add('sonoffdiy_mDNS', 'debug', 'data:1:: '.$result['data']);
		//$ss=json_decode($result['data'], true);
		//log::add('sonoffdiy_mDNS', 'debug', 'data:2:: '.$result['data']);
		//log::add('sonoffdiy_mDNS', 'debug', 'data:3:: '.json_decode($result['data'], true));
		$eqLogic->sauvegardeCmdsInfo(json_decode($result['data'], true), false, $_id);
		
		
		
		
		
		/*
				if (($this->getType() == 'action') && ($this->getConfiguration('infoName') != '')) {
				$LogicalIdCmd=$this->getConfiguration('infoName');
				
					//$cmd = cmd::byId(str_replace('#', '', $this->getConfiguration('infoName')));
					//return $cmd->execCmd($_options);
				
				//$cmd=$this->getEqLogic()->getCmd(null, $LogicalIdCmd);
				$cmd=cmd::byId($LogicalIdCmd);
				if (is_object($cmd)) { 
					log::add('sonoffdiy', 'debug', $LogicalIdCmd.' prévu dans infoName de '.$this->getName().' trouvé !');
					$this->getEqLogic()->checkAndUpdateCmd($cmd->getLogicalId(), time());
				} else {
					log::add('sonoffdiy', 'warning', $LogicalIdCmd.' prévu dans infoName de '.$this->getName().' mais non trouvé ! donc ignoré');
				} 
				}*/
		
		
		
		

		return true;
	}
		public function enregistreCmdInfo($_CmdInfo, $_Data, $_eqLogic) {
			//log::add('sonoffdiy_mDNS', 'debug', 'debut  enregistreCmdInfo ');
			//log::add('sonoffdiy_mDNS', 'debug', '$_Data1 : '.$_Data);
		
			if (array_key_exists($_CmdInfo,$_Data)) {
				
			//log::add('sonoffdiy_mDNS', 'debug', '$_CmdInfo : '.$_CmdInfo);
			//log::add('sonoffdiy_mDNS', 'debug', '$_Data2 : '.json_encode($_Data));
			//log::add('sonoffdiy_mDNS', 'debug', '>>>>>>>>>>>>>>Data[CmdInfo] : '.$_Data['switch']);
			//log::add('sonoffdiy_mDNS', 'debug', '>>>>>>>>>>>>>>Data[CmdInfo] : '.$_Data[$_CmdInfo]);
				if ($_Data[$_CmdInfo]!="") {
					$valeur_enregistree=$_Data[$_CmdInfo];
					//if ($_CmdInfo=='signalStrength') $_CmdInfo='rssi';
					log::add('sonoffdiy', 'debug', 'Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]);
					//if ($valeur_enregistree=='on') $valeur_enregistree=1;	if ($valeur_enregistree=='off') $valeur_enregistree=0;
					$_eqLogic->checkAndUpdateCmd($_CmdInfo, $valeur_enregistree);	
				}
			}
		//else log::add('sonoffdiy', 'debug', 'ERREUR Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]. "dans ".json_decode($_Data, true));
			//log::add('sonoffdiy_mDNS', 'debug', 'ERREUR Enregistrements dans '.json_decode($_Data, true));
		}
}