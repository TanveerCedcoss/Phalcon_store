<?php

use Phalcon\Mvc\Controller;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;
use Phalcon\Http\Response;

/**
 * UserController class is for all the operations related to the users
 */
class UserController extends Controller
{
    /**
     * addUser adds a new user in db. It also pass list of all roles to the view to display in dropdown
     * It also calls createToken action of SecureController to generate token for the user
     * @return void
     */
    public function addUserAction()
    {
        $adapter = new Stream('../app/logs/signup.log');
        $logger  = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );
        $roles = Roles::find();

        //passing list of all roles to view to display in the dropdown
        $this->view->allRoles = $roles;
        if (!$this->request->getpost()) {
            return;
        }

         /**
         * Instantiating an object for a class which sanitise the passed values
         * and then using it to sanitizing the data recieved from the form.
         */
        $sanitize = new \App\Components\MyEscaper();
        $role = $sanitize->sanitize($this->request->getPost('selectedRole'));
        $getToken = new SecureController();
        //getting token for the said role
        $token = $getToken->createTokenAction($role);

        //sanitising data
        $InputData = array(
            'name' => $sanitize->sanitize($this->request->getPost('name')),
            'email' => $sanitize->sanitize($this->request->getPost('email')),
            'password' => $sanitize->sanitize($this->request->getPost('password')),
            'role' => $sanitize->sanitize($this->request->getPost('selectedRole')),
            'token' => $token
        );

        $user = new Users;
        //Assigning the values and then adding those values to the database
        if ($this->request->ispost()) {
            $user->assign(
                $InputData,
                [
                    'name',
                    'email',
                    'password',
                    'role',
                    'token'
                ]
            );
            $success = $user->save();

              /**
             * Passing the message to view of this class based on whether the database operation was successful or not
             */
            $this->view->success = $success;
            if ($success) {
                $this->view->message = "Register succesfully";
            } else {
                $this->view->message = "Not Register succesfully due to following reason: <br>" . implode("<br>", $user->getMessages());
                //logging the errors that caused the operation to fail
                $logger->error(implode(' & ', $user->getMessages()));
            }
        }
    }

     /**
     * loginAction recieves the data from login form and perform checks to identify whether a user exists or not
     * and redirect the user to a landing page if it exists
     * it also performs checks to see if the user is already login
     * or previously asked to be remembered and redirecting the user accordingly
     * @return void
     */
    public function loginAction()
    {
        $adapter = new Stream('../app/logs/login.log');
        $logger  = new Logger(
            'messages',
            [
             'main' => $adapter,
            ]
        );
        
        if ($this->request->ispost()) {
            $info = $this->request->getpost();
            $sanitize = new \App\Components\MyEscaper();
            $InputData = array(
                'email' => $sanitize->sanitize($info['email']),
                'password' => $sanitize->sanitize($info['password'])
            );
            $user = Users::find(
                [
                    'conditions' => 'email = :email: and password = :password:' ,
                    'bind'       => [
                        'email' => $InputData['email'],
                        'password' => $InputData['password'],
                    ]
                ]
            );
            if (count($user)) {
                $role = json_decode(json_encode($user[0]->role));
                if ($role != 'admin') {
                    $response = new Response();
                    $logger->error('not admin');
                    $response->setStatusCode(404, 'Not admin');
                    $response->setContent("<h4>You are not admin, please use token to access!</h4>");
                    $response->send();
                    die();
                }
                $admin = [
                    "admin_email" => $user-> email,
                ];
                $this->session->set('admin', (object)$admin);
                $this->response->redirect('product/view');
               

            } else {
                $response = new Response();
                $logger->error('Authentication failed');
                $response->setStatusCode(404, 'Not Found');
                $response->setContent("<h4>Authentication failed</h4>");
                $response->send();
                die();
            }
        }
    }

    /**
     * logoutAction is called when a user click on logout
     * it deletes the session details of the user(which is used to check the status whether a user is logged in or not)
     * also, if set, it deletes the cookies used for remembering the user
     * @return void
     */
    public function logoutAction()
    {
        $this->session->remove('admin');
        $this->response->redirect('user/login');
    }

      /**
     * viewAction get and pass all the users to the View of this class
     *
     * @return void
     */
    public function viewAction()
    {
        $this->view->users = Users::find();
    }

    /**
     * deleteAction gets the ID of the user as param and then find the user in db based on that
     *  and finally delete it from the the database and redirects to view of this controller
     * @param [num] $id
     * @return void
     */
    public function deleteAction($id)
    {
        $delUser = Users::findFirst($id);
        $delUser->delete();
        $this->response->redirect('user/view');
    }
}
