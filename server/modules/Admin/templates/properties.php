<div class="page_properties" id="page_properties">

	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal"><a class="expander" href="#" id="bigblock_expander"></a><h3 id="bigblock_title">Настройка характеристик товаров</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="properties_area">
					<div id="properties_tree_wrapper"><div id="properties_tree" class="properties_tree"></div></div>
					<div id="properties_tree_bottom">
						<div style="width:75px;float:right;">
							<div class="tree_button"><img src="/client/images/icons/icon_expand_24.png" title="Раскрыть дерево" id="tree_expand_button"></div>
							<div class="tree_button"><img src="/client/images/icons/icon_collapse_24.png" title="Свернуть дерево" id="tree_collapse_button"></div>
						</div>
						<div style="margin-right:80px;">
							<div class="ui-button-light" id="group_add_button" style="width:99%;margin:0;padding:0;"><span>Добавить группу характеристик</span></div>
						</div>
					</div>
				</div>

				<div id="splitter"></div>

				<div id="info_area">


					<div id="info_group" style="display:none;">
						<div class="tool_area">
							<div class="left">
								<div class="ui-button-light" id="property_add_button" style="margin:0px;"><span class="ileft icon_add">Добавить характеристику в эту группу</span></div>
							</div>
							<div class="left">
								<div class="ui-button-light" id="group_rename_button" style="margin:0px;"><span class="ileft icon_edit">Переименовать группу</span></div>
								<div class="ui-button-light" id="group_delete_button" style="margin:0px;"><span class="ileft icon_delete">Удалить группу</span></div>
							</div>
						</div>
					</div>


					<div id="info_property" style="display:none;">
						<div class="tool_area">
							<div class="left">
								<div class="ui-button-light" id="property_delete_button" style="margin:0px;"><span class="ileft icon_delete">Удалить характеристику</span></div>
							</div>
						</div>

						<div id="tabs_area_wrapper"><div id="tabs_area" class="tabs_area absolute">
						<!--tabs_area-->

							<ul class="tabs">
								<li class="tab">Свойства</li>
								<li class="tab">Привязка к каталогам</li>
							</ul>

							<div class="tab_content"><div class="wrapper">
							<!--Свойства-->

								<div class="miniform">

									<fieldset style="float:right;width:300px;">
										<legend>Группа свойств</legend>
										<div style="padding-right:5px;"><select id="info_pgroup_id" style="width:100%;"></select></div>
									</fieldset>

									<fieldset style="margin-right:310px;">
										<legend>Название характеристики ID: <b id="info_property_id"></b></legend>
										<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_name"/></div>
									</fieldset>

									<div class="clear"></div>

									<fieldset>
										<legend>Внутренняя информация</legend>
										<div style="padding-right:5px;"><textarea id="info_admin_info" style="width:100%;height:50px;"></textarea></div>
									</fieldset>

									<div class="clear"></div>

									<fieldset style="float:right;width:200px;">
										<legend>Единица изменения характеристики</legend>
										<div style="padding-right:5px;"><input style="width:100%;" maxlength="255" type="text" value="" id="info_measure"/></div>
									</fieldset>

									<fieldset style="margin-right:210px;">
										<legend>Тип данных</legend>
										<div style="padding-right:5px;"><select id="info_type" style="width:100%;">
											<option value="list">Список с выбором одного элемента</option>
											<option value="multilist">Список с выбором нескольких элементов</option>
											<option value="bool">Логическое значение (Да/Нет)</option>
											<option value="num">Число</option>
										</select></div>
									</fieldset>

									<div class="clear"></div>

									<fieldset id="values_area" style="display:none;">
										<legend>Список значений</legend>
										<div style="float:right;width:130px;">
												<input type="button" id="value_add_button"  style="width:120px;margin-bottom:5px;" value="Добавить"/>
												<input type="button" id="value_edit_button" style="width:120px;margin-bottom:5px;" value="Редактировать"/>
												<input type="button" id="value_delete_button" style="width:120px;margin-bottom:5px;" value="Удалить"/>
												<input type="button" id="value_up_button" style="width:120px;margin-top:30px;margin-bottom:5px;" value="Выше"/>
												<input type="button" id="value_down_button" style="width:120px;margin-bottom:5px;" value="Ниже"/>
												<input type="button" id="value_sort_asc_button" style="width:120px;margin-top:30px;margin-bottom:5px;" value="Сортировка А...Я"/>
												<input type="button" id="value_sort_desc_button" style="width:120px;margin-bottom:5px;" value="Сортировка Я...А"/>
										</div>
										<div style="margin-right:140px;"><select id="values_list" style="width:100%;" size="10"></select></div>
									</fieldset>

									<div style="margin-left:10px;">
										<div class="ui-button" id="property_save_button" style="width:165px;margin:5px 0px;"><span>Сохранить изменения</span></div>
									</div>

								</div>

							<!--Свойства-->
							</div></div>

							<div class="tab_content"><div class="wrapper"><div class="miniform">
							<!--Каталоги-->
								<fieldset>
									<legend><b>Краткое описание</b></legend>
									Здесь Вы можете выбрать каталоги товаров, для которых данная характеристика будет использоваться в качестве фильтра.<br>
									То есть, клиенты, переходя в один из указанных каталогов будут иметь возможность отфильтровать товары по данному свойству.<br>
									Примечание: разумеется, для корректной работы фильтра, у товаров, размещенных в каталоге, должны быть заданы значения данной характеристики.
								</fieldset>

								<fieldset>
									<legend><b>Список каталогов, для которых доступна данная характеристика в качестве фильтра</b></legend>
									<div style="float:right;width:100px;">
											<input type="button" id="category_add_button"  style="width:90px;margin-bottom:5px;" value="Добавить"/>
											<input type="button" id="category_delete_button" style="width:90px;margin-bottom:5px;" value="Удалить"/>
									</div>
									<div style="margin-right:110px;"><select id="categories_list" style="width:100%;" size="15"></select></div>
								</fieldset>

							<!--Каталоги-->
							</div></div></div>

						</div></div>

					</div>


				</div>


			</div>
		</div>
	</div>



	<div class="bigblock" id="category_selector" style="display:none;">
		<input type="hidden" id="category_selector_show_element" value="">
		<div class="titlebar"><h3 id="category_selector_title">Выберите каталог</h3></div>
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


</div>