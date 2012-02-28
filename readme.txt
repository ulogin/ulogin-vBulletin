Пакет: vBulletin
Продукт: Авторизация через социальные сети при помощи сервиса uLogin.ru (ID: ulogin)
Автор: uLogin http://ulogin.ru team@ulogin.ru
Описание: Авторизация (регистрация нового пользователя, а затем авторизация) на сайте через сервис uLogin.ru для vBulletin.
Лицензия: GPL2
Установка:
	1) Скопировать все файлы и папки находящиеся в папке /upload в архиве.
	2) Импортировать продукт product-ulogin.xml, находящийся в корне архива, в vBulletin с помощью администраторской панели.
	3) Правка шаблона с помощью администраторской панели.
		3.1) Для vBulletin 4.x.x:
		Шаблон:		header
		Найти:
		
		<vb:else />
			<ul class="nouser">
			<vb:if condition="$show['registerbutton']">
				<li><a href="register.php{vb:raw session.sessionurl_q}" rel="nofollow">{vb:rawphrase register}</a></li>
			</vb:if>
				<li><a rel="help" href="faq.php{vb:raw session.sessionurl_q}">{vb:rawphrase help}</a></li>
				<li>
		
		Ниже добавить:
		
				{vb:raw template_hook.ulogin}
				
		3.2) Для vBulletin 3.x.x:
		Шаблон:		navbar
		Найти:
		
		<tr>
			<td class="smallfont"><label for="navbar_password">$vbphrase[password]</label></td>
			<td><input type="password" class="bginput" style="font-size: 11px" name="vb_login_password" id="navbar_password" size="10" tabindex="102" /></td>
			<td><input type="submit" class="button" value="$vbphrase[log_in]" tabindex="104" title="$vbphrase[enter_username_to_login_or_register]" accesskey="s" /></td>
		</tr>
		
		Ниже добавить:
		
		<tr>
			<td colspan="3">$template_hook[ulogin]</td>
		</tr>
			
	4) По необходимости изменить настройки продукта средствами администраторской панели.
