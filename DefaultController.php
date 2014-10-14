<?php

class DefaultController extends Controller
{
	
	/**
	 * @var string the default layout for the views. Defaults to '//layouts/column2', meaning
	 * using two-column layout. See 'protected/views/layouts/column2.php'.
	 */
	public $layout='//layouts/column1';
	public $defaultAction = 'admin';
	private $myModule;

	public function init()
	{
		parent::init();
		
		if(!empty($this->module)){
			$this->myModule = $this->module;
		}else{
			$this->myModule = Yii::app()->getModule ('wdcalendar');        
		}
		
	}
	
	/**
	 * @return array action filters
	 */
	public function filters()
	{
		return array(
			'accessControl', // perform access control for CRUD operations
			'postOnly + delete', // we only allow deletion via POST request
		);
	}

	/**
	 * Specifies the access control rules.
	 * This method is used by the 'accessControl' filter.
	 * @return array access control rules
	 */
	public function accessRules()
	{
		return array(
			array('allow',  // allow all users to perform 'index' and 'view' actions
				'actions'=>array('view', 'submitform'),
				'users'=>array('*'),
			),
			array('allow', // allow authenticated user to perform 'create' and 'update' actions
				'actions'=>array('index','create','update','admin','delete', 'deleteForm', 'formresponses','deleteFormresponse','getJsonUpdate'),
				'users'=>Yii::app()->getModule('user')->getAdmins(),
			),
			array('deny',  // deny all users
				'users'=>array('*'),
			),
		);
	}

	/**
	 * Displays a particular model.
	 * @param integer $id the ID of the model to be displayed
	 */
	public function actionView($id)
	{
		$this->render('view',array(
			'model'=>$this->loadModel($id),
		));
	}
	
	
	public function actionFormresponses($id)
	{
		$formresponse = new FormResponse;
		$form = new FormFields;
		$formfields = $form->findAllByAttributes(array('form_id'=>$id));
		$formsresponses = $formresponse->findAllByAttributes(array('form_id'=>$id));
		$this->render('formresponses',array(
			'model'=>$formresponse,
			'formsresponses'=>$formsresponses,
			'formfields'=>$formfields
		));
	}
	
	
	
	public function actionSubmitform()
	{
		if($_POST){


            $modules = Yii::app()->getModules();

            $formfield = new FormFields;
			$forms = new Forms;
			$form_data = $forms->findByPk($_POST['form_id']);
			$fieldData = $formfield->findAllByAttributes(array('form_id'=>$_POST['form_id']));
			
			$msg = array();
			$msg['error'] = '';
			
			foreach($fieldData as $field){
				$field->name = str_replace(" ", "_", $field->name);
				
				if($field->type == 'checkbox'){
					if($field->validation == '1'){
						if(!isset($_POST[$field->name])){
							$msg['error'] .= str_replace("_", " ", $field->name).' is required, '.$field->helping_text." <br/>";
						}
					}
				}elseif($field->type == 'radio'){
					if($field->validation == '1'){
						if(!isset($_POST[$field->name])){
							$msg['error'] .= str_replace("_", " ", $field->name).' is required, '.$field->helping_text." <br/>";
						}
					}
				}elseif($field->type == 'Email Field'){
					
					if($field->validation == '1'){
						if($_POST[$field->name] == ''){
							$msg['error'] .= str_replace("_", " ", $field->name).' is required, '.$field->helping_text." <br/>";
						}

                        $email_field = $_POST[$field->name];

						if (!filter_var($_POST[$field->name], FILTER_VALIDATE_EMAIL)) {
							$msg['error'] .= 'Please provide valid email address to '.str_replace("_", " ", $field->name)." field<br/>";
						}
					}
										
				}elseif($field->type == 'Recaptcha'){
					
					$setting = Settings::model()->find();
					# the response from reCAPTCHA
					$resp = null;
					# the error code from reCAPTCHA, if any
					$error = null;
					$publickey = $setting->public_key;
					$privatekey = $setting->private_key;
					Yii::import('application.extensions.recaptchalib');		
					$re_captcha = new recaptchalib;
					$resp = $re_captcha->recaptcha_check_answer ($privatekey,
                                        $_SERVER["REMOTE_ADDR"],
                                        $_POST["recaptcha_challenge_field"],
                                        $_POST["recaptcha_response_field"]);

					if (!$resp->is_valid) {
						$msg['error'] .= $error = $resp->error.', '.$field->helping_text." <br/>";
					} else {
							# set the error code so that we can display it
							
					}
										
				}else{
					if($field->validation == '1'){
						if($_POST[$field->name] == ''){
							$msg['error'] .= str_replace("_", " ", $field->name).' is required, '.$field->helping_text." <br/>";
						}
					}
				}
				
				
				
			}
			if($msg['error'] != ''){
				$msg['success'] = '';
				echo json_encode($msg);
				die();
			}elseif($msg['error'] == ''){
				// validation passed and now send Email and save data in Database
				
				$response = new FormResponse;
				
				$attrib['form_id'] = $_POST['form_id'];
				$attrib['data'] = serialize($_POST);
				
				if(Yii::app()->user->id){
					$attrib['user_id'] = Yii::app()->user->id;
				}
				
				$html_msg ='<h1>Contact Us Request</h1><table>';
				$array =  array();
				foreach($fieldData as $field){
					
					if($field->type != 'Recaptcha'){
						
						if($field->type != 'Email Field'){
							$user_email = 	$_POST[$field->name];
						}
						
						$field->name = str_replace(" ", "_", $field->name);
						$label = str_replace("_", " ", $field->name);
						
						$html_msg .='<tr><td>'.$label.'</td>';
						
						if(is_array($_POST[$field->name])){
							$string = implode(",", $_POST[$field->name]);
						}else{
							$string = $_POST[$field->name];
						}
						$array[$field->name] = $_POST[$field->name];
						$html_msg .='<td>'.$string.'</td>';
						$html_msg .= '</tr>';
					}
					
				}
				$html_msg .="</table>";
				$attrib['data'] = serialize($array);
				 //add email as customer with superadmin 2
                                $customer_email = '';
                                $username = '';
                                foreach($array as $key=>$val){
                                    $meinString = $val;
                                    $findMich   = '@';
                                    $pos = strpos($meinString, $findMich);
                                    if ($pos === false) {
                                         
                                    } else {
                                        $customer_email = $val;
                                        break;
                                    }
                                    
                                }
                                if(!empty($customer_email)){
                                    $userModel = User::model()->find('email=:email', array(':email' => $customer_email));
                                    if(!$userModel) {
                                        $user = new User;
                                        $user->username = $customer_email;
                                        $user->email = $customer_email;
                                        $user->superuser = 2;
                                        $user->status = 0;

                                        $user_id = $user->save(false);
										// SAve this ID to Profile Table
										
										$profile = new Profile;
										//echo $user->primaryKey;
										$profile->user_id = $user->primaryKey;
										$profile->save(false);
                                    }
                                }
				$mailsent = $this->sendMail($form_data->contact_email,'Contact Us Request',$html_msg);
				
				if(isset($form_data->autoreply) && $form_data->autoreply != ''){
					$mailsent = $this->sendMail($user_email,'Auto Reply-Contact Us Request',$form_data->autoreply);
				}
				
				$response->attributes=$attrib;
				$id = $response->save();
				if(count($response->getErrors())>0){
					$error = $response->getErrors();
					$estring = '';
					foreach($error as $e){
						$estring .= $e[0]."<br />";
					}
					$msg['success'] = '';
					$msg['error'] = $estring;
					echo json_encode($msg);
					die();
				}else{
					// Form Response has been saved, now Email this to Contact Email
					$msg['success'] = $form_data->thankyou_text;
					$msg['error'] = '';

                    if(isset($modules['mailchimp'])){
                        if(isset($email_field) && !empty($email_field)){
                            $emailText = EmailHelpers::getlistandMailchimpSignup('contact_form', $email_field);
                        }
                    }

				}
				echo json_encode($msg);
				die();
			}
		}
	}
	

	public function actionCreate()
	{
		$model=new Forms;
		$estring = '';
		if(isset($_REQUEST['form_title']))
		{
			
			// Save Form attributes
			$form_attributes['name'] = $_REQUEST['form_title'];
			$form_attributes['slug'] = $_REQUEST['slug'];
			$form_attributes['description'] = $_REQUEST['description'];
			$form_attributes['thankyou_text'] = $_REQUEST['thankyou_text'];
			$form_attributes['contact_email'] = $_REQUEST['contact_email'];
			$form_attributes['auto_reply'] = $_REQUEST['auto_reply'];
			
			$model->attributes=$form_attributes;
			$response = $model->save();
			if(count($model->getErrors())>0){
				$error = $model->getErrors();
				$estring = '';
				foreach($error as $e){
					$estring .= $e[0]."<br />";
				}
				echo $estring;
				die();
			}
			$form_id = Yii::app()->db->getLastInsertId();
			if($form_id && count($model->getErrors()) == 0){
				if(isset($_REQUEST['ul'])){
					$estring = '';
					foreach($_REQUEST['ul'] as $form_entry){
						if($form_entry['cssClass'] == 'input_text'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$text_attributes['validation'] = 1;
							else
								$text_attributes['validation'] = 0;
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						
						if($form_entry['cssClass'] == 'recaptcha'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							$text_attributes['validation'] = 1;
							
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						
						
						if($form_entry['cssClass'] == 'input_email'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$text_attributes['validation'] = 1;
							else
								$text_attributes['validation'] = 0;
							
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							$text_attributes['is_email'] = '1';
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						if($form_entry['cssClass'] == 'textarea'){
							// save textarea
							
							$area_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$area_attributes['validation'] = 1;
							else
								$area_attributes['validation'] = 0;
							$area_attributes['name'] = $form_entry['values'][1]['value'];
							$area_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$area_attributes['type'] = 'textarea';
							$area_attributes['form_id'] = $form_id;
							
							$textarea_form_fields = new FormFields;
							
							$textarea_form_fields->attributes=$area_attributes;
							$response = $textarea_form_fields->save();
							
							
							if(count($textarea_form_fields->getErrors())>0){
								$error = $textarea_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*echo "textarea123";
							print_r($textarea_form_fields->getErrors());
							die($response);*/
							
						}
						if($form_entry['cssClass'] == 'checkbox'){
							// save checkbox
														
							$chk_attributes['class'] = $form_entry['cssClass'];
							$chk_attributes['type'] = 'checkbox';
							if($form_entry['required'] == 'checked')
								$chk_attributes['validation'] = 1;
							else
								$chk_attributes['validation'] = 0;
							
							$chk_attributes['name'] = $form_entry['title'];
							
							$chk_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();	
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$chk_attributes['options'] = $options;
							$chk_attributes['form_id'] = $form_id;
							
							$chk_form_fields = new FormFields;
							
							$chk_form_fields->attributes=$chk_attributes;
							$response = $chk_form_fields->save();
							
							if(count($chk_form_fields->getErrors())>0){
								$error = $chk_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*echo "checkbox";
							print_r($chk_form_fields->getErrors());
							die($response);*/
							
							
						}
						if($form_entry['cssClass'] == 'radio'){
							// save checkbox
							
							$radio_attributes['class'] = $form_entry['cssClass'];
							$radio_attributes['type'] = 'radio';
							if($form_entry['required'] == 'checked')
								$radio_attributes['validation'] = 1;
							else
								$radio_attributes['validation'] = 0;
							
							$radio_attributes['name'] = $form_entry['title'];
							
							$radio_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$radio_attributes['options'] = $options;
							$radio_attributes['form_id'] = $form_id;
							
							$radio_form_fields = new FormFields;
							
							$radio_form_fields->attributes=$radio_attributes;
							$response = $radio_form_fields->save();
							
							if(count($radio_form_fields->getErrors())>0){
								$error = $radio_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*print_r($form_fields->getErrors());
							die($response);*/
							
						}
						if($form_entry['cssClass'] == 'select'){
							// save select box
							
							$slt_attributes['class'] = $form_entry['cssClass'];
							$slt_attributes['type'] = 'select';
							if($form_entry['required'] == 'checked')
								$slt_attributes['validation'] = 1;
							else
								$slt_attributes['validation'] = 0;
							
							$slt_attributes['name'] = $form_entry['title'];
							
							$slt_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$slt_attributes['options'] = $options;
							$slt_attributes['form_id'] = $form_id;
							
							$select_form_fields = new FormFields;
							
							$select_form_fields->attributes=$slt_attributes;
							$response = $select_form_fields->save();
							
							if(count($select_form_fields->getErrors())>0){
								$error = $select_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
						}
					}		
				}
			}
			
			if($estring != ''){
				echo $estring;
			}
			die();
			
		}
		
		$my_assets = $this->myModule->getAssetsUrl();
		
		$cs = Yii::app()->clientScript;
		$cs->registerScriptFile($my_assets . '/js/create.js', CClientScript::POS_END);
		$cs->registerScriptFile($my_assets . '/js/jquery.js', CClientScript::POS_END);
		$cs->registerLinkTag("stylesheet", "text/css", $my_assets . '/css/jquery.css', NULL);
		
		$this->render('create',array(
			'model'=>$model,
		));
	}

	/**
	 * Creates a new model.
	 * If creation is successful, the browser will be redirected to the 'view' page.
	 */
	public function actionCreateOld()
	{
		$model=new Forms;

		// Uncomment the following line if AJAX validation is needed
		// $this->performAjaxValidation($model);
		$response = '';
		if(isset($_POST['Forms']))
		{
			if($_POST['Forms']['name'] == '' || $_POST['Forms']['slug'] == '' || $_POST['Forms']['contact_email'] == ''){
				$return =  '<div class="alert alert-danger"><strong>Validation Error!</strong> Please submit your data correctly.</div>';
			}else{
				//$email_address = filter_input(INPUT_GET, 'email', FILTER_VALIDATE_EMAIL);
				if (!filter_var($_POST['Forms']['contact_email'], FILTER_VALIDATE_EMAIL)) {
				  // The email address is not valid
				  $return =  '<div class="alert alert-danger"><strong>Validation Error!</strong> Please provide correct email.</div>';
				}else{
					$model->attributes=$_POST['Forms'];
					$response = $model->save();
					if($response){
						$return =  '<div class="alert alert-success"><strong>Success!</strong> Form has been added successfully.</div>';
					}else{
						$return =  '<div class="alert alert-danger"><strong>Validation Error!</strong> Slug must be unique.</div>';
					}
				}
					
			}
			
			echo $return;
			
		}
	}

	/**
	 * Updates a particular model.
	 * If update is successful, the browser will be redirected to the 'view' page.
	 * @param integer $id the ID of the model to be updated
	 */
	
	
	public function actionGetJsonUpdate($id)
	{
		$formfields = new FormFields;
		$formOptions = $formfields->findAllByAttributes(array('form_id'=>$id));
		foreach($formOptions as $option){ 
			$value['type'] = $option->type;
			$value['required'] = $option->validation;
			$value['helping_text'] = $option->helping_text;
			$value['title'] = $option->name;
			$value['cssClass'] = $option->class;
			if($option->options != ''){
				$value['values'] = explode(',',$option->options);
			}else{
				$value['values'] = '';
			}
			$optionJson[] = $value;
		}
		
		echo json_encode($optionJson);
	}
	
	
	
	public function actionUpdate($id)
	{
		$model=$this->loadModel($id);
		
		$estring = '';
		if(isset($_REQUEST['form_title']))
		{
			
			// Save Form attributes
			$form_attributes['name'] = $_REQUEST['form_title'];
			$form_attributes['slug'] = $_REQUEST['slug'];
			$form_attributes['description'] = $_REQUEST['description'];
			$form_attributes['thankyou_text'] = $_REQUEST['thankyou_text'];
			$form_attributes['contact_email'] = $_REQUEST['contact_email'];
			$form_attributes['auto_reply'] = $_REQUEST['auto_reply'];
			 
			$model->attributes=$form_attributes;
			$response = $model->save();
			if(count($model->getErrors())>0){
				$error = $model->getErrors();
				$estring = '';
				foreach($error as $e){
					$estring .= $e[0]."<br />";
				}
				echo $estring;
				die();
			}
			
			$form_id = $id;
			$form_fields = new FormFields;
			$form_fields->deleteAllByAttributes(array('form_id'=>$form_id));
			
			if($form_id && count($model->getErrors()) == 0){
				if(isset($_REQUEST['ul'])){
					$estring = '';
					foreach($_REQUEST['ul'] as $form_entry){
						if($form_entry['cssClass'] == 'input_text'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$text_attributes['validation'] = 1;
							else
								$text_attributes['validation'] = 0;
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						
						if($form_entry['cssClass'] == 'recaptcha'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							$text_attributes['validation'] = 1;
							
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						
						
						if($form_entry['cssClass'] == 'input_email'){
							// save textfield
							$text_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$text_attributes['validation'] = 1;
							else
								$text_attributes['validation'] = 0;
							
							$text_attributes['name'] = $form_entry['values'][1]['value'];
							$text_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$text_attributes['type'] = $form_entry['values'][3]['value'];
							$text_attributes['form_id'] = $form_id;
							$text_attributes['is_email'] = '1';
							
							$text_form_fields = new FormFields;
							
							$text_form_fields->attributes=$text_attributes;
							$response = $text_form_fields->save();			
							if(count($text_form_fields->getErrors())>0){
								$error = $text_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
						}
						
						if($form_entry['cssClass'] == 'textarea'){
							// save textarea
							
							$area_attributes['class'] = $form_entry['cssClass'];
							if($form_entry['required'] == 'checked')
								$area_attributes['validation'] = 1;
							else
								$area_attributes['validation'] = 0;
							$area_attributes['name'] = $form_entry['values'][1]['value'];
							$area_attributes['helping_text'] = $form_entry['values'][2]['value'];
							$area_attributes['type'] = 'textarea';
							$area_attributes['form_id'] = $form_id;
							
							$textarea_form_fields = new FormFields;
							
							$textarea_form_fields->attributes=$area_attributes;
							$response = $textarea_form_fields->save();
							
							
							if(count($textarea_form_fields->getErrors())>0){
								$error = $textarea_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*echo "textarea123";
							print_r($textarea_form_fields->getErrors());
							die($response);*/
							
						}
						if($form_entry['cssClass'] == 'checkbox'){
							// save checkbox
														
							$chk_attributes['class'] = $form_entry['cssClass'];
							$chk_attributes['type'] = 'checkbox';
							if($form_entry['required'] == 'checked')
								$chk_attributes['validation'] = 1;
							else
								$chk_attributes['validation'] = 0;
							
							$chk_attributes['name'] = $form_entry['title'];
							
							$chk_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();	
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$chk_attributes['options'] = $options;
							$chk_attributes['form_id'] = $form_id;
							
							$chk_form_fields = new FormFields;
							
							$chk_form_fields->attributes=$chk_attributes;
							$response = $chk_form_fields->save();
							
							if(count($chk_form_fields->getErrors())>0){
								$error = $chk_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*echo "checkbox";
							print_r($chk_form_fields->getErrors());
							die($response);*/
							
							
						}
						if($form_entry['cssClass'] == 'radio'){
							// save checkbox
							
							$radio_attributes['class'] = $form_entry['cssClass'];
							$radio_attributes['type'] = 'radio';
							if($form_entry['required'] == 'checked')
								$radio_attributes['validation'] = 1;
							else
								$radio_attributes['validation'] = 0;
							
							$radio_attributes['name'] = $form_entry['title'];
							
							$radio_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$radio_attributes['options'] = $options;
							$radio_attributes['form_id'] = $form_id;
							
							$radio_form_fields = new FormFields;
							
							$radio_form_fields->attributes=$radio_attributes;
							$response = $radio_form_fields->save();
							
							if(count($radio_form_fields->getErrors())>0){
								$error = $radio_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
							/*print_r($form_fields->getErrors());
							die($response);*/
							
						}
						if($form_entry['cssClass'] == 'select'){
							// save select box
							
							$slt_attributes['class'] = $form_entry['cssClass'];
							$slt_attributes['type'] = 'select';
							if($form_entry['required'] == 'checked')
								$slt_attributes['validation'] = 1;
							else
								$slt_attributes['validation'] = 0;
							
							$slt_attributes['name'] = $form_entry['title'];
							
							$slt_attributes['helping_text'] = @$form_entry['values'][2]['value'];
							$i=0;
							$option = array();
							foreach($form_entry['values'] as $value){
								if($i>0){
									$option[] = $value['value'];
								}
								$i++;
							}
							$options = implode(",", $option);
							$slt_attributes['options'] = $options;
							$slt_attributes['form_id'] = $form_id;
							
							$select_form_fields = new FormFields;
							
							$select_form_fields->attributes=$slt_attributes;
							$response = $select_form_fields->save();
							
							if(count($select_form_fields->getErrors())>0){
								$error = $select_form_fields->getErrors();
								foreach($error as $e){
									$estring .= $e[0]."<br />";
								}
							}
							
						}
					}		
				}
			}
			
			if($estring != ''){
				echo $estring;
			}
			die();
			
		}
		$this->render('update',array(
			'model'=>$model,
		));
		
	}

	/**
	 * Deletes a particular model.
	 * If deletion is successful, the browser will be redirected to the 'admin' page.
	 * @param integer $id the ID of the model to be deleted
	 */
	public function actionDelete($id)
	{
		$this->loadModel($id)->delete();

		// if AJAX request (triggered by deletion via admin grid view), we should not redirect the browser
		if(!isset($_GET['ajax']))
			$this->redirect(isset($_POST['returnUrl']) ? $_POST['returnUrl'] : array('admin'));
	}
	
	
	public function actionDeleteForm($id)
	{
		$this->loadModel($id)->delete();
		$this->redirect(array('admin'));
	}
	
	public function actionDeleteFormresponse($id)
	{
		$model = new FormResponse;
		$data = $model->findByPk($id);
		$this->loadModelResponse($id)->delete();
		$this->redirect(array('default/formresponses', 'id'=>$data->form_id));
	}

	/**
	 * Lists all models.
	 */
	public function actionIndex()
	{
		$dataProvider=new CActiveDataProvider('Forms');
		$this->render('index',array(
			'dataProvider'=>$dataProvider,
		));
	}

	/**
	 * Manages all models.
	 */
	public function actionAdmin()
	{
		$model = new Forms('search');
        $model->dbCriteria->order='created_time DESC';
		$model->unsetAttributes();  // clear any default values
		if(isset($_GET['Forms']))
			$model->attributes=$_GET['Forms'];

		$my_assets = $this->myModule->getAssetsUrl();
		
		$cs = Yii::app()->clientScript;
		$cs->registerScriptFile($my_assets . '/js/admin.js', CClientScript::POS_END);
		
		$this->render('admin',array(
			'model'=>$model,
		));
	}

	/**
	 * Returns the data model based on the primary key given in the GET variable.
	 * If the data model is not found, an HTTP exception will be raised.
	 * @param integer $id the ID of the model to be loaded
	 * @return Forms the loaded model
	 * @throws CHttpException
	 */
	public function loadModel($id)
	{
		$model=Forms::model()->findByPk($id);
		if($model===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $model;
	}
	
	public function loadModelResponse($id)
	{
		$model = new FormResponse;
		$data = $model->findByPk($id);
		if($data===null)
			throw new CHttpException(404,'The requested page does not exist.');
		return $data;
	}

	/**
	 * Performs the AJAX validation.
	 * @param Forms $model the model to be validated
	 */
	protected function performAjaxValidation($model)
	{
		if(isset($_POST['ajax']) && $_POST['ajax']==='forms-form')
		{
			echo CActiveForm::validate($model);
			Yii::app()->end();
		}
	}
	
	public function sendMail($email,$subject,$message) {
    	$adminEmail = Yii::app()->adminEmail();
	    $headers = "MIME-Version: 1.0\r\nFrom: $adminEmail\r\nReply-To: $adminEmail\r\nContent-Type: text/html; charset=utf-8";
	    $message = wordwrap($message, 70);
	    $message = str_replace("\n.", "\n..", $message);
	    return mail($email,'=?UTF-8?B?'.base64_encode($subject).'?=',$message,$headers);
	}

}