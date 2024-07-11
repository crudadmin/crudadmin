<?php

namespace Admin\Helpers;

use Admin\Eloquent\AdminModel;

class AdminRows
{
    protected $model = null;
    protected $parentTable;
    protected $parentId;
    protected $limit;
    protected $page;
    protected $languageId;
    protected $scopes;
    protected $search;

    /**
     * Class constructor
     *
     * @param  Admin\Eloquent\AdminModel  $model
     */
    public function __construct(AdminModel $model, $request = null)
    {
        $this->model = $model->getAdminRows();

        if ( $request ){
            $this->loadRequestParams($request);
        }
    }

    public function loadRequestParams($request)
    {
        $this->parentTable = $request['parentTable'] ?? null;
        $this->parentId = $request['parentId'] ?? null;
        $this->limit = (int)$request['limit'];
        $this->page = (int)$request['page'];
        $this->languageId = (int)$request['language_id'];
        $this->scopes = $request['scopes'] ?? null;
        $this->search = $request['search'] ?? null;
    }

    public function setPage($page)
    {
        $this->page = $page;

        return $this;
    }

    private function isSingle()
    {
        return $this->model->getProperty('inParent') || $this->model->getProperty('single');
    }

    /*
     * Apply pagination for given eloqment builder
     */
    protected function paginateRecords($query, $initialRequest)
    {
        //If limit is not enabled
        if ($this->limit <= 0) {
            return;
        }

        //If is first loading of first page and model is in reversed mode, then return last x rows.
        if ($this->page == 1 && $initialRequest && $this->model->isReversed() === true) {
            $count = $query->count();
            $take = $this->limit - ((ceil($count / $this->limit) * $this->limit) - $count);

            $query->offset($count - $take)->take($take);

            return;
        }

        $start = $this->limit * $this->page;
        $offset = $start - $this->limit;

        $query->offset($offset)->take($this->limit);
    }

    /*
     * Returns filtered and paginated rows from administration
     */
    private function getRowsDataQuery($callback = null, $withDependencies = false)
    {
        $query = $this->model->newQuery();

        if ( $withDependencies === true ) {
            $query->withFieldRelations();
        }

        //Filter by localization
        if ($this->languageId > 0) {
            $query->localization($this->languageId);
        }

        //Filter rows by language id and parent id
        $query->filterByParent($this->parentId, $this->parentTable);

        //Filter by scopes
        $query->filterByScopes($this->scopes);

        //Filter by fields Group::where clausule
        $query->filterByParentGroup();

        //With history count
        $query->withHistoryChangesCount();

        //Search in rows
        (new AdminRowsSearch($this->model, $query, $this->search))->filter();

        if (is_callable($callback)) {
            call_user_func_array($callback, [$query]);
        }

        return $query;
    }

    /*
     * Returns all rows with base fields
     */
    protected function getRows($rowsData)
    {
        $rows = [];

        foreach ($rowsData as $row) {
            if ( $this->isSingle() ) {
                $data = $row->getMutatedAdminAttributes(false, true);
            } else {
                $data = $row->getMutatedAdminAttributes(true);
            }

            $rows[] = $data;
        }

        return $rows;
    }

    /*
     * Generate button
     */
    protected function generateButton($row)
    {
        if ($buttons = $this->model->getAdminButtons()) {
            $data = [];

            foreach ($buttons as $key => $buttonClass) {
                $button = new $buttonClass($row);

                if ($button->active === true) {
                    $data[$key] = [
                        'key' => self::getButtonKey($buttonClass),
                        'name' => $button->name,
                        'class' => $button->class,
                        'icon' => $button->icon,
                        'type' => $button->type,
                        'reloadAll' => $button->reloadAll,
                        'tooltipEncode' => $button->tooltipEncode,
                        'action' => $button->getAction(),
                    ];
                }
            }

            return $data;
        }

        return false;
    }

    public static function getButtonKey($buttonClass)
    {
        return class_basename($buttonClass);
    }

    /*
     * Generate buttons properties for each row
     */
    public function generateButtonsProperties($rows)
    {
        $buttons = [];

        foreach ($rows as $row) {
            if ($button = $this->generateButton($row)) {
                $buttons[$row->getKey()] = $button;
            }
        }

        return $buttons;
    }

    public function returnModelData($onlyIds = [], $initialRequest)
    {
        try {
            $withoutRows = $this->returnNoData($onlyIds);

            if (! $withoutRows) {
                $paginatedRowsData = $this->getRowsDataQuery(function ($query) use ($onlyIds, $initialRequest) {
                    //Get specific id
                    if ($onlyIds && count($onlyIds) > 0) {
                        $query->whereIn($this->model->fixAmbiguousColumn($this->model->getKeyName()), $onlyIds);
                    } else {
                        $this->paginateRecords($query, $initialRequest);
                    }
                }, true)->get();

                $totalResultsCount = $this->getRowsDataQuery();
            }

            $data = [
                'rows' => $withoutRows ? [] : $this->getRows($paginatedRowsData),
                'count' => $withoutRows ? 0 : $totalResultsCount->count(),
                'limit' => $this->limit,
                'page' => $this->page,
                'buttons' => $withoutRows ? [] : $this->generateButtonsProperties($paginatedRowsData),
            ];
        } catch (\Illuminate\Database\QueryException $e) {
            autoAjax()->mysqlError($e)->throw();
        }

        return $data;
    }

    private function returnNoData(array $onlyIds)
    {
        if ( admin()->hasAccess($this->model, 'read') === false ){
            return true;
        }

        if ( $this->limit === 0 ){
            return true;
        }

        //We want retrieve only specific rows, we can allow get this rows.
        if ( count($onlyIds) > 0 ){
            return false;
        }

        //We cant return data when listing in non existing parent
        if ( $this->parentTable && !$this->parentId ){
            return true;
        }

        return false;
    }
}
