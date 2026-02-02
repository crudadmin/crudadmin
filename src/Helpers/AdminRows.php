<?php

namespace Admin\Helpers;

use Admin\Eloquent\AdminModel;
use Admin\Contracts\Exports\AdminButtonExport;

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
        $this->model = $model->withAdminRows();

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
    protected function getPaginatedQuery($initialRequest, $rowsCount)
    {
        $query = $this->model->newQuery();

        //If limit is not enabled
        if ($this->limit <= 0) {
            return $query;
        }

        //If is first loading of first page and model is in reversed mode, then return last x rows.
        if ($this->page == 1 && $initialRequest && $this->model->isReversed() === true) {
            $take = $this->limit - ((ceil($rowsCount / $this->limit) * $this->limit) - $rowsCount);

            $query->offset($rowsCount - $take)->take($take);

            return $query;
        }

        $start = $this->limit * $this->page;
        $offset = $start - $this->limit;

        $query->offset($offset)->take($this->limit);

        return $query;
    }

    /*
     * Returns filtered and paginated rows from administration
     */
    public function getRowsDataQuery($query = null, $withDependencies = false)
    {
        $query = $query ?: $this->model->newQuery();

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

        return $query;
    }

    /*
     * Returns all rows with base fields
     */
    protected function setRowsResponses($rowsData, $openedFormIds = [])
    {
        $rows = [];

        foreach ($rowsData as $row) {
            if ( $this->isSingle() ) {
                $data = $row->getMutatedAdminAttributes(false, true);
            } else {
                $data = $row->getMutatedAdminAttributes(
                    true,
                    // By default this is false, but in some cases we want display response as opened row.
                    in_array($row->getKey(), $openedFormIds)
                );
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
                        'tooltip' => $button->tooltip,
                        'class' => $button->class,
                        'icon' => $button->icon,
                        'type' => $button->type,
                        'reloadAll' => $button->reloadAll,
                        'reloadRow' => $button->reloadRow,
                        'reloadChildren' => $button->reloadChildren,
                        'tooltipEncode' => $button->tooltipEncode,
                        'isMultiple' => method_exists($button, 'fireMultiple') ? true : false,
                        'isSingle' => method_exists($button, 'fire') ? true : false,
                        'isExport' => is_subclass_of($buttonClass, AdminButtonExport::class, true) ? true : false,
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

    public function returnModelData($onlyIds = [], $initialRequest, $openedFormIds = [])
    {
        try {
            $data = [
                'rows' => [],
                'count' => 0,
                'limit' => $this->limit,
                'page' => $this->page,
                'buttons' => [],
            ];

            if ( $this->skipRowsResponse($onlyIds) === false ) {
                $data['count'] = $this->getRowsDataQuery()->count();

                //Get specific id
                if ($onlyIds && count($onlyIds) > 0) {
                    $query = $this->model->newQuery()->whereIn($this->model->fixAmbiguousColumn($this->model->getKeyName()), $onlyIds);
                } else {
                    $query = $this->getPaginatedQuery($initialRequest, $data['count']);
                }

                $paginatedRows = $this->getRowsDataQuery($query, true)->get();

                $data['rows'] = $this->setRowsResponses($paginatedRows, $openedFormIds);

                $data['buttons'] = $this->generateButtonsProperties($paginatedRows);
            }

            return $data;
        } catch (\Illuminate\Database\QueryException $e) {
            autoAjax()->mysqlError($e)->throw();
        }
    }

    private function skipRowsResponse(array $onlyIds)
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
