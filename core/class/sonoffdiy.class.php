<?php
require_once dirname(__FILE__) . '/../../../../core/php/core.inc.php';
class sonoffdiy extends eqLogic {
	
	
	public function refresh() {
		try {
			foreach ($this->getCmd('info') as $cmd) {
				$value = $cmd->execute();
				log::add('sonoffdiy', 'debug', 'Refresh de '.$cmd->getName());

				//if ($cmd->execCmd() != $cmd->formatValue($value)) {
				//	$cmd->event($value);
				//}
			}
		} catch (Exception $exc) {
			log::add('virtual', 'error', __('Erreur pour ', __FILE__) . $eqLogic->getHumanName() . ' : ' . $exc->getMessage());
		}
	}
	
	
	public function postSave() {
					log::add('sonoffdiy', 'debug', 'postSAVE ');
					
				
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
			$refresh->save();
		}


				$cmd = $this->getCmd(null, 'Off');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Off');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Off');
					$cmd->setConfiguration('request', 'switch?command=off');
					$cmd->setConfiguration('expliq', 'Eteindre');
					$cmd->setDisplay('title_disable', 1);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'Info');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('Info');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Info');
					$cmd->setConfiguration('request', 'info');
					$cmd->setDisplay('title_disable', 1);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
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
					$cmd->setConfiguration('expliq', 'Allumer');
					$cmd->setDisplay('title_disable', 1);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
				}
				$cmd->save();
				
				$cmd = $this->getCmd(null, 'PulseOff');
				if (!is_object($cmd)) {
					$cmd = new sonoffdiyCmd();
					$cmd->setType('action');
					$cmd->setLogicalId('PulseOff');
					$cmd->setSubType('message');
					$cmd->setEqLogic_id($this->getId());
					$cmd->setName('Pulse Off');
					//$cmd->setConfiguration('parameter', '5000');					
					$cmd->setConfiguration('request', 'pulse?command=off');
					$cmd->setConfiguration('expliq', 'Désactive le mode Pulse');
					$cmd->setDisplay('title_disable', 1);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);
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
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					$cmd->setIsVisible(1);

				}
				$cmd->save();
				
				$signalinfo = $this->getCmd(null, 'switch');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('switch');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('Switch Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();
				
				$signalinfo = $this->getCmd(null, 'startup');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('startup');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('Startup Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();

				
				$signalinfo = $this->getCmd(null, 'pulse');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('pulse');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('Pulse Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();
				
				
				$signalinfo = $this->getCmd(null, 'pulseWidth');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('pulseWidth');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('Pulse Time Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();
				
				
				$signalinfo = $this->getCmd(null, 'ssid');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('ssid');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('SSID Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();
								
				$signalinfo = $this->getCmd(null, 'signalStrength');
				if (!is_object($signalinfo)) {
					$signalinfo = new sonoffdiyCmd();
					$signalinfo->setType('info');
					$signalinfo->setLogicalId('signalStrength');
					$signalinfo->setSubType('string');
					$signalinfo->setEqLogic_id($this->getId());
					$signalinfo->setName('Signal Info');
					$signalinfo->setIsVisible(1);
					$signalinfo->setOrder(21);
					//$signalinfo->setDisplay('icon', '<i class="fa fa-volume-up"></i>');
					//$signalinfo->setDisplay('forceReturnLineBefore', true);
				}
				$signalinfo->save();
				
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
					$cmd->setDisplay('title_disable', 1);
					//$cmd->setDisplay('icon', '<i class="fa jeedomapp-audiospeak"></i>');
					//$cmd->setValue($signal->getId());
					//log::add('sonoffdiy', 'debug', '***setValue:'.$signal->getId());
					$cmd->setConfiguration('infoName', $signalinfo->getId());
					$cmd->setIsVisible(1);
				}
				$cmd->save();		
			

			//$signalinfo = $this->getCmd(null, 'signal');
			$signalaction = $this->getCmd(null, 'signal_strength');
					if((is_object($signalinfo)) && (is_object($signalaction))) {
					$signalaction->setValue($signalinfo->getId());// Lien 
					$signalaction->save();
				log::add('sonoffdiy', 'debug', '***lien:signalaction/signalinfo');
				}
/**/
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
		if ($this->getLogicalId() == 'refresh') {
			return;
		}
	}
	
	public function execute($_options = null) {
		$eqLogic = $this->getEqLogic();
		//log::add('sonoffdiy', 'debug', 'Execute');
		//log::add('sonoffdiy', 'info', 'options : ' . json_encode($_options));//Request : http://192.168.0.21:3456/volume?value=50&device=G090LF118173117U
		
		if ($this->getLogicalId() == 'refresh') {
			$this->getEqLogic()->refresh();
			return;
		}
		$adresse_ip = $this->getEqLogic()->getConfiguration('adresse_ip');
		//log::add('sonoffdiy', 'debug', '----adresse_ip:'.$adresse_ip);
		$device_id = $this->getEqLogic()->getConfiguration('device_id');
		//log::add('sonoffdiy', 'debug', '----device_id:'.$device_id);
	if ($this->getType() != 'action') return $this->getConfiguration('request');
	list($command, $arguments) = explode('?', $this->getConfiguration('request'), 2);
	//log::add('sonoffdiy', 'info', '----Command:*'.$command.'* arguments:'.$arguments);
	list($variable, $valeur) = explode('=', $arguments, 2);
	$parameter=(int)$this->getConfiguration('parameter');
	//log::add('sonoffdiy', 'info', '----variable:*'.$variable.'* valeur:'.$valeur);

			$url = "http://".$adresse_ip.":8081/zeroconf/".$command; // Envoyer la commande Refresh via jeeAlexaapi
			$ch = curl_init($url);
			if ($command=="switch")			
			$data = array(
				'deviceid'        => $device_id,
				'data'    => array(
					'switch'      => $valeur
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
			log::add('sonoffdiy', 'debug', 'Enregistrement de '.$_CmdInfo.' dans '.$_eqLogic->getName().' : '.$_Data[$_CmdInfo]);
			$_eqLogic->checkAndUpdateCmd($_CmdInfo, $_Data[$_CmdInfo]);	
		}		
		}
}