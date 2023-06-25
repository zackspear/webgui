function displayconsole(url) {
  window.open(url, '_blank', 'scrollbars=yes,resizable=yes');
}

function downloadFile(source) {
  var a = document.createElement('a');
  a.setAttribute('href',source);
  a.setAttribute('download',source.split('/').pop());
  a.style.display = 'none';
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
}

function ajaxVMDispatch(params, spin){
  if (spin) $('#vm-'+params['uuid']).parent().find('i').removeClass('fa-play fa-square fa-pause').addClass('fa-refresh fa-spin');
  $.post("/plugins/dynamix.vm.manager/include/VMajax.php", params, function(data) {
    if (data.error) {
      swal({
        title:_("Execution error"), html:true,
        text:data.error, type:"error",
        confirmButtonText:_('Ok')
      },function(){
        if (spin) setTimeout(spin+'()',500); else location=window.location.href;
      });
    } else {
      if (spin) setTimeout(spin+'()',500); else location=window.location.href;
    }
  },'json');
}
function ajaxVMDispatchconsole(params, spin){
  if (spin) $('#vm-'+params['uuid']).parent().find('i').removeClass('fa-play fa-square fa-pause').addClass('fa-refresh fa-spin');
  $.post("/plugins/dynamix.vm.manager/include/VMajax.php", params, function(data) {
    if (data.error) {
      swal({
        title:_("Execution error"), html:true,
        text:data.error, type:"error",
        confirmButtonText:_('Ok')
      },function(){
        if (spin) setTimeout(spin+'()',500); else location=window.location.href;
      });
    } else {
      if (spin) setTimeout(spin+'()',500); else location=window.location.href;
      setTimeout('displayconsole("'+data.vmrcurl+'")',500) ;
    }
  },'json');
}
function ajaxVMDispatchconsoleRV(params, spin){
  if (spin) $('#vm-'+params['uuid']).parent().find('i').removeClass('fa-play fa-square fa-pause').addClass('fa-refresh fa-spin');
  $.post("/plugins/dynamix.vm.manager/include/VMajax.php", params, function(data) {
    if (data.error) {
      swal({
        title:_("Execution error"), html:true,
        text:data.error, type:"error",
        confirmButtonText:_('Ok')
      },function(){
        if (spin) setTimeout(spin+'()',500); else location=window.location.href;
      });
    } else {
      if (spin) setTimeout(spin+'()',500); else location=window.location.href;
      downloadFile(data.vvfile) ;
    }
  },'json');
}
function addVMContext(name, uuid, template, state, vmrcurl, vmrcprotocol, log, console="web"){  
  var opts = [];
  var path = location.pathname;
  var x = path.indexOf("?");
  if (x!=-1) path = path.substring(0,x);
  if (vmrcurl !== "" && state == "running")  {
    if (console == "web" || console == "both") {
      var vmrctext=_("VM Console") + "(" + vmrcprotocol + ")" ;
      opts.push({text:vmrctext, icon:"fa-desktop", action:function(e) {
        e.preventDefault();
        window.open(vmrcurl, '_blank', 'scrollbars=yes,resizable=yes');
      }});
    }
    if (console == "remote" || console == "both") {
      opts.push({text:_("VM remote-viewer")+ "(" + vmrcprotocol + ")" , icon:"fa-desktop", action:function(e) {
        e.preventDefault();
        ajaxVMDispatchconsoleRV({action:"domain-consoleRV", uuid:uuid, vmrcurl:vmrcurl}, "loadlist") ;  
      }});
    }
  
    opts.push({divider:true});
  }
  context.settings({right:false,above:false});
  if (state == "running") {
    opts.push({text:_("Stop"), icon:"fa-stop", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-stop", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Pause"), icon:"fa-pause", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-pause", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Restart"), icon:"fa-refresh", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-restart", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Hibernate"), icon:"fa-bed", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-pmsuspend", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Force Stop"), icon:"fa-bomb", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch( {action:"domain-destroy", uuid:uuid}, "loadlist");
    }});
  } else if (state == "pmsuspended") {
    opts.push({text:_("Resume"), icon:"fa-play", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-pmwakeup", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Force Stop"), icon:"fa-bomb", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-destroy", uuid:uuid}, "loadlist");
    }});
  } else if (state == "paused" || state == "unknown") {
    opts.push({text:_("Resume"), icon:"fa-play", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-resume", uuid:uuid}, "loadlist");
    }});
    opts.push({text:_("Force Stop"), icon:"fa-bomb", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-destroy", uuid:uuid}, "loadlist");
    }});
  } else {
    opts.push({text:_("Start"), icon:"fa-play", action:function(e) {
      e.preventDefault();
      ajaxVMDispatch({action:"domain-start", uuid:uuid}, "loadlist");
    }});
    if (vmrcprotocol == "VNC" || vmrcprotocol == "SPICE") { 
      if (console == "web" || console == "both")  {
        opts.push({text:_("Start with console")+ "(" + vmrcprotocol + ")" , icon:"fa-play", action:function(e) {
          e.preventDefault();
          ajaxVMDispatchconsole({action:"domain-start-console", uuid:uuid, vmrcurl:vmrcurl}, "loadlist") ;  
        }});}
      if (console == "remote" || console == "both")  {
        opts.push({text:_("Start with remote-viewer")+ "(" + vmrcprotocol + ")" , icon:"fa-play", action:function(e) {
          e.preventDefault();
          ajaxVMDispatchconsoleRV({action:"domain-start-consoleRV", uuid:uuid, vmrcurl:vmrcurl}, "loadlist") ;  
        }});
      }
  }}
  opts.push({divider:true});
  if (log !== "") {
    opts.push({text:_("Logs"), icon:"fa-navicon", action:function(e){e.preventDefault(); openTerminal('log',name,log);}});
  }
  opts.push({text:_("Edit"), icon:"fa-pencil", href:path+'/UpdateVM?uuid='+uuid});

  if (state == "shutoff") {

    opts.push({text:_("Clone"), icon:"fa-clone", action:function(e) {
      e.preventDefault();
      var clonename = VMClone(uuid,name) ;
    }});

    opts.push({divider:true});
    opts.push({text:_("Remove VM"), icon:"fa-minus", action:function(e) {
      e.preventDefault();
      swal({
        title:_("Are you sure?"),
        text:_("Remove definition:")+name,
        type:"warning",
        showCancelButton:true,
        confirmButtonText:_('Proceed'),
        cancelButtonText:_('Cancel')
      },function(){
        $('#vm-'+uuid).find('i').removeClass('fa-play fa-square fa-pause').addClass('fa-refresh fa-spin');
        ajaxVMDispatch({action:"domain-undefine",uuid:uuid}, "loadlist");
      });
    }});
    if (template != 'OpenELEC') {
      opts.push({text:_("Remove VM")+" & "+_("Disks"), icon:"fa-trash", action:function(e) {
        e.preventDefault();
        swal({
          title:_("Are you sure?"),
          text:_("Completely REMOVE")+" "+name+" "+_("disk image and definition"),
          type:"warning",
          showCancelButton:true,
          confirmButtonText:_('Proceed'),
          cancelButtonText:_('Cancel')
        },function(){
          $('#vm-'+uuid).find('i').removeClass('fa-play fa-square fa-pause').addClass('fa-refresh fa-spin');
          ajaxVMDispatch({action:"domain-delete",uuid:uuid}, "loadlist");
        });
      }});
    }
  }
  context.attach('#vm-'+uuid, opts);
}
function startAll() {
  $('input[type=button]').prop('disabled',true);
  for (var i=0,vm; vm=kvm[i]; i++) if (vm.state!='running') $('#vm-'+vm.id).parent().find('i').removeClass('fa-square').addClass('fa-refresh fa-spin');
  $.post('/plugins/dynamix.vm.manager/include/VMManager.php',{action:'start'}, function(){loadlist();});
}
function stopAll() {
  $('input[type=button]').prop('disabled',true);
  for (var i=0,vm; vm=kvm[i]; i++) if (vm.state=='running') $('#vm-'+vm.id).parent().find('i').removeClass('fa-play').addClass('fa-refresh fa-spin');
  $.post('/plugins/dynamix.vm.manager/include/VMManager.php',{action:'stop'}, function(){loadlist();});
}
function vncOpen() {
  $.post('/plugins/dynamix.vm.manager/include/vnc.php',{cmd:'open',root:'<?=$docroot?>',file:'<?=$docroot?>/plugins/dynamix.vm.manager/vncconnect.vnc'},function(data) {
    window.location.href = data;
  });
}
function toggle_id(itemID){
   var cookie = $.cookie('vmshow')||'';
   if ((document.getElementById(itemID).style.display == 'none')) {
      slideDownRows($('#'+itemID));
      if (cookie.indexOf(itemID)<0) $.cookie('vmshow',cookie+itemID+','); 
   } else {
      slideUpRows($('#'+itemID));
      if (cookie.indexOf(itemID)>=0) $.cookie('vmshow',cookie.replace(itemID+',',''));
   }
   return false;
}
function showInput(){
  $(this).off('click');
  $(this).siblings('input').each(function(){$(this).show();});
  $(this).siblings('input').focus();
  $(this).hide();
}
function hideInput(){
  $(this).hide();
  $(this).siblings('span').show();
  $(this).siblings('span').click(showInput);
}
function addVM() {
  var path = location.pathname;
  var x = path.indexOf("?");
  if (x!=-1) path = path.substring(0,x);
  location = path+"/VMTemplates";
}


function getCloneName(uuid,name){
  var root = "" ;
  var match= ".iso" ;
  var box = $("#dialogWindow");
  box.html($("#templateClone").html());

  box.find('#VMBeingCloned').html(name).change() ;

    var height = 100;
  box.dialog({
    title: "Enter Clone Name",
    resizable: false,
    width: 600,
    height: 300,
    modal: true,
    show: {effect:'fade', duration:250},
    hide: {effect:'fade', duration:250},
    buttons: {
      "Clone": function(){
        var target = box.find('#target');
        if (target.length) {
          target = target.val();
          if (!target ) {errorTarget(); return;}
        } else target = '';
        box.find('#target').prop('disabled',true);
        ajaxVMDispatch({action:"domain-clone", uuid:uuid , clone:target}, "loadlist"); ;
        box.dialog('close');
      },
      "Cancel": function(){
        box.dialog('close');
      }
    }
  });
  dialogStyle();
}

function VMClone(uuid, name){

  //var root = <?= '"'.$domain_cfg["MEDIADIR"].'"';?>;
  var match= ".iso";
  var box = $("#dialogWindow");
  box.html($("#templateblock").html());
  var height = 200;
  box.html($("#templateClone").html());

  box.find('#VMBeingCloned').html(name).change() ;

 //document.getElementById("targetsnaprmv").checked = true ;
 //document.getElementById("targetsnaprmvmeta").checked = true ;
 //document.getElementById("targetsnapkeep").checked = true ;
 //document.getElementById("targetsnapfspc").checked = true ;

  box.dialog({
    title: "_(VM Clone)_",
    resizable: false,
    width: 600,
    height: 500,
    modal: true,
    show: {effect:'fade', duration:250},
    hide: {effect:'fade', duration:250},
    buttons: {
      "Clone" : function(){
        var target = box.find('#target');
        if (target.length) {
          target = target.val();
          if (!target ) {errorTarget(); return;}
        } else target = '';

        var clone = box.find("#target").prop('value') ;
       

        x = box.find('#Start').prop('checked') ;
        if (x) start = 'yes' ; else start = 'no' ;
        x = box.find('#Edit').prop('checked') ;
        if (x) edit = 'yes' ; else edit = 'no' ;
        x = box.find('#Overwrite').prop('checked') ;
        if (x) overwrite = 'yes' ; else overwrite = 'no' ;
        x = box.find('#Free').prop('checked') ;
        if (x) free = 'yes' ; else free = 'no' ;
        scripturl =  "VMClone.php " + encodeURIComponent("/usr/local/emhttp/plugins/dynamix.vm.manager/include/VMClone.php&" + $.param({action:"clone" , name:name ,clone:clone, overwrite:overwrite , edit:edit, start,start, free:free})) ;
        openVMAction((scripturl),"VM Clone", "dynamix.vm.manager", "loadlist") ;
        box.dialog('close');
      },
      "_(Cancel)_": function(){
        box.dialog('close');
      }
    }
  });
  dialogStyle();
}