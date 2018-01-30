<div class="page_product_add" id="page_product_add">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><h3 id="bigblock_title">Добавить новый товар</h3></div>
		<div class="contentwrapper">
			<div class='contentareafull'>

				<div id="product_add_done" style='display:none;'>
					<br><br><br>
					<div class="big_title">Товар добавлен</div>
					<br><br><br>
					<div class="middle_title" id="new_product_id"></div>
				</div>

				<div id="product_add_area">
				<!--Основное-->
					<div class="fline w200"><span>Родительский каталог:</span><p id="add_product_category_name">-[Выберите каталог в котором будет размещен товар]-</p></div>
					<div class="fline w200"><span>&nbsp;</span><input type="button" id="add_product_category_change" value="Изменить родительский каталог..."/><input type="hidden" id="add_product_category_id" value=""></div>
					<div class="splitline"></div>
					<div class="fline w200"><span>Отображение в каталоге:</span><select style="width:112px;" id="add_product_enabled"><option value="0">Скрыт</option><option value="1">Виден</option></select></div>
					<div class="fline w200"><span>Наименование товара:</span><input style="width:300px;" maxlength="255" type="text" value="" id="add_product_name"/></div>
					<div class="fline w200"><span>Краткое описание:</span>
						<textarea id="add_product_description" style="width:297px;height:200px;"></textarea>
					</div>
					<div class="fline w200"><span>Парт номера:</span><input style="width:300px;" maxlength="255" type="text" value="" id="add_product_part_nums"/></div>
					<div class="fline w200"><span>Артикульный номер:</span><input style="width:300px;" maxlength="255" type="text" value="" id="add_product_article"/></div>
					<div class="fline w200"><span>Производитель (бренд):</span><input style="width:300px;" maxlength="255" type="text" value="" id="add_product_vendor"/></div>
					<div class="fline w200"><span>Выгрузка в Yandex.маркет:</span><select style="width:112px;" id="add_product_yml"><option value="0">Нет</option><option value="1">Да</option></select></div>
					<div class="fline w200"><span>Карусель товаров:</span><select style="width:112px;" id="add_product_stockgallery"><option value="0">Нет</option><option value="1">Да</option></select></div>
					<div class="splitline"></div>
					<div class="fline w200"><span>Единица измерения:</span><input style="width:300px;" maxlength="32" type="text" value="" id="add_product_measure"/></div>
					<div class="fline w200"><span>Вес товара (кг):</span><input style="width:100px;" maxlength="32" type="text" value="" id="add_product_weight"/></div>
					<div class="fline w200"><span>Габариты товара, ширина (см):</span><input style="width:100px;" maxlength="10" type="text" value="" id="add_product_size_x"/></div>
					<div class="fline w200"><span>Габариты товара, высота (см):</span><input style="width:100px;" maxlength="10" type="text" value="" id="add_product_size_y"/></div>
					<div class="fline w200"><span>Габариты товара, глубина(см):</span><input style="width:100px;" maxlength="10" type="text" value="" id="add_product_size_z"/></div>
					<div class="splitline"></div>
					<div class="fline w200"><span>Валюта товара:</span><select style="width:312px;" id="add_product_currency"></select></div>
					<div class="fline w200"><span>Базовая цена:</span><input style="width:100px;" maxlength="10" type="text" value="" id="add_product_base_price"/></div>
					<div class="fline w200"><span>Базовая цена, руб:</span><input style="width:100px;" class="disabled" readonly="true" type="text" id="add_product_base_price_rub"/></div>
					<div class="splitline"></div>
					<div class="fline w200"><span>Внутренняя информация:</span>
						<textarea id="add_product_admin_info" style="width:297px;height:150px;"></textarea>
					</div>
					<div style="height:20px;"></div>
					<div class="ui-button" id="product_save_button" style="margin-left:210px;"><span>Добавить товар</span></div>
				<!--Основное-->
				</div>

			</div>

		</div>
	</div>


	<div class="bigblock" id="category_selector" style="display:none;">
		<input type="hidden" id="category_selector_show_element" value="">
		<div class="titlebar"><h3 id="category_selector_title">Выберите родительский каталог</h3></div>
		<div class="contentwrapper">
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
