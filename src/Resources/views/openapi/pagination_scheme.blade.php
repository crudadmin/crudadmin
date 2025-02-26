openapi: 3.0.0
servers:
  - url: {{ env('APP_URL') }}/admin/api
info:
  title: {{ env('APP_NAME') }}
  version: 1.0.0
  contact:
    email: {{ $email = (env('MAIL_FROM_ADDRESS') ?: 'info@marekgogol.sk') }}
paths:
  @include('admin::openapi.auth_scheme', [ 'email' => admin() ? admin()->email : $email ])

  @include('admin::openapi.models_list_scheme')

@foreach($models as $model)
  /model/{{ $model->getTable() }}:
    get:
      tags:
        - {{ $model->getProperty('name') }}
      summary: Fetch paginated rows from {{ $model->getProperty('name') }}
      security:
        - bearerAuth: []
      parameters:
        - in: query
          name: columns
          description: Which columns should be returned from given scheme. Empty values is for all columns.
          required: false
          schema:
            type: string
            example: {{ implode(',', $model->getAdminApiColumns()) }}
        - in: query
          name: limit
          description: number of records in pagination. Use -1 to disable pagination.
          schema:
            type: integer
            format: int32
            minimum: -1
          example:
            -1 or 10 or 20 ...
        - in: query
          name: with[]
          description: Fetch additional order relationships
          schema:
            type: string
@if ( count($model->getAdminApiRelations()) )
          examples:
@foreach( $model->getAdminApiRelations() as $relationKey => $relation )
            {{ $relationKey }} ({{ $relation['name'] }}):
              value: {{ $relationKey.':'.implode(',', $relation['relation']->getAdminApiColumns()) }}
@endforeach
@endif
        - in: query
          name: where[]
          description: Filter by column value.
          schema:
            type: object
            properties:
              column_name:
                type: "string"
          example:
            where[column_name]: 5
            where[id,>]: 10
            where[id,<]: 10
            where[id,<=]: 12
            where[id,>=]: 12
            where[id,in]: 1,2,3,4,5
            where[id,in,;]: 1;2;3;4;5
            where[file,notNull]:
        - in: query
          name: scope[]
          description: Filter rows by scope/preddefined filters with parameters.
          schema:
            type: object
            properties:
              scope_name:
                type: "string"
          example:
            scope[myScopeName]: 'parameterValue'
            scope[withInvoice]: 1
            scope[onlyGreen]: 0
            scope[]: 'myScopeName'
@if ( Admin::isEnabledLocalization() )
        - in: header
          name: App-Locale
          description: Optional localization header for defining response only in given language. If header is not present, all language versions will be returned under localized field.
          schema:
            type: string
          required: false
          example: {{ Localization::get()->pluck('slug')->join(' / ') }}
@endif
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
@if ( admin()->hasAccess($model, 'insert') )
    post:
      tags:
        - {{ $model->getProperty('name') }}
      summary: Insert row into {{ $model->getProperty('name') }}
      security:
        - bearerAuth: []
      @include('admin::openapi.model_post', compact('model'))
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    data:
                      type: object
                      properties:
                        row:
                          $ref: '#/components/schemas/{{ class_basename(get_class($model)) }}'
@endif
  /model/{{ $model->getTable() }}/{identifier}:
    get:
      tags:
        - {{ $model->getProperty('name') }}
      summary: Receive row from {{ $model->getProperty('name') }}
      security:
        - bearerAuth: []
      parameters:
        - in: path
          name: identifier
          schema:
            type: integer
          required: true
          description: Numeric ID of the model to get. Or any other value defined by selector query param.
        - in: query
          name: selector
          description: Defines identifier column. Default value is "id". With this property we can search eg. with order number etc...
          required: false
          schema:
            type: string
            example: id
        - in: query
          name: columns
          description: Which columns should be returned from given scheme. Empty values is for all columns.
          required: false
          schema:
            type: string
            example: {{ implode(',', $model->getAdminApiColumns()) }}
        - in: query
          name: with[]
          description: Fetch additional order relationships
          schema:
            type: string
          example: Same as in listing model.
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    data:
                      type: object
                      properties:
                        row:
                          $ref: '#/components/schemas/{{ class_basename(get_class($model)) }}'
@if ( admin()->hasAccess($model, 'update') )
    post:
      tags:
        - {{ $model->getProperty('name') }}
      summary: Update row in {{ $model->getProperty('name') }}
      security:
        - bearerAuth: []
      parameters:
        - in: path
          name: identifier
          schema:
            type: integer
          required: true
          description: Numeric ID of the model to get. Or any other value defined by selector query param.
        - in: query
          name: _selector
          description: Defines identifier column. Default value is "id". With this property we can search eg. with order number etc...
          required: false
          schema:
            type: string
            example: id
        - in: query
          name: _columns
          description: Which columns should be returned from given scheme. Empty values is for all columns.
          required: false
          schema:
            type: string
            example: {{ implode(',', $model->getAdminApiColumns()) }}
        - in: query
          name: _with[]
          description: Fetch additional order relationships
          schema:
            type: string
          example: Same as in listing model.
      @include('admin::openapi.model_post', compact('model'))
      responses:
        '200':
          description: Success response
          content:
            application/json:
              schema:
                type: object
                properties:
                    data:
                      type: object
                      properties:
                        row:
                          $ref: '#/components/schemas/{{ class_basename(get_class($model)) }}'
@endif
@endforeach
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
  schemas:
@php
$schemes = [];
@endphp
     {{ view('admin::openapi.model_scheme', ['model' => $authModel = Admin::getAuthModel(), 'deep' => false]) }}
@foreach($models as $model)
@continue($model instanceof $authModel)
@php $schemes[] = $model->getTable() @endphp
     {{ view('admin::openapi.model_scheme', compact('model') + ['deep' => true]) }}
@foreach( collect($model->getAdminApiRelations())->unique('table') as $relationKey => $relation )
@continue(in_array($relation['table'], $schemes) || $models->has($relation['table']))
@php $schemes[] = $relation['table']; @endphp
     {{ view('admin::openapi.model_scheme', [
      'model' => $relation['relation'],
      'deep' => false,
]) }}@endforeach
@endforeach