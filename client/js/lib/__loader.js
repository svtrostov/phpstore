/*
JS tools
Stanislav V. Tretyakov (svtrostov@yandex.ru)
*/
var _loader = {
	require: function(lib) {
		document.write('<script type="text/javascript" src="'+lib+'"></script>');
	},
	load: function() {
		var path = new String();
		var incl = new String();
		var i;
		var s = document.getElementsByTagName("script");
		for(i=0; i<s.length; ++i){
			if(s[i].src && s[i].src.match(/__loader\.js(\?.*)?$/)){
				path = s[i].src.replace(/__loader\.js(\?.*)?$/,'');
				incl = s[i].src.match(/\?.*do=([A-Za-z0-9_,]*)/)
				if(incl)incl=incl[1];
				break;
			}
		}
		this.require(path+'__core.js');
		this.require(path+'__more.js');
		this.require(path+'__utils.js');
		this.require(path+'__app.js');
		this.require(path+'axRequest.class.js');

		if(incl){
			var use = incl.split(',');
			for(var i=0; i<use.length; ++i){
				this.require(path+use[i]+'.js');
			}
		}
	}
}
_loader.load();