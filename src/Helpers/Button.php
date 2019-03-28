<?php

namespace Gogol\Admin\Helpers;

use Gogol\Admin\Models\Model as AdminModel;
use Gogol\Admin\Traits\FieldComponent;

class Button
{
    use FieldComponent;

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
     * Button type
     * button|action|multiple
     */
    public $type = 'button';

    /*
     * Redirect after action
     */
    public $redirect = null;

    /*
     * Redirect in new tab
     */
    public $open = false;

    /*
     * Is enabled button
     */
    public $active = true;

    /*
     * Need load all rows after action?
     */
    public $reloadAll = false;

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
        return $this->error('Metóda <strong>fire</strong> nebola nájdená.');
    }

    /**
     * Firing callback on press button for multiple items
     * @param  collection $rows
     */
    public function fireMultiple($rows)
    {
        return $this->error('Metóda <strong>fireMultiple</strong> nebola nájdená.');
    }

    /*
     * Set response message
     */
    public function message($message, $title = null, $type = 'success')
    {
        if ( $title )
            $this->message['title'] = $title;

        $this->message['message'] = $message;
        $this->message['type'] = $type;

        return $this;
    }

    /*
     * Set title separately
     */
    public function title($title)
    {
        $this->message['title'] = $title;

        return $this;
    }

    /*
     * Set error message
     */
    public function error($message, $title = null)
    {
        return $this->message($message, $title, 'danger');
    }

    /*
     * Set warning message
     */
    public function warning($message, $title = null)
    {
        return $this->message($message, $title, 'warning');
    }

    /*
     * Set success message
     */
    public function success($message, $title = null)
    {
        return $this->message($message, $title, 'success');
    }

    /*
     * Set redirect
     */
    public function redirect($redirect)
    {
        $this->redirect = $redirect;

        return $this;
    }

    /*
     * Set redirect in new tab
     */
    public function open($redirect)
    {
        $this->redirect = $redirect;

        $this->open = true;

        return $this;
    }

    /*
     * Render VueJs template
     */
    public function component($template)
    {
        $this->message['component'] = $this->renderVuejs($template);

        return $this;
    }

    /*
     * Ask question with form before action
     */
    // public function ask()
    // {
    //     return $this->title('Your title...')
    //                 ->component('YoutComponent.vue');
    // }
}

?>