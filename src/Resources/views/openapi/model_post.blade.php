requestBody:
        content:
          multipart/form-data:
            schema:
              type: object
              required: {{ implode(',', array_keys(array_filter($model->getFields(), function($field){
                return ($field['required'] ?? false) && !($field['default'] ?? null);
              }))) }}
              properties:
@foreach(array_unique(array_intersect($model->getFillable(), $model->getApiColumns())) as $key)
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