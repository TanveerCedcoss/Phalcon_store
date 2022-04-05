<?php

use Phalcon\Mvc\Controller;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;

/**
 * OrderController class is responsible for all the actions related to Orders
 * @package Store
 * @author Tanveer <tanveer@cedcoss.com>
 */
class OrderController extends Controller
{
    /**
     * indexAction is only here to redirect the user to View action of Order controller if no action is provided
     *
     * @return void
     */
    public function indexAction()
    {
        $this->response->redirect('order/view');
    }

    /**
     * addAction add new order to database
     * and also assign value to $message accordingly(whether the action was successful or not)
     * @return void
     */
    public function addAction()
    {
        $adapter = new Stream('../app/logs/order.log');
        $logger  = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );

        //Passing product data to display all products in the dropdown
        $this->view->products = Products::find();

        $order = new Orders();
        if ($this->request->ispost()) {
            /**
             * Instantiating an object for a class which sanitise the passed values
             * and then using it to sanitizing the data recieved from the form.
             */
            $sanitize = new \App\Components\MyEscaper();
            $newOrderData = array(
                'customer_name' => $sanitize->sanitize($this->request->getPost('customer_name')),
                'customer_address' => $sanitize->sanitize($this->request->getPost('customer_address')),
                'zipcode' => $sanitize->sanitize($this->request->getPost('zipcode')),
                'product' => $sanitize->sanitize($this->request->getPost('product')),
                'quantity' => $sanitize->sanitize($this->request->getPost('quantity')),
            );

            //Firing an event to provide a hook to perform any manipulation on the recieved data
            $eventsManager = $this->di->get('EventsManager');
            $newOrderDataAfterEvent = $eventsManager->fire('notification:updateInventory', $this, $newOrderData);

            //Assigning the values and then adding those values to the database
            $order->assign(
                $newOrderDataAfterEvent,
                [
                    'customer_name',
                    'customer_address',
                    'zipcode',
                    'product',
                    'quantity',
                ]
            );

            $success = $order->save();

            /**
             * Passing the message to view of this class based on whether the database operation was successful or not
             */
            $this->view->success = $success;
            if ($success) {
                $this->view->message = "Order placed succesfully";
            } else {
                $this->view->message = "Order not placed due to following reason: <br>" . implode("<br>", $order->getMessages());
                //logging the errors that caused the operation to fail
                $logger->error(implode(' & ', $order->getMessages()));
            }
        }
    }

    /**
     * viewAction get and pass all the orders to the View of this class
     *
     * @return void
     */
    public function viewAction()
    {
        $this->view->orders = Orders::find();
    }

    /**
     * deleteAction gets the ID of the order as param and then find the order in db based on that
     *  and finally delete it from the the database and redirects to view of this controller
     * @param [num] $id
     * @return void
     */
    public function deleteAction($id)
    {
        $delOrder = Orders::findFirst($id);
        $delOrder->delete();
        $this->response->redirect('order/view');
    }
}
