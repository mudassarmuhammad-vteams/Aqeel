<?php

class WebApplicationEndBehavior extends CBehavior
{
    // Web application end's name.
    private $_endName;

    // Getter.
    // Allows to get the current -end's name
    // this way: Yii::app()->endName;
    public function getEndName()
    {
        return $this->_endName;
    }

    // Check end name and authorize backend only
    // this way: Yii::app()->checkEndForAdmin();
    public function checkEndForAdmin()
    {
        $end = $this->endName;
        $users = Yii::app()->getModule('user')->getAdmins();
        if ($end == 'front') {
            throw new CHttpException(404, 'The requested page does not exist.');
        }
    }

    // Get and return the sub domain name for current client
    // this way: Yii::app()->returnClientPortal();
    public function returnClientPortal()
    {
        $exp = $_SERVER['SERVER_NAME'];
        $client_portal = trim(strtolower($exp[0]));
        $subDomain = Yii::app()->extract_subdomains($_SERVER['SERVER_NAME']);
        if ($subDomain != '' && $subDomain != 'www') {
            // Sub Domain Request die($subDomain);
            return $subDomain;
        } else {
            if ($exp == Yii::app()->params['SiteUrl'] || $exp == 'www.' . Yii::app()->params['SiteUrl']) {
                // Live Site URL die(Yii::app()->params['SiteName']);
                return Yii::app()->params['SiteName'];
            } else {
                // Parked Domain URL
                $connection = Yii::app()->openMasterDbConnection();
                $limitedURL = substr($exp, 0, 4);
                if ($limitedURL == 'www.') {
                    $URL_WWW = substr($exp, 4);
                } else {
                    $URL_WWW = "www." . $exp;
                }
                /* Query for Master Database */
                $sql = "SELECT sub_domain FROM user_domains WHERE domain_name = '" . $exp . "'  OR domain_name = '" . $URL_WWW . "'";
                $command = $connection->createCommand($sql);
                $result = $command->queryRow();
                $expression = explode(".", $result['sub_domain']);
                $my_portal = trim(strtolower($expression[0]));
                Yii::app()->closeMasterDbConnection($connection);
                return $my_portal;
            }
        }
    }

    // Get and return the sub domain name from given URL
    // this way: Yii::app()->returnClientPortalFromURL();
    public function returnClientPortalFromURL($url)
    {
        $exp = $url;
        $subDomain = Yii::app()->extract_subdomains($url);
        if (!empty($subDomain) && $subDomain != 'www') {
            // Sub Domain Request die($subDomain);
            return $subDomain;
        } else {
            if ($exp == Yii::app()->params['SiteUrl'] || $exp == 'www.' . Yii::app()->params['SiteUrl']) {
                // Live Site URL die(Yii::app()->params['SiteName']);
                return Yii::app()->params['SiteName'];
            } else {
                // Parked Domain URL
                $connection = Yii::app()->openMasterDbConnection();
                $limitedURL = substr($exp, 0, 4);
                if ($limitedURL == 'www.') {
                    $URL_WWW = substr($exp, 4);
                } else {
                    $URL_WWW = "www." . $exp;
                }
                /* Query for Master Database */
                $sql = "SELECT sub_domain FROM user_domains WHERE domain_name = '" . $exp . "'  OR domain_name = '" . $URL_WWW . "'";
                $command = $connection->createCommand($sql);
                $result = $command->queryRow();
                $expression = explode(".", $result['sub_domain']);
                $my_portal = trim(strtolower($expression[0]));
                Yii::app()->closeMasterDbConnection($connection);
                return $my_portal;
            }
        }
    }

    // Run application's end.
    public function runEnd($name)
    {
        $this->_endName = $name;

        // Attach the changeModulePaths event handler
        // and raise it.
        $this->onModuleCreate = array($this, 'changeModulePaths');
        $this->onModuleCreate(new CEvent($this->owner));
        $this->owner->run(); // Run application.
    }

    // This event should be raised when CWebApplication
    // or CWebModule instances are being initialized.
    public function onModuleCreate($event)
    {
        $this->raiseEvent('onModuleCreate', $event);
    }


    // onModuleCreate event handler.
    // A sender must have controllerPath and viewPath properties.
    protected function changeModulePaths($event)
    {
        $event->sender->controllerPath .= DIRECTORY_SEPARATOR . $this->_endName;
        $event->sender->viewPath .= DIRECTORY_SEPARATOR . $this->_endName;
    }


    // Get Names from all Modules and all available shortcodes
    // this way: Yii::app()->getAllShortCodes();
    public function getAllShortCodes()
    {
        $end = $this->endName;
        if ($end == 'front') {
            throw new CHttpException(404, 'The requested page does not exist.');
        } elseif ($end == 'back') {
            $return_array = array();
            // Get all Content Blocks From Database
            $content_block = new ContentBlock;
            $data_CB = $content_block->FindAll();
            foreach ($data_CB as $cb) {
                $cb_temp['title'] = $cb->title;
                $cb_temp['id'] = $cb->id;
                $cb_temp['slug'] = $cb->slug;
                $return_array['content_block'][] = $cb_temp;
            }
            // Get all Menus From Database
            $menu = new Menu;
            $data_menu = $menu->FindAll();
            foreach ($data_menu as $menu) {
                $cb_temp['title'] = $menu->title;
                $cb_temp['id'] = $menu->id;
                $cb_temp['slug'] = $menu->slug;
                $return_array['menu'][] = $cb_temp;
            }
            // Get all Galleries From Database
            $gallery = new Gal;
            $data_gallery = $gallery->FindAll();
            foreach ($data_gallery as $gal) {
                $cb_temp['title'] = $gal->title;
                $cb_temp['id'] = $gal->id;
                $return_array['gallery'][] = $cb_temp;
            }
            // Get all Galleries From Database
            $forms = new Forms;
            $data_forms = $forms->FindAll();
            foreach ($data_forms as $form) {
                $form_temp['title'] = $form->name;
                $form_temp['id'] = $form->id;
                $form_temp['slug'] = $form->slug;
                $return_array['forms'][] = $form_temp;
            }
            return $return_array;
        }
    }


    // Return Site Title
    // this way: Yii::app()->mySiteTitle;
    public function mySiteTitle()
    {
        $settings = new Settings;
        $meta = $settings->find();
        if (isset($meta->site_title)) {
            return $meta->site_title;
        } else {
            return Yii::app()->name;
        }
    }

    // Return Master admin Email
    // this way: Yii::app()->adminEmail();
    public function adminEmail()
    {
        $settings = new Settings;
        $meta = $settings->find();
        if (isset($meta->webmaster_email)) {
            return $meta->webmaster_email;
        } else {
            return ADMINEMAIL;
        }
    }

    // Open Master Database connection to perform other operations and multiple database connections at the same time
    // this way: Yii::app()->openMasterDbConnection();
    public function openMasterDbConnection()
    {
        $host = DBHOST;
        $user = DBUSER;
        $pass = DBPASS;
        $db = DBNAME;
        $connectionString = "mysql:host=" . DBHOST . ";dbname=" . DBNAME;
        $connection = new CDbConnection($connectionString, DBUSER, DBPASS);
        $connection->active = true;
        return $connection;
    }

    // Close Master Database connection and multiple database connections at the same time
    // this way: Yii::app()->closeMasterDbConnection();
    public function closeMasterDbConnection($connection)
    {
        $connection->active = false;
    }

    // Execute Query on Master database
    // this way: Yii::app()->queryMasterDbConnection();
    public function queryMasterDbConnection($query, $connection)
    {
        $command = $connection->createCommand($query);
        $result = $command->queryRow();
        if ($result) {
            return $result;
        }
        return false;
    }

    // this way: Yii::app()->executeQueryMasterDb();
    public function executeQueryMasterDb($query, $connection)
    {
        $command = $connection->createCommand($query);
        $result = $command->execute();
        if ($result) {
            return $result;
        }
        return false;
    }

    // Check for System or outside system URL
    // this way: Yii::app()->isSystemURL();
    public function isSystemURL()
    {
        $exp = explode(".", $_SERVER['SERVER_NAME']);
        if (trim(strtolower($exp[1])) == Yii::app()->params['SiteName']) {
            return true;
        } else {
            return false;
        }
    }

    // URL parsing and fetch domain name
    // this way: Yii::app()->extract_domain();
    public function extract_domain($domain)
    {
        if (preg_match("/(?P<domain>[a-z0-9][a-z0-9\-]{1,63}\.[a-z\.]{2,6})$/i", $domain, $matches)) {
            return $matches['domain'];
        } else {
            return $domain;
        }
    }

    // URL parsing and fetch sub-domain name
    // this way: Yii::app()->extract_subdomains();
    public function extract_subdomains($domain)
    {
        $subdomains = $domain;
        $domain = $this->extract_domain($subdomains);
        $subdomains = rtrim(strstr($subdomains, $domain, true), '.');
        return $subdomains;
    }

    // Get Stripe Information saved in master database
    // Update the token using Stripe Webservices and then save in master database again to avoid its expiry
    // this way: Yii::app()->getLatestStripeInfo();
    public function getLatestStripeInfo()
    {
        $user_id = Yii::app()->getUsersIDFromMasterDB();
        $connection = Yii::app()->openMasterDbConnection();
        $sql = "SELECT * FROM user_stripe_info WHERE user_id = $user_id";
        $command = $connection->createCommand($sql);
        $result = $command->queryRow();
        if (empty($result)) {
            $return = "Stripe Info Not Found";
        } else {
            $code = $result['refresh_token'];
            $token_request_body = array(
                'client_secret' => Yii::app()->params['stripe_api_key'],
                'grant_type' => 'refresh_token',
                'client_id' => Yii::app()->params['stripe_client_ID'],
                'refresh_token' => $code,
            );
            $req = curl_init(STRIPETOKENURI);
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($token_request_body));
            curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
            // TODO: Additional error handling

            $resp = json_decode(curl_exec($req), true);
            $curl_errors = curl_error($req);
            curl_close($req);

            if (!isset($resp['error']) && empty($resp['error']) && empty($curl_errors)) {
                $sql_query = "UPDATE `user_stripe_info` SET
						`access_token` = '" . $resp["access_token"] . "',
						`livemode` = '" . $resp["livemode"] . "',
						`refresh_token` = '" . $resp["refresh_token"] . "',
						`token_type` = '" . $resp["token_type"] . "',
						`stripe_publishable_key` = '" . $resp["stripe_publishable_key"] . "',
						`stripe_user_id` = '" . $resp["stripe_user_id"] . "',
						`scope` = '" . $resp["scope"] . "',
						`last_updated` = '" . $resp["access_token"] . "',
						`last_updated` = " . time() . "
						WHERE `id` ='" . $result['id'] . "';";
            } else {
                $sql_query = "DELETE FROM `user_stripe_info` WHERE `id` ='" . $result['id'] . "';";
            }
            $flag = true;
            $executeQry = $connection->createCommand($sql_query);
            $executeQry->execute();
            if (!$flag) {
                die(mysql_error() . "<br>" . $sql_query . "<hr>");
            }
            $return = $resp;
        }
        Yii::app()->closeMasterDbConnection($connection);
        return $return;
    }

    // Get master Stripe Information saved in master database for main/master website
    // Update the token using Stripe Webservices and then save in master database again to avoid its expiry
    // this way: Yii::app()->getLatestMaterStripeInfo();
    public function getLatestMaterStripeInfo()
    {

        $user_id = 1;
        $connection = Yii::app()->openMasterDbConnection();
        $sql = "SELECT * FROM user_stripe_info WHERE user_id = $user_id";
        $command = $connection->createCommand($sql);
        $result = $command->queryRow();
        if (empty($result)) {
            $return = "Stripe Info Not Found";
        } else {
            $code = $result['refresh_token'];
            $token_request_body = array(
                'client_secret' => Yii::app()->params['stripe_api_key'],
                'grant_type' => 'refresh_token',
                'client_id' => Yii::app()->params['stripe_client_ID'],
                'refresh_token' => $code,
            );
            $req = curl_init(STRIPETOKENURI);
            curl_setopt($req, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($req, CURLOPT_POST, true);
            curl_setopt($req, CURLOPT_POSTFIELDS, http_build_query($token_request_body));
            curl_setopt($req, CURLOPT_SSL_VERIFYPEER, false);
            // TODO: Additional error handling
            $respCode = curl_getinfo($req, CURLINFO_HTTP_CODE);
            $resp = json_decode(curl_exec($req), true);
            $curl_errors = curl_error($req);
            curl_close($req);
            if (!isset($resp['error']) && empty($resp['error']) && empty($curl_errors)) {
                $sql_query = "UPDATE `user_stripe_info` SET
						`access_token` = '" . $resp["access_token"] . "',
						`livemode` = '" . $resp["livemode"] . "',
						`refresh_token` = '" . $resp["refresh_token"] . "',
						`token_type` = '" . $resp["token_type"] . "',
						`stripe_publishable_key` = '" . $resp["stripe_publishable_key"] . "',
						`stripe_user_id` = '" . $resp["stripe_user_id"] . "',
						`scope` = '" . $resp["scope"] . "',
						`last_updated` = '" . $resp["access_token"] . "',
						`last_updated` = " . time() . "
						WHERE `id` ='" . $result['id'] . "';";
            } else {
                $sql_query = "DELETE FROM `user_stripe_info` WHERE `id` ='" . $result['id'] . "';";
            }

            $flag = true;
            $executeQry = $connection->createCommand($sql_query);
            $executeQry->execute();
            if (!$flag) {
                die(mysql_error() . "<br>" . $sql_query . "<hr>");
            }
            $return = $resp;
        }
        Yii::app()->closeMasterDbConnection($connection);
        return $return;
    }

    // Get current user ID from Master Database
    //Yii::app()->getUsersIDFromMasterDB();
    public function getUsersIDFromMasterDB()
    {
        $website_domain = Yii::app()->returnClientPortal();
        if ($website_domain == SITENAME) {
            $my_user = 1;
        } else {
            $connection = Yii::app()->openMasterDbConnection();
            $sql = "SELECT id FROM tbl_users WHERE website_domain = '" . $website_domain . "'";
            $command = $connection->createCommand($sql);
            $result = $command->queryRow();
            //$res = mysql_fetch_object(mysql_query("SELECT id FROM tbl_users WHERE website_domain = '".$website_domain."'"));
            $my_user = $result['id'];
            Yii::app()->closeMasterDbConnection($connection);
        }
        return $my_user;
    }

    // Get Users information from Master Database
    //Yii::app()->getUsersInfoFromMasterDB();

    public function getUsersInfoFromMasterDB()
    {
        $website_domain = Yii::app()->returnClientPortal();
        $connection = Yii::app()->openMasterDbConnection();
        if ($website_domain == SITENAME) {
            $sql = "SELECT * FROM tbl_users WHERE id = 1 ";
        } else {
            $sql = "SELECT * FROM tbl_users WHERE website_domain = '" . $website_domain . "'";
        }
        $command = $connection->createCommand($sql);
        $result = $command->queryRow();
        Yii::app()->closeMasterDbConnection($connection);
        return $result;
    }

    // Get and Update Google Analytic tokens from Master Database
    // Update using Google Webservices
    // this way: Yii::app()->getGAToken();

    public function getGAToken()
    {

        $meta = Settings::model()->find();
        $oauth2token_url = "https://accounts.google.com/o/oauth2/token";
        $clienttoken_post = array(
            "client_id" => Yii::app()->params['ga_clientID'],
            "client_secret" => Yii::app()->params['ga_clientSecret']
        );
        $clienttoken_post["refresh_token"] = $meta->ga_refresh_token;
        $clienttoken_post["grant_type"] = "refresh_token";
        $curl = curl_init($oauth2token_url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $clienttoken_post);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $json_response = curl_exec($curl);
        curl_close($curl);
        $authObj = json_decode($json_response);
        //if offline access requested and granted, get refresh token
        if (isset($authObj->refresh_token)) {
            $refreshToken = $authObj->refresh_token;
        }

        $accessToken = $authObj->access_token;
        return $accessToken;
    }

    // Get and Update Google Analytic tokens from Master Database with params
    // Update using Google Webservices
    // this way: Yii::app()->getGATokenWithParams();
    public function getGATokenWithParams($refresh_token)
    {
        $meta = Settings::model()->find();
        $oauth2token_url = "https://accounts.google.com/o/oauth2/token";
        $clienttoken_post = array(
            "client_id" => Yii::app()->params['ga_clientID'],
            "client_secret" => Yii::app()->params['ga_clientSecret'],
            "access_type " => 'offline',
            //'scope'=> 'readonly'
        );
        $clienttoken_post["refresh_token"] = $refresh_token;
        $clienttoken_post["grant_type"] = "refresh_token";
        $curl = curl_init($oauth2token_url);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $clienttoken_post);
        curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        $json_response = curl_exec($curl);
        curl_close($curl);
        $authObj = json_decode($json_response);
        //if offline access requested and granted, get refresh token
        if (isset($authObj->refresh_token)) {
            $refreshToken = $authObj->refresh_token;
        }
        return $json_response;

    }

    // Open secondary database connection
    // this way: Yii::app()->openSecondaryDbConnection($dbhost, $dbuser, $dbpass, $dbname);
    public function openSecondaryDbConnection($dbhost, $dbuser, $dbpass, $dbname)
    {
        $host = $dbhost;
        $user = $dbuser;
        $pass = $dbpass;
        $db = $dbname;
        $connectionString = "mysql:host=" . $host . ";dbname=" . $db;
        $connection = new CDbConnection($connectionString, $dbuser, $dbpass);
        $connection->active = true;
        return $connection;
    }


    // Close secondary database connection
    // this way: Yii::app()->closeSecondaryDbConnection();
    public function closeSecondaryDbConnection($connection)
    {
        $connection->active = false;
    }

    // fetch data from secondary database
    // this way: Yii::app()->fetchDataSecondaryDB();
    public function fetchDataSecondaryDB($query, $connection)
    {
        $command = $connection->createCommand($query);
        $result = $command->queryRow();
        if ($result) {
            return $result;
        }
        return false;
    }

    // Execute query (Update, insert, delete) from secondary database
    // this way: Yii::app()->exeQuerySecondaryDB($query, $connection);
    public function exeQuerySecondaryDB($query, $connection)
    {
        $command = $connection->createCommand($query);
        $result = $command->execute();
        if ($result) {
            return $result;
        }
        return false;
    }

}