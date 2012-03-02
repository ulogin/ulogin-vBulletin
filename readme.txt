=== uLogin - виджет авторизации через социальные сети ===
Donate link: http://ulogin.ru/
Tags: ulogin, login, social, authorization
Requires at least: 3.x.x
Tested up to: 4.x.x
Stable tag: 1.6
License: GPL3
Форма авторизации uLogin через социальные сети. Улучшенный аналог loginza.

== Description ==

uLogin — это инструмент, который позволяет пользователям получить единый доступ к различным Интернет-сервисам без необходимости повторной регистрации,
а владельцам сайтов — получить дополнительный приток клиентов из социальных сетей и популярных порталов (Google, Яндекс, Mail.ru, ВКонтакте, Facebook и др.)

== Installation ==

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
