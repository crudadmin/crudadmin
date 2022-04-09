<?php

namespace Admin\Admin\Rules;

use Admin;
use Admin\Eloquent\AdminModel;
use Admin\Eloquent\AdminRule;

class DeleteSitetreeSubtree extends AdminRule
{
    public function deleting(AdminModel $row)
    {
        $this->deleteRecursively($row);
    }

    private function deleteRecursively($parent)
    {
        foreach ($parent->getTree() as $row) {
            $this->deleteRecursively($row);
        }

        $parent->delete();
    }
}