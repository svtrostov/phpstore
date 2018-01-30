/*----------------------------------------------------------------------
Дерево
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/
var jsTreeMenu = new Class({

	Implements: [Options, Events],

	/*Настройки*/
	options: {
		iconPath: INTERFACE_IMAGES+'/tree/',//Путь к иконкам
		isExpanded: true, //Дерево развернуто
		parent: null,	// Родительский элемент
		treeclass: 'menutree',
		menu_id: 2,
		nodes: [],	//Массив объектов дерева в JSON формате
		onselectnode: null
	},

	/*Переменные*/
	created: false,			//Признак создания области панелей
	parent: null,			//Родительский элемент области панелей
	treeRoot: null,			//Root UL компонент дерева

	/*Елемент дерева по умолчанию*/
	defTreeNode:{
		'item_id': -1,
		'is_folder': false,
		'menu_id': 0,
		'parent_id': 0,
		'pos_no':0,
		'is_lock':0,
		'title': '',
		'desc': '',
		'href': '#',
		'class':'',
		'target':'_self',
		'selected': false,
		'childs': [],
		'collapsed':false
	},

	/*Инициализация*/
	initialize: function(options){
		this.setOptions(options);
		this.build();
	},//end function


	/*Применяет свойства к ноде*/
	calcNode: function(node){
		el = {};
		for (var key in this.defTreeNode) el[key] = this.defTreeNode[key];
		for (var key in node) el[key] = node[key];
		return el;
	},


	/*Создание дерева*/
	build: function(nodes){

		var nodes = nodes || this.options.nodes;

		//Проверка массива нод
		if(typeOf(nodes) != 'array') return this;
		if(nodes.length == 0) return this;

		//Родитель
		this.parent = $(this.options.parent);
		if (typeOf(this.parent) != 'element') return this;
		this.parent.empty();

		var threeNodes = {};
		var rootNodes = [];
		var item_id, parent_id;
		var empty_nodes = 0;

		//1: Убираем "битые" ноды, применяем значения по-умолчанию
		for(var i=0; i<nodes.length; ++i){
			if(typeOf(nodes[i])!='object') continue;
			item_id = parseInt(nodes[i]['item_id']);
			threeNodes[item_id] = Object.merge({}, this.defTreeNode, nodes[i]);
			if(threeNodes[item_id]['is_folder']){
				threeNodes[item_id]['collapsed'] = parseInt(App.localStorage.read('m_'+this.options.menu_id+'_'+item_id, 0, true)) == 0 ? false : true;
			}
		}

		//2: Вычисляем родитель->дитя
		for(item_id in threeNodes){
			parent_id = parseInt(threeNodes[item_id]['parent_id']);
			if(parent_id>0){
				if(typeOf(threeNodes[parent_id])!='object') continue;
				threeNodes[parent_id]['childs'].push(item_id);
			}else{
				rootNodes.push(item_id);
			}
		}

		//3: Удаление пустых узлов
		do{
			empty_nodes = 0;
			for(item_id in threeNodes){
				parent_id = parseInt(threeNodes[item_id]['parent_id']);
				if(threeNodes[item_id]['is_folder'] && threeNodes[item_id]['childs'].length == 0){
					if(parent_id>0){
						threeNodes[parent_id]['childs'].erase(item_id);
					}else{
						rootNodes.erase(item_id);
					}
					threeNodes[item_id]['is_folder'] = false;
					empty_nodes++;
				}
			}
		}while(empty_nodes>0);

		/*Построение дерева*/
		this.treeRoot = this.buildNodes(this.parent, threeNodes, rootNodes, 0, []);
		return this;

	},//end function



	/*Построение элемента дерева*/
	buildNodes: function(parent, allNodes, levelNodes, level, aLastNodes){

		var ul = new Element('ul').inject(parent);
		if(level == 0) ul.addClass(this.options['treeclass']);
		var isFolder, isFirst, isLast, img, src, span, child, arrLast, a, node, nodeCollapsed;

		//Сортировка элементов
		levelNodes.sort(function(a,b){
			var node_a = allNodes[a];
			var node_b = allNodes[b];
			if(typeOf(node_a)!='object'||typeOf(node_b)!='object') return 0;
			if(node_a['title']>node_b['title']) return 1;
			return -1;
		});

		//Массив признаков последних родительских нод на их уровнях
		arrLast = aLastNodes.clone();
		arrLast.push(false);

		//Просмотр массива нод текущего уровня
		for(var i=0; i<levelNodes.length; ++i){

			node = allNodes[levelNodes[i]];
			if(typeOf(node)!='object') continue;

			//Создание элемента
			li = new Element('li').inject(ul);

			//Этот элемент имеет дочерние элементы
			isFolder = node['is_folder'];
			isFirst = (i == 0) ? true : false;
			isLast = (i == levelNodes.length-1) ? true : false;
			arrLast[level] = isLast;

			for(var j=0;j<level;++j) new Element('img', {'src': this.options.iconPath + ( arrLast[j]==true ? 'empty.gif':'I.gif'),'width': 18,'height': 18}).inject(li);

			//Это обычный элемент
			if(!isFolder){
				src = (isLast) ? 'L.gif' : 'T.gif';
				new Element('img', {'src': this.options.iconPath + src,'width': 18,'height': 18}).inject(li);
				src = this.options.iconPath + '_doc.gif';
				new Element('img', {'src': src,'width': 18,'height': 18}).inject(li);
				span = new Element('span').inject(li);
				a = new Element('a',{
					'href': node['href'],
					'text': node['title'],
					'target': node['target']
				}).inject(span);
				if(node['selected']) a.addClass('selected');
			}
			//Это Раздел
			else{
				nodeCollapsed = (!this.options.isExpanded || node['collapsed']);
				if(isFirst && level == 0 && !isLast){
					node.minus = this.options.iconPath + 'Fminus.gif';
					node.plus =this.options.iconPath + 'Fplus.gif';
				}else{
					if(isLast){
						node.minus = this.options.iconPath + 'Lminus.gif';
						node.plus =this.options.iconPath + 'Lplus.gif';
					}else{
						node.minus = this.options.iconPath + 'Tminus.gif';
						node.plus =this.options.iconPath + 'Tplus.gif';
					}
				}
				node.open  = this.options.iconPath +'_open.gif';
				node.closed = this.options.iconPath +'_closed.gif';
				node.imgMP 		= new Element('img', {'src': (!nodeCollapsed ?  node.minus : node.plus ),'width': 18,'height': 18}).inject(li);
				node.imgFolder 	= new Element('img', {'src': (!nodeCollapsed ?  node.open : node.closed ),'width': 18,'height': 18}).inject(li);
				span = new Element('span').inject(li).set('text',node['title']).setStyle('cursor','pointer');
				node.childUL = this.buildNodes(li, allNodes, node['childs'], level+1, arrLast);
				if(nodeCollapsed){
					node.childUL.hide();
					span.addClass('collapsed');
				}else{
					span.addClass('expanded');
				}
				
				span.store('node', node);

				var ef = function(e){
					var node = this.retrieve('node');
					if(this.hasClass('collapsed')){
						this.removeClass('collapsed').addClass('expanded');
						node.imgMP.setProperty('src', node.minus);
						node.imgFolder.setProperty('src', node.open);
						node.childUL.show();
						App.localStorage.write('m_'+node.menu_id+'_'+node.item_id, 0, true);
					}else{
						this.removeClass('expanded').addClass('collapsed');
						node.imgMP.setProperty('src', node.plus);
						node.imgFolder.setProperty('src', node.closed);
						node.childUL.hide();
						App.localStorage.write('m_'+node.menu_id+'_'+node.item_id, 1, true);
					}
					if(e)e.stop();
				}

				span.addEvent('click', ef.bind(span));
				node.imgMP.addEvent('click', ef.bind(span));
				node.imgFolder.addEvent('click', ef.bind(span));

			}//Раздел

		}//for

		return ul;
	}

});//end class