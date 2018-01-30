/*
 * Центральный модуль web-приложения
 * Stanislav V. Tretyakov (svtrostov@yandex.ru)
 */

/*----------------------------------------------------------------------------------------------------------------
Глобальные переменные: Дата
----------------------------------------------------------------------------------------------------------------*/
var _NOW_TIME		= new Date();
var _NOW_YEAR		= _NOW_TIME.getFullYear();
var _NOW_MONTH		= _NOW_TIME.getMonth() + 1;
var _NOW_DAY		= _NOW_TIME.getDate();
var _NOW_DAY_T		= (_NOW_DAY < 10) ? '0'+ _NOW_DAY : _NOW_DAY;
var _NOW_MONTH_T	= (_NOW_MONTH < 10) ? '0'+ _NOW_MONTH : _NOW_MONTH;
var _MONTH_BEGIN	= '01.'+_NOW_MONTH_T+'.'+_NOW_YEAR;
var _TODAY			= _NOW_DAY_T+'.'+_NOW_MONTH_T+'.'+_NOW_YEAR;


/*----------------------------------------------------------------------
Ядро WEB-приложения
----------------------------------------------------------------------*/
var App;

/*domready*/
document.addEvent('domready', function() {

	Locale.use('en-US');

	if(typeof APP_AUTOCREATE_DISABLE != 'boolean'){
		App = new jsApp();
		App.start();
		document.fireEvent("appbegin");
	}
	
});


var jsApp = new Class({

	Implements: [Events],

	//==================================================================
	//Основные переменные

	isInit: false,				//Признак инициализации приложения
	preloaderComplete: false,	//Признак успешного выполнения предварительной загрузки
	debug : true,				//Режим отладки приложения
	isFirstPageLoaded: false,	//Признак загрузки первой страницы
	pages: {},					//Объекты страниц
	lang:{},					//Тексты языковой локализации
	user:{						//Сведения о пользователе
		'user_id': 0,
		'is_super':0
	},


	//==================================================================
	//Объекты
	stackController: null,	//Стек сообщений сервера
	mainMenu: null,			//Основное меню
	cookie: null,			//Работа с Cookie
	preloader: null,		//Предзагрузчик
	currentRequest: null,	//Текущий AJAX запрос


	//==================================================================
	//Инициализация приложения

	initialize: function(){
		
		this.Loader = new jsApp.Loader();
		this.localStorage = new jsApp.localStorage();
		this.Location = new jsApp.Location();
		return this;
	},


	/*Старт приложения*/
	start: function(content){

		if(!this.isInit){
			this.isInit = true;
			this.preloader = new Preloader(content);
			this.preloader.addEvent('complete',this.start.bind(this));
			this.preloader.load();
			return;
		}

		if(typeOf(REQUEST_INFO['get'])!='object') REQUEST_INFO['get']={'init':1};
		else REQUEST_INFO['get']['init']=1;
		/*
		this.Location.doPage({
			'href': REQUEST_INFO['path']+(REQUEST_INFO['query'].length>0?'?'+REQUEST_INFO['query']:''),
			'url' :	REQUEST_INFO['path'],
			'data': REQUEST_INFO['get'],
			'method':'get',
			'from': 'start'
		}, false);
		*/
	},

	//==================================================================
	//Функции работы с пользователем

	/*Получение ID пользователя*/
	getUserID: function(){
		if(typeOf(this.user)!='object') return 0;
		return parseInt(this.user['user_id']);
	},


	isSuperUser: function(){
		if(typeOf(this.user)!='object') return false;
		return (parseInt(this.user['is_super']) != 0);
	},




	//==================================================================
	//Языковые функции

	getLang: function(path, def){
		def = def || '?['+path+']?';
		var a = path.trim().split('/');
		var l = App.lang;
		for(var i=0; i< a.length; i++){
			if(a[i] == '' && i == 0) continue;
			if(a[i] == '' && i == a.length-1) break;
			if(l[a[i]] == undefined) return def;
			l = l[a[i]];
		}
		if(l == undefined) return def;
		return l;
	},




	//==================================================================
	//Функции отладки

	//Функции отладки - вывод сообщения
	echo: function(str, output){
		if(this.debug){
			var outputType = (typeof output == 'undefined') ? 'console' : output;
			this[outputType](str);
		}
		return true;
	},



	//Функции отладки - вывод сообщения
	alert: function(str){
		alert(str);
	},



	//Функции отладки - вывод сообщения
	console: function(str){
		if(window.console) console.log(str);
	},


	//Сообщение Server Debug
	debugMessage: function(message){
		new jsMessage({
			'width'		: '800px',
			'isUrgent'	: true,
			'autoDismiss': false,
			'centered'	: true,
			'title'		: 'Server Debug',
			'message'	: '<pre>'+message+'</pre>',
			'type'		: 'warning',
			'isModal'	: true,
			'yesLink'	: App.getLang('general/yes','Yes'),
			'noLink'	: App.getLang('general/cancel','Cancel')
		}).say();
	},

	//Дамп переменной
	dump: function(arr, level, replacements){
		var dumped_text = "", item_t;
		if(!level) level = 0;
		replacements = replacements || {};
		var level_padding = "";
		for(var j=0;j<level;j++) level_padding += "&nbsp; &nbsp; &nbsp; &nbsp; &nbsp; ";
		if(typeof(arr) == 'object'){ 
			for(var item in arr){
				item_t = (replacements[item] ? replacements[item] : item);
				var value = arr[item];
				if(typeof(value) == 'object') { //If it is an array,
					dumped_text += level_padding + "<b>" + item_t + "</b> =><br>";
					dumped_text += this.dump(value,level+1);
				} else {
					dumped_text += level_padding + "" + item_t + ": <b>" + value + "</b><br>";
				}
			}
		} else { //Stings/Chars/Numbers etc.
			dumped_text = "===>"+arr+"<===("+typeof(arr)+")";
		}
		return dumped_text;
	},


	//==================================================================
	//Работа с функциями

	//Проверка существования функции
	functionExists: function(name){
		if (typeof name == 'string'){
			return (typeof window[name] == 'function' ? 'window' : false);
		} else{
			return (name instanceof Function ? 'function' : false);
		}
		return false;
	},



	//Выполнение произвольной функции с передачей заданных параметров
	functionCall: function(name, args, object){
		if(!object)object = this;
		var funct = null, result = false;
		switch(this.functionExists(name)){
			case 'window': return window[name].apply(object, args);
			case 'function': return name.apply(object, args);
		}
		return false;
	},


	//==================================================================
	//Работа с контентом
	
	//Вставка контента в элемент
	setContent: function(data){
		var parent = data['parent'] || null;
		var content = data['content'] || '';
		var type = data['type'] || 'set';
		var container;
		var complete = false;
		
		var elements = $$(parent);
		for(var i=0; i<elements.length;i++){
			if(typeOf(elements[i]) == 'element'){
				switch(type){
					case 'set': $(elements[i]).set('html',content); complete = true; break;
					case 'add': 
						container = new Element('div').set('html',content).inject(elements[i]);
						complete = true;
					break;
						
				}
			}
		}

		return complete;
	},


	//==================================================================
	//Сообщения

	/*Генерация сообщения в окне Window*/
	message: function(title, message, message_type, callback){
		if(!message_type) message_type = 'INFO';
		var is_urgent = false, width = '400px';
		switch(message_type.toUpperCase()){
			case 'SUCCESS': message_type = 'success'; break;
			case 'WARNING': message_type = 'warning'; is_urgent=true; break;
			case 'ERROR': message_type = 'error'; is_urgent=true; break;
			case 'INFO': message_type = 'info'; width = '600px'; break;
			case 'DUMP': message_type = 'info'; width = '800px'; break;
			case 'CONFIRM': message_type = 'confirm'; break;
			default: message_type = 'info'; break;
		}
		new jsMessage({
			'width'		: width,
			'isUrgent'	: is_urgent,
			'autoDismiss': false,
			'centered'	: true,
			'title'		: title,
			'message'	: message,
			'type'		: message_type,
			'isModal'	: true,
			'callback'	: callback,
			'yesLink'	: App.getLang('general/yes','Yes'),
			'noLink'	: App.getLang('general/cancel','Cancel')
		}).say();
	},


	/*Генерация окна для комментария*/
	comment: function(title, message, callback, yesLink){
		new jsMessage({
			'width'		: '600px',
			'height'	: '200px',
			'isUrgent'	: true,
			'autoDismiss': false,
			'centered'	: true,
			'title'		: title,
			'message'	: message,
			'type'		: 'comment',
			'isModal'	: true,
			'yesLink'	: (yesLink || App.getLang('general/send','Send')),
			'noLink'	: App.getLang('general/cancel','Cancel'),
			'callback'	: callback
		}).comment();
	},



	/*Генерация всплывающего сообщения Tip*/
	tip: function(title, message, message_type){
		if(!message_type) message_type = 'INFO';
		var is_urgent = false;
		switch(message_type.toUpperCase()){
			case 'SUCCESS': message_type = 'success'; break;
			case 'WARNING': message_type = 'warning'; break;
			case 'ERROR': message_type = 'error'; break;
			default: message_type = 'info'; break;
		}
		new jsMessage({
			'width'		: '400px',
			'title'		: title,
			'message'	: message,
			'isModal'	: false,
			'type'		: message_type,
			'offset'	: 10
		}).tip();
	}

});
















/*
 * Загрузчик Javascript и CSS во время исполнения приложения
 */
jsApp.Loader = new Class({

	Implements: [Events],

	//==================================================================
	//Переменные класса

	//Получаем кеш от загрузчика или создаем свой, если загрузчик отсутствует
	loaded: {
		js: [],
		css: []
	},

	//Кеш
	cache: {
		js: {},
		css: {}
	},

	collectionData: null,

	//download files iframe
	downloadIFrame: null,


	//==================================================================
	//Функции

	//Инициализация кеша загруженных файлов
	initialize: function(){
		$$('script').each(function(script){
			if (loaderSrc = script.getAttribute('src')) this.loaded.js.push(loaderSrc);
		}.bind(this));
		$$('link').each(function(css){
			if (loaderSrc = css.getAttribute('href')) this.loaded.css.push(loaderSrc);
		}.bind(this));
	},



	//Получение типа файла js||css
	getFileType: function(source){
		var qoIndex;
		if (~(qoIndex = source.indexOf('?')) || ~(qoIndex = source.indexOf('#'))) source = source.substr(0, qoIndex);
		return source.toLowerCase().lastIndexOf('.css') == source.length - 4 ? 'css' : 'js';
	},


	//Получение уже загруженного элемента
	getLoadedElement: function(type, source){
		var elements = $$(type == 'js' ? 'script' : 'link');
		for(var i = 0; i < elements.length; i++){
			if(elements[i].get(type == 'js' ? 'src' : 'href') == source){
				return this.cache[type][source] = elements[i];
			}
		}
	},


	//Загрузка произвольного файла через IFrame
	downloadFile: function(source){
		if(!this.downloadIFrame){
			this.downloadIFrame = new Element('iframe',{
				'styles':{
					'display':'none'
				}
			}).inject(document.body);
		}
		this.downloadIFrame.src = source;
	},


	//Загрузка файла
	loadFile: function(type, source, callback, args){
		
		if(!args) args = [];
		if(!type) type = this.getFileType(source);
		var isLoaded = this.loaded[type].contains(source);
		var defaultProperties = (type == 'js'
				? {
					src: source,
					type: 'text/javascript'
				}
				: {
					rel: 'stylesheet',
					media: 'screen',
					type: 'text/css',
					href: source
				}
			);
		var element = isLoaded ? this.cache[type][source] || this.getLoadedElement(type, source) : new Element(type == 'js' ? 'script' : 'link', defaultProperties);

		if (!isLoaded) {
			this.loaded[type].push(source);
			this.cache[type][source] = element;
		}

		if(type == 'js' && callback){
			if(isLoaded){
				App.functionCall(callback, args);
				return element;
			}
			
			if(element.onreadystatechange !== undefined){
				element.addEvent('readystatechange', function(){
					if (['loaded', 'complete'].contains(this.readyState))
						App.functionCall(callback, args);
				});
			}else{
				element.addEvent('load', function(){App.functionCall(callback, args);});
			}
		}
		

		return isLoaded ? element : element.inject(document.head);
	},



	//Загрузка нескольких файлов последовательно (Цепочки файлов)
	loadCollection: function(files, args){

		if(typeOf(this.collectionData)=='object'){
			App.echo('WARNING:DOUBLE-COLLECTION!WARNING:DOUBLE-COLLECTION!WARNING:DOUBLE-COLLECTION!');
			App.echo(this.collectionData);
			App.echo('WARNING:DOUBLE-COLLECTION!WARNING:DOUBLE-COLLECTION!WARNING:DOUBLE-COLLECTION!');
		}

		if(!args) args = [];
		this.collectionData = {
			'files': $unlink(files),
			'args': args,
			'index':0
		};
		this.loadCollectionItem(0,false);
		return true;
	},


	//Загрузка следующего в цепочке файла
	loadCollectionItem: function(index, loaded){
		if(typeOf(this.collectionData)!='object'|| typeOf(this.collectionData['files'])!='array'|| !this.collectionData['files'].length || index >= this.collectionData['files'].length){
			this.collectionData = null;
			return;
		}
		if(loaded){
			if(this.collectionData['files'][index][2]) App.functionCall(this.collectionData['files'][index][2], this.collectionData['args']);
			index++;
		}
		if(index >= this.collectionData['files'].length){
			this.collectionData = null;
			return;
		}
		this.loadFile(this.collectionData['files'][index][0],this.collectionData['files'][index][1],function(i,l){App.Loader.loadCollectionItem(i,l);},[index, true]);
	}

});






















/*
 * Работа с Cookie
 */
jsApp.localStorage = new Class({

	Implements: [Events],

	//==================================================================
	//Переменные класса


	//Префикс для Cookie
	prefix:'',
	useLocalStorage: false,


	//==================================================================
	//Функции


	//Инициализация
	initialize: function(){
		if(typeof COOKIE_PREFIX =='string') this.prefix = COOKIE_PREFIX+'_';
		this.useLocalStorage = this.isLocalStorageAvailable();
	},

	//Запись
	write: function(name, value, noCookie){
		if(this.useLocalStorage) window.localStorage.setItem(this.prefix + name, value);
		else{
			if(!noCookie) Cookie.write(this.prefix + name, value);
		}
		return true;
	},


	isLocalStorageAvailable: function(){
		try {
			return 'localStorage' in window && window['localStorage'] !== null;
		} catch (e) {
			return false;
		}
	},


	//Чтение
	read: function(name, defValue, noCookie){
		var value = (this.useLocalStorage ?  window.localStorage.getItem(this.prefix + name) : (!noCookie ? Cookie.read(this.prefix + name) : defValue));
		return value;
	},


	//Удаление
	dispose: function(name, noCookie){
		if(this.useLocalStorage) window.localStorage.removeItem(name);
		if(!noCookie) Cookie.dispose(this.prefix + name);
		return true;
	}

});





/*
 * Работа с перенаправлениями
 */
jsApp.Location = new Class({

	Implements: [Events],

	//==================================================================
	//Переменные класса
	lastRequestedPage: '',	//Последняя запрошенная страница (только страницы)
	lastRequestedUrl: '',	//Последний запрошенный URL (страницы + ajax операции)
	lastRequestedHistoryHRef: '',	//Последний запрошенный URL в истории браузера
	hasReplaceState: false,
	hasPushState: false,
	beforeExitFunction: null,

	//==================================================================
	//Функции

	//Инициализация кеша загруженных файлов
	initialize: function(){
		this.hasReplaceState = ('replaceState' in history);
		this.hasPushState = ('pushState' in history);
		if(this.hasPushState){
			window.addEventListener('popstate',function(e){
				if(!App.isFirstPageLoaded) return;
				if(typeOf(history.state)=='object'){
					this.lastRequestedHistoryHRef = history.state.data['href'];
					App.Location.doPage(history.state.data, true);
				}
				
			});
		}
		//this.setAnchors();
	},


	//==================================================================
	//Проверки

	//Установить перед переходом на другую страницу вызов пользовательской функции
	setBeforeExitFunction: function(funct){
		this.beforeExitFunction = funct;
	},



	//==================================================================
	//Перенаправления и переходы по ссылкам

	//Перенаправление
	setLocation: function(location){
		switch (location) {
			case 'reload':
				document.location.reload();
			break;
			case 'refresh':
				document.location = document.location.href;
			break;
			default:
				document.location.href = location;
		}
		return true;
	},


	//Обработка нажатия на ссылку
	setAnchors: function() {
		document.addEvent('click', function(event){
			//App.echo(event);
			//if (event.event.which && event.event.which != 1) return true;
			if (!event || (event && typeOf(event.target) != 'element')) return true;
			var target = $(event.target);
			var tag = target.get('tag');
			var anchor = null;
			//App.echo(target);
			switch(tag){
				case 'a': anchor = target; break;
				case 'div':
				case 'span':
					anchor = target.getParent('a');
				break;
				case 'input':
				case 'select':
				case 'textarea':
					target.focus();
				break;
				case 'option':
					target.selected = true;
					var select = target.getParent('select');
					if(select){
						select.selectedIndex = target.index;
						select.focus();
					}
				break;
			}
			if(typeOf(anchor)!='element') return true;
			var href = $(anchor).getProperty('href') || '#';
			if($(anchor).getProperty('target') == '_blank' || $(anchor).hasClass('no-push')) return true;
			if(href.test(/^((javascript|https?|ftps?|mailto|file):.*?)/,'i')===false){
				if(href && href!='#'){

					var data = {
						'href': anchor.href,
						'url': App.Location.getUrl(href),
						'data': App.Location.getQueryObject(anchor.href),
						'method':'get',
						'from':'click_event'
					};

					if(App.Location.beforeExitFunction){
						App.Location.beforeExitFunction(data);
					}else{
						App.Location.doPage(data);
					}
				}
				return Function.stopEvent(event);
			}
			return true;
		})
	},

	//Возвращает URL без строки параметров, указанных после ? или #
	getUrl: function(url){
		if(!url) return '';
		var ndx1=parseInt(url.indexOf("?"));
		var ndx2=parseInt(url.indexOf("#"));
		if(ndx1==-1 && ndx2==-1) return url;
		if(ndx1==-1) return url.substring(0, ndx2);
		if(ndx2==-1) return url.substring(0, ndx1);
		return url.substring(0, (ndx1 > ndx2 ? ndx2 : ndx1));
	},


	//Возвращает строку параметров GET из URL
	getQueryString: function(url){
		if (!url) return '';
		var ndx=url.indexOf("?");
		if (ndx==-1) return '';
		var ndx2=url.indexOf("#", ndx+1);
		return url.substring(ndx+1, (ndx2==-1 ? url.length : ndx2));
	},


	//Возвращает строку параметров GET из URL
	getQueryObject: function(url, isQueryString){
		if(!isQueryString) url = this.getQueryString(url);
		if (!url) return {};
		var result = {};
		var pairs = url.split('&');
		var kv;
		for(var i=0; i<pairs.length; i++){
			kv = String(pairs[i]).split('=');
			if(kv.length == 2){
				result[kv[0]] = kv[1];
			}
		}
		return result;
	},


	//Получение контента страницы по AJAX
	doPage: function(ohref, noHistoryUpdate){

		if(!ohref) return;
		var href;

		if(typeOf(ohref)=='string'){
			href = {
				'href': ohref,
				'url': this.getUrl(ohref),
				'data': this.getQueryObject(ohref),
				'method':'get',
				'from': 'doPage_string'
			}
		}else{
			if(typeOf(ohref)=='object'){
				href = Object.merge({}, {}, ohref);
			}else{
				return;
			}
		}
		this.fireEvent('beforeLoadPage', href['url']);

		if(!noHistoryUpdate) this.updateHistory(href['href'], href);
		this.lastRequestedPage = href['url'];

		new axRequest({
			'url' :	href['url'],
			'data': href['data'],
			'method':href['method'],
			'silent': true,
			'waiter': true,
			'callback': function(){
				App.isFirstPageLoaded = true;
				App.Location.fireEvent('afterLoadPage', this['url']);
			}.bind(href)
		}).request();

	},

	/*History API, обновление URL в строке браузера*/
	updateHistory: function(url, data){
		if(this.hasPushState){
			history.pushState({'data':data}, null, url);
		}
	}



});




/*Предзагрузчик*/
var Preloader = new Class({
	
	Implements: [Options, Events],
	
	options: {
		images: {},
		sounds: {},
		videos: {},
		json: {},
		jsonp: {},
		scripts: {},
		stylesheets: {}
	},
	
	initialize: function(options) {
		this.setOptions(options);
	},

	load: function() {
		var assetCount = 0,
			completedCount = 0,
			startTime = new Date().getTime(),
			loaded = {
				images: {},
				sounds: {},
				videos: {},
				json: {},
				jsonp: {},
				scripts: {},
				stylesheets: {}
			},
			handleProgress = function() {
				completedCount += 1;
				this.fireEvent('progress', [completedCount, assetCount, 100 / assetCount * completedCount]);
				
				if(completedCount === assetCount) {
					this.fireEvent('complete', [loaded, assetCount, new Date().getTime() - startTime]);
				}
			}.bind(this);
		
		this.fireEvent('start', [assetCount]);
		
		//Расчет количества всех объектов в прелоадере
		Object.each(this.options, function(assetObject) {
			assetCount += Object.getLength(assetObject);
		});
		
		if(assetCount == 0){
			this.fireEvent('complete', [0, 0, new Date().getTime() - startTime]);
			return;
		}
		
		
		// Load images
		Object.each(this.options.images, function(path, name) {
			loaded.images[name] = Asset.image(path, {
				onLoad: handleProgress
			});
		});
		
		// Load sounds
		Object.each(this.options.sounds, function(path, name) {
			loaded.sounds[name] = new Element('audio');
			loaded.sounds[name].addEventListener('canplaythrough', handleProgress, false);
			loaded.sounds[name].set('src', path);
			loaded.sounds[name].load();
		});
		
		// Load videos
		Object.each(this.options.videos, function(path, name) {
			loaded.videos[name] = new Element('video');
			loaded.videos[name].addEventListener('canplaythrough', handleProgress, false);
			loaded.videos[name].set('src', path);
			loaded.videos[name].load();
		});
		
		// Load JSON
		Object.each(this.options.json, function(settings, name) {
			var request = new Request.JSON((typeof settings === 'object') ? settings : {url:settings});
			
			request.addEvent('success', function(loadedJson) {
					loaded.json[name] = loadedJson;
					handleProgress();
			});
			
			request.send();
		});
		
		// Load JSONP
		Object.each(this.options.jsonp, function(settings, name) {
			var request = new Request.JSONP((typeof settings === 'object') ? settings : {url:settings});
			
			request.addEvent('success', function(loadedJson) {
					loaded.jsonp[name] = loadedJson;
					handleProgress();
			});
			
			request.send();
		});
		
		// Load scripts
		Object.each(this.options.scripts, function(path, name) {
			loaded.scripts[name] = Asset.javascript(path, {
				onLoad: handleProgress
			});
		});
		
		// Load stylesheets
		Object.each(this.options.stylesheets, function(path, name) {
			loaded.stylesheets[name] = Asset.css(path);
			handleProgress();
		});
	}
});


function AddToCart(product_id){
	new axRequest({
		url : '/main/ajax/cart',
		data:{
			'action'		: 'add.to.cart',
			'product_id'	: product_id
		},
		silent: false,
		waiter: true,
		callback: function(success, status, data){
			if(success){
				$('cart_count').set('text',data['cart_count']);
				$('cart_sum').set('text',data['cart_sum']);
			}
		}
	}).request();
}//end function

