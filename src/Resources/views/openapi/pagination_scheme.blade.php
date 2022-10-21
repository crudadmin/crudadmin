openapi: 3.0.0
servers:
  - url: {{ env('APP_URL') }}/admin/api
info:
  title: {{ $model->getProperty('name') }}
  version: 1.0.0
  contact:
    email: {{ $email = (env('MAIL_FROM_ADDRESS') ?: 'info@marekgogol.sk') }}
paths:
  /auth/login:
    get:
      summary: Receive authorization token
      parameters:
        - in: query
          name: email
          description: Administrator email
          required: false
          schema:
            type: string
            example: {{ $email }}
        - in: query
          name: password
          description: Password from administration
          schema:
            type: string
            example: heslo123
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
                        user:
                          $ref: '#/components/schemas/{{ class_basename(get_class(Admin::getModel('User'))) }}'
                        token:
                          type: object
                          properties:
                            token:
                              type: string
                              example: 6|QthWyWYy5IMwE8eLxdkTJNqdav4D91um060hAvm8
  /model/{{ $model->getTable() }}:
    get:
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
  /model/{{ $model->getTable() }}/{identifier}:
    get:
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
            example: {{ implode(',', $model->getExportColumns()) }}
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
    post:
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
            example: {{ implode(',', $model->getExportColumns()) }}
        - in: query
          name: _with[]
          description: Fetch additional order relationships
          schema:
            type: string
          example: Same as in listing model.
      requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              properties:
@foreach(array_intersect($model->getFillable(), $model->getExportColumns()) as $key)
@php
$field = $model->getField($key) ?? [];
@endphp
                {{ $key }}:
                  type: {{ $model->getExportFieldType($key) }}
@if ( $name = $model->getExportFieldName($key) )
                  description: {{ $name }}
@if ( $model->isFieldType($key, 'file') )
                  format: binary
@endif
@endif
@endforeach
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
components:
  securitySchemes:
    bearerAuth:
      type: http
      scheme: bearer
  schemas:
     {{ view('admin::openapi.model_scheme', compact('model') + ['deep' => true]) }}
     {{ view('admin::openapi.model_scheme', ['model' => Admin::getModel('User'), 'deep' => false]) }}
@foreach( collect($model->getExportRelations())->unique('table') as $relationKey => $relation )
     {{ view('admin::openapi.model_scheme', [
      'model' => $relation['relation'],
      'deep' => false,
]) }}@endforeach