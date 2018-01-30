;(function(){
var PAGE_NAME = 'admin_discounts';
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
		//
		'ir_types':null,
		'ir_types_assoc':{}
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
					dataSource:'discount_id',
					dataType: 'int'
				},
				{
					width:'auto',
					sortable:true,
					caption: 'Наименование',
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
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
		this.objects['form_edit'].required('info_discount_id').numeric('info_discount_id').required('info_name').required('info_description').required('info_percent').ufloat('info_percent').minValue('info_percent',0).maxValue('info_percent',100);
		$('button_change_record').addEvent('click', this.changeRecord.bind(this));
		$('button_change_cancel').addEvent('click', this.changeRecordCancel.bind(this));

		this.objects['new_area'] = build_blockitem({
			'list': this.objects['info_area']['list'],
			'title'	: 'Добавить скидку'
		});
		this.objects['new_area']['li'].hide();
		$('tmpl_new').show().inject(this.objects['new_area']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_new');
		this.objects['form_add'].required('new_name').required('new_description').required('new_percent').ufloat('new_percent').minValue('new_percent',0).maxValue('new_percent',100);
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
		if(typeOf(data['discounts'])=='array'){
			data['discounts'].sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			this.objects['table_list'].setData(data['discounts']);
		}//Типы доступа


		if(data['discount_id']){
			this.objects['table_list'].selectOf([String(data['discount_id'])],1);
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
		$('info_discount_id').value = data['discount_id'];
		$('info_name').value = data['name'];
		$('info_description').value = data['description'];
		$('info_percent').value = data['percent'];
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
				'action':'discount.new',
				'name': $('new_name').value,
				'description': $('new_description').value,
				'percent': $('new_percent').value
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
				'action':'discount.edit',
				'discount_id': $('info_discount_id').value,
				'name': $('info_name').value,
				'description': $('info_description').value,
				'percent': $('info_percent').value
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
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['discount_id']) != String($('info_discount_id').value)) return;
		var discount_id = String($('info_discount_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранную скидку?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'discount.delete',
						'discount_id': discount_id
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