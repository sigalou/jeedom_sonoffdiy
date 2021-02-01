<?php
if (!isConnect('admin')) {
	throw new Exception('{{401 - Accès non autorisé}}');
}
// Obtenir l'identifiant du plugin
$plugin = plugin::byId('sonoffdiy');
// Charger le javascript
sendVarToJS('eqType', $plugin->getId());
//sendVarToJS('serveurtest', 'lionel dans sonoffdiy.php');
// Accéder aux données du plugin
$eqLogics = eqLogic::byType($plugin->getId());
$logicalIdToHumanReadable = array();

?>

<!-- Container global (Ligne bootstrap) -->
<div class="row row-overflow">
  <!-- Container des listes de commandes / éléments -->
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
		<!-- + -->
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
			<i class="fas fa-plus-circle" style="font-size : 5em;color:#4fbdce;"></i>
			<br />
			<span style="color:#4fbdce">{{Ajouter}}</span>
		</div>
		<!-- Bouton d accès à la configuration -->
		<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
			<i class="fas fa-wrench" style="font-size : 5em;color:#4fbdce;"></i>
			<br />
			<span style="color:#4fbdce">{{Configuration}}</span>
		</div>
    </div>
    <!-- Début de la liste des objets -->
    <legend><i class="fas fa-table"></i> {{Mes Sonoff DIY}}</legend>
	<div class="input-group" style="margin-bottom:5px;">
		<input class="form-control roundedLeft" placeholder="{{Rechercher}}" id="in_searchEqlogic" />
		<div class="input-group-btn">
			<a id="bt_resetEqlogicSearch" class="btn roundedRight" style="width:30px"><i class="fas fa-times"></i></a>
		</div>
	</div>	
    <!-- Container de la liste -->
	<div class="panel">
		<div class="panel-body">
			<div class="eqLogicThumbnailContainer prem">
<?php
foreach($eqLogics as $eqLogic) {
	if (($eqLogic->getConfiguration('devicetype') != "Smarthome") && ($eqLogic->getConfiguration('devicetype') != "Player") && ($eqLogic->getConfiguration('devicetype') != "PlayList")) {
		$opacity = ($eqLogic->getIsEnable()) ? '' : ' disableCard';
		echo '<div class="eqLogicDisplayCard cursor prem '.$opacity.'" data-eqLogic_id="'.$eqLogic->getId().'" >';
		
		$alternateImg = $eqLogic->getConfiguration('device');
		if (file_exists(dirname(__FILE__).'/../../core/config/devices/'.$alternateImg.'.png'))
			echo '<img class="lazy" src="plugins/sonoffdiy/core/config/devices/'.$alternateImg.'.png" style="min-height:75px !important;" />';
		else
			echo '<img class="lazy" src="'.$plugin->getPathImgIcon().'" style="min-height:75px !important;" />';

		
		echo "<br />";
		echo '<span class="name">'.$eqLogic->getHumanName(true, true).'</span>';
		echo '</div>';
	}
}
?>
			</div>
		</div>
    </div>
	
  </div>
  <!-- Container du panneau de contrôle -->
  <div class="col-lg-12 eqLogic" style="display: none;">
    <!-- Bouton sauvegarder -->
    <a class="btn btn-success eqLogicAction pull-right" data-action="save"><i class="fas fa-check-circle"></i> {{Sauvegarder}}</a>
    <!-- Bouton Supprimer -->
    <a class="btn btn-danger eqLogicAction pull-right" data-action="remove"><i class="fas fa-minus-circle"></i> {{Supprimer}}</a>
    <!-- Bouton configuration avancée -->
    <a class="btn btn-default eqLogicAction pull-right" data-action="configure"><i class="fas fa-cogs"></i> {{Configuration avancée}}</a>
   <!-- Liste des onglets -->
    <ul class="nav nav-tabs" role="tablist">
      <!-- Bouton de retour -->
      <li role="presentation"><a class="eqLogicAction cursor" aria-controls="home" role="tab" data-action="returnToThumbnailDisplay"><i class="fa fa-arrow-circle-left"></i></a></li>
      <!-- Onglet "Equipement" -->
      <li role="presentation" class="active"><a href="#eqlogictab" aria-controls="home" role="tab" data-toggle="tab"><i class="fas fa-tachometer-alt"></i> {{Equipement}}</a></li>
      <!-- Onglet "Commandes" -->
      <li role="presentation"><a href="#commandtab" aria-controls="profile" role="tab" data-toggle="tab"><i class="fas fa-list-alt"></i> {{Commandes}}</a></li>
    </ul>
    <!-- Container du contenu des onglets -->
    <div class="tab-content" style="height:calc(100% - 50px);overflow:auto;overflow-x: hidden;">
      <div role="tabpanel" class="tab-pane active" id="eqlogictab">
        <br/>
        <div class="row">
          <div class="col-sm-6">
            <form name="formulaire" class="form-horizontal">
              <fieldset>
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Nom de l'équipement Jeedom}}</label>
                  <div class="col-sm-8">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="name" placeholder="{{Nom de l'équipement Amazon}}"/>
                  </div>
                </div>
                <!-- Onglet "Objet Parent" -->
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Objet parent}}</label>
                  <div class="col-sm-6">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="id" style="display : none;"/>
                    <select class="eqLogicAttr form-control" data-l1key="object_id">
                    <option value="">{{Aucun}}</option>
<?php
foreach (jeeObject::all() as $object)
    echo '<option value="' . $object->getId() . '">' . $object->getName() . '</option>';
?>
                    </select>
                  </div>
                </div>

                <!-- Catégorie" -->
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Catégorie}}</label>
                  <div class="col-sm-8">
<?php
foreach (jeedom::getConfiguration('eqLogic:category') as $key => $value)
{
    echo '<label class="checkbox-inline">';
    echo '  <input type="checkbox" class="eqLogicAttr" data-l1key="category" data-l2key="' . $key . '" />' . $value['name'];
    echo '</label>';
}
?>
                  </div>
                </div>
                <!-- Onglet "Active Visible" -->
                <div class="form-group">
                  <label class="col-sm-4 control-label"></label>
                  <div class="col-sm-8">
                    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isEnable" checked/>{{Activer}}</label>
                    <label class="checkbox-inline"><input type="checkbox" class="eqLogicAttr" data-l1key="isVisible" checked/>{{Visible}}</label>
                  </div>
                </div><br><br>



				
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Adresse IP}}</label>
                  <div class="col-sm-8">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="adresse_ip" placeholder="{{192.168.0.xx}}"/>
                  </div>
                </div>	
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Device ID}}</label>
                  <div class="col-sm-8">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device_id" placeholder="{{xxxxxxxxxx}}"/>
                  </div>
                </div>					
              </fieldset>
            </form>
          </div>
					<div class="col-sm-6">
						<form class="form-horizontal">
							<fieldset>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Equipement}}</label>
									<div class="col-sm-6">
										<select class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="device">
										<option value="">Aucun</option>
										<option value="mini">Sonoff DIY MINI</option>
										<option value="miniR2">Sonoff DIY MINI R2</option>
										<option value="basicR3">Sonoff DIY Basic R3</option>
										<option value="RFR3">Sonoff DIY RF R3</option>
										</select>
									</div>
								</div>
								<div class="form-group modelList" style="display:none;">
									<label class="col-sm-3 control-label">{{Modèle}}</label>
									<div class="col-sm-6">
										<select class="eqLogicAttr form-control listModel" data-l1key="configuration" data-l2key="iconModel">
										</select>
									</div>
								</div>
								<div id="div_instruction"></div>
								<div class="form-group">
									<label class="col-sm-3 control-label">{{Création}}</label>
									<div class="col-sm-3">
										<span class="eqLogicAttr label label-default" data-l1key="configuration" data-l2key="createtime" title="{{Date de création de l'équipement}}" style="font-size : 1em;cursor : default;"></span>
									</div>
									<label class="col-sm-3 control-label">{{Communication}}</label>
									<div class="col-sm-3">
										<span class="eqLogicAttr label label-default" data-l1key="status" data-l2key="lastCommunication" title="{{Date de dernière communication}}" style="font-size : 1em;cursor : default;"></span>
									</div>
								</div>
								<div class="form-group">
									<label class="col-sm-3 control-label ">{{SSID}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="ssid"></span>
									</div>
									<label class="col-sm-3 control-label ">{{RSSI}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="rssi"></span>
									</div>								
									</div>
								<div class="form-group">
									<label class="col-sm-3 control-label ">{{Etat de la fonction Pulse}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="pulse"></span>
									</div>
									<label class="col-sm-3 control-label ">{{Tempo de la fonction Pulse}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="pulseWidth"></span>
									</div>								
									</div>								
								<div class="form-group">
									<label class="col-sm-3 control-label ">{{Etat à la mise sous tension}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="startup"></span>
									</div>
									<label class="col-sm-3 control-label ">{{Dernière Mise à jour}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="LastMAJ"></span>
									</div>								
									</div>	
								<div class="form-group">
									<label class="col-sm-3 control-label ">{{ID détectée}}</label>
									<div class="col-sm-3 ">
										<span class="eqLogicAttr label label-default" style="font-size : 1em;cursor : default;" data-l1key="configuration" data-l2key="IDdetectee"></span>
									</div>
										<br><br>									
									<center>
									<img src="core/img/no_image.gif" data-original=".jpg" id="img_device" class="img-responsive" style="max-height : 250px;"  onerror="this.src='plugins/sonoffdiy/plugin_info/sonoffdiy_icon.png'"/>
								</center>
							</fieldset>
						</form>
					</div>
      </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab">
        

        <table id="table_cmd_actions" class="table table-bordered table-condensed">
          <thead>
            <tr>

			  
			  <th style="width: 40px;">#</th>
              <th style="width: 200px;">{{Nom de la commande}}</th>
              <th style="width: 300px;">{{Action}}</th>
              <th style="width: 200px;">{{Commande}}</th>
              <th style="width: 200px;">{{Paramètre}}</th>
              <th style="width: 200px;">{{Options}}</th>
              <th style="width: 100px;"></th>
            </tr>
          </thead>
          <tbody>

          </tbody>
        </table>
		
        <table id="table_cmd_infos" class="table table-bordered table-condensed">
          <thead>
            <tr>

			  
			  <th style="width: 40px;">#</th>
              <th style="width: 200px;">{{Nom de l'info}}</th>
              <th style="width: 300px;">{{Valeur}}</th>
              <th style="width: 400px;"></th>
              <th style="width: 200px;">{{Options}}</th>
              <th style="width: 100px;"></th>
            </tr>
          </thead>
          <tbody>

          </tbody>
        </table>
		
		<form class="form-horizontal">
          <fieldset>
            <div class="form-actions">
            </div>
          </fieldset>
        </form>
		
      </div>
    </div>
  </div>
</div>
<?php include_file('desktop', 'sonoffdiy', 'js', 'sonoffdiy'); ?>
<?php include_file('desktop', 'sonoffdiy', 'css', 'sonoffdiy'); ?>
<?php include_file('core', 'plugin.template', 'js'); ?>
<script>
$('#in_searchEqlogic').off('keyup').keyup(function () {
  var search = $(this).value().toLowerCase();
  search = search.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
  if(search == ''){
    $('.eqLogicDisplayCard.prem').show();
    $('.eqLogicThumbnailContainer.prem').packery();
    return;
  }
  $('.eqLogicDisplayCard.prem').hide();
  $('.eqLogicDisplayCard.prem .name').each(function(){
    var text = $(this).text().toLowerCase();
    text = text.normalize('NFD').replace(/[\u0300-\u036f]/g, "")
    if(text.indexOf(search) >= 0){
      $(this).closest('.eqLogicDisplayCard.prem').show();
    }
  });
  $('.eqLogicThumbnailContainer.prem').packery();
});
</script>
