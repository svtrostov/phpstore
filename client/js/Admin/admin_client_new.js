;(function(){
var PAGE_NAME = 'admin_client_new';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		//таблицы jsTable
		'tables': [],
		'validators': ['form_client'],
		'form_client':null,
		//Вкладки
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

		this.objects['form_client'] = new jsValidator('client_new_form');
		this.objects['form_client'].required('info_username').required('info_password').required('info_name').email('info_email').phone('info_phone').numeric('info_inn').numeric('info_kpp')

		$('client_save_button').addEvent('click',this.clientSaveInfo.bind(this));

		this.setData(data);

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		//Список скидок
		if(typeOf(data['discounts'])=='array'){
			this.objects['discounts'] = data['discounts'];
			select_add({
				'list': 'info_discount_id',
				'key': 'discount_id',
				'value': 'name',
				'options': data['discounts'],
				'default': '0',
				'clear': false
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

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Сохранение изменений
	clientSaveInfo: function(){
		if(!this.objects['form_client'].validate()) return;
		new axRequest({
			url : '/admin/ajax/clients',
			data: {
				'action':'client.new',
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

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();