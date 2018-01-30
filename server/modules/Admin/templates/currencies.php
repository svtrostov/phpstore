<div class="page_currencies">

	<div class="bigblock" id="wrapper">
		<div class="titlebar"><h3>Курсы валют</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="area">
					<div id="table_area_wrapper"><div id="table_area" class="table_area"></div></div>
				</div>
				<div id="splitter"></div>
				<div id="info">
					<div class="ui-button-light" id="button_new_record"><span>Добавить валюту</span></div>
					<div class="ui-button-light" id="button_delete_record"><span>Удалить валюту</span></div>
					<div id="info_area"></div>

					<div id="cbr_area" style="display:none;width:100%;">
						<br><h2>Информация: актуальные курсы валют ЦБ РФ</h2>
						<div id="cbr_table_area"></div>
					</div>

				</div>


			</div>
		</div>
	</div>


	<div id="tmpl_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Код валюты:</span><input style="width:300px;" maxlength="3" class="disabled" readonly="true" type="text" value="" id="info_code"/></div>
		<div class="fline w150"><span>Наименование:</span><input style="width:300px;" maxlength="32" type="text" value="" id="info_name"/></div>
		<div class="fline w150"><span>Курс, рублей:</span><input style="width:100px;" maxlength="10" type="text" value="" id="info_exchange"/></div>
		<div class="ui-button-light" style="margin-left:160px;" id="button_change_record"><span>Сохранить</span></div>
		<div class="ui-button-light" style="margin-left:10px;" id="button_change_cancel"><span>Отмена</span></div>
	</div>

	<div id="tmpl_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Код валюты:</span><input style="width:300px;" maxlength="3" type="text" value="" id="new_code"/></div>
		<div class="fline w150"><span>Наименование:</span><input style="width:300px;" maxlength="32" type="text" value="" id="new_name"/></div>
		<div class="fline w150"><span>Курс, рублей:</span><input style="width:100px;" maxlength="10" type="text" value="" id="new_exchange"/></div>
		<div class="ui-button-light" style="margin-left:160px;"id="button_add_record" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
		<div class="ui-button-light" style="margin-left:10px;" id="button_add_cancel"><span>Отмена</span></div>
	</div>

</div>