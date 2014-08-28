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

    public function __construct($vb = NULL)
    {
        $this->vb = $vb;
        $this->db = $vb->db;
        $this->vb->input->clean_gpc('p', 'token', TYPE_STR);
        $this->vb->input->clean_gpc('g', 'back', TYPE_STR);
        $this->vb->input->clean_gpc('g', 'ident', TYPE_STR);

        if ($this->vb->GPC['token']) {
            $this->token = $this->vb->GPC['token'];
        }

        $this->back_url = base64_decode($this->vb->GPC['back']);

        if (!$this->back_url ||
            parse_url($this->back_url, PHP_URL_HOST) != $_SERVER['HTTP_HOST'] ||
            strpos($this->back_url, 'login.php') !== false ||
            strpos($this->back_url, 'ulogin.php') !== false ||
            strpos($this->back_url, 'register.php') !== false
        ) {
            $this->back_url = $this->vb->options['forumhome'] . '.php' . $this->vb->session->vars['sessionurl_q'];
        }

        $this->_get_user();
    }

    /**
     * Get user with same email and return user id
     *
     * @access    private
     * @return    int        user id
     */
    private function _check_email()
    {

        if ($this->user['verified_email'] != 1) {

            return false;

        }

        $email = $this->user['email'];
        $result = $this->db->query_first("SELECT userid FROM " . TABLE_PREFIX . "user WHERE email = '" . $email . "'");

        return $result['userid'];

    }

    /**
     * @access 	private
     * @return 	bool
     */
    private function _check_mail()
    {
        if ($this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE email = '" . $this->db->escape_string($this->user['email']) . "'")) {
            return true;
        }

        return false;
    }

    /**
     * Get user from ulogin.ru by token
     *
     * @access    private
     * @return    mixed                if token expired or some errors occurred will return NULL else will return user data
     */
    private function _get_user()
    {
        if ($this->user) {
            return $this->user;
        }

        if ($this->token) {
            $this->_get_user_from_token();
        }

        return NULL;
    }

    /**
     * Perform request
     *
     * @access private
     * @return void
     */
    private function _get_user_from_token()
    {

        if (function_exists('file_get_contents') && ini_get('allow_url_fopen')) {

            $response = file_get_contents('http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $_SERVER['HTTP_HOST']);

        } elseif (in_array('curl', get_loaded_extensions())) {

            $request = curl_init('http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $_SERVER['HTTP_HOST']);
            curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
            $response = curl_exec($request);

        } else {

            return;

        }

        if ($response) {

            if (function_exists('json_decode')) {

                $this->user = json_decode($response, true);

            } else {

                $json = new Services_JSON();
                $this->user = $json->decode($response, true);

            }

        }

    }

    /**
     * Attach uLogin profile to profile of current user
     *
     * @access    public
     * @return    bool
     */
    public function attach()
    {

        if (!$this->user) {
            return false;
        }

        if (!$user = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->user['identity']) . "'")) {

            $this->db->query_write("INSERT INTO " . TABLE_PREFIX . "ulogin (userid, identity) VALUES (" . $this->vb->userinfo['userid'] . ", '" . $this->db->escape_string($this->user['identity']) . "')");

        }

        $user_id = $user['userid'];

        $this->_update_attached_profile($user_id);

        return true;
    }

    /**
     * Update attached profile
     *
     * @access    public
     * @param int $user_id
     * @return    void
     */
    private function _update_attached_profile($user_id = 0)
    {

        if ($user_id != $this->vb->userinfo['userid'] && $user_id > 1) {

            $this->db->query_write("UPDATE " . TABLE_PREFIX . "ulogin SET userid=" . $this->vb->userinfo['userid'] . " WHERE identity='" . $this->db->escape_string($this->user['identity']) . "'");

            if ($this->vb->options['ulogin_attach_del']) {

                $this->db->query_write("UPDATE " . TABLE_PREFIX . "ulogin SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $user_id);
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "album SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "attachment SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "event SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "reminder SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "pollvote SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "post SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "subscribediscussion SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "subscribeevent SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "subscribeforum SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "subscribegroup SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "subscribethread SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));
                $this->db->query_write("UPDATE " . TABLE_PREFIX . "usergroupleader SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));

                if ($this->vb->versionnumber < 4)
                    $this->db->query_write("UPDATE " . TABLE_PREFIX . "picture SET userid=" . $this->vb->userinfo['userid'] . " WHERE userid=" . $this->db->escape_string($user_id));

                $this->delete_user($user_id);

            }

        }

    }

    /**
     * Delete user profile
     *
     * @access    public
     * @param int $user_id
     * @return    void
     */
    private function delete_user($user_id = 0)
    {

        $user = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE userid = '" . $user_id . "'");
        $userdm =& datamanager_init('User', $this->vb, ERRTYPE_ARRAY);
        $userdm->set_existing($user);
        $userdm->delete();
        $userdm->pre_save();

        if (empty($userdm->errors)) {
            $userdm->save();
        } else {
            die(print_r($userdm->errors));
        }

    }

    /**
     * Detach uLogin profile
     *
     * @access    public
     * @return    bool
     */
    public function detach()
    {

        $this->db->query_write("DELETE FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->vb->GPC['ident']) . "' AND userid=" . $this->vb->userinfo['userid']);

    }

    /**
     * Auth user
     *
     * @access    public
     * @return    bool                if user authorized return true, else return false
     */
    public function auth()
    {
        if (!$this->user) {
            return false;
        }

        if (!$user = $this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->user['identity']) . "'")) {
            $user['userid'] = $this->_check_email();

            if ($user['userid'] && $this->vb->options['ulogin_auto_attach']) {

                $this->db->query_write("INSERT INTO " . TABLE_PREFIX . "ulogin (userid, identity) VALUES (" . $user['userid'] . ", '" . $this->db->escape_string($this->user['identity']) . "')");

            } else {

                return false;

            }
        }

        $this->vb->userinfo = fetch_userinfo($user['userid']);

        if (!$this->vb->userinfo['username']) {
            $this->db->query_write("DELETE FROM " . TABLE_PREFIX . "ulogin WHERE identity = '" . $this->db->escape_string($this->user['identity']) . "'");
            return false;
        }

        $email_parts = explode('@', $this->user['email']);
        $generated_email = $email_parts[0] . '+' . $this->vb->userinfo['username'] . '@' . $email_parts[1];
        if ($this->vb->userinfo['email'] == $generated_email && $this->user['verified_email'] == 1 && $this->vb->options['ulogin_auto_attach']) {

            if ($id = $this->_check_email()) {

                $this->vb->userinfo = fetch_userinfo($id);
                $this->attach();

            } else {

                $this->db->query_write("UPDATE " . TABLE_PREFIX . "user SET email = '" . $this->user['email'] . "' WHERE userid = " . $user['userid']);

            }

        }

        vbsetcookie('userid', $this->vb->userinfo['userid'], true, true, true);
        vbsetcookie('password', md5($this->vb->userinfo['password'] . COOKIE_SALT), true, true, true);

        process_new_login('', true, '');

        return true;
    }

    /**
     * @param string $first_name
     * @param string $last_name
     * @param string $nickname
     * @param string $bdate (string in format: dd.mm.yyyy)
     * @param array $delimiters
     * @return string
     */
    private function generateNickname($first_name, $last_name = "", $nickname = "", $bdate = "", $delimiters = array('.', '_'))
    {
        $delim = array_shift($delimiters);

        $first_name = $this->translitIt($first_name);
        $first_name_s = substr($first_name, 0, 1);

        $variants = array();
        if (!empty($nickname))
            $variants[] = $nickname;
        $variants[] = $first_name;
        if (!empty($last_name)) {
            $last_name = $this->translitIt($last_name);
            $variants[] = $first_name . $delim . $last_name;
            $variants[] = $last_name . $delim . $first_name;
            $variants[] = $first_name_s . $delim . $last_name;
            $variants[] = $first_name_s . $last_name;
            $variants[] = $last_name . $delim . $first_name_s;
            $variants[] = $last_name . $first_name_s;
        }
        if (!empty($bdate)) {
            $date = explode('.', $bdate);
            $variants[] = $first_name . $date[2];
            $variants[] = $first_name . $delim . $date[2];
            $variants[] = $first_name . $date[0] . $date[1];
            $variants[] = $first_name . $delim . $date[0] . $date[1];
            $variants[] = $first_name . $delim . $last_name . $date[2];
            $variants[] = $first_name . $delim . $last_name . $delim . $date[2];
            $variants[] = $first_name . $delim . $last_name . $date[0] . $date[1];
            $variants[] = $first_name . $delim . $last_name . $delim . $date[0] . $date[1];
            $variants[] = $last_name . $delim . $first_name . $date[2];
            $variants[] = $last_name . $delim . $first_name . $delim . $date[2];
            $variants[] = $last_name . $delim . $first_name . $date[0] . $date[1];
            $variants[] = $last_name . $delim . $first_name . $delim . $date[0] . $date[1];
            $variants[] = $first_name_s . $delim . $last_name . $date[2];
            $variants[] = $first_name_s . $delim . $last_name . $delim . $date[2];
            $variants[] = $first_name_s . $delim . $last_name . $date[0] . $date[1];
            $variants[] = $first_name_s . $delim . $last_name . $delim . $date[0] . $date[1];
            $variants[] = $last_name . $delim . $first_name_s . $date[2];
            $variants[] = $last_name . $delim . $first_name_s . $delim . $date[2];
            $variants[] = $last_name . $delim . $first_name_s . $date[0] . $date[1];
            $variants[] = $last_name . $delim . $first_name_s . $delim . $date[0] . $date[1];
            $variants[] = $first_name_s . $last_name . $date[2];
            $variants[] = $first_name_s . $last_name . $delim . $date[2];
            $variants[] = $first_name_s . $last_name . $date[0] . $date[1];
            $variants[] = $first_name_s . $last_name . $delim . $date[0] . $date[1];
            $variants[] = $last_name . $first_name_s . $date[2];
            $variants[] = $last_name . $first_name_s . $delim . $date[2];
            $variants[] = $last_name . $first_name_s . $date[0] . $date[1];
            $variants[] = $last_name . $first_name_s . $delim . $date[0] . $date[1];
        }
        $i = 0;

        $exist = true;
        while (true) {
            if ($exist = $this->userExist($variants[$i])) {
                foreach ($delimiters as $del) {
                    $replaced = str_replace($delim, $del, $variants[$i]);
                    if ($replaced !== $variants[$i]) {
                        $variants[$i] = $replaced;
                        if (!$exist = $this->userExist($variants[$i])) {
                            break;
                        }
                    }
                }
            }
            if ($i >= count($variants) - 1 || !$exist)
                break;
            $i++;
        }

        if ($exist) {
            while ($exist) {
                $nickname = $first_name . mt_rand(1, 100000);
                $exist = $this->userExist($nickname);
            }
            return $nickname;
        } else
            return $variants[$i];
    }

    /**
     * @param $nickname
     * @return bool
     */
    private function userExist($nickname)
    {
        return !!($this->db->query_first("SELECT * FROM " . TABLE_PREFIX . "user WHERE username = '" . $this->db->escape_string($nickname) . "'"));
    }

    /**
     * @param $str
     * @return mixed|string
     */
    private function translitIt($str)
    {
        $tr = array(
            "А" => "a", "Б" => "b", "В" => "v", "Г" => "g",
            "Д" => "d", "Е" => "e", "Ж" => "j", "З" => "z", "И" => "i",
            "Й" => "y", "К" => "k", "Л" => "l", "М" => "m", "Н" => "n",
            "О" => "o", "П" => "p", "Р" => "r", "С" => "s", "Т" => "t",
            "У" => "u", "Ф" => "f", "Х" => "h", "Ц" => "ts", "Ч" => "ch",
            "Ш" => "sh", "Щ" => "sch", "Ъ" => "", "Ы" => "yi", "Ь" => "",
            "Э" => "e", "Ю" => "yu", "Я" => "ya", "а" => "a", "б" => "b",
            "в" => "v", "г" => "g", "д" => "d", "е" => "e", "ж" => "j",
            "з" => "z", "и" => "i", "й" => "y", "к" => "k", "л" => "l",
            "м" => "m", "н" => "n", "о" => "o", "п" => "p", "р" => "r",
            "с" => "s", "т" => "t", "у" => "u", "ф" => "f", "х" => "h",
            "ц" => "ts", "ч" => "ch", "ш" => "sh", "щ" => "sch", "ъ" => "y",
            "ы" => "y", "ь" => "", "э" => "e", "ю" => "yu", "я" => "ya"
        );
        if (preg_match('/[^A-Za-z0-9\_\-]/', $str)) {
            $str = strtr($str, $tr);
            $str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
        }
        return $str;
    }

    /**
     * Check users access
     *
     * @access    public
     * @return    bool                if user have access return true, else return false
     */
    public function check_access()
    {

        if (!$this->vb->options['ulogin_enable'] || !$this->token || $this->vb->userinfo['userid']) {
            return false;
        }

        return true;
    }

    /**
     * Check profile attach access
     *
     * @access    public
     * @return    bool                if user have access return true, else return false
     */
    public function check_profile_attach_access()
    {

        if (!$this->vb->options['ulogin_enable'] || !$this->token || !$this->vb->userinfo['userid']) {
            return false;
        }

        return true;
    }

    /**
     * Check profile detach access
     *
     * @access    public
     * @return    bool                if user have access return true, else return false
     */
    public function check_profile_detach_access()
    {

        if (!$this->vb->options['ulogin_enable'] || !$this->vb->userinfo['userid'] || !$this->vb->GPC['ident']) {
            return false;
        }

        return true;
    }

    /**
     * Get back url
     *
     * @access    public
     * @return    string                return back url
     */
    public function get_url()
    {
        return $this->back_url;
    }

    /**
     * Register user
     *
     * @access    public
     */
    public function register()
    {
        if (!$this->vb->options['allowregistration'] && $this->vb->options['ulogin_vb_register']) {
            eval(standard_error(fetch_error('noregister')));
        }

        $userdata = &datamanager_init('User', $this->vb, ERRTYPE_ARRAY);

        if ($this->vb->options['ulogin_vb_register']) {
            if ($this->vb->options['verifyemail']) {
                $newusergroupid = 3;
            } else if ($this->vb->options['moderatenewmembers']) {
                $newusergroupid = 4;
            } else {
                $newusergroupid = 2;
            }
        } else {
            $newusergroupid = iif($this->vb->options['ulogin_groupid'], $this->vb->options['ulogin_groupid'], 2);
        }

        ($addmember_process_hook = vBulletinHook::fetch_hook('register_addmember_process')) ? eval($addmember_process_hook) : false;

        if ($this->_check_mail()) {
            eval(standard_error('Данный email уже зарегистрирован на сайте.' .
                '<br>Если вы хотите войти в существующий аккаунт, вам необходимо подтвердить, что почтовый адрес принадлежит вам.' .
                '<script>uLogin.mergeAccounts("' . $this->token . '");</script>', '', false));
            return false;
        }

        $bdate = explode('.', $this->user['bdate']);
        $userdata->set('username', $this->generateNickname($this->user['first_name'], $this->user['last_name'], $this->user['nickname'], $this->user['bdate']));
        $userdata->set('email', $this->user['email']);
        $userdata->set('password', fetch_random_password(10));
        $userdata->set('usergroupid', $newusergroupid);
        $userdata->set_usertitle('', false, $this->vb->usergroupcache["$newusergroupid"], false, false);
        $userdata->set('ipaddress', IPADDRESS);
        $userdata->set('languageid', $this->vb->userinfo['languageid']);
        $userdata->set('birthday', $bdate[2] . '-' . $bdate[1] . '-' . $bdate[0]);

        $userdata->pre_save();

        if ($userdata->errors) {
            $errorlist = '';

            foreach ($userdata->errors AS $index => $error) {
                $errorlist .= '<li>' . $error . '</li>';
            }

            eval(standard_error($errorlist));
        }

        $this->vb->userinfo['userid'] = $userid = $userdata->save();

        if (!$userid) {
            return false;
        }

        $this->db->query_write("INSERT INTO " . TABLE_PREFIX . "ulogin VALUES (NULL, " . $userid . ", '" . $this->db->escape_string($this->user['identity']) . "')");

        $userinfo = fetch_userinfo($userid);
        $this->vb->session->created = false;

        process_new_login('', false, '');

        if ($this->vb->options['newuseremail'] != '') {
            $ipaddress = IPADDRESS;

            eval(fetch_email_phrases('newuser', 0));

            $newemails = explode(' ', $this->vb->options['newuseremail']);

            $email = $message = $subject = false;

            foreach ($newemails AS $toemail) {
                if (trim($toemail)) {
                    vbmail($toemail, $subject, $message);
                }
            }
        }

        if ($this->vb->options['verifyemail']) {
            $activateid = build_user_activation_id($userid, (($this->vb->options['moderatenewmembers'] || $this->vb->GPC['coppauser']) ? 4 : 2), 0);
            eval(fetch_email_phrases('activateaccount'));
            vbmail($email, $subject, $message, true);
        } else if ($newusergroupid == 2) {
            if ($this->vb->options['welcomemail']) {
                eval(fetch_email_phrases('welcomemail'));
                vbmail($email, $subject, $message);
            }
        }

        $this->vb->userinfo =& $userinfo;

        $this->user_pic();

        $username = '';

        if ($this->vb->options['ulogin_vb_register']) {
            if ($this->vb->options['verifyemail']) {
                eval(standard_error(fetch_error('registeremail', $username, $email, $this->back_url), '', false));
            } else {
                if ($this->vb->options['moderatenewmembers']) {
                    eval(standard_error(fetch_error('moderateuser', $username, $this->back_url), '', false));
                } else {
                    eval(standard_error(fetch_error('registration_complete', $username, $this->vb->session->vars['sessionurl'], $this->back_url), '', false));
                }
            }
        } else {
            eval(standard_error(fetch_error('registration_complete', $username, $this->vb->session->vars['sessionurl'], $this->back_url), '', false));
        }

        ($addmember_complete_hook = vBulletinHook::fetch_hook('register_addmember_complete')) ? eval($addmember_complete_hook) : false;

    }

    /**
     * @access  public
     * @return  void
     */
    public function user_pic()
    {
        if (isset($this->vb->userinfo['userid'])) {

            $filedata = '';
            $handler = fopen($this->user['photo'], 'rb');
            while (!feof($handler)) {
                $filedata .= fread($handler, 1024 * 8);
            }
            fclose($handler);

            $userpic = &datamanager_init('userpic', $this->vb, ERRTYPE_ARRAY);

            if ($this->vb->options['avatarenabled'] && $this->user['photo'] != 'http://ulogin.ru/img/photo.png') {
                $avatar = $userpic->fetch_library($this->vb, ERRTYPE_ARRAY, 'userpic_avatar');
                $avatar->set('userid', $this->vb->userinfo['userid']);
                $avatar->set('filedata', $filedata);
                $avatar->set('filename', $this->user['username']);
                $avatar->pre_save();
                $avatar->save();
            }

            if ($this->vb->options['profilepicenabled'] && $this->user['photo'] != 'http://ulogin.ru/img/photo.png') {
                $profilepic = $userpic->fetch_library($this->vb, ERRTYPE_ARRAY, 'userpic_profilepic');
                $profilepic->set('userid', $this->vb->userinfo['userid']);
                $profilepic->set('filedata', $filedata);
                $profilepic->set('filename', $this->user['username']);
                $profilepic->pre_save();
                $profilepic->save();
            }
        }

    }
}
