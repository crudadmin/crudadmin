<?php

namespace Admin\Eloquent\Concerns;

use Admin\Admin\Buttons\RemoveRow;
use Admin\Admin\Buttons\TogglePublishRow;
use Admin\Admin\Buttons\HistoryButton;

trait HasButtons
{
    public function getAdminButtons()
    {
        $buttons = array_values(
            array_filter((array) $this->getProperty('buttons', []))
        );

        return array_merge($buttons, [
            HistoryButton::class,
            TogglePublishRow::class,
            RemoveRow::class,
        ]);
    }
}
