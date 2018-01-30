<div class="page_anket_info">


	<div class="bigblock" id="anket_info_wrapper">
		<div class="titlebar"><h3 id="anket_info_title">New Publisher anket</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Publisher anket</li>
					</ul>


					<div class="tab_content">
					<!--anket-->

						<div class="tab_content_area" id="anket_form_area">
							<div class="splitline"></div>
							<h1>Anket info</h1>
							<div class="fline w200"><span>ID:</span><p id="info_id"></p></div>
							<div class="fline w200"><span>Is approved:</span><p id="info_is_approved"></p></div>
							<div class="fline w200" id="approved_time_label"><span>Approved time:</span><p id="info_approve_time"></p></div>
							<div class="ui-button-light" style="margin-left:210px;display:none;width:200px;" id="client_profile_button2"><span>Go to publisher</span></div>
							<div class="splitline"></div>
							<h1>Publisher info</h1>
							<div class="fline w200"><span>Full name:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_name"/></div>
							<div class="fline w200"><span>ZIP-code:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_zip"/></div>
							<div class="fline w200"><span>Country:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_country"/></div>
							<div class="fline w200"><span>City:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_city"/></div>
							<div class="fline w200"><span>Address:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_address"/></div>
							<div class="fline w200"><span>Phone:</span><input style="width:200px" placeholder="Пример: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
							<div class="fline w200"><span>E-Mail address:</span><input style="width:200px;" placeholder="Example: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
							<div class="fline w200"><span>E-Mail confirmed:</span><input type="checkbox" value="1" id="info_is_confirmed"/></div>
							<div class="fline w200"><span>Description:</span><p><textarea style="width:400px;height:150px;" id="info_description"></textarea></p></div>
							<div class="fline w200"><span>Domains:</span><p><textarea style="width:400px;height:150px;" id="info_domains"></textarea></p></div>

							<div id="anket_checked_form">
								<div class="splitline"></div>
								<div  style="margin-left:210px;margin-top:10px;">
									<h1>Approval</h1>
									<b>This publisher has not yet been approved.<br/></b>
									<br/>
									<input type="checkbox" value="1" id="send_notification" checked="checked"/> <label for="send_notification">Send an email with notification of account creation</label>
									<br/>
									<div class="ui-button-light" style="margin:5px 0px;" id="anket_approve_button"><span>Approve and create publisher account</span></div>
								</div>
							</div>

						</div>

					<!--anket-->
					</div>


				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Anket not found</h1>
				</div>

			</div>
		</div>
	</div>



</div>