/*
 * Работа с AJAX запросами
 * Stanislav V. Tretyakov (svtrostov@yandex.ru)
 */

/*----------------------------------------------------------------------
Класс работы с AJAX запросами
----------------------------------------------------------------------*/
var REQUEST_LAST_ACTION = null;
var REQUEST_LAST_DOCUMENT = null;
var REQUEST_LAST_DATA_GET = null;

var axRequest = new Class({

	Implements: [Options, Events],


	//==================================================================
	//Переменные класса

	request: null,		//Объект запроса
	autoDestroy: true,	//Автоматически удалять объект после обработки запроса
	stackController: null, //Контроллер стека

	//Опции
	options: {
		callback: null,		//Функция, для возврата результата запроса, пример: function(response, status, data){}
		method:'post',		//метод запроса, GET/POST
		url:'/',			//URL адрес, привер '/index.php'
		data:{},			//данные, направляемые серверу, пример data:{ key1:'value1',key2:'value2'}
		silent:true,		//Признак "бесшумного" режима, при удачном запросе, запрещает пояления окна "запрос выполнен успешно"
		waiter:true,		//Признак отображения окна ожидания в процессе получения данных
		spinner:false,		//Признак отображения спиннера
		display: 'window',	//Тип отображения сообщений, полученных от сервера: none, window, hint
		uploaderForm: null	//Элемент типа Форма, для загрузки файлов на сервер
	},

	//Обрабатываемые ключи ответа
	//ВАЖНО!: 
	//не меняйте порядок элементов в массиве,
	//поскольку это может привести к непредсказуемым результатам
	//обработка должна идти именно в заданном порядке
	responseKeys: [
		'debug',
		'status',
		'action',
		'document',
		'get',
		'location',
		'title',
		'content',
		'data',
		'required',
		'callback',
		'request',
		'messages',
		'stack'
	],

	uploadIFrameLoading: false,


	//==================================================================
	//Функции


	//Инициализация
	initialize: function(options){

		this.setOptions(options);
		this.stackController = (typeof App.stackController == 'function' ? App.stackController : (typeof window['stackController'] == 'function' ? (App.stackController = window['stackController']) : null));

	},//end function



	//Уничтожение
	terminate: function(){
		if(this.request){
			if(this.request.xhr) this.request.xhr = null;
		}
		for(var key in this) this[key] = null;
	},//end function



	//Начало запроса, отправка данных
	onSendRequest: function(){

		if(this.options.waiter){
			if($('spinner')) $('spinner').show();
		}

		return true;
	},//end function



	//Запрос
	request: function(data){
		if(!data) data = this.options.data;
		if(typeof data != 'string') data = Object.toQueryString(data).trim();
		data += (data.length > 0 ? '&' : '') + 'RuId='+String.uniqueID();

		if(!this.onSendRequest()) return false;

		App.echo(data);

		App.Location.lastRequestedUrl = this.options.url;

		this.request = new Request({
			'url'		: this.options.url,
			'method'	: this.options.method,
			'data'		: data,
			'timeout'	: 30000,
			'onSuccess'	: this.success.bind(this),
			'onFailure'	: this.failure.bind(this),
			'onTimeout'	: this.failure.bind(this)
		}).send();
		
		return true;
	},//end function



	//Запрос успешно выполнен, от сервера получен ответ
	success: function(responseText){

		var response = null;
		var customData = null;
		var status = 'error';
		var key, dataType;

		try{
			response = JSON.decode(responseText, true);
		}catch(e){
			App.echo('==DECODE SERVER ANSVER ERROR: CONTENT===============================');
			App.echo(responseText);
			App.echo('====================================================================');
			App.echo(e);
			return this.onCompleteRequest(false, status, null);
		}

		App.echo(response);

		if(!response || typeOf(response) != 'object'){
			return this.onCompleteRequest(false, status, null);
		}

		if(response['status']) status = response['status'];
		var success = (status!='success' ? false : true);

		for(var i=0; i<this.responseKeys.length; i++){
			key = this.responseKeys[i];
			if(!response[key]) continue;
			dataType = typeOf(response[key]);

			switch(key){

				//Статус обработки запроса
				case 'debug':
					if(response[key]){
						if(App.debug) App.debugMessage(response[key]);
					}
				break;

				//Статус обработки запроса
				case 'status':
					
				break;

				//Запрошенное действие
				case 'action': REQUEST_LAST_ACTION = response[key]; break;

				//Запрошенный документ
				case 'document': REQUEST_LAST_DOCUMENT = response[key]; break;

				//GET параметры
				case 'get': REQUEST_LAST_DATA_GET = response[key]; break;

				//Перенаправление
				case 'location':
					App.Location.setLocation(response[key]);
					return;
				break;

				//Произвольные данные
				case 'data':
					customData = response[key];
				break;

				//Подключение дополнительных файлов (JS, CSS)
				case 'required':
					if(!success) break;
					if(dataType == 'array'){
						response[key].each(function(entry){
							if(typeOf(entry) == 'string'){
								App.Loader.loadFile(null, entry);
								return;
							}else
							if(typeOf(entry) == 'object'){
								if((entry['browser'] && !Browser.inCondition(entry['browser'])) || (entry['os'] && !Browser.Platform.inCondition(entry['os']))) return;
								if(entry['file']) App.Loader.loadFile(null, entry['file'], entry['callback'], [success, status, customData]);
							}else
							if(typeOf(entry) == 'array'){
								var collection = [];
								entry.each(function(item){
									if((item['browser'] && !Browser.inCondition(item['browser'])) || (item['os'] && !Browser.Platform.inCondition(item['os']))) return;
									if(item['file']) collection.push([null, item['file'], item['callback']]);
								});
								App.Loader.loadCollection(collection, [success, status, customData]);
							}
						});
					}
				break;

				//Вызов callback функций
				//Примечание: функции из только что подключенных файлов required
				//здесь отрабатывать не будут, их следует задавать в required[callback]
				case 'callback':
					if(!success) break;
					App.functionCall(response[key],[success, status, customData]);
				break;

				//Создание AJAX запроса
				case 'request':
					if(!success) break;
					//
				break;

				//Сообщения от сервера
				case 'messages':
					if(dataType == 'array'){
						for(var v=0;v<response[key].length;v++){
							if(response[key][v]['type'] == 'error' || response[key][v]['type'] == 'warning'){
								App.message(
									response[key][v]['title'],
									response[key][v]['text'],
									response[key][v]['type']
								);
							}else
							if(!this.options.silent){
								if(this.options.display=='hint' || response[key][v]['display']=='hint'){
									App.tip(
										response[key][v]['title'],
										response[key][v]['text'],
										response[key][v]['type']
									);
								}else
								if(this.options.display=='window'){
									App.message(
										response[key][v]['title'],
										response[key][v]['text'],
										response[key][v]['type']
									);
								}
							}
						}
					}
				break;

				//HTML контент
				case 'content':
					if(!success) break;
					if(dataType=='string'){
						App.setContent({
							'parent':'body',
							'content':response[key],
							'type':'set'
						});
						break;
					}else
					if(dataType=='array'){
						var updates=0;
						do{
							updates = 0;
							for(var v=0;v<response[key].length;v++){
								if(typeOf(response[key][v])=='array'){
									if(App.setContent({
										'parent':response[key][v][0],
										'content':response[key][v][1],
										'type':response[key][v][2]
									})==true){
										updates++;
										response[key][v]=null;
									}
								}
							}
						}while(updates>0);
					}
				break;

				//Заголовок страницы
				case 'title':
					if(!success) break;
					if(dataType=='string') document.title = response[key];
				break;
			
			}

		}

		return this.onCompleteRequest(success, status, customData, response['stack']);
	},//end function




	//Запрос не выполнен, ошибка
	failure: function(){
		return this.onCompleteRequest(false, 'error', null, null);
	},//end function




	//Окончание запроса, результат отправки
	onCompleteRequest: function(success, status, data, stack){

		//Вызов пользовательской функции 
		if(this.options.callback) this.options.callback(success, status, data);

		if(this.stackController) this.stackController((!stack ? {} : stack));
		
		if(this.options.waiter){
			if($('spinner')) $('spinner').hide();
		}

		setTimeout(function(axR){
			try{
				axR.terminate();
				delete axR;
			}catch(e){}
		}.bind(null,this),2000);

		return true;
	},//end function



	//==================================================================
	//Загрузка файлов на сервер


	//Загрузка файлов на сервер
	upload: function(uploaderForm){

		if(this.uploadIFrameLoading) return;
		this.uploadForm = $(uploaderForm || this.options.uploaderForm);
		if(typeOf(this.uploadForm)!='element' || this.uploadForm.get('tag')!='form') return false;
		this.uploadFormTarget = this.uploadForm.get('target');

		var iFrameID = String.uniqueID();

		this.uploadIFrameEvent = function(){
			this.uploadIFrameLoading = true;
			this.onSendRequest();
			App.echo('request');
			return true;
		}.bind(this);

		this.uploadIFrame = new IFrame({
			'name': iFrameID,
			'styles': {
				display: 'none'
			},
			'src': 'about:blank',
			'events': {
				'load': function(){
					if (this.uploadIFrameLoading){
						if(typeOf(this.uploadIFrame)=='element'){
							var doc = this.uploadIFrame.contentWindow.document;
							if (doc && doc.location.href != 'about:blank'){
								this.success(doc.body.innerText);
							} else {
								this.failure();
							}
							this.uploadIFrame.destroy();
							this.uploadForm.set('target', this.uploaderFormTarget);
							this.uploadIFrame = null;
							this.uploadIFrameEvent = null;
							this.uploaderFormTarget = null;
						}
						this.uploadIFrameLoading = false;
					}
				}.bind(this)
			}
		}).inject(document.body);

		this.uploadForm.set('target', iFrameID);
		this.uploadIFrameLoading = true;
		this.onSendRequest();
		this.uploadForm.submit();

	}//end function



});//end class
