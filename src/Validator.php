<?php

namespace App;

class Validator
{
    public function validate(array $user)
    {
        $errors = [];
        if (empty($user['id'])) {
            $errors['id'] = "No ID, ask admin!";
        }
        if (empty($user['name'])) {
            $errors['name'] = "No name";
        }
        if (empty($user['email'])) {
            $errors['email'] = "No email";
        }
        if (empty($user['city'])) {
            $errors['city'] = "No city";
        }
        return $errors;
    }
}