<script>
	$(function(){
		$('#my-form-builder').formbuilder({
			'save_url': '<?php echo Yii::app()->baseUrl;?>/backend.php?r=forms/default/create',
			//'load_url': '<?php echo Yii::app()->baseUrl;?>/backend.php?r=forms/default/create',
			'useJson' : false
		});
		$(function() {
			$("#my-form-builder ul").sortable({ opacity: 0.6, cursor: 'move'});
		});
	});
	
</script>
<?php
$this->breadcrumbs=array(
	'Website Content'=>array('/page/default/dashboard'),
	'Form Builder'=>array('admin'),
        'Create'=>array('create'),
       
);
?><!-- End Breadcrumb -->


<div class="listing_widget">
        <h2 class="title">Add New Form</h2>
</div>
<p>Add New Contact Form</p>


<div class="widget">
    <div class="widget-content no-padding">
        <div class="widget-content-inner">

<div class="form">
<div class="form-horizontal">

<div class="top-header"></div>
    <form action="#" method="post" id="form_builder">
    <div id="tabs1">
        
            <ul>
                <li><a href="#tabs-1">General Information</a></li>
                <li><a href="#tabs-2">Fields Settings</a></li>
                <li><a href="#tabs-3">Auto Reply Email</a></li>
            </ul>
            
            <div id="tabs-1">
                <div class="alert alert-danger" style="margin-top:10px; display:none;"></div>
                <div class="alert alert-success" style="margin-top:10px; display:none;"></div>

                <input type="hidden" value="<?php echo Yii::app()->request->csrfToken; ?>" name="YII_CSRF_TOKEN" id="YII_CSRF_TOKEN" />
                <div class="control-group">
                    <label class="required" for="Page_title">Form Title <span class="required">*</span></label>
                    <div class="controls">
                        <input type="text" id="title" name="title" placeholder="Form Title" maxlength="255" size="60">                                        
                    </div>
                </div>
                
                <div class="control-group">
                    <label class="required" for="Page_title">Form Slug <span class="required">*</span></label>
                    <div class="controls">
                        <input type="text" id="slug" name="slug" maxlength="255" size="60" disabled="disabled">                                        
                    </div>
                </div>
                
                <div class="control-group">
                    <label class="required" for="Page_title">Contact Email <span class="required">*</span></label>
                    <div class="controls">
                        <input type="text" id="contact_email" name="contact_email" placeholder="Contact Email" maxlength="255" size="60">                                        
                    </div>
                </div>
                    
                <div class="control-group">
                    <label>Form Description</label>
                    <div class="controls">
                    <textarea  id="form-descp" name="form-descp" placeholder="Form Discription" class="descp-textarea ckeditor"></textarea>
                    </div>
                </div>
                
                <div class="control-group">
                    <label>Thankyou Text</label>
                    <div class="controls">
                    <textarea  id="form-thanks" name="form-thanks" placeholder="Thank you Text on Successful Submission" class="descp-textarea ckeditor"></textarea>
                    </div>
                </div>
                
            </div>
            
            <div id="tabs-2">
                <div id="dynamicFields">
                    <div id="my-form-builder"></div>
                    <div id="mycustom" style="display:none">
                         <select class="frmb-control-new"   data-native-menu="false">
                             <option value="Text Field" class="text_field">Text Field</option>
                             <option value="Email Field" class="email_field">Email Field</option>
                             <option value="Paragraph" class="paragraph_field">Paragraph</option>
                             <option value="Checkboxes" class="checkbox_group">Checkboxes</option>
                             <option value="Radio" class="radio_group">Radio</option>
                             <option value="Select List" class="select_list">Select List</option>
                         </select>
                        <div class="clear"></div>
                        
                        <div  class="mycust-field">
                             <input type="text" disabled  style="display:none;" >
                        </div>
                     </div>
                     </div>
            </div>
    	
        
        <div id="tabs-3">
            <div class="alert alert-info">
                <strong>Info!</strong> Leave Empty if you dnt want to sent auto reply email.
            </div>
            <div class="control-group">
                    <label>Auto Reply Email Template</label>
                    <div class="controls">
                    <textarea id="auto_reply" name="auto_reply" placeholder="" class="descp-textarea ckeditor"></textarea>
                    </div>
                </div>
        </div>
   
    
</div>	
</form> 
<!-- form -->
</div>
 
 
</div>
</div>
</div>
</div>