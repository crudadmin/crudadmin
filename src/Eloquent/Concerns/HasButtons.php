<?php

namespace Admin\Eloquent\Concerns;

use Admin\Admin\Buttons\RemoveRow;
use Admin\Admin\Buttons\TogglePublishRow;

trait HasButtons
{
    public function getAdminButtons()
    {
        $buttons = array_values(
            array_filter((array) $this->getProperty('buttons', []))
        );

        return array_merge($buttons, [
            TogglePublishRow::class,
            RemoveRow::class,
        ]);
    }
}
