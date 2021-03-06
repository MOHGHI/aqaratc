<?php
namespace PHPMVC\Models;

use PHPMVC\LIB\SessionManager;

class UserModel extends AbstractModel
{
    public $UserId;
    public $Username;
    public $Password;
    public $Email;
    public $PhoneNumber;
    public $SubscriptionDate;
    public $LastLogin;
    public $GroupId;
    public $Status;
    public $Activation;

    /**
     * @var UserProfileModel
     */
    public $profile;
    public $privileges;

    protected static $tableName = 'app_users';

    protected static $tableSchema = array(
        'UserId'            => self::DATA_TYPE_INT,
        'Username'          => self::DATA_TYPE_STR,
        'Password'          => self::DATA_TYPE_STR,
        'Email'             => self::DATA_TYPE_STR,
        'PhoneNumber'       => self::DATA_TYPE_STR,
        'SubscriptionDate'  => self::DATA_TYPE_DATE,
        'LastLogin'         => self::DATA_TYPE_STR,
        'GroupId'           => self::DATA_TYPE_INT,
        'Status'            => self::DATA_TYPE_INT,
        'Activation'        => self::DATA_TYPE_STR,
    );

    protected static $primaryKey = 'UserId';

    public function cryptPassword($password)
    {
        $this->Password = crypt($password, APP_SALT);
    }

    public static function getUsers(UserModel $user)
    {
        return self::get(
        'SELECT au.*, aug.GroupName GroupName, ab.BranchName BranchName FROM ' . self::$tableName . ' au INNER JOIN app_users_groups aug ON aug.GroupId = au.GroupId INNER JOIN app_branches ab ON ab.BranchId = au.BranchId WHERE au.UserId != ' . $user->UserId
        );
    }

    public static function userExists($username)
    {
        return self::get('
            SELECT * FROM ' . self::$tableName . ' WHERE Username = "' . $username . '"
        ');
    }

    public static function emailExists($email)
    {
        return self::get('
            SELECT * FROM ' . self::$tableName . ' WHERE Email = "' . $email . '"
        ');
    }

    public static function authenticate ($username, $password, SessionManager $session)
    {
        $password = crypt($password, APP_SALT) ;
        $sql = 'SELECT *, (SELECT GroupName FROM app_users_groups WHERE app_users_groups.GroupId = ' . self::$tableName . '.GroupId) GroupName FROM ' . self::$tableName . ' WHERE Username = "' . $username . '" AND Password = "' .  $password . '"';
        $foundUser = self::getOne($sql);
        if(false !== $foundUser) {
            if($foundUser->Status == 2) {
                return 2;
            }
            $foundUser->LastLogin = date('Y-m-d H:i:s');
            $foundUser->save();
            $session->logged = 1;
            $session->u = serialize($foundUser);
            return $foundUser;
        }
        return false;
    }

    /**
     * @param $code
     * @return bool|UserModel
     */
    public static function getUserByActivationCode($code)
    {
        $sql = 'SELECT * FROM ' . self::$tableName . " WHERE activation = '" . $code . "'";
        $user = self::getOne($sql);
        return false !== $user ? $user : false;
    }
}