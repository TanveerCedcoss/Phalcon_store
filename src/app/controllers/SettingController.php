<?php

use Phalcon\Mvc\Controller;

/**
 * SettingController class is basically for defining/storing the default values for some properties
 * @package Setting
 * @author Tanveer <tanveer@cedcoss.com>
 */
class SettingController extends Controller
{
    public function indexAction()
    {
        $this->response->redirect('setting/save');
    }
    /**
     * saveAction updates the values of setting params in db
     *
     * @return void
     */
    public function saveAction()
    {
        $settings = new Settings();
        $this->view->values = Settings::findFirst(1);
        if ($this->request->ispost()) {
            $newSetting = Settings::findFirst(1);

            /**
             * Instantiating an object for a class which sanitise the passed values
             * and then using it to sanitizing the data recieved from the form.
             */
            $sanitize = new \App\Components\MyEscaper();
            $title_option = $sanitize->sanitize($this->request->getPost('title_option'));
            $default_price = $sanitize->sanitize($this->request->getPost('default_price'));
            $default_stock = $sanitize->sanitize($this->request->getPost('default_stock'));
            $default_zipcode = $sanitize->sanitize($this->request->getPost('default_zipcode'));

            $newSetting->title_option = $title_option;
            $newSetting->default_price = $default_price;
            $newSetting->default_stock = $default_stock;
            $newSetting->default_zipcode = $default_zipcode;

            $success = $newSetting->update();

             /**
             * Passing the message to view of this class based on whether the database operation was successful or not
             */
            $this->view->success = $success;
            print_r($success);
            if ($success) {
                $this->view->message = "Settings updated.";
            } else {
                $this->view->message = "Settings not updated due to following reason: <br>" . implode("<br>", $settings->getMessages());
            }
        }
    }
}
