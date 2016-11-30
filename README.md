# rememberme
Codeigniter library to enable rememberme  functionality for 1 day

3 function which we have to use in our code to enable this feature.

 1) isRememberMe():-  call isRememberMe() function after valid user check, it will check that user comes with remember me checkbox checked or not on the basis of that it will set persistance cookie for one day you can change cookie lifetime as you want at set_cookie() function.<br/>
    e.g  if(user === valid){
           $this->persistencelogin->isRememberMe();
           // your code here goes down
    }
    
 2) checkForPersistanceLogin() :-  call checkForPersistanceLogin() function before login page render or call. 
    e.g  user call login page where it get login page tu put email id or password or whatever your system need to login.
     $this->persistencelogin->checkForPersistanceLogin();
     $this->load->view('users/login');
    
 3) deleteCookie() :-  call deleteCookie() on logout to clear cookie
     e.g. 
       function logout (){
       $this->persistencelogin->deleteCookie();
       //your code to logout user
       //session_destroy()
       }
       
   
