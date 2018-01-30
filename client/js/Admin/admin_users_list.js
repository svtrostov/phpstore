;(function(){
var PAGE_NAME = 'admin_users_list';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_users'],
		'table_users': null
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

		this.setData(data);
	},//end function




	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;

		//Список клиентов
		if(typeOf(data['users'])=='array'){
			this.usersDataSet(data['users']);
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	usersDataSet: function(data){
		if(!data.length){
			$('users_table').hide();
			$('users_none').show();
			return;
		}else{
			$('users_none').hide();
			$('users_table').show();
		}

		if(!this.objects['table_users']){
			this.objects['table_users'] = new jsTable('users_table',{
				'dataBackground1':'#efefef',
				columns:[
					{
						width:'40px',
						sortable:false,
						caption: '-',
						styles:{'min-width':'40px'},
						dataStyle:{'text-align':'center'},
						dataSource:'user_id',
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							new Element('img',{
								'src': INTERFACE_IMAGES+'/document_go.png',
								'styles':{
									'cursor':'pointer',
									'margin-left':'4px'
								},
								'events':{
									'click': function(e){
										App.Location.doPage('/admin/users/info?user_id='+text);
									}
								}
							}).inject(cell);
							return '';
						}
					},
					{
						caption: 'ID',
						dataSource:'user_id',
						sortable:true,
						dataType:'int',
						width:60,
						dataStyle:{'text-align':'center'}
					},
					{
						caption: 'Логин',
						dataSource:'username',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'left'}
					},
					{
						caption: 'Статус',
						dataSource:'enabled',
						sortable:true,
						width:100,
						dataStyle:{'text-align':'left'},
						dataType:'html',
						dataFunction:function(table, cell, text, data){
							if(parseInt(text)==0) return '<font color="red">Блокирован</font>';
							return '<font color="green">Активен</font>';
						}
					},
					{
						caption: 'Имя администратора',
						sortable:true,
						dataSource:'name',
						width:150,
						dataStyle:{'text-align':'left'}
					}
				]
			});
		}

		this.objects['table_users'].setData(data);
	},






	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();