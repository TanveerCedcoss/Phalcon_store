<?php

use Phalcon\Mvc\Controller;
use Phalcon\Logger;
use Phalcon\Logger\Adapter\Stream;

/**
 * ProductController class is responsible for all the operations related to the Products
 * @package Product
 * @author Tanveer <tanveer@cedcoss.com>
 */
class ProductController extends Controller
{
     /**
     * indexAction is only here to redirect the user to View action of Product controller if no action is provided
     *
     * @return void
     */
    public function indexAction()
    {
        $this->response->redirect('product/view');
    }

    /**
     * addAction add new product to database
     * and also assign value to $message accordingly
     * @return void
     */
    public function addAction()
    {
        $adapter = new Stream('../app/logs/product.log');
        $logger  = new Logger(
            'messages',
            [
                'main' => $adapter,
            ]
        );
        $product = new Products();
        
        if ($this->request->ispost()) {
             /**
             * Instantiating an object for a class which sanitise the passed values
             * and then using it to sanitizing the data recieved from the form.
             */
            $sanitize = new \App\Components\MyEscaper();
            $newProductData = array(
                'name' => $sanitize->sanitize($this->request->getPost('name')),
                'description' => $sanitize->sanitize($this->request->getPost('description')),
                'price' => $sanitize->sanitize($this->request->getPost('price')),
                'tags' => $sanitize->sanitize($this->request->getPost('tags')),
                'stock' => $sanitize->sanitize($this->request->getPost('stock')),
            );

            //Firing an event to provide a hook to perform any manipulation on the recieved data
            $eventsManager = $this->di->get('EventsManager');
            $newProductDataAfterEvent = $eventsManager->fire('notification:addProduct', $this, $newProductData);

            //Assigning the values and then adding those values to the database
            $product->assign(
                $newProductDataAfterEvent,
                [
                    'name',
                    'description',
                    'price',
                    'tags',
                    'stock',
                ]
            );

            $success = $product->save();

            /**
             * Passing the message to view of this class based on whether the database operation was successful or not
             */
            $this->view->success = $success;
            if ($success) {
                $this->view->message = "Product added succesfully";
            } else {
                $this->view->message = "Product not added succesfully due to following reason: <br>" . implode("<br>", $product->getMessages());
                //logging the errors that caused the operation to fail
                $logger->error(implode(' & ', $product->getMessages()));
            }
        }
    }
    /**
     * viewAction get and pass all the products to the View of this class
     *
     * @return void
     */
    public function viewAction()
    {
        $this->view->products = Products::find();
    }

    public function deleteAction($id)
    {
        $delPro = Products::findFirst($id);
        $delPro->delete();
        $this->response->redirect('product/view');
    }
}
