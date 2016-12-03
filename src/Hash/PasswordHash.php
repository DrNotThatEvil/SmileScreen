<?php 
namespace SmileScreen\Hash;

use SmileScreen\Database as Database;

class PasswordHash 
{
    public static function checkHash($password, $hash) 
    {
        return password_verify($password, $hash);
    }


    public static function getUserByLogin($model, $username, $password, 
        $usernameField = 'email', $passwordField = 'password') 
    {
        $select = new Database\SelectQuery();
        $select->where([$usernameField => ['=', $username]]);

        $results = $model::where($select);

        if (count($results) == 0) {
            return false; 
        }
        
        if (count($results) > 1) {
            return false; 
        }

        $userModel = $results[0];

        if (password_verify($password, $userModel->$passwordField)) {
            return $userModel; 
        }

        return false;
    }
}
