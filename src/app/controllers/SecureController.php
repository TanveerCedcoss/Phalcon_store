<?php

use Phalcon\Mvc\Controller;
use Phalcon\Acl\Adapter\Memory;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * SecureController contains functions mainly related to the security features(like creating tokens, acl files, etc)
 * @package Store
 * @author Tanveer
 */
class SecureController extends Controller
{
    /**
     * createTokenAction creates a JWT token based on the role passed to it as an argument
     * It recieves role as param
     * @param [string] $role
     * @return [str] $jwt which is json web token
     */
    public function createTokenAction($role, $name = '', $email = '')
    {
        $now     = new DateTimeImmutable();
        $expires = $now->modify('+1 day')->getTimestamp();
        $key = "example_key";
        $payload = array(
            "iss" => "http://localhost:8080",
            "aud" => "http://localhost:8080",
            "iat" => $now->getTimestamp(),
            "nbf" => $now->getTimestamp(),
            "rol" => $role,
            "nam" => $name,
            "ema" => $email,
            "exp" => $expires
        );
        $jwt = JWT::encode($payload, $key, 'HS256');
        
        return $jwt;
    }


    /**
     * addRoleAction is called when adding a new role in ACL
     * It gets the role name add the role in acl file and in db as well
     *
     * @return void
     */
    public function addRoleAction()
    {
        if ($this->request->ispost()) {
            $role = $this->request->getpost('roleAdd');
            $aclFile = APP_PATH . '/security/acl.cache';

            if (true !== is_file($aclFile)) {
                $acl = new Memory();

                $acl->addRole($role);
                file_put_contents(
                    $aclFile,
                    serialize($acl)
                );
                $permission = new Roles();
                $permission->role = $role;
                $permission->save();
            } else {
                $acl = unserialize(
                    file_get_contents($aclFile)
                );
                $acl->addRole($role);
                file_put_contents(
                    $aclFile,
                    serialize($acl)
                );
                $permission = new Roles();
                $permission->role = $role;
                $permission->save();
            }
            $this->view->message = 'Role added successfully';
        }
    }

    /**
     * addComponent perform two main actions. It adds a new component to the acl file.
     * Secondly, it also define the accessible pages for any role.
     * It also save the permissions of every role in db
     * @return void
     */
    public function addComponentAction()
    {
        $this->view->allRoles = Roles::find();
        $searchComponent = new App\Components\SearchComponent();
        $controllers = $searchComponent->getControllersAction();
        $this->view->controllers = $controllers;

        if ($this->request->getpost('roleCheck') == 'Select Controllers') {
            $role = $this->request->getpost('selectedRole');
            $this->session->roleSelected = $role;
            $this->view->role = $role;
        }

        if ($this->request->getpost('controllerCheck') == 'Select Actions') {
            $choosenControllers = $this->request->getpost();
            array_pop($choosenControllers);

            $role =  $this->session->roleSelected;

            foreach ($choosenControllers as $key => $value) {
                $methods = $searchComponent->getMethodsAction($value);
                $data = array($key => $methods);
                if (!isset($finalData)) {
                    $finalData = array();
                }
                $finalData = array_merge($finalData, $data);
            }
            $this->view->role = $role;
            $this->view->methods = $finalData;
        }
        if ($this->request->getpost('access') == 'Allow access') {
            $role =  $this->session->roleSelected;
            $components = $this->request->getpost();
            array_pop($components);

            $aclFile = APP_PATH . '/security/acl.cache';

            if (true !== is_file($aclFile)) {
                $acl = new Memory();
                foreach ($components as $key => $value) {
                    $val = explode('-', $value);
                    $acl->addComponent(
                        $val[0],
                        [
                            $val[1],
                        ]
                    );
                    $acl->allow($role, $val[0], $val[1]);
                    $permission = new Permissions();
                    $permission->role = $role;
                    $permission->controller = $val[0];
                    $permission->action = $val[1];
                    $permission->save();
                }
                file_put_contents(
                    $aclFile,
                    serialize($acl)
                );
            } else {
                $acl = unserialize(
                    file_get_contents($aclFile)
                );
                foreach ($components as $key => $value) {
                    $val = explode('-', $key);
                    $acl->addComponent(
                        $val[0],
                        [
                            $val[1],
                        ]
                    );
                    $acl->allow($role, $val[0], $val[1]);
                    $permission = new Permissions();
                    $permission->role = $role;
                    $permission->controller = $val[0];
                    $permission->action = $val[1];
                    $permission->save();
                }
                file_put_contents(
                    $aclFile,
                    serialize($acl)
                );
            }
            $this->view->message = 'Permissions assigned successfully';
        }
    }
}
