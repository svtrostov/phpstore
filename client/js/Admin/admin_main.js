;(function(){
var PAGE_NAME = 'admin_main';
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
		//
		'orders': null,
		'order_statuses': []
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


		//Область статистики
		this.objects['li_orders'] = build_blockitem({
			'list': 'right_dashboard_list',
			'title': 'Последние заказы'
		});
		this.objects['li_orders']['container'].setStyles({
			'padding': '0px',
			'margin': '0px',
			'background-color':'#a8b0bd'
		});
		//$('tmpl_stats').inject(this.objects['li_orders']['container']).show();


		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type,el,area=$('tmpl_stats');
		if(typeOf(data)!='object') return;

		//
		if(typeOf(data['order_statuses'])=='array'){
			this.objects['order_statuses'] = data['order_statuses'];
		}

		//Статистика
		if(typeOf(data['stats'])=='object'){
			area.empty();
			for(var key in data['stats']){
				el = new Element('div',{'class':'iline w200'}).inject(area);
				new Element('span',{'text':key}).inject(el);
				new Element('p',{'text':data['stats'][key]}).inject(el);
			}
		}//Статистика


		//Заявки
		if(typeOf(data['orders'])=='array'){
			if(data['orders'].length > 0) this.ordersDataSet(data['orders']);
		}//Заявки


	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	ordersDataSet: function(data){
		if(!data.length) return;

		if(!this.objects['table_orders']){
			this.objects['table_orders'] = new jsTable(this.objects['li_orders']['container'],{
				'dataBackground1':'#b6bfce',
				'dataBackground2':'#a8b0bd',
				'class': 'jsTableDashboard',
				columns:[
					{
						width:'30px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'30px'},
						dataStyle:{'text-align':'center'},
						dataSource:'order_id',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/orders/info?order_id='+data['order_id']);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'Номер заказа',
						dataSource:'order_num',
						sortable:true,
						width:'70px',
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Статус',
						dataSource:'status',
						sortable:true,
						width:'80px',
						dataStyle:{'text-align':'left'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							var status = App.pages[PAGE_NAME].objects['order_statuses'].filterResult('name','status',parseInt(text));
							var color = App.pages[PAGE_NAME].objects['order_statuses'].filterResult('color','status',parseInt(text));
							if(!status) return '<font color="#aaaaaa">Неизвестный статус ID:'+text+'</font>';
							return '<font color="'+color+'">'+status+'</font>';
						}
					},
					{
						caption: 'Время заказа',
						sortable:true,
						dataSource:'timestamp',
						width:'80px',
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Клиент',
						sortable:true,
						dataSource:'name',
						width:'120px',
						dataStyle:{'text-align':'left'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							return text+(data['company']!=''? "<br>"+data['company'] : '');
						}
					},
					{
						caption: 'Сумма заказа (руб)',
						sortable:true,
						dataSource:'sum',
						width:'100px',
						dataStyle:{'text-align':'right'},
						dataType:'num'
					}
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
			url : '/admin/ajax/orders',
			data:{
				'action':'orders.search',
				'search_term': $('filter_search_term').getValue(),
				'term_type': $('filter_search_term_type').getValue(),
				'status': $('filter_request_status').getValue(),
				'type': $('filter_request_type').getValue(),
				'company_id': $('filter_company_id').getValue(),
				'iresource_id': $('filter_iresource_id').getValue(),
				'route_id': $('filter_route_id').getValue(),
				'period': $('filter_period').getValue()
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