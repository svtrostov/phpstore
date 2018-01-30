<div class="page_admin_news" id="page_admin_news">


	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Новости</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->

				<div id="news_start">
					<br><br><br>
					<h1 class="big_title">Загрузка данных...</h1>
				</div>

				<div id="news_area">
					<div class="tool_area">
						<div class="left">
							<div class="ui-button-light" id="button_new_news" style="margin:0px;"><span class="ileft icon_add">Добавить новость</span></div>
						</div>
					</div>

					<div class="news_area">
						<div id="news_table"></div>
						<div id="news_none" style="display:none;">
							<h1 class="errorpage_title">Новостей нет</h1>
						</div>
					</div>
				</div>


			<!--Content-->
			</div>
		</div>
	</div>



	<div class="bigblock" id="neditor" style="display:none;">
		<input type="hidden" id="news_id" value="">
		<div class="titlebar"><h3 id="neditor_title">Новость</h3></div>
		<div class="contentwrapper">
			<div class="contentarea">

				<div class="neditor_area">

					<b>Отображение новости:</b><br>
					<select id="news_enabled" style="width:150px;"><option value="0">Скрыть новость</option><option value="1">Показывать новость</option></select>

					<br><br>


					<b>Дата новости:</b><br>
					<input type="text" value="" id="news_date" class="calendar_input" style="width:120px;"/>

					<br><br>

					<b>Заголовок:</b><br>
					<input type="text" value="" id="news_theme" style="width:500px;"/>

					<br><br>

					<b>Текст новости:</b><br>
					<textarea id="news_content" name="news_content" style="width:100%;height:150px;" class="tinymce"></textarea>

				</div>


			</div>
			<div class="buttonarea">
				<div class="ui-button" id="neditor_complete_button"><span>Сохранить</span></div>
				<div class="ui-button" id="neditor_cancel_button"><span>Закрыть</span></div>
			</div>
		</div>
	</div>


</div>