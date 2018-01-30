;(function(){
var PAGE_NAME = 'admin_user_protocol';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_protocol'],
		'table_protocol': null,
		'users': []
	},


	/*******************************************************************
	 * Инициализация
	 ******************************************************************/

	//Вход на страницу
	enter: function(success, status, data){
		App.Location.addEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		this.objects = $unlink(this.defaults);
		this.start(data);
	},//end function



	//Выход со страницы
	exit: function(){
		App.Location.removeEvent('beforeLoadPage', App.pages[PAGE_NAME].exit);
		var self = App.pages[PAGE_NAME];
		self.fullscreen(true);
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		$('bigblock_expander').addEvent('click',this.fullscreen.bind(this));
		$('filter_button').addEvent('click',this.filter.bind(this));
		$('filter_user_id').addEvent('change',this.filter.bind(this));

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		this.setData(data);
		$('filter_date_from').setValue(_TODAY);
		$('filter_date_to').setValue(_TODAY);
		var storage_data = App.localStorage.read('log_filter', null, true);
		if(storage_data){
			var filter = String(storage_data).fromQueryString();
			if(typeOf(filter)=='object'){
				var value;
				for(var key in filter){
					value = filter[key];
					switch(key){
						/*case 'date_from': $('filter_date_from').setValue(value); break;
						case 'date_to': $('filter_date_to').setValue(value); break;*/
						case 'user_id': $('filter_user_id').setValue(value); break;
						case 'limit': $('filter_limit').setValue(value); break;
					}
				}
			}
			this.filter();
		}
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Список менеджеров
		if(typeOf(data['users'])=='array'){
			this.objects['users'] = data['users'];
			select_add({
				'list': 'filter_user_id',
				'key': 'user_id',
				'value': 'name',
				'options': data['users'],
				'default': '0',
				'clear': false
			});
		}


		//Информация о сессии
		if(typeOf(data['session_info'])=='object'){
			var out = 
			'UID сессии: '+data['session_info']['session_uid']+'<br>'+
			'ID пользователя: '+data['session_info']['user_id']+'<br>'+
			'IP адрес: '+data['session_info']['ip_addr']+'<br>'+
			'IP реальный: '+data['session_info']['ip_real']+'<br>'+
			'Время входа: '+data['session_info']['login_time']+'<br>';
			App.message('Информация о сессии',out,'INFO');
		}//Информация о сессии


		//Данные события
		if(typeOf(data['action_data']) =='object'){
			App.message('Информация о действии UID:'+data['action_data']['action_uid'],'<pre>'+this.syntaxHighlight(data['action_data']['data'])+'</pre>','INFO');
		}//Информация о сессии


		//Заявки
		if(typeOf(data['protocol'])=='array'){
			this.protocolDataSet(data['protocol']);
		}//Заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	protocolDataSet: function(data){
		if(!data.length){
			$('protocol_table_wrapper').hide();
			$('protocol_none').show();
			return;
		}else{
			$('protocol_none').hide();
			$('protocol_table_wrapper').show();
		}

		if(!this.objects['table_protocol']){
			this.objects['table_protocol'] = new jsTable('protocol_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'80px',
						sortable:true,
						caption: 'UID',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'action_uid',
						dataType: 'int'
					},
					{
						width:'80px',
						sortable:false,
						caption: 'ID сессии',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'session_uid',
						dataType: 'int',
						dataFunction:function(table, cell, text, data){
							new Element('a',{
								'href':'#',
								'text':text,
								'events':{
									'click':function(){
										App.pages[PAGE_NAME].sessionInfo(text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						width:'120px',
						sortable:true,
						caption: 'Время события',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'timestamp',
						dataType: 'date'
					},
					{
						caption: 'Пользователь',
						dataSource:'user_id',
						width:120,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							return App.pages[PAGE_NAME].objects['users'].filterResult('name','user_id',text);
						}
					},
					{
						caption: 'Действие',
						dataSource:'action',
						width:'100px',
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Сведения',
						dataSource:'info',
						width:'auto',
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						width:'40px',
						sortable:false,
						caption: 'Data',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'action_uid',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': '/client/images/info.png',
								'events':{
									'click':function(){
										App.pages[PAGE_NAME].actionData(text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
				]
			});
		}

		this.objects['table_protocol'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_user_protocol');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function



	//Фильтрация данных
	filter: function(){
		var filter = {
			'user_id': $('filter_user_id').getValue(),
			'date_from': $('filter_date_from').getValue(),
			'date_to': $('filter_date_to').getValue(),
			'limit': $('filter_limit').getValue()
		};
		App.localStorage.write('log_filter', Object.toQueryString(filter), true);
		filter['action'] = 'protocol.search';
		new axRequest({
			url : '/admin/ajax/users',
			data: filter,
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Информация о сессии
	sessionInfo: function(session_id){
		new axRequest({
			url : '/admin/ajax/users',
			data:  {
				'action': 'protocol.session.info',
				'session_id': session_id
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function



	//Информация о сессии
	actionData: function(action_uid){
		new axRequest({
			url : '/admin/ajax/users',
			data:  {
				'action': 'protocol.action.data',
				'action_uid': action_uid
			},
			silent: true,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	syntaxHighlight: function(obj){
		var str = JSON.stringify(obj, undefined, 4);
		App.echo(str);
		str = str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
		return str.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
			var cls = 'number';
			if (/^"/.test(match)) {
				if (/:$/.test(match)) {
					cls = 'key';
				} else {
					cls = 'string';
				}
			} else if (/true|false/.test(match)) {
				cls = 'boolean';
			} else if (/null/.test(match)) {
				cls = 'null';
			}
			return '<span class="' + cls + '">' + match + '</span>';
		});
	},

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();