openapi: 3.0.0
servers:
  - url: {{ action('\Admin\Controllers\Export\ExportController@rows', $model->getTable()) }}
info:
  title: {{ $model->getProperty('name') }}
  version: 1.0.0
  contact:
    email: {{ env('MAIL_FROM_ADDRESS') ?: 'info@marekgogol.sk' }}
paths:
  /:
    get:
      summary: Fetch paginated rows from {{ $model->getProperty('name') }}
      parameters:
        - in: query
          name: columns
          description: Which columns should be returned from given scheme. Empty values is for all columns.
          required: false
          schema:
            type: string
            example: {{ implode(',', $model->getExportColumns()) }}
        - in: query
          name: limit
          description: number of records in pagination
          schema:
            type: integer
            format: int32
            minimum: 1
        - in: query
          name: with[]
          description: Fetch additional order relationships
          schema:
            type: string
          examples:
@foreach( $model->getExportRelations() as $relationKey => $relation )
            {{ $relationKey }} ({{ $relation['name'] }}):
              value: {{ $relationKey.':'.implode(',', $relation['relation']->getExportColumns()) }}
@endforeach
      responses:
        '200':
          description: search results matching criteria
          content:
            application/json:
              schema:
                type: object
                properties:
                    pagination:
                      type: object
                      properties:
                        total: { type: integer }
                        data:
                          type: array
                          items:
                            $ref: '#/components/schemas/{{ class_basename(get_class($model)) }}'
components:
  schemas:
     {{ view('admin::openapi.model_scheme', compact('model') + ['deep' => true]) }}
@foreach( collect($model->getExportRelations())->unique('table') as $relationKey => $relation )
     {{ view('admin::openapi.model_scheme', [
      'model' => $relation['relation'],
      'deep' => false,
]) }}@endforeach