var stackController_objects = {
	'change_password': false
};
/*----------------------------------------------------------------------
Контроллер стека сообщений от сервера
----------------------------------------------------------------------*/
function stackController(stack){

	if(!stack || typeOf(stack)!='object') return;

	var data;

	//Просмотр стека
	for(var item in stack){

		//обработка элемента стека
		switch(item){

			//Построение меню интерфейса
			case 'menu':
				if(!App.mainMenu) App.mainMenu = new jsMainMenu('navigation_area',{
					'menu': null,
					'build': false
				});
				App.mainMenu.build(stack[item]);
			break;

			//Построение меню интерфейса
			case 'adminmenu':
				if(!App.adminMenu) App.adminMenu = new jsTreeMenu({
					'parent': 'leftmenu',
					'menu_id': 2,
					'nodes': null
				});
				App.adminMenu.build(stack[item]);
			break;

			//Количество непросмотренных заявок
			case 'unreadrequests':
				if($('unreadcount'))$('unreadcount').set('text',stack[item]);
			break;

			case 'password':
				switch(stack[item]){
					case 'change':
						if(stackController_objects['change_password']) break;
						stackController_objects['change_password'] = true;
						new jsMessage({
							'width'		: '500px',
							'isUrgent'	: false,
							'autoDismiss': false,
							'centered'	: true,
							'title'		: 'Требуется смена пароля',
							'message'	: 'Вы зашли используя временный пароль<br/><br/>'+
										  'Зайдите в профиль Вашей учетной записи (кнопка &laquo;Профиль&raquo; в верхней левой части страницы) в раздел &laquo;Безопасность&raquo; и смените пароль.<br/><br/>',
							'type'		: 'confirmwarn',
							'isModal'	: true,
							'callback'	: function(){App.Location.doPage('/main/profile?id_area=security');},
							'yesLink'	: 'Сменить пароль сейчас',
							'noLink'	: 'Закрыть'
						}).say();
					break;
				}
			break;

			case 'contacts':
				switch(stack[item]){
					case 'change':
						if(stackController_objects['change_contacts']) break;
						stackController_objects['change_contacts'] = true;
						new jsMessage({
							'width'		: '500px',
							'isUrgent'	: false,
							'autoDismiss': false,
							'centered'	: true,
							'title'		: 'Не задана контактная информация',
							'message'	: 'Отсутствует номер Вашего мобильного телефона и/или адрес электронной почты.<br/>Эта информация может понадобиться Вашим коллегам в процессе согласования и исполнения заявок.<br/>'+
										  'Зайдите в профиль Вашей учетной записи (кнопка &laquo;Профиль&raquo; в верхней левой части страницы) в раздел &laquo;Профиль&raquo; для ввода контактных данных.<br/><br/>',
							'type'		: 'confirmwarn',
							'isModal'	: true,
							'callback'	: function(){App.Location.doPage('/main/profile?id_area=profile');},
							'yesLink'	: 'Ввести контакты сейчас',
							'noLink'	: 'Закрыть'
						}).say();
					break;
				}
			break;

		}//обработка элемента стека

	
	}//Просмотр стека

}//end function