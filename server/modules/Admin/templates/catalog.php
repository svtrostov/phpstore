<div class="page_catalog" id="page_catalog">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Каталог товаров</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="categories_area">
					<div id="categories_tree_wrapper"><div id="categories_tree" class="categories_tree"></div></div>
					<div id="categories_tree_bottom">
						<div class="ui-button-light" id="category_add_button" style="width:99%;margin:0;padding:0;"><span>Создать каталог</span></div>
					</div>
				</div>
				<div id="splitter"></div>
				<div id="product_area">

					<div id="category_area" style="display:none;">

						<div id="category_title_area">
						
						</div>

						<div id="tabs_area_wrapper"><div id="tabs_area" class="tabs_area absolute">
						<!--tabs_area-->

							<ul class="tabs">
								<li class="tab">Товары в каталоге</li>
								<li class="tab">Настройки каталога</li>
								<li class="tab">Фильтры товаров</li>
								<li class="tab">Операции с каталогом</li>
							</ul>

							<div class="tab_content"><div class="wrapper">
							<!--Товары в каталоге-->
							<div id="products_table_wrapper">



								<div class="tool_area">
									<div class="left" style="height:31px;">
										<img src="/client/images/dtbox/prev.png" id="navigator_page_prev">
										<input type="text" style="width:50px" value="1" id="navigator_page_no">
										<img src="/client/images/dtbox/next.png" id="navigator_page_next">
									</div>
									<div class="left">
										Всего товаров <span id="navigator_count" style="font-weight:bold;">0</span>, на странице: <select id="navigator_per_page">
											<option value="10">10</option>
											<option value="20" selected="true">20</option>
											<option value="50">50</option>
											<option value="100">100</option>
											<option value="200">200</option>
											<option value="500">500</option>
											<option value="1000">1000</option>
										</select>
									</div>
									<div class="left">
										<div class="ui-button-light" id="catalog_reload" style="margin:0px;"><span class="ileft icon_reload">Обновить</span></div>
									</div>
								</div>

								<div class="tool_area">
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
											</optgroup>
										</select>
										<div class="ui-button-light" id="products_action_go" style="margin:0px;"><span class="ileft icon_play">Выполнить</span></div>
									</div>
									<div class="right">
										<input type="text" style="width:130px;" id="products_filter">
									</div>
								</div>

								<div id="products_table" class="products_table"></div>
							</div>
							<div id="products_table_none"><h1 class="errorpage_title">Каталог пуст</h1></div>
							<!--Товары в каталоге-->
							</div></div>

							<div class="tab_content"><div class="wrapper">
							<!--Настройки каталога-->
								<div id="category_info_area">
									<input type="hidden" id="info_category_parent_id" value=""/>
									<div class="fline w200"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_category_id"/></div>
									<div class="fline w200"><span>Наименование*:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_category_name"/></div>
									<div class="fline w200"><span>SEO наименование:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_category_seo"/></div>
									<div class="fline w200"><span>Описание:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_category_desc"/></div>
									<div class="fline w200"><span>Видимость каталога:</span><select style="width:212px;" id="info_category_enabled"><option value="0">Скрыт</option><option value="1">Виден</option></select></div>
									<div class="fline w200"><span>Отображение фильтров:</span><select style="width:312px;" id="info_category_hide_filters"><option value="0">Показывать фильтры товаров в каталоге</option><option value="1">Скрыть фильтры товаров в каталоге</option></select></div>
									<div class="fline w200"><span>Родительский каталог:</span><p id="info_category_path"></p></div>
									<div class="fline w200"><span>&nbsp;</span><input type="button" id="category_path_change_button" value="Изменить родительский каталог..."/></div>
									<div class="splitline"></div>
									<div class="fline w200"><span>URL картинки каталога:</span>
										<input style="width:300px;" class="disabled" readonly="true" type="text" value="" id="info_category_pic_small"/>
										<img src="/client/images/preview_active.png" style="margin-left:10px; cursor: pointer; vertical-align: middle; margin-left: 3px;" id="info_category_pic_preview">
										<img src="/client/images/icons/icon_delete.png" style="margin-left:10px; cursor: pointer; vertical-align: middle; margin-left: 3px;" id="info_category_pic_delete">
									</div>
									<form action="/admin/ajax/catalog" method="post" enctype="multipart/form-data" id="category_image_upload_form">
										<input type="hidden" name="action" value="category.image.upload"/>
										<input type="hidden" name="ajax" value="1"/>
										<input type="hidden" name="category_id" id="category_image_upload_form_category_id" value=""/>
										<div class="iline w200" style="height:20px;"><span>&nbsp;</span>
											<label class="upload"><input type="file" accept="image/jpeg,image/png,image/gif" id="category_image_upload_file" name="image" multiple="false"/><span>Выбрать картинку...</span></label>
											<div class="ui-button-light" style="margin-left:10px;" id="category_image_upload_button"><span id="category_image_upload_button_title">Загрузить на сервер</span></div>
										</div>
									</form>
									<div class="splitline"></div>
									<div class="iline w200"><span>&nbsp;</span><div class="ui-button" id="category_info_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div></div>
								</div>
							<!--Настройки каталога-->
							</div></div>

							<div class="tab_content"><div class="wrapper"><div class="miniform">
							<!--Фильтры товаров-->
								<fieldset>
									<legend><b>Краткое описание</b></legend>
									Здесь Вы можете выбрать характеристики товаров, которые будут использоваться в качестве фильтров.<br>
									То есть, клиенты, переходя этот каталог будут иметь возможность отфильтровать товары по выбранным характеристикам.<br>
									Примечание: разумеется, для корректной работы фильтра, у товаров, размещенных в каталоге, должны быть добавлены эти характеристики и заданы их значения.
								</fieldset>

								<fieldset>
									<legend><b>Список характеристик, используемых в качестве фильтра</b></legend>
									<div style="float:right;width:100px;">
											<input type="button" id="property_add_button"  style="width:90px;margin-bottom:5px;" value="Добавить"/>
											<input type="button" id="property_delete_button" style="width:90px;margin-bottom:5px;" value="Удалить"/>
									</div>
									<div style="margin-right:110px;"><select id="properties_list" style="width:100%;" size="15"></select></div>
								</fieldset>

							<!--Фильтры товаров-->
							</div></div></div>

							<div class="tab_content"><div class="wrapper">
							<!--Операции с каталогом-->
								<div class="ui-button" id="category_delete_button" style="margin:5px 0px;"><span>Удалить текущий каталог</span></div>
							<!--Операции с каталогом-->
							</div></div>

						<!--tabs_area-->
						</div></div>
					</div>

					<div id="category_none">
						<h1 class="errorpage_title">Выберите каталог</h1>
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


	<div class="bigblock" id="property_selector" style="display:none;">
		<div class="titlebar"><h3 id="property_selector_title">Выберите характеристику</h3></div>
		<div class="contentwrapper">

			<div class="property_selector_info">
				<div class="iline wauto"><span>Выбрана характеристика:</span><p class="" id="property_selector_selected_name"></p></div>
			</div>


			<div class="property_selector_wrapper" id="property_selector_wrapper">
				<div id="property_selector_tree">

				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="property_selector_complete_button"><span>Выбрать характеристику</span></div>
				<div class="ui-button" id="property_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>

</div>
