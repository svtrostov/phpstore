;(function(){
var PAGE_NAME = 'admin_client_info';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': ['table_orders','table_tickets'],
		'validators': ['form_client'],
		'table_orders':null,
		'table_tickets':null,
		'form_client':null,
		//Вкладки
		'tabs': null,
		'client_info': null,
		'discounts':[],
		'orders': null,
		'order_statuses': [],
		'sticket':null
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

		this.objects['table_tickets'] = new jsTable('tickets_table_area',{
			'name': PAGE_NAME+'_tickets_table',
			'dataBackground1':'#efefef',
			'contextmenu':true,
			'class': 'jsTable',
			columns:[
				{
					name:'comment',
					width:'30px',
					sortable:false,
					caption: '-',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataFunction:function(table, cell, text, data){
						if(parseInt(data['is_support'])>0) return '-';
						new Element('img',{
							'src': INTERFACE_IMAGES+'/comment.gif',
							'styles':{
								'cursor':'pointer',
								'margin-left':'4px'
							},
							'events':{
								'click': function(e){
									App.pages[PAGE_NAME].objects['sticket'] = data;
									App.pages[PAGE_NAME].addTicketComment();
									e.stop();
								}
							}
						}).inject(cell);
						return '';
					}
				},
				{
					name: 'is_support',
					caption: 'Автор',
					sortable: true,
					width:'80px',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataType: 'html',
					dataSource:'is_support',
					dataFunction:function(table, cell, text, data){
						return (parseInt(text)>0?'DTBox':'Клиент');
					}
				},

				{
					name: 'timestamp',
					caption: 'Время сообщения',
					sortable: true,
					width:150,
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'center'},
					dataType: 'text',
					dataSource:'timestamp'
				},
				{
					name: 'message',
					caption: 'Сообщение',
					sortable: true,
					width:'auto',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataType: 'html',
					dataSource:'subject',
					dataFunction:function(table, cell, text, data){
						return '<b>'+data['subject']+'</b><br>'+data['message'];
					}
				},
				{
					name:'delete',
					width:'30px',
					sortable:false,
					caption: '-',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataFunction:function(table, cell, text, data){
						var product_id = data['product_id'];
						new Element('img',{
							'src': INTERFACE_IMAGES+'/delete.png',
							'styles':{
								'cursor':'pointer',
								'margin-left':'4px'
							},
							'events':{
								'click': function(e){
									App.message(
										'Подтвердите действие',
										'Вы действительно хотите удалить данное сообщение?',
										'CONFIRM',
										function(){
											App.pages[PAGE_NAME].deleteMessage(data['ticket_id']);
										}
									);
									e.stop();
								}
							}
						}).inject(cell);
						return '';
					}
				}
			]
		});

		this.setData(data);

		if(typeOf(this.objects['client_info'])!='object'){
			$('tabs_area').hide();
			$('tabs_none').show();
			return;
		}

		this.objects['form_client'] = new jsValidator('client_form');
		this.objects['form_client'].required('info_username').required('info_name').email('info_email').phone('info_phone').numeric('info_inn').numeric('info_kpp')

		$('client_save_button').addEvent('click',this.clientSaveInfo.bind(this));
		$('order_new_button').addEvent('click',this.orderCreate.bind(this));

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		if(data['created_order_id'] != undefined){
			$('tabs_area').hide();
			$('order_add_done').show();
			var link = '/admin/orders/info?order_id='+data['created_order_id'];
			$('new_order_id').set('text','ID заказа '+data['created_order_id']);
			setTimeout(function(){App.Location.doPage(link);},100);
		}

		if(typeOf(data['order_statuses'])=='array'){
			this.objects['order_statuses'] = data['order_statuses'];
		}

		//Список скидок
		if(typeOf(data['discounts'])=='array'){
			this.objects['discounts'] = data['discounts'];
			select_add({
				'list': 'info_discount_id',
				'key': 'discount_id',
				'value': 'name',
				'options': data['discounts'],
				'default': '0',
				'clear': true
			});
		}

		//Список менеджеров
		if(typeOf(data['managers'])=='array'){
			this.objects['managers'] = data['managers'];
			select_add({
				'list': 'info_manager_id',
				'key': 'user_id',
				'value': 'name',
				'options': data['managers'],
				'default': '0',
				'clear': false
			});
		}

		//client_info
		if(typeOf(data['client_info'])=='object'){
			this.objects['client_info'] = data['client_info'];
			for(var key in data['client_info']){
				switch(key){
					default:
						if($('info_'+key)){
							$('info_'+key).setValue(data['client_info'][key]);
						}
				}
			}
			$('bigblock_title').set('text','Клиент ID:'+data['client_info']['client_id']+' - '+data['client_info']['name']);
		}//client_info

		//Заявки
		if(typeOf(data['orders'])=='array'){
			if(data['orders'].length > 0){
				this.ordersDataSet(data['orders']);
			}
		}//Заявки

		//client_tickets
		if(typeOf(data['client_tickets'])=='array'){
			this.objects['client_tickets'] = data['client_tickets'];
			if(data['client_tickets'].length>0){
				$('tickets_none_area').hide();
				$('tickets_table_area').show();
				this.objects['table_tickets'].setData(data['client_tickets']);
			}else{
				$('tickets_table_area').hide();
				$('tickets_none_area').show();
			}
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	ordersDataSet: function(data){
		if(!data.length){
			$('orders_none').show();
			$('orders_table').hide();
			return;
		}

		if(!this.objects['table_orders']){
			this.objects['table_orders'] = new jsTable('orders_table',{
				'dataBackground1':'#efefef',
				'class': 'jsTable',
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
						caption: 'К-во позиций',
						sortable:true,
						dataSource:'products',
						width:'60px',
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'К-во товаров',
						sortable:true,
						dataSource:'count',
						width:'60px',
						dataStyle:{'text-align':'center'}
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
		$('orders_none').hide();
		$('orders_table').show();
	},


	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Сохранение изменений
	clientSaveInfo: function(){
		if(typeOf(this.objects['client_info'])!='object') return;
		if(!this.objects['form_client'].validate()) return;
		var client_id = this.objects['client_info']['client_id'];
		new axRequest({
			url : '/admin/ajax/clients',
			data: {
				'action':'client.edit',
				'client_id': client_id,
				'username': $('info_username').getValue(),
				'password': $('info_password').getValue(),
				'name': $('info_name').getValue(),
				'email': $('info_email').getValue(),
				'phone': $('info_phone').getValue(),
				'enabled': $('info_enabled').getValue(),
				'zip': $('info_zip').getValue(),
				'country': $('info_country').getValue(),
				'address': $('info_address').getValue(),
				'city': $('info_city').getValue(),
				'company': $('info_company').getValue(),
				'discount_id': $('info_discount_id').getValue(),
				'manager_id': $('info_manager_id').getValue(),
				'inn': $('info_inn').getValue(),
				'kpp': $('info_kpp').getValue(),
				'okpo': $('info_okpo').getValue(),
				'bank_name':		$('info_bank_name').getValue(),
				'bank_bik':			$('info_bank_bik').getValue(),
				'bank_account':		$('info_bank_account').getValue(),
				'bank_account_corr':$('info_bank_account_corr').getValue(),
				'legal_address': 	$('info_legal_address').getValue()
			},
			silent: false,
			waiter: true,
			display: 'none',
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	},//end function


	//Добавление комментария
	addTicketComment: function(e){
		if(typeOf(this.objects['client_info'])!='object') return;
		if(typeOf(this.objects['sticket'])!='object') return;
		App.comment('Добавление ответа на сообщение','',this.addTicketCommentProcess.bind(this));
	},//end function


	//Добавление комментария - процесс
	addTicketCommentProcess: function(comment){
		if(typeOf(this.objects['sticket'])!='object') return;
		comment = String(comment).trim();
		if(!comment.length) return;
		new axRequest({
			url : '/admin/ajax/clients',
			data:{
				'action':'comment.add',
				'ticket_id': this.objects['sticket']['ticket_id'],
				'client_id': this.objects['sticket']['client_id'],
				'for_client': true,
				'comment': comment
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


	//Удаление сообщения
	deleteMessage: function(ticket_id){
		if(typeOf(this.objects['client_info'])!='object') return;
		new axRequest({
			url : '/admin/ajax/clients',
			data:{
				'action':'comment.delete',
				'ticket_id': ticket_id,
				'client_id': this.objects['client_info']['client_id'],
				'for_client': true
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


	//Создание нового заказа
	orderCreate: function(){
		if(typeOf(this.objects['client_info'])!='object') return;
		var client_id = this.objects['client_info']['client_id'];
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите создать от имени этого клиента новый заказ?<br><br>Примечание: После подтверждения действия будет создан новый пустой заказ и открыта его карточка',
			'CONFIRM',
			function(){
		new axRequest({
			url : '/admin/ajax/order',
			data:{
				'action':'order.add',
				'client_id': client_id
			},
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
			}
		);

	},//end function

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();