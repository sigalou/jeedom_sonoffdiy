<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class sonoffdiy extends eqLogic {
	

	public function refresh() {
		
			//log::add('sonoffdiy', 'info', '--Refresh');
			
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
					log::add('sonoffdiy', 'debug', 'postSAVE ');
					
				
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
					$cmd->setName('State');
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
					$cmd->setName('Startup Info');
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
					$cmd->setName('Pulse Info');
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
					$cmd->setName('Pulse Delay (ms)');
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
					$cmd->setName('SSID : ');
					$cmd->setIsVisible(1);
					$cmd->setOrder(5);
					//$cmd->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$cmd->setDisplay('forceReturnLineBefore', true);
				}
				$cmd->save();
								
				$cmd = $this->getCmd(null, 'signalStrength');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('info');
					$cmd->setLogicalId('signalStrength');
					$cmd->setSubType('string');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('RSSI : ');
					$cmd->setIsVisible(1);
					$cmd->setOrder(6);
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
	log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur);
	log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
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
		
		
		
		
		$data=json_decode($result['data'], true);
		self::enregistreCmdInfo('signalStrength', $data, $eqLogic);
		self::enregistreCmdInfo('switch', $data, $eqLogic);
		self::enregistreCmdInfo('startup', $data, $eqLogic);
		self::enregistreCmdInfo('pulse', $data, $eqLogic);
		self::enregistreCmdInfo('pulseWidth', $data, $eqLogic);
		self::enregistreCmdInfo('ssid', $data, $eqLogic);
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
		if ($_Data[$_CmdInfo]!="") {
			$valeur_enregistree=$_Data[$_CmdInfo];
			//log::add('sonoffdiy', 'debug', 'Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]);
			//if ($valeur_enregistree=='on') $valeur_enregistree=1;	if ($valeur_enregistree=='off') $valeur_enregistree=0;
			$_eqLogic->checkAndUpdateCmd($_CmdInfo, $valeur_enregistree);	
		}		
		}
}