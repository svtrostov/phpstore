<html>
	<head>
		<title>Вход</title>
		<meta http-equiv="X-UA-Compatible" content="IE=9"/>
		<link rel="stylesheet" type="text/css" href="/client/css/ui-reset.css"/>
		<link rel="stylesheet" type="text/css" href="/client/css/ui-login.css"/>
		<script type="text/javascript">
			var COOKIE_PREFIX = "{%SESSION_COOKIE%}";
			var INTERFACE_LANG = "{%USER_LANGUAGE%}";
			var INTERFACE_THEME = "{%USER_THEME%}";
			var INTERFACE_MODULE = "{%REQUEST_MODULE%}";
			var APP_AUTOCREATE_DISABLE = true;
		</script>
		
	</head>
	<body>
	
		<div class="login_area">
			<div class="logo">DTBox panel</div>
			{%error%}
			<div class="loginbox">
				<form action="/admin/login/?key={%TIMESTAMP%}" method="post" name="frm{%TIMESTAMP%}" id="frm{%TIMESTAMP%}" autocomplete="off">
					<input type="hidden" name="key" value="{%TIMESTAMP%}" />
					<input type="hidden" name="page" value="{%GET::page,1%}" />
					<div class="cls{%TIMESTAMP%} username_field"><input autocomplete="off" type="text" name="u{%TIMESTAMP%}" placeholder="Имя пользователя" class="cls{%TIMESTAMP%} required" /></div>
					<input type="password" style="display:none;" name="pfake{%TIMESTAMP%}">
					<div class="cls{%TIMESTAMP%} password_field"><input autocomplete="off" type="password" name="p{%TIMESTAMP%}" placeholder="Пароль" class="cls{%TIMESTAMP%} required"/></div>
					<div class="buttonline">
						<input type="submit" class="loginbutton" autocomplete="off" value="Вход"/>
						<label for="remember">Запомнить меня <input type="checkbox" name="r{%TIMESTAMP%}" value="1"/></label>
					</div>
				</form>
			</div>
		</div>

	</body>	

</html>
