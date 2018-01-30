;(function(){
var PAGE_NAME = 'admin_clients_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_clients'],
		'table_clients': null,
		'discounts':[],
		'managers':[]
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
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		$('filter_button').addEvent('click',this.filter);
		$('filter_search_name').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Установки фильтра
		if(typeOf(data['filter'])=='object'){
			for(var key in data['filter']){
				if($('filter_'+key)){
					$('filter_'+key).setValue(data['filter'][key]);
				}
			}
		}

		//Список менеджеров
		if(typeOf(data['managers'])=='array'){
			this.objects['managers'] = data['managers'];
			select_add({
				'list': 'filter_manager_id',
				'key': 'user_id',
				'value': 'name',
				'options': data['managers'],
				'default': 'all',
				'clear': false
			});
		}

		//Список скидок
		if(typeOf(data['discounts'])=='array'){
			this.objects['discounts'] = data['discounts'];
		}


		//Список клиентов
		if(typeOf(data['clients_search'])=='array'){
			this.clientsDataSet(data['clients_search']);
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	clientsDataSet: function(data){
		if(!data.length){
			$('clients_table').hide();
			$('clients_none').show();
			return;
		}else{
			$('clients_none').hide();
			$('clients_table').show();
		}

		if(!this.objects['table_clients']){
			this.objects['table_clients'] = new jsTable('clients_table',{
				'name': PAGE_NAME+'_clients_table',
				'dataBackground1':'#efefef',
				'contextmenu':true,
				columns:[
					{
						name:'edit',
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'client_id',
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/clients/info?client_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						name:'client_id',
						caption: 'ID',
						dataSource:'client_id',
						sortable:true,
						dataType:'int',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						name:'username',
						caption: 'Логин',
						dataSource:'username',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'left'}
					},
					{
						name:'enabled',
						caption: 'Учетная запись',
						dataSource:'enabled',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'center'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)==0) return '<font color="red">Заблокирована</font>';
							return '<font color="green">Активна</font>';
						}
					},
					{
						name:'discount_id',
						caption: 'Статус',
						dataSource:'discount_id',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)==0) return 'Обычный пользователь';
							return App.pages[PAGE_NAME].objects['discounts'].filterResult('name','discount_id',text);
						}
					},
					{
						name:'manager_id',
						caption: 'Менеджер',
						dataSource:'manager_id',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'left'},
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)==0) return '---';
							return App.pages[PAGE_NAME].objects['managers'].filterResult('name','user_id',text);
						}
					},
					{
						name:'name',
						caption: 'Контактное имя',
						sortable:true,
						dataSource:'name',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						name:'email',
						caption: 'E-Mail',
						dataSource:'email',
						width:150,
						sortable: true,
						dataStyle:{'text-align':'left'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							return '<a class="mailto" href="mailto:'+data['email']+'">'+data['email']+'</a>';
						}
					},
					{
						name:'phone',
						caption: 'Телефон',
						sortable:true,
						dataSource:'phone',
						width:100,
						dataStyle:{'text-align':'left'}
					},
					{
						name:'company',
						caption: 'Организация',
						sortable:true,
						dataSource:'company',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						name:'address',
						caption: 'Адрес',
						sortable:true,
						dataSource:'address',
						width:150,
						dataStyle:{'text-align':'left'}
					},
					{
						name:'create_time',
						caption: 'Время регистрации',
						sortable:true,
						dataSource:'create_time',
						width:100,
						dataStyle:{'text-align':'center'}
					},
					{
						name:'create_ip_addr',
						caption: 'IP адрес регистрации',
						sortable:true,
						dataSource:'create_ip_addr',
						width:100,
						dataStyle:{'text-align':'center'}
					}
				]
			});
		}

		this.objects['table_clients'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/



	//Фильтрация данных
	filter: function(){
		new axRequest({
			url : '/admin/ajax/clients',
			data:{
				'action':'clients.search',
				'term': $('filter_search_name').getValue(),
				'limit': $('filter_limit').getValue(),
				'manager_id': $('filter_manager_id').getValue(),
				'extended': 'clients_list'
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





	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();