var Autofill=new Class({Implements:[Events,Options],options:{observe:"",trigger:"",cnn:0,table:0,map:"",editOrig:false,fillOnLoad:false,confirm:true},initialize:function(a){this.setOptions(a);Fabrik.addEvent("fabrik.form.elements.added",function(b){this.setUp(b)}.bind(this))},setUp:function(g){try{this.form=g}catch(f){return}var h=this.lookUp.bind(this);this.element=this.form.formElements.get(this.options.observe);if(!this.element){var e=new RegExp(this.options.observe+"$");var a=Object.keys(this.form.formElements);var c=a.each(function(j){if(j.test(e)){this.element=this.form.formElements.get(j)}}.bind(this))}if(this.options.trigger===""){if(!this.element){fconsole("autofill - couldnt find element to observe")}else{var d=this.element.element.get("tag")==="select"?"change":"blur";this.form.dispatchEvent("",this.element.strElement,d,h)}}else{this.form.dispatchEvent("",this.options.trigger,"click",h)}if(this.options.fillOnLoad&&g.options.rowid==="0"){var b=this.options.trigger===""?this.element.strElement:this.options.trigger;this.form.dispatchEvent("",b,"load",h)}},lookUp:function(){if(this.options.confirm===true){if(!confirm(Joomla.JText._("PLG_FORM_AUTOFILL_DO_UPDATE"))){return}}Fabrik.loader.start("form_"+this.options.formid,Joomla.JText._("PLG_FORM_AUTOFILL_SEARCHING"));var a=this.element.getValue();var c=this.options.formid;var d=this.options.observe;var b=new Request({url:"",method:"post",data:{option:"com_fabrik",format:"raw",task:"plugin.pluginAjax",plugin:"autofill",method:"ajax_getAutoFill",g:"form",v:a,formid:c,observe:d,cnn:this.options.cnn,table:this.options.table,map:this.options.map},onComplete:function(e){Fabrik.loader.stop("form_"+this.options.formid);this.updateForm(e)}.bind(this)}).send()},updateForm:function(a){a=$H(JSON.decode(a));if(a.length===0){alert(Joomla.JText._("PLG_FORM_AUTOFILL_NORECORDS_FOUND"))}a.each(function(e,b){var d=b.substr(b.length-4,4);if(d==="_raw"){b=b.replace("_raw","");var c=this.form.formElements.get(b);if(typeOf(c)!=="null"){c.update(e)}}}.bind(this));if(this.options.editOrig===true){this.form.getForm().getElement("input[name=rowid]").value=a.__pk_val}}});