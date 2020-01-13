/* This file is part of Jeedom.
*
* Jeedom is free software: you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation, either version 3 of the License, or
* (at your option) any later version.
*
* Jeedom is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with Jeedom. If not, see <http://www.gnu.org/licenses/>.

*/
function CaseCocheeSamba()
{
	if (document.formulaire.caseSamba.checked==1) {
		document.formulaire.caseLocal.checked=0;
		document.formulaire.caseFacebook.checked=0;
		$('#albumsFacebook').parent().hide();
	}
}
function CaseCocheeLocal()
{
    if (document.formulaire.caseLocal.checked==1) {
		document.formulaire.caseSamba.checked=0;
		document.formulaire.caseFacebook.checked=0;
		$('#albumsFacebook').parent().hide();
	}
}
function CaseCocheeFacebook()
{
    if (document.formulaire.caseFacebook.checked==1) {
		document.formulaire.caseSamba.checked=0;
		document.formulaire.caseLocal.checked=0;
		$('#albumsFacebook').parent().show();
	}
}
function bt_enregistreAlbumFB()
{
	var albums = new Array();
	for (const album of albumsFacebook) {
		$value = $('.eqLogicAttr[data-l1key=configuration][data-l2key=albumsFacebook][data-l3key=albumfb_'+album.id+']').value();
		albums.push([album.id,$value]);
	}
  $.ajax({
      type: "POST", 
      url: "plugins/sonoffdiy/core/ajax/sonoffdiy.ajax.php", 
      data:
      {
          action: "enregistreAlbumFB",
		  albums: json_encode(albums),
		  id: $('.eqLogicAttr[data-l1key=id]').value()
      },
      dataType: 'json',
      error: function (request, status, error)
      {
          handleAjaxError(request, status, error);
      },
      success: function (data)
      { 
          if (data.state != 'ok') {
              $('#div_alert').showAlert({message: data.result, level: 'danger'});
              return;
          }
          window.location.reload();
      }
  });
}
function scanLienPhotos()
{
  $.ajax({
      type: "POST", 
      url: "plugins/sonoffdiy/core/ajax/sonoffdiy.ajax.php", 
      data:
      {
          action: "scanLienPhotos",
		  id: $('.eqLogicAttr[data-l1key=id]').value()
      },
      dataType: 'json',
      error: function (request, status, error)
      {
          handleAjaxError(request, status, error);
      },
      success: function (data)
      { 
          if (data.state != 'ok') {
              $('#div_alert').showAlert({message: data.result, level: 'danger'});
              return;
          }
          window.location.reload();
      }
  });
}
$('.eqLogicAttr[data-l1key=configuration][data-l2key=cheminsonoffdiyValide]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=cheminsonoffdiyValide]').value();
	if($icon != '' && $icon != null)
		$('#img_device').attr("src", 'plugins/sonoffdiy/desktop/images/' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=localEtat]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=localEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_local').attr("src", 'plugins/sonoffdiy/desktop/images/jeedom_' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=sambaEtat]').on('change', function ()
{
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=sambaEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_samba').attr("src", 'plugins/sonoffdiy/desktop/images/samba_' + $icon + '.png');
});
$('.eqLogicAttr[data-l1key=configuration][data-l2key=facebookEtat]').on('change', function ()
{
	//alert( 'Hello, world!' );
	$icon = $('.eqLogicAttr[data-l1key=configuration][data-l2key=facebookEtat]').value();
	if($icon != '' && $icon != null)
		$('#img_facebook').attr("src", 'plugins/sonoffdiy/desktop/images/facebook_' + $icon + '.png');
});
$('#bt_testLienPhotos').off('click').on('click', function () {
    scanLienPhotos();
});
$('#bt_cronGenerator').off('click').on('click',function(){
    jeedom.getCronSelectModal({},function (result) {
        $('.eqLogicAttr[data-l1key=configuration][data-l2key=autorefresh]').value(result.value);
    });
});
$('#bt_enregistreAlbumFB').off('click').on('click', function () {
    bt_enregistreAlbumFB();
});
$('#bt_configFacebook').off('click').on('click', function ()
{
  $('#md_modal').dialog({title: "{{Config Facebook}}"});
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=logicalId]').value()).dialog('open');
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=configuration][data-l2key=stockageLocal]').value()).dialog('open');
  $('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice='+ $('.eqLogicAttr[data-l1key=id]').value()).dialog('open');
  //$('#md_modal').load('index.php?v=d&plugin=sonoffdiy&modal=configFacebook&iddevice=123').dialog('open');

});
