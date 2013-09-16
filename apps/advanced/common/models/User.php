<?php
namespace common\models;

use yii\db\ActiveRecord;
use yii\helpers\Security;
use yii\web\IIdentity;

/**
 * Class User
 * @package common\models
 *
 * @property integer $id
 * @property string $username
 * @property string $password_hash
 * @property string $password_reset_token
 * @property string $email
 * @property string $auth_key
 * @property integer $role
 * @property integer $status
 * @property integer $create_time
 * @property integer $update_time
 */
class User extends ActiveRecord implements IIdentity
{
	/**
	 * @var string the raw password. Used to collect password input and isn't saved in database
	 */
	public $password;

	const STATUS_DELETED = 0;
	const STATUS_ACTIVE = 10;

	const ROLE_USER = 10;

	public function behaviors()
	{
		return array(
			'timestamp' => array(
				'class' => 'yii\behaviors\AutoTimestamp',
				'attributes' => array(
					ActiveRecord::EVENT_BEFORE_INSERT => array('create_time', 'update_time'),
					ActiveRecord::EVENT_BEFORE_UPDATE => 'update_time',
				),
			),
		);
	}

	/**
	 * Finds an identity by the given ID.
	 *
	 * @param string|integer $id the ID to be looked for
	 * @return IIdentity|null the identity object that matches the given ID.
	 */
	public static function findIdentity($id)
	{
		return static::find($id);
	}

	/**
	 * Finds user by username
	 *
	 * @param string $username
	 * @return null|User
	 */
	public static function findByUsername($username)
	{
		return static::find(array('username' => $username, 'status' => static::STATUS_ACTIVE));
	}

	/**
	 * @return int|string current user ID
	 */
	public function getId()
	{
		return $this->id;
	}

	/**
	 * @return string current user auth key
	 */
	public function getAuthKey()
	{
		return $this->auth_key;
	}

	/**
	 * @param string $authKey
	 * @return boolean if auth key is valid for current user
	 */
	public function validateAuthKey($authKey)
	{
		return $this->getAuthKey() === $authKey;
	}

	/**
	 * @param string $password password to validate
	 * @return bool if password provided is valid for current user
	 */
	public function validatePassword($password)
	{
		return Security::validatePassword($password, $this->password_hash);
	}

	public function rules()
	{
		return array(
			array('username', 'filter', 'filter' => 'trim'),
			array('username', 'required'),
			array('username', 'string', 'min' => 2, 'max' => 255),

			array('email', 'filter', 'filter' => 'trim'),
			array('email', 'required'),
			array('email', 'email'),
			array('email', 'unique', 'message' => 'This email address has already been taken.', 'on' => 'signup'),
			array('email', 'exist', 'message' => 'There is no user with such email.', 'on' => 'requestPasswordResetToken'),

			array('password', 'required'),
			array('password', 'string', 'min' => 6),
		);
	}

	public function scenarios()
	{
		return array(
			'signup' => array('username', 'email', 'password'),
			'login' => array('username', 'password'),
			'resetPassword' => array('password'),
			'requestPasswordResetToken' => array('email'),
		);
	}

	public function beforeSave($insert)
	{
		if (parent::beforeSave($insert)) {
			if (($this->isNewRecord || $this->getScenario() === 'resetPassword') && !empty($this->password)) {
				$this->password_hash = Security::generatePasswordHash($this->password);
			}
			if ($this->isNewRecord) {
				$this->auth_key = Security::generateRandomKey();
			}
			return true;
		}
		return false;
	}
}
