<div class="page_admin_clients_approve" id="page_admin_clients_approve">


	<div class="bigblock" id="bigblock_wrapper">
		<div class="titlebar normal">
			<h3 id="bigblock_title">Publishers ankets</h3>
		</div>
		<div class="contentwrapper">
			<div class="contentareafull">
			<!--Content-->

				<div class="filter_area">

					<div class="filter_line">

						<div class="left">
							<select id="filter_approved">
								<option value="1">Approved</option>
								<option value="0" selected="selected">Not approved</option>
							</select>
						</div>

						<div class="left">
							<select id="filter_confirmed">
								<option value="0">E-mail not confirmed</option>
								<option value="1" selected="selected">E-mail confirmed</option>
							</select>
						</div>
						<div class="left">
							<input type="text" value="" id="filter_search_name" placeholder="Search in ankets..."/>
							<div class="ui-button-light" id="filter_button" style="margin:0px;"><span class="ileft icon_filter">Search</span></div>
						</div>

					</div>

				</div>


				<div class="clients_area">
					<div id="clients_table"></div>
					<div id="clients_none" style="display:none;">
						<h1 class="errorpage_title">Ankets not found</h1>
					</div>
				</div>



			<!--Content-->
			</div>
		</div>
	</div>



</div>