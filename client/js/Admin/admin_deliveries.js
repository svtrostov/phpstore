;(function(){
var PAGE_NAME = 'admin_deliveries';
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
		'form_edit': null,
		'deliveries':[]
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
					dataSource:'delivery_id',
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
					width:'50px',
					sortable:true,
					caption: 'Цена',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'price',
					dataType: 'int'
				},
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['info_area'] = build_blockitem({
			'parent': 'info_area',
			'title'	: 'Настройка доставки'
		});
		$('tmpl_info').show().inject(this.objects['info_area']['container']);
		this.objects['info_area']['li'].hide();
		$('button_delete_record').hide();
		$('button_delete_record').addEvent('click', this.deleteRecord.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_info');
		this.objects['form_edit'].required('info_delivery_id').numeric('info_delivery_id').required('info_name').required('info_desc').
		required('info_price').ufloat('info_price').required('info_order_min').ufloat('info_order_min').required('info_order_max').ufloat('info_order_max');
		$('button_change_record').addEvent('click', this.changeRecord.bind(this));
		$('button_change_cancel').addEvent('click', this.changeRecordCancel.bind(this));

		this.objects['new_area'] = build_blockitem({
			'list': this.objects['info_area']['list'],
			'title'	: 'Добавить скидку'
		});
		this.objects['new_area']['li'].hide();
		$('tmpl_new').show().inject(this.objects['new_area']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_new');
		this.objects['form_add'].required('new_name').required('new_desc').
		required('new_price').ufloat('new_price').required('new_order_min').ufloat('new_order_min').required('new_order_max').ufloat('new_order_max');
		$('button_add_record').addEvent('click', this.addRecord.bind(this));
		$('button_new_record').addEvent('click', this.newRecord.bind(this));
		$('button_add_cancel').addEvent('click', this.changeRecordCancel.bind(this));
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
		if(typeOf(data['deliveries'])=='array'){
			data['deliveries'].sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			this.objects['table_list'].setData(data['deliveries']);
		}//Типы доступа


		if(data['delivery_id']){
			this.objects['table_list'].selectOf([String(data['delivery_id'])],1);
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
		for(var name in data){
			if($('info_'+name)) $('info_'+name).setValue(data[name]);
		}
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
				'action':'delivery.new',
				'name': $('new_name').getValue(),
				'desc': $('new_desc').getValue(),
				'price': $('new_price').getValue(),
				'order_min': $('new_order_min').getValue(),
				'order_max': $('new_order_max').getValue(),
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
				'action':'delivery.edit',
				'delivery_id': $('info_delivery_id').getValue(),
				'name': $('info_name').getValue(),
				'desc': $('info_desc').getValue(),
				'price': $('info_price').getValue(),
				'order_min': $('info_order_min').getValue(),
				'order_max': $('info_order_max').getValue(),
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
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['delivery_id']) != String($('info_delivery_id').value)) return;
		var delivery_id = String($('info_delivery_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранный способ доставки?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'delivery.delete',
						'delivery_id': delivery_id
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