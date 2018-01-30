;(function(){
var PAGE_NAME = 'admin_orders_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_orders'],
		'table_orders': null,
		'order_statuses':[],
		'deliveries':[],
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
		$('bigblock_expander').addEvent('click',this.fullscreen);
		$('filter_button').addEvent('click',this.filter);
		$('filter_term').addEvent('keydown',function(e){if(e.code==13) this.filter();}.bind(this));

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		if(typeOf(data['deliveries'])=='array'){
			this.objects['deliveries'] = data['deliveries'];
			select_add({
				'list': 'filter_delivery',
				'key': 'delivery_id',
				'value': 'name',
				'options': data['deliveries'],
				'default': 'all',
				'clear': false
			});
		}

		if(typeOf(data['order_statuses'])=='array'){
			this.objects['order_statuses'] = data['order_statuses'];
			select_add({
				'list': 'filter_status',
				'key': 'status',
				'value': 'name',
				'options': data['order_statuses'],
				'default': '1',
				'clear': false
			});
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

		//Установки фильтра
		if(typeOf(data['filter'])=='object'){
			for(var key in data['filter']){
				if($('filter_'+key)){
					$('filter_'+key).setValue(data['filter'][key]);
				}
			}
		}


		//Список заказов
		if(typeOf(data['orders_search'])=='array'){
			this.ordersDataSet(data['orders_search']);
		}


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/

	//Развертывание окна на весь экран
	fullscreen: function(normal_screen){
		var panel = $('page_orders_list');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function


	ordersDataSet: function(data){
		if(!data.length){
			$('orders_table').hide();
			$('orders_none').show();
			return;
		}else{
			$('orders_none').hide();
			$('orders_table').show();
		}

		if(!this.objects['table_orders']){
			this.objects['table_orders'] = new jsTable('orders_table',{
				'dataBackground1':'#efefef',
				'name': PAGE_NAME+'_orders_table',
				'contextmenu':true,
				columns:[
					{
						name:'document_go',
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'order_id',
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
										App.pages[PAGE_NAME].fullscreen(true);
										App.Location.doPage('/admin/orders/info?order_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						name:'order_id',
						caption: 'ID',
						dataSource:'order_id',
						sortable:true,
						dataType:'int',
						width:'60px',
						dataStyle:{'text-align':'center'}
					},
					{
						name:'order_num',
						caption: 'Номер заказа',
						dataSource:'order_num',
						sortable:true,
						width:'80px',
						dataStyle:{'text-align':'left'}
					},
					{
						name:'status',
						caption: 'Статус',
						dataSource:'status',
						sortable:true,
						width:'120px',
						dataStyle:{'text-align':'center'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							var status = App.pages[PAGE_NAME].objects['order_statuses'].filterResult('name','status',parseInt(text));
							var color = App.pages[PAGE_NAME].objects['order_statuses'].filterResult('color','status',parseInt(text));
							if(!status) return '<font color="#aaaaaa">Неизвестный статус ID:'+text+'</font>';
							return '<font color="'+color+'">'+status+'</font>';
						}
					},
					{
						name:'timestamp',
						caption: 'Время заказа',
						sortable:true,
						dataSource:'timestamp',
						width:'80px',
						dataStyle:{'text-align':'center'}
					},
					{
						name:'client_id',
						width:'100px',
						sortable:false,
						caption: 'Клиент',
						styles:{'min-width':'60px'},
						dataStyle:{'text-align':'center'},
						dataSource:'client_id',
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)>0){
								new Element('a',{
									'href': '/admin/clients/info?client_id='+text,
									'target':'_blank',
									'class':'no-push',
									'text': text,
									'events':{
										'click':function(e){e.stopPropagation();return true;}
									}
								}).inject(cell);
								return '';
							}else{
								return 'Анонимус';
							}
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
							if(parseInt(data['client_id'])==0) return '---';
							if(parseInt(text)==0) return '---';
							return App.pages[PAGE_NAME].objects['managers'].filterResult('name','user_id',text);
						}
					},
					{
						name:'name',
						caption: 'Контактное имя',
						sortable:true,
						dataSource:'name',
						width:'120px',
						dataStyle:{'text-align':'left'}
					},
					{
						name:'email',
						caption: 'E-Mail',
						dataSource:'email',
						width:'120px',
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
						width:'100px',
						dataStyle:{'text-align':'left'}
					},
					{
						name:'company',
						caption: 'Организация',
						sortable:true,
						dataSource:'company',
						width:'120px',
						dataStyle:{'text-align':'left'}
					},
					{
						name:'products',
						caption: 'К-во позиций',
						sortable:true,
						dataSource:'products',
						width:'70px',
						dataStyle:{'text-align':'center'},
						dataType:'int'
					},
					{
						name:'count',
						caption: 'К-во товаров',
						sortable:true,
						dataSource:'count',
						width:'70px',
						dataStyle:{'text-align':'center'},
						dataType:'int'
					},
					{
						name:'sum',
						caption: 'Сумма заказа (руб)',
						sortable:true,
						dataSource:'sum',
						width:'100px',
						dataStyle:{'text-align':'right'},
						dataType:'num'
					},
					{
						name:'delivery_id',
						caption: 'Метод доставки',
						dataSource:'delivery_id',
						sortable:true,
						width:'150px',
						dataStyle:{'text-align':'left'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							var delivery = App.pages[PAGE_NAME].objects['deliveries'].filterResult('name','delivery_id',parseInt(text));
							if(!delivery) return '<font color="#aaaaaa">Неизвестный метод ID:'+text+'</font>';
							return delivery;
						}
					},
					{
						name:'delivery_cost',
						caption: 'Цена доставки (руб)',
						sortable:true,
						dataSource:'delivery_cost',
						width:'100px',
						dataStyle:{'text-align':'right'},
						dataType:'num'
					},
				]
			});
		}

		this.objects['table_orders'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/



	//Фильтрация данных
	filter: function(){
		new axRequest({
			url : '/admin/ajax/order',
			data:{
				'action':'orders.search',
				'term': $('filter_term').getValue(),
				'status': $('filter_status').getValue(),
				'delivery': $('filter_delivery').getValue(),
				'period': $('filter_period').getValue(),
				'manager_id': $('filter_manager_id').getValue(),
				'limit': $('filter_limit').getValue(),
				'extended': 'orders_list'
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