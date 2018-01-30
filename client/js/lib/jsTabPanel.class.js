/*----------------------------------------------------------------------
Панель вкладок
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/
var jsTabPanel = new Class({

	Implements : [ Events, Options ],

	options : {
		tabSelector : '.tab',				//Стиль вкладки
		contentSelector : '.tab_content',	//Стиль DIV с контентом вкладки
		activeClass : 'active',				//Стиль активной вкладки
		prefixTab : 'tab_',					//
		prefixPanel : 'panel_',				//
		index : 0,							//
		tabWithFocus : null,
		panelWithFocus : null,
		imageWithFocus : null,
		tabGroup : '.tabs',
		onchange: null						//Функция, вызываемая при смене вкладки
		},

	parent : null,
	showNow : false,
	tabs : null,
	contents : null,
	index: 0,


	/*Инициализация*/
	initialize : function(parent, options) {
		this.setOptions(options);

		this.parent = parent;
		if (typeOf(this.parent) == 'string') this.parent = $(this.parent);
		if (typeOf(this.parent) != 'element') return this;

		// Сохранить все существующие вкладки в 'this.tabs':
		this.tabs = this.parent.getChildren(this.options.tabGroup)[0].getChildren(this.options.tabSelector);

		//Сохранить весь существующий контент в 'this.contents':
		this.contents = this.parent.getChildren(this.options.contentSelector);

		//Пометить первую вкладку, как активную:
		this.tabs[0].addClass(this.options.activeClass);

		//Скрыть весь доспупный контент
		this.contents.setStyle('display', 'none');

		// отобразить контент активной (первой) вкладки
		this.contents[0].setStyle('display', 'block');

		//Добавить событие CLICK для обработки события нажатия на вкладку
		this.parent.addEvent('click:relay('+ this.options.tabSelector + ')', function(event,tab){
			var index = this.tabs.indexOf(tab);
			if(this.options.onchange) this.options.onchange(index);
			this.showTab(index, tab);
		}.bind(this));

		//Обновление индекса активной вкладки
		this.updateTabIndex();
		this.parent.getChildren('ul').setProperty('role','tablist');

		//Создание вкладок
		this.tabs.each(function(element, index){
			if (element.getChildren()[0] != null) {
				element.addClass('delete');
				clone = element.getChildren()[0].clone(true, true).inject(element, 'after');
				element.getChildren()[0].destroy();
			}

			//Добавление обработчиков событий
			element.addEvent('focus', function(event){
				this.options.tabWithFocus = element;
				this.options.panelWithFocus = null;
				this.updateTabIndex();
			}.bind(this));
			element.addEvent('blur', function(){
				this.options.tabWithFocus = null;
			}.bind(this));
			element.setProperty('role', 'tab');
			element.setProperty('id', this.options.prefixTab + this.options.index);

			var content = this.contents[index];
			content.setProperty('id', this.options.prefixPanel + this.options.index);
			content.addEvent('focus', function(){
				this.options.panelWithFocus = content;
			}.bind(this));
			content.addEvent('blur', function(){
			}.bind(this));
			content.setProperty('role', 'tabpanel');
			content.setProperty('aria-labelledby', this.options.prefixTab + this.options.index);

			if (index == 0) {
				content.setProperty('aria-hidden', 'false');
				element.setProperty('aria-selected', 'true');
			}
			else {
				content.setProperty('aria-hidden', 'true');
				element.setProperty('aria-selected', 'false');
			}

			this.options.index++;

		}.bind(this));//Создание вкладок

	},//end function


	//Показать вкладку
	showTab : function(index, tab) {
		var content = this.contents[index];
		if (!tab) tab = this.tabs[index];
		if (content) {
			this.contents.each(function(el) {el.setStyle('display', 'none');});
			this.tabs.each(function(el) {
				el.removeClass(this.options.activeClass);
			}.bind(this));
			tab.addClass(this.options.activeClass);
			content.setStyle('display', 'block');
			this.fireEvent('change', index);
		}
		this.updateTabIndex();
	},//end function



	//Обновление индекса вкладок
	updateTabIndex : function() {
		this.tabs.each(function(element, index) {
			//Если текущая вкладка актина
			if (element.hasClass(this.options.activeClass)) {
				this.index = index;
				element.setProperty('tabindex', 0);
				this.contents[index].setProperty('tabindex', 0);
				this.contents[index].setProperty('aria-hidden','false');
				element.setProperty('aria-selected', 'true');
			}
			//Текущая вкладка не актина
			else{
				element.setProperty('tabindex', -1);
				this.contents[index].setProperty('tabindex', -1);
				this.contents[index].setProperty('aria-hidden','true');
				element.setProperty('aria-selected', 'false');
			}

		}.bind(this));

	},//end function



	// Установка аттрибутов для активной вкладки
	activateTab : function(tab, content) {
		if(tab.getProperty('tabindex') == 0) return;
		setTimeout(function() {tab.focus();}, 0);
		tab.setProperty('tabindex', 0);
		content.setProperty('tabindex', 0);
		tab.addClass(this.options.activeClass);
		content.setStyle('display', 'block');
		content.setProperty('aria-hidden', 'false');
		tab.setProperty('aria-selected', 'true');
	},//end function



	//Установка аттрибутов для неактивной вкладки
	inactivateTab : function(tab, content) {

		if(!tab || tab.getProperty('tabindex') == -1) return;
		tab.setProperty('tabindex', -1);
		tab.removeClass(this.options.activeClass);
		content.setStyle('display', 'none');
		content.setProperty('aria-hidden', 'true');
		tab.setProperty('aria-selected', 'false');
		content.setProperty('tabindex', -1);

	}//end function


});//end class
