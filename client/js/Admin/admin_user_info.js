;(function(){
var PAGE_NAME = 'admin_user_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': ['table_login','table_action'],
		'validators': ['form_info'],
		'form_info':null,
		'table_login':null,
		'table_action':null,
		//Вкладки
		'tabs': null,
		'user_info': null,
		'user_access':[],
		'access_list':[]
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
		self.objects['tables'].each(function(table){
			if(self.objects[table]) self.objects[table].terminate();
		});
		self.objects['validators'].each(function(validator){
			if(self.objects[validator]) self.objects[validator].destroy();
		});
		if(self.objects['orgchart'])self.objects['orgchart'].empty();
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		//Вкладки
		this.objects['tabs'] = new jsTabPanel('tabs_area',{
			'onchange': null
		});

		//Инициализация таблицы выбора объектов доступа
		this.objects['table_login'] = new jsTable('login_table',{
			'class': 'jsTable',
			columns: [
				{
					width:'10%',
					sortable:false,
					caption: 'UID сессии',
					styles:{'min-width':'150px'},
					dataStyle:{'text-align':'center'},
					dataSource:'session_uid'
				},
				{
					width:'30%',
					sortable:false,
					caption: 'Время входа',
					styles:{'min-width':'150px'},
					dataStyle:{'text-align':'center'},
					dataSource:'login_time'
				},
				{
					width:'20%',
					sortable:false,
					caption: 'IP адрес (proxy)',
					styles:{'min-width':'150px'},
					dataStyle:{'text-align':'center'},
					dataSource:'ip_addr'
				},
				{
					width:'20%',
					sortable:false,
					caption: 'IP адрес (реальный)',
					styles:{'min-width':'150px'},
					dataStyle:{'text-align':'center'},
					dataSource:'ip_real'
				}
			],
			selectType:1
		});

		this.setData(data);

		if(typeOf(this.objects['user_info'])!='object'){
			$('tabs_area').hide();
			$('tabs_none').show();
			return;
		}

		this.objects['form_info'] = new jsValidator('user_form');
		this.objects['form_info'].required('info_username').required('info_name');

		$('user_save_button').addEvent('click',this.userSaveInfo.bind(this));

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		if(typeOf(data['access_list'])=='array'){
			this.objects['access_list'] = data['access_list'];
			for(var i=0; i<data['access_list'].length; i++){
				var more = data['access_list'][i]['more'];
				data['access_list'][i]['name'] = data['access_list'][i]['name'] + ' <font color="#999">('+data['access_list'][i]['access'] + (typeOf(more)=='array' && more.length>0 ? ' ИЛИ ' + more.join(' ИЛИ ') : '' ) +')</font>';
			}
		}

		if(typeOf(data['user_access'])=='array'){
			this.objects['user_access'] = data['user_access'];
			var uaccess = [];
			for(var i=0; i<data['user_access'].length; i++){
				if(parseInt(data['user_access'][i]['level'])>0) uaccess.push(data['user_access'][i]['access']);
			}
			buildChecklist({
				'parent': 'access_area',
				'options': this.objects['access_list'],
				'key': 'access',
				'value': 'name',
				'selected': uaccess,
				'clear': true
			});
		}

		if(typeOf(data['login_log'])=='array'){
			if(data['login_log'].length == 0){
				$('login_table').hide();
				$('login_none').show();
			}else{
				$('login_none').hide();
				this.objects['table_login'].setData(data['login_log']);
				$('login_table').show();
			}
		}


		//user_info
		if(typeOf(data['user_info'])=='object'){
			this.objects['user_info'] = data['user_info'];
			for(var key in data['user_info']){
				switch(key){
					default:
						if($('info_'+key)){
							$('info_'+key).setValue(data['user_info'][key]);
						}
				}
			}
			$('bigblock_title').set('text','Пользователь ID:'+data['user_info']['user_id']+' - '+data['user_info']['name']);
		}//user_info


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/



	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Сохранение изменений
	userSaveInfo: function(){
		if(typeOf(this.objects['user_info'])!='object') return;
		if(!this.objects['form_info'].validate()) return;
		var user_id = this.objects['user_info']['user_id'];
		var uaccess=[];
		$('access_area').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){uaccess.push(el.value);}});
		new axRequest({
			url : '/admin/ajax/users',
			data: {
				'action':'user.edit',
				'user_id': user_id,
				'username': $('info_username').getValue(),
				'password': $('info_password').getValue(),
				'name': $('info_name').getValue(),
				'enabled': $('info_enabled').getValue(),
				'access': uaccess
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function





	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();