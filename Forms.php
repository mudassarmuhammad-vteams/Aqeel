<?php

/**
 * This is the model class for table "forms".
 *
 * The followings are the available columns in table 'forms':
 * @property integer $id
 * @property string $name
 * @property string $slug
 * @property string $description
 * @property string $contact_email
 * @property string $created_time
 *
 * The followings are the available model relations:
 * @property FormResponses[] $formResponses
 * @property FormToFeilds[] $formToFeilds
 */
class Forms extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Forms the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'forms';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('name, slug, contact_email', 'required'),
			array('slug , name', 'unique'),
			array('contact_email', 'email'),
			array('name, slug, contact_email', 'length', 'max'=>255),
			array('description, thankyou_text, auto_reply', 'length', 'max'=>10000),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, slug, description, contact_email, created_time', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'formResponses' => array(self::HAS_MANY, 'FormResponses', 'form_id'),
			'formToFeilds' => array(self::HAS_MANY, 'FormToFeilds', 'form_id'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'slug' => 'Slug',
			'description' => 'Description',
			'contact_email' => 'Contact Email',
			'created_time' => 'Created Time',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->compare('slug',$this->slug,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('contact_email',$this->contact_email,true);
		$criteria->compare('created_time',$this->created_time,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}