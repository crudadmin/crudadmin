<?php

namespace Admin\Helpers;

use Admin\Contracts\Buttons\HasButtonsSupport;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\Concerns\VueComponent;
use Admin\Helpers\SecureDownloader;
use Illuminate\Support\Collection;

class Button
{
    use VueComponent,
        HasButtonsSupport;

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
     * Should button response be acceptable with yes/no buttons?
     */
    public $accept = false;

    /*
     * Should be tooltip encoded?
     */
    public $tooltipEncode = true;

    /*
     * Should button response return rows? Modifiable wia ->withRows(true)
     * fire() and fireMultiple() returns rows by default
     */
    public $withRows = false;

    /*
     * Which action has been fired after response
     * mutate with action() method
     */
    public $action;

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
     * Firing callback on press button for multiple items.
     * @param  collection $rows
     */
    public function fireMultiple(Collection $rows)
    {
        return $this->error('Metóda <strong>fireMultiple</strong> nebola nájdená.');
    }

    /*
     * Set response message
     */
    public function message($message, $title = null, $type = 'success', $toast = false)
    {
        if ($title) {
            $this->message['title'] = $title;
        }

        if ( $type ) {
            $this->message['type'] = $type;
        }

        $this->message['message'] = $message;
        $this->message['toast'] = $toast;

        return $this;
    }

    /*
     * Display toast message
     */
    public function toast($message, $type = null)
    {
        return $this->message($message, null, $type, true);
    }

    public function errorToast($message)
    {
        return $this->toast($message, 'error');
    }

    public function download($basepath)
    {
        $this->redirect = (new SecureDownloader($basepath))->getDownloadPath();

        return $this;
    }

    public function type($type)
    {
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
        if (! $this->message['title']) {
            $title = trans('admin::admin.warning');
        }

        return $this->message($message, $title, 'danger')->accept(false);
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
    public function component($template, $component_data = [])
    {
        $this->message['component'] = $this->renderVuejs($template);
        $this->message['component_data'] = $component_data;

        return $this;
    }

    /*
     * We can modify which action will be fired after response
     */
    public function action($action)
    {
        $this->action = $action;

        return $this;
    }

    /*
     * Allow/denny accept button in question alert
     */
    public function accept($accept)
    {
        $this->accept = $accept;

        return $this;
    }

    /*
     * We want update rows in this response
     */
    public function withRows($state)
    {
        $this->withRows = $state;

        return $this;
    }

    /*
     * Ask question with form before action
     */
    // public function question()
    // {
    //     return $this->title('Your title...')
    //                 ->component('YoutComponent.vue');
    // }
}
