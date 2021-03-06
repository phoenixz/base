<?php
/*
    @author        :    Giriraj Namachivayam
    @date         :    Mar 20, 2013
    @demourl    :    http://ngiriraj.com/socialMedia/oauthlogin/
    @license    :     Free to use,
    @History    :    V1.0 - Released oauth 2.0 service providers login access
    @oauth2        :    Support following oauth2 login
                    Bitly
                    Wordpress
                    Paypal
                    Facebook
                    Google
                    Microsoft(MSN,Live,Hotmail)
                    Foursquare
                    Box
                    Reddit
                    Yammer
                    Yandex
                    SoundCloud
                    Meetup
                    StockTwits
                    Github

                    Recently added on 25-Apr-2013
                    LinkedIn
                    Imgur
                    AngelList
                    DeviantArt
                    Snapr
                    DailyMotion
                    MailChimp
                    Formstack
                    Wepay
                    Stripe
                    MixCloud
                    Flattr
    Document    :    Step-by-step Tutorial for oAuth2 connect and Demo
                    http://ngiriraj.com/work/stocktwits-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/meetup-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/soundcloud-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/box-connect-using-oauth-in-php/
                    http://ngiriraj.com/work/wordpress-connect-using-oauth-in-php-2/
                    http://ngiriraj.com/work/bitly-connect-using-oauth-in-php/
                    http://ngiriraj.com/work/yammer-connect-using-oauth-in-php/
                    http://ngiriraj.com/work/foursquare-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/paypal-connect-by-using-oauth-in-php-2/
                    http://ngiriraj.com/work/facebook-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/google-connect-by-using-oauth-in-php/
                    http://ngiriraj.com/work/hotmail-connect-by-using-oauth/

*/

class socialmedia_oauth_connect
{

      public $socialmedia_oauth_connect_version = '1.0';

    public $client_id;
    public $client_secret;
    public $scope;
    public $responseType;
    public $nonce;
    public $state;
    public $redirect_uri;
    public $code;
    public $oauth_version;
    public $provider;
    public $accessToken;

    protected $requestUrl;
      protected $accessTokenUrl;
      protected $dialogUrl;
    protected $userProfileUrl;
    protected $header;

      public function Initialize(){
          $this->nonce = time() . rand();
          switch(strtolower($this->provider))
        {
            case 'bitly':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://bitly.com/oauth/authorize?';
                $this->accessTokenUrl = 'https://api-ssl.bitly.com/oauth/access_token?';
                $this->responseType="code";
                $this->scope="";
                $this->state="";
                $this->userProfileUrl = "https://api-ssl.bitly.com/v3/user/info?";
                $this->header="";
                break;

            case 'wordpress':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://public-api.wordpress.com/oauth2/authorize?';
                $this->accessTokenUrl = 'https://public-api.wordpress.com/oauth2/token?';
                $this->responseType="code";
                $this->scope="";
                $this->state="";
                $this->userProfileUrl = "https://public-api.wordpress.com/rest/v1/me/?pretty=1";
                $this->header="Authorization: Bearer ";
                break;

            case 'paypal':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/authorize?';
                $this->accessTokenUrl = 'https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/tokenservice?';
                $this->responseType="code";
                $this->state="";
                $this->userProfileUrl = "https://www.paypal.com/webapps/auth/protocol/openidconnect/v1/userinfo?schema=openid&access_token=";
                $this->header="";
                break;

            case 'facebook':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.facebook.com/dialog/oauth?client_id='.$this->client_id.'&redirect_uri='.$this->redirect_uri.'&scope='.$this->scope.'&state='.$this->state;
                $this->accessTokenUrl = 'https://graph.facebook.com/oauth/access_token';
                $this->responseType="code";
                $this->userProfileUrl = "https://graph.connect.facebook.com/me/?";
                $this->header="";
                break;

            case 'google':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://accounts.google.com/o/oauth2/auth?';
                $this->accessTokenUrl = 'https://accounts.google.com/o/oauth2/token';
                $this->responseType="code";
                $this->userProfileUrl = "https://www.googleapis.com/oauth2/v1/userinfo?access_token=";
                $this->header="Authorization: Bearer ";
                break;


            case 'microsoft':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://login.live.com/oauth20_authorize.srf?';
                $this->accessTokenUrl = 'https://login.live.com/oauth20_token.srf';
                $this->responseType="code";
                $this->userProfileUrl = "https://apis.live.net/v5.0/me?access_token=";
                $this->header="";
                break;


            case 'foursquare':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://foursquare.com/oauth2/authorize?';
                $this->accessTokenUrl = 'https://foursquare.com/oauth2/access_token';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.foursquare.com/v2/users/self?oauth_token=";
                $this->header="";
                break;

            case 'box':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.box.com/api/oauth2/authorize?';
                $this->accessTokenUrl = 'https://www.box.com/api/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.box.com/2.0/users/me?oauth_token=";
                $this->header="Authorization: Bearer ";
                break;

            case 'yammer':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.yammer.com/dialog/oauth?';
                $this->accessTokenUrl = 'https://www.yammer.com/oauth2/access_token.json?';
                $this->responseType="code";
                $this->userProfileUrl = "https://www.yammer.com/api/v1/users/current.json?access_token=";
                $this->header="";
                break;

            case 'reddit':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://ssl.reddit.com/api/v1/authorize?';
                $this->accessTokenUrl = 'https://ssl.reddit.com/api/v1/access_token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://oauth.reddit.com/api/v1/me.json?access_token=";
//                $this->header="Authorization: Basic";
                $this->header="Authorization: Bearer ";
                $this->state="frikandel";
                break;

            case 'yandex':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://oauth.yandex.com/authorize?display=popup&';
                $this->accessTokenUrl = 'https://oauth.yandex.com/token?';
                $this->responseType="code";
                $this->userProfileUrl = "http://api-fotki.yandex.ru/api/me/?oauth_token=";
                $this->header="";
                break;

            case 'soundcloud':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://soundcloud.com/connect?';
                $this->accessTokenUrl = 'https://api.soundcloud.com/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.soundcloud.com/me.json?oauth_token=";
                $this->scope="non-expiring";
                break;

            case 'meetup':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://secure.meetup.com/oauth2/authorize?';
                $this->accessTokenUrl = 'https://secure.meetup.com/oauth2/access?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.meetup.com/2/member/self?access_token=";
                $this->scope="basic";
                break;

            case 'stocktwits':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://api.stocktwits.com/api/2/oauth/authorize?';
                $this->accessTokenUrl = 'https://api.stocktwits.com/api/2/oauth/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.stocktwits.com/api/2/account/verify.json?access_token=";
                $this->scope="read";
                break;

            case 'github':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://github.com/login/oauth/authorize?';
                $this->accessTokenUrl = 'https://github.com/login/oauth/access_token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.github.com/user?access_token=";
                $this->scope="read";
                break;

            case 'linkedin':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.linkedin.com/uas/oauth2/authorization?';
                $this->accessTokenUrl = 'https://www.linkedin.com/uas/oauth2/accessToken?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.linkedin.com/v1/people/~?format=json&oauth2_access_token=";
                break;

            case 'flattr':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://flattr.com/oauth/authorize?';
                $this->accessTokenUrl = 'https://flattr.com/oauth/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.flattr.com/rest/v2/user?access_token=";
                $this->scope="flattr%20thing";
                break;

            case 'mixcloud':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.mixcloud.com/oauth/authorize?';
                $this->accessTokenUrl = 'https://www.mixcloud.com/oauth/access_token?';
                $this->responseType="code";
                $this->userProfileUrl = "";
                break;

            case 'stripe':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://connect.stripe.com/oauth/authorize?';
                $this->accessTokenUrl = 'https://connect.stripe.com/oauth/token?';
                $this->responseType="code";
                $this->scope="read_write";
                $this->userProfileUrl = "";
                break;

            case 'wepay':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.wepay.com/v2/oauth2/authorize?';
                $this->accessTokenUrl = 'https://wepayapi.com/v2/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "";
                $this->scope="view_user";
                break;

            case 'formstack':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.formstack.com/api/v2/oauth2/authorize?';
                $this->accessTokenUrl = 'https://www.formstack.com/api/v2/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "";
                break;

            case 'mailchimp':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://login.mailchimp.com/oauth2/authorize?';
                $this->accessTokenUrl = 'https://login.mailchimp.com/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "";
                break;

            case 'dailymotion':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://api.dailymotion.com/oauth/authorize?';
                $this->accessTokenUrl = 'https://api.dailymotion.com/oauth/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.dailymotion.com/me?access_token=";
                $this->scope="read+write";
                break;

            case 'snapr':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://sna.pr/api/oauth/authorize?';
                $this->accessTokenUrl = 'https://sna.pr/api/oauth/access_token?';
                $this->responseType="code";
                $this->userProfileUrl = "";
                break;

            case 'deviantart':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://www.deviantart.com/oauth2/draft10/authorize?';
                $this->accessTokenUrl = 'https://www.deviantart.com/oauth2/draft10/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://www.deviantart.com/api/draft10/user/whoami?access_token=";
                break;

            case 'angellist':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://angel.co/api/oauth/authorize?';
                $this->accessTokenUrl = 'https://angel.co/api/oauth/token?';
                $this->responseType="code";
                $this->userProfileUrl = "https://api.angel.co/1/me?access_token=";
                break;

            case 'imgur':
                $this->oauth_version="2.0";
                $this->dialogUrl = 'https://api.imgur.com/oauth2/authorize?';
                $this->accessTokenUrl = 'https://api.imgur.com/oauth2/token?';
                $this->responseType="code";
                $this->userProfileUrl = "";//https://api.imgur.com/3/account/me?access_token=
                $this->header="Authorization: Bearer ";
                break;


            case 'twitter':
                $this->oauth_version  = '2.0';
                $this->dialogUrl      = 'https://api.twitter.com/oauth2/token';
                $this->accessTokenUrl = 'https://api.twitter.com/oauth/request_token';
                $this->responseType   = 'code';
                $this->userProfileUrl = 'https://api.twitter.com/oauth/access_token';
                break;

            case '';
                throw new bException('No provider specified');

            default:
                throw new bException('Provider "'.$this->provider.'" is not yet a supported. We will release soon. Contact kayalshri@gmail.com!');
        }
      }



      public function Authorize(){

          if($this->oauth_version == "2.0"){
              $dialog_url = $this->dialogUrl
                  ."client_id=".$this->client_id
            ."&response_type=".$this->responseType
            ."&scope=".$this->scope
            /*."&nonce=".$this->nonce*/
            ."&state=".$this->state
            ."&redirect_uri=".urlencode($this->redirect_uri);
             echo("<script> top.location.href='" . $dialog_url . "'</script>");

         }else{

            $date = new DateTime();
            $request_url = $this->requestUrl;
            $postvals ="oauth_consumer_key=".$this->client_id
                    ."&oauth_signature_method=HMAC-SHA1"
                    ."&oauth_timestamp=".$date->getTimestamp()
                    ."&oauth_nonce=".$this->nonce
                    ."&oauth_callback=".$this->redirect_uri
                    ."&oauth_signature=".$this->client_secret
                    ."&oauth_version=1.0";
            $redirect_url = $request_url."".$postvals;

            $oauth_redirect_value= $this->curl_request($redirect_url,'GET','');
              $dialog_url = $this->dialogUrl.$oauth_redirect_value;

            echo("<script> top.location.href='" . $dialog_url . "'</script>");
        }
      }


      public function getAccessToken(){
        $postvals = "client_id=".$this->client_id
            ."&client_secret=".$this->client_secret
            ."&grant_type=authorization_code"
            ."&redirect_uri=".urlencode($this->redirect_uri)
            ."&code=".$this->code;
        return $this->curl_request($this->accessTokenUrl,'POST',$postvals);
      }

      public function getUserProfile(){
          $getAccessToken_value = $this->getAccessToken();
          $getatoken = json_decode( stripslashes($getAccessToken_value) );

        if( $getatoken === NULL ){
            $atoken=$getAccessToken_value;

           }else{
            if(!empty($getatoken->error)){
                if(is_object($getatoken->error)){
                    throw new Exception('socialmedia_oauth_connect->getUserProfile(): Provider "'.$this->provider.'" returned error "'.$getatoken->error->code.'" with message "'.$getatoken->error->message.'"');
                }

                throw new Exception('socialmedia_oauth_connect->getUserProfile(): Provider "'.$this->provider.'" returned error "'.$getatoken->error.'"');
            }

               $atoken = $getatoken->access_token;
           }

           if(strtolower($this->provider) == 'yammer'){
               $atoken = $getatoken->access_token->token;
           }

          if ($this->userProfileUrl){
          $profile_url = $this->userProfileUrl."".$atoken;
          #$_SESSION['atoken']=$atoken;
        #print "profile :".$profile_url;
        #exit();

        return $this->curl_request($profile_url,"GET",$atoken);
        }else{
        return $getAccessToken_value;
        }

      }

      public function APIcall($url){
          return $this->curl_request($url,"GET",$_SESSION['atoken']);
      }

      public function debugJson($data){
          echo "<pre>";
          print_r($data);
          echo "</pre>";

        # Redirect where ever you need **************************************************************
        #$c_session = $this->provider."_profile";
          #$_SESSION[$this->provider] = "true";
        #$_SESSION[$c_session] = $data;

        #echo("<script> top.location.href='index.php#".$this->provider."'</script>");

      }
    public function curl_request($url,$method,$postvals){

    $ch = curl_init($url);
    if ($method == "POST"){
       $options = array(
                CURLOPT_POST => 1,
                CURLOPT_POSTFIELDS => $postvals,
                CURLOPT_RETURNTRANSFER => 1,
        );

    }else{

       $options = array(
                CURLOPT_RETURNTRANSFER => 1,
        );

    }
    curl_setopt_array( $ch, $options );
    if($this->header){

        curl_setopt( $ch, CURLOPT_HTTPHEADER, array( $this->header . $postvals) );
    }

    $response = curl_exec($ch);
    curl_close($ch);
#    print_r($response);
    return $response;
    }

}

?>
