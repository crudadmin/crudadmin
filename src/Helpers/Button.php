<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Model as AdminModel;

class Button
{
    /*
     * Button row
     */
    protected $row;

    /*
     * Name of button
     */
    public $name = 'Test button';

    /*
     * Button classes
     */
    public $class = 'btn-default';

    /*
     * Button Icon
     */
    public $icon = 'fa-gift';

    /*
     * Redirect after action
     */
    public $redirect = null;

    /*
     * Title
     */
    public $message = [
        'type' => null,
        'title' => null,
        'message' => null,
    ];

    /*
     * Bind button
     */
    public function __construct(AdminModel $row)
    {
        $this->row = $row;
    }

    /*
     * Firing callback on press button
     */
    public function fire(AdminModel $row)
    {

    }

    /*
     * Set response message
     */
    public function message($message, $title = null, $type = 'warning')
    {
        $this->message['title'] = $title;
        $this->message['message'] = $message;
        $this->message['type'] = $type;
    }

    /*
     * Set error message
     */
    public function error($message, $title = null)
    {
        $this->message($message, $title, 'danger');
    }

    /*
     * Set success message
     */
    public function success($message, $title = null)
    {
        $this->message($message, $title, 'success');
    }

    /*
     * Set redirect
     */
    public function redirect($redirect)
    {
        $this->redirect = $redirect;
    }
}

?>