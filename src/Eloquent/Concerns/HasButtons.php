<?php

namespace Admin\Eloquent\Concerns;

use Admin\Admin\Buttons\RemoveRow;
use Admin\Admin\Buttons\HistoryButton;
use Admin\Admin\Buttons\TogglePublishRow;
use Admin\Contracts\Exports\AdminButtonExport;

trait HasButtons
{
    public function getAdminButtons()
    {
        $buttons = array_values(
            array_filter((array) $this->getProperty('buttons', []))
        );

        $exports = array_filter(array_values(
            array_filter((array) $this->getProperty('exports', []))
        ), function($export) {
            return is_subclass_of($export, AdminButtonExport::class, true);
        });

        $buttons = array_merge($buttons, $exports);

        return array_merge($buttons, [
            HistoryButton::class,
            TogglePublishRow::class,
            RemoveRow::class,
        ]);
    }
}
