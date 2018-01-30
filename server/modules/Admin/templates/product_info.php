<div class="page_product_info" id="page_product_info">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Информация о товаре</h3></div>
		<div class="contentwrapper">

			<div id="product_start">
				<br><br><br>
				<h1 class="big_title">Загрузка данных...</h1>
			</div>


			<div id="product_card"><div id="product_tabs" class="tabs_area absolute" style="bottom:50px;">
			<!--tabs_area-->

				<ul class="tabs">
					<li class="tab">Основное</li>
					<li class="tab">Cклады</li>
					<li class="tab">Описание</li>
					<li class="tab">Совместимость</li>
					<li class="tab">Объединение</li>
					<li class="tab">Характеристики</li>
					<li class="tab">Изображения</li>
				</ul>

				<div class="tab_content"><div class="wrapper miniform" id="product_info_area">
				<!--Основное-->
					<input type="hidden" id="info_product_category_id" value="">
					<fieldset>
						<legend>Родительский каталог ID: <b id="label_product_category_id"></b></legend>
						<div style="float:right;width:70px;"><input type="button" style="height:23px;" id="info_product_category_change" value="Изменить..."/></div>
						<div style="margin-right:80px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_category_name"/></div>
					</fieldset>
					<fieldset>
						<legend>Наименование Товара ID: <b id="info_product_product_id"></b> (добавлен: <b id="info_product_create_time"></b>, последнее изменение: <b id="info_product_update_time"></b>)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_name"/></div>
					</fieldset>
					<fieldset>
						<legend>SEO наименование (SEO из наименования: <b id="info_product_seo_name"></b>)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_seo"/></div>
					</fieldset>
					<fieldset>
						<legend>Краткое описание товара</legend>
						<div style="padding-right:5px;"><textarea id="info_product_description" style="width:100%;height:50px;"></textarea></div>
					</fieldset>
					
					<div class="clear"></div>
					<fieldset style="display:inline-block;width:150px;">
						<legend>Валюта цены товара</legend>
						<div style="padding-right:5px;"><select style="width:100%;" id="info_product_currency"></select></div>
					</fieldset>

					<fieldset style="display:inline-block;width:320px;">
						<legend>Реальная базовая цена и ее коэффициент изменения</legend>
						<div style="padding-right:5px;">
							Цена: <input style="width:100px;" maxlength="32" type="text" value="" id="info_product_base_price_real"/> х
							Коэф: <input style="width:100px;" maxlength="32" type="text" value="" id="info_product_base_price_factor"/>
						</div>
					</fieldset>

					<fieldset style="display:inline-block;width:230px;">
						<legend>Расчетная базовая цена товара</legend>
						<div style="padding-right:5px;">
							<input class="disabled" readonly="true" style="width:100px;" maxlength="32" type="text" value="" id="info_product_base_price"/>
							= <span  id="info_product_base_price_rub"></span> руб
						</div>
					</fieldset>


					<div class="clear"></div>

					<fieldset style="display:inline-block;width:200px;">
						<legend>Артикульный номер</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_article"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:200px;">
						<legend>Производитель (бренд)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_vendor"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:300px;">
						<legend>Парт номера</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_part_nums"/></div>
					</fieldset>

					<div class="clear"></div>

					<fieldset style="display:inline-block;width:auto;">
						<legend>Виден в каталоге</legend>
						<div style="padding-right:5px;"><select style="width:100%;" id="info_product_enabled"><option value="0">Скрыт</option><option value="1">Виден</option></select></div>
					</fieldset>
					<fieldset style="display:inline-block;width:auto;">
						<legend>Yandex.маркет</legend>
						<div style="padding-right:5px;"><select style="width:100%;" id="info_product_yml_enabled"><option value="0">Нет</option><option value="1">Да</option></select></div>
					</fieldset>
					<fieldset style="display:inline-block;width:auto;">
						<legend>Карусель товаров</legend>
						<div style="padding-right:5px;"><select style="width:100%;" id="info_product_stockgallery"><option value="0">Нет</option><option value="1">Да</option></select></div>
					</fieldset>

					<fieldset style="display:inline-block;width:auto;">
						<legend>Специальное предложение</legend>
						<div style="padding-right:5px;">
							<select style="width:80px;" id="info_product_offer"><option value="0">Нет</option><option value="1">Да</option></select>
							<span  id="info_product_offer_discount_area"> Размер скидки: <input style="width:80px;" maxlength="10" type="text" value="0.00" id="info_product_offer_discount"/></span>
						</div>
					</fieldset>


					<div class="clear"></div>

					<fieldset style="display:inline-block;width:auto;">
						<legend>Единица измерения</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_measure"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:100px;">
						<legend>Вес товара (кг)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_weight"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:80px;">
						<legend>Ширина (см)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_size_x"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:80px;">
						<legend>Высота (см)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_size_y"/></div>
					</fieldset>
					<fieldset style="display:inline-block;width:80px;">
						<legend>Глубина (см)</legend>
						<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_product_size_z"/></div>
					</fieldset>

					<div class="clear"></div>

					<fieldset>
						<legend>Внутренняя информация о товаре</legend>
						<div style="padding-right:5px;"><textarea id="info_product_admin_info" style="width:100%;height:50px;"></textarea></div>
					</fieldset>

					<div id="product_source_area" style="display:none;">
						<fieldset>
							<legend>Цена и остатки на складах данного товара могут обновляться от поставщика автоматически</legend>
							<div class="fline w200"><span>Поставщик:</span><input style="width:300px;" class="disabled" readonly="true" type="text" id="info_source_source_name"/></div>
							<div class="fline w200"><span>Время добавления:</span><input style="width:150px;" class="disabled" readonly="true" type="text" id="info_source_add_time"/></div>
							<div class="fline w200"><span>Дата последнего обновления:</span><input style="width:150px;" class="disabled" readonly="true" type="text" id="info_source_update_time"/></div>
							<div class="fline w200"><span>Обновлять цену товара:</span><select style="width:112px;" id="info_source_need_update_price"><option value="0">Нет</option><option value="1">Да</option></select></div>
							<div class="fline w200"><span>Обновлять остатки на складах:</span><select style="width:112px;" id="info_source_need_update_warehouse"><option value="0">Нет</option><option value="1">Да</option></select></div>
							<div class="fline w200"><span>Обновлять картинку:</span><select style="width:112px;" id="info_source_image_checked"><option value="1">Нет</option><option value="0">Да</option></select></div>
							<div style="margin-left:200px"><div class="ui-button-light" id="source_update_force_button"><span>Обновить цену и остатки товара от поставщика прямо сейчас</span></div></div>
						</fieldset>
					</div>
					
					<div style="height:20px;"></div>

				<!--Основное-->
				</div></div>

				<div class="tab_content"><div class="wrapper">
				<!--Остатки на складах-->
				<div id="product_wh_table" class="product_wh_table"></div>
				<!--Остатки на складах-->
				</div></div>

				<div class="tab_content"><div class="wrapper" id="info_product_content_wrapper">
				<!--Описание-->
					<textarea id="info_product_content" name="info_product_content" style="width:100%;height:100%;" class="tinymce"></textarea>
				<!--Описание-->
				</div></div>

				<div class="tab_content"><div class="wrapper" id="info_product_compatible_wrapper">
				<!--Таблица совместимости-->
					<textarea id="info_product_compatible" name="info_product_compatible" style="width:100%;height:100%;" class="tinymce"></textarea>
				<!--Таблица совместимости-->
				</div></div>

				<div class="tab_content"><div class="wrapper">
				<!--Объединение-->
					<h3>Объединение товаров суммирует их остатки на складах и показывает клиенту единую цену, вычисляемую в зависимости от заданных настроек магазина</h3>
					<br>
					<div class="iline w150"><span>Объединение ID:</span><input style="width:100px;" maxlength="32" type="text" value="" id="info_product_bridge_id"/><div class="ui-button-light" id="bridge_update_button"><span style="margin:0;">Изменить</span></div></div>
					<div class="iline w150"><span>&nbsp;</span>
						<p class="small">
							В поле выше Вы можете изменить идентификатор объединения вручную, однако Вы должны понимать что Вы делаете.<br>
							Объединения, в которых находится только один товар, никакого эффекта не имеют, при этом &laquo;затормаживают&raquo; работу магазина.<br>
							Если идентификатор объединения задан как &laquo;ноль&raquo; (0), то считается, что товар не объединен с другими товарами.<br>
							Если Вы изменили идентификатор объединения на &laquo;ноль&raquo;, то товар будет исключен из существующего объединения, при этом если в объединении присутствует менее трех товаров - оно будет удалено.
						</p>
					</div>
					<div class="splitline"></div>

					<div id="bridge_none" style="display:none;"><br><br><br><h1 class="big_title">Объединение отсутствует</h1></div>
					<div id="bridge_info" style="display:none;">
						<h1>Сведения о текущем объединении товаров</h1>
						<div class="iline w200"><span>Объединенная цена, руб:</span><p id="info_bridge_price"></p></div>
						<div class="iline w200"><span>Объединенный остаток:</span><p id="info_bridge_count"></p></div>
						<br>
						<div id="bridge_table"></div>
						<div class="ui-button" id="bridge_product_add_button"><span style="margin:0;">Выбрать товар для объединения...</span></div>
					</div>

				<!--Объединение-->
				</div></div>


				<div class="tab_content"><div class="wrapper miniform">
				<!--Характеристики-->

					<fieldset id="properties_area">
						<legend><b>Характеристики товара</b></legend>
						<div id="properties_toolbar">
							<input type="button" id="property_add_button"  style="width:95px;" value="Добавить"/>
							<input type="button" id="property_delete_button" style="width:95px;margin-left:5px;" value="Удалить"/>
						</div>
						<div class="list_wrapper"><select id="properties_list" style="width:100%;height:100%" size="15"></select></div>
					</fieldset>

					<fieldset id="values_area">
						<legend><b>Значение</b></legend>
						<div id="property_value_list">
							<select id="value_list" style="width:100%;height:100%" size="15"></select>
						</div>

						<div id="property_value_multilist">
						</div>

						<div id="property_value_num">
							<div style="padding-right:15px;"><input style="width:100%;" maxlength="32" type="text" value="" id="value_num" placeholder="Введите числовое значение"/></div>
						</div>

						<div id="property_value_bool">
							<select style="width:100%;" id="value_bool"><option value="0">Нет</option><option value="1">Да</option></select>
						</div>

						<div id="property_value_button">
							<input type="button" style="height:26px;width: 200px;" id="property_value_save" value="Сохранить изменения"/><br>
						</div>


					</fieldset>

				<!--Характеристики-->
				</div></div>


				<div class="tab_content"><div class="wrapper">
				<!--Изображения-->
					<h1>Основное изображение товара</h1>
					<div class="fline w200"><span>URL картинки товара:</span>
						<input style="width:300px;" class="disabled" readonly="true" type="text" value="" id="info_product_pic_big"/>
						<img src="/client/images/preview_active.png" style="margin-left:10px; cursor: pointer; vertical-align: middle; margin-left: 3px;" id="info_product_pic_preview">
						<img src="/client/images/icons/icon_delete.png" style="margin-left:10px; cursor: pointer; vertical-align: middle; margin-left: 3px;" id="info_product_pic_delete">
					</div>
					<form action="/admin/ajax/catalog" method="post" enctype="multipart/form-data" id="product_image_upload_form">
						<input type="hidden" name="action" value="product.image.upload"/>
						<input type="hidden" name="ajax" value="1"/>
						<input type="hidden" name="product_id" id="product_image_upload_form_product_id" value=""/>
						<div class="iline w200" style="height:20px;"><span>&nbsp;</span>
							<label class="upload"><input type="file" id="product_image_upload_file" accept="image/jpeg,image/png,image/gif" name="image" multiple="false"/><span>Выбрать картинку...</span></label>
							<div class="ui-button-light" style="margin-left:10px;" id="product_image_upload_button"><span id="product_image_upload_button_title">Загрузить на сервер</span></div>
						</div>
					</form>
					<br>
					<div class="splitline"></div>
					<h1>Дополнительные изображения</h1>
					<ul class="imglist" id="product_imglist">
					</ul>
					<form action="/admin/ajax/catalog" method="post" enctype="multipart/form-data" id="product_imglist_upload_form">
						<input type="hidden" name="action" value="product.imglist.upload"/>
						<input type="hidden" name="ajax" value="1"/>
						<input type="hidden" name="product_id" id="product_imglist_upload_form_product_id" value=""/>
							<label class="upload"><input type="file" id="product_imglist_upload_file" accept="image/jpeg,image/png,image/gif" name="image" multiple="false"/><img src="/client/images/buttons/button_plus.png" style="margin:11px;"></label>
					</form>
				<!--Изображения-->
				</div></div>

			</div></div>

			<div class="buttonarea">
				<div class="ui-button" id="product_save_button"><span>Сохранить изменения</span></div>
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


			<div class="category_selector_wrapper" id="category_selector_wrapper">
				<div id="category_selector_tree">

				</div>
			</div>
			<div class="buttonarea">
				<div class="ui-button" id="category_selector_complete_button"><span>Выбрать каталог</span></div>
				<div class="ui-button" id="category_selector_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


	<div class="bigblock" id="pselector" style="display:none;">
		<input type="hidden" id="pselector_selected_id" value="">
		<div class="titlebar"><h3 id="pselector_title">Выберите товар для объединения</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">


				<div class="filter_area">

					<div class="filter_line">
						<div class="left">
							<input type="text" value="" id="pselector_term" placeholder="Поиск товаров по ID, артикулу, наименованию, описанию..." style="width:400px;"/>
						</div>
						<div class="left">
							<select id="pselector_limit">
								<option value="50">лимит 50 записей</option>
								<option value="100" selected="selected">лимит 100 записей</option>
								<option value="500">лимит 500 записей</option>
							</select>
							<div class="ui-button-light" id="pselector_search" style="margin:0px;"><span class="ileft icon_filter">Искать</span></div>
						</div>
					</div>

					<div class="filter_line">
						<div class="left">
							<div class="input_title">ID товара:</div>
							<input type="text" value="" id="search_product_id" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Артикул содержит:</div>
							<input type="text" value="" id="search_article" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Производитель:</div>
							<input type="text" value="" id="search_vendor" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Описание содержит:</div>
							<input type="text" value="" id="search_description" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
						<div class="left">
							<div class="input_title">Парт-номер содержит:</div>
							<input type="text" value="" id="search_part_nums" style="width:90px;" placeholder="Нет фильтра"/>
						</div>
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
					</div>

				</div>

				<div class="pselector_area">
					<div id="pselector_table" style="display:none;"></div>
					<div id="pselector_none" style="display:none;">
						<h1 class="errorpage_title">Товары не найдены</h1>
					</div>
					<div id="pselector_select" style="display: block;">
						<h1 class="errorpage_title">Поиск по товарам</h1>
					</div>
				</div>


			</div>
			<div class="buttonarea">
				<div class="ui-button" id="pselector_complete_button"><span>Выбрать товар</span></div>
				<div class="ui-button" id="pselector_cancel_button"><span>Закрыть</span></div>
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
