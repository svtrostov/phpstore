/*----------------------------------------------------------------------
Дерево
Stanislav V. Tretyakov (svtrostov@yandex.ru)
----------------------------------------------------------------------*/
var jsTreeMenuDesign = new Class({

	Implements: [Options, Events],

	/*Настройки*/
	options: {
		iconPath: INTERFACE_IMAGES+'/tree/',//Путь к иконкам
		isExpanded: true, //Дерево развернуто
		parent: null,	// Родительский элемент
		treeclass: 'treedesign',
		menu_id: 2,
		nodes: [],	//Массив объектов дерева в JSON формате
		onselectnode: null
	},

	/*Переменные*/
	created: false,			//Признак создания области панелей
	parent: null,			//Родительский элемент области панелей
	treeRoot: null,			//Root UL компонент дерева
	selectedNode: null,		//Выбранная нода

	/*Елемент дерева по умолчанию*/
	defTreeNode:{
		'item_id': -1,
		'is_folder': '0',
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


	/*Очищает дерево*/
	clear: function(){
		this.selectedNode = null;
		if(typeOf(this.treeRoot)=='element')this.treeRoot.destroy();
	},


	/*Создание дерева*/
	build: function(nodes){

		this.clear();

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
			threeNodes[item_id]['is_folder'] = String(threeNodes[item_id]['is_folder'])=='0'?false:true;
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
				span = new Element('span',{
					'text': node['title']
				}).inject(li);
			}
			//Это Раздел
			else{
				nodeCollapsed = false;
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
				this.buildNodes(li, allNodes, node['childs'], level+1, arrLast);
			}//Раздел

			span.store('node', node);
			span.addEvent('click',this.nodeclick.bind(this));

		}//for

		return ul;
	},//end function



	/*Клик по ноде*/
	nodeclick: function(e){
		e.stop();
		if(this.selectedNode) this.selectedNode.removeClass('selected');
		this.selectedNode = null;
		e.target = $(e.target);
		if(!e.target) return;
		this.selectedNode = e.target;
		e.target.addClass('selected');
		var data = e.target.retrieve('node');
		if(typeOf(data)=='object'){
			this.fireEvent('selectnode', [data]);
			if(this.options.onselectnode) this.options.onselectnode(data);
		}
	},


	/*Выбор ноды по идентификатору пункта меню*/
	selectNodeById: function(item_id, fire_event){
		if(!this.treeRoot) return;
		var data;
		var nodes = this.treeRoot.getChildren('li span');
		for(var i=0; i<nodes.length;i++){
			data = nodes[i].retrieve('node');
			if(typeOf(data)=='object'){
				if(String(data['item_id']) == String(item_id)){
					if(this.selectedNode) this.selectedNode.removeClass('selected');
					this.selectedNode = nodes[i];
					this.selectedNode.addClass('selected');
					if(fire_event){
						this.fireEvent('selectnode', [data]);
						if(this.options.onselectnode) this.options.onselectnode(data);
					}
					return this;
				}
			}
		}
		return this;
	}


});//end class
