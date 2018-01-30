;(function(){
var PAGE_NAME = 'admin_currencies';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_list','table_cbr'],
		'validators': ['form_add','form_edit'],
		'table_list': null,
		'form_add': null,
		'form_edit': null,
		'cbr_found': false
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

		//Организации
		this.objects['splitter'] = set_splitter_h({
			'left'		: $('area'),
			'right'		: $('info'),
			'splitter'	: $('splitter'),
			'parent'	: $('splitter').getParent('.contentareafull')
		});

		this.objects['table_cbr'] = new jsTable('cbr_table_area', {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'70px',
					sortable:true,
					caption: 'Код',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'CharCode'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Наименование',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'Name'
				},
				{
					width:'100px',
					sortable:true,
					caption: 'Курс',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'Value'
				},
			],
			selectType:1
		});

		var settings = {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'Код',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'code'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Наименование',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					width:'50px',
					sortable:true,
					caption: 'Курс',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'exchange',
					dataType: 'num'
				},
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['info_area'] = build_blockitem({
			'parent': 'info_area',
			'title'	: 'Настройка валютного курса'
		});
		$('tmpl_info').show().inject(this.objects['info_area']['container']);
		this.objects['info_area']['li'].hide();
		$('button_delete_record').hide();
		$('button_delete_record').addEvent('click', this.deleteRecord.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_info');
		this.objects['form_edit'].required('info_code').alpha('info_code').range('info_code',3,3).required('info_name').required('info_exchange').ufloat('info_exchange');
		$('button_change_record').addEvent('click', this.changeRecord.bind(this));
		$('button_change_cancel').addEvent('click', this.changeRecordCancel.bind(this));

		this.objects['new_area'] = build_blockitem({
			'list': this.objects['info_area']['list'],
			'title'	: 'Добавить валюту'
		});
		this.objects['new_area']['li'].hide();
		$('tmpl_new').show().inject(this.objects['new_area']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_new');
		this.objects['form_add'].required('new_code').alpha('new_code').range('new_code',3,3).required('new_name').required('new_exchange').ufloat('new_exchange');
		$('button_add_record').addEvent('click', this.addRecord.bind(this));
		$('button_new_record').addEvent('click', this.newRecord.bind(this));


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
		if(typeOf(data['currencies'])=='array'){
			data['currencies'].sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			this.objects['table_list'].setData(data['currencies']);
		}//Типы доступа


		if(data['code']){
			this.objects['table_list'].selectOf([String(data['code'])],1);
		}

		if(typeOf(data['cbr'])=='object'&&typeOf(data['cbr']['Valute'])=='array'){
			this.objects['cbr_found'] = true;
			this.objects['table_cbr'].setData(data['cbr']['Valute']);
		}


		this.selectItem();

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/








	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Выбран элемент
	selectItem: function(){
		$('button_delete_record').hide();
		this.objects['info_area']['li'].hide();
		this.objects['new_area']['li'].hide();
		this.objects['sobject']=null;
		if(this.objects['cbr_found']) $('cbr_area').show();
		if(!this.objects['table_list'].selectedRows.length) return;
		var tr = this.objects['table_list'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('info_code').value = data['code'];
		$('info_name').value = data['name'];
		$('info_exchange').value = data['exchange'];
		this.objects['sobject']=data;
		this.objects['info_area']['li'].show();
		$('button_delete_record').show();
		$('cbr_area').hide();
	},//end function



	//Добавление - показ формы
	newRecord: function(){
		if(!this.objects['table_list']) return;
		this.objects['table_list'].clearSelected();
		this.objects['sobject'] = null;
		this.objects['info_area']['li'].hide();
		this.objects['new_area']['li'].show();
		$('button_delete_record').hide();
	},//end function



	//Добавление - процесс
	addRecord: function(){
		if(!this.objects['form_add'].validate()) return;
		new axRequest({
			url : '/admin/ajax/admin',
			data:{
				'action':'currency.new',
				'code': $('new_code').value,
				'name': $('new_name').value,
				'exchange': $('new_exchange').value
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



	//Изменение - процесс
	changeRecord: function(){
		if(typeOf(this.objects['sobject'])!='object') return;
		if(!this.objects['form_edit'].validate()) return;
		new axRequest({
			url : '/admin/ajax/admin',
			data:{
				'action':'currency.edit',
				'code': $('info_code').value,
				'name': $('info_name').value,
				'exchange': $('info_exchange').value
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


	//Изменение - отмена
	changeRecordCancel: function(){
		$('button_delete_record').hide();
		this.objects['info_area']['li'].hide();
		this.objects['new_area']['li'].hide();
		this.objects['sobject']=null;
		if(this.objects['cbr_found']) $('cbr_area').show();
	},//end function


	//Удаление - процесс
	deleteRecord: function(){
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['code']) != String($('info_code').value)) return;
		var code = String($('info_code').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранную валюту?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'currency.delete',
						'code': code
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