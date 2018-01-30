<div class="page_client_add">


	<div class="bigblock" id="client_add_wrapper">
		<div class="titlebar"><h3 id="client_add_title">Add a new Publisher</h3></div>
		<div class="contentwrapper">
			<div class="contentareafull">

				<div id="tabs_area" class="tabs_area absolute">
				<!--tabs_area-->

					<ul class="tabs">
						<li class="tab">Publisher info</li>
					</ul>


					<div class="tab_content">
					<!--anket-->

						<div class="tab_content_area">
							<div id="client_form">
								<div class="fline w200"><span>Full name*:</span><input style="width:200px;" type="text" maxlength="128" value="" id="info_name"/></div>
								<div class="fline w200"><span>E-Mail address*:</span><input style="width:200px;" placeholder="Example: example@example.ru" maxlength="64" type="text" value="" id="info_email"/></div>
								<div class="splitline"></div>
								<div class="fline w200">
									<span>Login*:</span><input style="width:150px;" type="text" maxlength="255" value="" id="info_username"/>
									<span id="username_check"></span>
								</div>
								<div class="fline w200"><span>Password*:</span><input style="width:150px;" type="password" maxlength="255" value="" id="info_password"/></div>
								<div class="fline w200"><span>Confirm password*:</span><input style="width:150px;" type="password" maxlength="255" value="" id="info_password2"/></div>
								<div class="fline w200"><span>Account status:</span><select id="info_is_inactive" style="width:112px;"><option value="1">Locked</option><option value="0" selected="selected">Active</option></select></div>
								<div class="splitline"></div>
								<h1>Publisher Revenue percent</h1>
								<div class="fline w200"><span>Percent value (0-100)*:</span><input style="width:100px;" type="text" maxlength="3" value="0" id="info_revenue_percent"/></div>
								<div class="splitline"></div>
								<div class="fline w200"><span>ZIP-code:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_zip"/></div>
								<div class="fline w200"><span>Country:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_country"/></div>
								<div class="fline w200"><span>City:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_city"/></div>
								<div class="fline w200"><span>Address:</span><input style="width:200px;" type="text" maxlength="32" value="" id="info_address"/></div>
								<div class="fline w200"><span>Phone:</span><input style="width:200px" placeholder="Example: 8 (800) 123-45-67" maxlength="64" type="text" value="" id="info_phone"/></div>
								<div class="fline w200"><span>Description:</span><p><textarea style="width:400px;height:100px;" id="info_description"></textarea></p></div>
								<div class="fline w200"><span>Domains:</span><p><textarea style="width:400px;height:100px;" id="info_domains"></textarea></p></div>

								<div id="client_add_action_area">
									<div class="splitline"></div>
									<div  style="margin-left:210px;margin-top:10px;">
										<input type="checkbox" value="1" id="send_notification"/> <label for="send_notification">Send an email with notification of account creation</label>
										<br/>
										<div class="ui-button-light" style="margin:5px 0px;" id="client_add_button"><span>Create Publisher account</span></div>
									</div>
								</div>
							</div>
							<div id="client_complete" style="display:none;">
								<br>
								<h1>Publisher account created</h1>
								<div class="splitline"></div>
								<div class="iline w200"><span>Publisher ID:</span><p id="complete_client_id"></p></div>
								<div class="iline w200"><span>Name:</span><p id="complete_name"></p></div>
								<div class="iline w200"><span>E-Mail:</span><p id="complete_email"></p></div>
								<div class="iline w200"><span>Login:</span><p id="complete_username"></p></div>
								<div class="iline w200"><span>Password:</span><p id="complete_password"></p></div>
								<div class="splitline"></div>
								<div class="ui-button-light" style="margin-left:210px;width:200px;" id="client_profile_button"><span>Go to publisher</span></div>
							</div>
						</div>

					<!--anket-->
					</div>


				<!--tabs_area-->
				</div>

				<div id="tabs_none" style="display:none;">
					<h1 class="errorpage_title">Client not found</h1>
				</div>

			</div>
		</div>
	</div>



</div>