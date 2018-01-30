/*----------------------------------------------------------------------
Класс отвечающий за работу меню
----------------------------------------------------------------------*/

var jsMainMenu = new Class({

	Implements: [Options, Events],
	
	//==================================================================
	//Переменные класса
	
	//Родительский элемент, в котором будет строиться меню
	parent: null,
	
	//Объект меню
	instance: null,
	
	//Последний активный раздел
	activeSection: null,
	
	//Объекты
	menuitems: [],
	
	//Опции класса
	options:{
		'menu':[],
		'build':false
	},
	
	//Элемент меню по-умолчанию
	defaultItem:{
		'id'		: null,		//Идентификатор пункта меню	
		'name'		: '',		//Отображаемое название
		'link'		: '',		//Ссылка на страницу
		'class'		: '',		//Класс
		'section'	: null		//Секция, в которой должен быть данный пункт меню
	},
	
	
	//==================================================================
	//Функции


	//Инициализация
	initialize: function(parent, options){

		this.setOptions(options);
		this.parent = ($(parent)) ? $(parent) : null;
		if(this.options.build) this.build(this.options.menu)
		
		App.Location.addEvent('afterLoadPage',this.updateActive.bind(this));

		
	},//end function
	
	
	//Построение меню
	build: function(menu){

		if(!$(this.parent)) return false;
		if(typeOf(menu)!='array' || !menu.length) return false;
		
		var sections = {};
		var items = {};
		
		if($(this.instance)){
			this.instance.empty();
			this.activeSection = null;
			this.menuitems.empty();
		}else{
			this.instance = new Element('ul',{
				'class': 'navigation'
			}).inject(this.parent);
		}

		
		//Просмотр меню, формирование секций и их элементов
		for(var indx=0; indx<menu.length;indx++){
			
			menu[indx] = $merge(this.defaultItem, menu[indx]);
			menu[indx]['id'] = parseInt(menu[indx]['id']);
			menu[indx]['section'] = parseInt(menu[indx]['section']);
			if(menu[indx]['id']==0) continue;
			if(menu[indx]['section']==0){
				sections[menu[indx]['id']] = menu[indx];
			}else{
				if(typeOf(items[menu[indx]['section']])!='array') items[menu[indx]['section']] = [];
				items[menu[indx]['section']].push(menu[indx]);
			}

		}//Просмотр меню
		
		var ul, dsection, ditem, collapsible;
		
		//Построение секций
		for(var id in sections){
			
			dsection = this.buildItem(this.instance, sections[id]);
			ul = new Element('ul').inject(dsection['li']);

			if(dsection['active']){
				dsection['li'].addClass('activepage');
				this.activeSection = dsection['li'];
			}

			this.menuitems.push({
				'sli': dsection['li'],
				'li': dsection['li'],
				'link': sections[id]['link'],
				'id': id
			});

			if(typeOf(items[id])!='array') continue;
			
			for(var indx=0;indx<items[id].length;indx++){
				ditem = this.buildItem(ul, items[id][indx]);
				if(ditem['active']){
					dsection['li'].addClass('activepage');
					this.activeSection = dsection['li'];
				}
				this.menuitems.push({
					'sli': dsection['li'],
					'li': ditem['li'],
					'link': items[id][indx]['link'],
					'id': items[id][indx]['id']
				});
			}
			
		}//Построение секций
		
		
	},//end function
	
	
	
	//Построение элемента меню
	buildItem: function(parent, item){

		var li,a;

		if(!item['liclass']) item['liclass'] = 'w150';

		li = new Element('li',{
			'class': item['liclass']
		}).inject(parent);

		a  = new Element('a',{
			'href': (!item['link'] ? '#' : item['link']),
			'text': item['name'],
			'class': (!item['class'] ? 'empty': item['class'])
		}).inject(li);
			
		return {
			'li':li,
			'a':a,
			'active': (App.Location.lastRequestedPage == item['link'] ? true : false)
		};
	},//end function
	
	
	
	//Обновление активной секции
	updateActive: function(href){
		if($(this.activeSection)) $(this.activeSection).removeClass('activepage');
		if(typeOf(this.menuitems)!='array') return;
		for(var i=0; i<this.menuitems.length; i++){
			if(this.menuitems[i]['link'] == href){
				if($(this.menuitems[i]['sli'])){
					$(this.menuitems[i]['sli']).addClass('activepage');
					this.activeSection = $(this.menuitems[i]['sli']);
				}
				return;
			}
			
		}
	}
	
	
});
