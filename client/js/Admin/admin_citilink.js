;(function(){
var PAGE_NAME = 'admin_citilink';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': [],
		'validators': [],
		'price_info': null
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

		$('upload_file').addEvent('change', this.priceFileChange.bind(this));
		$('upload_file_button').addEvent('click', this.priceUpload.bind(this)).hide();


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

		if(typeOf(data['price_info'])=='object'){
			this.objects['price_info'] = data['price_info'];
			$('last_price_time').setValue(data['price_info']['time']);
			$('last_price_info').show();
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/



	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/


	/*
	 * Выбор файла прайс-листа
	 */
	priceFileChange: function(){
		var files = $('upload_file').files;
		App.echo(files);
		App.echo(typeOf(files));
		if(typeOf(files)!='collection') return;
		if(files.length > 0){
			$('upload_file_button').show();
			$('upload_file_button_title').set('text','Загрузить "'+files[0]['name']+'" на сервер');
		}else{
			$('upload_file_button').hide();
		}
	},//end function



	/*
	 * Загрузка файла прайс-листа на сервер
	 */
	priceUpload: function(){
		var price_exists = false;
		if(typeOf(this.objects['price_info'])=='object') price_exists = true;

		if(price_exists){
			App.message(
				'Подтвердите действие',
				'Вы действительно хотите загрузить новый прайс-лист на сервер?<br>Текущий файл прайс-листа, загруженный '+this.objects['price_info']['time']+' будет удален и заменен на новый',
				'CONFIRM',
				this.priceUploadProcess.bind(this)
			);
		}else{
			this.priceUploadProcess();
		}
	},//end function

	priceUploadProcess: function(){
		new axRequest({
			uploaderForm: $('upload_form'),
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					$('upload_form').reset();
					$('upload_file_button').hide();
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).upload();
	},//end function



	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();