;(function(){
var PAGE_NAME = 'admin_accounts';
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
					width:'30px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'account_id',
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
					caption: 'Код',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'left'},
					dataSource:'code'
				}
			],
			selectType:1
		};
		this.objects['table_list'] = new jsTable('table_area', settings);
		this.objects['table_list'].addEvent('click', this.selectItem.bind(this));

		this.objects['info_area'] = build_blockitem({
			'parent': 'info_area',
			'title'	: 'Настройка реквизитов'
		});
		$('tmpl_info').show().inject(this.objects['info_area']['container']);
		this.objects['info_area']['li'].hide();
		$('button_delete_record').hide();
		$('button_delete_record').addEvent('click', this.deleteRecord.bind(this));
		this.objects['form_edit'] = new jsValidator('tmpl_info');
		this.objects['form_edit'].required('info_account_id').numeric('info_account_id').required('info_name').required('info_code').
		required('info_bank_name').required('info_bank_bik').required('info_bank_account').numeric('info_bank_account').required('info_bank_account_corr').numeric('info_bank_account_corr').
		required('info_company').required('info_address').required('info_phone').phone('info_phone').numeric('info_ogrn').numeric('info_inn').numeric('info_kpp').required('info_sign_name').required('info_sign_post');
		$('button_change_record').addEvent('click', this.changeRecord.bind(this));
		$('button_change_cancel').addEvent('click', this.changeRecordCancel.bind(this));

		this.objects['new_area'] = build_blockitem({
			'list': this.objects['info_area']['list'],
			'title'	: 'Добавить новые реквизиты'
		});
		this.objects['new_area']['li'].hide();
		$('tmpl_new').show().inject(this.objects['new_area']['container']);
		this.objects['form_add'] = new jsValidator('tmpl_new');
		this.objects['form_add'].required('new_name').required('new_code').
		required('new_bank_name').required('new_bank_bik').required('new_bank_account').numeric('new_bank_account').required('new_bank_account_corr').numeric('new_bank_account_corr').
		required('new_company').required('new_address').required('new_phone').phone('new_phone').numeric('new_ogrn').numeric('new_inn').numeric('new_kpp').required('new_sign_name').required('new_sign_post');
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
		if(typeOf(data['accounts'])=='array'){
			data['accounts'].sort(function(a,b){if(a['name']>b['name'])return 1;return -1;});
			this.objects['table_list'].setData(data['accounts']);
		}//Типы доступа


		if(data['account_id']){
			this.objects['table_list'].selectOf([String(data['account_id'])],1);
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
		for(var key in data){
			if($('info_'+key))$('info_'+key).setValue(data[key]);
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
				'action':			'account.new',
				'code':				$('new_code').getValue(),
				'name':				$('new_name').getValue(),
				'bank_name':		$('new_bank_name').getValue(),
				'bank_bik':			$('new_bank_bik').getValue(),
				'bank_account':		$('new_bank_account').getValue(),
				'bank_account_corr':$('new_bank_account_corr').getValue(),
				'company':			$('new_company').getValue(),
				'address':			$('new_address').getValue(),
				'phone':			$('new_phone').getValue(),
				'inn':				$('new_inn').getValue(),
				'kpp':				$('new_kpp').getValue(),
				'okpo':				$('new_okpo').getValue(),
				'sign_name':		$('new_sign_name').getValue(),
				'sign_post':		$('new_sign_post').getValue(),
				'address_real':		$('new_address_real').getValue(),
				'ogrn':				$('new_ogrn').getValue(),
				'certificate':		$('new_certificate').getValue()
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
				'action':			'account.edit',
				'account_id':		$('info_account_id').getValue(),
				'code':				$('info_code').getValue(),
				'name':				$('info_name').getValue(),
				'bank_name':		$('info_bank_name').getValue(),
				'bank_bik':			$('info_bank_bik').getValue(),
				'bank_account':		$('info_bank_account').getValue(),
				'bank_account_corr':$('info_bank_account_corr').getValue(),
				'company':			$('info_company').getValue(),
				'address':			$('info_address').getValue(),
				'phone':			$('info_phone').getValue(),
				'inn':				$('info_inn').getValue(),
				'kpp':				$('info_kpp').getValue(),
				'okpo':				$('info_okpo').getValue(),
				'sign_name':		$('info_sign_name').getValue(),
				'sign_post':		$('info_sign_post').getValue(),
				'address_real':		$('info_address_real').getValue(),
				'ogrn':				$('info_ogrn').getValue(),
				'certificate':		$('info_certificate').getValue()
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
		if(typeOf(this.objects['sobject'])!='object' || String(this.objects['sobject']['account_id']) != String($('info_account_id').value)) return;
		var account_id = String($('info_account_id').value);
		App.message(
			'Подтвердите действие',
			'Вы действительно хотите удалить выбранные реквизиты?',
			'CONFIRM',
			function(){
				new axRequest({
					url : '/admin/ajax/admin',
					data:{
						'action':'account.delete',
						'account_id': account_id
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