;(function(){
var PAGE_NAME = 'admin_product_add';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': [],
		'validators': ['form_product_add'],
		'form_product_add':null,
		'currencies': null,
		'currencies_assoc': null,
		'categories_list':{},
		'pselector_selected':null
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
		['add_product_content','add_product_compatible'].each(function(id){
			var ed = tinyMCE.get(id);
			if(ed) ed.destroy(false);
		});
		for(var i in self.objects) self.objects[i] = null;
		self.objects = {};
	},//end function


	//Инициализация страницы
	start: function(data){

		this.objects['catalog_selector'] = new jsCatalog({
			'parent': 'category_selector_tree',
			'onselectnode': this.selectParentCategoryChange.bind(this),
			'showRoot': false
		});

		$('product_save_button').addEvent('click', this.productSaveInfo.bind(this));
		this.objects['form_product_add'] = new jsValidator('product_add_area');
		this.objects['form_product_add'].required('add_product_name').required('add_product_article').required('add_product_vendor').required('add_product_category_id', 'Не выбран каталог размещения добавляемого товара').
		required('add_product_measure').ufloat('add_product_weight').ufloat('add_product_size_x').ufloat('add_product_size_y').ufloat('add_product_size_z').
		required('add_product_base_price').ufloat('add_product_base_price');
		$('add_product_category_change').addEvent('click', this.selectParentCategory.bind(this));

		$('add_product_currency').addEvent('change', this.productBasePriceRubCalculate.bind(this));
		$('add_product_base_price').addEvent('change', this.productBasePriceRubCalculate.bind(this));

		$('category_selector_complete_button').addEvent('click', this.selectParentCategoryComplete.bind(this));
		$('category_selector_cancel_button').addEvent('click', this.selectParentCategoryClose.bind(this));


		this.setData(data);

	},//end function


	/*******************************************************************
	 * Обработка данных
	 ******************************************************************/

	//Обработка данных
	setData: function(data){
		var type;
		if(typeOf(data)!='object') return;


		if(typeOf(data['currencies'])=='array'){
			this.objects['currencies'] = data['currencies'];
			select_add({
				'list': 'add_product_currency',
				'key': 'code',
				'value': 'name',
				'options': data['currencies'],
				'default': 'rub',
				'clear': true
			});
			this.objects['currencies_assoc'] = {};
			for(var i=0; i<data['currencies'].length; i++){
				this.objects['currencies_assoc'][data['currencies'][i]['code']]= parseFloat(data['currencies'][i]['exchange']);
			}
		}

		if(typeOf(data['categories'])=='array'){
			this.objects['categories'] = data['categories'];
			this.objects['catalog_selector'].build(data['categories']);
		}

		if(data['product_id'] != undefined){
			$('product_add_area').hide();
			$('product_add_done').show();
			var link = '/admin/products/info?product_id='+data['product_id'];
			$('new_product_id').set('text','ID товара '+data['product_id']);
			setTimeout(function(){App.Location.doPage(link);},100);
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	catalogPathCalculate: function(data){
		var path='[Корень]';
		data.each(function(item, index){
			path+=' > '+item['name'];
		});
		return path;
	},




	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	/*
	 * Выбор родительского каталога
	 */
	selectParentCategory: function(){
		this.objects['catalog_selector_selected'] = null;
		$('bigblock_wrapper').hide();
		$('category_selector').show();
		$('category_selector_complete_button').hide();
	},//end function


	/*
	 * Закрытие выбора родительского каталога
	 */
	selectParentCategoryClose: function(){
		$('bigblock_wrapper').show();
		$('category_selector').hide();
	},//end function


	/*
	 * Родительский каталог был выбран
	 */
	selectParentCategoryComplete: function(){
		if(typeOf(this.objects['catalog_selector_selected'])!='object')return;
		$('add_product_category_name').setValue(this.objects['catalog_selector_selected']['name']);
		$('add_product_category_id').setValue(this.objects['catalog_selector_selected']['category_id']);
		this.objects['catalog_selector_selected'] = null;
		this.selectParentCategoryClose();
	},//end function


	/*
	 * Выбран новый родительский элемент
	 */
	selectParentCategoryChange: function(data){
		$('category_selector_complete_button').show();
		this.objects['catalog_selector_selected'] = data;
	},//end function


	/*
	 * Сохранение изменений в карточке товара
	 */
	productSaveInfo: function(){
		if(!this.objects['form_product_add'].validate()){
			var o,field='',msg='',i, errors = this.objects['form_product_add'].errorMessages;
			for(i=0; i<errors.length; i++){
				o = $(errors[i][0]).getParent('div span');
				if(o){
					msg += '<b>'+o.get('text')+'</b> '+errors[i][1]+'<br>';
				}else{
					msg += '<b>'+errors[i][0]+'</b> '+errors[i][1]+'<br>';
				}
			}
			App.message('Ошибки в заполнении формы', msg, 'ERROR');
			return;
		}
		var rx_data = {
				'action':'product.add',
				'enabled': $('add_product_enabled').getValue(),
				'name': $('add_product_name').getValue(),
				'description': $('add_product_description').getValue(),
				'part_nums': $('add_product_part_nums').getValue(),
				'admin_info': $('add_product_admin_info').getValue(),
				'article': $('add_product_article').getValue(),
				'vendor': $('add_product_vendor').getValue(),
				'yml': $('add_product_yml').getValue(),
				'stockgallery': $('add_product_stockgallery').getValue(),
				'measure': $('add_product_measure').getValue(),
				'weight': $('add_product_weight').getValue(),
				'size_x': $('add_product_size_x').getValue(),
				'size_y': $('add_product_size_y').getValue(),
				'size_z': $('add_product_size_z').getValue(),
				'currency': $('add_product_currency').getValue(),
				'base_price': $('add_product_base_price').getValue(),
				'category_id': $('add_product_category_id').getValue()
			};

		new axRequest({
			url : '/admin/ajax/catalog',
			data: rx_data,
			silent: false,
			waiter: true,
			callback: function(success, status, data){
				if(success){
					App.pages[PAGE_NAME].setData(data);
				}
			}
		}).request();
	}, //end function


	/*
	 * Пересчет базовой цены в рубли при изменении цены товара или валюты товара
	 */
	productBasePriceRubCalculate: function(){
		var currency = $('add_product_currency').getValue();
		var base_price = parseFloat($('add_product_base_price').getValue());
		if(isNaN(base_price))base_price=0;
		var price =  Math.ceil(this.objects['currencies_assoc'][currency] * base_price);
		$('add_product_base_price_rub').setValue(price+'.00');
	},//end function


	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();