;(function(){
var PAGE_NAME = 'admin_request_log';
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

		//Создание календарей
		new Picker.Date($$('.calendar_input'), {
			timePicker: false,
			positionOffset: {x: 0, y: 2},
			pickerClass: 'calendar',
			useFadeInOut: false
		});

		$('navigator_page_prev').addEvent('click', this.navigatorPagePrev.bind(this));
		$('navigator_page_next').addEvent('click', this.navigatorPageNext.bind(this));
		$('navigator_page_no').addEvent('change', this.navigatorPageSet.bind(this));

		$('filter_date_from').setValue(_TODAY);
		$('filter_date_to').setValue(_TODAY);

		this.filter();

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Заявки
		if(typeOf(data['logs'])=='array'){
			this.protocolDataSet(data['logs']);
		}//Заявки

		if(typeOf(data['navigator'])=='object'){
			$('navigator_page_no').setValue(data['navigator']['page_no']);
			$('navigator_per_page').setValue(data['navigator']['per_page']);
			$('navigator_count').set('text',data['navigator']['count']);
		}

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
						caption: 'ID',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'id',
						dataType: 'int'
					},
					{
						width:'80px',
						sortable:true,
						caption: 'Session ID',
						styles:{'min-width':'140px'},
						dataStyle:{'text-align':'center'},
						dataSource:'session_id'
					},
					{
						width:'80px',
						sortable:true,
						caption: 'Код',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'error_code',
						dataType: 'int'
					},
					{
						width:'120px',
						sortable:true,
						caption: 'Дата/время',
						styles:{'min-width':'80px'},
						dataStyle:{'text-align':'center'},
						dataSource:'timestamp',
						dataType: 'date'
					},
					{
						caption: 'Клиент',
						dataSource:'client_id',
						width:80,
						sortable: true,
						dataStyle:{'text-align':'center'},
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
						caption: 'Запрошенная страница',
						dataSource:'url',
						width:'200px',
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Откуда был запрос',
						dataSource:'referer',
						width:'auto',
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'IP адрес',
						dataSource:'ip_addr',
						width:'120px',
						sortable: true,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'User Agent',
						dataSource:'user_agent',
						width:'200px',
						sortable: true,
						dataStyle:{'text-align':'left'}
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
		var panel = $('page_request_log');
		var title = $('bigblock_title').getParent('.titlebar');
		if(title.hasClass('expanded')||normal_screen==true){
			title.removeClass('expanded').addClass('normal');
			panel.inject($('adminarea'));
		}else{
			title.removeClass('normal').addClass('expanded');
			panel.inject(document.body);
		}
	},//end function


	/*
	 * Следующая страница
	 */
	navigatorPageNext: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)) page_no = 0;
		$('navigator_page_no').setValue(page_no + 1);
		this.filter();
	},

	/*
	 * Предыдущая страница
	 */
	navigatorPagePrev: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)){
			page_no = 1;
		}else{
			page_no = (page_no > 1 ? page_no-1 : 1);
		}
		$('navigator_page_no').setValue(page_no);
		this.filter();
	},


	/*
	 * Задана страница
	 */
	navigatorPageSet: function(){
		var page_no = parseInt($('navigator_page_no').getValue());
		if(isNaN(page_no)) page_no = 1;
		$('navigator_page_no').setValue(page_no);
		this.filter();
	},


	//Фильтрация данных
	filter: function(){
		new axRequest({
			url : '/admin/ajax/clients',
			data: {
				'action': 'log.request',
				'page_no':$('navigator_page_no').getValue(),
				'per_page':$('navigator_per_page').getValue(),
				'date_from': $('filter_date_from').getValue(),
				'ip_addr': $('filter_ip').getValue(),
				'url': $('filter_url').getValue(),
				'code': $('filter_code').getValue(),
				'date_to': $('filter_date_to').getValue()
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