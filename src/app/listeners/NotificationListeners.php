<?php

namespace App\Listeners;

use OrderController;
use Phalcon\Di\Injectable;
use Phalcon\Events\Event;
use ProductController;
use Phalcon\Acl\Adapter\Memory;
use Settings;
use Permissions;
use Roles;
use Phalcon\Security\JWT\Token\Parser;
use Phalcon\Security\JWT\Validator;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * NotificationListeners class is mainly related to all the Events related operations
 * i.e. it contains mainly the event handlers
 * @package NotificationListener
 * @author Tanveer <tanveer@cedcoss.com>
 */
class NotificationListeners extends Injectable
{
    /**
     * updateInventory fires whenever an order is placed and checks if zipcode is provided or not
     * and if not then attach a default zipcode
     * @param Event $event
     * @param OrderController $component
     * @param [num] $order
     * @return $order, if the passed param is empty then it returns a default assigned value else the param itself
     */
    public function updateInventory(Event $event, OrderController $component, $order)
    {
        $settings = Settings::find();

        if ($order['zipcode'] == '') {
            $order['zipcode'] = $settings[0]->default_zipcode;
        }
        return $order;
    }

    /**
     *  addProduct fires whenever an product is added and checks if price and stock are provided or not
     *  and if not then attach a default value from settings
     *  it also perform title optimisation(update value of name by concatinating it with tags)
     *  if selected as such in settings
     * @param Event $event
     * @param ProductController $component
     * @param [type] $product
     * @return $product
     */
    public function addProduct(Event $event, ProductController $component, $product)
    {
        $settings = Settings::find();

        if ($settings[0]->title_option == 2) {
            $product['name'] = $product['name'] . $product['tags'];
        }
        if ($product['price'] == '') {
            $product['price'] = $settings[0]->default_price;
        }
        if ($product['stock'] == '') {
            $product['stock'] = $settings[0]->default_stock;
        }
        return $product;
    }

    /**
     * beforeHandleRequest gets called every time before hitting any URL to check the accessibility permission
     * In case acl is missing is also calls on the function that creates acl
     * @param Event $event
     * @param \Phalcon\Mvc\Application $application
     * @return void
     */
    public function beforeHandleRequest(Event $event, \Phalcon\Mvc\Application $application)
    {
        $aclFile = APP_PATH . '/security/acl.cache';
        if (true === is_file($aclFile)) {
            $acl = unserialize(
                file_get_contents($aclFile)
            );


            if ($this->session->has('admin')) {
                return;
            }
            if ($this->router->getControllerName() == 'user' && $this->router->getActionName() == 'login') {
                return;
            }
            $bearer = $application->request->get('bearer')??'';
            if ($bearer) {
                try {
                    $key = "example_key";
                    $parser      = new Parser();

                    $tokenObject = $parser->parse($bearer);

                    $now           = new \DateTimeImmutable();
                    $expireCheck        = $now->getTimestamp();
            
                    $validator = new Validator($tokenObject, 100);

                    $validator->validateExpiration($expireCheck);

                    $decoded = JWT::decode($bearer, new Key($key, 'HS256'));
                    

                    $role = $decoded->rol;
                    $controller = ucwords($this->router->getControllerName())??'index';
                    $action = $this->router->getActionName()??'index';
                    if (!$role || true !== $acl->isAllowed($role, $controller, $action)) {
                        echo "<h2>Access denied 404</h2>";
                        die();
                    }
                } catch (\Exception $e) {
                    echo $e->getMessage();
                    die;
                }
            } else {
                echo 'Token not provided';
                die;
            }

        } else {
            echo "Token missing";
            $this->buildAclAction();
        }
    }

    /**
     * buildAclAction creates a new acl file if it doesn't exist
     * it also checks for presence of data in db and creates acl file such that it contains all data from db
     * @return void
     */
    public function buildAclAction()
    {
        $aclFile = APP_PATH . '/security/acl.cache';
        if (true !== is_file($aclFile)) {
            $acl = new Memory();


            $acl->addRole('admin');
            $acl->allow('admin', '*', '*');

            $checkRoles = new Roles();
            $checkRoles = Roles::find();
            if (count($checkRoles) > 0) {
                $acl = $this->restoreRolesFromDB($acl);
            }

            $checkPermissions = new Permissions();
            $checkPermissions = Permissions::find();
            if (count($checkPermissions) > 0) {
                $acl = $this->restorePermissionsFromDB($acl);
            }
            file_put_contents(
                $aclFile,
                serialize($acl)
            );
        } else {
            $acl = unserialize(
                file_get_contents($aclFile)
            );
        }
    }

    /**
     * restoreRolesFromDB gets called when during creation of acl files role some data is found in db
     *
     * @param [cache] $acl
     * @return srting
     */
    public function restoreRolesFromDB($acl)
    {
        $restoreRoles = new Roles();
        $restoreRoles = Roles::find();
        foreach ($restoreRoles as $key => $value) {
            $acl->addRole($value->role);
        }
        return $acl;
    }

    /**
     * restorePermissionsFromDB gets called when during creation of acl files some permission data is found in db
     *
     * @param [cache] $acl
     * @return srting
     */
    public function restorePermissionsFromDB($acl)
    {
        $restorePermissions = new Permissions();
        $restorePermissions = Permissions::find();
        foreach ($restorePermissions as $key => $value) {
            $acl->addComponent(
                $value->controller,
                [
                    $value->action,
                ]
            );
            $acl->allow($value->role, $value->controller, $value->action);
        }
        return $acl;
    }
}
