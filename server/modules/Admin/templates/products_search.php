<div class="page_products_search" id="page_products_search">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Поиск товаров</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">


				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<select id="search_enabled">
								<option value="all" selected="true">Все товары</option>
								<option value="1">Только видимые</option>
							</select>
						</div>
						<div class="left">
							<select id="search_yml">
								<option value="all" selected="true">Все товары</option>
								<option value="yml">Yandex.market</option>
								<option value="stockgallery">Карусель</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="search_term" placeholder="Поиск по ID, артикулу, наименованию, описанию..." style="width:300px;"/>
						</div>
						<div class="left">
							<select id="search_limit">
								<option value="50">лимит 50 записей</option>
								<option value="100" selected="selected">лимит 100 записей</option>
								<option value="500">лимит 500 записей</option>
								<option value="1000">лимит 1000 записей</option>
								<option value="2000">лимит 2000 записей</option>
							</select>
						</div>
						<div class="left">
							<div class="ui-button-light" id="search_button" style="margin:0px;"><span class="ileft icon_filter">Искать</span></div>
						</div>
					</div>

					<div class="filter_line">
						<div class="left">
							<div class="input_title">ID товара:</div>
							<input type="text" value="" id="search_product_id" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Артикул содержит:</div>
							<input type="text" value="" id="search_article" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Производитель:</div>
							<input type="text" value="" id="search_vendor" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Описание содержит:</div>
							<input type="text" value="" id="search_description" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Совместимость:</div>
							<input type="text" value="" id="search_compatible" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Парт-номера:</div>
							<input type="text" value="" id="search_part_nums" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Валюта товара:</div>
							<select id="search_currency" style="width:100px;"><option value="all">-[Все]-</option></select>
						</div>
						<div class="left">
							<div class="input_title">Цена от:</div>
							<input type="text" value="" id="search_price_min" style="width:80px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Цена до:</div>
							<input type="text" value="" id="search_price_max" style="width:80px;" placeholder="Нет фильтра"/>
						</div>

					</div>

					<div class="filter_line">
						<div class="left">
							<div class="input_title">Поставщик:</div>
							<select id="search_source">
								<option value="all">-[Все]-</option>
								<option value="0">DTBOX</option>
								<option value="1">TEKO</option>
								<option value="2">VTT</option>
								<option value="3">CITILINK</option>
							</select>
						</div>
						<div class="left">
							<div class="input_title">Выбор места поиска: по всему каталогу или только по отдельной части</div>
							<select id="search_catalog" style="width:350px;"><option value="all" selected="true">-[Поиск по всему каталогу]-</option></select>
							<select id="search_subcategories" style="width:150px;">
								<option value="0">Только этот каталог</option>
								<option value="1" selected="selected">Этот и дочерние каталоги</option>
								<option value="2">Только дочерние каталоги</option>

								<option value="3">Кроме этого каталога</option>
								<option value="4">Кроме этого и дочерних</option>
								<option value="5">Только кроме дочерних</option>
							</select>
						</div>
						<div class="left">
							<div class="input_title">Временной фильтр товаров:</div>
							<select id="search_datetype"  style="width:150px;">
								<option value="all" selected="selected">-[Нет фильтра]-</option>
								<option value="addbefore">Добавлен ранее даты</option>
								<option value="addafter">Добавлен позднее даты</option>
								<option value="updbefore">Изменен ранее даты</option>
								<option value="updafter">Изменен позднее даты</option>
							</select>
							<input type="text" value="" id="search_date" class="calendar_input" style="width:90px;" placeholder="Дата события"/>
						</div>
					</div>

					<div class="tool_area" style="border-bottom: 1px solid #dadada; display:none;" id="search_table_tool">
						<div class="left">
							<div class="ui-button-light" id="products_select_all" style="margin:0px;"><span class="ileft icon_select_all">Выбрать все</span></div>
							<div class="ui-button-light" id="products_select_none" style="margin:0px;"><span class="ileft icon_select_none">Снять выбор</span></div>
						</div>
						<div class="left">
							<select id="products_selected_action" style="width:300px;">
								<option value="none" selected="true">Действия с выделенными товарами...</option>
								<optgroup label="Видимость товаров">
									<option value="enable">Сделать товары видимыми</option>
									<option value="disable">Сделать товары скрытыми</option>
								</optgroup>
								<optgroup label="Yandex.market">
									<option value="ymlon">Добавить в выгрузку Yandex.market</option>
									<option value="ymloff">Удалить из выгрузки Yandex.market</option>
								</optgroup>
								<optgroup label="Действия">
									<option value="move">Переместить в другой каталог</option>
									<option value="bridge">Объединить товары</option>
								</optgroup>
							</select>
							<div class="ui-button-light" id="products_action_go" style="margin:0px;"><span class="ileft icon_play">Выполнить</span></div>
						</div>
						<div class="right">
							<input type="text" style="width:130px;" id="products_filter">
						</div>
					</div>


				</div>

				<div class="search_area">
					<div id="search_table" style="display:none;">
					
					</div>
					<div id="search_none" style="display:none;">
						<h1 class="errorpage_title">Товары не найдены</h1>
					</div>
					<div id="search_select" style="display: block;">
						<h1 class="errorpage_title">Поиск по товарам</h1>
					</div>
				</div>


			</div>
		</div>
	</div>


	<div class="bigblock" id="category_selector" style="display:none;">
		<input type="hidden" id="category_selector_show_element" value="">
		<div class="titlebar"><h3 id="category_selector_title">Выберите родительский каталог</h3></div>
		<div class="contentwrapper">

			<div class="category_selector_info">
				<div class="iline wauto"><span>Выбран каталог:</span><p class="" id="category_selector_selected_name"></p></div>
			</div>


			<div class="contentarea" id="category_selector_wrapper">
				<div id="category_selector_tree">

				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="category_selector_complete_button"><span>Выбрать каталог</span></div>
				<div class="ui-button" id="category_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>

</div>