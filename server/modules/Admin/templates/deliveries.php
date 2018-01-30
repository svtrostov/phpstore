<div class="page_deliveries">

	<div class="bigblock" id="wrapper">
		<div class="titlebar"><h3>Методы доставки</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="area">
					<div id="table_area_wrapper"><div id="table_area" class="table_area"></div></div>
				</div>
				<div id="splitter"></div>
				<div id="info">
					<div class="ui-button-light" id="button_new_record"><span>Добавить</span></div>
					<div class="ui-button-light" id="button_delete_record"><span>Удалить</span></div>
					<div id="info_area"></div>
				</div>


			</div>
		</div>
	</div>


	<div id="tmpl_info" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Идентификатор:</span><input style="width:150px;" type="text" class="disabled" readonly="true" value="" id="info_delivery_id"/></div>
		<div class="fline w150"><span>Наименование:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_name"/></div>
		<div class="fline w150"><span>Описание:</span><input style="width:300px;" maxlength="255" type="text" value="" id="info_desc"/></div>
		<div class="fline w150"><span>Статус:</span><select style="width:212px;" id="info_enabled"><option value="0">Заблокирован</option><option value="1">Доступен</option></select></div>
		<div class="fline w150"><span>Стоимость доставки:</span><input style="width:100px;" maxlength="10" type="text" value="" id="info_price"/></div>
		<div class="fline w150"><span>&nbsp;</span>Сумма заказа, при которой доступен данный метод доставки</div>
		<div class="fline w150"><span>Сумма заказа от (руб):</span><input style="width:100px;" maxlength="10" type="text" value="" id="info_order_min"/></div>
		<div class="fline w150"><span>Сумма заказа до (руб):</span><input style="width:100px;" maxlength="10" type="text" value="" id="info_order_max"/></div>
		<div class="ui-button-light" style="margin-left:160px;" id="button_change_record"><span>Сохранить</span></div>
		<div class="ui-button-light" style="margin-left:10px;" id="button_change_cancel"><span>Отмена</span></div>
	</div>

	<div id="tmpl_new" style="padding:10px;display:none;width:100%;">
		<div class="fline w150"><span>Наименование:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_name"/></div>
		<div class="fline w150"><span>Описание:</span><input style="width:300px;" maxlength="255" type="text" value="" id="new_desc"/></div>
		<div class="fline w150"><span>Статус:</span><select style="width:212px;" id="new_enabled"><option value="0">Заблокирован</option><option value="1">Доступен</option></select></div>
		<div class="fline w150"><span>Стоимость доставки:</span><input style="width:100px;" maxlength="10" type="text" value="" id="new_price"/></div>
		<div class="fline w150"><span>&nbsp;</span>Сумма заказа, при которой доступен данный метод доставки</div>
		<div class="fline w150"><span>Сумма заказа от (руб):</span><input style="width:100px;" maxlength="10" type="text" value="" id="new_order_min"/></div>
		<div class="fline w150"><span>Сумма заказа до (руб):</span><input style="width:100px;" maxlength="10" type="text" value="" id="new_order_max"/></div>
		<div class="ui-button-light" style="margin-left:160px;"id="button_add_record" style="width:165px;margin:5px 0px;"><span>Добавить</span></div>
		<div class="ui-button-light" style="margin-left:10px;" id="button_add_cancel"><span>Отмена</span></div>
	</div>


</div>