;(function(){
var PAGE_NAME = 'admin_settings';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_list'],
		'validators': ['form_add','form_edit'],
		'table_list': null,
		'form_add': null,
		'form_edit': null
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
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		var settings = {
			'dataBackground1':'#efefef',
			'class': 'jsTable',
			columns: [
				{
					width:'150px',
					sortable:true,
					caption: 'Параметр',
					styles:{'min-width':'150px'},
					dataStyle:{'text-align':'left'},
					dataSource:'param'
				},
				{
					width:'300px',
					sortable:false,
					caption: 'Значение',
					styles:{'min-width':'300px'},
					dataStyle:{'text-align':'left'},
					dataSource:'value',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						var inpt = new Element('input',{
							'type':'text',
							'value':text,
						}).setStyles({
							'width':'270px'
						});
						var img = new Element('img',{
							'src':INTERFACE_IMAGES+'/edit.png',
						}).setStyles({
							'cursor':'pointer',
							'vertical-align':'middle',
							'margin-left':'3px'
						}).addEvents({
							'click': function(e){
								App.pages[PAGE_NAME].changeRecord(data['param'],inpt.getValue());
							}
						});
						inpt.inject(cell);
						img.inject(cell);
						return '';
					}
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Описание',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'desc'
				},
			],
			selectType:0
		};
		this.objects['table_list'] = new jsTable('table_area', settings);

		//Данные
		this.setData(data);

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Типы доступа
		if(typeOf(data['settings'])=='array'){
			data['settings'].sort(function(a,b){if(a['param']>b['param'])return 1;return -1;});
			this.objects['table_list'].setData(data['settings']);
		}//Типы доступа

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	//Изменение - процесс
	changeRecord: function(param, value){

		App.message(
			'Подтвердите действие',
			'Вы действительно хотите для параметра установить новое значение?:<br> <b>'+param+'</b> = '+value+'',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'config.edit',
						'param': param,
						'value': value
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