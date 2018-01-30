;(function(){
var PAGE_NAME = 'admin_warehouses';
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

		//Организации
		this.objects['splitter'] = set_splitter_h({
			'left'		: $('area'),
			'right'		: $('info'),
			'splitter'	: $('splitter'),
			'parent'	: $('splitter').getParent('.contentareafull')
		});

		var settings = {
			'dataBackground1':'#efefef',
			'class': 'jsTableLight',
			columns: [
				{
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'warehouse_id',
					dataType: 'int'
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
					width:'25px',
					sortable:true,
					caption: '',
					styles:{'min-width':'25px'},
					dataStyle:{'text-align':'center'},
					dataSource:'enabled',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="red">Off</font>';
						return '<font color="green">On</font>';
					}
				}
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['info_area'] = build_blockitem({
			'parent': 'info_area',
			'title'	: 'Настройка скидки'
		});
		$('tmpl_info').show().inject(this.objects['info_area']['container']);
		this.objects['info_area']['li'].hide();
		$('button_delete_record').hide();
		$('button_delete_record').addEvent('click', this.deleteRecord.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_info');
		this.objects['form_edit'].required('info_warehouse_id').numeric('info_warehouse_id').required('info_name').required('info_desc');
		$('button_change_record').addEvent('click', this.changeRecord.bind(this));
		$('button_change_cancel').addEvent('click', this.changeRecordCancel.bind(this));

		this.objects['new_area'] = build_blockitem({
			'list': this.objects['info_area']['list'],
			'title'	: 'Добавить скидку'
		});
		this.objects['new_area']['li'].hide();
		$('tmpl_new').show().inject(this.objects['new_area']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_new');
		this.objects['form_add'].required('new_name').required('new_desc');
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
		if(typeOf(data['warehouses'])=='array'){
			data['warehouses'].sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			this.objects['table_list'].setData(data['warehouses']);
		}//Типы доступа


		if(data['warehouse_id']){
			this.objects['table_list'].selectOf([String(data['warehouse_id'])],1);
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
		if(!this.objects['table_list'].selectedRows.length) return;
		var tr = this.objects['table_list'].selectedRows[0];
		if(typeOf(tr)!='element') return;
		var data = tr.retrieve('data');
		if(typeOf(data)!='object') return;
		$('info_warehouse_id').value = data['warehouse_id'];
		$('info_name').value = data['name'];
		$('info_desc').value = data['desc'];
		$('info_enabled').setValue(data['enabled']);
		this.objects['sobject']=data;
		this.objects['info_area']['li'].show();
		$('button_delete_record').show();
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
				'action':'warehouse.new',
				'name': $('new_name').getValue(),
				'desc': $('new_desc').getValue(),
				'enabled': $('new_enabled').getValue()
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
				'action':'warehouse.edit',
				'warehouse_id': $('info_warehouse_id').getValue(),
				'name': $('info_name').getValue(),
				'desc': $('info_desc').getValue(),
				'enabled': $('info_enabled').getValue()
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
	},//end function


	//Удаление - процесс
	deleteRecord: function(){
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['warehouse_id']) != String($('info_warehouse_id').value)) return;
		var warehouse_id = String($('info_warehouse_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный склад?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'warehouse.delete',
						'warehouse_id': warehouse_id
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