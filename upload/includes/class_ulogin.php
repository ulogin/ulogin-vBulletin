<?php

/** 
 * Auth via uLogin.ru
 * @package vBulletin
 * @subpackage uLogin Product
 * @author uLogin http://ulogin.ru team@ulogin.ru 
 */

require_once(DIR . '/includes/class_JSON.php'); // http://pear.php.net/pepr/pepr-proposal-show.php?id=198
require_once(DIR . '/includes/functions_login.php'); // vB
require_once(DIR . '/includes/functions_user.php'); // vB

class uLogin
{
	private $vb = NULL; // vB main class ($vbulletin)
	private $db = NULL; // vB database class ($vbulletin->db, $db)
	private $token = NULL; // uLogin token
	private $user = NULL; // uLogin user data
	private $back_url = NULL; // back url
	
	private $max_level = 5; // max nesting level (method: __fetch_random_name)

	public function __construct($vb = NULL)
	{
		$this->vb = $vb;
		$this->db = $vb->db;
		$this->vb->input->clean_gpc('p', 'token', TYPE_STR);
		$this->vb->input->clean_gpc('g', 'back', TYPE_STR);
		
		if ($this->vb->GPC['token'])
		{
			$this->token = $this->vb->GPC['token'];
		}
		
		$this->back_url = base64_decode($this->vb->GPC['back']);
		
		if (!$this->back_url ||
		parse_url($this->back_url, PHP_URL_HOST) != $_SERVER['HTTP_HOST'] || 
		strpos($this->back_url, 'login.php') !== false ||
		strpos($this->back_url, 'ulogin.php') !== false || 
		strpos($this->back_url, 'register.php') !== false)
		{
			$this->back_url = $this->vb->options['forumhome'] . '.php' . $this->vb->session->vars['sessionurl_q'];
		}
		
		$this->__get_user();
	}
	
	/**
	 * Get current user email or generate random
	 * 
	 * @access 	private
	 * @param 	bool 		$random		if true will generate random email
	 * @return 	string				return email
	 */
	private function __fetch_random_email($random = false)
	{
		if (!$random && $this->user['email'])
		{
			if ($user = $this->vb->db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE email = '" . $this->vb->db->escape_string($this->user['email']) . "'"))
			{
				return $this->__fetch_random_email(true);
			}
			
			return $this->user['email'];
		}
		
		return 'uLogin' . $this->__random1(20) . '@' . $_SERVER['HTTP_HOST'].'.org';
	}
	
	/**
	 * Get current user name or generate random
	 * 
	 * @access 	private
	 * @param 	string 		$name		if set will append random string
	 * @param	int		$level		the higher the value the more random string will be in result
	 * @return 	string				return user name
	 */
	private function __fetch_random_name($name = '', $level = 0)
	{
		if ($level == $this->max_level)
		{
			return '';
		}
		
		if ($name)
		{
			$name = $name . $this->__random1(1);
		}
		else if ($this->user['first_name'] && $this->user['last_name'])
		{
			$name = $this->user['last_name'] . ' ' . $this->user['first_name'];
		}
		else if ($this->user['first_name'])
		{
			$name = $this->user['first_name'];
		}
		else if ($this->user['last_name'])
		{
			$name = $this->user['last_name'];
		}
		else
		{
			$name = 'uLogin' . $this->__random1(5);
		}
		
$iso = array(
   "Є"=>"YE","І"=>"I","Ѓ"=>"G","і"=>"i","№"=>"#","є"=>"ye","ѓ"=>"g",
   "А"=>"A","Б"=>"B","В"=>"V","Г"=>"G","Д"=>"D",
   "Е"=>"E","Ё"=>"YO","Ж"=>"ZH",
   "З"=>"Z","И"=>"I","Й"=>"J","К"=>"K","Л"=>"L",
   "М"=>"M","Н"=>"N","О"=>"O","П"=>"P","Р"=>"R",
   "С"=>"S","Т"=>"T","У"=>"U","Ф"=>"F","Х"=>"X",
   "Ц"=>"C","Ч"=>"CH","Ш"=>"SH","Щ"=>"SHH","Ъ"=>"'",
   "Ы"=>"Y","Ь"=>"","Э"=>"E","Ю"=>"YU","Я"=>"YA",
   "а"=>"a","б"=>"b","в"=>"v","г"=>"g","д"=>"d",
   "е"=>"e","ё"=>"yo","ж"=>"zh",
   "з"=>"z","и"=>"i","й"=>"j","к"=>"k","л"=>"l",
   "м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
   "с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"x",
   "ц"=>"c","ч"=>"ch","ш"=>"sh","щ"=>"shh","ъ"=>"",
   "ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya","«"=>"","»"=>"","—"=>"-"
  );
		$name=strtr($name, $iso);
		if ($user = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE username = '" . $this->db->escape_string($name) . "'"))
		{
			return $this->__fetch_random_name($name, ($level + 1));
		}
		
		return $name;
	}
	
	/**
	 * Get user from ulogin.ru by token
	 * 
	 * @access 	private
	 * @return 	mixed				if token expired or some errors occurred will return NULL else will return user data
	 */
	private function __get_user()
	{
		if ($this->user)
		{
			return $this->user;
		}
		
		if ($this->token)
		{
			$info = file_get_contents('http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $_SERVER['HTTP_HOST']);
			
			if (function_exists('json_decode'))
			{
				$this->user = json_decode($info, true);
			}
			else
			{
				$json = new Services_JSON();
				
				$this->user = $json->decode($info, true);
			}
			
			return $this->user;
		}
		
		return NULL;
	}
	
	/**
	 * Generate random string
	 * 
	 * @access 	private
	 * @param	int		$length		length of generating string
	 * @return 	string				return generated string
	 */
	private function __random1($length = 10)
	{
		$random = '';
		
		for ($i = 0; $i < $length; $i++)
		{
			$random += chr(rand(48, 57));
		}
		
		return $random;
	}
	
	/**
	 * Auth user
	 * 
	 * @access 	public
	 * @return 	bool				if user authorized return true, else return false
	 */
	public function auth()
	{
		if (!$this->user)
		{
			return false;
		}
		
		if (!$user = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->user['identity']) . "'"))
		{
			return false;
		}
		
		$this->vb->userinfo = fetch_userinfo($user['userid']);
		
		if (!$this->vb->userinfo['username'])
		{
			$this->db->query_write("DELETE FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->user['identity']) . "'");
		
			return false;
		}
		
		vbsetcookie('userid', $this->vb->userinfo['userid'], true, true, true);
		vbsetcookie('password', md5($this->vb->userinfo['password'] . COOKIE_SALT), true, true, true);
		
		process_new_login('', true, '');
		
		return true;
	}
	
	/**
	 * Check users access
	 * 
	 * @access 	public
	 * @return 	bool				if user have access return true, else return false
	 */
	public function check_access()
	{

		if (!$this->vb->options['ulogin_enable'] || !$this->token || $this->vb->userinfo['userid']) // || parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) != 'ulogin.ru' || parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST) != 'ulogin.ru' 
		{
			return false;
		}
		
		return true;
	}
	
	/**
	 * Get back url
	 * 
	 * @access 	public
	 * @return 	string				return back url
	 */
	public function get_url()
	{
		return $this->back_url;
	}
	
	/**
	 * Register user
	 * 
	 * @access 	public
	 */
	public function register()
	{
		if (!$this->vb->options['allowregistration'] && $this->vb->options['ulogin_vb_register'])
		{
			eval(standard_error(fetch_error('noregister')));
		}
		
		$username = '';
		$email = '';
		
		if ($this->user)
		{
			$username = $this->__fetch_random_name();
			$email = $this->__fetch_random_email();
		}
		
		$userdata =& datamanager_init('User', $this->vb, ERRTYPE_ARRAY);
		
		if ($this->vb->options['ulogin_vb_register'])
		{
			if ($this->vb->options['verifyemail'])
			{
				$newusergroupid = 3;
			}
			else if ($this->vb->options['moderatenewmembers'])
			{
				$newusergroupid = 4;
			}
			else
			{
				$newusergroupid = 2;
			}
		}
		else
		{
			$newusergroupid = iif($this->vb->options['ulogin_groupid'], $this->vb->options['ulogin_groupid'], 2);
		}
		
		$userdata->set('username', $username);
		$userdata->set('email', $email);
		$userdata->set('password', fetch_random_password(10));
		$userdata->set('usergroupid', $newusergroupid);
		$userdata->set_usertitle('', false, $this->vb->usergroupcache["$newusergroupid"], false, false);
		$userdata->set('ipaddress', IPADDRESS);
		$userdata->set('languageid', $this->vb->userinfo['languageid']);
		
		$userdata->pre_save();

		if ($userdata->errors)
		{
			$errorlist = '';
			
			foreach ($userdata->errors AS $index => $error)
			{
				$errorlist .= '<li>' . $error . '</li>';
			}
			
			eval(standard_error($errorlist));
		}
		
		$this->vb->userinfo['userid'] = $userid = $userdata->save();
		
		if (!$userid)
		{
			return false;
		}
		
		$this->db->query_write("INSERT INTO " . TABLE_PREFIX . "ulogin VALUES (NULL, " . $userid . ", '" . $this->db->escape_string($this->user['identity']) . "')");
				
		$userinfo = fetch_userinfo($userid);
		$this->vb->session->created = false;
				
		process_new_login('', false, '');
				
		if ($this->vb->options['newuseremail'] != '')
		{
			$ipaddress = IPADDRESS;
					
			eval(fetch_email_phrases('newuser', 0));
					
			$newemails = explode(' ', $this->vb->options['newuseremail']);
					
			foreach ($newemails AS $toemail)
			{
				if (trim($toemail))
				{
					vbmail($toemail, $subject, $message);
				}
			}
		}
		
		if ($this->vb->options['verifyemail'])
		{
			$activateid = build_user_activation_id($userid, (($this->vb->options['moderatenewmembers'] || $this->vb->GPC['coppauser']) ? 4 : 2), 0);
			eval(fetch_email_phrases('activateaccount'));
			vbmail($email, $subject, $message, true);
		}
		else if ($newusergroupid == 2)
		{
			if ($this->vb->options['welcomemail'])
			{
				eval(fetch_email_phrases('welcomemail'));
				vbmail($email, $subject, $message);
			}
		}
		
		$this->vb->userinfo =& $userinfo;
		
		if ($this->vb->options['ulogin_vb_register'])
		{
			if ($this->vb->options['verifyemail'])
			{
				eval(standard_error(fetch_error('registeremail', $username, $email, $this->back_url), '', false));
			}
			else
			{
				if ($this->vb->options['moderatenewmembers'])
				{
					eval(standard_error(fetch_error('moderateuser', $username, $this->back_url), '', false));
				}
				else
				{
					eval(standard_error(fetch_error('registration_complete', $username, $this->vb->session->vars['sessionurl'], $this->back_url), '', false));
				}
			}
		}
		else
		{
			eval(standard_error(fetch_error('registration_complete', $username, $this->vb->session->vars['sessionurl'], $this->back_url), '', false));
		}
	}
}

?>
