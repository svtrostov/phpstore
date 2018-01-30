;(function(){
var PAGE_NAME = 'admin_user_add';
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
		'validators': ['form_info'],
		'form_info':null,
		//Вкладки
		'tabs': null,
		'access_list':[]
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

		this.setData(data);

		this.objects['form_info'] = new jsValidator('user_form');
		this.objects['form_info'].required('info_username').required('info_name').required('info_password');

		$('user_save_button').addEvent('click',this.userAdd.bind(this));

	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var text;
		if(typeOf(data)!='object') return;

		if(typeOf(data['access_list'])=='array'){
			this.objects['access_list'] = data['access_list'];
			for(var i=0; i<data['access_list'].length; i++){
				var more = data['access_list'][i]['more'];
				data['access_list'][i]['name'] = data['access_list'][i]['name'] + ' <font color="#999">('+data['access_list'][i]['access'] + (typeOf(more)=='array' && more.length>0 ? ' ИЛИ ' + more.join(' ИЛИ ') : '' ) +')</font>';
			}
			buildChecklist({
				'parent': 'access_area',
				'options': this.objects['access_list'],
				'key': 'access',
				'value': 'name',
				'clear': true
			});
		}

		if(data['user_id'] != undefined){
			$('user_form').hide();
			$('user_done').show();
			var link = '/admin/users/info?user_id='+data['user_id'];
			setTimeout(function(){App.Location.doPage(link);},100);
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/



	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	//Сохранение изменений
	userAdd: function(){
		if(!this.objects['form_info'].validate()) return;
		var uaccess=[];
		$('access_area').getElements('input[type=checkbox]').each(function(el){if(el.checked==true){uaccess.push(el.value);}});
		new axRequest({
			url : '/admin/ajax/users',
			data: {
				'action':'user.add',
				'username': $('info_username').getValue(),
				'password': $('info_password').getValue(),
				'name': $('info_name').getValue(),
				'enabled': $('info_enabled').getValue(),
				'access': uaccess
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





	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();