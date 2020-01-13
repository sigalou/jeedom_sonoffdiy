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
foreach ($eqLogics as $eqLogic)
{
  $logicalIdToHumanReadable[$eqLogic->getLogicalId()] = $eqLogic->getHumanName(true, false);
}
//if faut envoyer config::byKey('albumsFacebook', 'sonoffdiy', '0') à JS
sendVarToJS('albumsFacebook', config::byKey('albumsFacebook', 'sonoffdiy', '0'));
// Pour savoir si on va afficher ou pas le bloc #albumsFacebook en JS
  if (is_object($eqLogic)) {
		if ($eqLogic->getConfiguration('facebookEtat')=="ok")
			sendVarToJS('facebookEtat', "ok");
		else
			sendVarToJS('facebookEtat', "nok");
  }
//echo $eqLogic->getConfiguration('facebookEtat');
?>
<script>
if (facebookEtat == "ok") 
	$('#albumsFacebook').parent().show(); 
else 
	$('#albumsFacebook').parent().hide();
   // alert( 'facebookEtat='+facebookEtat );
</script>
<!-- Container global (Ligne bootstrap) -->
<div class="row row-overflow">
  <!-- Container des listes de commandes / éléments -->
  <div class="col-xs-12 eqLogicThumbnailDisplay">
    <legend><i class="fas fa-cog"></i> {{Gestion}}</legend>
    <div class="eqLogicThumbnailContainer">
		<!-- + -->
      <div class="cursor eqLogicAction logoPrimary" data-action="add">
			<i class="fas fa-plus-circle" style="font-size : 5em;color:#a15bf7;"></i>
			<br />
			<span style="color:#a15bf7">{{Ajouter}}</span>
		</div>
		<!-- Bouton d accès à la configuration -->
		<div class="cursor eqLogicAction logoSecondary" data-action="gotoPluginConf">
			<i class="fas fa-wrench" style="font-size : 5em;color:#a15bf7;"></i>
			<br />
			<span style="color:#a15bf7">{{Configuration}}</span>
		</div>
    </div>
    <!-- Début de la liste des objets -->
    <legend><i class="fas fa-table"></i> {{Mes sonoffdiys}}</legend>
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
                </div>
				<div class="form-group">
			<label class="col-sm-4 control-label">{{Actualisation du sonoffdiy}}</label>
				<div class="col-sm-8">
					<div class="input-group">
					<input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="autorefresh" placeholder="{{Auto-actualisation (cron)}}"/>
					<span class="input-group-btn">
					<a class="btn btn-success btn-sm " id="bt_cronGenerator" ><i class="fas fa-question-circle"></i></a>
					</span>
					</div>
				</div>
			</div>
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Largeur des images}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="largeurPhoto" placeholder="{{250}}"/>
                  </div>
                </div>
							<div class="form-group">
                  <label class="col-sm-4 control-label"></label>
                  <div class="col-sm-8">
				<input type="checkbox" style="position:relative;top:2px;" class="eqLogicAttr" title="Les photos sont stockées sur la même machine que celle de Jeedom" data-l1key="configuration" data-l2key="centrerLargeur"/> {{Centrer sur la largeur}}
                  </div>
                </div>
				
				
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Hauteur des images}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="hauteurPhoto" placeholder="{{250}}"/>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Angles arrondis}}</label>
                  <div class="col-sm-2">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="arrondiPhoto" placeholder="{{30%}}"/>
                  </div>
                </div>				<div class="form-group">
                <label class="col-sm-4 control-label">{{Nombre de photos à générer<br>(2 par défaut)}}</label>
                <div class="col-sm-6">
                    <select class="eqLogicAttr form-control" data-l1key='configuration' data-l2key='nbPhotosaGenerer'>
                        <option value='1'>1</option>
                        <option value='2' selected>2</option>
                        <option value='3'>3</option>
                        <option value='4'>4</option>
                        <option value='5'>5</option>
                        <option value='6'>6</option>
                        <option value='7'>7</option>
                        <option value='8'>8</option>                    
                        <option value='9'>9</option>				
						</select>
                </div>
            </div>
			
			<br><br> 
			<div class="form-group">
                  <label class="col-sm-4 control-label"></label>
                  <div class="col-sm-8">
				<input type="checkbox" name='caseLocal' onclick="setTimeout(function(){CaseCocheeLocal()},300)" style="position:relative;top:2px;" class="eqLogicAttr" title="Les photos sont stockées sur la même machine que celle de Jeedom" data-l1key="configuration" data-l2key="stockageLocal"/> {{Stockage des photos en local}}
                  </div>
                </div>
				
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Chemin local des photos}}</label>
                  <div class="col-sm-8">
                    <input type="text" class="eqLogicAttr form-control" data-l1key="configuration" data-l2key="cheminsonoffdiy" placeholder="{{/../images/}}"/>
                  </div>
                </div>	
				
			<br><br> 
			<div class="form-group">
                  <label class="col-sm-4 control-label"></label>
                  <div class="col-sm-8">
				<input type="checkbox" name='caseFacebook' onclick="setTimeout(function(){CaseCocheeFacebook()},300)" style="position:relative;top:2px;" class="eqLogicAttr" title="Les photos sont sur un compte Facebook" data-l1key="configuration" data-l2key="stockageFacebook"/> {{Utilisation des photos Facebook}}
                  </div>
                </div>

				

<?php

// Pour SAMBA
$sambaActif	= config::byKey('samba::enable')	;
//log::add('sonoffdiy', 'debug', "sambaActif:".$sambaActif);				
	if ($sambaActif)
	{
		//echo 'Samba est actif';
	$sambaIP	= config::byKey('samba::backup::ip')	;
	$sambaUsername	= config::byKey('samba::backup::username')	;
	$sambaPassword	= config::byKey('samba::backup::password')	;
	$sambaShare	= config::byKey('samba::backup::share')	;
	$sambaFolder	= config::byKey('samba::backup::folder')	;
		?>
				<br><br>
							<div class="form-group">
					  <label class="col-sm-4 control-label"></label>
					  <div class="col-sm-8">
					<input type="checkbox" name='caseSamba' onclick="setTimeout(function(){CaseCocheeSamba()},300)" style="position:relative;top:2px;" class="eqLogicAttr" title="Les photos sont accessibles via Samba" data-l1key="configuration" data-l2key="stockageSamba"/> {{Stockage des photos sur le réseau via Samba}}
					  </div>
					</div>
					
					<div class="form-group">
					  <label class="col-sm-4 control-label">{{Chemin Samba des photos}}</label>
					  <div class="col-sm-8"><?php echo $sambaShare?>
						<input type="text" name="inputSamba" class="eqLogicAttr form-control" style="width: 50%" data-l1key="configuration" data-l2key="dossierSambasonoffdiy" placeholder="{{/mesPhotos}}"/>
					  </div>
					</div>	
					<br>
	<?php	
	}
	else
	{?><br><br>						<div class="form-group">
					  <label class="col-sm-4 control-label"></label>
					  <div class="col-sm-8">
					<input type="checkbox" name='caseSamba' onclick="setTimeout(function(){CaseCocheeSamba()},300)" style="position:relative;top:2px;" class="eqLogicAttr" title="Les photos sont accessibles via Samba" data-l1key="configuration" data-l2key="stockageSamba"/> {{Stockage des photos en réseau via Samba}}
					  </div>
					</div>
					
					<div class="form-group">
					  <label class="col-sm-4 control-label">{{Chemin Samba des photos}}</label>
					  <div class="col-sm-8">
						<b>Samba</b> est inactif dans la configuration de Jeedom, donc impossible d'utiliser un chemin Samba
					  </div>
					</div>				
	<?php
	}
	?>
              </fieldset>
            </form>
          </div>
          <div class="col-sm-6 alert-<?php
		  $stockageSamba="";
		  if (is_object($eqLogic)) {
				if ($eqLogic->getConfiguration('cheminsonoffdiyValide')=="ok")
				  echo "success";
				elseif ($eqLogic->getConfiguration('cheminsonoffdiyValide')=="nok")
				  echo "danger";		  
				else
				  echo "warning";
			  $stockageSamba=$eqLogic->getConfiguration('stockageSamba');
			  $stockageFacebook=$eqLogic->getConfiguration('stockageFacebook');
		  }			  
		  ?> ">
            <br><br><form class="form-horizontal">
              <fieldset>
				<span style="display:none" class="eqLogicAttr" data-l1key="configuration" data-l2key="cheminsonoffdiyValide"></span>
				<span style="display:none" class="eqLogicAttr" data-l1key="configuration" data-l2key="localEtat"></span>
				<span style="display:none" class="eqLogicAttr" data-l1key="configuration" data-l2key="sambaEtat"></span>
				<span style="display:none" class="eqLogicAttr" data-l1key="configuration" data-l2key="facebookEtat"></span>
				<div class="form-group">
				  <label class="col-sm-4 control-label">{{Lien au dossier Photos}}</label>
                      <img style="max-height : 40px;float:left;margin:0 10px 0 10px;" src="core/img/no_image.gif"  id="img_device" class="img-responsive" title="Etat de la connexion au dossier Photos" onerror="this.src='plugins/sonoffdiy/images/question.png'"/>
					  <img style="max-height : 40px;float:left;margin:0 10px 0 10px;" src="core/img/no_image.gif"  id="img_local" class="img-responsive" title="Etat de la connexion au dossier Local" onerror="this.src='plugins/sonoffdiy/images/question.png'"/>
					  <img style="max-height : 40px;float:left;margin:0 10px 0 10px;" src="core/img/no_image.gif"  id="img_samba" class="img-responsive" title="Etat de la connexion au dossier via Samba" onerror="this.src='plugins/sonoffdiy/images/question.png'"/>
					  <img style="max-height : 40px;float:left;margin:0 10px 0 10px;" src="core/img/no_image.gif"  id="img_facebook" class="img-responsive" title="Etat de la connexion au compte Facebook" onerror="this.src='plugins/sonoffdiy/images/question.png'"/>				
					  </div>
				
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Les photos sont sur}}</label>
                  <div class="col-sm-8">
                      <span style="position:relative;top:+5px;" class="eqLogicAttr" data-l1key="configuration" data-l2key="cheminsonoffdiyComplet"></span>
                  </div>
                </div>
                <div class="form-group" id="family">
                  <label class="col-sm-4 control-label">{{Nombre de photos}}</label>
                  <div class="col-sm-8">
                      <span style="position:relative;top:+5px;left:+5px;" class="eqLogicAttr" data-l1key="configuration" data-l2key="nombrePhotos"></span>
                  </div>
                </div>
                <div class="form-group">
                  <label class="col-sm-4 control-label">{{Dernière mise à jour de l'état du lien}}</label>
                  <div class="col-sm-8">
                      <span style="position:relative;top:+5px;" class="eqLogicAttr" data-l1key="configuration" data-l2key="derniereMAJ"></span>
                  </div>
                </div><div class="form-group">
                  <label class="col-sm-4 control-label">{{}}</label>
                  <div class="col-sm-8">
                      <span style="position:relative;top:+5px;" class="eqLogicAttr" data-l1key="configuration" data-l2key="cheminsonoffdiyMessage"></span>
                  </div>
                </div><br>
				
		<?php		
// Pour savoir si on va afficher ou pas le bloc #albumsFacebook en JS
// Bug non trouvé qu'avec JS, quand on fait Samba puis test, la liste réapparait, donc ajout de ce code en php
		  if ((is_object($eqLogic)) && ($eqLogic->getConfiguration('facebookEtat')=="ok")) {?>	

		  <?php }
		  if ($stockageSamba=="1")
			  echo '<center><a id="bt_testLienPhotos" class="btn btn-default pull-center"><i class="far fa-check-circle"></i> {{Tester le lien vers le dossier SAMBA des photos}}</a></center><br>';
		  if ($stockageFacebook=="1")
			  echo '<center><a id="bt_configFacebook" class="btn btn-primary pull-center"><i class="far fa-check-circle"></i> {{Sélectionner les albums photos Facebook}}</a></center><br>';
		  ?> 
			 </fieldset>			 
</form>			 
 
        </div>
      </div>
      </div>

      <div role="tabpanel" class="tab-pane" id="commandtab">
        

        <table id="table_cmd" class="table table-bordered table-condensed">
          <thead>
            <tr>
              <th style="width: 40px;">#</th>
              <th style="width: 200px;">{{Nom}}</th>
              <th style="width: 150px;">{{Type}}</th>
              <th style="width: 300px;">{{Commande & Variable}}</th>
              <th style="width: 40px;">{{Min}}</th>
              <th style="width: 40px;">{{Max}}</th>
              <th style="width: 150px;">{{Paramètres}}</th>
              <th style="width: 100px;"></th>
            </tr>
          </thead>
          <tbody>

          </tbody>
        </table>
		

		
		<form class="form-horizontal">
          <fieldset>
            <div class="form-actions">
              <a class="btn btn-success btn-sm cmdAction" id="bt_addespeasyAction"><i class="fa fa-plus-circle"></i> {{Ajouter une commande action}}</a>
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
