/*----------------------------------------------------------------------
Вспомогательные функции и расширения объектов
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/


//==================================================================
//Расширения Browser


//Проверка браузера на соответствие. Формат: chrome|firefox|ie|opera|safari [lt|lte|gt|gte [version]], ..., ...
Browser.inCondition = function(condition){
	if (typeOf(condition) != 'string')
		return true;
	if (!(condition = condition.trim()))
		return true;
	
	var condEntries = condition.split(/,\s*/);
	if (!condEntries.length)
		return true;
	
	for (
		var i = 0,
			oses = [],
			condEntry,
			mch,
			mchOp,
			mchVersion;
		i < condEntries.length; i++
	) {
		condEntry = condEntries[i].toLowerCase();
		if (!(mch = condEntry.match(/^([a-zA-Z]+)\s?([egltEGLT]{2,3})?\s?([\d.]+)?$/)))
			continue;
		
		if (mch[1] == this.name) {
			
			if (mch[2] && mch[3]) {
				mchOp = mch[2].toLowerCase(),
				mchVersion = mch[3].toFloat();
				
				if (
					(mchOp == 'lt' && this.version < mchVersion) ||
					(mchOp == 'lte' && this.version <= mchVersion) ||
					(mchOp == 'gt' && this.version > mchVersion) ||
					(mchOp == 'gte' && this.version >= mchVersion)
				)
					return true;
			}
			else if ((!mch[2] && !mch[3]) || (!mch[2] && this.version.toInt() == mch[3].toInt()))
				return true;
		}
	}

	return false;
};


//Проверка ОС на соответствие. Формат: win|mac|linux|ios|android|webos, ..., ...
Browser.Platform.inCondition = function(condition) {
	if (typeOf(condition) != 'string')
		return true;
	if (!(condition = condition.trim()))
		return true;
	return condition.split(/,\s*/).contains(this.name);
};



//==================================================================
//Расширения Function

Function.implement({
	/* Остановка события */
	stopEvent: function(event){
		if (typeOf(event) == 'domevent')
			return !event.stop();
		return false;
	}
});



function getMonday(d){
	d = new Date(d);
	var day = d.getDay(),
		diff = d.getDate() - day + (day == 0 ? -6:1); // adjust when day is sunday
	return new Date(d.setDate(diff));
}




//==================================================================
//Построение чеклиста
//Добавление опций в SELECT из массива options
function checklist_add(data){

	if(typeOf(data)!='object') return false;
	if(typeOf(data['list'])=='array'){
		var list = data['list'].clone();
		for(var i=0;i<list.length;i++){
			data['list'] = list[i];
			checklist_add(data);
		}
		return;
	}

	var params = Object.merge({
		'list'		: null,
		'parent'	: null,
		'options'	: null,
		'key'		: 0,
		'checked'	: 1,
		'text'		: 2,
		'prefix'	: 'chlist_',
		'clear'		: false
	},data);
	params['list'] = $(params['list']);
	params['parent'] = $(params['parent']);
	if(!params['list'] && !params['parent']) return false;
	if(!params['list'] && params['parent']) params['list'] = new Element('ul').inject(params['parent']);
	params['selected'] = false;
	if(params['clear']) params['list'].empty();

	var funct = function(item, key){
		var setter = (this.iterator ? this.iterator(item) : {'key':item[this.key],'value':item[this.value]});
		if(typeOf(setter)!='object') return;
		if(this.white && !this.white.contains(setter['value'])) return;
		if(this.black && this.black.contains(setter['value'])) return;
		var option = new Element('option',{
			'value': setter['key'],
			'text': setter['value']
		}).inject(this.list);
		if(String(setter['key']) == String(params['default'])){
			option.selected = true;
			option.defaultSelected = true;
			this.selected = true;
		}
	};

	switch(typeOf(params['options'])){

		case 'array':
			Array.each(params['options'], funct.bind(params));
		break;

		case 'object':
			Object.each(params['options'], funct.bind(params));
		break
	}

	if(!params['selected']) params['list'].selectedIndex = 0;

	return params['list'];
};






//==================================================================
//Работа со списками SELECT

//Сортировка списка SELECT
function select_sort(list){
	list = $(list);
	$A(list.options).sort(function(a,b){ return (a.text.toLowerCase() < b.text.toLowerCase() ) ? -1 : 1; }).each(function(o,i){ list.options[i] = o;})
};


//Копирование выделенных / не выделенных элементов из списка source в список dectination
function select_copy_selected(source, dectination, selected){
	selected = selected || false;
	source = $(source);
	dectination = $(dectination);
	//dectination.empty();
	source.getElements('option').each(function(option){
		if(option.selected == selected){
			new Element('option',{
				'value': option.value,
				'text': option.text
			}).inject(dectination);
		}
	});
};



//Удаление выделенных / не выделенных элементов из списка
function select_delete_selected(list, selected){
	selected = selected || false;
	list = $(list);
	list.getElements('option').each(function(option){
		if(option.selected == selected) option.destroy();
	});
};




//Фильтрация массива SELECT
function select_filter(list, term, defaultValue){
	defaultValue = defaultValue || '';
	term = String(term).trim();
	list = $(list);
	var options = list.retrieve('options');
	if(typeOf(options)!='array'||!options.length){
		options = [];
		list.getElements('option').each(function(option){
			options.push({
				'value': option.value,
				'text': option.text
			});
		});
		list.store('options',options);
	}
	list.empty();
	for(var i=0;i<options.length;i++){
		if(term == '' || String(options[i]['text']).containscase(term)){
			var selected = (String(options[i]['value']) == String(defaultValue) ? true : false);
			new Element('option',{
				'value': options[i]['value'],
				'text': options[i]['text'],
				'defaultSelected': selected,
				'selected': selected
			}).inject(list);
		}
	}
	return list;
};



//Добавление опций в SELECT из массива options
function select_add(data){

	if(typeOf(data)!='object') return false;
	if(typeOf(data['list'])=='array'){
		var list = data['list'].clone();
		for(var i=0;i<list.length;i++){
			data['list'] = list[i];
			select_add(data);
		}
		return;
	}

	var params = Object.merge({
		'list'		: null,
		'parent'	: null,
		'options'	: null,
		'default'	: 0,
		'white'		: null,
		'black'		: null,
		'key'		: 0,
		'value'		: 1,
		'iterator'	: null,
		'clear'		: false,
		'filter'	: '',
		'selectoptions': [],
		'autoselect': true
	},data);
	params['list'] = $(params['list']);
	params['parent'] = $(params['parent']);
	if(!params['list'] && !params['parent']) return false;
	if(!params['list'] && params['parent']) params['list'] = new Element('select').inject(params['parent']);
	params['selected'] = false;
	if(params['clear']) params['list'].empty();
	params['filter'] = String(params['filter']).trim();
	if(typeOf(params['default'])!='array') params['default'] = [String(params['default'])];

	var funct = (params['filter'] == ''
	? function(item, key){
		var setter = (this.iterator ? this.iterator(item) : {'key':item[this.key],'value':item[this.value]});
		if(typeOf(setter)!='object') return;
		if(this.white && !this.white.contains(setter['key'])) return;
		if(this.black && this.black.contains(setter['key'])) return;
		var option = new Element('option',{
			'value': setter['key'],
			'text': setter['value']
		}).inject(this.list);
		if(params['default'].contains(String(setter['key']))){
			option.selected = true;
			option.defaultSelected = true;
			this.selected = true;
		}
	}
	: function(item, key){
		var setter = (this.iterator ? this.iterator(item) : {'key':item[this.key],'value':item[this.value]});
		if(typeOf(setter)!='object') return;
		if(this.white && !this.white.contains(setter['key'])) return;
		if(this.black && this.black.contains(setter['key'])) return;
		this['selectoptions'].push({'value':setter['key'],'text': setter['value']});
	});

	switch(typeOf(params['options'])){

		case 'array':
			Array.each(params['options'], funct.bind(params));
		break;

		case 'object':
			Object.each(params['options'], funct.bind(params));
		break
	}

	params['list'].store('options', params['selectoptions']);

	if(params['filter'] != '') return select_filter(params['list'], params['filter'], params['default']);
	if(!params['selected'] && params['autoselect']) params['list'].selectedIndex = 0;

	return params['list'];
};



/*Функция выбора опции в списке SELECT*/
function select_set(list, value){
	list = $(list);
	if(!list) return -1;
	for(var i=0; i<list.options.length; ++i){
		if(String(list.options[i].value) == String(value)){
			list.options[i].selected = true;
			list.selectedIndex = i;
			return i;
		}
	}
	return -1;
};



/*Функция возвращает массив выбранных опций*/
function select_getValueArray(list, all){
	list = $(list);
	var result = [];
	all = all || false;
	if(!list) return result;
	if(!list.options.length) return result;
	list.getElements('option').each(function(option){
		if(option.selected == true || all == true){
			result.push(option.value);
		}
	});
	return result;
};

/*Функция возвращает массив объектов выбранных опций*/
function select_getValueObject(list, all){
	list = $(list);
	var result = [];
	all = all || false;
	if(!list) return result;
	if(!list.options.length) return result;
	list.getElements('option').each(function(option){
		if(option.selected == true || all == true){
			result.push({'key':option.value, 'value':option.text});
		}
	});
	return result;
};


/*Функция возвращает значение выбранной опции*/
function select_getValue(list){
	list = $(list);
	if(!list) return false;
	if(!list.options.length) return null;
	if(list.selectedIndex==-1) return null;
	return list.options[list.selectedIndex].value;
};



/*Функция возвращает значение выбранной опции*/
function select_getText(list){
	list = $(list);
	if(!list) return false;
	if(!list.options.length) return null;
	if(list.selectedIndex==-1) return null;
	return list.options[list.selectedIndex].text;
};


//==================================================================
//Расширение для элементов


Element.implement({

	disable: function(){
		this.set('disabled',true);
		return this.addClass('disabled');
	},

	enable: function(){
		this.set('disabled',false);
		return this.removeClass('disabled');
	},
	
	/*Возвращает первый родительский элемент, содержащий CSS class*/
	getParentByClass: function(className){
		if(this.hasClass(className))return this;
		var parent = this.getParent();
		while(parent != document.body){
			if(parent.hasClass(className))return parent;
			parent = parent.getParent();
		}
		return null;
	},

	/*Возвращает первый родительский элемент с определенным именем тега*/
	getParentByTag: function(tagName){
		if(this.tagName==tagName)return this;
		var parent = this.getParent();
		while(parent != document.body){
			if(parent.tagName ==tagName)return parent;
			parent = parent.getParent();
		}
		return null;
	},


	/*Получение значения*/
	getValue: function(){
		//ELEMENT TAG
		switch(this.get('tag')){

			//Список
			case 'select':
				return select_getValue(this);
			break;

			//Поле пормы
			case 'input':
			case 'textarea':
				switch(this.get('type')){
					case 'checkbox':
						return (this.checked ? 1 : 0);
					break;
					default:
						return this.get('value');
				}
			break;

			//Другое
			default:
				return this.get('text');
		}//ELEMENT TAG
		
	},


	/*Запись значения*/
	setValue: function(value){
		//ELEMENT TAG
		switch(this.get('tag')){

			//Список
			case 'select':
				select_set(this, value);
			break;

			//Поле пормы
			case 'input':
			case 'textarea':
				switch(this.get('type')){
					case 'checkbox':
						value = String.from(value).toLowerCase();
						this.checked = (value=='1'||value=='true'||value=='on');
					break;
					default:
						this.set('value', value);
				}
			break;

			//Другое
			default:
				this.set('html',value);
		}//ELEMENT TAG

		return this;
	},



	/*
	To: The color you want the element to flash to next.
	From: The color you want the element to flash to first.
	Reps: Number of times to repeat the flash.
	Property: Property to flash. Background color works best.
	Duration: The duration of the color change
	*/
	flash: function(to,from,reps,prop,dur) {
		if(!from) { reps = '#FFFFFF'; }
		if(!reps) { reps = 4; }
		if(!prop) { prop = 'background-color'; }
		if(!dur) { dur = 500; }
		var effect = new Fx.Tween(this, {
				duration: dur,
				link: 'chain'
			})
		for(x = 1; x <= reps; x++){
			effect.start(prop,from,to).start(prop,to,from);
		}
	}

});


(function(){
	var behaviors = {};
	var define = Element.defineBehavior = function(behavior, fn){
		behaviors[behavior] = fn;
		return this;
	};
	Element.defineBehaviors = define.overloadSetter();
	var lookup = Element.lookupBehavior = function(behavior){
		return behaviors[behavior];
	}
	Element.lookupBehaviors = lookup.overloadGetter();

	Element.startBehaviors = function(attribute){
		if (!attribute) attribute = 'data-filter';
		$$('[' + attribute + ']').each(function(element){
			element.get(attribute).split(/ +|\t+|\n+/).each(function(raw){
				var split = raw.split(':'),
					filter = split[0],
					options = JSON.decode((element.get('data-' + (split[1] || filter))) || '{}');
				if (raw == '' || element.retrieve('behavior-' + raw)) return;
				var behavior = behaviors[filter];
				if (!behavior) throw new Error('Фильтр `' + filter + '` не определен');
				element.store('behavior-' + raw, behavior.call(element, options) || true);
			});
		});
	};

	Element.stopBehaviors = function(attribute){
		if (!attribute) attribute = 'data-filter';
		$$('[' + attribute + ']').each(function(element){
			element.get(attribute).split(/ +|\t+|\n+/).each(function(raw){
				var split = raw.split(':'),
					filter = split[0],
					options = JSON.decode((element.get('data-' + (split[1] || filter))) || '{}');
				if (raw == '') return;
				var behavior = behaviors[filter];
				if (!behavior) throw new Error('Filter `' + filter + '` is undefined');
				var data = element.retrieve('behavior-' + raw);
				if(typeOf(data)=='object' && typeOf(data['stop'])=='function') data['stop']();
				element.store('behavior-' + raw, null);
			});
		});
	};

})();


Element.defineBehaviors({

	pulse: function(options){
		var periodical,
			tween = new Fx.Tween(this, {
				property: options.property,
				link: 'chain',
				duration: options.duration / 2
			});

		function pulse(){ tween.start(options.from).start(options.to) }
		function start(){ pulse(); periodical = pulse.periodical(options.duration) }
		function stop(){ tween.cancel(); clearInterval(periodical) }

		start();
		return { tween: tween, start: start, stop: stop };
	},

	gray: function(){
		this.setStyle('background', '#cccccc');
	}

});




//==================================================================
//Расширение для массивов

Array.implement({

	//Возвращает значения из столбца resultColumn в виде линейного массива
	fromField: function(resultColumn, distinct){
		var result = [];
		if(!resultColumn) resultColumn = 0;
		for(var i=0; i<this.length; ++i){
			if(!distinct || (distinct && !result.contains(this[i][resultColumn]))){
				result.push(this[i][resultColumn]);
			}
		}
		return result;
	},


	//Возвращает значение из столбца resultColumn при первом совпадении term и termColumn
	//Читается как SELECT [resultColumn] FROM ARRAY WHERE [termColumn] = [term] LIMIT 1
	filterResult: function(resultColumn, termColumn, term){
		if(!termColumn) termColumn=0;
		if(!resultColumn) resultColumn = 1;
		for(var i=0; i<this.length; ++i){
			if(String(this[i][termColumn]) == String(term)) return this[i][resultColumn];
		}
		return false;
	},


	//Записывает значение value в setColumn при совпадении term и termColumn для limit записей
	//Возвращает количество измененных записей
	//Читается как UPDATE ARRAY SET [setColumn] = [value] WHERE [termColumn] = [term] LIMIT [limit]
	filterUpdate: function(setColumn, value, termColumn, term, limit){
		var count = 0;
		if(!limit) limit=1;
		if(!termColumn) termColumn=0;
		if(!setColumn) setColumn = 1;
		for(var i=0; i<this.length; ++i){
			if(String(this[i][termColumn]) == String(term)){
				count++;
				this[i][setColumn] = value;
				if(limit && count >= limit) return count;
			}
		}
		return count;
	},


	//Возвращает строки при совпадении term и termColumn
	//Читается как 
	//SELECT * FROM ARRAY WHERE [termColumn] = [term] LIMIT [limit]
	//SELECT * FROM ARRAY WHERE ([termColumn1] = [term1] AND [termColumn2] = [term2] AND ...) LIMIT [limit]
	filterSelect: function(){
		if(!arguments.length) return false;
		var is_object = (typeOf(arguments[0])=='object');
		var conditions = is_object ? arguments[0] : {};
		if(!is_object) conditions[arguments[0]] = arguments[1];
		var limit_index = is_object ? 1 : 2;
		var limit = (!arguments[limit_index]) ? 0 : parseInt(arguments[limit_index]);
		var count = 0;
		var result = [];
		var allowed = false;
		var i, field, term, value, condition;

		//Просмотр условий, подготовка функций
		for(field in conditions){
			if(typeOf(conditions[field])!='object'){
				term = conditions[field];
				conditions[field] = {};
				conditions[field]['value'] = term;
				conditions[field]['condition'] = '=';
			}
			if(!conditions[field]['condition']) conditions[field]['condition'] = '=';
			switch(conditions[field]['condition']){
				case '!=':
				case '<>':
					conditions[field]['funct'] = function(value, term){return (String(value) != String(term))};
				break;
				case 'LIKE':
					conditions[field]['funct'] = function(value, term){return (String(value).contains(String(term)))};
				break;
				case 'NOTLIKE':
					conditions[field]['funct'] = function(value, term){return (!String(value).contains(String(term)))};
				break;
				case 'IN':
					conditions[field]['funct'] = function(value, term){return (term.indexOf(String(value))>-1)};
				break;
				case 'NOTIN':
					conditions[field]['funct'] = function(value, term){return (term.indexOf(String(value))==-1)};
				break;
				case '>':
					conditions[field]['funct'] = function(value, term){return (value >= term)};
				break;
				case '<':
					conditions[field]['funct'] = function(value, term){return (value <= term)};
				break;
				case '=':
				default: 
					conditions[field]['funct'] = function(value, term){return (String(value) == String(term))};
			}
		}

		for(i=0; i<this.length; ++i){
			allowed = true;
			for(field in conditions){
				value = this[i][field];
				allowed = conditions[field]['funct'](value, conditions[field]['value']);
				if(!allowed) break;
			}
			if(allowed){
				count++;
				result.push(this[i]);
				if(limit && count >= limit) return result;
			}
		}

		return result;
	},


	//Выбирает одну строку из SELECT
	filterRow: function(){
		if(!arguments.length) return false;
		if(typeOf(arguments[0])=='object'){
			arguments[1] = 1;
		}else{
			arguments[2] = 1;
		}
		var result = this.filterSelect.apply(this, arguments);
		if(typeOf(result)!='array'||!result.length) return false;
		return result[0];
	},



	//Удаляет строки при совпадении term и termColumn
	//Возвращает количество удаленных записей
	//Читается как DELETE FROM ARRAY WHERE [termColumn] = [term] LIMIT [limit]
	filterDelete: function(termColumn, term, limit){
		var count = 0;
		if(!limit) limit=0;
		if(!termColumn) termColumn=0;
		for(var i=0; i<this.length; ++i){
			if(String(this[i][termColumn]) == String(term)){
				count++;
				this.erase(this[i]);
				i = (i==0) ? 0 : i-1;
				if(limit && count >= limit) return count;
			}
		}
		return count;
	}

});



//==================================================================
//Расширение для Объектов

String.implement({
	fromQueryString: function(query){ 
		query = query || this;
		var parameters = {};
		var params = query.split('&');
		params.each(function(param){ 
			param = param.split('=');
			parameters[param[0]] = param[1];
		}); 
		return parameters; 
	},

	containscase: function(string){
		return String(this).toLowerCase().indexOf(String(string).toLowerCase()) > -1;
	}
});



//==================================================================
//Функция добавляет символы из pad_string в строку input пока не будет достигнута длинна pad_length*/
function strPad(input, pad_length, pad_string, pad_type){
	var half = '', pad_to_go;
	var str_pad_repeater = function(s, len){
			var collect = '', i;
			while(collect.length < len) collect += s;
			collect = collect.substr(0,len);
			return collect;
		};
	if (pad_type != 'STR_PAD_LEFT' && pad_type != 'STR_PAD_RIGHT' && pad_type != 'STR_PAD_BOTH') { pad_type = 'STR_PAD_LEFT'; }
	if ((pad_to_go = pad_length - input.length) > 0) {
		if (pad_type == 'STR_PAD_LEFT') { input = str_pad_repeater(pad_string, pad_to_go) + input; }
		else if (pad_type == 'STR_PAD_RIGHT') { input = input + str_pad_repeater(pad_string, pad_to_go); }
		else if (pad_type == 'STR_PAD_BOTH') {
			half = str_pad_repeater(pad_string, Math.ceil(pad_to_go/2));
			input = half + input + half;
			input = input.substr(0, pad_length);
		}
	}
	return input;
};




//==================================================================
//Построение панели

function build_blockitem(data){

	if(typeOf(data)!='object') return false;
	var params = Object.merge({
		'list'		: null,
		'parent'	: null,
		'li_class'	: 'dark',
		'title'		: '',
		
	},data);

	params['list'] = $(params['list']);
	params['parent'] = $(params['parent']);
	if(!params['list'] && !params['parent']) return false;
	if(!params['list'] && params['parent']) params['list'] = new Element('ul',{'class':'blocklist'}).inject(params['parent']);

	var li = new Element('li',{'class':params['li_class']}).inject(params['list']);
	var heading = new Element('h3',{'class':'opened'}).inject(li);
	var heading_collapser = new Element('a',{'class':'collapser'}).inject(heading);
	var heading_toolbar = new Element('div',{'class':'toolbar'}).inject(heading);
	var heading_title = new Element('span').inject(heading).set('html',params['title']);

	var div = new Element('div',{'class':'collapse'}).inject(li);
	var container = new Element('div',{'class':'collapse-container'}).inject(div);

	var collapsible = new Fx.Slide(div, {
		duration: 100, 
		transition: Fx.Transitions.linear,
		onComplete: function(obj){ 
			var open = obj.getStyle('margin-top').toInt();
			if(open >= 0) new Fx.Scroll(window).toElement(heading);
			if(open) heading.addClass('closed').removeClass('opened');
			else heading.addClass('opened').removeClass('closed');
			obj.setStyle('height','');
			if(!open) obj.getParent().setStyle('height','');
		}
	});
	heading.onclick = function(){
		collapsible.toggle();
		return false;
	}

	return {
		'list'		: params['list'],
		'li'		: li,
		'toolbar'	: heading_toolbar,
		'title'		: heading_title,
		'container'	: container
	};
};



//==================================================================
//Построение комментария

function build_commentitem(data){

	if(typeOf(data)!='object') return false;
	var params = Object.merge({
		'list'		: null,
		'parent'	: null,
		'author'	: '',
		'timestamp'	: '',
		'message'	: '',
		'bg_color'	: null
	},data);

	params['list'] = $(params['list']);
	params['parent'] = $(params['parent']);
	if(!params['list'] && !params['parent']) return false;
	if(!params['list'] && params['parent']) params['list'] = new Element('ul',{'class':'commentlist'}).inject(params['parent']);

	var li = new Element('li').inject(params['list']);
	var title = new Element('div',{'class':'title'}).inject(li);
	var timestamp = new Element('span',{'class':'timestamp'}).inject(title).set('html',params['timestamp']);
	var author = new Element('span',{'class':'author'}).inject(title).set('html',params['author']);
	var message = new Element('div',{'class':'message'}).inject(li).set('html',params['message']);

	if(params['bg_color']) li.setStyle('background-color', params['bg_color']);

	return {
		'list'		: params['list'],
		'li'		: li,
		'author'	: author,
		'timestamp'	: timestamp,
		'message'	: message
	};
};



//==================================================================
//Построение указателя наличия скроллинга

function build_scrolldown(element, minforshow){
	element = $(element);
	minforshow = (!minforshow ? 50 : parseInt(minforshow));
	var height = element.getHeight();
	var scrollheight = element.getScrollHeight();
	if(scrollheight - height < minforshow) return;
	var target = new Element('div',{
		'class': 'scroll_down_element',
		'styles':{
			bottom: element.getHeight() - 10
		}
	});
	target.inject(element).set('html','<br>Смотри ниже');

	new Fx.Morph(target, {
		link: 'chain',
		duration: 2000,
		transition: Fx.Transitions.Bounce.easeOut,
		onComplete: function(){
			new Fx.Morph(this, {
				duration: 3000,
				transition: Fx.Transitions.linear,
				onComplete: function(){this.destroy();}.bind(this)
			}).start({
				opacity: [0]
			});
		}.bind(target)
	}).start({
		bottom: [10]
	});
};


//==================================================================
//Задает разделитель splitter
function set_splitter_h(data){

	if(typeOf(data)!='object') return false;
	var params = Object.merge({
		'left'			: null,
		'right'			: null,
		'splitter'		: null,
		'handle'		: null,
		'min'			: 200,
		'max'			: 900,
		'parent'		: null,
		'offset_left'	: 3,
		'offset_right'	: 1
	},data);
	if(!params['handle']) params['handle'] = params['splitter'];
	return $(params['handle']).makeResizable({
		handle: $(params['splitter']),
		modifiers: {x: 'left', y: false},
		limit: {x: [params['min'], params['max']]},
		invert: false,
		onStart: function(){},
		onComplete: function(){
			var splitter = $(this['splitter']).getCoordinates(this['parent']);
			var twidth = $(this['left']).getCoordinates(this['parent']);
			var new_width = splitter.left - twidth.left - this['offset_left'];
			var new_left  = splitter.right + this['offset_right'];
			$(this['left']).setStyle('width',new_width+'px');
			$(this['right']).setStyle('left',new_left+'px');
		}.bind(params)
	});

};



//==================================================================
//Построение списка Checkbox из массива options
function buildChecklist(data){

	if(typeOf(data)!='object') return false;
	if(typeOf(data['list'])=='array'){
		var list = data['list'].clone();
		for(var i=0;i<list.length;i++){
			data['list'] = list[i];
			select_add(data);
		}
		return;
	}

	var params = Object.merge({
		'list'		: null,
		'name'		: '',
		'parent'	: null,
		'options'	: null,
		'white'		: null,
		'black'		: null,
		'key'		: 0,
		'value'		: 1,
		'iterator'	: null,
		'clear'		: false,
		'selected'	: [],
		'events'	: {},
		'onclick'	: null,
		'properties': {},
		'sections'	: false
	},data);
	params['list'] = $(params['list']);
	params['parent'] = $(params['parent']);
	if(!params['list'] && !params['parent']) return false;
	if(!params['list'] && params['parent']){
		if(params['clear']) params['parent'].empty();
		params['list'] = new Element('ul').addClass('checklist').inject(params['parent']);
	}else{
		if(params['clear']) params['list'].empty();
	}
	var checkbox_click = params['onclick'];

	var funct = function(item, key){
		var tof = typeOf(item);
		var is_section = (tof!='array'&&tof!='object');
		if(this.sections && is_section){
			return new Element('li').inject(this.list).addClass('section').set('text', item);
		}
		var setter = (this.iterator ? this.iterator(item) : {'key':item[this.key],'value':(item[this.value]==''?'...':item[this.value])});
		if(typeOf(setter)!='object') return;
		if(this.white && !this.white.contains(setter['value'])) return;
		if(this.black && this.black.contains(setter['value'])) return;
		var li = new Element('li').inject(this.list);
		if(key%2==0) li.addClass('alt');
		var label = new Element('div').inject(li).setStyles({
			'float':'left',
			'width':'30px'
		});
		var checkbox = new Element('input',{
			'type': 'checkbox',
			'value': setter['key'],
			'name': this.name,
			'title': setter['value'],
			'id': name
		}).inject(label).addEvents(this.events).setProperties(this.properties);
		checkbox.addEvent('click',function(e){
			var el = this.getParent('li');
			if(!el) return;
			if(this.checked){
				el.addClass('selected');
			}else{
				el.removeClass('selected');
			}
			if(checkbox_click) checkbox_click(this);
			e.stop();
		}.bind(checkbox));
		label = new Element('div',{
			'html':setter['value']
		}).inject(li).setStyles({
			'margin-left':'30px'
		}).addEvents({
			'click': function(e){
				this.checked = !this.checked;
				var el = this.getParent('li');
				if(!el) return;
				if(this.checked){
					el.addClass('selected');
				}else{
					el.removeClass('selected');
				}
				if(checkbox_click) checkbox_click(this);
				e.stop();
			}.bind(checkbox)
		}).addEvents(this.events);
		if(this['selected'].contains(String(setter['key']))){
			checkbox.checked = true;
			li.addClass('selected');
		}
	};

	switch(typeOf(params['options'])){
		case 'array':
			Array.each(params['options'], funct.bind(params));
		break;
		case 'object':
			Object.each(params['options'], funct.bind(params));
		break
	}

	return params['list'];
};
