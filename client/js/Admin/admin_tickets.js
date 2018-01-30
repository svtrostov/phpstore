;(function(){
var PAGE_NAME = 'admin_tickets';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_tickets'],
		'table_tickets': null,
		'clients':{},
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
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

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
						var product_id = data['product_id'];
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
					name: 'client_id',
					caption: 'ID клиента',
					sortable: true,
					width:'80px',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataType: 'html',
					dataSource:'client_id',
					dataFunction:function(table, cell, text, data){
						return '<a href="/admin/clients/info?client_id='+text+'" target=_blank>'+text+'</a>';
					}
				},
				{
					name: 'username',
					caption: 'Логин',
					sortable: true,
					visible: false,
					width:'100px',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'username'
				},
				{
					name: 'name',
					caption: 'Контактное лицо',
					sortable: true,
					width:'150px',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					name: 'company',
					caption: 'Организация',
					sortable: true,
					visible: false,
					width:'150px',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'company'
				},
				{
					name: 'email',
					caption: 'E-Mail',
					sortable: true,
					visible: false,
					width:'150px',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'email'
				},
				{
					name: 'phone',
					caption: 'Телефон',
					sortable: true,
					visible: false,
					width:'150px',
					styles:{'min-width':'100px'},
					dataStyle:{'text-align':'left'},
					dataSource:'phone'
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
										'Вы действительно хотите удалить данное сообщение клиента?',
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
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//clients
		if(typeOf(data['clients'])=='object'){
			this.objects['clients'] = data['clients'];
		}

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





	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/




	//Добавление комментария
	addTicketComment: function(e){
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
		new axRequest({
			url : '/admin/ajax/clients',
			data:{
				'action':'comment.delete',
				'ticket_id': ticket_id
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