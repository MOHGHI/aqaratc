<?php
namespace PHPMVC\Controllers;

use PHPMVC\LIB\Helper;
use PHPMVC\LIB\InputFilter;
use PHPMVC\lib\Mailer;
use PHPMVC\lib\Messenger;
use PHPMVC\Models\UserModel;
use PHPMVC\Models\UserProfileModel;

class AuthController extends AbstractController
{
    use Helper;
    use InputFilter;

    public function loginAction()
    {
        if(isset($this->session->u)) {
            $this->redirect('/');
        }

        $this->language->load('auth.login');

        if(isset($_POST['username']) && isset($_POST['password']) && isset($_POST['submit'])) {
            $isAuthorized = UserModel::authenticate($this->filterString($_POST['username']), $_POST['password'], $this->session);
            if($isAuthorized instanceof UserModel) {
                $this->redirect('/');
            } elseif ((int) $isAuthorized == 2) {
                $this->messenger->add($this->language->get('text_user_disabled'), Messenger::APP_MESSAGE_ERROR);
            } else {
                $this->messenger->add($this->language->get('text_user_not_found'), Messenger::APP_MESSAGE_ERROR);
            }
        }

        $this->_view();
    }

    public function registerAction()
    {
        if(isset($this->session->u)) {
            $this->redirect('/');
        }

        $this->language->load('auth.register');
        $this->language->load('mailer.mailer');

        if(isset($_POST['submit'])) {

            $user = new UserModel();
            $user->Username = $this->filterString($_POST['username']);
            $user->cryptPassword($_POST['password']);
            $user->Email = $this->filterString($_POST['email']);
            $user->PhoneNumber = $this->filterString($_POST['phone']);
            $user->GroupId = 2;
            $user->SubscriptionDate = date('Y-m-d');
            $user->LastLogin = date('Y-m-d H:i:s');
            $user->Status = 2;
            $user->Activation = base64_encode(sha1($user->Username . $user->Password . APP_SALT));

            if(UserModel::userExists($user->Username)) {
                $this->messenger->add($this->language->get('message_user_exists'), Messenger::APP_MESSAGE_ERROR);
                goto problem;
            }

            if(UserModel::emailExists($user->Email)) {
                $this->messenger->add($this->language->get('message_email_exists'), Messenger::APP_MESSAGE_ERROR);
                goto problem;
            }

            if($user->save()) {
                $userProfile = new UserProfileModel();
                $userProfile->UserId = $user->UserId;
                $userProfile->FirstName = $user->Username;
                $userProfile->LastName = $user->Username;
                $userProfile->save(false);

                $url = ((isset($_SERVER['HTTPS'])) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . '/';
                $username = $user->Username;
                $activation = $url . 'activate/?code=' . $user->Activation;
                $mailBody = $this->language->get('text_activation_mail');
                $mailBody = preg_replace('/\[\[(name)\]\]/', $username, $mailBody);
                $mailBody = preg_replace('/\[\[(activation)\]\]/', $activation, $mailBody);

                Mailer::notify(
                    $user->Email,
                    $mailBody
                );

                $this->messenger->add($this->language->get('message_user_create_success'));
            } else {
                $this->messenger->add($this->language->get('message_user_create_error'), Messenger::APP_MESSAGE_ERROR);
            }

            $this->redirect('/auth/register');
        }

        problem:
        $this->_view();
    }

    public function logoutAction()
    {
        // TODO: check the cookie deletion
        $this->session->kill();
        $this->redirect('/auth/login');
    }
}