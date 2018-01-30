<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
	<head>
		<title>DTBox panel</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<meta http-equiv="Content-Type" content="text; charset=utf-8" />
		<meta charset="UTF-8">
		<link rel="stylesheet" type="text/css" href="/client/css/ui-reset.css"/>
		<link rel="stylesheet" type="text/css" href="/client/css/ui-index.css"/>
		<link rel="stylesheet" type="text/css" href="/client/css/ui-admin.css"/>
		<script type="text/javascript">
			var COOKIE_PREFIX = "{%SESSION_COOKIE%}";
			var INTERFACE_LANG = "{%USER_LANGUAGE%}";
			var INTERFACE_THEME = "{%USER_THEME%}";
			var INTERFACE_IMAGES = "/client/images";
			var INTERFACE_CSS = "/client/css";
			var INTERFACE_MODULE = "{%REQUEST_MODULE%}";
			var REQUEST_INFO = {%REQUEST_INFO%};
		</script>
		<script type="text/javascript" src="/client/js/lib/__core.js"></script>
		<script type="text/javascript" src="/client/js/lib/__more.js"></script>
		<script type="text/javascript" src="/client/js/lib/__utils.js"></script>
		<script type="text/javascript" src="/client/js/lib/__app_adm.js"></script>
		<script type="text/javascript" src="/client/js/lib/axRequest.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsSlideShow.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsMessage.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTable.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsPicker.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsValidator.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsSlimbox.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTreeMenu.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTreeMenuDesign.class.js"></script>
		<script type="text/javascript" src="/client/js/lib/jsTabPanel.class.js"></script>
		<script type="text/javascript" src="/client/js/Admin/stackController.js"></script>
		<script type="text/javascript" src="/client/js/Admin/jsMainMenu.class.js"></script>
		<script type="text/javascript" src="/client/js/Admin/jsCatalog.class.js"></script>
		<script type="text/javascript" src="/client/js/Admin/jsPropertiesTree.class.js"></script>
		<script type="text/javascript" src="/client/js/tinymce/tinymce.min.js"></script>

	</head>
	<body>

		<div id="user_interactive_editor"></div>

		<div id="spinner">
			<div>
				<img src="/client/images/spinner_big.gif" alt=""/><br/>
				<span class="logotext">Loading...</span>
			</div>
		</div>
		
		<div class="userbar"><!--userbar begin-->
		
			<div class="profile">
				<div class="avatar">
					<div class="admin"></div>
				</div>
				<div class="profileinfo">
					<h3 class="usermodule">Панель администрирования</h3>
					<h3 class="username">{%USER::name%}</h3>
					<span class="ip_addr">{%REQUEST::ip_addr%}</span>
					<div class="clear"></div>
				</div>
			</div>

			<div id="navigation_area"></div>

		</div><!--userbar end-->


		<div class="leftmenu">
			<div class="titlebar"><h3>Навигация</h3></div>
				<div class="wrapper">
						<div class="menuarea">
							<div class="menubox" id="leftmenu">
							</div>
						</div>
						<div class="copyright">
							<a href="/admin/about">Copyright &copy; 2015 DTBox.ru</a>
						</div>
				</div>
		</div>

		<div id="adminarea"></div>

	</body>
</html>