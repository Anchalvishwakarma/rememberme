<?php

defined('BASEPATH') OR exit('No direct script access allowed');

/*
 * This class will manage persistence user Login.
 * user will logged in for a single day if it's not logout.
 * a remember me checkbox is given to enable persistence login at time of login.
 * 
 *  */

class PersistenceLogin {
    /*
     *  private variable hold CI Instance
     */

    private $CI;

    /*
     *  private variable for User data
     */
    private $userData = array();

    /*
     *  private variable hash value
     */
    private $hash = 'gdfeahahSHHW766JHKSKGSJAGDJASGDFDdsds';

    /*
     * Cookie private variable
     * comes from browser
     */
    private $cookieValue;


    /*
     * username private variable
     */
    private $identifier;


    /*
     * username private variable
     */
    private $token;

    /*
     * username private variable
     * come from browser
     */
    private $cookie_useremail;


    /*
     * identifier private variable
     * come from browser
     */
    private $cookie_identifier;


    /*
     * token private variable
     * come from browser
     */
    private $cookie_token;

    /*
     * All userInput Data from login form
     * come from browser
     */
    private $input_array = array();

    /*
     * check user with database exist or not
     * */
    private $is_user_exist = FALSE;

    public function __construct() {
        $this->CI = & get_instance();
        $this->CI->load->model('Users');// assueming users module to check user with DB
        $this->CI->load->helper('cookie');
        $this->CI->load->library('encrypt');
        $this->input_array = $this->CI->input->post();
    }

    /*
     * set Cookie for one day
     * use class variable for operation
     * @classvariable :  $this->input_array
     * @classvariable :  $this->identifier
     * @classvariable :  $this->token
     * */

    public function CustomSetCookie() {
        $cookieData = array(
            'name' => 'user',
            'value' => $this->CI->encrypt->encode($this->input_array['user_name'] . "-" . $this->identifier . "-" . $this->token),
            'expire' => strtotime('tomorrow') - time() //set seconds for next day left
        );
        set_cookie($cookieData);
    }








    /*
     * Get Cookie forom browser
     * set class variable :  $this->cookieValue
     * */

    public function CustomGetCookie() {
        $this->cookieValue = ($this->CI->input->cookie('user', TRUE) != NULL) ? $this->CI->input->cookie('user', TRUE) : NULL;
    }

    /*
     * check user exist or not cross check with DB
     * use class variable for operation
     * @class variable :  $this->input_array
     * @class variable :  $this->is_user_exist
     * */

    public function isUserExist() {
        $this->CI->db->select('*');
        $this->CI->db->from('users');
        $this->CI->db->where('users.user_email', $this->input_array['user_email']);
        $query = $this->CI->db->get();
        $this->is_user_exist = ($query->num_rows() == 1) ? TRUE : FALSE;
    }

    /*
     * check user come with rememberme option
     * use class variable for operation
     * @class variable :  $this->input_array
     * @class variable :  $this->identifier
     * @class variable :  $this->token
     * */

    public function isRememberMe() {
        if (isset($this->input_array['rememberme']) && $this->input_array['rememberme'] == 1) {
            $this->isUserExist();
            $this->generateTokenAndIdentifires();

            //save token and identifier in users table for log-in user and set cookie
            if ($this->is_user_exist === TRUE) {
                $this->CustomSetCookie();
                $userData = array(
                    'identifier' => $this->identifier,
                    'token' => $this->token,
                );

                $this->CI->db->where('user_email', $this->input_array['user_email']);
                $this->CI->db->update('users', $userData);
            }
        } else {
            return FALSE;
        }
    }

    /*
     * create hashs for cookie
     * use class variable for operation
     * @class variable :  $this->input_array
     * @class variable :  $this->identifier
     * @class variable :  $this->token
     * */

    public function generateTokenAndIdentifires() {
        $this->identifier = md5($this->input_array['user_email']);
        $this->token = hash('sha512', $this->hash);
    }

    /*
     * Delete cookie when user after clicking on logout
     * use CI session userData B,coz user_name field will present at session
     * @session variable :  session->userdata('user_name')
     * */

    public function deleteCookie() {
        delete_cookie('user');
        //set NUll to user token and identifier
        $userData = array(
            'identifier' => NULL,
            'token' => NULL,
        );

        $this->CI->db->where('user_email', $this->CI->session->userdata('user_email'));
        $this->CI->db->update('users', $userData);
    }

    /*
     * check user for Persistence Cookie based Login
     * @class variable :  $this->cookieValue
     * @case1: if cookie not found than redirect to login page
     * @case2: if user has valid cookie than set userdata and log-in
     * @case3: if user has invalid cookie than redirect to login page
     */

    public function checkForPersistanceLogin() {
        $this->CustomGetCookie();
        //if cookie value not set perform valid cookie check
        if ($this->cookieValue != NULL) {
            $this->decodeAndSetCookieValue();
            $this->isCookieUserValid();
        }
    }

    /*
     * decode cookie data and assign to instance variable
     * use class variable for operation
     * @class variable :  $this->cookieValue
     * @class variable :  $this->cookie_username
     * @class variable :  $this->cookie_identifier
     * @class variable :  $this->token
     * */

    public function decodeAndSetCookieValue() {
        $cookieArray = explode('-', $this->CI->encrypt->decode($this->cookieValue));
        $this->cookie_useremail = $cookieArray[0];
        $this->cookie_identifier = $cookieArray[1];
        $this->cookie_token = $cookieArray[2];
    }

    /*
     * check function for user if data comes from cookie
     * if user valid than redirect  to dashboard
     * use class variable for operation
     * @class variable :  $this->cookie_identifier
     * @class variable :  $this->token
     * */

    public function isCookieUserValid() {
        $this->CI->db->select('*');
        $this->CI->db->from('users');
        $this->CI->db->where('users.identifier', $this->cookie_identifier);
        $this->CI->db->where('users.token', $this->cookie_token);
        $query = $this->CI->db->get();
        $this->userData = $query->result_array();
        $rowCount = $query->num_rows();

        if ($query->num_rows() == 1 && $this->cookie_identifier == $this->userData[0]['identifier'] && $this->cookie_token == $this->userData[0]['token']) {
            $this->setUserDataToSession();
            //valid user case redirect to dashboard
            redirect('dashboard');
            die;
        }
    }

    /*
     * set user data to session when user persistence cookie is valid
     * use class variable for operation
     * @class variable :  $this->userData
     * */

    public function setUserDataToSession() {
        //set user data into session after login
        $this->CI->session->set_userdata($this->userData[0]);
    }

}
