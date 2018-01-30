;(function(){
var PAGE_NAME = 'admin_offers';
/*////////////////////////////////////////////////////////////////////*/
window[PAGE_NAME] = function(success, status, data){
App.pages[PAGE_NAME].enter(success, status, data);};
App.pages[PAGE_NAME] = {

	//Объекты
	objects: {},

	//Объекты, используемые по-умолчанию
	defaults: {
		'tables': ['table_offers'],
		'table_products':null
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

		if(typeOf(data['offers'])=='array'){
			this.offersDataSet(data['offers']);
		}

	},//end function





	/*******************************************************************
	 * Функции BuildTime
	 ******************************************************************/


	offersDataSet: function(data){
		if(!data.length){
			$('offers_table').hide();
			$('offers_none').show();
			return;
		}else{
			$('offers_none').hide();
			$('offers_table').show();
		}

		if(!this.objects['table_offers']){
			this.objects['table_offers'] = new jsTable('offers_table',{
				'dataBackground1':'#efefef',
				sectionCollapsible: true,
				columns:[
				{
					name:'edit',
					width:'30px',
					sortable:false,
					caption: '-',
					styles:{'min-width':'30px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataFunction:function(table, cell, text, data){
						var a = new Element('a',{
							'href': '/admin/products/info?product_id='+text,
							'target':'_blank',
							'class':'no-push',
							'events':{
								'click':function(e){e.stopPropagation();return true;}
							}
						}).inject(cell);
						var img = new Element('img',{
							'src': INTERFACE_IMAGES+'/edit.png',
							'styles':{
								'cursor':'pointer',
								'margin-left':'4px'
							}
						}).inject(a);
						return '';
					}
				},
				{
					name:'product_id',
					width:'50px',
					sortable:true,
					caption: 'ID',
					styles:{'min-width':'50px'},
					dataStyle:{'text-align':'center'},
					dataSource:'product_id',
					dataType: 'int'
				},
				{
					name:'article',
					width:'130px',
					sortable:true,
					caption: 'Артикул',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'left'},
					dataSource:'article'
				},
				{
					name:'preview',
					width:'18px',
					caption: '-',
					styles:{'min-width':'18px'},
					dataStyle:{'text-align':'center'},
					dataSource:'pic_big',
					dataFunction:function(table, cell, text, data){
						if(!text||text==''){
							new Element('img',{
								'src':INTERFACE_IMAGES+'/preview_none.png',
							}).inject(cell).setStyles({
								'cursor':'default',
								'float':'left'
							});
						}else{
							new Element('img',{
								'src':INTERFACE_IMAGES+'/preview_active.png',
							}).inject(cell).setStyles({
								'cursor':'pointer',
								'float':'left'
							}).addEvents({
								'click': function(e){
									jsSlimbox.open(text, 0, {});
									e.stop();
								}
							});
						}
						return '';
					}
				},
				{
					name:'name',
					width:'auto',
					caption: 'Наименование',
					sortable:true,
					styles:{'min-width':'160px'},
					dataStyle:{'text-align':'left'},
					dataSource:'name'
				},
				{
					name:'offer',
					width:'120px',
					sortable:false,
					visible: true,
					caption: 'Спецпредложение',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'offer',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						if(parseInt(text)==0) return '<font color="red">Нет</font>';
						return '<font color="green">Да</font>';
					}
				},
				{
					name:'offer_discount',
					width:'120px',
					sortable:false,
					visible: true,
					caption: 'Процент скидки',
					styles:{'min-width':'60px'},
					dataStyle:{'text-align':'center'},
					dataSource:'offer_discount',
					dataType:'html',
					dataFunction:function(table, cell, text, data){
						return text+'%';
					}
				},
				]
			});
		}

		this.objects['table_offers'].setData(data);
	},




	/*******************************************************************
	 * Функции RunTime
	 ******************************************************************/

	empty: null
}
/*////////////////////////////////////////////////////////////////////*/
})();