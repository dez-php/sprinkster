<?php

namespace Chat;

use \User\User;

class ApiController extends \Core\Base\Action {

    public function validateAction() {
        $token = $this->getRequest()->getPost('token');
        $ip = $this->getRequest()->getPost('ip');

        if (!Session::isValidToken($token) || !$ip)
            return $this->responseJsonCallback([ 'status' => FALSE]);

        $session = Session::validate($token, $ip);
        $user = new User;
        $user = $user->get($session ? $session->user_id : 0);

        if (!$session || !$user)
            return $this->responseJsonCallback([ 'status' => FALSE]);

        $userdata = [
            'ID' => $user->id,
            'username' => $user->username,
            'name' => $user->getUserFullName(),
            'avatar' => (array) \User\Helper\Avatar::getImage('small', $user),
        ];

        return $this->responseJsonCallback([ 'status' => (bool) $session, 'user' => $userdata]);
    }

    public function invalidateAction() {
        $token = $this->getRequest()->getPost('token');

        if (!Session::isValidToken($token))
            return $this->responseJsonCallback([ 'status' => FALSE]);

        return $this->responseJsonCallback([ 'status' => Session::invalidate($token)]);
    }

    public function contactsAction() {
        $token = $this->getRequest()->getPost('token');
        $ip = $this->getRequest()->getPost('ip');

        if (!Session::isValidToken($token) || !$ip)
            return $this->responseJsonCallback(NULL);

        $contacts = Session::contacts($token, $ip);

        if (!$contacts || 0 >= $contacts->count())
            return $this->responseJsonCallback(NULL);

        $result = [];

        foreach ($contacts as $contact) {
            $result[] = [
                'id' => $contact->id,
                'username' => $contact->username,
                'name' => $contact->getUserFullName(),
                'avatar' => (array) \User\Helper\Avatar::getImage('small', $contact),
            ];
        }

        return $this->responseJsonCallback($result);
    }

}
